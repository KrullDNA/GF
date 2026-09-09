<?php
/**
 * The Stripe Card field.
 *
 * This is where Stripe mounts its card element on the front end. The card
 * number never touches this server: the element is an iframe served by Stripe,
 * and all that comes back is a token, which is why there are no card inputs
 * here to render or validate.
 *
 * @package KDNAFormsStripe
 */

if ( ! class_exists( 'KDNAForms' ) ) {
	die();
}

/**
 * Class KDNA_Field_Stripe_Card
 *
 * @since 1.1.0
 */
class KDNA_Field_Stripe_Card extends KDNA_Field {

	/**
	 * @since 1.1.0
	 * @var string
	 */
	public $type = 'kdna_stripe_card';

	/**
	 * The title shown on the field and in the editor.
	 *
	 * @since 1.1.0
	 *
	 * @return string
	 */
	public function get_form_editor_field_title() {
		return esc_attr__( 'Stripe Card', 'kdnaforms-stripe' );
	}

	/**
	 * What the field does, shown in the editor's field picker.
	 *
	 * @since 1.1.0
	 *
	 * @return string
	 */
	public function get_form_editor_field_description() {
		return esc_attr__( 'Collects card details through Stripe. The card is entered in a frame served by Stripe, so the number never reaches this site.', 'kdnaforms-stripe' );
	}

	/**
	 * The icon shown against the field.
	 *
	 * @since 1.1.0
	 *
	 * @return string
	 */
	public function get_form_editor_field_icon() {
		return 'kform-icon--credit-card';
	}

	/**
	 * Puts the field in the Pricing group, beside the product fields.
	 *
	 * @since 1.1.0
	 *
	 * @return array
	 */
	public function get_form_editor_button() {
		return array(
			'group' => 'pricing_fields',
			'text'  => $this->get_form_editor_field_title(),
		);
	}

	/**
	 * The settings offered for this field.
	 *
	 * There is no required setting: a card field is required whenever the feed
	 * that uses it runs, and making it optional would only produce a payment
	 * form that can be submitted without a card.
	 *
	 * @since 1.1.0
	 *
	 * @return array
	 */
	public function get_form_editor_field_settings() {
		return array(
			'conditional_logic_field_setting',
			'force_ssl_field_setting',
			'error_message_setting',
			'label_setting',
			'label_placement_setting',
			'admin_label_setting',
			'rules_setting',
			'description_setting',
			'css_class_setting',
		);
	}

	/**
	 * @since 1.1.0
	 *
	 * @return bool
	 */
	public function is_conditional_logic_supported() {
		return true;
	}

	/**
	 * Renders the field.
	 *
	 * In the editor this is a static stand-in, because the real element needs
	 * Stripe.js and a publishable key and would be meaningless there.
	 *
	 * @since 1.1.0
	 *
	 * @param array        $form  The form.
	 * @param string|array $value The field value.
	 * @param null|array   $entry The entry.
	 *
	 * @return string
	 */
	public function get_field_input( $form, $value = '', $entry = null ) {

		$form_id = absint( rgar( $form, 'id' ) );

		if ( $this->is_form_editor() || $this->is_entry_detail() ) {
			return sprintf(
				'<div class="kinput_container kdna-stripe-placeholder">%s</div>',
				esc_html__( 'Stripe collects the card here when the form is live.', 'kdnaforms-stripe' )
			);
		}

		// If Stripe cannot start, say so where the card box would be. An empty
		// container is indistinguishable from a broken plugin.
		if ( ! class_exists( 'KDNA_Stripe' ) || ! KDNA_Stripe::get_instance()->is_configured() ) {
			return sprintf(
				'<div class="kinput_container kdna-stripe-unconfigured">%s</div>',
				esc_html__( 'Card payments are not available: Stripe has not been set up on this site.', 'kdnaforms-stripe' )
			);
		}

		// The field is the one thing that certainly knows Stripe is needed here.
		KDNA_Stripe::get_instance()->enqueue_frontend_assets( $form_id );

		return sprintf(
			'<div class="kinput_container kinput_container_%1$s">
				<div class="kdna-stripe-element" data-form-id="%2$d" data-field-id="%3$s"></div>
				<div class="kdna-stripe-errors" role="alert" aria-live="polite"></div>
				<input type="hidden" name="kdna_stripe_intent_id" value="" />
				<input type="hidden" name="kdna_stripe_payment_method" value="" />
			</div>',
			esc_attr( $this->type ),
			$form_id,
			esc_attr( $this->id )
		);
	}

	/**
	 * Nothing about the card is stored, so there is no value to save.
	 *
	 * @since 1.1.0
	 *
	 * @param string $value The value.
	 * @param array  $form  The form.
	 *
	 * @return string
	 */
	public function get_value_save_entry( $value, $form, $input_name, $lead_id, $lead ) {
		return '';
	}

	/**
	 * The field is never "empty" for validation purposes.
	 *
	 * Stripe validates the card in its own frame before the form is allowed to
	 * submit, and by the time this runs the field genuinely has no value — so
	 * treating it as empty would fail a required check that has already passed
	 * on the client.
	 *
	 * @since 1.1.0
	 *
	 * @param int $form_id The form id.
	 *
	 * @return bool
	 */
	public function is_value_submission_empty( $form_id ) {
		return false;
	}
}

KDNA_Fields::register( new KDNA_Field_Stripe_Card() );
