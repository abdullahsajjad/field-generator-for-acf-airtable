<?php
/**
 * Plugin Name: Field Generator for Airtable to ACF
 * Plugin URI:  https://wordpress.org/plugins/field-generator-for-acf-airtable/
 * Description: Generate ACF field groups automatically from Airtable table schemas with intelligent field type mapping.
 * Version:     1.0.0
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * Author:      Abdullah Sajjad
 * Author URI:  https://abdullahsajjad.dev/
 * License:     GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: field-generator-for-acf-airtable
 * Domain Path: /languages
 *
 * @package AFGFA
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'AFGFA_VERSION', '1.0.0' );
define( 'AFGFA_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'AFGFA_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'AFGFA_PLUGIN_FILE', __FILE__ );
define( 'AFGFA_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

/**
 * Autoload plugin classes.
 */
spl_autoload_register( function ( $class_name ) {
	if ( 0 !== strpos( $class_name, 'AFGFA_' ) ) {
		return;
	}

	$class_file = str_replace( '_', '-', strtolower( substr( $class_name, 6 ) ) );
	$file_path  = AFGFA_PLUGIN_DIR . 'includes/class-' . $class_file . '.php';

	if ( file_exists( $file_path ) ) {
		require_once $file_path;
	}
} );

/**
 * Initialize the plugin on plugins_loaded to ensure ACF is available.
 */
function afgfa_init() {
	if ( ! function_exists( 'acf_add_local_field_group' ) ) {
		add_action( 'admin_notices', 'afgfa_acf_missing_notice' );
		return;
	}

	$plugin = AFGFA_Plugin_Core::get_instance();
	$plugin->init();
}
add_action( 'plugins_loaded', 'afgfa_init', 20 );

/**
 * Admin notice when ACF is not active.
 */
function afgfa_acf_missing_notice() {
	if ( ! current_user_can( 'activate_plugins' ) ) {
		return;
	}
	?>
	<div class="notice notice-error">
		<p>
			<?php
			echo wp_kses_post(
				sprintf(
					/* translators: %s: plugin name */
					__( '<strong>Field Generator for Airtable to ACF</strong> requires Advanced Custom Fields to be installed and activated.', 'field-generator-for-acf-airtable' ),
				)
			);
			?>
		</p>
	</div>
	<?php
}

/**
 * Activation hook.
 */
function afgfa_activate() {
	if ( ! get_option( 'afgfa_version' ) ) {
		add_option( 'afgfa_version', AFGFA_VERSION );
	} else {
		update_option( 'afgfa_version', AFGFA_VERSION );
	}

	$defaults = array(
		'api_key'           => '',
		'base_id'           => '',
		'label_format'      => 'title_case',
		'field_group_name'  => '{table_name} Fields',
		'field_prefix'      => '',
		'excluded_fields'   => '',
		'cache_duration'    => 15,
		'batch_size'        => 25,
		'processing_method' => 'immediate',
	);

	if ( ! get_option( 'afgfa_settings' ) ) {
		add_option( 'afgfa_settings', $defaults );
	}
}
register_activation_hook( __FILE__, 'afgfa_activate' );

/**
 * Deactivation hook.
 */
function afgfa_deactivate() {
	global $wpdb;

	// Clear all plugin transients (no object cache alternative for wildcard transient removal).
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	$wpdb->query(
		$wpdb->prepare(
			"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
			'_transient_afgfa_%',
			'_transient_timeout_afgfa_%'
		)
	);

	wp_clear_scheduled_hook( 'afgfa_cleanup' );
}
register_deactivation_hook( __FILE__, 'afgfa_deactivate' );

/**
 * Add plugin action links.
 *
 * @param array $links Existing links.
 * @return array Modified links.
 */
function afgfa_plugin_action_links( $links ) {
	$plugin_links = array(
		'<a href="' . esc_url( admin_url( 'admin.php?page=afgfa-settings' ) ) . '">' . esc_html__( 'Settings', 'field-generator-for-acf-airtable' ) . '</a>',
		'<a href="' . esc_url( admin_url( 'admin.php?page=afgfa-generator' ) ) . '">' . esc_html__( 'Generator', 'field-generator-for-acf-airtable' ) . '</a>',
	);

	return array_merge( $plugin_links, $links );
}
add_filter( 'plugin_action_links_' . AFGFA_PLUGIN_BASENAME, 'afgfa_plugin_action_links' );
