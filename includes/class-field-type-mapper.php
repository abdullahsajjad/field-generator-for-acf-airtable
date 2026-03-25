<?php
/**
 * Field Type Mapper.
 *
 * Maps Airtable field types to ACF field types and provides
 * default settings for each ACF field type.
 *
 * @package AFGFA
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class AFGFA_Field_Type_Mapper
 *
 * Handles the mapping between Airtable field types and ACF field types,
 * provides a full registry of available ACF field types grouped by category,
 * and supplies per-type default settings for field configuration.
 *
 * @since 1.0.0
 */
class AFGFA_Field_Type_Mapper {

	/**
	 * Airtable type to ACF type mapping.
	 *
	 * @since 1.0.0
	 * @var array<string, string>
	 */
	private const AIRTABLE_TYPE_MAP = array(
		'singleLineText'      => 'text',
		'multilineText'       => 'textarea',
		'richText'            => 'wysiwyg',
		'email'               => 'email',
		'url'                 => 'url',
		'phoneNumber'         => 'text',
		'number'              => 'number',
		'currency'            => 'number',
		'percent'             => 'number',
		'rating'              => 'range',
		'checkbox'            => 'true_false',
		'singleSelect'        => 'select',
		'multipleSelects'     => 'checkbox',
		'date'                => 'date_picker',
		'dateTime'            => 'date_time_picker',
		'multipleAttachments' => 'gallery',
		'multipleRecordLinks' => 'relationship',
		'formula'             => 'text',
		'rollup'              => 'text',
		'lookup'              => 'text',
		'count'               => 'number',
		'autoNumber'          => 'number',
		'duration'            => 'number',
		'barcode'             => 'text',
		'button'              => 'url',
		'createdTime'         => 'date_time_picker',
		'lastModifiedTime'    => 'date_time_picker',
		'createdBy'           => 'text',
		'lastModifiedBy'      => 'text',
		'externalSyncSource'  => 'text',
		'aiText'              => 'textarea',
	);

	/**
	 * Check whether ACF Pro is active.
	 *
	 * @since  1.0.0
	 * @return bool True if ACF Pro is available, false otherwise.
	 */
	public function is_acf_pro() {
		return class_exists( 'acf_pro' ) || defined( 'ACF_PRO' );
	}

	/**
	 * Get the default ACF field type for a given Airtable field type.
	 *
	 * Handles the special case of `multipleAttachments` which maps to
	 * `gallery` when ACF Pro is available, or `image` when using ACF free.
	 *
	 * @since  1.0.0
	 * @param  string $airtable_type The Airtable field type identifier.
	 * @return string The corresponding ACF field type, or 'text' as fallback.
	 */
	public function get_acf_type( $airtable_type ) {
		if ( ! isset( self::AIRTABLE_TYPE_MAP[ $airtable_type ] ) ) {
			return 'text';
		}

		$acf_type = self::AIRTABLE_TYPE_MAP[ $airtable_type ];

		// Gallery requires ACF Pro; fall back to image for ACF free.
		if ( 'gallery' === $acf_type && ! $this->is_acf_pro() ) {
			return 'image';
		}

		return $acf_type;
	}

	/**
	 * Get the full registry of ACF field types grouped by category.
	 *
	 * Pro-only types are excluded when ACF Pro is not active.
	 * Each entry contains a human-readable label and a boolean
	 * indicating whether the type requires ACF Pro.
	 *
	 * @since  1.0.0
	 * @return array<string, array<string, array{label: string, pro: bool}>> Grouped field types.
	 */
	public function get_acf_field_types() {
		$is_pro = $this->is_acf_pro();

		$types = array(
			'basic'      => array(
				'text'     => array(
					'label' => __( 'Text', 'field-generator-for-acf-airtable' ),
					'pro'   => false,
				),
				'textarea' => array(
					'label' => __( 'Text Area', 'field-generator-for-acf-airtable' ),
					'pro'   => false,
				),
				'number'   => array(
					'label' => __( 'Number', 'field-generator-for-acf-airtable' ),
					'pro'   => false,
				),
				'range'    => array(
					'label' => __( 'Range', 'field-generator-for-acf-airtable' ),
					'pro'   => false,
				),
				'email'    => array(
					'label' => __( 'Email', 'field-generator-for-acf-airtable' ),
					'pro'   => false,
				),
				'url'      => array(
					'label' => __( 'URL', 'field-generator-for-acf-airtable' ),
					'pro'   => false,
				),
				'password' => array(
					'label' => __( 'Password', 'field-generator-for-acf-airtable' ),
					'pro'   => false,
				),
			),
			'content'    => array(
				'image'  => array(
					'label' => __( 'Image', 'field-generator-for-acf-airtable' ),
					'pro'   => false,
				),
				'file'   => array(
					'label' => __( 'File', 'field-generator-for-acf-airtable' ),
					'pro'   => false,
				),
				'wysiwyg' => array(
					'label' => __( 'WYSIWYG Editor', 'field-generator-for-acf-airtable' ),
					'pro'   => false,
				),
				'oembed' => array(
					'label' => __( 'oEmbed', 'field-generator-for-acf-airtable' ),
					'pro'   => false,
				),
			),
			'choice'     => array(
				'select'       => array(
					'label' => __( 'Select', 'field-generator-for-acf-airtable' ),
					'pro'   => false,
				),
				'checkbox'     => array(
					'label' => __( 'Checkbox', 'field-generator-for-acf-airtable' ),
					'pro'   => false,
				),
				'radio'        => array(
					'label' => __( 'Radio Button', 'field-generator-for-acf-airtable' ),
					'pro'   => false,
				),
				'button_group' => array(
					'label' => __( 'Button Group', 'field-generator-for-acf-airtable' ),
					'pro'   => false,
				),
				'true_false'   => array(
					'label' => __( 'True / False', 'field-generator-for-acf-airtable' ),
					'pro'   => false,
				),
			),
			'relational' => array(
				'link'         => array(
					'label' => __( 'Link', 'field-generator-for-acf-airtable' ),
					'pro'   => false,
				),
				'post_object'  => array(
					'label' => __( 'Post Object', 'field-generator-for-acf-airtable' ),
					'pro'   => false,
				),
				'page_link'    => array(
					'label' => __( 'Page Link', 'field-generator-for-acf-airtable' ),
					'pro'   => false,
				),
				'relationship' => array(
					'label' => __( 'Relationship', 'field-generator-for-acf-airtable' ),
					'pro'   => false,
				),
				'taxonomy'     => array(
					'label' => __( 'Taxonomy', 'field-generator-for-acf-airtable' ),
					'pro'   => false,
				),
				'user'         => array(
					'label' => __( 'User', 'field-generator-for-acf-airtable' ),
					'pro'   => false,
				),
			),
			'jquery'     => array(
				'date_picker'      => array(
					'label' => __( 'Date Picker', 'field-generator-for-acf-airtable' ),
					'pro'   => false,
				),
				'date_time_picker' => array(
					'label' => __( 'Date Time Picker', 'field-generator-for-acf-airtable' ),
					'pro'   => false,
				),
				'time_picker'      => array(
					'label' => __( 'Time Picker', 'field-generator-for-acf-airtable' ),
					'pro'   => false,
				),
				'color_picker'     => array(
					'label' => __( 'Color Picker', 'field-generator-for-acf-airtable' ),
					'pro'   => false,
				),
				'google_map'       => array(
					'label' => __( 'Google Map', 'field-generator-for-acf-airtable' ),
					'pro'   => true,
				),
			),
			'layout'     => array(
				'message'          => array(
					'label' => __( 'Message', 'field-generator-for-acf-airtable' ),
					'pro'   => false,
				),
				'accordion'        => array(
					'label' => __( 'Accordion', 'field-generator-for-acf-airtable' ),
					'pro'   => false,
				),
				'tab'              => array(
					'label' => __( 'Tab', 'field-generator-for-acf-airtable' ),
					'pro'   => false,
				),
				'group'            => array(
					'label' => __( 'Group', 'field-generator-for-acf-airtable' ),
					'pro'   => false,
				),
				'repeater'         => array(
					'label' => __( 'Repeater', 'field-generator-for-acf-airtable' ),
					'pro'   => true,
				),
				'flexible_content' => array(
					'label' => __( 'Flexible Content', 'field-generator-for-acf-airtable' ),
					'pro'   => true,
				),
				'clone'            => array(
					'label' => __( 'Clone', 'field-generator-for-acf-airtable' ),
					'pro'   => true,
				),
			),
			'pro'        => array(
				'gallery'          => array(
					'label' => __( 'Gallery', 'field-generator-for-acf-airtable' ),
					'pro'   => true,
				),
				'repeater'         => array(
					'label' => __( 'Repeater', 'field-generator-for-acf-airtable' ),
					'pro'   => true,
				),
				'flexible_content' => array(
					'label' => __( 'Flexible Content', 'field-generator-for-acf-airtable' ),
					'pro'   => true,
				),
				'clone'            => array(
					'label' => __( 'Clone', 'field-generator-for-acf-airtable' ),
					'pro'   => true,
				),
			),
		);

		// Remove Pro-only types when ACF Pro is not active.
		if ( ! $is_pro ) {
			foreach ( $types as $category => &$fields ) {
				$fields = array_filter(
					$fields,
					function ( $field ) {
						return ! $field['pro'];
					}
				);
			}
			unset( $fields );

			// Remove empty categories (e.g. the entire 'pro' group).
			$types = array_filter( $types );
		}

		return $types;
	}

	/**
	 * Get default sub-settings for a given ACF field type.
	 *
	 * Returns the configuration array with sensible defaults that ACF
	 * expects for each field type. Unknown types return an empty array.
	 *
	 * @since  1.0.0
	 * @param  string $acf_type The ACF field type identifier.
	 * @return array<string, mixed> Default settings for the field type.
	 */
	public function get_field_type_defaults( $acf_type ) {
		$defaults = array(
			'text'              => array(
				'default_value' => '',
				'placeholder'   => '',
				'maxlength'     => '',
				'prepend'       => '',
				'append'        => '',
			),
			'textarea'          => array(
				'default_value' => '',
				'placeholder'   => '',
				'maxlength'     => '',
				'rows'          => 4,
			),
			'number'            => array(
				'default_value' => '',
				'placeholder'   => '',
				'prepend'       => '',
				'append'        => '',
				'min'           => '',
				'max'           => '',
				'step'          => '',
			),
			'range'             => array(
				'default_value' => '',
				'min'           => 0,
				'max'           => 100,
				'step'          => 1,
			),
			'email'             => array(
				'default_value' => '',
				'placeholder'   => '',
			),
			'url'               => array(
				'default_value' => '',
				'placeholder'   => '',
			),
			'password'          => array(
				'placeholder' => '',
			),
			'image'             => array(
				'return_format' => 'array',
				'preview_size'  => 'medium',
				'library'       => 'all',
				'min_width'     => '',
				'min_height'    => '',
				'max_width'     => '',
				'max_height'    => '',
				'mime_types'    => '',
			),
			'file'              => array(
				'return_format' => 'array',
				'library'       => 'all',
				'mime_types'    => '',
			),
			'wysiwyg'           => array(
				'default_value' => '',
				'tabs'          => 'all',
				'toolbar'       => 'full',
				'media_upload'  => 1,
			),
			'oembed'            => array(
				'width'  => '',
				'height' => '',
			),
			'select'            => array(
				'choices'       => array(),
				'default_value' => '',
				'allow_null'    => 0,
				'multiple'      => 0,
				'ui'            => 1,
				'ajax'          => 0,
				'return_format' => 'value',
				'placeholder'   => '',
			),
			'checkbox'          => array(
				'choices'       => array(),
				'default_value' => '',
				'layout'        => 'vertical',
				'toggle'        => 0,
				'return_format' => 'value',
			),
			'radio'             => array(
				'choices'       => array(),
				'default_value' => '',
				'layout'        => 'vertical',
				'other_choice'  => 0,
				'return_format' => 'value',
			),
			'button_group'      => array(
				'choices'       => array(),
				'default_value' => '',
				'layout'        => 'horizontal',
				'return_format' => 'value',
			),
			'true_false'        => array(
				'default_value' => 0,
				'message'       => '',
				'ui'            => 1,
				'ui_on_text'    => '',
				'ui_off_text'   => '',
			),
			'link'              => array(
				'return_format' => 'array',
			),
			'post_object'       => array(
				'post_type'     => array(),
				'taxonomy'      => array(),
				'allow_null'    => 0,
				'multiple'      => 0,
				'return_format' => 'object',
			),
			'page_link'         => array(
				'post_type'  => array(),
				'taxonomy'   => array(),
				'allow_null' => 0,
				'multiple'   => 0,
			),
			'relationship'      => array(
				'post_type'     => array(),
				'taxonomy'      => array(),
				'filters'       => array( 'search', 'post_type', 'taxonomy' ),
				'elements'      => array(),
				'min'           => '',
				'max'           => '',
				'return_format' => 'object',
			),
			'taxonomy'          => array(
				'taxonomy'      => 'category',
				'field_type'    => 'checkbox',
				'add_term'      => 1,
				'save_terms'    => 0,
				'load_terms'    => 0,
				'return_format' => 'id',
				'multiple'      => 0,
			),
			'user'              => array(
				'role'       => array(),
				'allow_null' => 0,
				'multiple'   => 0,
			),
			'date_picker'       => array(
				'display_format' => 'd/m/Y',
				'return_format'  => 'd/m/Y',
				'first_day'      => 1,
			),
			'date_time_picker'  => array(
				'display_format' => 'd/m/Y g:i a',
				'return_format'  => 'd/m/Y g:i a',
				'first_day'      => 1,
			),
			'time_picker'       => array(
				'display_format' => 'g:i a',
				'return_format'  => 'g:i a',
			),
			'color_picker'      => array(
				'default_value'  => '',
				'enable_opacity' => 0,
			),
			'message'           => array(
				'message'   => '',
				'new_lines' => 'wpautop',
				'esc_html'  => 0,
			),
			'accordion'         => array(
				'open'         => 0,
				'multi_expand' => 0,
				'endpoint'     => 0,
			),
			'tab'               => array(
				'placement' => 'top',
				'endpoint'  => 0,
			),
			'group'             => array(
				'layout'     => 'block',
				'sub_fields' => array(),
			),
			'repeater'          => array(
				'layout'       => 'table',
				'min'          => 0,
				'max'          => 0,
				'button_label' => '',
				'sub_fields'   => array(),
			),
			'flexible_content'  => array(
				'layouts'      => array(),
				'button_label' => '',
				'min'          => '',
				'max'          => '',
			),
			'gallery'           => array(
				'return_format' => 'array',
				'preview_size'  => 'medium',
				'library'       => 'all',
				'min'           => '',
				'max'           => '',
				'min_width'     => '',
				'min_height'    => '',
				'max_width'     => '',
				'max_height'    => '',
				'mime_types'    => '',
			),
			'clone'             => array(
				'clone'        => array(),
				'display'      => 'seamless',
				'layout'       => 'block',
				'prefix_label' => 0,
				'prefix_name'  => 0,
			),
		);

		if ( isset( $defaults[ $acf_type ] ) ) {
			return $defaults[ $acf_type ];
		}

		return array();
	}

	/**
	 * Enrich ACF field settings with metadata from the Airtable field definition.
	 *
	 * Populates choice lists for select and checkbox fields, sets currency
	 * symbols for currency fields, configures percent constraints, and
	 * adjusts range settings for rating fields.
	 *
	 * @since  1.0.0
	 * @param  array  $acf_settings  The current ACF field settings array.
	 * @param  string $acf_type      The resolved ACF field type.
	 * @param  object $airtable_field The raw Airtable field object with type and options.
	 * @return array The enriched ACF field settings.
	 */
	public function enrich_from_airtable( $acf_settings, $acf_type, $airtable_field ) {
		$airtable_type = isset( $airtable_field->type ) ? $airtable_field->type : '';
		$options       = isset( $airtable_field->options ) ? $airtable_field->options : null;

		// Populate choices for select and checkbox fields.
		if ( in_array( $acf_type, array( 'select', 'checkbox' ), true ) && $options && isset( $options->choices ) ) {
			$choices = array();

			foreach ( $options->choices as $choice ) {
				$name = isset( $choice->name ) ? sanitize_text_field( $choice->name ) : '';
				if ( '' !== $name ) {
					$choices[ $name ] = $name;
				}
			}

			$acf_settings['choices'] = $choices;
		}

		// Set currency symbol as prepend for currency fields.
		if ( 'currency' === $airtable_type && 'number' === $acf_type && $options && isset( $options->symbol ) ) {
			$acf_settings['prepend'] = sanitize_text_field( $options->symbol );
		}

		// Configure percent field constraints.
		if ( 'percent' === $airtable_type && 'number' === $acf_type ) {
			$acf_settings['append'] = '%';
			$acf_settings['min']    = 0;
			$acf_settings['max']    = 100;
		}

		// Adjust range settings for rating fields.
		if ( 'rating' === $airtable_type && 'range' === $acf_type ) {
			$acf_settings['min'] = 1;
			$acf_settings['max'] = ( $options && isset( $options->max ) ) ? (int) $options->max : 5;
		}

		return $acf_settings;
	}
}
