<?php
/**
 * ACF Field Generator Class
 *
 * @package AFGFA
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Generates ACF field groups from Airtable table schemas.
 */
class AFGFA_Field_Generator {

	/**
	 * Plugin settings.
	 *
	 * @var array
	 */
	private $settings;

	/**
	 * Field type mapper instance.
	 *
	 * @var AFGFA_Field_Type_Mapper
	 */
	private $mapper;

	/**
	 * Tracks used field names for uniqueness within a single generation run.
	 *
	 * @var array
	 */
	private $used_field_names = array();

	/**
	 * Constructor.
	 *
	 * @param AFGFA_Field_Type_Mapper $mapper Field type mapper instance.
	 */
	public function __construct( $mapper ) {
		$this->mapper = $mapper;
		$this->load_settings();
	}

	/**
	 * Load plugin settings.
	 */
	private function load_settings() {
		$this->settings = get_option( 'afgfa_settings', array() );
	}

	/**
	 * Generate an ACF field group from Airtable table data.
	 *
	 * @param object $table_data      Airtable table data object with fields.
	 * @param array  $selected_fields Array of Airtable field names to include. Empty = all.
	 * @param array  $field_overrides Associative array of field_name => acf_type overrides.
	 * @param array  $location_rules  ACF location rules array. Each item: array of {param, operator, value}.
	 * @param string $group_name      Custom field group name. Falls back to template if empty.
	 * @return array|WP_Error Field group array or error.
	 */
	public function generate_field_group( $table_data, $selected_fields = array(), $field_overrides = array(), $location_rules = array(), $group_name = '' ) {
		$this->load_settings();
		$this->used_field_names = array();

		if ( empty( $table_data->fields ) ) {
			return new WP_Error( 'no_fields', __( 'No fields found in table.', 'field-generator-for-acf-airtable' ) );
		}

		// Filter to selected fields only.
		$fields = $table_data->fields;
		if ( ! empty( $selected_fields ) ) {
			$fields = array_filter( $fields, function ( $field ) use ( $selected_fields ) {
				return in_array( $field->name, $selected_fields, true );
			} );
		}

		// Filter out excluded fields from settings.
		$fields = $this->filter_excluded_fields( $fields );

		if ( empty( $fields ) ) {
			return new WP_Error( 'no_valid_fields', __( 'No valid fields found after filtering.', 'field-generator-for-acf-airtable' ) );
		}

		// Build title.
		$title = ! empty( $group_name )
			? sanitize_text_field( $group_name )
			: $this->format_field_group_name( $table_data->name );

		// Build location rules.
		$location = $this->build_location_rules( $location_rules );

		$field_group = array(
			'key'                   => 'group_' . wp_generate_uuid4(),
			'title'                 => $title,
			'fields'                => array(),
			'location'              => $location,
			'menu_order'            => 0,
			'position'              => 'normal',
			'style'                 => 'default',
			'label_placement'       => 'top',
			'instruction_placement' => 'label',
			'hide_on_screen'        => '',
			'active'                => true,
			'description'           => sprintf(
				/* translators: %s: Airtable table name */
				__( 'Generated from Airtable table: %s', 'field-generator-for-acf-airtable' ),
				sanitize_text_field( $table_data->name )
			),
		);

		// Process fields.
		$processing = $this->settings['processing_method'] ?? 'immediate';

		if ( 'batched' === $processing ) {
			$field_group['fields'] = $this->process_fields_batched( $fields, $field_overrides );
		} else {
			$field_group['fields'] = $this->process_fields( $fields, $field_overrides );
		}

		return $field_group;
	}

	/**
	 * Process all fields at once.
	 *
	 * @param array $fields          Airtable fields.
	 * @param array $field_overrides Per-field ACF type overrides.
	 * @return array ACF fields array.
	 */
	private function process_fields( $fields, $field_overrides = array() ) {
		$acf_fields = array();

		foreach ( $fields as $field ) {
			$acf_field = $this->convert_field( $field, $field_overrides );
			if ( $acf_field ) {
				$acf_fields[] = $acf_field;
			}
		}

		return $acf_fields;
	}

	/**
	 * Process fields in batches.
	 *
	 * @param array $fields          Airtable fields.
	 * @param array $field_overrides Per-field ACF type overrides.
	 * @return array ACF fields array.
	 */
	private function process_fields_batched( $fields, $field_overrides = array() ) {
		$batch_size = $this->settings['batch_size'] ?? 25;
		$batches    = array_chunk( $fields, $batch_size );
		$acf_fields = array();

		foreach ( $batches as $batch ) {
			foreach ( $batch as $field ) {
				$acf_field = $this->convert_field( $field, $field_overrides );
				if ( $acf_field ) {
					$acf_fields[] = $acf_field;
				}
			}
		}

		return $acf_fields;
	}

	/**
	 * Convert a single Airtable field to ACF format.
	 *
	 * @param object $field           Airtable field object.
	 * @param array  $field_overrides Per-field ACF type overrides.
	 * @return array|null ACF field array or null to skip.
	 */
	private function convert_field( $field, $field_overrides = array() ) {
		$field_name  = $this->format_field_name( $field->name );
		$field_label = $this->format_field_label( $field->name );

		// Determine type: user override > setting-based default.
		if ( ! empty( $field_overrides[ $field->name ] ) ) {
			$acf_type = sanitize_key( $field_overrides[ $field->name ] );
		} else {
			$default_mode = $this->settings['default_field_type'] ?? 'smart';
			if ( 'text' === $default_mode ) {
				$acf_type = 'text';
			} else {
				$airtable_type = $field->type ?? 'singleLineText';
				$acf_type      = $this->mapper->get_acf_type( $airtable_type );
			}
		}

		$acf_field = array(
			'key'   => 'field_' . wp_generate_uuid4(),
			'label' => $field_label,
			'name'  => $field_name,
			'type'  => $acf_type,
		);

		// Get default settings for this ACF type.
		$type_defaults = $this->mapper->get_field_type_defaults( $acf_type );
		$acf_field     = array_merge( $acf_field, $type_defaults );

		// Enrich from Airtable metadata (choices, currency symbols, etc.).
		$acf_field = $this->mapper->enrich_from_airtable( $acf_field, $acf_type, $field );

		// Common settings.
		$acf_field['required']     = 0;
		$acf_field['instructions'] = sprintf(
			/* translators: %s: original Airtable field name */
			__( 'Source: %s', 'field-generator-for-acf-airtable' ),
			sanitize_text_field( $field->name )
		);

		return $acf_field;
	}

	/**
	 * Build ACF location rules array from user input.
	 *
	 * @param array $rules Array of rule groups. Each group is an array of {param, operator, value}.
	 * @return array ACF-formatted location array.
	 */
	private function build_location_rules( $rules ) {
		if ( empty( $rules ) || ! is_array( $rules ) ) {
			// Default: show on all posts.
			return array(
				array(
					array(
						'param'    => 'post_type',
						'operator' => '==',
						'value'    => 'post',
					),
				),
			);
		}

		$location = array();

		foreach ( $rules as $group ) {
			if ( ! is_array( $group ) ) {
				continue;
			}

			$acf_group = array();

			foreach ( $group as $rule ) {
				if ( empty( $rule['param'] ) || empty( $rule['value'] ) ) {
					continue;
				}

				$acf_group[] = array(
					'param'    => sanitize_key( $rule['param'] ),
					'operator' => in_array( $rule['operator'] ?? '==', array( '==', '!=' ), true )
						? $rule['operator']
						: '==',
					'value'    => sanitize_text_field( $rule['value'] ),
				);
			}

			if ( ! empty( $acf_group ) ) {
				$location[] = $acf_group;
			}
		}

		if ( empty( $location ) ) {
			return array(
				array(
					array(
						'param'    => 'post_type',
						'operator' => '==',
						'value'    => 'post',
					),
				),
			);
		}

		return $location;
	}

	/**
	 * Filter out excluded fields based on settings.
	 *
	 * @param array $fields Fields array.
	 * @return array Filtered fields.
	 */
	private function filter_excluded_fields( $fields ) {
		$excluded = $this->settings['excluded_fields'] ?? '';

		if ( empty( $excluded ) ) {
			return $fields;
		}

		$excluded_list = array_map( 'trim', explode( ',', strtolower( $excluded ) ) );

		return array_filter( $fields, function ( $field ) use ( $excluded_list ) {
			return ! in_array( strtolower( $field->name ), $excluded_list, true );
		} );
	}

	/**
	 * Format field group name from template.
	 *
	 * @param string $table_name Airtable table name.
	 * @return string Formatted group name.
	 */
	private function format_field_group_name( $table_name ) {
		$template = $this->settings['field_group_name'] ?? '{table_name} Fields';
		return str_replace( '{table_name}', $table_name, $template );
	}

	/**
	 * Format a unique field name (key).
	 *
	 * @param string $name Original field name.
	 * @return string Sanitized unique field name.
	 */
	private function format_field_name( $name ) {
		$prefix   = $this->settings['field_prefix'] ?? '';
		$base     = sanitize_title( $name );

		if ( '' === $base ) {
			$base = 'field';
		}

		// Convert hyphens to underscores for ACF field names.
		$base     = str_replace( '-', '_', $base );
		$proposed = $prefix . $base;
		$unique   = $proposed;
		$index    = 2;

		while ( isset( $this->used_field_names[ $unique ] ) ) {
			$unique = $proposed . '_' . $index;
			$index++;
		}

		$this->used_field_names[ $unique ] = true;

		return $unique;
	}

	/**
	 * Format field label based on settings.
	 *
	 * @param string $name Original field name.
	 * @return string Formatted label.
	 */
	private function format_field_label( $name ) {
		$format = $this->settings['label_format'] ?? 'title_case';

		switch ( $format ) {
			case 'original':
				return $name;
			case 'lowercase':
				return strtolower( str_replace( array( '_', '-' ), ' ', $name ) );
			case 'uppercase':
				return strtoupper( str_replace( array( '_', '-' ), ' ', $name ) );
			case 'title_case':
			default:
				return ucwords( str_replace( array( '_', '-' ), ' ', $name ) );
		}
	}

	/**
	 * Register a generated field group with ACF.
	 *
	 * @param array $field_group Field group array.
	 * @return array|WP_Error Result data or error.
	 */
	public function register_field_group( $field_group ) {
		if ( ! function_exists( 'acf_update_field_group' ) ) {
			return new WP_Error( 'acf_not_available', __( 'ACF is not available.', 'field-generator-for-acf-airtable' ) );
		}

		try {
			unset( $field_group['ID'] );

			foreach ( $field_group['fields'] as &$field ) {
				if ( empty( $field['key'] ) ) {
					$field['key'] = 'field_' . wp_generate_uuid4();
				}
			}

			// Prefer atomic import.
			if ( function_exists( 'acf_import_field_group' ) ) {
				$group_key = $field_group['key'];

				foreach ( $field_group['fields'] as $i => &$f ) {
					$f['parent']     = $group_key;
					$f['menu_order'] = $i;
				}

				acf_import_field_group( $field_group );

				$group_id     = null;
				$saved_fields = array();

				if ( function_exists( 'acf_get_field_group' ) ) {
					$saved = acf_get_field_group( $group_key );
					if ( is_array( $saved ) ) {
						$group_id = $saved['ID'] ?? null;
					}
				}

				if ( function_exists( 'acf_get_fields' ) ) {
					$maybe = acf_get_fields( $group_key );
					if ( is_array( $maybe ) ) {
						$saved_fields = $maybe;
					}
				}

				return array(
					'success'           => true,
					'field_group_id'    => $group_id,
					'field_group_key'   => $group_key,
					'edit_url'          => $group_id ? admin_url( 'post.php?post=' . $group_id . '&action=edit' ) : '',
					'acf_url'           => admin_url( 'edit.php?post_type=acf-field-group' ),
					'saved_field_count' => count( $saved_fields ),
				);
			}

			// Fallback to update functions.
			$saved_group = acf_update_field_group( $field_group );

			if ( ! $saved_group || is_wp_error( $saved_group ) ) {
				return new WP_Error( 'acf_save_failed', __( 'Failed to save ACF field group.', 'field-generator-for-acf-airtable' ) );
			}

			$group_id  = $saved_group['ID'] ?? null;
			$group_key = $saved_group['key'] ?? $field_group['key'];

			if ( ! function_exists( 'acf_update_field' ) ) {
				return new WP_Error( 'acf_not_available', __( 'ACF field update function is not available.', 'field-generator-for-acf-airtable' ) );
			}

			$saved_fields = array();
			foreach ( $field_group['fields'] as $i => $field ) {
				$field['parent']     = $group_key;
				$field['menu_order'] = $i;
				unset( $field['ID'] );

				$result = acf_update_field( $field );
				if ( $result && ! is_wp_error( $result ) ) {
					$saved_fields[] = $result;
				}
			}

			return array(
				'success'           => true,
				'field_group_id'    => $group_id,
				'field_group_key'   => $group_key,
				'edit_url'          => $group_id ? admin_url( 'post.php?post=' . $group_id . '&action=edit' ) : '',
				'acf_url'           => admin_url( 'edit.php?post_type=acf-field-group' ),
				'saved_field_count' => count( $saved_fields ),
			);
		} catch ( Exception $e ) {
			return new WP_Error( 'registration_exception', $e->getMessage() );
		}
	}

	/**
	 * Preview fields that would be generated.
	 *
	 * @param object $table_data Airtable table data.
	 * @return array Preview data.
	 */
	public function preview_fields( $table_data ) {
		$this->load_settings();
		$this->used_field_names = array();

		if ( empty( $table_data->fields ) ) {
			return array();
		}

		$fields  = $this->filter_excluded_fields( $table_data->fields );
		$preview = array();

		$default_mode = $this->settings['default_field_type'] ?? 'smart';

		foreach ( $fields as $field ) {
			$airtable_type = $field->type ?? 'unknown';
			$suggested     = ( 'text' === $default_mode ) ? 'text' : $this->mapper->get_acf_type( $airtable_type );

			$preview[] = array(
				'original_name'  => $field->name,
				'field_name'     => $this->format_field_name( $field->name ),
				'field_label'    => $this->format_field_label( $field->name ),
				'airtable_type'  => $airtable_type,
				'suggested_type' => $suggested,
			);
		}

		return $preview;
	}
}
