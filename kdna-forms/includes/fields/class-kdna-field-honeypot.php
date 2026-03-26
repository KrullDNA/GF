<?php

use KDNA_Forms\KDNA_Forms\Honeypot;

if ( ! class_exists( 'KDNAForms' ) ) {
	die();
}

/**
 * The honeypot field used to capture spam.
 *
 * @since 2.9.16
 */
class KDNA_Field_Honeypot extends KDNA_Field {

	/**
	 * The field type.
	 *
	 * @since 2.9.16
	 *
	 * @var string
	 */
	public $type = 'honeypot';

	/**
	 * Prevent the field type button appearing in the form editor.
	 *
	 * @since 2.9.16
	 *
	 * @return array
	 */
	public function get_form_editor_button() {
		return array();
	}

	/**
	 * Returns the field inner markup.
	 *
	 * @since 2.9.16
	 *
	 * @param array      $form  The form the field is to be output for.
	 * @param string     $value The field value.
	 * @param null|array $entry Null or the current entry.
	 *
	 * @return string
	 */
	public function get_field_input( $form, $value = '', $entry = null ) {
		/** @var Honeypot\KDNA_Honeypot_Handler $handler */
		$handler = KDNAForms::get_service_container()->get( Honeypot\KDNA_Honeypot_Service_Provider::KDNA_HONEYPOT_HANDLER );

		return sprintf(
			"<div class='kdnainput_container'><input name='%s' id='input_%d_%d' type='text' value='%s' autocomplete='new-password'/></div>",
			esc_attr( $handler->get_input_name( $form, $this->id ) ),
			absint( rgar( $form, 'id', $this->formId ) ),
			absint( $this->id ),
			esc_attr( $value )
		);
	}

}

KDNA_Fields::register( new KDNA_Field_Honeypot() );
