/**
 * Settings page JavaScript.
 *
 * @package AFGFA
 */
(function( $ ) {
	'use strict';

	var AfgfaSettings = {

		init: function() {
			this.bindEvents();
		},

		bindEvents: function() {
			$( '#afgfa-toggle-api-key' ).on( 'click', this.toggleApiKey );
			$( '#afgfa-test-connection' ).on( 'click', this.testConnection );
		},

		toggleApiKey: function( e ) {
			e.preventDefault();
			var $input  = $( '#afgfa-api-key' );
			var $button = $( this );

			if ( 'password' === $input.attr( 'type' ) ) {
				$input.attr( 'type', 'text' );
				$button.text( afgfaSettings.strings.hide );
			} else {
				$input.attr( 'type', 'password' );
				$button.text( afgfaSettings.strings.show );
			}
		},

		testConnection: function( e ) {
			e.preventDefault();

			var apiKey = $( '#afgfa-api-key' ).val().trim();
			var baseId = $( '#afgfa-base-id' ).val().trim();
			var $status = $( '#afgfa-connection-status' );

			if ( ! apiKey || ! baseId ) {
				$status
					.removeClass( 'status-success status-loading' )
					.addClass( 'status-error' )
					.text( afgfaSettings.strings.missingCredentials );
				return;
			}

			var $button      = $( this );
			var originalText = $button.text();

			$button.prop( 'disabled', true );
			$status
				.removeClass( 'status-success status-error' )
				.addClass( 'status-loading' )
				.text( afgfaSettings.strings.testing );

			$.ajax( {
				url:  afgfaSettings.ajaxUrl,
				type: 'POST',
				data: {
					action:  'afgfa_test_connection',
					nonce:   afgfaSettings.nonce,
					api_key: apiKey,
					base_id: baseId
				},
				success: function( response ) {
					if ( response.success ) {
						$status
							.removeClass( 'status-loading status-error' )
							.addClass( 'status-success' )
							.text( response.data.message );
					} else {
						$status
							.removeClass( 'status-loading status-success' )
							.addClass( 'status-error' )
							.text( response.data.message || afgfaSettings.strings.error );
					}
				},
				error: function() {
					$status
						.removeClass( 'status-loading status-success' )
						.addClass( 'status-error' )
						.text( afgfaSettings.strings.error );
				},
				complete: function() {
					$button.prop( 'disabled', false ).text( originalText );
				}
			} );
		}
	};

	$( document ).ready( function() {
		AfgfaSettings.init();
	} );

})( jQuery );
