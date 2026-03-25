<?php
/**
 * Settings Page Class
 *
 * @package AFGFA
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles the plugin settings admin page using the WordPress Settings API.
 */
class AFGFA_Settings_Page {

	/**
	 * Option group and option name.
	 *
	 * @var string
	 */
	const OPTION_NAME = 'afgfa_settings';

	/**
	 * Settings page slug.
	 *
	 * @var string
	 */
	const PAGE_SLUG = 'afgfa-settings';

	/**
	 * Current settings.
	 *
	 * @var array
	 */
	private $settings;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->load_settings();
		add_action( 'admin_init', array( $this, 'register_settings' ) );
	}

	/**
	 * Load settings with defaults.
	 */
	private function load_settings() {
		$defaults = array(
			'api_key'            => '',
			'base_id'            => '',
			'default_field_type' => 'smart',
			'label_format'       => 'title_case',
			'field_group_name'   => '{table_name} Fields',
			'field_prefix'       => '',
			'excluded_fields'    => '',
			'cache_duration'     => 15,
			'batch_size'         => 25,
			'processing_method'  => 'immediate',
		);

		$this->settings = wp_parse_args( get_option( self::OPTION_NAME, array() ), $defaults );
	}

	/**
	 * Register settings, sections, and fields.
	 */
	public function register_settings() {
		register_setting(
			self::OPTION_NAME,
			self::OPTION_NAME,
			array(
				'sanitize_callback' => array( $this, 'sanitize_settings' ),
			)
		);

		// Connection section.
		add_settings_section(
			'afgfa_connection',
			__( 'Connection Settings', 'field-generator-for-acf-airtable' ),
			array( $this, 'render_connection_section' ),
			self::PAGE_SLUG
		);

		add_settings_field(
			'api_key',
			__( 'Airtable API Key', 'field-generator-for-acf-airtable' ),
			array( $this, 'render_api_key_field' ),
			self::PAGE_SLUG,
			'afgfa_connection'
		);

		add_settings_field(
			'base_id',
			__( 'Airtable Base ID', 'field-generator-for-acf-airtable' ),
			array( $this, 'render_base_id_field' ),
			self::PAGE_SLUG,
			'afgfa_connection'
		);

		// Field Defaults section.
		add_settings_section(
			'afgfa_field_defaults',
			__( 'Field Defaults', 'field-generator-for-acf-airtable' ),
			array( $this, 'render_field_defaults_section' ),
			self::PAGE_SLUG
		);

		add_settings_field(
			'default_field_type',
			__( 'Default Field Type', 'field-generator-for-acf-airtable' ),
			array( $this, 'render_default_field_type_field' ),
			self::PAGE_SLUG,
			'afgfa_field_defaults'
		);

		add_settings_field(
			'label_format',
			__( 'Label Format', 'field-generator-for-acf-airtable' ),
			array( $this, 'render_label_format_field' ),
			self::PAGE_SLUG,
			'afgfa_field_defaults'
		);

		add_settings_field(
			'field_group_name',
			__( 'Field Group Name Template', 'field-generator-for-acf-airtable' ),
			array( $this, 'render_field_group_name_field' ),
			self::PAGE_SLUG,
			'afgfa_field_defaults'
		);

		add_settings_field(
			'field_prefix',
			__( 'Field Prefix', 'field-generator-for-acf-airtable' ),
			array( $this, 'render_field_prefix_field' ),
			self::PAGE_SLUG,
			'afgfa_field_defaults'
		);

		// Advanced section.
		add_settings_section(
			'afgfa_advanced',
			__( 'Advanced', 'field-generator-for-acf-airtable' ),
			array( $this, 'render_advanced_section' ),
			self::PAGE_SLUG
		);

		add_settings_field(
			'excluded_fields',
			__( 'Excluded Fields', 'field-generator-for-acf-airtable' ),
			array( $this, 'render_excluded_fields_field' ),
			self::PAGE_SLUG,
			'afgfa_advanced'
		);

		add_settings_field(
			'cache_duration',
			__( 'Cache Duration', 'field-generator-for-acf-airtable' ),
			array( $this, 'render_cache_duration_field' ),
			self::PAGE_SLUG,
			'afgfa_advanced'
		);

		// Performance section.
		add_settings_section(
			'afgfa_performance',
			__( 'Performance', 'field-generator-for-acf-airtable' ),
			array( $this, 'render_performance_section' ),
			self::PAGE_SLUG
		);

		add_settings_field(
			'batch_size',
			__( 'Batch Size', 'field-generator-for-acf-airtable' ),
			array( $this, 'render_batch_size_field' ),
			self::PAGE_SLUG,
			'afgfa_performance'
		);

		add_settings_field(
			'processing_method',
			__( 'Processing Method', 'field-generator-for-acf-airtable' ),
			array( $this, 'render_processing_method_field' ),
			self::PAGE_SLUG,
			'afgfa_performance'
		);
	}

	/**
	 * Render the settings page.
	 */
	public function render() {
		include AFGFA_PLUGIN_DIR . 'views/settings-page.php';
	}

	/**
	 * Sanitize all settings input.
	 *
	 * @param array $input Raw input.
	 * @return array Sanitized settings.
	 */
	public function sanitize_settings( $input ) {
		$sanitized = array();

		$sanitized['api_key']          = sanitize_text_field( wp_unslash( $input['api_key'] ?? '' ) );
		$sanitized['base_id']          = sanitize_text_field( wp_unslash( $input['base_id'] ?? '' ) );
		$sanitized['default_field_type'] = in_array( $input['default_field_type'] ?? '', array( 'smart', 'text' ), true )
			? $input['default_field_type']
			: 'smart';
		$sanitized['label_format']     = in_array( $input['label_format'] ?? '', array( 'original', 'title_case', 'lowercase', 'uppercase' ), true )
			? $input['label_format']
			: 'title_case';
		$sanitized['field_group_name'] = sanitize_text_field( wp_unslash( $input['field_group_name'] ?? '{table_name} Fields' ) );
		$sanitized['field_prefix']     = sanitize_key( $input['field_prefix'] ?? '' );
		$sanitized['excluded_fields']  = sanitize_textarea_field( wp_unslash( $input['excluded_fields'] ?? '' ) );
		$sanitized['cache_duration']   = max( 1, min( 60, absint( $input['cache_duration'] ?? 15 ) ) );
		$sanitized['batch_size']       = max( 10, min( 50, absint( $input['batch_size'] ?? 25 ) ) );
		$sanitized['processing_method'] = in_array( $input['processing_method'] ?? '', array( 'immediate', 'batched' ), true )
			? $input['processing_method']
			: 'immediate';

		return $sanitized;
	}

	/**
	 * Get a single setting value.
	 *
	 * @param string $key     Setting key.
	 * @param mixed  $default Default value.
	 * @return mixed
	 */
	public function get_setting( $key, $default = null ) {
		return $this->settings[ $key ] ?? $default;
	}

	/**
	 * Get all settings.
	 *
	 * @return array
	 */
	public function get_settings() {
		return $this->settings;
	}

	// -------------------------------------------------------------------------
	// Section callbacks
	// -------------------------------------------------------------------------

	/**
	 * Connection section description.
	 */
	public function render_connection_section() {
		echo '<p>' . esc_html__( 'Enter your Airtable API credentials to connect.', 'field-generator-for-acf-airtable' ) . '</p>';
	}

	/**
	 * Field defaults section description.
	 */
	public function render_field_defaults_section() {
		echo '<p>' . esc_html__( 'Configure how ACF fields are generated from Airtable columns.', 'field-generator-for-acf-airtable' ) . '</p>';
	}

	/**
	 * Advanced section description.
	 */
	public function render_advanced_section() {
		echo '<p>' . esc_html__( 'Advanced configuration options.', 'field-generator-for-acf-airtable' ) . '</p>';
	}

	/**
	 * Performance section description.
	 */
	public function render_performance_section() {
		echo '<p>' . esc_html__( 'Optimize performance for large tables.', 'field-generator-for-acf-airtable' ) . '</p>';
	}

	// -------------------------------------------------------------------------
	// Field callbacks
	// -------------------------------------------------------------------------

	/**
	 * Render API key field.
	 */
	public function render_api_key_field() {
		$value = $this->settings['api_key'];
		?>
		<input type="password"
			id="afgfa-api-key"
			name="<?php echo esc_attr( self::OPTION_NAME ); ?>[api_key]"
			value="<?php echo esc_attr( $value ); ?>"
			class="regular-text" />
		<button type="button" id="afgfa-toggle-api-key" class="button">
			<?php esc_html_e( 'Show', 'field-generator-for-acf-airtable' ); ?>
		</button>
		<p class="description">
			<?php esc_html_e( 'Your Airtable personal access token.', 'field-generator-for-acf-airtable' ); ?>
		</p>
		<?php
	}

	/**
	 * Render Base ID field.
	 */
	public function render_base_id_field() {
		$value = $this->settings['base_id'];
		?>
		<input type="text"
			id="afgfa-base-id"
			name="<?php echo esc_attr( self::OPTION_NAME ); ?>[base_id]"
			value="<?php echo esc_attr( $value ); ?>"
			class="regular-text" />
		<button type="button" id="afgfa-test-connection" class="button">
			<?php esc_html_e( 'Test Connection', 'field-generator-for-acf-airtable' ); ?>
		</button>
		<span id="afgfa-connection-status"></span>
		<p class="description">
			<?php esc_html_e( 'Your Airtable base ID (starts with "app").', 'field-generator-for-acf-airtable' ); ?>
		</p>
		<?php
	}

	/**
	 * Render default field type field.
	 */
	public function render_default_field_type_field() {
		$value   = $this->settings['default_field_type'];
		$options = array(
			'smart' => __( 'Smart Select (auto-detect from Airtable type)', 'field-generator-for-acf-airtable' ),
			'text'  => __( 'Text (always use text field)', 'field-generator-for-acf-airtable' ),
		);

		foreach ( $options as $key => $label ) {
			printf(
				'<label><input type="radio" name="%s[default_field_type]" value="%s" %s /> %s</label><br>',
				esc_attr( self::OPTION_NAME ),
				esc_attr( $key ),
				checked( $value, $key, false ),
				esc_html( $label )
			);
		}
		echo '<p class="description">' . esc_html__( 'Smart Select maps each Airtable column to the best matching ACF field type. Text uses a plain text field for everything.', 'field-generator-for-acf-airtable' ) . '</p>';
	}

	/**
	 * Render label format field.
	 */
	public function render_label_format_field() {
		$value   = $this->settings['label_format'];
		$options = array(
			'original'   => __( 'Keep original', 'field-generator-for-acf-airtable' ),
			'title_case' => __( 'Title Case', 'field-generator-for-acf-airtable' ),
			'lowercase'  => __( 'lowercase', 'field-generator-for-acf-airtable' ),
			'uppercase'  => __( 'UPPERCASE', 'field-generator-for-acf-airtable' ),
		);

		foreach ( $options as $key => $label ) {
			printf(
				'<label><input type="radio" name="%s[label_format]" value="%s" %s /> %s</label><br>',
				esc_attr( self::OPTION_NAME ),
				esc_attr( $key ),
				checked( $value, $key, false ),
				esc_html( $label )
			);
		}
	}

	/**
	 * Render field group name template field.
	 */
	public function render_field_group_name_field() {
		$value = $this->settings['field_group_name'];
		?>
		<input type="text"
			name="<?php echo esc_attr( self::OPTION_NAME ); ?>[field_group_name]"
			value="<?php echo esc_attr( $value ); ?>"
			class="regular-text" />
		<p class="description">
			<?php esc_html_e( 'Use {table_name} as a placeholder for the Airtable table name.', 'field-generator-for-acf-airtable' ); ?>
		</p>
		<?php
	}

	/**
	 * Render field prefix field.
	 */
	public function render_field_prefix_field() {
		$value = $this->settings['field_prefix'];
		?>
		<input type="text"
			name="<?php echo esc_attr( self::OPTION_NAME ); ?>[field_prefix]"
			value="<?php echo esc_attr( $value ); ?>"
			class="regular-text" />
		<p class="description">
			<?php esc_html_e( 'Optional prefix for all generated field names (e.g., "at_").', 'field-generator-for-acf-airtable' ); ?>
		</p>
		<?php
	}

	/**
	 * Render excluded fields textarea.
	 */
	public function render_excluded_fields_field() {
		$value = $this->settings['excluded_fields'];
		?>
		<textarea
			name="<?php echo esc_attr( self::OPTION_NAME ); ?>[excluded_fields]"
			rows="3"
			class="large-text"><?php echo esc_textarea( $value ); ?></textarea>
		<p class="description">
			<?php esc_html_e( 'Comma-separated list of Airtable field names to always exclude.', 'field-generator-for-acf-airtable' ); ?>
		</p>
		<?php
	}

	/**
	 * Render cache duration field.
	 */
	public function render_cache_duration_field() {
		$value = $this->settings['cache_duration'];
		?>
		<input type="number"
			name="<?php echo esc_attr( self::OPTION_NAME ); ?>[cache_duration]"
			value="<?php echo esc_attr( $value ); ?>"
			min="1" max="60" class="small-text" />
		<?php esc_html_e( 'minutes', 'field-generator-for-acf-airtable' ); ?>
		<p class="description">
			<?php esc_html_e( 'How long to cache Airtable table data to reduce API calls.', 'field-generator-for-acf-airtable' ); ?>
		</p>
		<?php
	}

	/**
	 * Render batch size field.
	 */
	public function render_batch_size_field() {
		$value = $this->settings['batch_size'];
		?>
		<input type="number"
			name="<?php echo esc_attr( self::OPTION_NAME ); ?>[batch_size]"
			value="<?php echo esc_attr( $value ); ?>"
			min="10" max="50" class="small-text" />
		<?php esc_html_e( 'fields per batch', 'field-generator-for-acf-airtable' ); ?>
		<p class="description">
			<?php esc_html_e( 'Number of fields to process at once. Lower for slower servers.', 'field-generator-for-acf-airtable' ); ?>
		</p>
		<?php
	}

	/**
	 * Render processing method field.
	 */
	public function render_processing_method_field() {
		$value   = $this->settings['processing_method'];
		$options = array(
			'immediate' => __( 'Immediate (process all at once)', 'field-generator-for-acf-airtable' ),
			'batched'   => __( 'Batched (process in chunks)', 'field-generator-for-acf-airtable' ),
		);

		foreach ( $options as $key => $label ) {
			printf(
				'<label><input type="radio" name="%s[processing_method]" value="%s" %s /> %s</label><br>',
				esc_attr( self::OPTION_NAME ),
				esc_attr( $key ),
				checked( $value, $key, false ),
				esc_html( $label )
			);
		}
	}
}
