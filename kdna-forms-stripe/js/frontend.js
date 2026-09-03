/**
 * Stripe card collection and confirmation.
 *
 * The amount is never sent from here. The browser asks the server for a payment
 * intent, the server works out what the form is actually charging and creates
 * it, and only the intent id comes back. That way a customer editing the page
 * cannot change what they pay.
 *
 * @package KDNAFormsStripe
 */

window.KDNAStripe = ( function ( $ ) {

	'use strict';

	var instances = {};

	/**
	 * Sets up Stripe Elements for one form.
	 *
	 * @param {Object} args Settings passed from PHP.
	 *
	 * @return {void}
	 */
	function init( args ) {

		if ( ! args || ! args.publishableKey ) {
			return;
		}

		if ( typeof Stripe === 'undefined' ) {
			return;
		}

		var formId = args.formId;

		// initializeOnLoaded fires again on an AJAX form after each render, so
		// an element already mounted for this form is left alone rather than
		// being stacked on top of itself.
		if ( instances[ formId ] ) {
			return;
		}

		var $mount = $( '.kdna-stripe-element[data-form-id="' + formId + '"]' );

		if ( ! $mount.length ) {
			return;
		}

		var stripe   = Stripe( args.publishableKey );
		var elements = stripe.elements();
		var card     = elements.create( 'card', {
			hidePostalCode: false,
			style: {
				base: {
					fontSize: '16px',
					color: '#32325d',
					'::placeholder': { color: '#aab7c4' }
				},
				invalid: { color: '#9c0f17', iconColor: '#9c0f17' }
			}
		} );

		card.mount( $mount.get( 0 ) );

		instances[ formId ] = {
			stripe: stripe,
			card: card,
			args: args,
			confirmed: false
		};

		card.on( 'change', function ( event ) {
			showError( formId, event.error ? event.error.message : '' );
		} );

		bindSubmit( formId );
	}

	/**
	 * Intercepts the submit so the card can be confirmed first.
	 *
	 * @param {number} formId The form id.
	 *
	 * @return {void}
	 */
	function bindSubmit( formId ) {

		var $form = $( '#kform_' + formId );

		if ( ! $form.length ) {
			return;
		}

		$form.off( 'submit.kdnaStripe' ).on( 'submit.kdnaStripe', function ( event ) {

			var instance = instances[ formId ];

			if ( ! instance || instance.confirmed ) {
				return true;
			}

			event.preventDefault();
			setBusy( formId, true );
			confirm( formId, $form );

			return false;
		} );
	}

	/**
	 * Asks the server for an intent, confirms the card against it, then lets
	 * the form submit for real.
	 *
	 * @param {number} formId The form id.
	 * @param {Object} $form  The form element.
	 *
	 * @return {void}
	 */
	function confirm( formId, $form ) {

		var instance = instances[ formId ];

		$.post( instance.args.ajaxUrl, {
			action: 'kdna_stripe_create_intent',
			nonce: instance.args.nonce,
			form_id: formId,
			form_data: $form.serialize()
		} ).done( function ( response ) {

			if ( ! response || ! response.success ) {
				fail( formId, response && response.data ? response.data.message : genericError() );
				return;
			}

			// A zero-total submission has nothing to charge, so it goes through
			// without touching Stripe at all.
			if ( response.data.skip ) {
				release( formId, $form );
				return;
			}

			if ( response.data.subscription ) {
				confirmForSubscription( formId, $form, response.data );
				return;
			}

			instance.stripe.confirmCardPayment( response.data.clientSecret, {
				payment_method: { card: instance.card }
			} ).then( function ( result ) {

				if ( result.error ) {
					fail( formId, result.error.message );
					return;
				}

				$form.find( 'input[name="kdna_stripe_intent_id"]' ).val( result.paymentIntent.id );
				release( formId, $form );
			} );

		} ).fail( function () {
			fail( formId, genericError() );
		} );
	}

	/**
	 * Collects a payment method for a subscription.
	 *
	 * A subscription is created server-side against the customer, so the card
	 * only needs turning into a payment method here.
	 *
	 * @param {number} formId The form id.
	 * @param {Object} $form  The form element.
	 *
	 * @return {void}
	 */
	function confirmForSubscription( formId, $form ) {

		var instance = instances[ formId ];

		instance.stripe.createPaymentMethod( {
			type: 'card',
			card: instance.card
		} ).then( function ( result ) {

			if ( result.error ) {
				fail( formId, result.error.message );
				return;
			}

			$form.find( 'input[name="kdna_stripe_payment_method"]' ).val( result.paymentMethod.id );
			release( formId, $form );
		} );
	}

	/**
	 * Lets the form submit now the card side is done.
	 *
	 * @param {number} formId The form id.
	 * @param {Object} $form  The form element.
	 *
	 * @return {void}
	 */
	function release( formId, $form ) {
		instances[ formId ].confirmed = true;
		$form.trigger( 'submit' );
	}

	/**
	 * Shows an error and gives the form back to the customer.
	 *
	 * @param {number} formId  The form id.
	 * @param {string} message The message to show.
	 *
	 * @return {void}
	 */
	function fail( formId, message ) {
		setBusy( formId, false );
		showError( formId, message );
	}

	/**
	 * Writes a message into the error area for a form.
	 *
	 * @param {number} formId  The form id.
	 * @param {string} message The message, empty to clear.
	 *
	 * @return {void}
	 */
	function showError( formId, message ) {
		$( '.kdna-stripe-element[data-form-id="' + formId + '"]' )
			.siblings( '.kdna-stripe-errors' )
			.text( message || '' );
	}

	/**
	 * Disables or re-enables the submit button while Stripe is working.
	 *
	 * @param {number}  formId The form id.
	 * @param {boolean} busy   Whether a call is in flight.
	 *
	 * @return {void}
	 */
	function setBusy( formId, busy ) {

		var $form = $( '#kform_' + formId );

		$form.find( 'input[type="submit"], button[type="submit"]' ).prop( 'disabled', !! busy );
		$form.toggleClass( 'kdna-stripe-busy', !! busy );
	}

	/**
	 * @return {string} The fallback message when nothing more specific is known.
	 */
	function genericError() {
		return 'The payment could not be started. Please try again.';
	}

	return {
		init: init
	};

} )( jQuery );
