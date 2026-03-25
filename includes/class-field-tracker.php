<?php
/**
 * Field Tracker
 *
 * Tracks generated ACF field groups and their metadata.
 *
 * @package AFGFA
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class AFGFA_Field_Tracker
 *
 * Manages the storage, retrieval, update, and deletion of generated
 * ACF field group tracking records in the WordPress options table.
 *
 * @since 1.0.0
 */
class AFGFA_Field_Tracker {

	/**
	 * Option name used to store generated group records.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	const OPTION_NAME = 'afgfa_generated_groups';

	/**
	 * Store a newly generated field group record.
	 *
	 * Appends a group entry to the tracked groups array with an automatic
	 * created timestamp.
	 *
	 * @since 1.0.0
	 *
	 * @param array $data {
	 *     Group data to store.
	 *
	 *     @type string   $key          ACF field group key.
	 *     @type int|null $group_id     WordPress post ID of the ACF field group.
	 *     @type string   $title        Field group title.
	 *     @type int      $field_count  Number of fields in the group.
	 *     @type string   $source_table Airtable table ID.
	 *     @type string   $source_name  Airtable table name.
	 *     @type string   $post_type    WordPress post type assigned.
	 * }
	 * @return true Always returns true.
	 */
	public function store_group( $data ) {
		$groups = $this->get_all_groups();

		$record = array(
			'key'          => isset( $data['key'] ) ? sanitize_text_field( $data['key'] ) : '',
			'group_id'     => isset( $data['group_id'] ) ? absint( $data['group_id'] ) : null,
			'title'        => isset( $data['title'] ) ? sanitize_text_field( $data['title'] ) : '',
			'field_count'  => isset( $data['field_count'] ) ? absint( $data['field_count'] ) : 0,
			'source_table' => isset( $data['source_table'] ) ? sanitize_text_field( $data['source_table'] ) : '',
			'source_name'  => isset( $data['source_name'] ) ? sanitize_text_field( $data['source_name'] ) : '',
			'post_type'    => isset( $data['post_type'] ) ? sanitize_key( $data['post_type'] ) : '',
			'created'      => current_time( 'mysql' ),
		);

		$groups[] = $record;

		update_option( self::OPTION_NAME, $groups );

		return true;
	}

	/**
	 * Retrieve all tracked field group records.
	 *
	 * @since 1.0.0
	 *
	 * @return array Array of group records. Empty array if no groups are tracked.
	 */
	public function get_all_groups() {
		$groups = get_option( self::OPTION_NAME, array() );

		if ( ! is_array( $groups ) ) {
			return array();
		}

		return $groups;
	}

	/**
	 * Trash a tracked field group by its key.
	 *
	 * Removes the group from the tracking option and trashes the associated
	 * ACF field group post in WordPress.
	 *
	 * @since 1.0.0
	 *
	 * @param string $group_key The ACF field group key to trash.
	 * @return bool True on success, false if the group was not found.
	 */
	public function trash_group( $group_key ) {
		$groups = $this->get_all_groups();
		$found  = false;

		foreach ( $groups as $index => $group ) {
			if ( isset( $group['key'] ) && $group['key'] === $group_key ) {
				// Trash the ACF field group post if a group_id is stored.
				if ( ! empty( $group['group_id'] ) ) {
					wp_trash_post( $group['group_id'] );
				}

				unset( $groups[ $index ] );
				$found = true;
				break;
			}
		}

		if ( ! $found ) {
			return false;
		}

		// Re-index the array to prevent gaps.
		$groups = array_values( $groups );

		update_option( self::OPTION_NAME, $groups );

		return true;
	}

	/**
	 * Check whether a group with the given key exists in tracked groups.
	 *
	 * @since 1.0.0
	 *
	 * @param string $group_key The ACF field group key to check.
	 * @return bool True if the group exists, false otherwise.
	 */
	public function group_exists( $group_key ) {
		$group = $this->get_group( $group_key );

		return null !== $group;
	}

	/**
	 * Update specific fields of a tracked group by its key.
	 *
	 * Merges the provided data into the existing group entry. Only keys
	 * present in $data will be overwritten.
	 *
	 * @since 1.0.0
	 *
	 * @param string $group_key The ACF field group key to update.
	 * @param array  $data      Associative array of fields to update.
	 * @return bool True on success, false if the group was not found.
	 */
	public function update_group( $group_key, $data ) {
		$groups = $this->get_all_groups();
		$found  = false;

		foreach ( $groups as $index => $group ) {
			if ( isset( $group['key'] ) && $group['key'] === $group_key ) {
				$groups[ $index ] = array_merge( $group, $data );
				$found            = true;
				break;
			}
		}

		if ( ! $found ) {
			return false;
		}

		update_option( self::OPTION_NAME, $groups );

		return true;
	}

	/**
	 * Retrieve a single tracked group entry by its key.
	 *
	 * @since 1.0.0
	 *
	 * @param string $group_key The ACF field group key to retrieve.
	 * @return array|null The group record array, or null if not found.
	 */
	public function get_group( $group_key ) {
		$groups = $this->get_all_groups();

		foreach ( $groups as $group ) {
			if ( isset( $group['key'] ) && $group['key'] === $group_key ) {
				return $group;
			}
		}

		return null;
	}
}
