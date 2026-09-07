/**
 * Toggles the Service tab's visibility based on the "_is_service" checkbox,
 * mirroring how WooCommerce core toggles .show_if_virtual internally.
 */
( function ( $ ) {
	'use strict';

	function toggleServiceTab() {
		var isChecked = $( '#_is_service' ).is( ':checked' );
		$( '.show_if_service' ).toggle( isChecked );
	}

	$( function () {
		toggleServiceTab();
		$( '#_is_service' ).on( 'change', toggleServiceTab );
	} );
} )( jQuery );
