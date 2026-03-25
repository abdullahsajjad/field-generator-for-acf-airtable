/**
 * Generator page JavaScript.
 *
 * @package AFGFA
 */
(function( $ ) {
	'use strict';

	var AfgfaGenerator = {

		fieldData: [],

		// Location rule parameter options.
		locationParams: {
			'post_type':         'Post Type',
			'post':              'Post',
			'page':              'Page',
			'page_template':     'Page Template',
			'page_parent':       'Page Parent',
			'post_category':     'Post Category',
			'post_format':       'Post Format',
			'post_taxonomy':     'Post Taxonomy',
			'current_user_role': 'Current User Role'
		},

		// Cache for loaded values per param.
		valueCache: {},

		init: function() {
			this.bindEvents();
			this.loadTables();
			this.loadFieldGroups();
		},

		bindEvents: function() {
			$( '#afgfa-table-select' ).on( 'change', this.onTableChange );
			$( '#afgfa-load-fields' ).on( 'click', this.loadFields.bind( this ) );
			$( '#afgfa-select-all' ).on( 'change', this.toggleSelectAll );
			$( '#afgfa-generate-btn' ).on( 'click', this.generateFields.bind( this ) );
			$( '#afgfa-add-rule-group' ).on( 'click', this.addRuleGroup.bind( this ) );
			$( document ).on( 'click', '.afgfa-trash-group', this.trashGroup.bind( this ) );
			$( document ).on( 'click', '.afgfa-add-rule', this.addRuleToGroup.bind( this ) );
			$( document ).on( 'click', '.afgfa-remove-rule', this.removeRule.bind( this ) );
			$( document ).on( 'change', '.afgfa-rule-param', this.onParamChange.bind( this ) );
		},

		// =====================================================================
		// Tables
		// =====================================================================

		loadTables: function() {
			var self = this;
			$.ajax( {
				url:  afgfaGenerator.ajaxUrl,
				type: 'POST',
				data: {
					action: 'afgfa_get_tables',
					nonce:  afgfaGenerator.nonce
				},
				success: function( response ) {
					if ( response.success && response.data.tables ) {
						self.populateTables( response.data.tables );
					} else {
						$( '#afgfa-table-select' )
							.empty()
							.append( $( '<option>' ).val( '' ).text( afgfaGenerator.strings.noTables ) );
					}
				},
				error: function() {
					$( '#afgfa-table-select' )
						.empty()
						.append( $( '<option>' ).val( '' ).text( afgfaGenerator.strings.loadError ) );
				}
			} );
		},

		populateTables: function( tables ) {
			var $select = $( '#afgfa-table-select' );
			$select.empty().append( $( '<option>' ).val( '' ).text( afgfaGenerator.strings.selectTable ) );

			$.each( tables, function( i, table ) {
				$select.append( $( '<option>' ).val( table.id ).text( table.name ) );
			} );
		},

		onTableChange: function() {
			var hasValue = !! $( this ).val();
			$( '#afgfa-load-fields' ).prop( 'disabled', ! hasValue );

			if ( ! hasValue ) {
				$( '.afgfa-field-preview, .afgfa-generate-controls' ).hide();
			}
		},

		// =====================================================================
		// Fields
		// =====================================================================

		loadFields: function() {
			var self    = this;
			var tableId = $( '#afgfa-table-select' ).val();
			var tableName = $( '#afgfa-table-select option:selected' ).text();

			if ( ! tableId ) {
				return;
			}

			var $btn = $( '#afgfa-load-fields' );
			$btn.prop( 'disabled', true ).text( afgfaGenerator.strings.loading );

			$.ajax( {
				url:  afgfaGenerator.ajaxUrl,
				type: 'POST',
				data: {
					action:   'afgfa_preview_fields',
					nonce:    afgfaGenerator.nonce,
					table_id: tableId
				},
				success: function( response ) {
					if ( response.success && response.data.preview ) {
						self.fieldData = response.data.preview;
						self.renderFieldsTable( response.data.preview );
						$( '.afgfa-field-preview, .afgfa-generate-controls' ).show();

						// Pre-fill group name from template with table name.
						var currentName = $( '#afgfa-group-name' ).val();
						if ( ! currentName ) {
							$( '#afgfa-group-name' ).val( tableName + ' Fields' );
						}

						// Init location rules if empty.
						if ( 0 === $( '#afgfa-location-groups .afgfa-rule-group' ).length ) {
							self.addRuleGroup();
						}
					} else {
						self.showNotice(
							response.data && response.data.message
								? response.data.message
								: afgfaGenerator.strings.error,
							'error'
						);
					}
				},
				error: function() {
					self.showNotice( afgfaGenerator.strings.error, 'error' );
				},
				complete: function() {
					$btn.prop( 'disabled', false ).text( afgfaGenerator.strings.loadFields );
				}
			} );
		},

		renderFieldsTable: function( fields ) {
			var $tbody     = $( '#afgfa-fields-tbody' );
			var fieldTypes = afgfaGenerator.fieldTypes;

			$tbody.empty();

			$.each( fields, function( i, field ) {
				var $row = $( '<tr>' );

				// Checkbox.
				$row.append(
					$( '<td>' ).addClass( 'check-column' ).append(
						$( '<input>' )
							.attr( 'type', 'checkbox' )
							.addClass( 'afgfa-field-check' )
							.val( field.original_name )
							.prop( 'checked', true )
					)
				);

				// Field name.
				$row.append( $( '<td>' ).text( field.original_name ) );

				// Airtable type badge.
				$row.append(
					$( '<td>' ).append(
						$( '<span>' )
							.addClass( 'afgfa-type-badge afgfa-badge-airtable' )
							.text( field.airtable_type )
					)
				);

				// Suggested ACF type badge.
				$row.append(
					$( '<td>' ).append(
						$( '<span>' )
							.addClass( 'afgfa-type-badge afgfa-badge-acf' )
							.text( field.suggested_type )
					)
				);

				// Override dropdown.
				var $select = $( '<select>' )
					.addClass( 'afgfa-type-override' )
					.attr( 'data-field-name', field.original_name );

				$select.append(
					$( '<option>' )
						.val( '' )
						.text( afgfaGenerator.strings.useSuggested + ' (' + field.suggested_type + ')' )
				);

				$.each( fieldTypes, function( category, types ) {
					var $group = $( '<optgroup>' ).attr( 'label', category );
					$.each( types, function( typeKey, typeData ) {
						var label = typeData.label;
						if ( typeData.pro ) {
							label += ' (Pro)';
						}
						$group.append( $( '<option>' ).val( typeKey ).text( label ) );
					} );
					$select.append( $group );
				} );

				$row.append( $( '<td>' ).append( $select ) );

				// Field name preview.
				$row.append(
					$( '<td>' ).append(
						$( '<code>' )
							.addClass( 'afgfa-field-name-preview' )
							.text( field.field_name )
					)
				);

				$tbody.append( $row );
			} );

			$( '#afgfa-select-all' ).prop( 'checked', true );
		},

		toggleSelectAll: function() {
			var checked = $( this ).prop( 'checked' );
			$( '.afgfa-field-check' ).prop( 'checked', checked );
		},

		// =====================================================================
		// Location Rules Builder
		// =====================================================================

		/**
		 * Build the param <select> element.
		 */
		buildParamSelect: function( selectedParam ) {
			var $sel = $( '<select>' ).addClass( 'afgfa-rule-param' );
			$.each( this.locationParams, function( val, label ) {
				var $opt = $( '<option>' ).val( val ).text( label );
				if ( val === selectedParam ) {
					$opt.prop( 'selected', true );
				}
				$sel.append( $opt );
			} );
			return $sel;
		},

		/**
		 * Build the operator <select>.
		 */
		buildOperatorSelect: function( selectedOp ) {
			var $sel = $( '<select>' ).addClass( 'afgfa-rule-operator' );
			$sel.append( $( '<option>' ).val( '==' ).text( afgfaGenerator.strings.isEqualTo ) );
			$sel.append( $( '<option>' ).val( '!=' ).text( afgfaGenerator.strings.isNotEqualTo ) );
			if ( selectedOp ) {
				$sel.val( selectedOp );
			}
			return $sel;
		},

		/**
		 * Build a single rule row.
		 */
		buildRuleRow: function( param, operator, value ) {
			param    = param    || 'post_type';
			operator = operator || '==';

			var $row = $( '<div>' ).addClass( 'afgfa-rule-row' );

			$row.append( this.buildParamSelect( param ) );
			$row.append( this.buildOperatorSelect( operator ) );

			// Value select (placeholder — will be loaded via AJAX).
			var $valSel = $( '<select>' ).addClass( 'afgfa-rule-value' );
			$valSel.append( $( '<option>' ).val( '' ).text( afgfaGenerator.strings.loading ) );
			$row.append( $valSel );

			$row.append(
				$( '<button>' )
					.attr( 'type', 'button' )
					.addClass( 'button afgfa-add-rule' )
					.text( afgfaGenerator.strings.andText )
			);
			$row.append(
				$( '<button>' )
					.attr( 'type', 'button' )
					.addClass( 'button afgfa-remove-rule' )
					.html( '&times;' )
			);

			// Load values for the default param.
			this.loadValuesForRow( $row, param, value );

			return $row;
		},

		/**
		 * Add a new rule group (OR group).
		 */
		addRuleGroup: function() {
			var $container = $( '#afgfa-location-groups' );
			var groupIndex = $container.children( '.afgfa-rule-group' ).length;

			// Add "or" divider if not first group.
			var $group = $( '<div>' ).addClass( 'afgfa-rule-group' ).attr( 'data-group-index', groupIndex );

			if ( groupIndex > 0 ) {
				$group.prepend( $( '<div>' ).addClass( 'afgfa-or-divider' ).text( afgfaGenerator.strings.orText ) );
			}

			$group.append( this.buildRuleRow() );
			$container.append( $group );
		},

		/**
		 * Add a rule within a group (AND).
		 */
		addRuleToGroup: function( e ) {
			var $group = $( e.currentTarget ).closest( '.afgfa-rule-group' );
			$group.append( this.buildRuleRow() );
		},

		/**
		 * Remove a rule row, and remove the group if empty.
		 */
		removeRule: function( e ) {
			var $row   = $( e.currentTarget ).closest( '.afgfa-rule-row' );
			var $group = $row.closest( '.afgfa-rule-group' );

			$row.remove();

			// If group is empty of rules, remove the group.
			if ( 0 === $group.find( '.afgfa-rule-row' ).length ) {
				$group.remove();
			}

			// If no groups remain, add one back.
			if ( 0 === $( '#afgfa-location-groups .afgfa-rule-group' ).length ) {
				this.addRuleGroup();
			}
		},

		/**
		 * When the param dropdown changes, reload the value dropdown.
		 */
		onParamChange: function( e ) {
			var $row  = $( e.currentTarget ).closest( '.afgfa-rule-row' );
			var param = $( e.currentTarget ).val();
			this.loadValuesForRow( $row, param );
		},

		/**
		 * Load values for a specific rule row via AJAX (with caching).
		 */
		loadValuesForRow: function( $row, param, preselect ) {
			var self   = this;
			var $valSel = $row.find( '.afgfa-rule-value' );

			// If cached, use it.
			if ( this.valueCache[ param ] ) {
				this.populateValueSelect( $valSel, this.valueCache[ param ], preselect );
				return;
			}

			$valSel.empty().append( $( '<option>' ).val( '' ).text( afgfaGenerator.strings.loading ) );

			$.ajax( {
				url:  afgfaGenerator.ajaxUrl,
				type: 'POST',
				data: {
					action: 'afgfa_get_location_values',
					nonce:  afgfaGenerator.nonce,
					param:  param
				},
				success: function( response ) {
					if ( response.success && response.data.values ) {
						self.valueCache[ param ] = response.data.values;
						self.populateValueSelect( $valSel, response.data.values, preselect );
					} else {
						$valSel.empty().append( $( '<option>' ).val( '' ).text( '—' ) );
					}
				},
				error: function() {
					$valSel.empty().append( $( '<option>' ).val( '' ).text( '—' ) );
				}
			} );
		},

		/**
		 * Populate a value <select> from a key-value map.
		 */
		populateValueSelect: function( $select, values, preselect ) {
			$select.empty();
			$.each( values, function( val, label ) {
				var $opt = $( '<option>' ).val( val ).text( label );
				if ( preselect && val === preselect ) {
					$opt.prop( 'selected', true );
				}
				$select.append( $opt );
			} );

			// If preselect not found but has values, select first.
			if ( ! preselect && $select.children().length > 0 ) {
				$select.children().first().prop( 'selected', true );
			}
		},

		/**
		 * Gather all location rules from the UI.
		 * Returns an array of groups, each group is an array of rule objects.
		 */
		gatherLocationRules: function() {
			var rules = [];

			$( '#afgfa-location-groups .afgfa-rule-group' ).each( function() {
				var group = [];

				$( this ).find( '.afgfa-rule-row' ).each( function() {
					var param    = $( this ).find( '.afgfa-rule-param' ).val();
					var operator = $( this ).find( '.afgfa-rule-operator' ).val();
					var value    = $( this ).find( '.afgfa-rule-value' ).val();

					if ( param && value ) {
						group.push( {
							param:    param,
							operator: operator || '==',
							value:    value
						} );
					}
				} );

				if ( group.length > 0 ) {
					rules.push( group );
				}
			} );

			return rules;
		},

		// =====================================================================
		// Generate
		// =====================================================================

		generateFields: function() {
			var self      = this;
			var tableId   = $( '#afgfa-table-select' ).val();
			var groupName = $( '#afgfa-group-name' ).val().trim();

			if ( ! tableId ) {
				this.showNotice( afgfaGenerator.strings.selectTableFirst, 'error' );
				return;
			}

			// Gather selected fields.
			var selectedFields = [];
			$( '.afgfa-field-check:checked' ).each( function() {
				selectedFields.push( $( this ).val() );
			} );

			if ( 0 === selectedFields.length ) {
				this.showNotice( afgfaGenerator.strings.noFieldsSelected, 'error' );
				return;
			}

			// Gather overrides.
			var fieldOverrides = {};
			$( '.afgfa-type-override' ).each( function() {
				var val = $( this ).val();
				if ( val ) {
					fieldOverrides[ $( this ).data( 'field-name' ) ] = val;
				}
			} );

			// Gather location rules.
			var locationRules = this.gatherLocationRules();

			// Show progress.
			$( '#afgfa-progress-overlay' ).show();
			$( '.afgfa-progress-fill' ).css( 'width', '30%' );
			$( '.afgfa-progress-status' ).text( afgfaGenerator.strings.generating );

			$.ajax( {
				url:  afgfaGenerator.ajaxUrl,
				type: 'POST',
				data: {
					action:          'afgfa_generate_fields',
					nonce:           afgfaGenerator.nonce,
					table_id:        tableId,
					group_name:      groupName,
					selected_fields: selectedFields,
					field_overrides: fieldOverrides,
					location_rules:  locationRules
				},
				success: function( response ) {
					$( '.afgfa-progress-fill' ).css( 'width', '100%' );

					if ( response.success ) {
						var editUrl = response.data.field_group && response.data.field_group.edit_url
							? response.data.field_group.edit_url
							: null;
						self.showNotice( response.data.message, 'success', editUrl, afgfaGenerator.strings.editGroup );
						self.loadFieldGroups();
					} else {
						self.showNotice(
							response.data && response.data.message
								? response.data.message
								: afgfaGenerator.strings.error,
							'error'
						);
					}

					setTimeout( function() {
						$( '#afgfa-progress-overlay' ).hide();
						$( '.afgfa-progress-fill' ).css( 'width', '0' );
					}, 1500 );
				},
				error: function() {
					$( '#afgfa-progress-overlay' ).hide();
					$( '.afgfa-progress-fill' ).css( 'width', '0' );
					self.showNotice( afgfaGenerator.strings.error, 'error' );
				}
			} );
		},

		// =====================================================================
		// Field Groups Tracking
		// =====================================================================

		loadFieldGroups: function() {
			var self = this;

			$.ajax( {
				url:  afgfaGenerator.ajaxUrl,
				type: 'POST',
				data: {
					action: 'afgfa_get_field_groups',
					nonce:  afgfaGenerator.nonce
				},
				success: function( response ) {
					if ( response.success ) {
						self.renderGroupsTable( response.data.groups );
					}
				}
			} );
		},

		renderGroupsTable: function( groups ) {
			var $tbody = $( '#afgfa-groups-tbody' );
			$tbody.empty();

			if ( ! groups || 0 === groups.length ) {
				var $noGroupsRow = $( '<tr>' ).addClass( 'afgfa-no-groups' );
				$noGroupsRow.append( $( '<td>' ).attr( 'colspan', 6 ).text( afgfaGenerator.strings.noGroups ) );
				$tbody.append( $noGroupsRow );
				return;
			}

			$.each( groups, function( i, group ) {
				var $row = $( '<tr>' );

				var $titleTd = $( '<td>' );
				if ( group.group_id ) {
					var editUrl = afgfaGenerator.adminUrl + 'post.php?post=' + parseInt( group.group_id, 10 ) + '&action=edit';
					$titleTd.append( $( '<a>' ).attr( 'href', editUrl ).attr( 'target', '_blank' ).text( group.title ) );
				} else {
					$titleTd.text( group.title );
				}
				$row.append( $titleTd );
				$row.append( $( '<td>' ).text( group.field_count || 0 ) );
				$row.append( $( '<td>' ).text( group.source_name || group.source_table || '\u2014' ) );
				$row.append( $( '<td>' ).text( group.post_type || '\u2014' ) );
				$row.append( $( '<td>' ).text( group.created || '\u2014' ) );

				var $actions    = $( '<td>' );
				var $actionsDiv = $( '<div>' ).addClass( 'afgfa-group-actions' );

				if ( group.group_id ) {
					var eUrl = afgfaGenerator.adminUrl + 'post.php?post=' + group.group_id + '&action=edit';
					$actionsDiv.append(
						$( '<a>' )
							.addClass( 'button button-small' )
							.attr( 'href', eUrl )
							.attr( 'target', '_blank' )
							.text( afgfaGenerator.strings.editInAcf )
					);
				}

				$actionsDiv.append(
					$( '<button>' )
						.addClass( 'button button-small afgfa-trash-group' )
						.attr( 'data-key', group.key )
						.text( afgfaGenerator.strings.trashGroup )
				);

				$actions.append( $actionsDiv );
				$row.append( $actions );
				$tbody.append( $row );
			} );
		},

		trashGroup: function( e ) {
			var self     = this;
			var $btn     = $( e.currentTarget );
			var groupKey = $btn.data( 'key' );

			if ( ! confirm( afgfaGenerator.strings.confirmTrash ) ) {
				return;
			}

			$btn.prop( 'disabled', true );

			$.ajax( {
				url:  afgfaGenerator.ajaxUrl,
				type: 'POST',
				data: {
					action:    'afgfa_trash_field_group',
					nonce:     afgfaGenerator.nonce,
					group_key: groupKey
				},
				success: function( response ) {
					if ( response.success ) {
						self.showNotice( response.data.message, 'success' );
						self.loadFieldGroups();
					} else {
						self.showNotice(
							response.data && response.data.message
								? response.data.message
								: afgfaGenerator.strings.error,
							'error'
						);
						$btn.prop( 'disabled', false );
					}
				},
				error: function() {
					self.showNotice( afgfaGenerator.strings.error, 'error' );
					$btn.prop( 'disabled', false );
				}
			} );
		},

		// =====================================================================
		// Notices
		// =====================================================================

		showNotice: function( message, type, linkUrl, linkText ) {
			$( '.afgfa-generator-wrap > .notice' ).remove();

			var $p = $( '<p>' ).text( message );
			if ( linkUrl && linkText ) {
				$p.append( ' ' ).append(
					$( '<a>' ).attr( 'href', linkUrl ).attr( 'target', '_blank' ).text( linkText )
				);
			}

			var $notice = $( '<div>' )
				.addClass( 'notice notice-' + type + ' is-dismissible' )
				.append( $p );

			var $dismissBtn = $( '<button>' )
				.attr( 'type', 'button' )
				.addClass( 'notice-dismiss' )
				.append( $( '<span>' ).addClass( 'screen-reader-text' ).text( afgfaGenerator.strings.dismiss ) );
			$notice.append( $dismissBtn );

			$( '.afgfa-generator-wrap h1' ).after( $notice );

			$notice.find( '.notice-dismiss' ).on( 'click', function() {
				$notice.fadeOut( function() {
					$( this ).remove();
				} );
			} );

			if ( 'error' === type ) {
				setTimeout( function() {
					$notice.fadeOut( function() {
						$( this ).remove();
					} );
				}, 10000 );
			}
		}
	};

	$( document ).ready( function() {
		AfgfaGenerator.init();
	} );

})( jQuery );
