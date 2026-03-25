<?php
/**
 * AJAX Handler Class
 *
 * @package AFGFA
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Consolidated AJAX handler for all plugin actions.
 */
class AFGFA_Ajax_Handler {

	/**
	 * API client instance.
	 *
	 * @var AFGFA_Api_Client
	 */
	private $api_client;

	/**
	 * Field generator instance.
	 *
	 * @var AFGFA_Field_Generator
	 */
	private $field_generator;

	/**
	 * Field tracker instance.
	 *
	 * @var AFGFA_Field_Tracker
	 */
	private $field_tracker;

	/**
	 * Field type mapper instance.
	 *
	 * @var AFGFA_Field_Type_Mapper
	 */
	private $mapper;

	/**
	 * Constructor.
	 *
	 * @param AFGFA_Api_Client        $api_client      API client.
	 * @param AFGFA_Field_Generator   $field_generator  Field generator.
	 * @param AFGFA_Field_Tracker     $field_tracker    Field tracker.
	 * @param AFGFA_Field_Type_Mapper $mapper           Field type mapper.
	 */
	public function __construct( $api_client, $field_generator, $field_tracker, $mapper ) {
		$this->api_client      = $api_client;
		$this->field_generator = $field_generator;
		$this->field_tracker   = $field_tracker;
		$this->mapper          = $mapper;
	}

	/**
	 * Register AJAX hooks.
	 */
	public function register() {
		add_action( 'wp_ajax_afgfa_test_connection', array( $this, 'test_connection' ) );
		add_action( 'wp_ajax_afgfa_get_tables', array( $this, 'get_tables' ) );
		add_action( 'wp_ajax_afgfa_preview_fields', array( $this, 'preview_fields' ) );
		add_action( 'wp_ajax_afgfa_generate_fields', array( $this, 'generate_fields' ) );
		add_action( 'wp_ajax_afgfa_get_field_groups', array( $this, 'get_field_groups' ) );
		add_action( 'wp_ajax_afgfa_trash_field_group', array( $this, 'trash_field_group' ) );
		add_action( 'wp_ajax_afgfa_get_location_values', array( $this, 'get_location_values' ) );
	}

	/**
	 * Verify request: nonce + capability.
	 *
	 * @return void Sends JSON error and dies on failure.
	 */
	private function verify_request() {
		check_ajax_referer( 'afgfa_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array(
				'message' => __( 'You do not have permission to perform this action.', 'field-generator-for-acf-airtable' ),
			) );
		}
	}

	/**
	 * Get an API client configured with credentials from the request or saved settings.
	 *
	 * @return AFGFA_Api_Client|null Configured client or null (sends JSON error).
	 */
	private function get_configured_client() {
		$settings = get_option( 'afgfa_settings', array() );

		// phpcs:disable WordPress.Security.NonceVerification.Missing -- verified in verify_request().
		$api_key = ! empty( $_POST['api_key'] )
			? sanitize_text_field( wp_unslash( $_POST['api_key'] ) )
			: ( $settings['api_key'] ?? '' );
		$base_id = ! empty( $_POST['base_id'] )
			? sanitize_text_field( wp_unslash( $_POST['base_id'] ) )
			: ( $settings['base_id'] ?? '' );
		// phpcs:enable

		if ( empty( $api_key ) || empty( $base_id ) ) {
			wp_send_json_error( array(
				'message' => __( 'API key and Base ID are required.', 'field-generator-for-acf-airtable' ),
			) );
			return null; // Unreachable, but explicit for static analysis.
		}

		$client = new AFGFA_Api_Client( $api_key );
		return $client;
	}

	/**
	 * Test Airtable connection.
	 */
	public function test_connection() {
		$this->verify_request();

		$client  = $this->get_configured_client();
		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		$base_id = sanitize_text_field( wp_unslash( $_POST['base_id'] ?? '' ) );

		$result = $client->test_connection( $base_id );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}

		wp_send_json_success( $result );
	}

	/**
	 * Get available tables.
	 */
	public function get_tables() {
		$this->verify_request();

		$client  = $this->get_configured_client();
		$settings = get_option( 'afgfa_settings', array() );

		// phpcs:disable WordPress.Security.NonceVerification.Missing -- verified in verify_request().
		$base_id = ! empty( $_POST['base_id'] )
			? sanitize_text_field( wp_unslash( $_POST['base_id'] ) )
			: ( $settings['base_id'] ?? '' );
		// phpcs:enable

		$tables_data = $client->get_tables( $base_id );

		if ( is_wp_error( $tables_data ) ) {
			wp_send_json_error( array( 'message' => $tables_data->get_error_message() ) );
		}

		wp_send_json_success( array( 'tables' => $tables_data->tables ?? array() ) );
	}

	/**
	 * Preview fields for a table.
	 */
	public function preview_fields() {
		$this->verify_request();

		// phpcs:disable WordPress.Security.NonceVerification.Missing
		$table_id = sanitize_text_field( wp_unslash( $_POST['table_id'] ?? '' ) );
		// phpcs:enable

		if ( empty( $table_id ) ) {
			wp_send_json_error( array(
				'message' => __( 'Table ID is required.', 'field-generator-for-acf-airtable' ),
			) );
		}

		$client   = $this->get_configured_client();
		$settings = get_option( 'afgfa_settings', array() );

		// phpcs:disable WordPress.Security.NonceVerification.Missing -- verified in verify_request().
		$base_id = ! empty( $_POST['base_id'] )
			? sanitize_text_field( wp_unslash( $_POST['base_id'] ) )
			: ( $settings['base_id'] ?? '' );
		// phpcs:enable

		$table_data = $client->get_table( $base_id, $table_id );

		if ( is_wp_error( $table_data ) ) {
			wp_send_json_error( array( 'message' => $table_data->get_error_message() ) );
		}

		$preview = $this->field_generator->preview_fields( $table_data );

		wp_send_json_success( array( 'preview' => $preview ) );
	}

	/**
	 * Generate ACF field group.
	 */
	public function generate_fields() {
		$this->verify_request();

		// phpcs:disable WordPress.Security.NonceVerification.Missing
		$table_id        = sanitize_text_field( wp_unslash( $_POST['table_id'] ?? '' ) );
		$group_name      = sanitize_text_field( wp_unslash( $_POST['group_name'] ?? '' ) );
		$selected_fields = array();
		$field_overrides  = array();
		$location_rules   = array();

		if ( ! empty( $_POST['selected_fields'] ) && is_array( $_POST['selected_fields'] ) ) {
			$selected_fields = array_map( 'sanitize_text_field', wp_unslash( $_POST['selected_fields'] ) );
		}

		if ( ! empty( $_POST['field_overrides'] ) && is_array( $_POST['field_overrides'] ) ) {
			foreach ( wp_unslash( $_POST['field_overrides'] ) as $name => $type ) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
				$field_overrides[ sanitize_text_field( $name ) ] = sanitize_key( $type );
			}
		}

		if ( ! empty( $_POST['location_rules'] ) && is_array( $_POST['location_rules'] ) ) {
			$raw_rules = wp_unslash( $_POST['location_rules'] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			foreach ( $raw_rules as $group ) {
				if ( ! is_array( $group ) ) {
					continue;
				}
				$sanitized_group = array();
				foreach ( $group as $rule ) {
					if ( is_array( $rule ) ) {
						$sanitized_group[] = array(
							'param'    => sanitize_key( $rule['param'] ?? '' ),
							'operator' => in_array( $rule['operator'] ?? '==', array( '==', '!=' ), true ) ? $rule['operator'] : '==',
							'value'    => sanitize_text_field( $rule['value'] ?? '' ),
						);
					}
				}
				if ( ! empty( $sanitized_group ) ) {
					$location_rules[] = $sanitized_group;
				}
			}
		}
		// phpcs:enable

		if ( empty( $table_id ) ) {
			wp_send_json_error( array(
				'message' => __( 'Table ID is required.', 'field-generator-for-acf-airtable' ),
			) );
		}

		$client  = $this->get_configured_client();
		$settings = get_option( 'afgfa_settings', array() );

		// phpcs:disable WordPress.Security.NonceVerification.Missing -- verified in verify_request().
		$base_id = ! empty( $_POST['base_id'] )
			? sanitize_text_field( wp_unslash( $_POST['base_id'] ) )
			: ( $settings['base_id'] ?? '' );
		// phpcs:enable

		$table_data = $client->get_table( $base_id, $table_id );

		if ( is_wp_error( $table_data ) ) {
			wp_send_json_error( array( 'message' => $table_data->get_error_message() ) );
		}

		// Generate.
		$field_group = $this->field_generator->generate_field_group(
			$table_data,
			$selected_fields,
			$field_overrides,
			$location_rules,
			$group_name
		);

		if ( is_wp_error( $field_group ) ) {
			wp_send_json_error( array( 'message' => $field_group->get_error_message() ) );
		}

		// Register with ACF.
		$result = $this->field_generator->register_field_group( $field_group );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array(
				'message' => sprintf(
					/* translators: %s: error message */
					__( 'Failed to register ACF field group: %s', 'field-generator-for-acf-airtable' ),
					$result->get_error_message()
				),
			) );
		}

		// Derive a human-readable location summary for tracking.
		$location_summary = '';
		if ( ! empty( $location_rules[0][0]['param'] ) ) {
			$first = $location_rules[0][0];
			$location_summary = $first['param'] . ' ' . ( $first['operator'] ?? '==' ) . ' ' . ( $first['value'] ?? '' );
		}

		// Track the generated group.
		$this->field_tracker->store_group( array(
			'key'          => $result['field_group_key'],
			'group_id'     => $result['field_group_id'],
			'title'        => $field_group['title'],
			'field_count'  => $result['saved_field_count'],
			'source_table' => $table_id,
			'source_name'  => sanitize_text_field( $table_data->name ?? '' ),
			'post_type'    => $location_summary,
		) );

		wp_send_json_success( array(
			'message'     => __( 'ACF field group generated successfully!', 'field-generator-for-acf-airtable' ),
			'field_group' => array(
				'id'          => $result['field_group_id'],
				'key'         => $result['field_group_key'],
				'title'       => $field_group['title'],
				'field_count' => $result['saved_field_count'],
				'edit_url'    => $result['edit_url'],
				'acf_url'     => $result['acf_url'],
			),
		) );
	}

	/**
	 * Get tracked field groups.
	 */
	public function get_field_groups() {
		$this->verify_request();

		$groups = $this->field_tracker->get_all_groups();

		wp_send_json_success( array( 'groups' => $groups ) );
	}

	/**
	 * Trash a tracked field group.
	 */
	public function trash_field_group() {
		$this->verify_request();

		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		$group_key = sanitize_text_field( wp_unslash( $_POST['group_key'] ?? '' ) );

		if ( empty( $group_key ) ) {
			wp_send_json_error( array(
				'message' => __( 'Group key is required.', 'field-generator-for-acf-airtable' ),
			) );
		}

		$trashed = $this->field_tracker->trash_group( $group_key );

		if ( ! $trashed ) {
			wp_send_json_error( array(
				'message' => __( 'Field group not found.', 'field-generator-for-acf-airtable' ),
			) );
		}

		wp_send_json_success( array(
			'message' => __( 'Field group moved to trash.', 'field-generator-for-acf-airtable' ),
		) );
	}

	/**
	 * Get values for a location rule parameter.
	 *
	 * Returns the available options for the value dropdown based on
	 * the selected param (post_type, page, page_template, etc.).
	 */
	public function get_location_values() {
		$this->verify_request();

		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		$param = sanitize_key( wp_unslash( $_POST['param'] ?? '' ) );

		if ( empty( $param ) ) {
			wp_send_json_error( array( 'message' => __( 'Parameter is required.', 'field-generator-for-acf-airtable' ) ) );
		}

		$values = array();

		switch ( $param ) {
			case 'post_type':
				$post_types = get_post_types( array( 'show_ui' => true ), 'objects' );
				foreach ( $post_types as $pt ) {
					$values[ $pt->name ] = $pt->labels->singular_name;
				}
				break;

			case 'page':
				$pages = get_pages( array( 'post_status' => 'publish,draft,private', 'number' => 200 ) );
				foreach ( $pages as $page ) {
					$values[ (string) $page->ID ] = $page->post_title;
				}
				break;

			case 'page_template':
				$values['default'] = __( 'Default Template', 'field-generator-for-acf-airtable' );
				$templates = wp_get_theme()->get_page_templates();
				foreach ( $templates as $file => $name ) {
					$values[ $file ] = $name;
				}
				break;

			case 'page_parent':
				$pages = get_pages( array( 'post_status' => 'publish,draft,private', 'number' => 200 ) );
				foreach ( $pages as $page ) {
					$values[ (string) $page->ID ] = $page->post_title;
				}
				break;

			case 'post':
				$posts = get_posts( array( 'post_status' => 'any', 'numberposts' => 200 ) );
				foreach ( $posts as $p ) {
					$values[ (string) $p->ID ] = $p->post_title;
				}
				break;

			case 'post_category':
				$cats = get_categories( array( 'hide_empty' => false ) );
				foreach ( $cats as $cat ) {
					$values[ (string) $cat->term_id ] = $cat->name;
				}
				break;

			case 'post_format':
				$formats = get_post_format_strings();
				foreach ( $formats as $slug => $label ) {
					$values[ $slug ] = $label;
				}
				break;

			case 'post_taxonomy':
				$taxonomies = get_taxonomies( array( 'public' => true ), 'objects' );
				foreach ( $taxonomies as $tax ) {
					$terms = get_terms( array( 'taxonomy' => $tax->name, 'hide_empty' => false, 'number' => 50 ) );
					if ( ! is_wp_error( $terms ) ) {
						foreach ( $terms as $term ) {
							$values[ $tax->name . ':' . $term->term_id ] = $tax->labels->singular_name . ': ' . $term->name;
						}
					}
				}
				break;

			case 'current_user_role':
				$roles = wp_roles()->roles;
				foreach ( $roles as $slug => $role ) {
					$values[ $slug ] = $role['name'];
				}
				break;

			case 'options_page':
				if ( function_exists( 'acf_get_options_pages' ) ) {
					$pages = acf_get_options_pages();
					if ( $pages ) {
						foreach ( $pages as $page ) {
							$values[ $page['menu_slug'] ] = $page['page_title'];
						}
					}
				}
				break;
		}

		wp_send_json_success( array( 'values' => $values ) );
	}
}
