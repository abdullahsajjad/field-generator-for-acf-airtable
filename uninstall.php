<?php
/**
 * Uninstall handler.
 *
 * Fired when the plugin is deleted via the WordPress admin.
 * Removes all plugin options and transients.
 * Generated ACF field groups are preserved (user content).
 *
 * @package AFGFA
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// Delete plugin options.
delete_option( 'afgfa_settings' );
delete_option( 'afgfa_generated_groups' );
delete_option( 'afgfa_version' );

// Delete all plugin transients (no object cache alternative for wildcard transient removal).
global $wpdb;
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
$wpdb->query(
	$wpdb->prepare(
		"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
		'_transient_afgfa_%',
		'_transient_timeout_afgfa_%'
	)
);
