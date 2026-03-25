<?php
/**
 * Settings page template.
 *
 * @package AFGFA
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="wrap afgfa-settings-wrap">
	<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

	<?php settings_errors(); ?>

	<form method="post" action="options.php" id="afgfa-settings-form">
		<?php
		settings_fields( AFGFA_Settings_Page::OPTION_NAME );
		do_settings_sections( AFGFA_Settings_Page::PAGE_SLUG );
		submit_button( __( 'Save Settings', 'field-generator-for-acf-airtable' ) );
		?>
	</form>
</div>
