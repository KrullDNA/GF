/**
 * Admin behaviour for the Stripe settings and feed screens.
 *
 * @package KDNAFormsStripe
 */

( function ( $ ) {

	'use strict';

	$( document ).ready( function () {

		// The early bird price is meaningless without an expiry and vice versa,
		// so enabling one focuses attention on the pair rather than letting a
		// half-filled setting save quietly.
		var $enabled = $( 'input[name="_kdnaforms_stripe_early_bird_enabled"]' );

		if ( ! $enabled.length ) {
			return;
		}

		$enabled.on( 'change', function () {
			if ( $( this ).is( ':checked' ) ) {
				$( '#early_bird_amount' ).trigger( 'focus' );
			}
		} );
	} );

} )( jQuery );
