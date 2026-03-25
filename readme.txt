=== Field Generator for Airtable to ACF ===
Contributors: abdullahsajjad
Tags: acf, airtable, custom fields, field generator, advanced custom fields
Requires at least: 5.8
Tested up to: 6.9
Stable tag: 1.0.0
Requires PHP: 7.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Generate ACF field groups automatically from Airtable table schemas with intelligent field type mapping.

== Description ==

Field Generator for Airtable to ACF connects to your Airtable base and automatically generates Advanced Custom Fields (ACF) field groups from your table schemas.

**Features:**

* **Intelligent Field Type Mapping** — Automatically maps Airtable field types (text, number, select, date, etc.) to the appropriate ACF field types.
* **Per-Field Type Override** — Review suggested ACF types in a preview table and override any field to a different ACF type before generating.
* **Field Selection** — Choose exactly which Airtable fields to include in your ACF field group.
* **ACF Pro Support** — Detects ACF Pro and unlocks additional field types like Gallery, Repeater, Flexible Content, and Clone.
* **Post Type Assignment** — Assign generated field groups to any registered post type.
* **Field Group Tracking** — View, manage, and trash generated field groups directly from the plugin.
* **Configurable Defaults** — Set label formatting, field name prefixes, excluded fields, and group name templates.
* **Airtable Metadata Enrichment** — Automatically populates select/checkbox choices, currency symbols, and rating ranges from Airtable field metadata.
* **Connection Caching** — Configurable cache duration to minimize Airtable API calls.

**Requirements:**

* WordPress 5.8 or later
* PHP 7.4 or later
* Advanced Custom Fields (free or Pro) plugin

== Installation ==

1. Upload the `field-generator-for-acf-airtable` folder to the `/wp-content/plugins/` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Go to **ACF Generator > Settings** and enter your Airtable API key and Base ID.
4. Click **Test Connection** to verify your credentials.
5. Navigate to **ACF Generator > Field Generator** to start generating field groups.

== Frequently Asked Questions ==

= Where do I get an Airtable API key? =

Create a personal access token at [airtable.com/create/tokens](https://airtable.com/create/tokens). The token needs the `schema.bases:read` scope for reading table schemas.

= Does this plugin require ACF Pro? =

No. The plugin works with both the free version and ACF Pro. When ACF Pro is detected, additional field types (Gallery, Repeater, Flexible Content, Clone) become available.

= Will trashing a tracked field group remove the ACF fields? =

Yes. Trashing a tracked group from the plugin removes the tracking record and moves the ACF field group post to trash. Any data stored in those fields on your posts will remain in the database. You can restore the ACF field group from the WordPress trash if needed.

= Can I override the suggested field type? =

Yes. The Field Generator page shows a preview table where each field has a dropdown to override the auto-suggested ACF type before generating.

= Does this plugin sync data from Airtable? =

No. This plugin only reads your Airtable table schema (field names and types) to generate ACF field definitions. It does not sync or import any record data.

== Screenshots ==

1. Settings page with Airtable connection configuration.
2. Field Generator showing field preview with type override dropdowns.
3. Generated field groups tracking table.

== Changelog ==

= 1.0.0 =
* Initial release.
* Intelligent Airtable-to-ACF field type mapping for 30+ Airtable types.
* Per-field ACF type override with grouped dropdown.
* Field selection checkboxes.
* Post type assignment for generated field groups.
* Field group tracking with edit and trash actions.
* Settings page with connection testing, field defaults, and performance options.
* ACF Pro detection with Pro-only field types.
* Airtable metadata enrichment (choices, currency symbols, rating ranges).
* WordPress.org coding standards compliance.

== Upgrade Notice ==

= 1.0.0 =
Initial release.
