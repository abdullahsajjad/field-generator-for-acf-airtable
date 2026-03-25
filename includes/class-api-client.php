<?php
/**
 * Airtable API Client Class
 *
 * Handles all communication with the Airtable REST API, including
 * authentication, request handling, response validation, and caching.
 *
 * @package ACF_Fields_Generator_For_Airtable
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class AFGFA_Api_Client
 *
 * Provides methods to interact with the Airtable API v0, including
 * listing bases/tables, retrieving records, and managing cached responses.
 *
 * @since 1.0.0
 */
class AFGFA_Api_Client {

	/**
	 * Airtable API base endpoint.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	protected $endpoint = 'https://api.airtable.com/v0';

	/**
	 * Airtable Personal Access Token.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	protected $token;

	/**
	 * Whether to skip the transient cache.
	 *
	 * @since 1.0.0
	 * @var bool
	 */
	protected $skip_cache = false;

	/**
	 * Cache duration in minutes.
	 *
	 * @since 1.0.0
	 * @var int
	 */
	protected $cache_duration = 15;

	/**
	 * Constructor.
	 *
	 * Initialises the API client with an optional token, loads cache settings
	 * from the plugin options, and checks the skip-cache constant.
	 *
	 * @since 1.0.0
	 *
	 * @param string $token Optional. Airtable Personal Access Token.
	 */
	public function __construct( $token = '' ) {
		$this->token = $token;

		$settings             = get_option( 'afgfa_settings', array() );
		$this->cache_duration = ! empty( $settings['cache_duration'] ) ? (int) $settings['cache_duration'] : 15;
		$this->skip_cache     = defined( 'AFGFA_SKIP_CACHE' ) && AFGFA_SKIP_CACHE;
	}

	/**
	 * Set the API token.
	 *
	 * @since 1.0.0
	 *
	 * @param string $token Airtable Personal Access Token.
	 */
	public function set_token( $token ) {
		$this->token = $token;
	}

	/**
	 * Test the connection to an Airtable base.
	 *
	 * Attempts to retrieve the list of tables for a given base and returns
	 * a success/failure result with the table count.
	 *
	 * @since 1.0.0
	 *
	 * @param string $base_id The Airtable Base ID.
	 * @return array|WP_Error Success array with message and table count, or WP_Error on failure.
	 */
	public function test_connection( $base_id ) {
		try {
			$result = $this->get_tables( $base_id, false );

			if ( is_wp_error( $result ) ) {
				return $result;
			}

			return array(
				'success'      => true,
				'message'      => __( 'Connection successful', 'field-generator-for-acf-airtable' ),
				'tables_count' => count( $result->tables ?? array() ),
			);
		} catch ( Exception $e ) {
			return new WP_Error( 'connection_failed', $e->getMessage() );
		}
	}

	/**
	 * Retrieve the list of tables for a given base.
	 *
	 * Results are cached using WordPress transients unless caching is
	 * disabled or explicitly bypassed.
	 *
	 * @since 1.0.0
	 *
	 * @param string $base_id   The Airtable Base ID.
	 * @param bool   $use_cache Optional. Whether to use the transient cache. Default true.
	 * @return object|WP_Error Decoded tables data object, or WP_Error on failure.
	 */
	public function get_tables( $base_id, $use_cache = true ) {
		if ( empty( $this->token ) ) {
			return new WP_Error( 'no_token', __( 'API token not set', 'field-generator-for-acf-airtable' ) );
		}

		if ( empty( $base_id ) ) {
			return new WP_Error( 'no_base_id', __( 'Base ID not provided', 'field-generator-for-acf-airtable' ) );
		}

		$tables         = array();
		$transient_name = sprintf( 'afgfa_tables_%s', $base_id );

		if ( $this->skip_cache ) {
			$use_cache = false;
		}

		if ( $use_cache ) {
			$tables = get_transient( $transient_name );
		}

		if ( empty( $tables ) ) {
			try {
				$tables = $this->make_api_request( "/meta/bases/$base_id/tables" );

				if ( ! is_wp_error( $tables ) ) {
					$tables = $this->process_tables_data( $tables );
					set_transient( $transient_name, $tables, (int) $this->cache_duration * MINUTE_IN_SECONDS );
				}
			} catch ( Exception $e ) {
				return new WP_Error( 'api_error', $e->getMessage() );
			}
		}

		return $tables;
	}

	/**
	 * Retrieve a single table from a base by its ID.
	 *
	 * @since 1.0.0
	 *
	 * @param string $base_id  The Airtable Base ID.
	 * @param string $table_id The Airtable Table ID.
	 * @return object|WP_Error The table object, or WP_Error on failure.
	 */
	public function get_table( $base_id, $table_id ) {
		$tables_data = $this->get_tables( $base_id );

		if ( is_wp_error( $tables_data ) ) {
			return $tables_data;
		}

		if ( empty( $tables_data->tables ) ) {
			return new WP_Error( 'no_tables', __( 'No tables found in base', 'field-generator-for-acf-airtable' ) );
		}

		foreach ( $tables_data->tables as $table ) {
			if ( $table->id === $table_id ) {
				return $table;
			}
		}

		return new WP_Error( 'table_not_found', __( 'Table not found', 'field-generator-for-acf-airtable' ) );
	}

	/**
	 * List records from a specific table.
	 *
	 * @since 1.0.0
	 *
	 * @param string $base_id  The Airtable Base ID.
	 * @param string $table_id The Airtable Table ID.
	 * @param array  $options  Optional. Query parameters for the API request.
	 * @return object|WP_Error Decoded response object, or WP_Error on failure.
	 */
	public function list_records( $base_id, $table_id, $options = array() ) {
		if ( empty( $this->token ) ) {
			return new WP_Error( 'no_token', __( 'API token not set', 'field-generator-for-acf-airtable' ) );
		}

		try {
			return $this->make_api_request( "/$base_id/$table_id", $options );
		} catch ( Exception $e ) {
			return new WP_Error( 'api_error', $e->getMessage() );
		}
	}

	/**
	 * Make an HTTP request to the Airtable API.
	 *
	 * @since 1.0.0
	 *
	 * @param string $url    Relative API endpoint path.
	 * @param array  $data   Optional. Request data (query params for GET, body for POST).
	 * @param string $method Optional. HTTP method. Default 'GET'.
	 * @return object Decoded JSON response object.
	 *
	 * @throws Exception If the request fails or the response is invalid.
	 */
	protected function make_api_request( $url, $data = array(), $method = 'GET' ) {
		$url = $this->endpoint . $url;

		if ( 'POST' === $method && ! empty( $data ) ) {
			$data = wp_json_encode( $data );

			if ( false === $data ) {
				throw new Exception( esc_html__( 'Cannot encode body in JSON', 'field-generator-for-acf-airtable' ) );
			}
		}

		$args = $this->get_request_args( array( 'body' => $data ) );

		if ( 'GET' === $method && ! empty( $data ) ) {
			$url = add_query_arg( $data, $url );
		}

		$response = 'POST' === $method ? wp_remote_post( $url, $args ) : wp_remote_get( $url, $args );

		return $this->validate_response( $response );
	}

	/**
	 * Build the request arguments array for wp_remote_* calls.
	 *
	 * @since 1.0.0
	 *
	 * @param array $args Optional. Additional arguments to merge.
	 * @return array Merged request arguments including authorization headers.
	 */
	protected function get_request_args( $args = array() ) {
		return array_merge(
			array(
				'headers' => array(
					'Authorization' => 'Bearer ' . $this->token,
					'Content-Type'  => 'application/json',
				),
				'timeout' => 15,
			),
			$args
		);
	}

	/**
	 * Validate an HTTP response from the Airtable API.
	 *
	 * @since 1.0.0
	 *
	 * @param array|WP_Error $response The response from wp_remote_get/wp_remote_post.
	 * @return object Decoded JSON response object.
	 *
	 * @throws Exception If the response is a WP_Error, a non-200 status, or invalid JSON.
	 */
	protected function validate_response( $response ) {
		if ( is_wp_error( $response ) ) {
			throw new Exception( esc_html( sprintf(
				/* translators: %s: error message from wp_remote_get/wp_remote_post */
				__( 'Airtable API: %s', 'field-generator-for-acf-airtable' ),
				$response->get_error_message()
			) ) );
		}

		$response_code = wp_remote_retrieve_response_code( $response );

		if ( 200 !== $response_code ) {
			$body = wp_remote_retrieve_body( $response );
			$data = json_decode( $body );

			if ( ! empty( $data->error ) ) {
				throw new Exception( esc_html( sprintf(
					/* translators: %s: Airtable API error message */
					__( 'Airtable API: %s', 'field-generator-for-acf-airtable' ),
					$this->get_error_message( $data )
				) ) );
			}

			throw new Exception( esc_html( sprintf(
				/* translators: %d: HTTP response code */
				__( 'Airtable API: Received HTTP Error, code %d', 'field-generator-for-acf-airtable' ),
				(int) $response_code
			) ) );
		}

		$body = wp_remote_retrieve_body( $response );
		$data = json_decode( $body );

		if ( is_null( $data ) ) {
			throw new Exception( esc_html__( 'Airtable API: Could not decode JSON response', 'field-generator-for-acf-airtable' ) );
		}

		return $data;
	}

	/**
	 * Extract a human-readable error message from an Airtable API error response.
	 *
	 * @since 1.0.0
	 *
	 * @param object $data Decoded JSON error response.
	 * @return string The error message string.
	 */
	protected function get_error_message( $data ) {
		if ( ! empty( $data->error->message ) ) {
			return $data->error->message;
		}

		if ( ! empty( $data->error->type ) ) {
			return $data->error->type;
		}

		if ( is_string( $data->error ) ) {
			return $data->error;
		}

		return __( 'Unknown error', 'field-generator-for-acf-airtable' );
	}

	/**
	 * Process raw tables data from the API response.
	 *
	 * Decodes any unicode-escaped emoji sequences in field names.
	 *
	 * @since 1.0.0
	 *
	 * @param object $tables_data The raw tables data object from the API.
	 * @return object The processed tables data object.
	 */
	protected function process_tables_data( $tables_data ) {
		if ( empty( $tables_data->tables ) ) {
			return $tables_data;
		}

		foreach ( $tables_data->tables as &$table ) {
			if ( ! empty( $table->fields ) ) {
				foreach ( $table->fields as &$field ) {
					$field->name = $this->decode_emoji( $field->name );
				}
			}
		}

		return $tables_data;
	}

	/**
	 * Decode unicode-escaped emoji sequences in a string.
	 *
	 * Converts sequences like \u1F600 into their actual UTF-8 characters.
	 *
	 * @since 1.0.0
	 *
	 * @param string $content The string potentially containing unicode escape sequences.
	 * @return string The decoded string.
	 */
	protected function decode_emoji( $content ) {
		if ( empty( $content ) ) {
			return $content;
		}

		$content = preg_replace_callback(
			'/\\\\u([0-9a-fA-F]{4})/',
			function ( $matches ) {
				return mb_convert_encoding( pack( 'H*', $matches[1] ), 'UTF-8', 'UCS-2BE' );
			},
			$content
		);

		return $content;
	}

	/**
	 * Clear the cached tables data for a specific base.
	 *
	 * @since 1.0.0
	 *
	 * @param string $base_id The Airtable Base ID.
	 * @return bool True if the transient was deleted, false otherwise.
	 */
	public function clear_cache( $base_id ) {
		$transient_name = sprintf( 'afgfa_tables_%s', $base_id );

		return delete_transient( $transient_name );
	}

	/**
	 * Get the transient cache key for a specific base.
	 *
	 * @since 1.0.0
	 *
	 * @param string $base_id The Airtable Base ID.
	 * @return string The transient cache key.
	 */
	public function get_cache_key( $base_id ) {
		return sprintf( 'afgfa_tables_%s', $base_id );
	}

	/**
	 * Check whether tables data for a base is currently cached.
	 *
	 * @since 1.0.0
	 *
	 * @param string $base_id The Airtable Base ID.
	 * @return bool True if cached data exists, false otherwise.
	 */
	public function is_cached( $base_id ) {
		$transient_name = sprintf( 'afgfa_tables_%s', $base_id );

		return false !== get_transient( $transient_name );
	}
}
