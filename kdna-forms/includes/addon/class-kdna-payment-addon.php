<?php
/**
 * KDNA Forms payment add-on framework.
 *
 * Base class for add-ons that take payments (Stripe, PayPal, etc). It sits
 * between KDNAFeedAddOn and the individual gateway add-on, and owns everything
 * that is the same no matter which gateway is being used:
 *
 *   - reading the pricing fields on a form into a normalised order
 *   - running the gateway at the right moment during submission
 *   - writing payment status, amount and transaction id onto the entry
 *   - recording every transaction in its own table
 *   - the shared feed settings (payment amount, billing info, trials, etc)
 *
 * A gateway add-on subclasses this and implements authorize(), capture() and
 * subscribe(). Everything else is inherited.
 *
 * @package KDNA_Forms
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'KDNAFeedAddOn' ) ) {
	require_once __DIR__ . '/class-kdna-feed-addon.php';
}

/**
 * Class KDNAPaymentAddOn
 */
abstract class KDNAPaymentAddOn extends KDNAFeedAddOn {

	/**
	 * Schema version for the payment tables. Bump to trigger a re-run of dbDelta.
	 *
	 * @var string
	 */
	private static $payment_schema_version = '1.0';

	/**
	 * Whether this gateway needs a credit card field on the form.
	 *
	 * Gateways that collect card details in their own hosted/iframe UI (most
	 * modern ones) leave this false.
	 *
	 * @var bool
	 */
	protected $_requires_credit_card = false;

	/**
	 * Whether this gateway sends server-to-server notifications (webhooks).
	 *
	 * @var bool
	 */
	protected $_supports_callbacks = false;

	/**
	 * Slug written to the entry's payment_method column.
	 *
	 * @var string
	 */
	protected $_payment_method = '';

	/**
	 * The feed currently being processed.
	 *
	 * @var array|null
	 */
	protected $current_feed = null;

	/**
	 * The normalised order data for the submission currently being processed.
	 *
	 * @var array|false
	 */
	protected $current_submission_data = false;

	/**
	 * Whether this add-on is the gateway handling the current submission.
	 *
	 * @var bool
	 */
	protected $is_payment_gateway = false;

	/**
	 * Result returned by authorize() for the current submission.
	 *
	 * @var array
	 */
	protected $authorization = array();

	/**
	 * Redirect URL set by a gateway that needs to send the user off-site.
	 *
	 * @var string
	 */
	protected $redirect_url = '';

	// -------------------------------------------------------------------------
	// Bootstrap
	// -------------------------------------------------------------------------

	/**
	 * Register the hooks that have to be in place before init.
	 *
	 * @return void
	 */
	public function pre_init() {
		parent::pre_init();

		// The gateway runs after the entry exists but before the confirmation.
		add_filter( 'kdnaform_entry_post_save', array( $this, 'entry_post_save' ), 10, 2 );
		add_filter( 'kdnaform_validation', array( $this, 'validation' ), 20 );

		if ( $this->_supports_callbacks ) {
			add_action( 'wp', array( $this, 'maybe_process_callback' ), 5 );
		}
	}

	/**
	 * Set up the add-on.
	 *
	 * @return void
	 */
	public function init() {
		parent::init();

		add_filter( 'kdnaform_currency', array( $this, 'maybe_override_currency' ), 5 );
		add_action( 'kdnaform_after_delete_field', array( $this, 'before_delete_field' ), 10, 2 );
	}

	/**
	 * Admin-side setup: entry detail payment box and feed ordering.
	 *
	 * @return void
	 */
	public function init_admin() {
		parent::init_admin();

		add_action( 'kdnaform_payment_details', array( $this, 'entry_detail_payment_details' ), 10, 2 );
		add_filter( 'kdnaform_entry_meta', array( $this, 'get_entry_meta' ), 10, 2 );
	}

	/**
	 * Create the payment tables if they are missing.
	 *
	 * The core plugin references these two tables in its maintenance routines
	 * but never creates them, so the framework installs its own schema.
	 *
	 * @return void
	 */
	public static function maybe_install_payment_tables() {
		if ( get_option( 'kdnaform_payment_schema_version' ) === self::$payment_schema_version ) {
			return;
		}

		global $wpdb;

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$charset = $wpdb->get_charset_collate();

		$transaction_table = self::get_transaction_table_name();
		$callback_table    = self::get_callback_table_name();

		dbDelta(
			"CREATE TABLE {$transaction_table} (
				id int(10) unsigned not null auto_increment,
				lead_id int(10) unsigned not null,
				transaction_type varchar(30) not null,
				transaction_id varchar(50),
				subscription_id varchar(50),
				is_recurring tinyint(1) not null default 0,
				amount decimal(19,2),
				date_created datetime,
				PRIMARY KEY  (id),
				KEY lead_id (lead_id),
				KEY transaction_type (transaction_type),
				KEY type_lead_id (lead_id,transaction_type)
			) {$charset};"
		);

		dbDelta(
			"CREATE TABLE {$callback_table} (
				id int(10) unsigned not null auto_increment,
				addon_slug varchar(50) not null,
				callback_id varchar(250) not null,
				date_created datetime,
				PRIMARY KEY  (id),
				UNIQUE KEY slug_callback_id (addon_slug,callback_id)
			) {$charset};"
		);

		update_option( 'kdnaform_payment_schema_version', self::$payment_schema_version );
	}

	/**
	 * Full name of the transaction table.
	 *
	 * @return string
	 */
	public static function get_transaction_table_name() {
		global $wpdb;

		return $wpdb->prefix . 'kdna_addon_payment_transaction';
	}

	/**
	 * Full name of the callback (webhook de-duplication) table.
	 *
	 * @return string
	 */
	public static function get_callback_table_name() {
		global $wpdb;

		return $wpdb->prefix . 'kdna_addon_payment_callback';
	}

	// -------------------------------------------------------------------------
	// Submission flow
	// -------------------------------------------------------------------------

	/**
	 * Validate the submission before the entry is created.
	 *
	 * Runs the credit card checks where the gateway needs them, then hands the
	 * result to the gateway so it can add its own errors.
	 *
	 * @param array $validation_result The current validation result.
	 *
	 * @return array
	 */
	public function validation( $validation_result ) {
		if ( ! $this->has_feed( rgars( $validation_result, 'form/id' ) ) ) {
			return $validation_result;
		}

		$form  = $validation_result['form'];
		$entry = KDNAFormsModel::create_lead( $form );
		$feed  = $this->get_payment_feed( $entry, $form );

		if ( ! $feed || ! $this->is_feed_condition_met( $feed, $form, $entry ) ) {
			return $validation_result;
		}

		$submission_data = $this->get_submission_data( $feed, $form, $entry );

		// Nothing to charge means nothing to validate.
		if ( ! $this->is_valid_payment_amount( $submission_data ) ) {
			return $validation_result;
		}

		if ( $this->_requires_credit_card && ! $this->has_credit_card_field( $form ) ) {
			$validation_result['is_valid'] = false;

			return $validation_result;
		}

		$this->is_payment_gateway      = true;
		$this->current_feed            = $feed;
		$this->current_submission_data = $submission_data;

		// Authorize while the submission can still be stopped. Doing it here
		// rather than after the entry is saved is what lets a declined card
		// come back as a validation error on the form instead of an entry that
		// exists but was never paid for.
		//
		// Wrapped because a gateway throwing here takes the whole submission
		// down as a 500 with nothing in the response to say why — the customer
		// sees a dead form and the site owner sees a blank error log entry at
		// best. Catching it turns that into a logged reason and a message on
		// the form.
		try {
			if ( 'subscription' === rgars( $feed, 'meta/transactionType' ) ) {
				$this->authorization = $this->subscribe( $feed, $submission_data, $form, $entry );
			} else {
				$this->authorization = $this->authorize( $feed, $submission_data, $form, $entry );
			}
		} catch ( \Throwable $e ) {
			$this->log_error(
				sprintf(
					'%s(): the gateway threw %s in %s on line %d: %s',
					__METHOD__,
					get_class( $e ),
					$e->getFile(),
					$e->getLine(),
					$e->getMessage()
				)
			);

			$this->authorization = array(
				'is_authorized' => false,
				'error_message' => esc_html__( 'The payment could not be processed. Please try again, or contact us if the problem continues.', 'kdnaforms' ),
			);
		}

		if ( ! is_array( $this->authorization ) ) {
			$this->authorization = array();
		}

		// A gateway that reports neither outcome has authorized nothing, and
		// treating silence as success would save an unpaid entry as Paid.
		$succeeded = rgar( $this->authorization, 'is_authorized' ) || rgar( $this->authorization, 'is_success' );

		if ( ! $succeeded ) {
			$validation_result = $this->get_validation_result( $validation_result, $this->authorization );
		}

		return $validation_result;
	}

	/**
	 * Merge a gateway authorization failure into the validation result.
	 *
	 * Marks the credit card field invalid (or the form as a whole where there
	 * is no card field) so the user sees the gateway's message in place.
	 *
	 * @param array $validation_result   The current validation result.
	 * @param array $authorization_result The result returned by the gateway.
	 *
	 * @return array
	 */
	public function get_validation_result( $validation_result, $authorization_result ) {
		if ( rgar( $authorization_result, 'is_authorized' ) ) {
			return $validation_result;
		}

		$message      = rgar( $authorization_result, 'error_message' );
		$card_matched = false;

		foreach ( $validation_result['form']['fields'] as &$field ) {
			if ( $field->type !== 'creditcard' ) {
				continue;
			}

			$field->failed_validation  = true;
			$field->validation_message = $message;
			$card_matched              = true;
			break;
		}

		if ( ! $card_matched ) {
			// No card field on the form, so surface the error at form level.
			$validation_result['payment_error'] = $message;
		}

		$validation_result['is_valid'] = false;

		return $validation_result;
	}

	/**
	 * Run the gateway once the entry has been saved.
	 *
	 * This is the main entry point. Single payments are authorized and then
	 * captured; subscriptions are handed to process_subscription().
	 *
	 * @param array $entry The entry that was just created.
	 * @param array $form  The form that was submitted.
	 *
	 * @return array The entry, possibly with payment fields populated.
	 */
	public function entry_post_save( $entry, $form ) {
		if ( ! $this->is_payment_gateway ) {
			return $entry;
		}

		$feed            = $this->current_feed;
		$submission_data = $this->current_submission_data;

		if ( empty( $feed ) || empty( $submission_data ) ) {
			return $entry;
		}

		$entry['payment_method'] = $this->_payment_method;

		$transaction_type = rgars( $feed, 'meta/transactionType' );

		// Same reasoning as validation(): by this point the entry exists, so a
		// throw here loses the submission entirely rather than recording it
		// unpaid.
		try {
			if ( 'subscription' === $transaction_type ) {
				$entry = $this->process_subscription( $this->authorization, $feed, $submission_data, $form, $entry );
			} else {
				$entry = $this->process_capture( $this->authorization, $feed, $submission_data, $form, $entry );
			}
		} catch ( \Throwable $e ) {
			$this->log_error(
				sprintf(
					'%s(): processing threw %s in %s on line %d: %s',
					__METHOD__,
					get_class( $e ),
					$e->getFile(),
					$e->getLine(),
					$e->getMessage()
				)
			);

			$entry['payment_status'] = 'Failed';
			$this->add_note( rgar( $entry, 'id' ), $e->getMessage(), 'error' );
		}

		KDNAAPI::update_entry( $entry );

		$this->post_payment_action( $entry, $this->authorization );

		return $entry;
	}

	/**
	 * Capture an authorized single payment and write the result to the entry.
	 *
	 * @param array $authorization   The result of authorize().
	 * @param array $feed            The feed being processed.
	 * @param array $submission_data The normalised order data.
	 * @param array $form            The form being submitted.
	 * @param array $entry           The entry.
	 *
	 * @return array The updated entry.
	 */
	public function process_capture( $authorization, $feed, $submission_data, $form, $entry ) {
		if ( empty( $authorization ) ) {
			return $entry;
		}

		if ( ! rgar( $authorization, 'is_authorized' ) ) {
			$entry['payment_status'] = 'Failed';

			$this->add_note(
				$entry['id'],
				sprintf(
					/* translators: %s: the error returned by the payment gateway. */
					esc_html__( 'Payment failed. Reason: %s', 'kdnaforms' ),
					rgar( $authorization, 'error_message' )
				),
				'error'
			);

			return $entry;
		}

		// Some gateways capture during authorize(); others return a capture block.
		$capture = rgar( $authorization, 'captured_payment' );

		if ( empty( $capture ) ) {
			$capture = $this->capture( $authorization, $feed, $submission_data, $form, $entry );
		}

		if ( rgar( $capture, 'is_success' ) ) {
			$entry['transaction_id']   = rgar( $capture, 'transaction_id' );
			$entry['payment_amount']   = rgar( $capture, 'amount' );
			$entry['payment_date']     = gmdate( 'Y-m-d H:i:s' );
			$entry['payment_status']   = 'Paid';
			$entry['transaction_type'] = 1;
			$entry['is_fulfilled']     = 1;

			$this->insert_transaction( $entry['id'], 'payment', $entry['transaction_id'], $entry['payment_amount'] );

			$this->add_note(
				$entry['id'],
				sprintf(
					/* translators: 1: formatted amount, 2: gateway transaction id. */
					esc_html__( 'Payment of %1$s completed. Transaction ID: %2$s', 'kdnaforms' ),
					KDNACommon::to_money( $entry['payment_amount'], $entry['currency'] ),
					$entry['transaction_id']
				),
				'success'
			);
		} else {
			$entry['payment_status'] = 'Failed';

			$this->add_note(
				$entry['id'],
				sprintf(
					/* translators: %s: the error returned by the payment gateway. */
					esc_html__( 'Payment failed. Reason: %s', 'kdnaforms' ),
					rgar( $capture, 'error_message' )
				),
				'error'
			);
		}

		return $entry;
	}

	/**
	 * Mark an authorization complete without capturing funds.
	 *
	 * Used by gateways that authorize now and capture later.
	 *
	 * @param array $entry  The entry, by reference.
	 * @param array $action The action describing the authorization.
	 *
	 * @return bool
	 */
	public function complete_authorization( &$entry, $action ) {
		$entry['transaction_id']   = rgar( $action, 'transaction_id' );
		$entry['transaction_type'] = 1;
		$entry['payment_status']   = 'Authorized';

		if ( isset( $action['amount'] ) ) {
			$entry['payment_amount'] = $action['amount'];
		}

		KDNAAPI::update_entry( $entry );

		$this->insert_transaction(
			$entry['id'],
			'authorization',
			rgar( $action, 'transaction_id' ),
			rgar( $action, 'amount' )
		);

		if ( ! rgempty( 'note', $action ) ) {
			$this->add_note( $entry['id'], $action['note'], 'success' );
		}

		return true;
	}

	/**
	 * Mark a previously authorized payment as paid.
	 *
	 * @param array $entry  The entry, by reference.
	 * @param array $action The action describing the payment.
	 *
	 * @return bool
	 */
	public function complete_payment( &$entry, $action ) {
		if ( ! rgar( $action, 'payment_status' ) ) {
			$action['payment_status'] = 'Paid';
		}

		if ( ! rgar( $action, 'payment_date' ) ) {
			$action['payment_date'] = gmdate( 'Y-m-d H:i:s' );
		}

		$entry['is_fulfilled']    = 1;
		$entry['transaction_id']  = rgar( $action, 'transaction_id' );
		$entry['payment_status']  = $action['payment_status'];
		$entry['payment_date']    = $action['payment_date'];

		if ( isset( $action['amount'] ) ) {
			$entry['payment_amount'] = $action['amount'];
		}

		KDNAAPI::update_entry( $entry );

		$this->insert_transaction(
			$entry['id'],
			'payment',
			rgar( $action, 'transaction_id' ),
			rgar( $action, 'amount' )
		);

		if ( ! rgempty( 'note', $action ) ) {
			$this->add_note( $entry['id'], $action['note'], 'success' );
		}

		return true;
	}

	/**
	 * Record a refund against an entry.
	 *
	 * @param array $entry  The entry, by reference.
	 * @param array $action The action describing the refund.
	 *
	 * @return bool
	 */
	public function refund_payment( &$entry, $action ) {
		$entry['payment_status'] = 'Refunded';
		$entry['is_fulfilled']   = 0;

		KDNAAPI::update_entry( $entry );

		$this->insert_transaction(
			$entry['id'],
			'refund',
			rgar( $action, 'transaction_id' ),
			rgar( $action, 'amount' )
		);

		$note = rgar( $action, 'note' );

		if ( empty( $note ) ) {
			$note = sprintf(
				/* translators: %s: formatted refund amount. */
				esc_html__( 'Payment refunded. Amount: %s', 'kdnaforms' ),
				KDNACommon::to_money( rgar( $action, 'amount' ), rgar( $entry, 'currency' ) )
			);
		}

		$this->add_note( $entry['id'], $note, 'success' );

		return true;
	}

	/**
	 * Record a payment that the gateway rejected.
	 *
	 * The entry is kept rather than discarded: someone has filled the form in,
	 * and a failed payment is something the site owner needs to see, not
	 * something to lose quietly.
	 *
	 * @since 3.5.1
	 *
	 * @param array $entry  The entry, by reference.
	 * @param array $action The action describing the failure.
	 *
	 * @return bool
	 */
	public function fail_payment( &$entry, $action ) {
		$entry['payment_status'] = 'Failed';
		$entry['is_fulfilled']   = 0;

		KDNAAPI::update_entry( $entry );

		$note = rgar( $action, 'note' );

		if ( empty( $note ) ) {
			$note = esc_html__( 'The payment failed.', 'kdnaforms' );
		}

		$this->add_note( $entry['id'], $note, 'error' );

		return true;
	}

	/**
	 * Record a successful recurring payment against a subscription.
	 *
	 * Each renewal is its own transaction row, so the entry carries the whole
	 * billing history rather than only the most recent charge.
	 *
	 * @since 3.5.1
	 *
	 * @param array $entry  The entry, by reference.
	 * @param array $action The action describing the payment.
	 *
	 * @return bool
	 */
	public function add_subscription_payment( &$entry, $action ) {
		$entry['payment_status'] = 'Active';
		$entry['is_fulfilled']   = 1;

		KDNAAPI::update_entry( $entry );

		$this->insert_transaction(
			$entry['id'],
			'payment',
			rgar( $action, 'transaction_id' ),
			rgar( $action, 'amount' ),
			true
		);

		$note = rgar( $action, 'note' );

		if ( empty( $note ) ) {
			$note = sprintf(
				/* translators: 1: formatted amount, 2: the subscription id. */
				esc_html__( 'Subscription payment received. Amount: %1$s. Subscription: %2$s', 'kdnaforms' ),
				KDNACommon::to_money( rgar( $action, 'amount' ), rgar( $entry, 'currency' ) ),
				rgar( $action, 'subscription_id' )
			);
		}

		$this->add_note( $entry['id'], $note, 'success' );

		return true;
	}

	/**
	 * Record a recurring payment the gateway could not take.
	 *
	 * The subscription itself is left alone. A card can fail once and succeed
	 * on the gateway's retry, so cancelling here would end subscriptions that
	 * are about to recover on their own.
	 *
	 * @since 3.5.1
	 *
	 * @param array $entry  The entry, by reference.
	 * @param array $action The action describing the failure.
	 *
	 * @return bool
	 */
	public function fail_subscription_payment( &$entry, $action ) {
		$entry['payment_status'] = 'Failed';

		KDNAAPI::update_entry( $entry );

		$note = rgar( $action, 'note' );

		if ( empty( $note ) ) {
			$note = sprintf(
				/* translators: 1: formatted amount, 2: the subscription id. */
				esc_html__( 'Subscription payment failed. Amount: %1$s. Subscription: %2$s', 'kdnaforms' ),
				KDNACommon::to_money( rgar( $action, 'amount' ), rgar( $entry, 'currency' ) ),
				rgar( $action, 'subscription_id' )
			);
		}

		$this->add_note( $entry['id'], $note, 'error' );

		return true;
	}

	/**
	 * Mark a subscription as cancelled.
	 *
	 * @since 3.5.1
	 *
	 * @param array       $entry The entry, by reference.
	 * @param array|false $feed  The feed the subscription belongs to.
	 * @param string      $note  Optional note to record instead of the default.
	 *
	 * @return bool
	 */
	public function cancel_subscription( &$entry, $feed = false, $note = '' ) {
		$entry['payment_status'] = 'Cancelled';

		KDNAAPI::update_entry( $entry );

		if ( empty( $note ) ) {
			$note = sprintf(
				/* translators: %s: the subscription id. */
				esc_html__( 'Subscription cancelled. Subscription: %s', 'kdnaforms' ),
				rgar( $entry, 'transaction_id' )
			);
		}

		$this->add_note( $entry['id'], $note, 'success' );

		/**
		 * Fires once a subscription has been marked cancelled.
		 *
		 * @since 3.5.1
		 *
		 * @param array       $entry The entry.
		 * @param array|false $feed  The feed the subscription belongs to.
		 */
		do_action( 'kdnaform_subscription_canceled', $entry, $feed );

		return true;
	}

	/**
	 * Create a subscription and write the result to the entry.
	 *
	 * @param array $authorization   The result of authorize()/subscribe().
	 * @param array $feed            The feed being processed.
	 * @param array $submission_data The normalised order data.
	 * @param array $form            The form being submitted.
	 * @param array $entry           The entry.
	 *
	 * @return array The updated entry.
	 */
	public function process_subscription( $authorization, $feed, $submission_data, $form, $entry ) {
		$subscription = rgar( $authorization, 'subscription' );

		if ( empty( $subscription ) ) {
			return $entry;
		}

		if ( ! rgar( $subscription, 'is_success' ) ) {
			$entry['payment_status'] = 'Failed';

			$this->add_note(
				$entry['id'],
				sprintf(
					/* translators: %s: the error returned by the payment gateway. */
					esc_html__( 'Subscription failed. Reason: %s', 'kdnaforms' ),
					rgar( $subscription, 'error_message' )
				),
				'error'
			);

			return $entry;
		}

		$entry['transaction_id']   = rgar( $subscription, 'subscription_id' );
		$entry['payment_amount']   = rgar( $subscription, 'amount' );
		$entry['payment_status']   = 'Active';
		$entry['payment_date']     = gmdate( 'Y-m-d H:i:s' );
		$entry['transaction_type'] = 2;
		$entry['is_fulfilled']     = 1;

		$this->insert_transaction(
			$entry['id'],
			'subscription',
			rgar( $subscription, 'subscription_id' ),
			rgar( $subscription, 'amount' ),
			true
		);

		$this->add_note(
			$entry['id'],
			sprintf(
				/* translators: %s: the gateway subscription id. */
				esc_html__( 'Subscription created. Subscription ID: %s', 'kdnaforms' ),
				$entry['transaction_id']
			),
			'success'
		);

		return $entry;
	}

	/**
	 * Hook for gateways to act once payment processing has finished.
	 *
	 * @param array $entry         The entry.
	 * @param array $authorization The authorization result.
	 *
	 * @return void
	 */
	public function post_payment_action( $entry, $authorization ) {
		/**
		 * Fires after a payment add-on has finished processing an entry.
		 *
		 * @param array  $entry         The entry.
		 * @param array  $authorization The authorization result.
		 * @param string $slug          The add-on slug.
		 */
		do_action( 'kdnaform_post_payment_action', $entry, $authorization, $this->get_slug() );
	}

	// -------------------------------------------------------------------------
	// Gateway contract
	// -------------------------------------------------------------------------

	/**
	 * Authorize a single payment.
	 *
	 * Gateways must return an array with at least is_authorized, and either
	 * error_message on failure or transaction_id/amount on success. A gateway
	 * that captures immediately may also return a captured_payment array.
	 *
	 * @param array $feed            The feed being processed.
	 * @param array $submission_data The normalised order data.
	 * @param array $form            The form being submitted.
	 * @param array $entry           The entry.
	 *
	 * @return array
	 */
	public function authorize( $feed, $submission_data, $form, $entry ) {
		return array(
			'is_authorized' => false,
			'error_message' => esc_html__( 'This payment gateway does not support single payments.', 'kdnaforms' ),
		);
	}

	/**
	 * Capture a previously authorized payment.
	 *
	 * @param array $authorization   The result of authorize().
	 * @param array $feed            The feed being processed.
	 * @param array $submission_data The normalised order data.
	 * @param array $form            The form being submitted.
	 * @param array $entry           The entry.
	 *
	 * @return array
	 */
	public function capture( $authorization, $feed, $submission_data, $form, $entry ) {
		return array(
			'is_success'    => false,
			'error_message' => esc_html__( 'This payment gateway does not support capturing payments.', 'kdnaforms' ),
		);
	}

	/**
	 * Create a subscription.
	 *
	 * @param array $feed            The feed being processed.
	 * @param array $submission_data The normalised order data.
	 * @param array $form            The form being submitted.
	 * @param array $entry           The entry.
	 *
	 * @return array
	 */
	public function subscribe( $feed, $submission_data, $form, $entry ) {
		return array(
			'is_success'    => false,
			'error_message' => esc_html__( 'This payment gateway does not support subscriptions.', 'kdnaforms' ),
		);
	}

	// -------------------------------------------------------------------------
	// Order data
	// -------------------------------------------------------------------------

	/**
	 * Build the normalised order for a submission.
	 *
	 * Returns line items, the payment amount, and the billing/card details the
	 * gateway needs, all resolved from the feed's field mappings.
	 *
	 * @param array $feed  The feed being processed.
	 * @param array $form  The form being submitted.
	 * @param array $entry The entry.
	 *
	 * @return array|false
	 */
	public function get_submission_data( $feed, $form, $entry ) {
		$submission_data = $this->get_order_data( $feed, $form, $entry );

		if ( empty( $submission_data ) ) {
			return false;
		}

		// Card details, where the form collects them directly.
		$card_field = $this->get_credit_card_field( $form );

		if ( $card_field ) {
			$submission_data['card_number']          = rgpost( 'input_' . $card_field->id . '_1' );
			$submission_data['card_expiration_date'] = rgpost( 'input_' . $card_field->id . '_2' );
			$submission_data['card_security_code']   = rgpost( 'input_' . $card_field->id . '_3' );
			$submission_data['card_name']            = rgpost( 'input_' . $card_field->id . '_5' );
		}

		// Billing details, resolved from the feed's field map.
		$billing_fields = array(
			'email'     => 'email',
			'firstName' => 'first_name',
			'lastName'  => 'last_name',
			'address'   => 'address',
			'address2'  => 'address2',
			'city'      => 'city',
			'state'     => 'state',
			'zip'       => 'zip',
			'country'   => 'country',
		);

		foreach ( $billing_fields as $meta_key => $data_key ) {
			$field_id = rgars( $feed, 'meta/billingInformation_' . $meta_key );

			if ( ! empty( $field_id ) ) {
				$submission_data[ $data_key ] = $this->get_field_value( $form, $entry, $field_id );
			}
		}

		/**
		 * Filters the submission data before it is handed to the gateway.
		 *
		 * Applied both generically and per-form, so a site can hook either
		 * 'kdnaform_submission_data_pre_process_payment' or the form-specific
		 * 'kdnaform_submission_data_pre_process_payment_{form_id}'.
		 *
		 * @param array $submission_data The normalised order data.
		 * @param array $feed            The feed being processed.
		 * @param array $form            The form being submitted.
		 * @param array $entry           The entry.
		 */
		$submission_data = apply_filters(
			'kdnaform_submission_data_pre_process_payment',
			$submission_data,
			$feed,
			$form,
			$entry
		);

		return apply_filters(
			'kdnaform_submission_data_pre_process_payment_' . rgar( $form, 'id' ),
			$submission_data,
			$feed,
			$form,
			$entry
		);
	}

	/**
	 * Read the form's pricing fields into line items and a total.
	 *
	 * Respects the feed's paymentAmount setting: either the form total, or a
	 * single named product field.
	 *
	 * @param array $feed  The feed being processed.
	 * @param array $form  The form being submitted.
	 * @param array $entry The entry.
	 *
	 * @return array
	 */
	public function get_order_data( $feed, $form, $entry ) {
		$products       = KDNACommon::get_product_fields( $form, $entry );
		$payment_field  = rgars( $feed, 'meta/paymentAmount' );
		$line_items     = array();
		$discounts      = array();
		$amount         = 0;

		foreach ( $products['products'] as $field_id => $product ) {

			// When the feed charges a single product, skip everything else.
			if ( 'form_total' !== $payment_field && (string) $field_id !== (string) $payment_field ) {
				continue;
			}

			$quantity   = $product['quantity'] ? (float) $product['quantity'] : 1;
			$unit_price = KDNACommon::to_number( $product['price'], rgar( $entry, 'currency' ) );
			$options    = array();

			if ( is_array( rgar( $product, 'options' ) ) ) {
				foreach ( $product['options'] as $option ) {
					$options[]   = $option['option_name'];
					$unit_price += KDNACommon::to_number( $option['price'], rgar( $entry, 'currency' ) );
				}
			}

			if ( $unit_price < 0 ) {
				// Negative priced items are discounts, not line items.
				$discounts[] = array(
					'id'          => $field_id,
					'name'        => $product['name'],
					'description' => implode( ', ', $options ),
					'quantity'    => $quantity,
					'unit_price'  => $unit_price,
				);
			} else {
				$line_items[] = array(
					'id'          => $field_id,
					'name'        => $product['name'],
					'description' => implode( ', ', $options ),
					'quantity'    => $quantity,
					'unit_price'  => $unit_price,
					'options'     => rgar( $product, 'options' ),
				);
			}

			$amount += $unit_price * $quantity;
		}

		// Shipping only applies when charging the whole form total.
		if ( 'form_total' === $payment_field && ! empty( $products['shipping']['name'] ) ) {
			$shipping_price = KDNACommon::to_number( $products['shipping']['price'], rgar( $entry, 'currency' ) );

			$line_items[] = array(
				'id'          => 'shipping',
				'name'        => $products['shipping']['name'],
				'description' => '',
				'quantity'    => 1,
				'unit_price'  => $shipping_price,
				'is_shipping' => 1,
			);

			$amount += $shipping_price;
		}

		$order_data = array(
			'payment_amount' => $amount,
			'setup_fee'      => 0,
			'trial'          => 0,
			'line_items'     => $line_items,
			'discounts'      => $discounts,
		);

		// Subscription extras.
		if ( 'subscription' === rgars( $feed, 'meta/transactionType' ) ) {
			$order_data['setup_fee'] = $this->get_feed_amount( $feed, 'setupFee', $form, $entry );
			$order_data['trial']     = $this->get_feed_amount( $feed, 'trial', $form, $entry );
		}

		return $order_data;
	}

	/**
	 * Resolve a feed amount setting to a number.
	 *
	 * Handles both "enabled with a fixed amount" and "mapped to a form field".
	 *
	 * @param array  $feed    The feed being processed.
	 * @param string $setting The setting prefix, e.g. setupFee or trial.
	 * @param array  $form    The form being submitted.
	 * @param array  $entry   The entry.
	 *
	 * @return float
	 */
	protected function get_feed_amount( $feed, $setting, $form, $entry ) {
		if ( ! rgars( $feed, 'meta/' . $setting . '_enabled' ) ) {
			return 0;
		}

		$field_id = rgars( $feed, 'meta/' . $setting . '_product' );

		if ( ! empty( $field_id ) ) {
			$products = KDNACommon::get_product_fields( $form, $entry );

			if ( isset( $products['products'][ $field_id ] ) ) {
				return KDNACommon::to_number(
					$products['products'][ $field_id ]['price'],
					rgar( $entry, 'currency' )
				);
			}
		}

		return (float) rgars( $feed, 'meta/' . $setting . '_amount' );
	}

	/**
	 * Whether there is anything worth sending to the gateway.
	 *
	 * @param array $submission_data The normalised order data.
	 *
	 * @return bool
	 */
	public function is_valid_payment_amount( $submission_data ) {
		if ( empty( $submission_data ) ) {
			return false;
		}

		$amount    = (float) rgar( $submission_data, 'payment_amount' );
		$setup_fee = (float) rgar( $submission_data, 'setup_fee' );

		return ( $amount + $setup_fee ) > 0;
	}

	// -------------------------------------------------------------------------
	// Currency helpers
	// -------------------------------------------------------------------------

	/**
	 * Convert an amount to the smallest currency unit for the gateway.
	 *
	 * Most gateways want integer cents. Zero-decimal currencies (JPY and
	 * friends) are passed through whole.
	 *
	 * @param float  $amount   The amount in major units.
	 * @param string $currency The currency code. Defaults to the form currency.
	 *
	 * @return int|float
	 */
	public function get_amount_export( $amount, $currency = '' ) {
		if ( empty( $currency ) ) {
			$currency = KDNACommon::get_currency();
		}

		if ( $this->is_zero_decimal_currency( $currency ) ) {
			return (int) round( $amount );
		}

		return (int) round( $amount * 100 );
	}

	/**
	 * Convert an amount back from the gateway's smallest currency unit.
	 *
	 * @param int|float $amount   The amount in minor units.
	 * @param string    $currency The currency code. Defaults to the form currency.
	 *
	 * @return float
	 */
	public function get_amount_import( $amount, $currency = '' ) {
		if ( empty( $currency ) ) {
			$currency = KDNACommon::get_currency();
		}

		if ( $this->is_zero_decimal_currency( $currency ) ) {
			return (float) $amount;
		}

		return (float) $amount / 100;
	}

	/**
	 * Whether a currency has no minor unit.
	 *
	 * @param string $currency The currency code.
	 *
	 * @return bool
	 */
	protected function is_zero_decimal_currency( $currency ) {
		$zero_decimal = array(
			'BIF', 'CLP', 'DJF', 'GNF', 'JPY', 'KMF', 'KRW', 'MGA',
			'PYG', 'RWF', 'UGX', 'VND', 'VUV', 'XAF', 'XOF', 'XPF',
		);

		return in_array( strtoupper( $currency ), $zero_decimal, true );
	}

	/**
	 * Allow a gateway to override the currency.
	 *
	 * @param string $currency The current currency code.
	 *
	 * @return string
	 */
	public function maybe_override_currency( $currency ) {
		return $currency;
	}

	// -------------------------------------------------------------------------
	// Field helpers
	// -------------------------------------------------------------------------

	/**
	 * Whether the form has a credit card field.
	 *
	 * @param array $form The form.
	 *
	 * @return bool
	 */
	public function has_credit_card_field( $form ) {
		return false !== $this->get_credit_card_field( $form );
	}

	/**
	 * Get the form's credit card field.
	 *
	 * @param array $form The form.
	 *
	 * @return object|false
	 */
	public function get_credit_card_field( $form ) {
		$fields = KDNAAPI::get_fields_by_type( $form, array( 'creditcard' ) );

		return empty( $fields ) ? false : $fields[0];
	}

	/**
	 * Clear the feed's field mappings when a mapped field is deleted.
	 *
	 * @param int $form_id  The form the field belonged to.
	 * @param int $field_id The field that was deleted.
	 *
	 * @return void
	 */
	public function before_delete_field( $form_id, $field_id ) {
		$feeds = $this->get_feeds( $form_id );

		foreach ( $feeds as $feed ) {
			$meta    = $feed['meta'];
			$changed = false;

			foreach ( $meta as $key => $value ) {
				if ( (string) $value === (string) $field_id && (
					'paymentAmount' === $key || 0 === strpos( $key, 'billingInformation_' )
				) ) {
					$meta[ $key ] = '';
					$changed      = true;
				}
			}

			if ( $changed ) {
				$this->update_feed_meta( $feed['id'], $meta );
			}
		}
	}

	/**
	 * Product fields available as a payment amount choice.
	 *
	 * @param array $form The form.
	 *
	 * @return array
	 */
	public function get_payment_choices( $form ) {
		$choices = array(
			array(
				'label' => esc_html__( 'Form Total', 'kdnaforms' ),
				'value' => 'form_total',
			),
		);

		$product_fields = KDNAAPI::get_fields_by_type( $form, array( 'product' ) );

		foreach ( $product_fields as $field ) {
			$choices[] = array(
				'label' => KDNAFormsModel::get_label( $field ),
				'value' => $field->id,
			);
		}

		return $choices;
	}

	// -------------------------------------------------------------------------
	// Feed helpers
	// -------------------------------------------------------------------------

	/**
	 * Get the payment feed that applies to an entry.
	 *
	 * @param array      $entry The entry.
	 * @param array|bool $form  The form, if already loaded.
	 *
	 * @return array|false
	 */
	public function get_payment_feed( $entry, $form = false ) {
		$feed_id = rgar( $entry, $this->get_slug() . '_feed_id' );

		if ( $feed_id ) {
			$feed = $this->get_feed( $feed_id );

			return ! empty( $feed ) ? $feed : false;
		}

		if ( ! $form ) {
			$form = KDNAFormsModel::get_form_meta( rgar( $entry, 'form_id' ) );
		}

		$feeds = $this->get_feeds( rgar( $form, 'id' ) );

		foreach ( $feeds as $feed ) {
			if ( ! rgar( $feed, 'is_active' ) ) {
				continue;
			}

			if ( $this->is_feed_condition_met( $feed, $form, $entry ) ) {
				return $feed;
			}
		}

		return false;
	}

	/**
	 * Whether this add-on is handling payment for the current submission.
	 *
	 * @param int $form_id The form id.
	 *
	 * @return bool
	 */
	public function is_payment_gateway( $form_id ) {
		if ( $this->is_payment_gateway ) {
			return true;
		}

		return $this->get_slug() === rgpost( 'kdnaform_payment_gateway' );
	}

	/**
	 * Find an entry by its gateway transaction id.
	 *
	 * @param string $transaction_id The gateway transaction or subscription id.
	 *
	 * @return array|false
	 */
	public function get_entry_by_transaction_id( $transaction_id ) {
		if ( empty( $transaction_id ) ) {
			return false;
		}

		$entries = KDNAAPI::get_entries(
			0,
			array(
				'field_filters' => array(
					array(
						'key'   => 'transaction_id',
						'value' => $transaction_id,
					),
				),
			),
			array(),
			array( 'offset' => 0, 'page_size' => 1 )
		);

		if ( is_wp_error( $entries ) || empty( $entries ) ) {
			return false;
		}

		return $entries[0];
	}

	/**
	 * Hold non-payment feeds until payment completes, where configured.
	 *
	 * @param bool   $is_delayed Whether processing is already delayed.
	 * @param array  $form       The form being submitted.
	 * @param array  $entry      The entry.
	 * @param string $slug       The slug of the add-on being considered.
	 *
	 * @return bool
	 */
	public function maybe_delay_feed_processing( $is_delayed, $form, $entry, $slug ) {
		if ( $is_delayed || ! $this->is_payment_gateway( rgar( $form, 'id' ) ) ) {
			return $is_delayed;
		}

		$feed = $this->current_feed ? $this->current_feed : $this->get_payment_feed( $entry, $form );

		if ( empty( $feed ) ) {
			return $is_delayed;
		}

		return (bool) rgars( $feed, 'meta/delay_' . $slug );
	}

	/**
	 * Run the feeds that were held back until payment completed.
	 *
	 * @param array $entry The entry.
	 * @param array $form  The form.
	 *
	 * @return void
	 */
	public function trigger_payment_delayed_feeds( $entry, $form ) {
		if ( empty( $entry ) || empty( $form ) ) {
			return;
		}

		/**
		 * Fires when payment has completed and delayed feeds should run.
		 *
		 * @param array  $entry The entry.
		 * @param array  $form  The form.
		 * @param string $slug  The payment add-on slug.
		 */
		do_action( 'kdnaform_payment_delayed_feeds', $entry, $form, $this->get_slug() );
	}

	// -------------------------------------------------------------------------
	// Transactions and notes
	// -------------------------------------------------------------------------

	/**
	 * Record a transaction against an entry.
	 *
	 * @param int    $entry_id       The entry id.
	 * @param string $type           payment, authorization, refund or subscription.
	 * @param string $transaction_id The gateway transaction id.
	 * @param float  $amount         The amount.
	 * @param bool   $is_recurring   Whether this is a recurring charge.
	 *
	 * @return int|false The inserted row id, or false on failure.
	 */
	public function insert_transaction( $entry_id, $type, $transaction_id, $amount, $is_recurring = false ) {
		global $wpdb;

		$table = self::get_transaction_table_name();

		$inserted = $wpdb->insert( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$table,
			array(
				'lead_id'          => $entry_id,
				'transaction_type' => $type,
				'transaction_id'   => $transaction_id,
				'amount'           => $amount,
				'is_recurring'     => $is_recurring ? 1 : 0,
				'date_created'     => gmdate( 'Y-m-d H:i:s' ),
			),
			array( '%d', '%s', '%s', '%f', '%d', '%s' )
		);

		return $inserted ? $wpdb->insert_id : false;
	}

	/**
	 * Whether a webhook has already been handled.
	 *
	 * Gateways can retry webhooks, so events are recorded once and ignored on
	 * any repeat delivery.
	 *
	 * @param string $callback_id The gateway's unique event id.
	 *
	 * @return bool
	 */
	public function is_duplicate_callback( $callback_id ) {
		global $wpdb;

		$table = self::get_callback_table_name();

		$found = $wpdb->get_var( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->prepare(
				"SELECT id FROM {$table} WHERE addon_slug = %s AND callback_id = %s", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$this->get_slug(),
				$callback_id
			)
		);

		return ! empty( $found );
	}

	/**
	 * Record a webhook so repeat deliveries can be ignored.
	 *
	 * @param string $callback_id The gateway's unique event id.
	 *
	 * @return void
	 */
	public function register_callback( $callback_id ) {
		global $wpdb;

		$wpdb->insert( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			self::get_callback_table_name(),
			array(
				'addon_slug'   => $this->get_slug(),
				'callback_id'  => $callback_id,
				'date_created' => gmdate( 'Y-m-d H:i:s' ),
			),
			array( '%s', '%s', '%s' )
		);
	}

	/**
	 * Entry point for gateways that receive webhooks.
	 *
	 * Subclasses override this.
	 *
	 * @return void
	 */
	public function maybe_process_callback() {
	}

	/**
	 * Add a note to an entry, attributed to this add-on.
	 *
	 * @param int    $entry_id The entry id.
	 * @param string $note     The note text.
	 * @param string $type     success, error or empty.
	 *
	 * @return void
	 */
	public function add_note( $entry_id, $note, $type = '' ) {
		KDNAFormsModel::add_note(
			$entry_id,
			0,
			$this->get_short_title(),
			$note,
			$this->get_slug(),
			$type
		);
	}

	// -------------------------------------------------------------------------
	// Entry meta and admin display
	// -------------------------------------------------------------------------

	/**
	 * Register the feed id as entry meta so it survives on the entry.
	 *
	 * @param array $entry_meta The current entry meta.
	 * @param int   $form_id    The form id.
	 *
	 * @return array
	 */
	public function get_entry_meta( $entry_meta, $form_id ) {
		$entry_meta[ $this->get_slug() . '_feed_id' ] = array(
			'label'                      => esc_html__( 'Payment Feed ID', 'kdnaforms' ),
			'is_numeric'                 => false,
			'is_default_column'          => false,
			'update_entry_meta_callback' => array( $this, 'noop_entry_meta' ),
		);

		return $entry_meta;
	}

	/**
	 * Placeholder callback for entry meta that is written directly.
	 *
	 * @param string $key   The meta key.
	 * @param array  $entry The entry.
	 * @param array  $form  The form.
	 *
	 * @return string
	 */
	public function noop_entry_meta( $key, $entry, $form ) {
		return rgar( $entry, $key, '' );
	}

	/**
	 * Render the payment box on the entry detail screen.
	 *
	 * @param int   $form_id The form id.
	 * @param array $entry   The entry.
	 *
	 * @return void
	 */
	public function entry_detail_payment_details( $form_id, $entry ) {
		if ( rgar( $entry, 'payment_method' ) !== $this->_payment_method ) {
			return;
		}

		$transaction_id = rgar( $entry, 'transaction_id' );

		if ( empty( $transaction_id ) ) {
			return;
		}

		printf(
			'<div class="kdnaform-payment-details"><p>%s</p></div>',
			esc_html(
				sprintf(
					/* translators: 1: gateway name, 2: transaction id. */
					__( 'Processed by %1$s. Transaction ID: %2$s', 'kdnaforms' ),
					$this->get_short_title(),
					$transaction_id
				)
			)
		);
	}

	// -------------------------------------------------------------------------
	// Shared feed settings
	// -------------------------------------------------------------------------

	/**
	 * The feed settings every payment gateway shares.
	 *
	 * Gateways call parent::feed_settings_fields() and then add their own.
	 *
	 * @return array
	 */
	public function feed_settings_fields() {
		$form = $this->get_current_form();

		return array(
			array(
				'title'  => esc_html__( 'Payment Settings', 'kdnaforms' ),
				'fields' => array(
					array(
						'name'     => 'feedName',
						'label'    => esc_html__( 'Name', 'kdnaforms' ),
						'type'     => 'text',
						'required' => true,
						'class'    => 'medium',
						'tooltip'  => esc_html__( 'Enter a name to help you identify this feed.', 'kdnaforms' ),
					),
					array(
						'name'          => 'transactionType',
						'label'         => esc_html__( 'Transaction Type', 'kdnaforms' ),
						'type'          => 'select',
						'required'      => true,
						'default_value' => 'product',
						'choices'       => array(
							array(
								'label' => esc_html__( 'Products and Services', 'kdnaforms' ),
								'value' => 'product',
							),
							array(
								'label' => esc_html__( 'Subscription', 'kdnaforms' ),
								'value' => 'subscription',
							),
						),
					),
					array(
						'name'     => 'paymentAmount',
						'label'    => esc_html__( 'Payment Amount', 'kdnaforms' ),
						'type'     => 'select',
						'required' => true,
						'choices'  => $this->get_payment_choices( $form ),
						'tooltip'  => esc_html__( 'Choose which value is sent to the payment gateway.', 'kdnaforms' ),
					),
					array(
						'name'       => 'billingInformation',
						'label'      => esc_html__( 'Billing Information', 'kdnaforms' ),
						'type'       => 'field_map',
						'field_map'  => $this->billing_info_fields(),
						'tooltip'    => esc_html__( 'Map your form fields to the billing details the gateway expects.', 'kdnaforms' ),
					),
					array(
						'name'    => 'conditionalLogic',
						'label'   => esc_html__( 'Conditional Logic', 'kdnaforms' ),
						'type'    => 'feed_condition',
						'tooltip' => esc_html__( 'Only take payment when the conditions below are met.', 'kdnaforms' ),
					),
				),
			),
		);
	}

	/**
	 * The billing fields offered in the feed's field map.
	 *
	 * @return array
	 */
	public function billing_info_fields() {
		return array(
			array( 'name' => 'email',     'label' => esc_html__( 'Email', 'kdnaforms' ),           'required' => false ),
			array( 'name' => 'firstName', 'label' => esc_html__( 'First Name', 'kdnaforms' ),      'required' => false ),
			array( 'name' => 'lastName',  'label' => esc_html__( 'Last Name', 'kdnaforms' ),       'required' => false ),
			array( 'name' => 'address',   'label' => esc_html__( 'Address', 'kdnaforms' ),         'required' => false ),
			array( 'name' => 'address2',  'label' => esc_html__( 'Address 2', 'kdnaforms' ),       'required' => false ),
			array( 'name' => 'city',      'label' => esc_html__( 'City', 'kdnaforms' ),            'required' => false ),
			array( 'name' => 'state',     'label' => esc_html__( 'State', 'kdnaforms' ),           'required' => false ),
			array( 'name' => 'zip',       'label' => esc_html__( 'Zip', 'kdnaforms' ),             'required' => false ),
			array( 'name' => 'country',   'label' => esc_html__( 'Country', 'kdnaforms' ),         'required' => false ),
		);
	}

	/**
	 * Columns shown on the feed list.
	 *
	 * @return array
	 */
	public function feed_list_columns() {
		return array(
			'feedName'        => esc_html__( 'Name', 'kdnaforms' ),
			'transactionType' => esc_html__( 'Transaction Type', 'kdnaforms' ),
		);
	}
}
