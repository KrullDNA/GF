<?php
/**
 * The Stripe payment add-on.
 *
 * @package KDNAFormsStripe
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class KDNA_Stripe
 *
 * Takes one-off and recurring card payments through Stripe Payment Intents,
 * with 3D Secure handled on the client and confirmed on the server.
 *
 * @since 1.0.0
 */
class KDNA_Stripe extends KDNAPaymentAddOn {

	/**
	 * The single instance.
	 *
	 * @since 1.0.0
	 *
	 * @var KDNA_Stripe|null
	 */
	private static $_instance = null;

	/**
	 * @since 1.0.0
	 * @var string
	 */
	protected $_version = KDNA_STRIPE_VERSION;

	/**
	 * @since 1.0.0
	 * @var string
	 */
	protected $_min_kdnaforms_version = KDNA_STRIPE_MIN_KDNA_FORMS;

	/**
	 * @since 1.0.0
	 * @var string
	 */
	protected $_slug = 'kdnaformsstripe';

	/**
	 * @since 1.0.0
	 * @var string
	 */
	protected $_path = 'kdna-forms-stripe/kdna-forms-stripe.php';

	/**
	 * @since 1.0.0
	 * @var string
	 */
	protected $_full_path = __FILE__;

	/**
	 * @since 1.0.0
	 * @var string
	 */
	protected $_title = 'KDNA Forms Stripe';

	/**
	 * @since 1.0.0
	 * @var string
	 */
	protected $_short_title = 'Stripe';

	/**
	 * Stripe handles the card details itself, so the form does not need a
	 * credit card field and the card number never reaches this server.
	 *
	 * @since 1.0.0
	 * @var bool
	 */
	protected $_requires_credit_card = false;

	/**
	 * @since 1.0.0
	 * @var bool
	 */
	protected $_supports_callbacks = true;

	/**
	 * @since 1.0.0
	 * @var string
	 */
	protected $_payment_method = 'Stripe';

	/**
	 * Capabilities.
	 *
	 * @since 1.0.0
	 * @var string|array
	 */
	protected $_capabilities_settings_page = 'kdnaforms_stripe';
	protected $_capabilities_form_settings = 'kdnaforms_stripe';
	protected $_capabilities_uninstall     = 'kdnaforms_stripe_uninstall';
	protected $_capabilities              = array( 'kdnaforms_stripe', 'kdnaforms_stripe_uninstall' );

	/**
	 * Cached API instances, keyed by mode.
	 *
	 * @since 1.0.0
	 * @var array
	 */
	private $api = array();

	/**
	 * The price the early bird replaced, keyed by form id.
	 *
	 * apply_early_bird_to_form() lowers basePrice on the field during
	 * pre_render, and everything after that reads the lowered value — including
	 * the lookup that works out what to strike through, which then compared 25
	 * against 25 and concluded there was no discount. The original has to be
	 * captured at the moment it is replaced.
	 *
	 * @since 1.2.3
	 * @var array
	 */
	private $early_bird_was = array();

	/**
	 * Returns the single instance.
	 *
	 * @since 1.0.0
	 *
	 * @return KDNA_Stripe
	 */
	public static function get_instance() {
		if ( null === self::$_instance ) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}

	// ---------------------------------------------------------------------
	// Wiring
	// ---------------------------------------------------------------------

	/**
	 * Registers the webhook endpoint early, before the theme loads.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function pre_init() {
		parent::pre_init();

		add_action( 'wp', array( $this, 'maybe_process_webhook' ), 5 );
		add_action( 'init', array( $this, 'maybe_process_webhook' ), 5 );
	}

	/**
	 * Frontend hooks.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function init() {
		parent::init();

		add_action( 'kdnaform_register_init_scripts', array( $this, 'register_init_scripts' ), 10, 3 );

		// Early bird pricing has to reach the price the customer sees, the
		// order summary and the amount actually charged, or the three disagree.
		add_filter( 'kdnaform_product_info', array( $this, 'apply_early_bird_to_product_info' ), 10, 3 );

		// The price itself is changed on the field before the form renders, so
		// core formats it, the hidden input carries it and the running total
		// agrees — rather than rewriting formatted output after the fact.
		add_filter( 'kdnaform_pre_render', array( $this, 'apply_early_bird_to_form' ) );
	}

	/**
	 * AJAX hooks.
	 *
	 * The intent is created here rather than in the browser so the amount is
	 * decided by the form, not by whatever the client asks for.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function init_ajax() {
		parent::init_ajax();

		add_action( 'wp_ajax_kdna_stripe_create_intent', array( $this, 'ajax_create_intent' ) );
		add_action( 'wp_ajax_nopriv_kdna_stripe_create_intent', array( $this, 'ajax_create_intent' ) );
	}

	/**
	 * Creates the payment intent for a submission in progress.
	 *
	 * Only the client secret goes back to the browser. The amount is worked out
	 * here from the submitted values, so editing the page cannot change the
	 * price, and authorize() checks the intent again before the entry is saved.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function ajax_create_intent() {

		check_ajax_referer( 'kdna_stripe_intent', 'nonce' );

		$form_id = absint( rgpost( 'form_id' ) );
		$form    = KDNAFormsModel::get_form_meta( $form_id );

		if ( empty( $form ) ) {
			wp_send_json_error( array( 'message' => esc_html__( 'That form could not be found.', 'kdnaforms-stripe' ) ) );
		}

		if ( ! $this->is_configured() ) {
			wp_send_json_error( array( 'message' => esc_html__( 'Payments are not configured. Please contact the site owner.', 'kdnaforms-stripe' ) ) );
		}

		// The submitted values are needed to price the order, so they are
		// merged into the request the pricing code reads from.
		parse_str( (string) rgpost( 'form_data' ), $submitted );

		if ( is_array( $submitted ) ) {
			$_POST = array_merge( $_POST, $submitted ); // phpcs:ignore WordPress.Security.NonceVerification
		}

		$entry = KDNAFormsModel::create_lead( $form );
		$feed  = $this->get_payment_feed( $entry, $form );

		if ( empty( $feed ) || ! $this->is_feed_condition_met( $feed, $form, $entry ) ) {
			wp_send_json_success( array( 'skip' => true ) );
		}

		if ( 'subscription' === rgars( $feed, 'meta/transactionType' ) ) {
			wp_send_json_success( array( 'subscription' => true ) );
		}

		$submission_data = $this->get_submission_data( $feed, $form, $entry );
		$amount          = rgar( $submission_data, 'payment_amount' );

		if ( ! $this->is_valid_payment_amount( $submission_data ) ) {
			wp_send_json_success( array( 'skip' => true ) );
		}

		$currency = KDNACommon::get_currency();

		$args = array(
			'amount'         => $this->get_amount_export( $amount, $currency ),
			'currency'       => strtolower( $currency ),
			'capture_method' => $this->get_capture_method( $feed ),
			'metadata'       => array(
				'form_id' => $form_id,
				'form'    => rgar( $form, 'title' ),
			),
		);

		$descriptor = rgars( $feed, 'meta/statement_descriptor' );

		if ( ! empty( $descriptor ) ) {
			$args['statement_descriptor'] = substr( preg_replace( '/[<>\\\\\'"*]/', '', $descriptor ), 0, 22 );
		}

		if ( $this->is_early_bird_active( $feed ) ) {
			$args['metadata']['pricing'] = 'early_bird';
		}

		$intent = $this->get_api()->create_payment_intent( $args );

		if ( is_wp_error( $intent ) ) {
			$this->log_error( __METHOD__ . '(): ' . $intent->get_error_message() );
			wp_send_json_error( array( 'message' => $intent->get_error_message() ) );
		}

		wp_send_json_success(
			array(
				'clientSecret' => $intent->client_secret,
				'intentId'     => $intent->id,
			)
		);
	}

	/**
	 * Admin hooks.
	 *
	 * The add-on adds no menu of its own: the framework already gives it a tab
	 * under Forms → Settings, and a second entry would only duplicate it.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function init_admin() {
		parent::init_admin();

		add_action( 'admin_notices', array( $this, 'maybe_warn_about_missing_card_field' ) );
	}

	/**
	 * Warns when a form has a Stripe feed but nowhere to enter a card.
	 *
	 * Without the field the form simply renders without a card box and the
	 * payment silently never happens, which looks like the add-on is broken
	 * rather than like a form that is missing a field.
	 *
	 * @since 1.1.5
	 *
	 * @return void
	 */
	public function maybe_warn_about_missing_card_field() {

		if ( ! $this->is_form_settings() && ! $this->is_form_editor() ) {
			return;
		}

		$form_id = absint( rgget( 'id' ) );

		if ( ! $form_id || ! $this->has_feed( $form_id ) ) {
			return;
		}

		$form = KDNAFormsModel::get_form_meta( $form_id );

		foreach ( (array) rgar( $form, 'fields' ) as $field ) {
			if ( 'kdna_stripe_card' === $field->get_input_type() ) {
				return;
			}
		}

		printf(
			'<div class="notice notice-warning"><p>%s</p></div>',
			esc_html__( 'This form has a Stripe feed but no Stripe Card field, so there is nowhere to enter a card and no payment will be taken. Add the Stripe Card field from the Pricing Fields group in the form editor.', 'kdnaforms-stripe' )
		);
	}

	/**
	 * Scripts.
	 *
	 * @since 1.0.0
	 *
	 * @return array
	 */
	public function scripts() {

		$scripts = array(
			array(
				'handle'  => 'kdna_stripe_js',
				'src'     => 'https://js.stripe.com/v3/',
				'version' => $this->_version,
				'deps'    => array(),
				'enqueue' => array(
					array( $this, 'frontend_script_callback' ),
				),
			),
			array(
				'handle'    => 'kdna_stripe_frontend',
				'src'       => KDNA_STRIPE_URL . 'js/frontend.js',
				'version'   => $this->_version,
				'deps'      => array( 'jquery', 'kdna_stripe_js' ),
				'in_footer' => true,
				'enqueue'   => array(
					array( $this, 'frontend_script_callback' ),
				),
			),
			array(
				'handle'  => 'kdna_stripe_admin',
				'src'     => KDNA_STRIPE_URL . 'js/admin.js',
				'version' => $this->_version,
				'deps'    => array( 'jquery' ),
				'enqueue' => array(
					array( 'admin_page' => array( 'plugin_settings', 'form_settings' ), 'tab' => $this->_slug ),
				),
			),
		);

		return array_merge( parent::scripts(), $scripts );
	}

	/**
	 * Styles.
	 *
	 * @since 1.0.0
	 *
	 * @return array
	 */
	public function styles() {

		$styles = array(
			array(
				'handle'  => 'kdna_stripe_frontend',
				'src'     => KDNA_STRIPE_URL . 'css/frontend.css',
				'version' => $this->_version,
				'enqueue' => array(
					array( $this, 'frontend_script_callback' ),
				),
			),
		);

		return array_merge( parent::styles(), $styles );
	}

	/**
	 * Whether the frontend assets are needed for this form.
	 *
	 * @since 1.0.0
	 *
	 * @param array $form The form being rendered.
	 *
	 * @return bool
	 */
	public function frontend_script_callback( $form ) {
		return $form && $this->form_has_card_field( $form );
	}

	/**
	 * Whether a form carries the Stripe Card field.
	 *
	 * The assets used to load only when the form had a feed, which made an
	 * unconfigured form fail in the worst way available: the field rendered its
	 * container, no stylesheet or script arrived to fill it, and the page showed
	 * a label with nothing beneath it. The field being on the form is reason
	 * enough to load them — if Stripe cannot then start, it says so.
	 *
	 * @since 1.2.1
	 *
	 * @param array $form The form.
	 *
	 * @return bool
	 */
	public function form_has_card_field( $form ) {

		foreach ( (array) rgar( $form, 'fields' ) as $field ) {
			if ( 'kdna_stripe_card' === $field->get_input_type() ) {
				return true;
			}
		}

		return false;
	}

	// ---------------------------------------------------------------------
	// Plugin settings
	// ---------------------------------------------------------------------

	/**
	 * Plugin settings fields.
	 *
	 * @since 1.0.0
	 *
	 * @return array
	 */
	public function plugin_settings_fields() {

		return array(
			array(
				'title'       => esc_html__( 'Stripe API Keys', 'kdnaforms-stripe' ),
				'description' => sprintf(
					/* translators: 1: opening anchor tag, 2: closing anchor tag. */
					esc_html__( 'Your keys are in the Stripe dashboard under %1$sDevelopers → API keys%2$s. Test keys begin sk_test and pk_test, live keys begin sk_live and pk_live.', 'kdnaforms-stripe' ),
					'<a href="https://dashboard.stripe.com/apikeys" target="_blank" rel="noopener noreferrer">',
					'</a>'
				),
				'fields'      => array(
					array(
						'name'          => 'api_mode',
						'label'         => esc_html__( 'Mode', 'kdnaforms-stripe' ),
						'type'          => 'radio',
						'horizontal'    => true,
						'default_value' => 'test',
						'choices'       => array(
							array(
								'label' => esc_html__( 'Test', 'kdnaforms-stripe' ),
								'value' => 'test',
							),
							array(
								'label' => esc_html__( 'Live', 'kdnaforms-stripe' ),
								'value' => 'live',
							),
						),
						'tooltip'       => esc_html__( 'Test mode uses your test keys and never moves real money. Switch to live only once you have taken a successful test payment.', 'kdnaforms-stripe' ),
					),
					array(
						'name'  => 'test_publishable_key',
						'label' => esc_html__( 'Test Publishable Key', 'kdnaforms-stripe' ),
						'type'  => 'text',
						'class' => 'medium',
					),
					array(
						'name'              => 'test_secret_key',
						'label'             => esc_html__( 'Test Secret Key', 'kdnaforms-stripe' ),
						'type'              => 'text',
						'input_type'        => 'password',
						'class'             => 'medium',
						'feedback_callback' => array( $this, 'validate_secret_key' ),
					),
					array(
						'name'  => 'live_publishable_key',
						'label' => esc_html__( 'Live Publishable Key', 'kdnaforms-stripe' ),
						'type'  => 'text',
						'class' => 'medium',
					),
					array(
						'name'              => 'live_secret_key',
						'label'             => esc_html__( 'Live Secret Key', 'kdnaforms-stripe' ),
						'type'              => 'text',
						'input_type'        => 'password',
						'class'             => 'medium',
						'feedback_callback' => array( $this, 'validate_secret_key' ),
					),
				),
			),
			array(
				'title'       => esc_html__( 'Card Field Appearance', 'kdnaforms-stripe' ),
				'description' => esc_html__( 'The card inputs sit inside a frame served by Stripe, so no CSS on this site can reach them. Leave a box empty and that part is taken from your form\'s own inputs, which is usually what you want. Fill one in to override it.', 'kdnaforms-stripe' ),
				'fields'      => array(
					array(
						'name'        => 'card_background',
						'label'       => esc_html__( 'Background', 'kdnaforms-stripe' ),
						'type'        => 'text',
						'class'       => 'small',
						'placeholder' => '#ffffff',
					),
					array(
						'name'        => 'card_border_color',
						'label'       => esc_html__( 'Border Colour', 'kdnaforms-stripe' ),
						'type'        => 'text',
						'class'       => 'small',
						'placeholder' => '#686e77',
					),
					array(
						'name'        => 'card_border_width',
						'label'       => esc_html__( 'Border Width', 'kdnaforms-stripe' ),
						'type'        => 'text',
						'class'       => 'small',
						'placeholder' => '1px',
					),
					array(
						'name'        => 'card_border_radius',
						'label'       => esc_html__( 'Border Radius', 'kdnaforms-stripe' ),
						'type'        => 'text',
						'class'       => 'small',
						'placeholder' => '3px',
					),
					array(
						'name'        => 'card_padding',
						'label'       => esc_html__( 'Padding', 'kdnaforms-stripe' ),
						'type'        => 'text',
						'class'       => 'small',
						'placeholder' => '12px 15px',
					),
					array(
						'name'        => 'card_text_color',
						'label'       => esc_html__( 'Text Colour', 'kdnaforms-stripe' ),
						'type'        => 'text',
						'class'       => 'small',
						'placeholder' => '#32325d',
					),
					array(
						'name'        => 'card_font_size',
						'label'       => esc_html__( 'Font Size', 'kdnaforms-stripe' ),
						'type'        => 'text',
						'class'       => 'small',
						'placeholder' => '16px',
					),
				),
			),
			array(
				'title'       => esc_html__( 'Webhooks', 'kdnaforms-stripe' ),
				'description' => $this->get_webhooks_description(),
				'fields'      => array(
					array(
						'name'  => 'test_signing_secret',
						'label' => esc_html__( 'Test Signing Secret', 'kdnaforms-stripe' ),
						'type'  => 'text',
						'class' => 'medium',
					),
					array(
						'name'  => 'live_signing_secret',
						'label' => esc_html__( 'Live Signing Secret', 'kdnaforms-stripe' ),
						'type'  => 'text',
						'class' => 'medium',
					),
				),
			),
		);
	}

	/**
	 * Explains what to point at the webhook endpoint and which events to send.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function get_webhooks_description() {

		$events = array(
			'charge.refunded',
			'customer.subscription.deleted',
			'invoice.payment_succeeded',
			'invoice.payment_failed',
			'payment_intent.succeeded',
			'payment_intent.payment_failed',
		);

		return sprintf(
			'%s<br/><code>%s</code><br/><br/>%s<br/><code>%s</code><br/><br/>%s',
			esc_html__( 'Add an endpoint in the Stripe dashboard pointing at this URL:', 'kdnaforms-stripe' ),
			esc_url( $this->get_webhook_url() ),
			esc_html__( 'Send it these events:', 'kdnaforms-stripe' ),
			esc_html( implode( ', ', $events ) ),
			esc_html__( 'Then copy the endpoint signing secret into the matching box below. Without it, incoming events are rejected — which is deliberate: the signature is the only thing that proves an event really came from Stripe.', 'kdnaforms-stripe' )
		);
	}

	/**
	 * Confirms a secret key authenticates, for the tick beside the field.
	 *
	 * @since 1.0.0
	 *
	 * @param string $value The key entered.
	 *
	 * @return bool|null
	 */
	public function validate_secret_key( $value ) {

		if ( rgblank( $value ) ) {
			return null;
		}

		$api     = new KDNA_Stripe_API( $value );
		$account = $api->get_account();

		return ! is_wp_error( $account );
	}

	// ---------------------------------------------------------------------
	// Feed settings
	// ---------------------------------------------------------------------

	/**
	 * Feed settings fields.
	 *
	 * @since 1.0.0
	 *
	 * @return array
	 */
	public function feed_settings_fields() {

		$fields = parent::feed_settings_fields();

		// The framework's own transaction type choices cover product and
		// subscription; everything below hangs off that choice.
		$fields = $this->add_early_bird_settings( $fields );

		$fields[] = array(
			'title'  => esc_html__( 'Stripe Options', 'kdnaforms-stripe' ),
			'fields' => array(
				array(
					'name'          => 'capture_method',
					'label'         => esc_html__( 'Capture', 'kdnaforms-stripe' ),
					'type'          => 'radio',
					'default_value' => 'automatic',
					'choices'       => array(
						array(
							'label' => esc_html__( 'Charge the card immediately', 'kdnaforms-stripe' ),
							'value' => 'automatic',
						),
						array(
							'label' => esc_html__( 'Authorise now, capture later', 'kdnaforms-stripe' ),
							'value' => 'manual',
						),
					),
					'tooltip'       => esc_html__( 'An authorisation holds the funds without taking them. Stripe releases the hold if you do not capture within seven days.', 'kdnaforms-stripe' ),
					'dependency'    => array(
						'live'   => true,
						'fields' => array(
							array(
								'field'  => 'transactionType',
								'values' => array( 'product' ),
							),
						),
					),
				),
				array(
					'name'    => 'statement_descriptor',
					'label'   => esc_html__( 'Statement Descriptor', 'kdnaforms-stripe' ),
					'type'    => 'text',
					'class'   => 'medium',
					'tooltip' => esc_html__( 'What the customer sees on their bank statement. Stripe allows up to 22 characters and rejects < > \\ \' " *.', 'kdnaforms-stripe' ),
				),
				array(
					'name'    => 'payment_description',
					'label'   => esc_html__( 'Payment Description', 'kdnaforms-stripe' ),
					'type'    => 'text',
					'class'   => 'medium',
					'tooltip' => esc_html__( 'Shown against the payment in your Stripe dashboard. Merge tags are supported.', 'kdnaforms-stripe' ),
				),
				array(
					'name'      => 'metaData',
					'label'     => esc_html__( 'Metadata', 'kdnaforms-stripe' ),
					'type'      => 'dynamic_field_map',
					'limit'     => 20,
					'exclude_field_types' => 'creditcard',
					'tooltip'   => esc_html__( 'Send extra fields to Stripe alongside the payment. They appear on the payment in your dashboard and in exports.', 'kdnaforms-stripe' ),
					'validation_callback' => array( $this, 'validate_custom_meta' ),
				),
			),
		);

		$fields[] = array(
			'title'  => esc_html__( 'Conditional Logic', 'kdnaforms-stripe' ),
			'fields' => array(
				array(
					'name'    => 'feed_condition',
					'label'   => esc_html__( 'Condition', 'kdnaforms-stripe' ),
					'type'    => 'feed_condition',
					'checkbox_label' => esc_html__( 'Enable Condition', 'kdnaforms-stripe' ),
					'instructions'   => esc_html__( 'Take payment through Stripe if', 'kdnaforms-stripe' ),
				),
			),
		);

		return $fields;
	}

	/**
	 * Adds the early bird pricing section to the feed.
	 *
	 * The section sits with the other product settings because that is where
	 * someone setting a price will look for it.
	 *
	 * @since 1.0.0
	 *
	 * @param array $fields The feed settings sections.
	 *
	 * @return array
	 */
	protected function add_early_bird_settings( $fields ) {

		$fields[] = array(
			'title'       => esc_html__( 'Early Bird Pricing', 'kdnaforms-stripe' ),
			'description' => esc_html__( 'Charge a lower price until a date you choose, then fall back to the full price automatically. The switch happens on your site\'s timezone, and the amount charged, the order summary and the price shown on the form all follow it together.', 'kdnaforms-stripe' ),
			'fields'      => array(
				array(
					'name'    => 'early_bird_enabled',
					'label'   => esc_html__( 'Early Bird', 'kdnaforms-stripe' ),
					'type'    => 'checkbox',
					'choices' => array(
						array(
							'name'  => 'early_bird_enabled',
							'label' => esc_html__( 'Offer an early bird price', 'kdnaforms-stripe' ),
						),
					),
				),
				array(
					'name'       => 'early_bird_amount',
					'label'      => esc_html__( 'Early Bird Price', 'kdnaforms-stripe' ),
					'type'       => 'text',
					'class'      => 'small',
					'required'   => true,
					'tooltip'    => esc_html__( 'The amount to charge before the expiry date, in the form\'s currency. Enter the number only, for example 49.00.', 'kdnaforms-stripe' ),
					'validation_callback' => array( $this, 'validate_early_bird_amount' ),
					'dependency' => array(
						'live'   => true,
						'fields' => array(
							array(
								'field'  => 'early_bird_enabled',
								'values' => array( '1' ),
							),
						),
					),
				),
				array(
					'name'       => 'early_bird_expiry',
					'label'      => esc_html__( 'Expires', 'kdnaforms-stripe' ),
					'type'       => 'text',
					'class'      => 'medium',
					'required'   => true,
					'placeholder' => 'YYYY-MM-DD HH:MM',
					'tooltip'    => esc_html__( 'The moment the early bird price stops applying, in your site\'s timezone. A submission at or after this time pays the full price. Leave the time off to mean midnight at the start of that day.', 'kdnaforms-stripe' ),
					'validation_callback' => array( $this, 'validate_early_bird_expiry' ),
					'dependency' => array(
						'live'   => true,
						'fields' => array(
							array(
								'field'  => 'early_bird_enabled',
								'values' => array( '1' ),
							),
						),
					),
				),
			),
		);

		return $fields;
	}

	/**
	 * Rejects an early bird amount that is not a usable price.
	 *
	 * @since 1.0.0
	 *
	 * @param array $field The field being validated.
	 * @param mixed $value The submitted value.
	 *
	 * @return void
	 */
	public function validate_early_bird_amount( $field, $value ) {

		if ( rgblank( $value ) ) {
			return;
		}

		$amount = $this->parse_amount( $value );

		if ( null === $amount || $amount <= 0 ) {
			$this->set_field_error( $field, esc_html__( 'Enter the early bird price as a number greater than zero, for example 49.00.', 'kdnaforms-stripe' ) );
		}
	}

	/**
	 * Rejects an expiry that cannot be read as a date.
	 *
	 * @since 1.0.0
	 *
	 * @param array $field The field being validated.
	 * @param mixed $value The submitted value.
	 *
	 * @return void
	 */
	public function validate_early_bird_expiry( $field, $value ) {

		if ( rgblank( $value ) ) {
			return;
		}

		if ( null === $this->parse_expiry( $value ) ) {
			$this->set_field_error( $field, esc_html__( 'Enter the expiry as YYYY-MM-DD or YYYY-MM-DD HH:MM.', 'kdnaforms-stripe' ) );
		}
	}

	/**
	 * Rejects metadata keys Stripe will not accept.
	 *
	 * @since 1.0.0
	 *
	 * @param array $field The field being validated.
	 * @param mixed $value The submitted value.
	 *
	 * @return void
	 */
	public function validate_custom_meta( $field, $value ) {

		$metadata = $this->get_setting( 'metaData' );

		if ( empty( $metadata ) ) {
			return;
		}

		foreach ( $metadata as $meta ) {
			if ( empty( $meta['key'] ) ) {
				continue;
			}

			if ( strlen( $meta['key'] ) > 40 ) {
				$this->set_field_error( $field, esc_html__( 'Metadata keys are limited to 40 characters.', 'kdnaforms-stripe' ) );

				return;
			}
		}
	}

	// ---------------------------------------------------------------------
	// Early bird pricing
	// ---------------------------------------------------------------------

	/**
	 * Whether the early bird price applies to this feed right now.
	 *
	 * @since 1.0.0
	 *
	 * @param array $feed The feed.
	 *
	 * @return bool
	 */
	public function is_early_bird_active( $feed ) {

		if ( ! rgars( $feed, 'meta/early_bird_enabled' ) ) {
			return false;
		}

		$amount = $this->parse_amount( rgars( $feed, 'meta/early_bird_amount' ) );
		$expiry = $this->parse_expiry( rgars( $feed, 'meta/early_bird_expiry' ) );

		if ( null === $amount || null === $expiry ) {
			return false;
		}

		/**
		 * Filters whether the early bird price applies.
		 *
		 * @since 1.0.0
		 *
		 * @param bool  $is_active Whether the early bird price applies.
		 * @param array $feed      The feed being processed.
		 * @param int   $expiry    The expiry as a Unix timestamp.
		 */
		return (bool) apply_filters( 'kdnaform_stripe_early_bird_active', time() < $expiry, $feed, $expiry );
	}

	/**
	 * The early bird price for a feed, or null when it does not apply.
	 *
	 * @since 1.0.0
	 *
	 * @param array $feed The feed.
	 *
	 * @return float|null
	 */
	public function get_early_bird_amount( $feed ) {

		if ( ! $this->is_early_bird_active( $feed ) ) {
			return null;
		}

		return $this->parse_amount( rgars( $feed, 'meta/early_bird_amount' ) );
	}

	/**
	 * Reads an amount written for people into a float.
	 *
	 * Accepts 1,234.56 and 1.234,56 alike, because which one a merchant types
	 * depends on where they are rather than on what the form is set to.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $value The value as entered.
	 *
	 * @return float|null
	 */
	protected function parse_amount( $value ) {

		if ( is_numeric( $value ) ) {
			return (float) $value;
		}

		if ( ! is_string( $value ) || '' === trim( $value ) ) {
			return null;
		}

		$clean = preg_replace( '/[^0-9.,-]/', '', $value );

		if ( '' === $clean ) {
			return null;
		}

		$last_dot   = strrpos( $clean, '.' );
		$last_comma = strrpos( $clean, ',' );

		if ( false !== $last_comma && ( false === $last_dot || $last_comma > $last_dot ) ) {
			// Comma is the decimal separator: 1.234,56.
			$clean = str_replace( '.', '', $clean );
			$clean = str_replace( ',', '.', $clean );
		} else {
			// Dot is the decimal separator, or there is none.
			$clean = str_replace( ',', '', $clean );
		}

		return is_numeric( $clean ) ? (float) $clean : null;
	}

	/**
	 * Reads the expiry into a Unix timestamp in the site's timezone.
	 *
	 * A date with no time means the start of that day, so "expires 2026-04-01"
	 * behaves the way a person reading it would expect: the last day it applies
	 * is the 31st.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $value The value as entered.
	 *
	 * @return int|null
	 */
	protected function parse_expiry( $value ) {

		if ( ! is_string( $value ) || '' === trim( $value ) ) {
			return null;
		}

		$value = trim( $value );

		try {
			$tz   = wp_timezone();
			$date = new DateTimeImmutable( $value, $tz );
		} catch ( Exception $e ) {
			return null;
		}

		// DateTime accepts a lot of loose input; require something that at least
		// looks like a date so a typo does not silently become "now".
		if ( ! preg_match( '/\d{4}-\d{2}-\d{2}/', $value ) ) {
			return null;
		}

		return $date->getTimestamp();
	}

	/**
	 * Swaps the product price for the early bird price while it is running.
	 *
	 * This runs on the product info the form builds, so the order summary, the
	 * total the customer sees and the amount sent to Stripe all move together
	 * rather than disagreeing with one another.
	 *
	 * @since 1.0.0
	 *
	 * @param array $product_info The product and shipping info.
	 * @param array $form         The form.
	 * @param array $entry        The entry.
	 *
	 * @return array
	 */
	public function apply_early_bird_to_product_info( $product_info, $form, $entry ) {

		$feed = $this->get_payment_feed( $entry, $form );

		if ( empty( $feed ) || ! $this->is_early_bird_active( $feed ) ) {
			return $product_info;
		}

		$early_bird = $this->get_early_bird_amount( $feed );

		if ( null === $early_bird || empty( $product_info['products'] ) ) {
			return $product_info;
		}

		// The discount lands on the priced line item, which is the one the feed
		// is charging for. Quantity is left alone so a multiple still multiplies.
		foreach ( $product_info['products'] as $id => $product ) {
			$price = KDNACommon::to_number( rgar( $product, 'price' ) );

			if ( $price > 0 ) {
				$product_info['products'][ $id ]['price']           = $early_bird;
				$product_info['products'][ $id ]['is_early_bird']   = true;
				$product_info['products'][ $id ]['full_price']      = $price;
				break;
			}
		}

		return $product_info;
	}


	/**
	 * The form's Stripe feed, if one has a live early bird price.
	 *
	 * @since 1.1.5
	 *
	 * @param int $form_id The form id.
	 *
	 * @return array|false
	 */
	public function get_early_bird_feed( $form_id ) {

		foreach ( (array) $this->get_feeds( $form_id ) as $feed ) {
			if ( rgar( $feed, 'is_active' ) && $this->is_early_bird_active( $feed ) ) {
				return $feed;
			}
		}

		return false;
	}

	/**
	 * Whether this is the first priced product field on the form.
	 *
	 * @since 1.1.5
	 *
	 * @param object $field   The field being rendered.
	 * @param int    $form_id The form id.
	 *
	 * @return bool
	 */
	protected function is_first_priced_field( $field, $form_id ) {

		$form = KDNAFormsModel::get_form_meta( $form_id );

		foreach ( (array) rgar( $form, 'fields' ) as $candidate ) {
			if ( ! in_array( $candidate->get_input_type(), array( 'singleproduct', 'hiddenproduct', 'price' ), true ) ) {
				continue;
			}

			if ( KDNACommon::to_number( $candidate->basePrice ) > 0 ) {
				return (int) $candidate->id === (int) $field->id;
			}
		}

		return false;
	}

	/**
	 * Lowers the priced product field before the form is rendered.
	 *
	 * Changing basePrice here means core does the formatting, writes the hidden
	 * base price and drives its own running total from the same number. The
	 * alternative — rewriting the rendered money string — leaves the total and
	 * the label disagreeing.
	 *
	 * @since 1.1.4
	 *
	 * @param array $form The form about to render.
	 *
	 * @return array
	 */
	public function apply_early_bird_to_form( $form ) {

		if ( $this->is_form_editor() ) {
			return $form;
		}

		$feed = $this->get_early_bird_feed( rgar( $form, 'id' ) );

		if ( empty( $feed ) ) {
			return $form;
		}

		$early_bird = $this->get_early_bird_amount( $feed );

		if ( null === $early_bird ) {
			return $form;
		}

		foreach ( (array) rgar( $form, 'fields' ) as $field ) {
			if ( ! in_array( $field->get_input_type(), array( 'singleproduct', 'hiddenproduct', 'price' ), true ) ) {
				continue;
			}

			$full = KDNACommon::to_number( $field->basePrice );

			if ( $full > 0 && $early_bird < $full ) {
				$this->early_bird_was[ (int) rgar( $form, 'id' ) ] = array(
					'fieldId' => (int) $field->id,
					'was'     => KDNACommon::to_money( $full, KDNACommon::get_currency() ),
				);
				$field->basePrice = $early_bird;
				break;
			}
		}

		return $form;
	}

	/**
	 * Registers and enqueues the frontend assets on demand.
	 *
	 * Called by the field as it renders, rather than relying on the enqueue
	 * conditions alone. Those depend on the form reaching the callback, which
	 * does not happen on every embedding route, and when it fails the field
	 * renders an unstyled empty container and says nothing.
	 *
	 * @since 1.1.4
	 *
	 * @param int $form_id The form being rendered.
	 *
	 * @return void
	 */
	public function enqueue_frontend_assets( $form_id ) {

		if ( ! wp_script_is( 'kdna_stripe_js', 'registered' ) ) {
			wp_register_script( 'kdna_stripe_js', 'https://js.stripe.com/v3/', array(), $this->_version, true );
		}

		if ( ! wp_script_is( 'kdna_stripe_frontend', 'registered' ) ) {
			wp_register_script(
				'kdna_stripe_frontend',
				KDNA_STRIPE_URL . 'js/frontend.js',
				array( 'jquery', 'kdna_stripe_js' ),
				$this->_version,
				true
			);
		}

		if ( ! wp_style_is( 'kdna_stripe_frontend', 'registered' ) ) {
			wp_register_style( 'kdna_stripe_frontend', KDNA_STRIPE_URL . 'css/frontend.css', array(), $this->_version );
		}

		wp_enqueue_script( 'kdna_stripe_js' );
		wp_enqueue_script( 'kdna_stripe_frontend' );
		wp_enqueue_style( 'kdna_stripe_frontend' );

		$args = $this->get_frontend_args( $form_id );

		// Booting from here as well as from the init script means the element
		// mounts whichever of the two the page actually delivers; init() is
		// guarded against running twice for the same form.
		wp_add_inline_script(
			'kdna_stripe_frontend',
			sprintf(
				'jQuery(function(){ if ( window.KDNAStripe ) { window.KDNAStripe.init( %s ); } });',
				wp_json_encode( $args )
			)
		);
	}

	/**
	 * Everything the client needs, built once for both boot routes.
	 *
	 * The early bird price is worked out here from the feed and the stored form,
	 * not carried across from the render filters. Two attempts at passing it
	 * through the field object failed silently — the object reaching the content
	 * filter is not the one pre_render modified — and the client is the one part
	 * of this chain already proven to run.
	 *
	 * @since 1.2.0
	 *
	 * @param int $form_id The form id.
	 *
	 * @return array
	 */
	public function get_frontend_args( $form_id ) {

		$args = array(
			'formId'         => absint( $form_id ),
			'publishableKey' => $this->get_publishable_key(),
			'ajaxUrl'        => admin_url( 'admin-ajax.php' ),
			'nonce'          => wp_create_nonce( 'kdna_stripe_intent' ),
			'appearance'     => $this->get_appearance_settings(),
			'earlyBird'      => $this->get_early_bird_display( $form_id ),
		);

		/**
		 * Filters the settings handed to the Stripe client.
		 *
		 * @since 1.2.0
		 *
		 * @param array $args    The settings.
		 * @param int   $form_id The form being rendered.
		 */
		return apply_filters( 'kdnaform_stripe_frontend_args', $args, $form_id );
	}

	/**
	 * The price the early bird replaced, ready to show struck through.
	 *
	 * @since 1.2.0
	 *
	 * @param int $form_id The form id.
	 *
	 * @return array|null The field id and formatted old price, or null.
	 */
	public function get_early_bird_display( $form_id ) {

		// Recorded when the price was lowered. Recomputing here would read the
		// already-lowered price and find no discount.
		if ( isset( $this->early_bird_was[ (int) $form_id ] ) ) {
			return $this->early_bird_was[ (int) $form_id ];
		}

		$feed = $this->get_early_bird_feed( $form_id );

		if ( empty( $feed ) ) {
			return null;
		}

		$early_bird = $this->get_early_bird_amount( $feed );
		$form       = KDNAFormsModel::get_form_meta( $form_id );

		if ( null === $early_bird || empty( $form ) ) {
			return null;
		}

		foreach ( (array) rgar( $form, 'fields' ) as $field ) {
			if ( ! in_array( $field->get_input_type(), array( 'singleproduct', 'hiddenproduct', 'price' ), true ) ) {
				continue;
			}

			$full = KDNACommon::to_number( $field->basePrice );

			if ( $full > 0 && $early_bird < $full ) {
				return array(
					'fieldId' => (int) $field->id,
					'was'     => KDNACommon::to_money( $full, KDNACommon::get_currency() ),
				);
			}
		}

		return null;
	}

	/**
	 * The appearance settings for the card box.
	 *
	 * Blank values mean "take it from the form's own inputs", which is the
	 * default and keeps the card matching the theme without being configured.
	 *
	 * @since 1.2.0
	 *
	 * @return array
	 */
	public function get_appearance_settings() {

		return array(
			'background'   => (string) $this->get_plugin_setting( 'card_background' ),
			'borderColor'  => (string) $this->get_plugin_setting( 'card_border_color' ),
			'borderWidth'  => (string) $this->get_plugin_setting( 'card_border_width' ),
			'borderRadius' => (string) $this->get_plugin_setting( 'card_border_radius' ),
			'padding'      => (string) $this->get_plugin_setting( 'card_padding' ),
			'textColor'    => (string) $this->get_plugin_setting( 'card_text_color' ),
			'fontSize'     => (string) $this->get_plugin_setting( 'card_font_size' ),
		);
	}

	// ---------------------------------------------------------------------
	// Payment
	// ---------------------------------------------------------------------

	/**
	 * Authorises the payment.
	 *
	 * The card is confirmed on the client, so by the time this runs there is
	 * already a payment intent. All that is left is to check it really is
	 * payable and that its amount matches what this form expects — a client can
	 * send any intent id it likes, so the amount is verified server-side.
	 *
	 * @since 1.0.0
	 *
	 * @param array $feed            The feed.
	 * @param array $submission_data The submission data.
	 * @param array $form            The form.
	 * @param array $entry           The entry.
	 *
	 * @return array
	 */
	public function authorize( $feed, $submission_data, $form, $entry ) {

		$intent_id = $this->get_submitted_intent_id();

		if ( empty( $intent_id ) ) {
			return $this->authorization_error( esc_html__( 'The payment was not started. Please re-enter your card details and try again.', 'kdnaforms-stripe' ) );
		}

		$api    = $this->get_api();
		$intent = $api->get_payment_intent( $intent_id );

		if ( is_wp_error( $intent ) ) {
			return $this->authorization_error( $intent->get_error_message() );
		}

		$expected = $this->get_amount_export( rgar( $submission_data, 'payment_amount' ), rgar( $entry, 'currency' ) );

		if ( (int) $intent->amount !== (int) $expected ) {
			$this->log_error( __METHOD__ . '(): intent ' . $intent_id . ' is for ' . $intent->amount . ' but this form expects ' . $expected );

			return $this->authorization_error( esc_html__( 'The payment amount did not match the order. Nothing has been charged. Please try again.', 'kdnaforms-stripe' ) );
		}

		if ( ! in_array( $intent->status, array( 'succeeded', 'requires_capture', 'processing' ), true ) ) {
			return $this->authorization_error( esc_html__( 'The payment was not completed. Please try again.', 'kdnaforms-stripe' ) );
		}

		return array(
			'is_authorized'  => true,
			'transaction_id' => $intent_id,
			'amount'         => $this->get_amount_import( $intent->amount, rgar( $entry, 'currency' ) ),
		);
	}

	/**
	 * Captures the authorised payment.
	 *
	 * @since 1.0.0
	 *
	 * @param array $authorization   The authorization result.
	 * @param array $feed            The feed.
	 * @param array $submission_data The submission data.
	 * @param array $form            The form.
	 * @param array $entry           The entry.
	 *
	 * @return array
	 */
	public function capture( $authorization, $feed, $submission_data, $form, $entry ) {

		$intent_id = rgar( $authorization, 'transaction_id' );
		$api       = $this->get_api();
		$intent    = $api->get_payment_intent( $intent_id );

		if ( is_wp_error( $intent ) ) {
			return array(
				'is_success'    => false,
				'error_message' => $intent->get_error_message(),
			);
		}

		// A manual-capture feed leaves the money on hold; the entry is marked
		// Authorized and captured later from the entry screen.
		if ( 'requires_capture' === $intent->status && 'manual' === $this->get_capture_method( $feed ) ) {
			return array(
				'is_success'     => true,
				'is_authorized'  => true,
				'transaction_id' => $intent_id,
				'amount'         => $this->get_amount_import( $intent->amount, rgar( $entry, 'currency' ) ),
				'payment_method' => $this->_payment_method,
			);
		}

		if ( 'requires_capture' === $intent->status ) {
			$intent = $api->capture_payment_intent( $intent_id );

			if ( is_wp_error( $intent ) ) {
				return array(
					'is_success'    => false,
					'error_message' => $intent->get_error_message(),
				);
			}
		}

		$this->add_metadata_to_intent( $intent_id, $feed, $entry, $form );

		return array(
			'is_success'     => true,
			'transaction_id' => $intent_id,
			'amount'         => $this->get_amount_import( $intent->amount_received ? $intent->amount_received : $intent->amount, rgar( $entry, 'currency' ) ),
			'payment_method' => $this->_payment_method,
		);
	}

	/**
	 * Creates the subscription.
	 *
	 * @since 1.0.0
	 *
	 * @param array $feed            The feed.
	 * @param array $submission_data The submission data.
	 * @param array $form            The form.
	 * @param array $entry           The entry.
	 *
	 * @return array
	 */
	public function subscribe( $feed, $submission_data, $form, $entry ) {

		$api             = $this->get_api();
		$payment_method  = $this->get_submitted_payment_method_id();

		if ( empty( $payment_method ) ) {
			return $this->authorization_error( esc_html__( 'The card was not collected. Please re-enter your details and try again.', 'kdnaforms-stripe' ) );
		}

		$currency = rgar( $entry, 'currency' );
		$amount   = rgar( $submission_data, 'payment_amount' );

		// Early bird applies to the recurring amount as well, so a subscriber
		// who signs up in time keeps the lower rate for the life of the plan.
		$early_bird = $this->get_early_bird_amount( $feed );

		if ( null !== $early_bird ) {
			$amount = $early_bird;
		}

		$customer = $this->create_stripe_customer( $feed, $form, $entry, $payment_method );

		if ( is_wp_error( $customer ) ) {
			return $this->authorization_error( $customer->get_error_message() );
		}

		$price = $this->get_subscription_price( $feed, $amount, $currency, $form );

		if ( is_wp_error( $price ) ) {
			return $this->authorization_error( $price->get_error_message() );
		}

		$args = array(
			'customer'         => $customer->id,
			'items'            => array( array( 'price' => $price->id ) ),
			'expand'           => array( 'latest_invoice.payment_intent' ),
			'metadata'         => $this->get_metadata( $feed, $entry, $form ),
			'default_payment_method' => $payment_method,
		);

		$trial_days = (int) rgars( $feed, 'meta/trialPeriod' );

		if ( rgars( $feed, 'meta/trial_enabled' ) && $trial_days > 0 ) {
			$args['trial_period_days'] = $trial_days;
		}

		$subscription = $api->create_subscription( $args );

		if ( is_wp_error( $subscription ) ) {
			return $this->authorization_error( $subscription->get_error_message() );
		}

		if ( in_array( $subscription->status, array( 'incomplete', 'past_due' ), true ) ) {
			return $this->authorization_error( esc_html__( 'The first payment could not be taken. Please check your card details and try again.', 'kdnaforms-stripe' ) );
		}

		return array(
			'is_success'      => true,
			'subscription_id' => $subscription->id,
			'customer_id'     => $customer->id,
			'amount'          => $amount,
		);
	}

	/**
	 * Cancels a subscription.
	 *
	 * @since 1.0.0
	 *
	 * @param array $entry The entry.
	 * @param array $feed  The feed.
	 *
	 * @return bool
	 */
	public function cancel( $entry, $feed ) {

		$subscription_id = rgar( $entry, 'transaction_id' );

		if ( empty( $subscription_id ) ) {
			return false;
		}

		$result = $this->get_api()->cancel_subscription( $subscription_id );

		if ( is_wp_error( $result ) ) {
			$this->log_error( __METHOD__ . '(): ' . $result->get_error_message() );

			return false;
		}

		return true;
	}

	/**
	 * Builds or reuses the Stripe price a subscription feed bills against.
	 *
	 * @since 1.0.0
	 *
	 * @param array  $feed     The feed.
	 * @param float  $amount   The recurring amount.
	 * @param string $currency The currency code.
	 * @param array  $form     The form.
	 *
	 * @return \Stripe\Price|WP_Error
	 */
	protected function get_subscription_price( $feed, $amount, $currency, $form ) {

		$api      = $this->get_api();
		$interval = rgars( $feed, 'meta/billingCycle_unit' ) ? rgars( $feed, 'meta/billingCycle_unit' ) : 'month';
		$count    = (int) rgars( $feed, 'meta/billingCycle_length' );
		$count    = $count > 0 ? $count : 1;
		$unit     = $this->get_amount_export( $amount, $currency );

		$lookup_key = sprintf(
			'kdna_%d_%s_%s_%d_%s',
			rgar( $form, 'id' ),
			strtolower( $currency ),
			$unit,
			$count,
			$interval
		);

		$product_name = rgars( $feed, 'meta/feedName' ) ? rgars( $feed, 'meta/feedName' ) : rgar( $form, 'title' );

		$product = $api->create_product( array( 'name' => $product_name ) );

		if ( is_wp_error( $product ) ) {
			return $product;
		}

		return $api->get_or_create_price(
			$lookup_key,
			array(
				'unit_amount' => $unit,
				'currency'    => strtolower( $currency ),
				'recurring'   => array(
					'interval'       => $interval,
					'interval_count' => $count,
				),
				'product'     => $product->id,
			)
		);
	}

	/**
	 * Creates the Stripe customer a subscription is billed to.
	 *
	 * @since 1.0.0
	 *
	 * @param array  $feed           The feed.
	 * @param array  $form           The form.
	 * @param array  $entry          The entry.
	 * @param string $payment_method The payment method id.
	 *
	 * @return \Stripe\Customer|WP_Error
	 */
	protected function create_stripe_customer( $feed, $form, $entry, $payment_method ) {

		$args = array(
			'payment_method'  => $payment_method,
			'invoice_settings' => array( 'default_payment_method' => $payment_method ),
			'metadata'        => $this->get_metadata( $feed, $entry, $form ),
		);

		$email = $this->get_field_value( $form, $entry, rgars( $feed, 'meta/customerInformation_email' ) );

		if ( ! empty( $email ) ) {
			$args['email'] = $email;
		}

		$name = $this->get_field_value( $form, $entry, rgars( $feed, 'meta/customerInformation_name' ) );

		if ( ! empty( $name ) ) {
			$args['name'] = $name;
		}

		return $this->get_api()->create_customer( $args );
	}

	/**
	 * Attaches the feed's metadata and description to the payment.
	 *
	 * @since 1.0.0
	 *
	 * @param string $intent_id The intent id.
	 * @param array  $feed      The feed.
	 * @param array  $entry     The entry.
	 * @param array  $form      The form.
	 *
	 * @return void
	 */
	protected function add_metadata_to_intent( $intent_id, $feed, $entry, $form ) {

		$args     = array();
		$metadata = $this->get_metadata( $feed, $entry, $form );

		if ( ! empty( $metadata ) ) {
			$args['metadata'] = $metadata;
		}

		$description = rgars( $feed, 'meta/payment_description' );

		if ( ! empty( $description ) ) {
			$args['description'] = KDNACommon::replace_variables( $description, $form, $entry, false, false, false, 'text' );
		}

		if ( empty( $args ) ) {
			return;
		}

		$result = $this->get_api()->update_payment_intent( $intent_id, $args );

		if ( is_wp_error( $result ) ) {
			// Metadata is a convenience, not part of taking the money, so a
			// failure here is logged and the payment stands.
			$this->log_error( __METHOD__ . '(): ' . $result->get_error_message() );
		}
	}

	/**
	 * Builds the metadata to send with a payment.
	 *
	 * @since 1.0.0
	 *
	 * @param array $feed  The feed.
	 * @param array $entry The entry.
	 * @param array $form  The form.
	 *
	 * @return array
	 */
	protected function get_metadata( $feed, $entry, $form ) {

		$metadata = array(
			'entry_id' => rgar( $entry, 'id' ),
			'form_id'  => rgar( $form, 'id' ),
			'form'     => rgar( $form, 'title' ),
		);

		if ( $this->is_early_bird_active( $feed ) ) {
			$metadata['pricing'] = 'early_bird';
		}

		$mapped = rgars( $feed, 'meta/metaData' );

		if ( is_array( $mapped ) ) {
			foreach ( $mapped as $meta ) {
				if ( empty( $meta['key'] ) || empty( $meta['value'] ) ) {
					continue;
				}

				$value = $this->get_field_value( $form, $entry, $meta['value'] );

				if ( '' !== $value ) {
					// Stripe caps metadata values at 500 characters.
					$metadata[ $meta['key'] ] = substr( (string) $value, 0, 500 );
				}
			}
		}

		return $metadata;
	}

	/**
	 * The capture method for a feed.
	 *
	 * @since 1.0.0
	 *
	 * @param array $feed The feed.
	 *
	 * @return string
	 */
	public function get_capture_method( $feed ) {
		return 'manual' === rgars( $feed, 'meta/capture_method' ) ? 'manual' : 'automatic';
	}

	/**
	 * Shapes an authorization failure the framework understands.
	 *
	 * @since 1.0.0
	 *
	 * @param string $message The message to show the customer.
	 *
	 * @return array
	 */
	public function authorization_error( $message ) {
		return array(
			'error_message' => $message,
			'is_success'    => false,
			'is_authorized' => false,
		);
	}

	// ---------------------------------------------------------------------
	// Request plumbing
	// ---------------------------------------------------------------------

	/**
	 * The payment intent id the client confirmed.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	protected function get_submitted_intent_id() {
		return sanitize_text_field( rgpost( 'kdna_stripe_intent_id' ) );
	}

	/**
	 * The payment method id the client collected.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	protected function get_submitted_payment_method_id() {
		return sanitize_text_field( rgpost( 'kdna_stripe_payment_method' ) );
	}



	/**
	 * Registers the script that boots Stripe for this form.
	 *
	 * kdnaform_register_init_scripts is an action, not a filter — a returned
	 * value is discarded — so the script is handed to add_init_script() the way
	 * the framework's own frontend feeds do it. Getting this wrong is silent:
	 * the field renders, Stripe never mounts, and the card box is simply empty.
	 *
	 * @since 1.0.0
	 *
	 * @param array $form         The form being rendered.
	 * @param array $field_values Field values used to populate the form.
	 * @param bool  $is_ajax      Whether the form is rendered via AJAX.
	 *
	 * @return void
	 */
	public function register_init_scripts( $form, $field_values = array(), $is_ajax = false ) {

		if ( ! $this->form_has_card_field( $form ) || ! $this->is_configured() ) {
			return;
		}

		$args = $this->get_frontend_args( rgar( $form, 'id' ) );

		$script = sprintf(
			'; if ( window.KDNAStripe ) { window.KDNAStripe.init( %s ); }',
			wp_json_encode( $args )
		);

		KDNAFormDisplay::add_init_script(
			rgar( $form, 'id' ),
			'kdna_stripe',
			KDNAFormDisplay::ON_PAGE_RENDER,
			$script
		);
	}

	// ---------------------------------------------------------------------
	// Keys and mode
	// ---------------------------------------------------------------------

	/**
	 * The API instance for the current mode.
	 *
	 * @since 1.0.0
	 *
	 * @param string|null $mode Optional mode override.
	 *
	 * @return KDNA_Stripe_API
	 */
	public function get_api( $mode = null ) {

		$mode = $mode ? $mode : $this->get_api_mode();

		if ( ! isset( $this->api[ $mode ] ) ) {
			$this->api[ $mode ] = new KDNA_Stripe_API( $this->get_secret_key( $mode ) );
		}

		return $this->api[ $mode ];
	}

	/**
	 * The configured mode.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function get_api_mode() {
		$mode = $this->get_plugin_setting( 'api_mode' );

		return 'live' === $mode ? 'live' : 'test';
	}

	/**
	 * The secret key for a mode.
	 *
	 * @since 1.0.0
	 *
	 * @param string|null $mode Optional mode override.
	 *
	 * @return string
	 */
	public function get_secret_key( $mode = null ) {
		$mode = $mode ? $mode : $this->get_api_mode();

		return (string) $this->get_plugin_setting( $mode . '_secret_key' );
	}

	/**
	 * The publishable key for the current mode.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function get_publishable_key() {
		return (string) $this->get_plugin_setting( $this->get_api_mode() . '_publishable_key' );
	}

	/**
	 * The webhook signing secret for the current mode.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function get_signing_secret() {
		return (string) $this->get_plugin_setting( $this->get_api_mode() . '_signing_secret' );
	}

	/**
	 * Whether the add-on has everything it needs to take a payment.
	 *
	 * @since 1.0.0
	 *
	 * @return bool
	 */
	public function is_configured() {
		return '' !== $this->get_secret_key() && '' !== $this->get_publishable_key();
	}

	// ---------------------------------------------------------------------
	// Webhooks
	// ---------------------------------------------------------------------

	/**
	 * The URL Stripe should send events to.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function get_webhook_url() {
		return add_query_arg( 'callback', $this->_slug, home_url( '/', 'https' ) );
	}

	/**
	 * Handles an incoming Stripe event.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function maybe_process_webhook() {

		if ( $this->_slug !== rgget( 'callback' ) ) {
			return;
		}

		$payload   = file_get_contents( 'php://input' );
		$signature = isset( $_SERVER['HTTP_STRIPE_SIGNATURE'] ) ? $_SERVER['HTTP_STRIPE_SIGNATURE'] : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
		$event     = $this->get_api()->construct_webhook_event( $payload, $signature, $this->get_signing_secret() );

		if ( is_wp_error( $event ) ) {
			$this->log_error( __METHOD__ . '(): rejected — ' . $event->get_error_message() );
			status_header( 400 );
			die( esc_html( $event->get_error_message() ) );
		}

		// Stripe retries on any non-2xx, so a duplicate must be answered with a
		// success or the same event arrives again for ever.
		if ( $this->is_duplicate_callback( $event->id ) ) {
			status_header( 200 );
			die( 'Already handled.' );
		}

		$action = $this->process_webhook_event( $event );

		if ( ! empty( $action ) ) {
			$this->register_callback( $event->id );
			$this->process_callback_action( $action );
		}

		status_header( 200 );
		die( 'OK' );
	}

	/**
	 * Turns a Stripe event into a framework callback action.
	 *
	 * @since 1.0.0
	 *
	 * @param \Stripe\Event $event The event.
	 *
	 * @return array
	 */
	protected function process_webhook_event( $event ) {

		$object = $event->data->object;
		$action = array();

		switch ( $event->type ) {

			case 'charge.refunded':
				$entry = $this->get_entry_by_transaction_id( $object->payment_intent );

				if ( $entry ) {
					$action = array(
						'id'               => $event->id,
						'type'             => 'refund_payment',
						'transaction_id'   => $object->payment_intent,
						'entry_id'         => $entry['id'],
						'amount'           => $this->get_amount_import( $object->amount_refunded, $entry['currency'] ),
					);
				}
				break;

			case 'payment_intent.succeeded':
				$entry = $this->get_entry_by_transaction_id( $object->id );

				if ( $entry && 'Paid' !== rgar( $entry, 'payment_status' ) ) {
					$action = array(
						'id'             => $event->id,
						'type'           => 'complete_payment',
						'transaction_id' => $object->id,
						'entry_id'       => $entry['id'],
						'amount'         => $this->get_amount_import( $object->amount_received, $entry['currency'] ),
					);
				}
				break;

			case 'payment_intent.payment_failed':
				$entry = $this->get_entry_by_transaction_id( $object->id );

				if ( $entry ) {
					$action = array(
						'id'       => $event->id,
						'type'     => 'fail_payment',
						'entry_id' => $entry['id'],
						'note'     => esc_html__( 'The payment failed at Stripe.', 'kdnaforms-stripe' ),
					);
				}
				break;

			case 'invoice.payment_succeeded':
				$entry = $this->get_entry_by_transaction_id( $object->subscription );

				if ( $entry ) {
					$action = array(
						'id'              => $event->id,
						'type'            => 'add_subscription_payment',
						'subscription_id' => $object->subscription,
						'entry_id'        => $entry['id'],
						'amount'          => $this->get_amount_import( $object->amount_paid, $entry['currency'] ),
						'transaction_id'  => $object->charge,
					);
				}
				break;

			case 'invoice.payment_failed':
				$entry = $this->get_entry_by_transaction_id( $object->subscription );

				if ( $entry ) {
					$action = array(
						'id'              => $event->id,
						'type'            => 'fail_subscription_payment',
						'subscription_id' => $object->subscription,
						'entry_id'        => $entry['id'],
						'amount'          => $this->get_amount_import( $object->amount_due, $entry['currency'] ),
					);
				}
				break;

			case 'customer.subscription.deleted':
				$entry = $this->get_entry_by_transaction_id( $object->id );

				if ( $entry ) {
					$action = array(
						'id'              => $event->id,
						'type'            => 'cancel_subscription',
						'subscription_id' => $object->id,
						'entry_id'        => $entry['id'],
					);
				}
				break;
		}

		/**
		 * Filters the action taken for a Stripe webhook event.
		 *
		 * @since 1.0.0
		 *
		 * @param array         $action The action, empty to do nothing.
		 * @param \Stripe\Event $event  The event received.
		 */
		return apply_filters( 'kdnaform_stripe_webhook_action', $action, $event );
	}

	/**
	 * Applies a callback action through the framework.
	 *
	 * @since 1.0.0
	 *
	 * @param array $action The action.
	 *
	 * @return void
	 */
	protected function process_callback_action( $action ) {

		$entry = KDNAFormsModel::get_entry( rgar( $action, 'entry_id' ) );

		if ( empty( $entry ) || is_wp_error( $entry ) ) {
			return;
		}

		switch ( rgar( $action, 'type' ) ) {
			case 'complete_payment':
				$this->complete_payment( $entry, $action );
				break;
			case 'refund_payment':
				$this->refund_payment( $entry, $action );
				break;
			case 'fail_payment':
				$this->fail_payment( $entry, $action );
				break;
			case 'add_subscription_payment':
				$this->add_subscription_payment( $entry, $action );
				break;
			case 'fail_subscription_payment':
				$this->fail_subscription_payment( $entry, $action );
				break;
			case 'cancel_subscription':
				$this->cancel_subscription( $entry, $this->get_payment_feed( $entry ), rgar( $action, "note" ) );
				break;
		}
	}
}
