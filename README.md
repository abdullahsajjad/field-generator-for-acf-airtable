# Field Generator for Airtable to ACF

A WordPress plugin that automatically generates [Advanced Custom Fields (ACF)](https://www.advancedcustomfields.com/) field groups from your Airtable table schemas. Instead of manually creating ACF fields that mirror your Airtable columns, this plugin reads your table structure and builds the field groups for you with intelligent type mapping.

## How It Works

1. Connect to your Airtable base using a personal access token
2. Select a table from your base
3. Preview the suggested ACF field types for each Airtable column
4. Override any field type if needed
5. Choose which fields to include and assign a post type
6. Generate the ACF field group with one click

## Features

- **Intelligent Field Type Mapping** - Maps 30+ Airtable field types (text, number, select, date, attachments, linked records, etc.) to the best matching ACF field types
- **Per-Field Type Override** - Review suggested types in a preview table and override any field before generating
- **Field Selection** - Choose exactly which Airtable fields to include
- **ACF Pro Support** - Detects ACF Pro and unlocks additional field types (Gallery, Repeater, Flexible Content, Clone)
- **Post Type Assignment** - Assign generated field groups to any registered post type with flexible location rules
- **Field Group Tracking** - View, manage, and trash generated field groups directly from the plugin
- **Configurable Defaults** - Label formatting, field name prefixes, excluded fields, and group name templates
- **Airtable Metadata Enrichment** - Automatically populates select/checkbox choices, currency symbols, and rating ranges from Airtable field metadata
- **Connection Caching** - Configurable cache duration to minimize Airtable API calls

## Requirements

- WordPress 5.8+
- PHP 7.4+
- [Advanced Custom Fields](https://wordpress.org/plugins/advanced-custom-fields/) (free or Pro)

## Installation

1. Download or clone this repository into your `/wp-content/plugins/` directory
2. Activate the plugin through **Plugins** in WordPress
3. Go to **ACF Generator > Settings** and enter your Airtable API key and Base ID
4. Click **Test Connection** to verify your credentials
5. Navigate to **ACF Generator > Field Generator** to start generating field groups

### Getting an Airtable API Key

Create a personal access token at [airtable.com/create/tokens](https://airtable.com/create/tokens). The token needs the `schema.bases:read` scope for reading table schemas.

## FAQ

**Does this plugin sync data from Airtable?**
No. It only reads your Airtable table schema (field names and types) to generate ACF field definitions. It does not sync or import any record data.

**Does this require ACF Pro?**
No. It works with both the free version and ACF Pro. When Pro is detected, additional field types become available.

**Can I override the suggested field type?**
Yes. The Field Generator page shows a preview table where each field has a dropdown to change the ACF type before generating.

## License

GPLv2 or later. See [LICENSE](https://www.gnu.org/licenses/gpl-2.0.html).
