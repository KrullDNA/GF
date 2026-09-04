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

		var theme = readFormStyling( formId, $mount, args.appearance || {} );

		showEarlyBird( formId, args.earlyBird );

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
	function readFormStyling( formId, $mount, overrides ) {

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

		overrides = overrides || {};

		var sample = $( '#kform_' + formId )
			.find( 'input[type="text"], input[type="email"], input[type="tel"]' )
			.filter( ':visible' )
			.get( 0 );

		if ( ! sample || ! window.getComputedStyle ) {
			applyContainerStyle( $mount, {}, overrides );
			return withOverrides( fallback, overrides );
		}

		var s = window.getComputedStyle( sample );

		applyContainerStyle( $mount, s, overrides );

		return withOverrides( {
			card: {
				base: {
					fontFamily: s.fontFamily,
					fontSize: s.fontSize,
					fontWeight: s.fontWeight,
					color: s.color,
					// lineHeight is deliberately not passed: Stripe warns against
					// it and renders inconsistently across browsers. The box's
					// padding, copied above, does the same job.
					'::placeholder': { color: mutePlaceholder( s.color ) }
				},
				invalid: { color: '#c02b0a', iconColor: '#c02b0a' }
			}
		}, overrides );
	}

	/**
	 * Styles the box the frame sits in.
	 *
	 * The frame itself is transparent, so the background, border, radius and
	 * padding all belong to this element. A setting wins over what was read from
	 * the form; an empty setting leaves the form's own value in place.
	 *
	 * @param {Object} $mount    The container.
	 * @param {Object} s         Computed style of a sibling input, may be empty.
	 * @param {Object} overrides Settings from the plugin's appearance section.
	 *
	 * @return {void}
	 */
	function applyContainerStyle( $mount, s, overrides ) {

		var css = {};

		css['background-color'] = overrides.background || s.backgroundColor || '#fff';
		css['border-radius']    = overrides.borderRadius || s.borderRadius || '3px';
		css['padding']          = overrides.padding || s.padding || '12px 15px';

		if ( overrides.borderColor || overrides.borderWidth ) {
			css['border-style'] = 'solid';
			css['border-color'] = overrides.borderColor || s.borderColor || '#686e77';
			css['border-width'] = overrides.borderWidth || '1px';
		} else {
			css.border = s.border || '1px solid #686e77';
		}

		if ( s.height ) {
			css['min-height'] = s.height;
		}

		$mount.css( css );
	}

	/**
	 * Applies the text overrides Stripe needs passed in rather than styled.
	 *
	 * @param {Object} theme     The style object built from the form.
	 * @param {Object} overrides Settings from the plugin's appearance section.
	 *
	 * @return {Object} The style object with any overrides applied.
	 */
	function withOverrides( theme, overrides ) {

		if ( overrides.textColor ) {
			theme.card.base.color = overrides.textColor;
			theme.card.base['::placeholder'] = { color: mutePlaceholder( overrides.textColor ) };
		}

		if ( overrides.fontSize ) {
			theme.card.base.fontSize = overrides.fontSize;
		}

		return theme;
	}

	/**
	 * Puts the pre-early-bird price back, struck through, beside the new one.
	 *
	 * Done here rather than in PHP because two attempts to carry it through the
	 * render filters were lost, and this code is already known to run — the card
	 * element mounting is the proof of it.
	 *
	 * @param {number} formId    The form id.
	 * @param {Object} earlyBird The field id and formatted previous price.
	 *
	 * @return {void}
	 */
	function showEarlyBird( formId, earlyBird ) {

		if ( ! earlyBird || ! earlyBird.was ) {
			return;
		}

		var $price = $( '#kform_' + formId )
			.find( '.kinput_product_price' )
			.not( '.kinput_product_price_label' )
			.first();

		if ( ! $price.length || $price.prev( '.kdna-stripe-price-was' ).length ) {
			return;
		}

		$( '<span/>', { 'class': 'kdna-stripe-price-was', text: earlyBird.was } )
			.insertBefore( $price )
			.after( ' ' );
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
	 * Hooks the card confirmation into the form's own submission pipeline.
	 *
	 * The earlier approach bound a jQuery submit handler and called
	 * preventDefault(). That fights the pipeline rather than joining it: the
	 * form posts into a hidden iframe, core had already shown the spinner by
	 * the time the handler ran, and preventing the post meant the iframe never
	 * loaded, so the spinner never cleared — even though the payment itself had
	 * gone through at Stripe.
	 *
	 * kform/submission/pre_submission accepts an async filter, so the card can
	 * be confirmed before the submission continues, and setting abort on the
	 * data stops it cleanly and clears the spinner.
	 *
	 * @param {number} formId The form id.
	 *
	 * @return {void}
	 */
	function bindSubmit( formId ) {

		if ( window.kform && window.kform.utils && window.kform.utils.addAsyncFilter ) {
			bindToPipeline( formId );
			return;
		}

		bindToSubmitEvent( formId );
	}

	/**
	 * Joins the submission pipeline, which is what core uses.
	 *
	 * @param {number} formId The form id.
	 *
	 * @return {void}
	 */
	function bindToPipeline( formId ) {

		if ( instances[ formId ].bound ) {
			return;
		}

		instances[ formId ].bound = true;

		window.kform.utils.addAsyncFilter(
			'kform/submission/pre_submission',
			function ( data ) {

				var thisForm = parseInt( data && data.form ? data.form.dataset.formid : 0, 10 );
				var instance = instances[ formId ];

				if ( thisForm !== formId || ! instance || instance.confirmed ) {
					return Promise.resolve( data );
				}

				return confirmCard( formId ).then( function ( ok ) {

					if ( ! ok ) {
						data.abort = true;
					} else {
						instance.confirmed = true;
					}

					return data;
				} );
			},
			5
		);
	}

	/**
	 * The fallback for a form not using the submission pipeline.
	 *
	 * @param {number} formId The form id.
	 *
	 * @return {void}
	 */
	function bindToSubmitEvent( formId ) {

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
			event.stopImmediatePropagation();
			setBusy( formId, true );

			confirmCard( formId ).then( function ( ok ) {

				setBusy( formId, false );

				if ( ! ok ) {
					return;
				}

				instance.confirmed = true;
				$form.trigger( 'submit' );
			} );

			return false;
		} );
	}

	/**
	 * Prices the order server side, then confirms the card against it.
	 *
	 * @param {number} formId The form id.
	 *
	 * @return {Promise<boolean>} Whether the form may now be submitted.
	 */
	function confirmCard( formId ) {

		var instance = instances[ formId ];
		var $form    = $( '#kform_' + formId );

		return $.post( instance.args.ajaxUrl, {
			action: 'kdna_stripe_create_intent',
			nonce: instance.args.nonce,
			form_id: formId,
			form_data: $form.serialize()
		} ).then( function ( response ) {

			if ( ! response || ! response.success ) {
				showError( formId, response && response.data ? response.data.message : genericError() );

				return false;
			}

			// Nothing to charge, so the submission carries on untouched.
			if ( response.data.skip ) {
				return true;
			}

			if ( response.data.subscription ) {
				return instance.stripe.createPaymentMethod( {
					type: 'card',
					card: instance.card
				} ).then( function ( result ) {

					if ( result.error ) {
						showError( formId, result.error.message );

						return false;
					}

					$form.find( 'input[name="kdna_stripe_payment_method"]' ).val( result.paymentMethod.id );

					return true;
				} );
			}

			return instance.stripe.confirmCardPayment( response.data.clientSecret, {
				payment_method: { card: instance.card }
			} ).then( function ( result ) {

				if ( result.error ) {
					showError( formId, result.error.message );

					return false;
				}

				$form.find( 'input[name="kdna_stripe_intent_id"]' ).val( result.paymentIntent.id );

				return true;
			} );

		} ).then( null, function () {
			showError( formId, genericError() );

			return false;
		} );
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

	/**
	 * Reports what the client can and cannot see, for when something is missing.
	 *
	 * Run KDNAStripe.debug() in the browser console. It answers the questions
	 * that otherwise take a round trip each: did Stripe.js load, did the field
	 * render, did the settings arrive, was an early bird price passed, and did
	 * the element actually mount.
	 *
	 * @return {Object} The state of every link in the chain.
	 */
	function debug() {

		var report = {
			stripeJsLoaded: typeof Stripe !== 'undefined',
			mountPoints: $( '.kdna-stripe-element' ).length,
			forms: {}
		};

		$( '.kdna-stripe-element' ).each( function () {

			var id = $( this ).data( 'form-id' );
			var instance = instances[ id ];

			report.forms[ id ] = {
				initialised: !! instance,
				publishableKeyPassed: !! ( instance && instance.args.publishableKey ),
				earlyBirdPassed: !! ( instance && instance.args.earlyBird ),
				earlyBirdWas: instance && instance.args.earlyBird ? instance.args.earlyBird.was : null,
				priceElementFound: $( '#kform_' + id ).find( '.kinput_product_price' ).not( '.kinput_product_price_label' ).length,
				strikeThroughShown: $( '#kform_' + id ).find( '.kdna-stripe-price-was' ).length > 0,
				cardMounted: !! ( this.querySelector( 'iframe' ) ),
				submitRoute: ( window.kform && window.kform.utils && window.kform.utils.addAsyncFilter ) ? 'submission pipeline' : 'submit event',
				confirmed: !! ( instance && instance.confirmed )
			};
		} );

		return report;
	}

	return {
		init: init,
		debug: debug
	};

} )( jQuery );
