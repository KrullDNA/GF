<?php
/**
 * A thin wrapper over the Stripe PHP library.
 *
 * Everything the add-on needs from Stripe goes through here, so there is one
 * place that knows the secret key, one place that turns a Stripe exception into
 * a WP_Error, and one place to look when a call needs tracing.
 *
 * @package KDNAFormsStripe
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class KDNA_Stripe_API
 *
 * @since 1.0.0
 */
class KDNA_Stripe_API {

	/**
	 * The secret key this instance authenticates with.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	protected $secret_key;

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 *
	 * @param string $secret_key The Stripe secret key.
	 */
	public function __construct( $secret_key ) {
		$this->secret_key = $secret_key;
		self::load_library();
	}

	/**
	 * Loads the bundled Stripe library once.
	 *
	 * The library ships with the add-on rather than being pulled at runtime, so
	 * the version is fixed and known. It is Stripe's own MIT-licensed client.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public static function load_library() {
		if ( class_exists( '\Stripe\Stripe' ) ) {
			return;
		}

		require_once KDNA_STRIPE_PATH . 'includes/stripe-php/init.php';
	}

	/**
	 * Applies the key and identifies this integration to Stripe.
	 *
	 * The app info shows in the Stripe dashboard against each request, which is
	 * how a merchant tells our calls apart from anything else hitting the same
	 * account.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	protected function prepare() {
		\Stripe\Stripe::setApiKey( $this->secret_key );
		\Stripe\Stripe::setAppInfo( 'KDNA Forms Stripe', KDNA_STRIPE_VERSION, 'https://kdnaforms.com' );
		\Stripe\Stripe::setApiVersion( '2022-11-15' );
	}

	/**
	 * Runs a Stripe call and normalises whatever comes back.
	 *
	 * Stripe throws a family of exceptions; every one of them becomes a WP_Error
	 * carrying the message Stripe gave and, where there is one, the decline code
	 * — which is what the frontend needs to tell a customer what to do next.
	 *
	 * @since 1.0.0
	 *
	 * @param callable $call The call to make.
	 *
	 * @return mixed|WP_Error
	 */
	protected function call( callable $call ) {

		$this->prepare();

		try {
			return $call();
		} catch ( \Stripe\Exception\CardException $e ) {
			$body = $e->getJsonBody();

			return new WP_Error(
				'kdna_stripe_card_error',
				$e->getMessage(),
				array(
					'decline_code' => isset( $body['error']['decline_code'] ) ? $body['error']['decline_code'] : '',
					'code'         => $e->getStripeCode(),
					'status'       => $e->getHttpStatus(),
				)
			);
		} catch ( \Stripe\Exception\RateLimitException $e ) {
			return new WP_Error( 'kdna_stripe_rate_limit', $e->getMessage() );
		} catch ( \Stripe\Exception\InvalidRequestException $e ) {
			return new WP_Error( 'kdna_stripe_invalid_request', $e->getMessage(), array( 'param' => $e->getStripeParam() ) );
		} catch ( \Stripe\Exception\AuthenticationException $e ) {
			return new WP_Error( 'kdna_stripe_authentication', $e->getMessage() );
		} catch ( \Stripe\Exception\ApiConnectionException $e ) {
			return new WP_Error( 'kdna_stripe_connection', $e->getMessage() );
		} catch ( \Stripe\Exception\ApiErrorException $e ) {
			return new WP_Error( 'kdna_stripe_api', $e->getMessage() );
		} catch ( \Exception $e ) {
			return new WP_Error( 'kdna_stripe_error', $e->getMessage() );
		}
	}

	/**
	 * Confirms the key works and returns the account behind it.
	 *
	 * @since 1.0.0
	 *
	 * @return \Stripe\Account|WP_Error
	 */
	public function get_account() {
		return $this->call(
			function () {
				return \Stripe\Account::retrieve();
			}
		);
	}

	/**
	 * Creates a payment intent.
	 *
	 * @since 1.0.0
	 *
	 * @param array $args Intent arguments.
	 *
	 * @return \Stripe\PaymentIntent|WP_Error
	 */
	public function create_payment_intent( $args ) {
		return $this->call(
			function () use ( $args ) {
				return \Stripe\PaymentIntent::create( $args );
			}
		);
	}

	/**
	 * Retrieves a payment intent.
	 *
	 * @since 1.0.0
	 *
	 * @param string $intent_id The intent id.
	 *
	 * @return \Stripe\PaymentIntent|WP_Error
	 */
	public function get_payment_intent( $intent_id ) {
		return $this->call(
			function () use ( $intent_id ) {
				return \Stripe\PaymentIntent::retrieve( $intent_id );
			}
		);
	}

	/**
	 * Updates a payment intent.
	 *
	 * @since 1.0.0
	 *
	 * @param string $intent_id The intent id.
	 * @param array  $args      The properties to change.
	 *
	 * @return \Stripe\PaymentIntent|WP_Error
	 */
	public function update_payment_intent( $intent_id, $args ) {
		return $this->call(
			function () use ( $intent_id, $args ) {
				return \Stripe\PaymentIntent::update( $intent_id, $args );
			}
		);
	}

	/**
	 * Captures a previously authorised payment intent.
	 *
	 * @since 1.0.0
	 *
	 * @param string $intent_id The intent id.
	 * @param array  $args      Optional capture arguments.
	 *
	 * @return \Stripe\PaymentIntent|WP_Error
	 */
	public function capture_payment_intent( $intent_id, $args = array() ) {
		return $this->call(
			function () use ( $intent_id, $args ) {
				$intent = \Stripe\PaymentIntent::retrieve( $intent_id );

				return $intent->capture( $args );
			}
		);
	}

	/**
	 * Cancels a payment intent that was never captured.
	 *
	 * @since 1.0.0
	 *
	 * @param string $intent_id The intent id.
	 *
	 * @return \Stripe\PaymentIntent|WP_Error
	 */
	public function cancel_payment_intent( $intent_id ) {
		return $this->call(
			function () use ( $intent_id ) {
				$intent = \Stripe\PaymentIntent::retrieve( $intent_id );

				return $intent->cancel();
			}
		);
	}

	/**
	 * Refunds a charge, in full or in part.
	 *
	 * @since 1.0.0
	 *
	 * @param array $args Refund arguments.
	 *
	 * @return \Stripe\Refund|WP_Error
	 */
	public function create_refund( $args ) {
		return $this->call(
			function () use ( $args ) {
				return \Stripe\Refund::create( $args );
			}
		);
	}

	/**
	 * Creates a customer.
	 *
	 * @since 1.0.0
	 *
	 * @param array $args Customer arguments.
	 *
	 * @return \Stripe\Customer|WP_Error
	 */
	public function create_customer( $args ) {
		return $this->call(
			function () use ( $args ) {
				return \Stripe\Customer::create( $args );
			}
		);
	}

	/**
	 * Retrieves a customer.
	 *
	 * @since 1.0.0
	 *
	 * @param string $customer_id The customer id.
	 *
	 * @return \Stripe\Customer|WP_Error
	 */
	public function get_customer( $customer_id ) {
		return $this->call(
			function () use ( $customer_id ) {
				return \Stripe\Customer::retrieve( $customer_id );
			}
		);
	}

	/**
	 * Creates a subscription.
	 *
	 * @since 1.0.0
	 *
	 * @param array $args Subscription arguments.
	 *
	 * @return \Stripe\Subscription|WP_Error
	 */
	public function create_subscription( $args ) {
		return $this->call(
			function () use ( $args ) {
				return \Stripe\Subscription::create( $args );
			}
		);
	}

	/**
	 * Retrieves a subscription.
	 *
	 * @since 1.0.0
	 *
	 * @param string $subscription_id The subscription id.
	 *
	 * @return \Stripe\Subscription|WP_Error
	 */
	public function get_subscription( $subscription_id ) {
		return $this->call(
			function () use ( $subscription_id ) {
				return \Stripe\Subscription::retrieve( $subscription_id );
			}
		);
	}

	/**
	 * Cancels a subscription.
	 *
	 * @since 1.0.0
	 *
	 * @param string $subscription_id The subscription id.
	 * @param bool   $at_period_end   Whether to let the paid period finish first.
	 *
	 * @return \Stripe\Subscription|WP_Error
	 */
	public function cancel_subscription( $subscription_id, $at_period_end = false ) {
		return $this->call(
			function () use ( $subscription_id, $at_period_end ) {
				$subscription = \Stripe\Subscription::retrieve( $subscription_id );

				if ( $at_period_end ) {
					return \Stripe\Subscription::update( $subscription_id, array( 'cancel_at_period_end' => true ) );
				}

				return $subscription->cancel();
			}
		);
	}

	/**
	 * Finds a price by its lookup key, or creates it.
	 *
	 * Stripe prices are immutable, so a recurring feed needs a price object that
	 * matches its amount, currency and interval exactly. The lookup key encodes
	 * all three, which means changing any of them produces a new price rather
	 * than silently repricing existing subscribers.
	 *
	 * @since 1.0.0
	 *
	 * @param string $lookup_key   The key identifying this exact price.
	 * @param array  $price_args   Arguments to create the price with.
	 *
	 * @return \Stripe\Price|WP_Error
	 */
	public function get_or_create_price( $lookup_key, $price_args ) {

		$existing = $this->call(
			function () use ( $lookup_key ) {
				return \Stripe\Price::all(
					array(
						'lookup_keys' => array( $lookup_key ),
						'limit'       => 1,
					)
				);
			}
		);

		if ( is_wp_error( $existing ) ) {
			return $existing;
		}

		if ( ! empty( $existing->data ) ) {
			return $existing->data[0];
		}

		$price_args['lookup_key'] = $lookup_key;

		return $this->call(
			function () use ( $price_args ) {
				return \Stripe\Price::create( $price_args );
			}
		);
	}

	/**
	 * Creates a product.
	 *
	 * @since 1.0.0
	 *
	 * @param array $args Product arguments.
	 *
	 * @return \Stripe\Product|WP_Error
	 */
	public function create_product( $args ) {
		return $this->call(
			function () use ( $args ) {
				return \Stripe\Product::create( $args );
			}
		);
	}

	/**
	 * Verifies a webhook signature and returns the event.
	 *
	 * The signature check is what stops anyone posting a forged "payment
	 * succeeded" to the endpoint, so a missing signing secret is treated as a
	 * failure rather than waved through.
	 *
	 * @since 1.0.0
	 *
	 * @param string $payload        The raw request body.
	 * @param string $signature      The Stripe-Signature header.
	 * @param string $signing_secret The endpoint's signing secret.
	 *
	 * @return \Stripe\Event|WP_Error
	 */
	public function construct_webhook_event( $payload, $signature, $signing_secret ) {

		if ( empty( $signing_secret ) ) {
			return new WP_Error( 'kdna_stripe_no_signing_secret', __( 'No webhook signing secret is configured.', 'kdnaforms-stripe' ) );
		}

		self::load_library();

		try {
			return \Stripe\Webhook::constructEvent( $payload, $signature, $signing_secret );
		} catch ( \UnexpectedValueException $e ) {
			return new WP_Error( 'kdna_stripe_webhook_payload', $e->getMessage() );
		} catch ( \Stripe\Exception\SignatureVerificationException $e ) {
			return new WP_Error( 'kdna_stripe_webhook_signature', $e->getMessage() );
		} catch ( \Exception $e ) {
			return new WP_Error( 'kdna_stripe_webhook', $e->getMessage() );
		}
	}
}
