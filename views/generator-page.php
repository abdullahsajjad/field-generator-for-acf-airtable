<?php
/**
 * Generator page template.
 *
 * @package AFGFA
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="wrap afgfa-generator-wrap">
	<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

	<!-- Table Selector -->
	<div class="afgfa-section afgfa-table-selector">
		<h2><?php esc_html_e( 'Select Table', 'field-generator-for-acf-airtable' ); ?></h2>
		<div class="afgfa-table-selector-row">
			<select id="afgfa-table-select" class="regular-text">
				<option value=""><?php esc_html_e( 'Loading tables...', 'field-generator-for-acf-airtable' ); ?></option>
			</select>
			<button type="button" id="afgfa-load-fields" class="button button-secondary" disabled>
				<?php esc_html_e( 'Load Fields', 'field-generator-for-acf-airtable' ); ?>
			</button>
		</div>
	</div>

	<!-- Main two-column layout: Field Preview + Generate Sidebar -->
	<div class="afgfa-main-layout" style="display:none;">

		<!-- Field Preview Table -->
		<div class="afgfa-section afgfa-field-preview">
			<h2><?php esc_html_e( 'Field Preview', 'field-generator-for-acf-airtable' ); ?></h2>
			<p class="description">
				<?php esc_html_e( 'Review the fields below. You can override the suggested ACF type for each field and deselect fields you do not need.', 'field-generator-for-acf-airtable' ); ?>
			</p>

			<div class="afgfa-select-actions">
				<label>
					<input type="checkbox" id="afgfa-select-all" checked />
					<?php esc_html_e( 'Select All', 'field-generator-for-acf-airtable' ); ?>
				</label>
			</div>

			<div class="afgfa-table-scroll-wrap">
				<table class="wp-list-table widefat striped" id="afgfa-fields-table">
					<thead>
						<tr>
							<th class="check-column"><span class="screen-reader-text"><?php esc_html_e( 'Select', 'field-generator-for-acf-airtable' ); ?></span></th>
							<th class="column-name"><?php esc_html_e( 'Airtable Field', 'field-generator-for-acf-airtable' ); ?></th>
							<th class="column-airtable-type"><?php esc_html_e( 'Airtable Type', 'field-generator-for-acf-airtable' ); ?></th>
							<th class="column-suggested"><?php esc_html_e( 'Suggested ACF Type', 'field-generator-for-acf-airtable' ); ?></th>
							<th class="column-override"><?php esc_html_e( 'ACF Type Override', 'field-generator-for-acf-airtable' ); ?></th>
							<th class="column-preview"><?php esc_html_e( 'Field Name', 'field-generator-for-acf-airtable' ); ?></th>
						</tr>
					</thead>
					<tbody id="afgfa-fields-tbody">
					</tbody>
				</table>
			</div>
		</div>

		<!-- Generation Controls (sidebar) -->
		<div class="afgfa-section afgfa-generate-controls">
			<h2><?php esc_html_e( 'Generate', 'field-generator-for-acf-airtable' ); ?></h2>

			<!-- Field Group Name -->
			<div class="afgfa-generate-field">
				<label for="afgfa-group-name">
					<?php esc_html_e( 'Field Group Name', 'field-generator-for-acf-airtable' ); ?>
				</label>
				<input type="text" id="afgfa-group-name" class="regular-text afgfa-group-name-input" value="" placeholder="<?php esc_attr_e( 'e.g. My Custom Fields', 'field-generator-for-acf-airtable' ); ?>" />
				<p class="description"><?php esc_html_e( 'Leave blank to use the name template from settings.', 'field-generator-for-acf-airtable' ); ?></p>
			</div>

			<!-- Location Rules -->
			<div class="afgfa-generate-field afgfa-location-rules-wrap">
				<label><?php esc_html_e( 'Show this field group if', 'field-generator-for-acf-airtable' ); ?></label>

				<div id="afgfa-location-groups">
					<!-- Rule groups are built by JS -->
				</div>

				<button type="button" id="afgfa-add-rule-group" class="button">
					<?php esc_html_e( 'or', 'field-generator-for-acf-airtable' ); ?>
				</button>
			</div>

			<div class="afgfa-generate-action">
				<button type="button" id="afgfa-generate-btn" class="button button-primary button-hero">
					<?php esc_html_e( 'Generate ACF Field Group', 'field-generator-for-acf-airtable' ); ?>
				</button>
			</div>
		</div>

	</div>

	<!-- Generated Field Groups -->
	<div class="afgfa-section afgfa-tracked-groups">
		<h2><?php esc_html_e( 'Generated Field Groups', 'field-generator-for-acf-airtable' ); ?></h2>
		<table class="wp-list-table widefat fixed striped" id="afgfa-groups-table">
			<thead>
				<tr>
					<th class="column-title"><?php esc_html_e( 'Group Name', 'field-generator-for-acf-airtable' ); ?></th>
					<th class="column-fields"><?php esc_html_e( 'Fields', 'field-generator-for-acf-airtable' ); ?></th>
					<th class="column-source"><?php esc_html_e( 'Source Table', 'field-generator-for-acf-airtable' ); ?></th>
					<th class="column-post-type"><?php esc_html_e( 'Post Type', 'field-generator-for-acf-airtable' ); ?></th>
					<th class="column-created"><?php esc_html_e( 'Created', 'field-generator-for-acf-airtable' ); ?></th>
					<th class="column-actions"><?php esc_html_e( 'Actions', 'field-generator-for-acf-airtable' ); ?></th>
				</tr>
			</thead>
			<tbody id="afgfa-groups-tbody">
				<tr class="afgfa-no-groups">
					<td colspan="6"><?php esc_html_e( 'No field groups generated yet.', 'field-generator-for-acf-airtable' ); ?></td>
				</tr>
			</tbody>
		</table>
	</div>

	<!-- Progress Overlay -->
	<div id="afgfa-progress-overlay" class="afgfa-overlay" style="display:none;">
		<div class="afgfa-overlay-content">
			<h3><?php esc_html_e( 'Generating ACF Fields', 'field-generator-for-acf-airtable' ); ?></h3>
			<div class="afgfa-progress-bar">
				<div class="afgfa-progress-fill"></div>
			</div>
			<p class="afgfa-progress-status"><?php esc_html_e( 'Processing...', 'field-generator-for-acf-airtable' ); ?></p>
		</div>
	</div>
</div>
