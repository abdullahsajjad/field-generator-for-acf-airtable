<?php
/**
 * Plugin Core Class
 *
 * @package AFGFA
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Main plugin orchestrator. Singleton.
 */
class AFGFA_Plugin_Core {

	/**
	 * Singleton instance.
	 *
	 * @var AFGFA_Plugin_Core|null
	 */
	private static $instance = null;

	/**
	 * Settings page instance.
	 *
	 * @var AFGFA_Settings_Page
	 */
	private $settings_page;

	/**
	 * Generator page instance.
	 *
	 * @var AFGFA_Generator_Page
	 */
	private $generator_page;

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
	 * Field type mapper instance.
	 *
	 * @var AFGFA_Field_Type_Mapper
	 */
	private $mapper;

	/**
	 * Field tracker instance.
	 *
	 * @var AFGFA_Field_Tracker
	 */
	private $field_tracker;

	/**
	 * AJAX handler instance.
	 *
	 * @var AFGFA_Ajax_Handler
	 */
	private $ajax_handler;

	/**
	 * Settings page hook suffix.
	 *
	 * @var string
	 */
	private $settings_hook;

	/**
	 * Generator page hook suffix.
	 *
	 * @var string
	 */
	private $generator_hook;

	/**
	 * Get singleton instance.
	 *
	 * @return AFGFA_Plugin_Core
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Private constructor.
	 */
	private function __construct() {}

	/**
	 * Initialize the plugin.
	 */
	public function init() {
		$this->init_components();

		add_action( 'admin_menu', array( $this, 'register_admin_menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
	}

	/**
	 * Initialize all component classes.
	 */
	private function init_components() {
		$settings = get_option( 'afgfa_settings', array() );

		// Core components.
		$this->mapper          = new AFGFA_Field_Type_Mapper();
		$this->field_tracker   = new AFGFA_Field_Tracker();
		$this->api_client      = new AFGFA_Api_Client( $settings['api_key'] ?? '' );
		$this->field_generator = new AFGFA_Field_Generator( $this->mapper );

		// Admin pages.
		$this->settings_page  = new AFGFA_Settings_Page();
		$this->generator_page = new AFGFA_Generator_Page( $this->mapper );

		// AJAX.
		$this->ajax_handler = new AFGFA_Ajax_Handler(
			$this->api_client,
			$this->field_generator,
			$this->field_tracker,
			$this->mapper
		);
		$this->ajax_handler->register();
	}

	/**
	 * Register admin menu pages.
	 */
	public function register_admin_menu() {
		// Top-level menu.
		$this->settings_hook = add_menu_page(
			__( 'ACF Generator Settings', 'field-generator-for-acf-airtable' ),
			__( 'ACF Generator', 'field-generator-for-acf-airtable' ),
			'manage_options',
			'afgfa-settings',
			array( $this->settings_page, 'render' ),
			'dashicons-database-import',
			80
		);

		// Settings submenu (re-labels the top-level item).
		add_submenu_page(
			'afgfa-settings',
			__( 'ACF Generator Settings', 'field-generator-for-acf-airtable' ),
			__( 'Settings', 'field-generator-for-acf-airtable' ),
			'manage_options',
			'afgfa-settings',
			array( $this->settings_page, 'render' )
		);

		// Generator submenu.
		$this->generator_hook = add_submenu_page(
			'afgfa-settings',
			__( 'Field Generator', 'field-generator-for-acf-airtable' ),
			__( 'Field Generator', 'field-generator-for-acf-airtable' ),
			'manage_options',
			'afgfa-generator',
			array( $this->generator_page, 'render' )
		);
	}

	/**
	 * Enqueue admin CSS and JS conditionally per page.
	 *
	 * @param string $hook Current admin page hook.
	 */
	public function enqueue_admin_assets( $hook ) {
		// Settings page.
		if ( $hook === $this->settings_hook ) {
			wp_enqueue_style(
				'afgfa-admin-settings',
				AFGFA_PLUGIN_URL . 'assets/css/admin-settings.css',
				array(),
				AFGFA_VERSION
			);

			wp_enqueue_script(
				'afgfa-admin-settings',
				AFGFA_PLUGIN_URL . 'assets/js/admin-settings.js',
				array( 'jquery' ),
				AFGFA_VERSION,
				true
			);

			wp_localize_script( 'afgfa-admin-settings', 'afgfaSettings', array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'afgfa_nonce' ),
				'strings' => array(
					'show'               => __( 'Show', 'field-generator-for-acf-airtable' ),
					'hide'               => __( 'Hide', 'field-generator-for-acf-airtable' ),
					'testing'            => __( 'Testing...', 'field-generator-for-acf-airtable' ),
					'error'              => __( 'An error occurred.', 'field-generator-for-acf-airtable' ),
					'missingCredentials' => __( 'Please enter both API key and Base ID.', 'field-generator-for-acf-airtable' ),
				),
			) );
		}

		// Generator page.
		if ( $hook === $this->generator_hook ) {
			// Use ACF's bundled Select2.
			acf_enqueue_scripts();

			wp_enqueue_style(
				'afgfa-admin-generator',
				AFGFA_PLUGIN_URL . 'assets/css/admin-generator.css',
				array( 'select2' ),
				AFGFA_VERSION
			);

			wp_enqueue_script(
				'afgfa-admin-generator',
				AFGFA_PLUGIN_URL . 'assets/js/admin-generator.js',
				array( 'jquery', 'select2' ),
				AFGFA_VERSION,
				true
			);

			wp_localize_script( 'afgfa-admin-generator', 'afgfaGenerator', array(
				'ajaxUrl'    => admin_url( 'admin-ajax.php' ),
				'adminUrl'   => admin_url(),
				'nonce'      => wp_create_nonce( 'afgfa_nonce' ),
				'fieldTypes' => $this->generator_page->get_field_types_for_js(),
				'strings'    => array(
					'selectTable'      => __( 'Select a table...', 'field-generator-for-acf-airtable' ),
					'noTables'         => __( 'No tables found. Check your settings.', 'field-generator-for-acf-airtable' ),
					'loadError'        => __( 'Failed to load tables.', 'field-generator-for-acf-airtable' ),
					'loading'          => __( 'Loading...', 'field-generator-for-acf-airtable' ),
					'loadFields'       => __( 'Load Fields', 'field-generator-for-acf-airtable' ),
					'error'            => __( 'An error occurred.', 'field-generator-for-acf-airtable' ),
					'useSuggested'     => __( 'Use Suggested', 'field-generator-for-acf-airtable' ),
					'generating'       => __( 'Generating fields...', 'field-generator-for-acf-airtable' ),
					'selectTableFirst' => __( 'Please select a table first.', 'field-generator-for-acf-airtable' ),
					'noFieldsSelected' => __( 'Please select at least one field.', 'field-generator-for-acf-airtable' ),
					'editGroup'        => __( 'Edit in ACF', 'field-generator-for-acf-airtable' ),
					'editInAcf'        => __( 'Edit in ACF', 'field-generator-for-acf-airtable' ),
					'trashGroup'       => __( 'Trash', 'field-generator-for-acf-airtable' ),
					'confirmTrash'     => __( 'Are you sure you want to trash this field group?', 'field-generator-for-acf-airtable' ),
					'noGroups'         => __( 'No field groups generated yet.', 'field-generator-for-acf-airtable' ),
					'dismiss'          => __( 'Dismiss this notice.', 'field-generator-for-acf-airtable' ),
					'isEqualTo'        => __( 'is equal to', 'field-generator-for-acf-airtable' ),
					'isNotEqualTo'     => __( 'is not equal to', 'field-generator-for-acf-airtable' ),
					'andText'          => __( 'and', 'field-generator-for-acf-airtable' ),
					'orText'           => __( 'or', 'field-generator-for-acf-airtable' ),
				),
			) );
		}
	}
}
