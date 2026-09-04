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

		var $mount = $( '.kdna-stripe-element[data-form-id="' + formId + '"]' );

		if ( ! $mount.length ) {
			return;
		}

		// init runs again after an AJAX render, and again every time the
		// Elementor editor rebuilds the widget. Mounting twice into the same
		// node stacks two iframes, but skipping whenever an instance exists
		// leaves the editor with an empty box, because the node it mounted
		// into has been thrown away. So the test is whether the element we
		// mounted last time is still the one on the page.
		var existing = instances[ formId ];

		if ( existing && existing.mount === $mount.get( 0 ) && document.body.contains( existing.mount ) ) {
			return;
		}

		if ( existing && existing.card ) {
			try {
				existing.card.unmount();
			} catch ( e ) {
				// The node is already gone; nothing to detach from.
			}
		}

		var theme = readFormStyling( formId, $mount );

		var stripe   = Stripe( args.publishableKey );
		var elements = stripe.elements();
		var card     = elements.create( 'card', {
			hidePostalCode: false,
			style: theme.card
		} );

		card.mount( $mount.get( 0 ) );

		instances[ formId ] = {
			stripe: stripe,
			card: card,
			mount: $mount.get( 0 ),
			args: args,
			confirmed: false
		};

		card.on( 'change', function ( event ) {
			showError( formId, event.error ? event.error.message : '' );
		} );

		bindSubmit( formId );
	}

	/**
	 * Matches the card element to the form's own inputs.
	 *
	 * The card fields live in an iframe Stripe serves, so no stylesheet on this
	 * site can reach them — whatever Elementor or the form theme does to the
	 * other inputs stops at the frame. The only way in is the style object
	 * Stripe accepts, so the styling of a real input on the same form is read
	 * back and handed over. The box around the frame is a normal element, so
	 * that is copied directly.
	 *
	 * @param {number} formId The form id.
	 * @param {Object} $mount The element Stripe mounts into.
	 *
	 * @return {Object} A style object for Stripe, having styled the container.
	 */
	function readFormStyling( formId, $mount ) {

		var fallback = {
			card: {
				base: {
					fontSize: '16px',
					color: '#32325d',
					'::placeholder': { color: '#8f95a1' }
				},
				invalid: { color: '#c02b0a', iconColor: '#c02b0a' }
			}
		};

		var sample = $( '#kform_' + formId )
			.find( 'input[type="text"], input[type="email"], input[type="tel"]' )
			.filter( ':visible' )
			.get( 0 );

		if ( ! sample || ! window.getComputedStyle ) {
			return fallback;
		}

		var s = window.getComputedStyle( sample );

		// The frame is transparent, so the box it sits in has to carry the
		// border, background and spacing the other inputs have.
		$mount.css( {
			'background-color': s.backgroundColor,
			'border': s.border,
			'border-radius': s.borderRadius,
			'padding': s.padding,
			'min-height': s.height
		} );

		return {
			card: {
				base: {
					fontFamily: s.fontFamily,
					fontSize: s.fontSize,
					fontWeight: s.fontWeight,
					color: s.color,
					lineHeight: s.lineHeight === 'normal' ? undefined : s.lineHeight,
					'::placeholder': { color: mutePlaceholder( s.color ) }
				},
				invalid: { color: '#c02b0a', iconColor: '#c02b0a' }
			}
		};
	}

	/**
	 * A placeholder colour derived from the input's own text colour.
	 *
	 * @param {string} color The computed colour, as rgb() or rgba().
	 *
	 * @return {string} The same hue at reduced opacity.
	 */
	function mutePlaceholder( color ) {

		var parts = ( color || '' ).match( /\d+/g );

		if ( ! parts || parts.length < 3 ) {
			return '#8f95a1';
		}

		return 'rgba(' + parts[0] + ',' + parts[1] + ',' + parts[2] + ',0.55)';
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
