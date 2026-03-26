<?php

if ( ! class_exists( 'KDNAForms' ) ) {
	die();
}

class ChoiceDecorator {

	/**
	 * @var KDNA_Field
	 */
	protected $field;

	public function __construct( $field ) {
		$this->field = $field;
	}

	public function __call( $name, $args ) {
		return call_user_func_array( array( $this->field, $name ), $args );
	}

	/**
	 * Get the style classes for the image choice field.
	 *
	 * @since 2.9
	 *
	 * @param $form_id
	 * @param $field_id
	 *
	 * @return string
	 */
	public function get_field_classes( $form_id, $field ) {
		// Choice label visibility class
		$choice_label_visibility = KDNA_Field_Image_Choice::get_image_choice_label_visibility_setting( $field );
		$label_visibility_class  = "kdnainput_container_image_choice--label-{$choice_label_visibility}";

		// Choice input visibility class
		$choice_input_visibility = KDNA_Field_Image_Choice::get_image_choice_input_visibility_setting( $field );
		$input_visibility        = ( $choice_input_visibility === 'show' ) && ( $choice_label_visibility === 'show' ) ? 'show' : 'hide';
		$input_visibility_class  = "kdnainput_container_image_choice--input-{$input_visibility}";

		return $label_visibility_class . ' ' . $input_visibility_class;
	}

	/**
	 * Get the image markup for a choice field.
	 *
	 * @since 2.9
	 *
	 * @param $choice
	 * @param $choice_id
	 * @param $choice_number
	 * @param $form
	 *
	 * @return string
	 */
	public function get_image_markup( $choice, $choice_id, $choice_number, $form ) {
		$image_aria_describedby = 'kdnachoice_image_' . $choice_id;

		if ( ! empty( $choice['attachment_id'] ) ) {
			$image_alt  = get_post_meta( $choice['attachment_id'], '_wp_attachment_image_alt', true );
			$image_alt  = ! empty( $image_alt ) ? $image_alt : sprintf( '%s %d', __( 'Image for choice number', 'kdnaforms' ), $choice_number );
			$image_size = isset( $form['styles'] ) && rgar( $form['styles'], 'inputImageChoiceSize' ) ? $form['styles']['inputImageChoiceSize'] : 'md';

			$image = wp_get_attachment_image(
				$choice['attachment_id'],
				'kdnaform-image-choice-' . $image_size,
				false,
				array(
					'class'   => 'kdnafield-choice-image',
					'alt'     => $image_alt,
					'id'      => $image_aria_describedby,
					'loading' => 'false',
				)
			);
		} else {
			$image = sprintf(
				'<span class="kdnafield-choice-image-no-image" id="%s"><span>%s</span></span>',
				$image_aria_describedby,
				sprintf(
					'%s %d %s',
					__( 'Choice number', 'kdnaforms' ),
					$choice_number,
					__( 'does not have an image', 'kdnaforms' )
				)
			);
		}

		return sprintf( '<div class="kdnafield-choice-image-wrapper">%s</div>', $image );
	}
}

KDNACommon::glob_require_once( '/includes/fields/field-decorator-choice/class-kdna-field-decorator-choice-*.php' );
