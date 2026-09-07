/**
 * Toggles the Service tab's visibility based on the "_is_service" checkbox
 * and the current product type, since WooCommerce core's own show/hide
 * logic only knows about show_if_simple/show_if_variable, not show_if_service.
 */
( function ( $ ) {
	'use strict';

	function toggleServiceTab() {
		var isChecked = $( '#_is_service' ).is( ':checked' );
		var productType = $( '#product-type' ).val();
		var isEligibleType = 'simple' === productType || 'variable' === productType;

		$( '.show_if_service' ).toggle( isChecked && isEligibleType );
	}

	$( function () {
		toggleServiceTab();
		$( '#_is_service' ).on( 'change', toggleServiceTab );
		$( '#product-type' ).on( 'change', toggleServiceTab );
	} );
} )( jQuery );
