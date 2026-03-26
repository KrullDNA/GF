<?php

if ( ! class_exists( 'KDNAForms' ) ) {
	die();
}

class KDNA_Field_Address extends KDNA_Field {

	public $type = 'address';

	function get_form_editor_field_settings() {
		return array(
			'conditional_logic_field_setting',
			'prepopulate_field_setting',
			'error_message_setting',
			'label_setting',
			'admin_label_setting',
			'label_placement_setting',
			'sub_label_placement_setting',
			'default_input_values_setting',
			'input_placeholders_setting',
			'address_setting',
			'rules_setting',
			'copy_values_option',
			'description_setting',
			'visibility_setting',
			'css_class_setting',
			'autocomplete_setting',
		);
	}

	public function is_conditional_logic_supported() {
		return true;
	}

	public function get_form_editor_field_title() {
		return esc_attr__( 'Address', 'kdnaforms' );
	}

	/**
	 * Returns the field's form editor description.
	 *
	 * @since 2.5
	 *
	 * @return string
	 */
	public function get_form_editor_field_description() {
		return esc_attr__( 'Allows users to enter a physical address.', 'kdnaforms' );
	}

	/**
	 * Returns the field's form editor icon.
	 *
	 * This could be an icon url or a gform-icon class.
	 *
	 * @since 2.5
	 *
	 * @return string
	 */
	public function get_form_editor_field_icon() {
		return 'gform-icon--place';
	}

	/**
	 * Defines the IDs of required inputs.
	 *
	 * @since 2.5
	 *
	 * @return string[]
	 */
	public function get_required_inputs_ids() {
		return array( '1', '3', '4', '5', '6' );
	}


	/**
	 * Returns the HTML tag for the field container.
	 *
	 * @since 2.5
	 *
	 * @param array $form The current Form object.
	 *
	 * @return string
	 */
	public function get_field_container_tag( $form ) {

		if ( KDNACommon::is_legacy_markup_enabled( $form ) ) {
			return parent::get_field_container_tag( $form );
		}

		return 'fieldset';

	}

	/**
	 * Validates the address field inputs.
	 *
	 * @since 1.9
	 * @since 2.6.5 Updated to use set_required_error().
	 *
	 * @param string|array $value The field value from get_value_submission().
	 * @param array        $form  The Form Object currently being processed.
	 *
	 * @return void
	 */
	public function validate( $value, $form ) {
		if ( ! $this->isRequired ) {
			return;
		}

		$copy_values_option_activated = $this->enableCopyValuesOption && rgpost( 'input_' . $this->id . '_copy_values_activated' );
		if ( $copy_values_option_activated ) {
			// Validation will occur in the source field.
			return;
		}

		$this->set_required_error( $value, true );
	}

	public function get_value_submission( $field_values, $get_from_post_global_var = true ) {

		$value                                         = parent::get_value_submission( $field_values, $get_from_post_global_var );
		$value[ $this->id . '_copy_values_activated' ] = (bool) rgpost( 'input_' . $this->id . '_copy_values_activated' );

		return $value;
	}

	public function get_field_input( $form, $value = '', $entry = null ) {

		$is_entry_detail = $this->is_entry_detail();
		$is_form_editor  = $this->is_form_editor();
		$is_admin        = $is_entry_detail || $is_form_editor;

		$form_id  = absint( $form['id'] );
		$id       = intval( $this->id );
		$field_id = $is_entry_detail || $is_form_editor || $form_id == 0 ? "input_$id" : 'input_' . $form_id . "_$id";
		$form_id  = ( $is_entry_detail || $is_form_editor ) && empty( $form_id ) ? rgget( 'id' ) : $form_id;

		$disabled_text      = $is_form_editor ? "disabled='disabled'" : '';
		$class_suffix       = $is_entry_detail ? '_admin' : '';


		$form_sub_label_placement = rgar( $form, 'subLabelPlacement' );
		$field_sub_label_placement = $this->subLabelPlacement;
		$is_sub_label_above       = $field_sub_label_placement == 'above' || ( empty( $field_sub_label_placement ) && $form_sub_label_placement == 'above' );
		$sub_label_class          = $field_sub_label_placement == 'hidden_label' ? "hidden_sub_label screen-reader-text" : '';

		$street_value  = '';
		$street2_value = '';
		$city_value    = '';
		$state_value   = '';
		$zip_value     = '';
		$country_value = '';

		if ( is_array( $value ) ) {
			$street_value  = esc_attr( rgget( $this->id . '.1', $value ) );
			$street2_value = esc_attr( rgget( $this->id . '.2', $value ) );
			$city_value    = esc_attr( rgget( $this->id . '.3', $value ) );
			$state_value   = esc_attr( rgget( $this->id . '.4', $value ) );
			$zip_value     = esc_attr( rgget( $this->id . '.5', $value ) );
			$country_value = esc_attr( rgget( $this->id . '.6', $value ) );
		}

		// Inputs.
		$address_street_field_input  = KDNAFormsModel::get_input( $this, $this->id . '.1' );
		$address_street2_field_input = KDNAFormsModel::get_input( $this, $this->id . '.2' );
		$address_city_field_input    = KDNAFormsModel::get_input( $this, $this->id . '.3' );
		$address_state_field_input   = KDNAFormsModel::get_input( $this, $this->id . '.4' );
		$address_zip_field_input     = KDNAFormsModel::get_input( $this, $this->id . '.5' );
		$address_country_field_input = KDNAFormsModel::get_input( $this, $this->id . '.6' );

		// Placeholders.
		$street_placeholder_attribute  = KDNACommon::get_input_placeholder_attribute( $address_street_field_input );
		$street2_placeholder_attribute = KDNACommon::get_input_placeholder_attribute( $address_street2_field_input );
		$city_placeholder_attribute    = KDNACommon::get_input_placeholder_attribute( $address_city_field_input );
		$zip_placeholder_attribute     = KDNACommon::get_input_placeholder_attribute( $address_zip_field_input );

		$address_types = $this->get_address_types( $form_id );
		$addr_type     = empty( $this->addressType ) ? $this->get_default_address_type( $form_id ) : $this->addressType;
		$address_type  = rgar( $address_types, $addr_type );

		$state_label  = empty( $address_type['state_label'] ) ? esc_html__( 'State', 'kdnaforms' ) : $address_type['state_label'];
		$zip_label    = empty( $address_type['zip_label'] ) ? esc_html__( 'Zip Code', 'kdnaforms' ) : $address_type['zip_label'];
		$hide_country = ! empty( $address_type['country'] ) || $this->hideCountry || rgar( $address_country_field_input, 'isHidden' );

		if ( empty( $country_value ) ) {
			$country_value = $this->defaultCountry;
		}

		if ( empty( $state_value ) ) {
			$state_value = $this->defaultState;
		}

		$country_placeholder = KDNACommon::get_input_placeholder_value( $address_country_field_input );
		$country_list        = $this->get_country_dropdown( $country_value, $country_placeholder );

		// Changing css classes based on field format to ensure proper display.
		$address_display_format = apply_filters( 'kdnaform_address_display_format', 'default', $this );
		$city_location          = $address_display_format == 'zip_before_city' ? 'right' : 'left';
		$zip_location           = $address_display_format != 'zip_before_city' && ( $this->hideState || rgar( $address_state_field_input, 'isHidden' ) ) ? 'right' : 'left'; // support for $this->hideState legacy property
		$state_location         = $address_display_format == 'zip_before_city' ? 'left' : 'right';
		$country_location       = $this->hideState || rgar( $address_state_field_input, 'isHidden' ) ? 'left' : 'right'; // support for $this->hideState legacy property

		// Labels.
		$address_street_sub_label  = rgar( $address_street_field_input, 'customLabel' ) != '' ? $address_street_field_input['customLabel'] : esc_html__( 'Street Address', 'kdnaforms' );
		$address_street_sub_label  = gf_apply_filters( array( 'kdnaform_address_street', $form_id, $this->id ), $address_street_sub_label, $form_id );
		$address_street2_sub_label = rgar( $address_street2_field_input, 'customLabel' ) != '' ? $address_street2_field_input['customLabel'] : esc_html__( 'Address Line 2', 'kdnaforms' );
		$address_street2_sub_label = gf_apply_filters( array( 'kdnaform_address_street2', $form_id, $this->id ), $address_street2_sub_label, $form_id );
		$address_zip_sub_label     = rgar( $address_zip_field_input, 'customLabel' ) != '' ? $address_zip_field_input['customLabel'] : $zip_label;
		$address_zip_sub_label     = gf_apply_filters( array( 'kdnaform_address_zip', $form_id, $this->id ), $address_zip_sub_label, $form_id );
		$address_city_sub_label    = rgar( $address_city_field_input, 'customLabel' ) != '' ? $address_city_field_input['customLabel'] : esc_html__( 'City', 'kdnaforms' );
		$address_city_sub_label    = gf_apply_filters( array( 'kdnaform_address_city', $form_id, $this->id ), $address_city_sub_label, $form_id );
		$address_state_sub_label   = rgar( $address_state_field_input, 'customLabel' ) != '' ? $address_state_field_input['customLabel'] : $state_label;
		$address_state_sub_label   = gf_apply_filters( array( 'kdnaform_address_state', $form_id, $this->id ), $address_state_sub_label, $form_id );
		$address_country_sub_label = rgar( $address_country_field_input, 'customLabel' ) != '' ? $address_country_field_input['customLabel'] : esc_html__( 'Country', 'kdnaforms' );
		$address_country_sub_label = gf_apply_filters( array( 'kdnaform_address_country', $form_id, $this->id ), $address_country_sub_label, $form_id );

		// Autocomplete attributes.
		$address_street_autocomplete  = $this->enableAutocomplete ? $this->get_input_autocomplete_attribute( $address_street_field_input ) : '';
		$address_street2_autocomplete = $this->enableAutocomplete ? $this->get_input_autocomplete_attribute( $address_street2_field_input ) : '';
		$address_city_autocomplete    = $this->enableAutocomplete ? $this->get_input_autocomplete_attribute( $address_city_field_input ) : '';
		$address_zip_autocomplete     = $this->enableAutocomplete ? $this->get_input_autocomplete_attribute( $address_zip_field_input ) : '';
		$address_country_autocomplete = $this->enableAutocomplete ? $this->get_input_autocomplete_attribute( $address_country_field_input ) : '';

		// Aria attributes.
		$street_aria_attributes  = $this->get_aria_attributes( $value, '1' );
		$street2_aria_attributes = $this->get_aria_attributes( $value, '2' );
		$city_aria_attributes    = $this->get_aria_attributes( $value, '3' );
		$zip_aria_attributes     = $this->get_aria_attributes( $value, '5' );
		$country_aria_attributes = $this->get_aria_attributes( $value, '6' );

		// Address field.
		$street_address = '';
		$tabindex       = $this->get_tabindex();
		$style          = ( $is_admin && rgar( $address_street_field_input, 'isHidden' ) ) ? "style='display:none;'" : '';

		if ( $is_admin || ! rgar( $address_street_field_input, 'isHidden' ) ) {
			if ( $is_sub_label_above ) {
				$street_address = " <span class='ginput_full{$class_suffix} address_line_1 ginput_address_line_1 gform-grid-col' id='{$field_id}_1_container' {$style}>
                                        <label for='{$field_id}_1' id='{$field_id}_1_label' class='gform-field-label gform-field-label--type-sub {$sub_label_class}'>{$address_street_sub_label}</label>
                                        <input type='text' name='input_{$id}.1' id='{$field_id}_1' value='{$street_value}' {$tabindex} {$disabled_text} {$street_placeholder_attribute} {$street_aria_attributes} {$address_street_autocomplete} {$this->maybe_add_aria_describedby( $address_street_field_input, $field_id, $this['formId'] )}/>
                                   </span>";
			} else {
				$street_address = " <span class='ginput_full{$class_suffix} address_line_1 ginput_address_line_1 gform-grid-col' id='{$field_id}_1_container' {$style}>
                                        <input type='text' name='input_{$id}.1' id='{$field_id}_1' value='{$street_value}' {$tabindex} {$disabled_text} {$street_placeholder_attribute} {$street_aria_attributes} {$address_street_autocomplete} {$this->maybe_add_aria_describedby( $address_street_field_input, $field_id, $this['formId'] )}/>
                                        <label for='{$field_id}_1' id='{$field_id}_1_label' class='gform-field-label gform-field-label--type-sub {$sub_label_class}'>{$address_street_sub_label}</label>
                                    </span>";
			}
		}

		// Address line 2 field.
		$street_address2 = '';
		$style           = ( $is_admin && ( $this->hideAddress2 || rgar( $address_street2_field_input, 'isHidden' ) ) ) ? "style='display:none;'" : ''; // support for $this->hideAddress2 legacy property
		if ( $is_admin || ( ! $this->hideAddress2 && ! rgar( $address_street2_field_input, 'isHidden' ) ) ) {
			$tabindex = $this->get_tabindex();
			if ( $is_sub_label_above ) {
				$street_address2 = "<span class='ginput_full{$class_suffix} address_line_2 ginput_address_line_2 gform-grid-col' id='{$field_id}_2_container' {$style}>
                                        <label for='{$field_id}_2' id='{$field_id}_2_label' class='gform-field-label gform-field-label--type-sub {$sub_label_class}'>{$address_street2_sub_label}</label>
                                        <input type='text' name='input_{$id}.2' id='{$field_id}_2' value='{$street2_value}' {$tabindex} {$disabled_text} {$street2_placeholder_attribute} {$address_street2_autocomplete} {$street2_aria_attributes} {$this->maybe_add_aria_describedby( $address_street2_field_input, $field_id, $this['formId'] )}/>
                                    </span>";
			} else {
				$street_address2 = "<span class='ginput_full{$class_suffix} address_line_2 ginput_address_line_2 gform-grid-col' id='{$field_id}_2_container' {$style}>
                                        <input type='text' name='input_{$id}.2' id='{$field_id}_2' value='{$street2_value}' {$tabindex} {$disabled_text} {$street2_placeholder_attribute} {$address_street2_autocomplete} {$street2_aria_attributes} {$this->maybe_add_aria_describedby( $address_street2_field_input, $field_id, $this['formId'] )}/>
                                        <label for='{$field_id}_2' id='{$field_id}_2_label' class='gform-field-label gform-field-label--type-sub {$sub_label_class}'>{$address_street2_sub_label}</label>
                                    </span>";
			}
		}

		if ( $address_display_format == 'zip_before_city' ) {
			// Zip field.
			$zip      = '';
			$tabindex = $this->get_tabindex();
			$style    = ( $is_admin && rgar( $address_zip_field_input, 'isHidden' ) ) ? "style='display:none;'" : '';
			if ( $is_admin || ! rgar( $address_zip_field_input, 'isHidden' ) ) {
				if ( $is_sub_label_above ) {
					$zip = "<span class='ginput_{$zip_location}{$class_suffix} address_zip ginput_address_zip gform-grid-col' id='{$field_id}_5_container' {$style}>
                                    <label for='{$field_id}_5' id='{$field_id}_5_label' class='gform-field-label gform-field-label--type-sub {$sub_label_class}'>{$address_zip_sub_label}</label>
                                    <input type='text' name='input_{$id}.5' id='{$field_id}_5' value='{$zip_value}' {$tabindex} {$disabled_text} {$zip_placeholder_attribute} {$zip_aria_attributes} {$address_zip_autocomplete} {$this->maybe_add_aria_describedby( $address_zip_field_input, $field_id, $this['formId'] )}/>
                                </span>";
				} else {
					$zip = "<span class='ginput_{$zip_location}{$class_suffix} address_zip ginput_address_zip gform-grid-col' id='{$field_id}_5_container' {$style}>
                                    <input type='text' name='input_{$id}.5' id='{$field_id}_5' value='{$zip_value}' {$tabindex} {$disabled_text} {$zip_placeholder_attribute} {$zip_aria_attributes} {$address_zip_autocomplete} {$this->maybe_add_aria_describedby( $address_zip_field_input, $field_id, $this['formId'] )}/>
                                    <label for='{$field_id}_5' id='{$field_id}_5_label' class='gform-field-label gform-field-label--type-sub {$sub_label_class}'>{$address_zip_sub_label}</label>
                                </span>";
				}
			}

			// City field.
			$city     = '';
			$tabindex = $this->get_tabindex();
			$style    = ( $is_admin && rgar( $address_city_field_input, 'isHidden' ) ) ? "style='display:none;'" : '';
			if ( $is_admin || ! rgar( $address_city_field_input, 'isHidden' ) ) {
				if ( $is_sub_label_above ) {
					$city = "<span class='ginput_{$city_location}{$class_suffix} address_city ginput_address_city gform-grid-col' id='{$field_id}_3_container' {$style}>
                                    <label for='{$field_id}_3' id='{$field_id}_3_label' class='gform-field-label gform-field-label--type-sub {$sub_label_class}'>{$address_city_sub_label}</label>
                                    <input type='text' name='input_{$id}.3' id='{$field_id}_3' value='{$city_value}' {$tabindex} {$disabled_text} {$city_placeholder_attribute} {$city_aria_attributes} {$address_city_autocomplete} {$this->maybe_add_aria_describedby( $address_city_field_input, $field_id, $this['formId'] )}/>
                                 </span>";
				} else {
					$city = "<span class='ginput_{$city_location}{$class_suffix} address_city ginput_address_city gform-grid-col' id='{$field_id}_3_container' {$style}>
                                    <input type='text' name='input_{$id}.3' id='{$field_id}_3' value='{$city_value}' {$tabindex} {$disabled_text} {$city_placeholder_attribute} {$city_aria_attributes} {$address_city_autocomplete} {$this->maybe_add_aria_describedby( $address_city_field_input, $field_id, $this['formId'] )}/>
                                    <label for='{$field_id}_3' id='{$field_id}_3_label' class='gform-field-label gform-field-label--type-sub {$sub_label_class}'>{$address_city_sub_label}</label>
                                 </span>";
				}
			}

			// State field.
			$style = ( $is_admin && ( $this->hideState || rgar( $address_state_field_input, 'isHidden' ) ) ) ? "style='display:none;'" : ''; // support for $this->hideState legacy property
			if ( $is_admin || ( ! $this->hideState && ! rgar( $address_state_field_input, 'isHidden' ) ) ) {
				$aria_attributes = $this->get_aria_attributes( $value, '4' );
				$state_field = $this->get_state_field( $id, $field_id, $state_value, $disabled_text, $form_id, $aria_attributes, $address_state_field_input );
				if ( $is_sub_label_above ) {
					$state = "<span class='ginput_{$state_location}{$class_suffix} address_state ginput_address_state gform-grid-col' id='{$field_id}_4_container' {$style}>
                                           <label for='{$field_id}_4' id='{$field_id}_4_label' class='gform-field-label gform-field-label--type-sub {$sub_label_class}'>{$address_state_sub_label}</label>
                                           $state_field
                                      </span>";
				} else {
					$state = "<span class='ginput_{$state_location}{$class_suffix} address_state ginput_address_state gform-grid-col' id='{$field_id}_4_container' {$style}>
                                           $state_field
                                           <label for='{$field_id}_4' id='{$field_id}_4_label' class='gform-field-label gform-field-label--type-sub {$sub_label_class}'>{$address_state_sub_label} </label>
                                      </span>";
				}
			} else {
				$state = sprintf( "<input type='hidden' class='gform_hidden' name='input_%d.4' id='%s_4' value='%s'/>", $id, $field_id, $state_value );
			}
		} else {

			// City field.
			$city     = '';
			$tabindex = $this->get_tabindex();
			$style    = ( $is_admin && rgar( $address_city_field_input, 'isHidden' ) ) ? "style='display:none;'" : '';
			if ( $is_admin || ! rgar( $address_city_field_input, 'isHidden' ) ) {
				if ( $is_sub_label_above ) {
					$city = "<span class='ginput_{$city_location}{$class_suffix} address_city ginput_address_city gform-grid-col' id='{$field_id}_3_container' {$style}>
                                    <label for='{$field_id}_3' id='{$field_id}_3_label' class='gform-field-label gform-field-label--type-sub {$sub_label_class}'>{$address_city_sub_label}</label>
                                    <input type='text' name='input_{$id}.3' id='{$field_id}_3' value='{$city_value}' {$tabindex} {$disabled_text} {$city_placeholder_attribute} {$city_aria_attributes} {$address_city_autocomplete} {$this->maybe_add_aria_describedby( $address_city_field_input, $field_id, $this['formId'] )}/>
                                 </span>";
				} else {
					$city = "<span class='ginput_{$city_location}{$class_suffix} address_city ginput_address_city gform-grid-col' id='{$field_id}_3_container' {$style}>
                                    <input type='text' name='input_{$id}.3' id='{$field_id}_3' value='{$city_value}' {$tabindex} {$disabled_text} {$city_placeholder_attribute} {$city_aria_attributes} {$address_city_autocomplete} {$this->maybe_add_aria_describedby( $address_city_field_input, $field_id, $this['formId'] )}/>
                                    <label for='{$field_id}_3' id='{$field_id}_3_label' class='gform-field-label gform-field-label--type-sub {$sub_label_class}'>{$address_city_sub_label}</label>
                                 </span>";
				}
			}

			// State field.
			$style = ( $is_admin && ( $this->hideState || rgar( $address_state_field_input, 'isHidden' ) ) ) ? "style='display:none;'" : ''; // support for $this->hideState legacy property
			if ( $is_admin || ( ! $this->hideState && ! rgar( $address_state_field_input, 'isHidden' ) ) ) {
				$aria_attributes = $this->get_aria_attributes( $value, '4' );
				$state_field = $this->get_state_field( $id, $field_id, $state_value, $disabled_text, $form_id, $aria_attributes, $address_state_field_input );
				if ( $is_sub_label_above ) {
					$state = "<span class='ginput_{$state_location}{$class_suffix} address_state ginput_address_state gform-grid-col' id='{$field_id}_4_container' {$style}>
                                        <label for='{$field_id}_4' id='{$field_id}_4_label' class='gform-field-label gform-field-label--type-sub {$sub_label_class}'>$address_state_sub_label</label>
                                        $state_field
                                      </span>";
				} else {
					$state = "<span class='ginput_{$state_location}{$class_suffix} address_state ginput_address_state gform-grid-col' id='{$field_id}_4_container' {$style}>
                                        $state_field
                                        <label for='{$field_id}_4' id='{$field_id}_4_label' class='gform-field-label gform-field-label--type-sub {$sub_label_class}'>$address_state_sub_label</label>
                                      </span>";
				}
			} else {
				$state = sprintf( "<input type='hidden' class='gform_hidden' name='input_%d.4' id='%s_4' value='%s'/>", $id, $field_id, $state_value );
			}

			// Zip field.
			$zip      = '';
			$tabindex = KDNACommon::get_tabindex();
			$style    = ( $is_admin && rgar( $address_zip_field_input, 'isHidden' ) ) ? "style='display:none;'" : '';
			if ( $is_admin || ! rgar( $address_zip_field_input, 'isHidden' ) ) {
				if ( $is_sub_label_above ) {
					$zip = "<span class='ginput_{$zip_location}{$class_suffix} address_zip ginput_address_zip gform-grid-col' id='{$field_id}_5_container' {$style}>
                                    <label for='{$field_id}_5' id='{$field_id}_5_label' class='gform-field-label gform-field-label--type-sub {$sub_label_class}'>{$address_zip_sub_label}</label>
                                    <input type='text' name='input_{$id}.5' id='{$field_id}_5' value='{$zip_value}' {$tabindex} {$disabled_text} {$zip_placeholder_attribute} {$zip_aria_attributes} {$address_zip_autocomplete} {$this->maybe_add_aria_describedby( $address_zip_field_input, $field_id, $this['formId'] )}/>
                                </span>";
				} else {
					$zip = "<span class='ginput_{$zip_location}{$class_suffix} address_zip ginput_address_zip gform-grid-col' id='{$field_id}_5_container' {$style}>
                                    <input type='text' name='input_{$id}.5' id='{$field_id}_5' value='{$zip_value}' {$tabindex} {$disabled_text} {$zip_placeholder_attribute} {$zip_aria_attributes} {$address_zip_autocomplete} {$this->maybe_add_aria_describedby( $address_zip_field_input, $field_id, $this['formId'] )}/>
                                    <label for='{$field_id}_5' id='{$field_id}_5_label' class='gform-field-label gform-field-label--type-sub {$sub_label_class}'>{$address_zip_sub_label}</label>
                                </span>";
				}
			}
		}

		if ( $is_admin || ! $hide_country ) {
			$style    = $hide_country ? "style='display:none;'" : '';
			$tabindex = $this->get_tabindex();
			if ( $is_sub_label_above ) {
				$country = "<span class='ginput_{$country_location}{$class_suffix} address_country ginput_address_country gform-grid-col' id='{$field_id}_6_container' {$style}>
                                        <label for='{$field_id}_6' id='{$field_id}_6_label' class='gform-field-label gform-field-label--type-sub {$sub_label_class}'>{$address_country_sub_label}</label>
                                        <select name='input_{$id}.6' id='{$field_id}_6' {$tabindex} {$disabled_text} {$country_aria_attributes} {$address_country_autocomplete} {$this->maybe_add_aria_describedby( $address_country_field_input, $field_id, $this['formId'] )}>{$country_list} </select>
                                    </span>";
			} else {
				$country = "<span class='ginput_{$country_location}{$class_suffix} address_country ginput_address_country gform-grid-col' id='{$field_id}_6_container' {$style}>
                                        <select name='input_{$id}.6' id='{$field_id}_6' {$tabindex} {$disabled_text} {$country_aria_attributes} {$address_country_autocomplete} {$this->maybe_add_aria_describedby( $address_country_field_input, $field_id, $this['formId'] )}>{$country_list}</select>
                                        <label for='{$field_id}_6' id='{$field_id}_6_label' class='gform-field-label gform-field-label--type-sub {$sub_label_class}'>{$address_country_sub_label}</label>
                                    </span>";
			}
		} else {
			$country = sprintf( "<input type='hidden' class='gform_hidden' name='input_%d.6' id='%s_6' value='%s' {$this->maybe_add_aria_describedby( $address_country_field_input, $field_id, $this['formId'] )}/>", $id, $field_id, $country_value );
		}

		$inputs = $address_display_format == 'zip_before_city' ? $street_address . $street_address2 . $zip . $city . $state . $country : $street_address . $street_address2 . $city . $state . $zip . $country;

		$copy_values_option = '';
		$input_style        = '';
		if ( ( $this->enableCopyValuesOption || $is_form_editor ) && ! $is_entry_detail ) {
			$copy_values_label      = esc_html( $this->copyValuesOptionLabel );
			$copy_values_style      = $is_form_editor && ! $this->enableCopyValuesOption ? "style='display:none;'" : '';
			$copy_values_is_checked = isset( $value[$this->id . '_copy_values_activated'] ) ? $value[$this->id . '_copy_values_activated'] == true : $this->copyValuesOptionDefault == true;
			$copy_values_checked    = checked( true, $copy_values_is_checked, false );
			$copy_values_option     = "<div id='{$field_id}_copy_values_option_container' class='copy_values_option_container' {$copy_values_style}>
                                        <input type='checkbox' id='{$field_id}_copy_values_activated' class='copy_values_activated' value='1' data-source_field_id='" . absint( $this->copyValuesOptionField ) . "' name='input_{$id}_copy_values_activated' {$disabled_text} {$copy_values_checked}/>
                                        <label for='{$field_id}_copy_values_activated' id='{$field_id}_copy_values_option_label' class='copy_values_option_label inline gform-field-label gform-field-label--type-inline'>{$copy_values_label}</label>
                                    </div>";
			if ( $copy_values_is_checked ) {
				$input_style = "style='display:none;'";
			}
		}

		$css_class = $this->get_css_class();

		return "    {$copy_values_option}
                    <div class='ginput_complex{$class_suffix} ginput_container {$css_class} gform-grid-row' id='$field_id' {$input_style}>
                        {$inputs}
                    <div class='gf_clear gf_clear_complex'></div>
                </div>";
	}

	public function get_css_class() {

		$address_street_field_input  = KDNAFormsModel::get_input( $this, $this->id . '.1' );
		$address_street2_field_input = KDNAFormsModel::get_input( $this, $this->id . '.2' );
		$address_city_field_input    = KDNAFormsModel::get_input( $this, $this->id . '.3' );
		$address_state_field_input   = KDNAFormsModel::get_input( $this, $this->id . '.4' );
		$address_zip_field_input     = KDNAFormsModel::get_input( $this, $this->id . '.5' );
		$address_country_field_input = KDNAFormsModel::get_input( $this, $this->id . '.6' );

		$css_class = '';
		if ( ! rgar( $address_street_field_input, 'isHidden' ) ) {
			$css_class .= 'has_street ';
		}
		if ( ! rgar( $address_street2_field_input, 'isHidden' ) ) {
			$css_class .= 'has_street2 ';
		}
		if ( ! rgar( $address_city_field_input, 'isHidden' ) ) {
			$css_class .= 'has_city ';
		}
		if ( ! rgar( $address_state_field_input, 'isHidden' ) ) {
			$css_class .= 'has_state ';
		}
		if ( ! rgar( $address_zip_field_input, 'isHidden' ) ) {
			$css_class .= 'has_zip ';
		}
		if ( ! rgar( $address_country_field_input, 'isHidden' ) ) {
			$css_class .= 'has_country ';
		}

		$css_class .= 'ginput_container_address';

		return trim( $css_class );
	}

	public function get_address_types( $form_id ) {

		$addressTypes = array(
			'international' => array( 'label'       => esc_html__( 'International', 'kdnaforms' ),
			                          'zip_label'   => gf_apply_filters( array( 'kdnaform_address_zip', $form_id ), esc_html__( 'ZIP / Postal Code', 'kdnaforms' ), $form_id ),
			                          'state_label' => gf_apply_filters( array( 'kdnaform_address_state', $form_id ), esc_html__( 'State / Province / Region', 'kdnaforms' ), $form_id )
			),
			'us'            => array(
				'label'       => esc_html__( 'United States', 'kdnaforms' ),
				'zip_label'   => gf_apply_filters( array( 'kdnaform_address_zip', $form_id ), esc_html__( 'ZIP Code', 'kdnaforms' ), $form_id ),
				'state_label' => gf_apply_filters( array( 'kdnaform_address_state', $form_id ), esc_html__( 'State', 'kdnaforms' ), $form_id ),
				'country'     => 'United States',
				'states'      => array_merge( array( '' ), $this->get_us_states() )
			),
			'canadian'      => array(
				'label'       => esc_html__( 'Canadian', 'kdnaforms' ),
				'zip_label'   => gf_apply_filters( array( 'kdnaform_address_zip', $form_id ), esc_html__( 'Postal Code', 'kdnaforms' ), $form_id ),
				'state_label' => gf_apply_filters( array( 'kdnaform_address_state', $form_id ), esc_html__( 'Province', 'kdnaforms' ), $form_id ),
				'country'     => 'Canada',
				'states'      => array_merge( array( '' ), $this->get_canadian_provinces() )
			)
		);

		/**
		 * Filters the address types available.
		 *
		 * @since Unknown
		 *
		 * @param array $addressTypes Contains the details for existing address types.
		 * @param int   $form_id      The form ID.
		 */
		return gf_apply_filters( array( 'kdnaform_address_types', $form_id ), $addressTypes, $form_id );
	}

	/**
	 * Retrieve the default address type for this field.
	 *
	 * @param int $form_id The current form ID.
	 *
	 * @return string
	 */
	public function get_default_address_type( $form_id ) {
		$default_address_type = 'international';

		/**
		 * Allow the default address type to be overridden.
		 *
		 * @param string $default_address_type The default address type of international.
		 */
		$default_address_type = apply_filters( 'kdnaform_default_address_type', $default_address_type, $form_id );

		return apply_filters( 'kdnaform_default_address_type_' . $form_id, $default_address_type, $form_id );
	}

	/**
	 * Generates state field markup.
	 *
	 * @since unknown
	 * @since 2.5                       added new params $$aria_attributes.
	 *
	 * @param integer $id               Input id.
	 * @param integer $field_id         Field id.
	 * @param string  $state_value      State value.
	 * @param string  $disabled_text    Disabled attribute.
	 * @param integer $form_id          Current form id being processed.
	 * @param string  $aria_attributes  Aria attributes values.
	 *
	 * @return string
	 */
	public function get_state_field( $id, $field_id, $state_value, $disabled_text, $form_id, $aria_attributes = '', $address_state_field_input = '' ) {

		$is_entry_detail = $this->is_entry_detail();
		$is_form_editor  = $this->is_form_editor();
		$is_admin        = $is_entry_detail || $is_form_editor;

		$state_dropdown_class = $state_text_class = $state_style = $text_style = $state_field_id = '';

		if ( empty( $state_value ) ) {
			$state_value = $this->defaultState;

			// For backwards compatibility (Canadian address type used to store the default state into the defaultProvince property).
			if ( $this->addressType == 'canadian' && ! empty( $this->defaultProvince ) ) {
				$state_value = $this->defaultProvince;
			}
		}

		$address_type        = empty( $this->addressType ) ? $this->get_default_address_type( $form_id ) : $this->addressType;
		$address_types       = $this->get_address_types( $form_id );
		$has_state_drop_down = isset( $address_types[ $address_type ]['states'] ) && is_array( $address_types[ $address_type ]['states'] );

		if ( $is_admin && rgget('view') != 'entry' ) {
			$state_dropdown_class = "class='state_dropdown'";
			$state_text_class     = "class='state_text'";
			$state_style          = ! $has_state_drop_down ? "style='display:none;'" : '';
			$text_style           = $has_state_drop_down ? "style='display:none;'" : '';
			$state_field_id       = '';
		} else {
			// ID only displayed on front end.
			$state_field_id = "id='" . $field_id . "_4'";
		}

		$tabindex           = $this->get_tabindex();
		$state_input        = KDNAFormsModel::get_input( $this, $this->id . '.4' );
		$state_placeholder  = KDNACommon::get_input_placeholder_value( $state_input );
		$state_autocomplete = $this->enableAutocomplete ? $this->get_input_autocomplete_attribute( $state_input ) : '';
		$states             = empty( $address_types[ $address_type ]['states'] ) ? array() : $address_types[ $address_type ]['states'];
		$state_dropdown     = sprintf( "<select name='input_%d.4' %s {$tabindex} %s {$state_dropdown_class} {$state_style} {$aria_attributes} {$state_autocomplete} {$this->maybe_add_aria_describedby( $address_state_field_input, $field_id, $this['formId'] )}>%s</select>", $id, $state_field_id, $disabled_text, $this->get_state_dropdown( $states, $state_value, $state_placeholder ) );

		$tabindex                    = $this->get_tabindex();
		$state_placeholder_attribute = KDNACommon::get_input_placeholder_attribute( $state_input );
		$state_text                  = sprintf( "<input type='text' name='input_%d.4' %s value='%s' {$tabindex} %s {$state_text_class} {$text_style} {$state_placeholder_attribute} {$aria_attributes} {$state_autocomplete} {$this->maybe_add_aria_describedby( $address_state_field_input, $field_id, $this['formId'] )}/>", $id, $state_field_id, $state_value, $disabled_text );

		if ( $is_admin && rgget('view') != 'entry' ) {
			return $state_dropdown . $state_text;
		} elseif ( $has_state_drop_down ) {
			return $state_dropdown;
		} else {
			return $state_text;
		}
	}

	/**
	 * Returns a list of countries.
	 *
	 * @since Unknown
	 * @since 2.4     Updated to use ISO 3166-1 list of countries.
	 * @since 2.4.20  Updated to use KDNA_Field_Address::get_default_countries() and to sort the countries.
	 *
	 * @return array
	 */
	public function get_countries() {

		$countries = array_values( $this->get_default_countries() );
		sort( $countries );

		/**
		 * A list of countries displayed in the Address field country drop down.
		 *
		 * @since Unknown
		 *
		 * @param array $countries ISO 3166-1 list of countries.
		 */
		return apply_filters( 'kdnaform_countries', $countries );

	}

	/**
	 * Returns the default array of countries using the ISO 3166-1 alpha-2 code as the key to the country name.
	 *
	 * @since 2.4.20
	 *
	 * @return array
	 */
	public function get_default_countries() {
		return array(
			'AF' => __( 'Afghanistan', 'kdnaforms' ),
			'AX' => __( 'Åland Islands', 'kdnaforms' ),
			'AL' => __( 'Albania', 'kdnaforms' ),
			'DZ' => __( 'Algeria', 'kdnaforms' ),
			'AS' => __( 'American Samoa', 'kdnaforms' ),
			'AD' => __( 'Andorra', 'kdnaforms' ),
			'AO' => __( 'Angola', 'kdnaforms' ),
			'AI' => __( 'Anguilla', 'kdnaforms' ),
			'AQ' => __( 'Antarctica', 'kdnaforms' ),
			'AG' => __( 'Antigua and Barbuda', 'kdnaforms' ),
			'AR' => __( 'Argentina', 'kdnaforms' ),
			'AM' => __( 'Armenia', 'kdnaforms' ),
			'AW' => __( 'Aruba', 'kdnaforms' ),
			'AU' => __( 'Australia', 'kdnaforms' ),
			'AT' => __( 'Austria', 'kdnaforms' ),
			'AZ' => __( 'Azerbaijan', 'kdnaforms' ),
			'BS' => __( 'Bahamas', 'kdnaforms' ),
			'BH' => __( 'Bahrain', 'kdnaforms' ),
			'BD' => __( 'Bangladesh', 'kdnaforms' ),
			'BB' => __( 'Barbados', 'kdnaforms' ),
			'BY' => __( 'Belarus', 'kdnaforms' ),
			'BE' => __( 'Belgium', 'kdnaforms' ),
			'BZ' => __( 'Belize', 'kdnaforms' ),
			'BJ' => __( 'Benin', 'kdnaforms' ),
			'BM' => __( 'Bermuda', 'kdnaforms' ),
			'BT' => __( 'Bhutan', 'kdnaforms' ),
			'BO' => __( 'Bolivia', 'kdnaforms' ),
			'BQ' => __( 'Bonaire, Sint Eustatius and Saba', 'kdnaforms' ),
			'BA' => __( 'Bosnia and Herzegovina', 'kdnaforms' ),
			'BW' => __( 'Botswana', 'kdnaforms' ),
			'BV' => __( 'Bouvet Island', 'kdnaforms' ),
			'BR' => __( 'Brazil', 'kdnaforms' ),
			'IO' => __( 'British Indian Ocean Territory', 'kdnaforms' ),
			'BN' => __( 'Brunei Darussalam', 'kdnaforms' ),
			'BG' => __( 'Bulgaria', 'kdnaforms' ),
			'BF' => __( 'Burkina Faso', 'kdnaforms' ),
			'BI' => __( 'Burundi', 'kdnaforms' ),
			'CV' => __( 'Cabo Verde', 'kdnaforms' ),
			'KH' => __( 'Cambodia', 'kdnaforms' ),
			'CM' => __( 'Cameroon', 'kdnaforms' ),
			'CA' => __( 'Canada', 'kdnaforms' ),
			'KY' => __( 'Cayman Islands', 'kdnaforms' ),
			'CF' => __( 'Central African Republic', 'kdnaforms' ),
			'TD' => __( 'Chad', 'kdnaforms' ),
			'CL' => __( 'Chile', 'kdnaforms' ),
			'CN' => __( 'China', 'kdnaforms' ),
			'CX' => __( 'Christmas Island', 'kdnaforms' ),
			'CC' => __( 'Cocos Islands', 'kdnaforms' ),
			'CO' => __( 'Colombia', 'kdnaforms' ),
			'KM' => __( 'Comoros', 'kdnaforms' ),
			'CD' => __( 'Congo, Democratic Republic of the', 'kdnaforms' ),
			'CG' => __( 'Congo', 'kdnaforms' ),
			'CK' => __( 'Cook Islands', 'kdnaforms' ),
			'CR' => __( 'Costa Rica', 'kdnaforms' ),
			'CI' => __( "Côte d'Ivoire", 'kdnaforms' ),
			'HR' => __( 'Croatia', 'kdnaforms' ),
			'CU' => __( 'Cuba', 'kdnaforms' ),
			'CW' => __( 'Curaçao', 'kdnaforms' ),
			'CY' => __( 'Cyprus', 'kdnaforms' ),
			'CZ' => __( 'Czechia', 'kdnaforms' ),
			'DK' => __( 'Denmark', 'kdnaforms' ),
			'DJ' => __( 'Djibouti', 'kdnaforms' ),
			'DM' => __( 'Dominica', 'kdnaforms' ),
			'DO' => __( 'Dominican Republic', 'kdnaforms' ),
			'EC' => __( 'Ecuador', 'kdnaforms' ),
			'EG' => __( 'Egypt', 'kdnaforms' ),
			'SV' => __( 'El Salvador', 'kdnaforms' ),
			'GQ' => __( 'Equatorial Guinea', 'kdnaforms' ),
			'ER' => __( 'Eritrea', 'kdnaforms' ),
			'EE' => __( 'Estonia', 'kdnaforms' ),
			'SZ' => __( 'Eswatini', 'kdnaforms' ),
			'ET' => __( 'Ethiopia', 'kdnaforms' ),
			'FK' => __( 'Falkland Islands', 'kdnaforms' ),
			'FO' => __( 'Faroe Islands', 'kdnaforms' ),
			'FJ' => __( 'Fiji', 'kdnaforms' ),
			'FI' => __( 'Finland', 'kdnaforms' ),
			'FR' => __( 'France', 'kdnaforms' ),
			'GF' => __( 'French Guiana', 'kdnaforms' ),
			'PF' => __( 'French Polynesia', 'kdnaforms' ),
			'TF' => __( 'French Southern Territories', 'kdnaforms' ),
			'GA' => __( 'Gabon', 'kdnaforms' ),
			'GM' => __( 'Gambia', 'kdnaforms' ),
			'GE' => _x( 'Georgia', 'Country', 'kdnaforms' ),
			'DE' => __( 'Germany', 'kdnaforms' ),
			'GH' => __( 'Ghana', 'kdnaforms' ),
			'GI' => __( 'Gibraltar', 'kdnaforms' ),
			'GR' => __( 'Greece', 'kdnaforms' ),
			'GL' => __( 'Greenland', 'kdnaforms' ),
			'GD' => __( 'Grenada', 'kdnaforms' ),
			'GP' => __( 'Guadeloupe', 'kdnaforms' ),
			'GU' => __( 'Guam', 'kdnaforms' ),
			'GT' => __( 'Guatemala', 'kdnaforms' ),
			'GG' => __( 'Guernsey', 'kdnaforms' ),
			'GN' => __( 'Guinea', 'kdnaforms' ),
			'GW' => __( 'Guinea-Bissau', 'kdnaforms' ),
			'GY' => __( 'Guyana', 'kdnaforms' ),
			'HT' => __( 'Haiti', 'kdnaforms' ),
			'HM' => __( 'Heard Island and McDonald Islands', 'kdnaforms' ),
			'VA' => __( 'Holy See', 'kdnaforms' ),
			'HN' => __( 'Honduras', 'kdnaforms' ),
			'HK' => __( 'Hong Kong', 'kdnaforms' ),
			'HU' => __( 'Hungary', 'kdnaforms' ),
			'IS' => __( 'Iceland', 'kdnaforms' ),
			'IN' => __( 'India', 'kdnaforms' ),
			'ID' => __( 'Indonesia', 'kdnaforms' ),
			'IR' => __( 'Iran', 'kdnaforms' ),
			'IQ' => __( 'Iraq', 'kdnaforms' ),
			'IE' => __( 'Ireland', 'kdnaforms' ),
			'IM' => __( 'Isle of Man', 'kdnaforms' ),
			'IL' => __( 'Israel', 'kdnaforms' ),
			'IT' => __( 'Italy', 'kdnaforms' ),
			'JM' => __( 'Jamaica', 'kdnaforms' ),
			'JP' => __( 'Japan', 'kdnaforms' ),
			'JE' => __( 'Jersey', 'kdnaforms' ),
			'JO' => __( 'Jordan', 'kdnaforms' ),
			'KZ' => __( 'Kazakhstan', 'kdnaforms' ),
			'KE' => __( 'Kenya', 'kdnaforms' ),
			'KI' => __( 'Kiribati', 'kdnaforms' ),
			'KP' => __( "Korea, Democratic People's Republic of", 'kdnaforms' ),
			'KR' => __( 'Korea, Republic of', 'kdnaforms' ),
			'KW' => __( 'Kuwait', 'kdnaforms' ),
			'KG' => __( 'Kyrgyzstan', 'kdnaforms' ),
			'LA' => __( "Lao People's Democratic Republic", 'kdnaforms' ),
			'LV' => __( 'Latvia', 'kdnaforms' ),
			'LB' => __( 'Lebanon', 'kdnaforms' ),
			'LS' => __( 'Lesotho', 'kdnaforms' ),
			'LR' => __( 'Liberia', 'kdnaforms' ),
			'LY' => __( 'Libya', 'kdnaforms' ),
			'LI' => __( 'Liechtenstein', 'kdnaforms' ),
			'LT' => __( 'Lithuania', 'kdnaforms' ),
			'LU' => __( 'Luxembourg', 'kdnaforms' ),
			'MO' => __( 'Macao', 'kdnaforms' ),
			'MG' => __( 'Madagascar', 'kdnaforms' ),
			'MW' => __( 'Malawi', 'kdnaforms' ),
			'MY' => __( 'Malaysia', 'kdnaforms' ),
			'MV' => __( 'Maldives', 'kdnaforms' ),
			'ML' => __( 'Mali', 'kdnaforms' ),
			'MT' => __( 'Malta', 'kdnaforms' ),
			'MH' => __( 'Marshall Islands', 'kdnaforms' ),
			'MQ' => __( 'Martinique', 'kdnaforms' ),
			'MR' => __( 'Mauritania', 'kdnaforms' ),
			'MU' => __( 'Mauritius', 'kdnaforms' ),
			'YT' => __( 'Mayotte', 'kdnaforms' ),
			'MX' => __( 'Mexico', 'kdnaforms' ),
			'FM' => __( 'Micronesia', 'kdnaforms' ),
			'MD' => __( 'Moldova', 'kdnaforms' ),
			'MC' => __( 'Monaco', 'kdnaforms' ),
			'MN' => __( 'Mongolia', 'kdnaforms' ),
			'ME' => __( 'Montenegro', 'kdnaforms' ),
			'MS' => __( 'Montserrat', 'kdnaforms' ),
			'MA' => __( 'Morocco', 'kdnaforms' ),
			'MZ' => __( 'Mozambique', 'kdnaforms' ),
			'MM' => __( 'Myanmar', 'kdnaforms' ),
			'NA' => __( 'Namibia', 'kdnaforms' ),
			'NR' => __( 'Nauru', 'kdnaforms' ),
			'NP' => __( 'Nepal', 'kdnaforms' ),
			'NL' => __( 'Netherlands', 'kdnaforms' ),
			'NC' => __( 'New Caledonia', 'kdnaforms' ),
			'NZ' => __( 'New Zealand', 'kdnaforms' ),
			'NI' => __( 'Nicaragua', 'kdnaforms' ),
			'NE' => __( 'Niger', 'kdnaforms' ),
			'NG' => __( 'Nigeria', 'kdnaforms' ),
			'NU' => __( 'Niue', 'kdnaforms' ),
			'NF' => __( 'Norfolk Island', 'kdnaforms' ),
			'MK' => __( 'North Macedonia', 'kdnaforms' ),
			'MP' => __( 'Northern Mariana Islands', 'kdnaforms' ),
			'NO' => __( 'Norway', 'kdnaforms' ),
			'OM' => __( 'Oman', 'kdnaforms' ),
			'PK' => __( 'Pakistan', 'kdnaforms' ),
			'PW' => __( 'Palau', 'kdnaforms' ),
			'PS' => __( 'Palestine, State of', 'kdnaforms' ),
			'PA' => __( 'Panama', 'kdnaforms' ),
			'PG' => __( 'Papua New Guinea', 'kdnaforms' ),
			'PY' => __( 'Paraguay', 'kdnaforms' ),
			'PE' => __( 'Peru', 'kdnaforms' ),
			'PH' => __( 'Philippines', 'kdnaforms' ),
			'PN' => __( 'Pitcairn', 'kdnaforms' ),
			'PL' => __( 'Poland', 'kdnaforms' ),
			'PT' => __( 'Portugal', 'kdnaforms' ),
			'PR' => __( 'Puerto Rico', 'kdnaforms' ),
			'QA' => __( 'Qatar', 'kdnaforms' ),
			'RE' => __( 'Réunion', 'kdnaforms' ),
			'RO' => __( 'Romania', 'kdnaforms' ),
			'RU' => __( 'Russian Federation', 'kdnaforms' ),
			'RW' => __( 'Rwanda', 'kdnaforms' ),
			'BL' => __( 'Saint Barthélemy', 'kdnaforms' ),
			'SH' => __( 'Saint Helena, Ascension and Tristan da Cunha', 'kdnaforms' ),
			'KN' => __( 'Saint Kitts and Nevis', 'kdnaforms' ),
			'LC' => __( 'Saint Lucia', 'kdnaforms' ),
			'MF' => __( 'Saint Martin', 'kdnaforms' ),
			'PM' => __( 'Saint Pierre and Miquelon', 'kdnaforms' ),
			'VC' => __( 'Saint Vincent and the Grenadines', 'kdnaforms' ),
			'WS' => __( 'Samoa', 'kdnaforms' ),
			'SM' => __( 'San Marino', 'kdnaforms' ),
			'ST' => __( 'Sao Tome and Principe', 'kdnaforms' ),
			'SA' => __( 'Saudi Arabia', 'kdnaforms' ),
			'SN' => __( 'Senegal', 'kdnaforms' ),
			'RS' => __( 'Serbia', 'kdnaforms' ),
			'SC' => __( 'Seychelles', 'kdnaforms' ),
			'SL' => __( 'Sierra Leone', 'kdnaforms' ),
			'SG' => __( 'Singapore', 'kdnaforms' ),
			'SX' => __( 'Sint Maarten', 'kdnaforms' ),
			'SK' => __( 'Slovakia', 'kdnaforms' ),
			'SI' => __( 'Slovenia', 'kdnaforms' ),
			'SB' => __( 'Solomon Islands', 'kdnaforms' ),
			'SO' => __( 'Somalia', 'kdnaforms' ),
			'ZA' => __( 'South Africa', 'kdnaforms' ),
			'GS' => _x( 'South Georgia and the South Sandwich Islands', 'Country', 'kdnaforms' ),
			'SS' => __( 'South Sudan', 'kdnaforms' ),
			'ES' => __( 'Spain', 'kdnaforms' ),
			'LK' => __( 'Sri Lanka', 'kdnaforms' ),
			'SD' => __( 'Sudan', 'kdnaforms' ),
			'SR' => __( 'Suriname', 'kdnaforms' ),
			'SJ' => __( 'Svalbard and Jan Mayen', 'kdnaforms' ),
			'SE' => __( 'Sweden', 'kdnaforms' ),
			'CH' => __( 'Switzerland', 'kdnaforms' ),
			'SY' => __( 'Syria Arab Republic', 'kdnaforms' ),
			'TW' => __( 'Taiwan', 'kdnaforms' ),
			'TJ' => __( 'Tajikistan', 'kdnaforms' ),
			'TZ' => __( 'Tanzania, the United Republic of', 'kdnaforms' ),
			'TH' => __( 'Thailand', 'kdnaforms' ),
			'TL' => __( 'Timor-Leste', 'kdnaforms' ),
			'TG' => __( 'Togo', 'kdnaforms' ),
			'TK' => __( 'Tokelau', 'kdnaforms' ),
			'TO' => __( 'Tonga', 'kdnaforms' ),
			'TT' => __( 'Trinidad and Tobago', 'kdnaforms' ),
			'TN' => __( 'Tunisia', 'kdnaforms' ),
			'TR' => __( 'Türkiye', 'kdnaforms' ),
			'TM' => __( 'Turkmenistan', 'kdnaforms' ),
			'TC' => __( 'Turks and Caicos Islands', 'kdnaforms' ),
			'TV' => __( 'Tuvalu', 'kdnaforms' ),
			'UG' => __( 'Uganda', 'kdnaforms' ),
			'UA' => __( 'Ukraine', 'kdnaforms' ),
			'AE' => __( 'United Arab Emirates', 'kdnaforms' ),
			'GB' => __( 'United Kingdom', 'kdnaforms' ),
			'US' => __( 'United States', 'kdnaforms' ),
			'UY' => __( 'Uruguay', 'kdnaforms' ),
			'UM' => __( 'US Minor Outlying Islands', 'kdnaforms' ),
			'UZ' => __( 'Uzbekistan', 'kdnaforms' ),
			'VU' => __( 'Vanuatu', 'kdnaforms' ),
			'VE' => __( 'Venezuela', 'kdnaforms' ),
			'VN' => __( 'Viet Nam', 'kdnaforms' ),
			'VG' => __( 'Virgin Islands, British', 'kdnaforms' ),
			'VI' => __( 'Virgin Islands, U.S.', 'kdnaforms' ),
			'WF' => __( 'Wallis and Futuna', 'kdnaforms' ),
			'EH' => __( 'Western Sahara', 'kdnaforms' ),
			'YE' => __( 'Yemen', 'kdnaforms' ),
			'ZM' => __( 'Zambia', 'kdnaforms' ),
			'ZW' => __( 'Zimbabwe', 'kdnaforms' ),
		);
	}

	/**
	 * Returns the ISO 3166-1 alpha-2 code for the supplied country name.
	 *
	 * @since Unknown
	 *
	 * @param string $country_name The country name.
	 *
	 * @return string|null
	 */
	public function get_country_code( $country_name ) {
		$codes = $this->get_country_codes();

		return rgar( $codes, KDNACommon::safe_strtoupper( $country_name ) );
	}

	/**
	 * Returns the default countries array updated to use the uppercase country name as the key to the ISO 3166-1 alpha-2 code.
	 *
	 * @since Unknown
	 * @since 2.4     Updated to use ISO 3166-1 list of countries.
	 * @since 2.4.20  Updated to use KDNA_Field_Address::get_default_countries().
	 *
	 * @return array
	 */
	public function get_country_codes() {
		$countries = array_map( array( 'KDNACommon', 'safe_strtoupper' ), $this->get_default_countries() );

		return array_flip( $countries );
	}

	/**
	 * Returns the array of US states and territories.
	 *
	 * @since Unknown
	 *
	 * @return array The array of US states.
	 */
	public function get_us_states() {
		/**
		 * Filters the US states array.
		 *
		 * @since Unknown
		 *
		 * @param array The array of US states.
		 */
		return apply_filters(
			'kdnaform_us_states', array(
				__( 'Alabama', 'kdnaforms' ),
				__( 'Alaska', 'kdnaforms' ),
				__( 'American Samoa', 'kdnaforms' ),
				__( 'Arizona', 'kdnaforms' ),
				__( 'Arkansas', 'kdnaforms' ),
				__( 'California', 'kdnaforms' ),
				__( 'Colorado', 'kdnaforms' ),
				__( 'Connecticut', 'kdnaforms' ),
				__( 'Delaware', 'kdnaforms' ),
				__( 'District of Columbia', 'kdnaforms' ),
				__( 'Florida', 'kdnaforms' ),
				_x( 'Georgia', 'US State', 'kdnaforms' ),
				__( 'Guam', 'kdnaforms' ),
				__( 'Hawaii', 'kdnaforms' ),
				__( 'Idaho', 'kdnaforms' ),
				__( 'Illinois', 'kdnaforms' ),
				__( 'Indiana', 'kdnaforms' ),
				__( 'Iowa', 'kdnaforms' ),
				__( 'Kansas', 'kdnaforms' ),
				__( 'Kentucky', 'kdnaforms' ),
				__( 'Louisiana', 'kdnaforms' ),
				__( 'Maine', 'kdnaforms' ),
				__( 'Maryland', 'kdnaforms' ),
				__( 'Massachusetts', 'kdnaforms' ),
				__( 'Michigan', 'kdnaforms' ),
				__( 'Minnesota', 'kdnaforms' ),
				__( 'Mississippi', 'kdnaforms' ),
				__( 'Missouri', 'kdnaforms' ),
				__( 'Montana', 'kdnaforms' ),
				__( 'Nebraska', 'kdnaforms' ),
				__( 'Nevada', 'kdnaforms' ),
				__( 'New Hampshire', 'kdnaforms' ),
				__( 'New Jersey', 'kdnaforms' ),
				__( 'New Mexico', 'kdnaforms' ),
				__( 'New York', 'kdnaforms' ),
				__( 'North Carolina', 'kdnaforms' ),
				__( 'North Dakota', 'kdnaforms' ),
				__( 'Northern Mariana Islands', 'kdnaforms' ),
				__( 'Ohio', 'kdnaforms' ),
				__( 'Oklahoma', 'kdnaforms' ),
				__( 'Oregon', 'kdnaforms' ),
				__( 'Pennsylvania', 'kdnaforms' ),
				__( 'Puerto Rico', 'kdnaforms' ),
				__( 'Rhode Island', 'kdnaforms' ),
				__( 'South Carolina', 'kdnaforms' ),
				__( 'South Dakota', 'kdnaforms' ),
				__( 'Tennessee', 'kdnaforms' ),
				__( 'Texas', 'kdnaforms' ),
				__( 'Utah', 'kdnaforms' ),
				__( 'U.S. Virgin Islands', 'kdnaforms' ),
				__( 'Vermont', 'kdnaforms' ),
				__( 'Virginia', 'kdnaforms' ),
				__( 'Washington', 'kdnaforms' ),
				__( 'West Virginia', 'kdnaforms' ),
				__( 'Wisconsin', 'kdnaforms' ),
				__( 'Wyoming', 'kdnaforms' ),
				__( 'Armed Forces Americas', 'kdnaforms' ),
				__( 'Armed Forces Europe', 'kdnaforms' ),
				__( 'Armed Forces Pacific', 'kdnaforms' ),
			)
		);
	}

	/**
	 * Returns the two-letter US state code from the state name provided.
	 *
	 * @since Unknown
	 *
	 * @param string $state_name The state name.
	 *
	 * @return string The two-letter US state code.
	 */
	public function get_us_state_code( $state_name ) {
		$states = array(
			KDNACommon::safe_strtoupper( __( 'Alabama', 'kdnaforms' ) )                  => 'AL',
			KDNACommon::safe_strtoupper( __( 'Alaska', 'kdnaforms' ) )                   => 'AK',
			KDNACommon::safe_strtoupper( __( 'American Samoa', 'kdnaforms' ) )           => 'AS',
			KDNACommon::safe_strtoupper( __( 'Arizona', 'kdnaforms' ) )                  => 'AZ',
			KDNACommon::safe_strtoupper( __( 'Arkansas', 'kdnaforms' ) )                 => 'AR',
			KDNACommon::safe_strtoupper( __( 'California', 'kdnaforms' ) )               => 'CA',
			KDNACommon::safe_strtoupper( __( 'Colorado', 'kdnaforms' ) )                 => 'CO',
			KDNACommon::safe_strtoupper( __( 'Connecticut', 'kdnaforms' ) )              => 'CT',
			KDNACommon::safe_strtoupper( __( 'Delaware', 'kdnaforms' ) )                 => 'DE',
			KDNACommon::safe_strtoupper( __( 'District of Columbia', 'kdnaforms' ) )     => 'DC',
			KDNACommon::safe_strtoupper( __( 'Florida', 'kdnaforms' ) )                  => 'FL',
			KDNACommon::safe_strtoupper( _x( 'Georgia', 'US State', 'kdnaforms' ) )      => 'GA',
			KDNACommon::safe_strtoupper( __( 'Guam', 'kdnaforms' ) )                     => 'GU',
			KDNACommon::safe_strtoupper( __( 'Hawaii', 'kdnaforms' ) )                   => 'HI',
			KDNACommon::safe_strtoupper( __( 'Idaho', 'kdnaforms' ) )                    => 'ID',
			KDNACommon::safe_strtoupper( __( 'Illinois', 'kdnaforms' ) )                 => 'IL',
			KDNACommon::safe_strtoupper( __( 'Indiana', 'kdnaforms' ) )                  => 'IN',
			KDNACommon::safe_strtoupper( __( 'Iowa', 'kdnaforms' ) )                     => 'IA',
			KDNACommon::safe_strtoupper( __( 'Kansas', 'kdnaforms' ) )                   => 'KS',
			KDNACommon::safe_strtoupper( __( 'Kentucky', 'kdnaforms' ) )                 => 'KY',
			KDNACommon::safe_strtoupper( __( 'Louisiana', 'kdnaforms' ) )                => 'LA',
			KDNACommon::safe_strtoupper( __( 'Maine', 'kdnaforms' ) )                    => 'ME',
			KDNACommon::safe_strtoupper( __( 'Maryland', 'kdnaforms' ) )                 => 'MD',
			KDNACommon::safe_strtoupper( __( 'Massachusetts', 'kdnaforms' ) )            => 'MA',
			KDNACommon::safe_strtoupper( __( 'Michigan', 'kdnaforms' ) )                 => 'MI',
			KDNACommon::safe_strtoupper( __( 'Minnesota', 'kdnaforms' ) )                => 'MN',
			KDNACommon::safe_strtoupper( __( 'Mississippi', 'kdnaforms' ) )              => 'MS',
			KDNACommon::safe_strtoupper( __( 'Missouri', 'kdnaforms' ) )                 => 'MO',
			KDNACommon::safe_strtoupper( __( 'Montana', 'kdnaforms' ) )                  => 'MT',
			KDNACommon::safe_strtoupper( __( 'Nebraska', 'kdnaforms' ) )                 => 'NE',
			KDNACommon::safe_strtoupper( __( 'Nevada', 'kdnaforms' ) )                   => 'NV',
			KDNACommon::safe_strtoupper( __( 'New Hampshire', 'kdnaforms' ) )            => 'NH',
			KDNACommon::safe_strtoupper( __( 'New Jersey', 'kdnaforms' ) )               => 'NJ',
			KDNACommon::safe_strtoupper( __( 'New Mexico', 'kdnaforms' ) )               => 'NM',
			KDNACommon::safe_strtoupper( __( 'New York', 'kdnaforms' ) )                 => 'NY',
			KDNACommon::safe_strtoupper( __( 'North Carolina', 'kdnaforms' ) )           => 'NC',
			KDNACommon::safe_strtoupper( __( 'North Dakota', 'kdnaforms' ) )             => 'ND',
			KDNACommon::safe_strtoupper( __( 'Northern Mariana Islands', 'kdnaforms' ) ) => 'MP',
			KDNACommon::safe_strtoupper( __( 'Ohio', 'kdnaforms' ) )                     => 'OH',
			KDNACommon::safe_strtoupper( __( 'Oklahoma', 'kdnaforms' ) )                 => 'OK',
			KDNACommon::safe_strtoupper( __( 'Oregon', 'kdnaforms' ) )                   => 'OR',
			KDNACommon::safe_strtoupper( __( 'Pennsylvania', 'kdnaforms' ) )             => 'PA',
			KDNACommon::safe_strtoupper( __( 'Puerto Rico', 'kdnaforms' ) )              => 'PR',
			KDNACommon::safe_strtoupper( __( 'Rhode Island', 'kdnaforms' ) )             => 'RI',
			KDNACommon::safe_strtoupper( __( 'South Carolina', 'kdnaforms' ) )           => 'SC',
			KDNACommon::safe_strtoupper( __( 'South Dakota', 'kdnaforms' ) )             => 'SD',
			KDNACommon::safe_strtoupper( __( 'Tennessee', 'kdnaforms' ) )                => 'TN',
			KDNACommon::safe_strtoupper( __( 'Texas', 'kdnaforms' ) )                    => 'TX',
			KDNACommon::safe_strtoupper( __( 'Utah', 'kdnaforms' ) )                     => 'UT',
			KDNACommon::safe_strtoupper( __( 'U.S. Virgin Islands', 'kdnaforms' ) )      => 'VI',
			KDNACommon::safe_strtoupper( __( 'Vermont', 'kdnaforms' ) )                  => 'VT',
			KDNACommon::safe_strtoupper( __( 'Virginia', 'kdnaforms' ) )                 => 'VA',
			KDNACommon::safe_strtoupper( __( 'Washington', 'kdnaforms' ) )               => 'WA',
			KDNACommon::safe_strtoupper( __( 'West Virginia', 'kdnaforms' ) )            => 'WV',
			KDNACommon::safe_strtoupper( __( 'Wisconsin', 'kdnaforms' ) )                => 'WI',
			KDNACommon::safe_strtoupper( __( 'Wyoming', 'kdnaforms' ) )                  => 'WY',
			KDNACommon::safe_strtoupper( __( 'Armed Forces Americas', 'kdnaforms' ) )    => 'AA',
			KDNACommon::safe_strtoupper( __( 'Armed Forces Europe', 'kdnaforms' ) )      => 'AE',
			KDNACommon::safe_strtoupper( __( 'Armed Forces Pacific', 'kdnaforms' ) )     => 'AP',
		);

		$state_name = KDNACommon::safe_strtoupper( $state_name );
		$code       = isset( $states[ $state_name ] ) ? $states[ $state_name ] : $state_name;

		return $code;
	}

	public function get_canadian_provinces() {
		return array(
			__( 'Alberta', 'kdnaforms' ),
			__( 'British Columbia', 'kdnaforms' ),
			__( 'Manitoba', 'kdnaforms' ),
			__( 'New Brunswick', 'kdnaforms' ),
			__( 'Newfoundland and Labrador', 'kdnaforms' ),
			__( 'Northwest Territories', 'kdnaforms' ),
			__( 'Nova Scotia', 'kdnaforms' ),
			__( 'Nunavut', 'kdnaforms' ),
			__( 'Ontario', 'kdnaforms' ),
			__( 'Prince Edward Island', 'kdnaforms' ),
			__( 'Quebec', 'kdnaforms' ),
			__( 'Saskatchewan', 'kdnaforms' ),
			__( 'Yukon', 'kdnaforms' )
		);
	}

	public function get_state_dropdown( $states, $selected_state = '', $placeholder = '' ) {
		$str = '';
		foreach ( $states as $code => $state ) {
			if ( is_array( $state ) ) {
				$str .= sprintf( '<optgroup label="%1$s">%2$s</optgroup>', esc_attr( $code ), $this->get_state_dropdown( $state, $selected_state, $placeholder ) );
			} else {
				if ( is_numeric( $code ) ) {
					$code = $state;
				}
				if ( empty( $state ) ) {
					$state = $placeholder;
				}

				$str .= $this->get_select_option( $code, $state, $selected_state );
			}
		}

		return $str;
	}

	/**
	 * Returns the option tag for the current choice.
	 *
	 * @param string $value The choice value.
	 * @param string $label The choice label.
	 * @param string $selected_value The value for the selected choice.
	 *
	 * @return string
	 */
	public function get_select_option( $value, $label, $selected_value ) {
		$selected = $value == $selected_value ? "selected='selected'" : '';

		return sprintf( "<option value='%s' %s>%s</option>", esc_attr( $value ), $selected, esc_html( $label ) );
	}

	public function get_us_state_dropdown( $selected_state = '' ) {
		$states = array_merge( array( '' ), $this->get_us_states() );
		$str    = '';
		foreach ( $states as $code => $state ) {
			if ( is_numeric( $code ) ) {
				$code = $state;
			}

			$selected = $code == $selected_state ? "selected='selected'" : '';
			$str .= "<option value='" . esc_attr( $code ) . "' $selected>" . esc_html( $state ) . '</option>';
		}

		return $str;
	}

	public function get_canadian_provinces_dropdown( $selected_province = '' ) {
		$states = array_merge( array( '' ), $this->get_canadian_provinces() );
		$str    = '';
		foreach ( $states as $state ) {
			$selected = $state == $selected_province ? "selected='selected'" : '';
			$str .= "<option value='" . esc_attr( $state ) . "' $selected>" . esc_html( $state ) . '</option>';
		}

		return $str;
	}

	public function get_country_dropdown( $selected_country = '', $placeholder = '' ) {
		$str       = '';
		$selected_country = strtolower( $selected_country );
		$countries = array_merge( array( '' ), $this->get_countries() );
		foreach ( $countries as $code => $country ) {
			if ( is_numeric( $code ) ) {
				$code = $country;
			}
			if ( empty( $country ) ) {
				$country = $placeholder;
			}
			$selected = strtolower( esc_attr( $code ) ) == $selected_country ? "selected='selected'" : '';
			$str .= "<option value='" . esc_attr( $code ) . "' $selected>" . esc_html( $country ) . '</option>';
		}

		return $str;
	}

	/**
	 * Format the entry value for display on the entry detail page and for the {all_fields} merge tag.
	 *
	 * @since 1.9
	 * @since 2.9.29 Changed the second parameter $currency (string) to $entry (array).
	 *
	 * @param string|array $value    The field value.
	 * @param array        $entry    The entry.
	 * @param bool|false   $use_text When processing choice based fields should the choice text be returned instead of the value.
	 * @param string       $format   The format requested for the location the merge is being used. Possible values: html, text or url.
	 * @param string       $media    The location where the value will be displayed. Possible values: screen or email.
	 *
	 * @return string
	 */
	public function get_value_entry_detail( $value, $entry = array(), $use_text = false, $format = 'html', $media = 'screen' ) {
		if ( is_array( $value ) ) {
			$street_value  = trim( rgget( $this->id . '.1', $value ) );
			$street2_value = trim( rgget( $this->id . '.2', $value ) );
			$city_value    = trim( rgget( $this->id . '.3', $value ) );
			$state_value   = trim( rgget( $this->id . '.4', $value ) );
			$zip_value     = trim( rgget( $this->id . '.5', $value ) );
			$country_value = trim( rgget( $this->id . '.6', $value ) );

			if ( $format === 'html' ) {
				$street_value  = esc_html( $street_value );
				$street2_value = esc_html( $street2_value );
				$city_value    = esc_html( $city_value );
				$state_value   = esc_html( $state_value );
				$zip_value     = esc_html( $zip_value );
				$country_value = esc_html( $country_value );

				$line_break = '<br />';
			} else {
				$line_break = "\n";
			}

			/**
			 * Filters the format that the address is displayed in.
			 *
			 * @since Unknown
			 *
			 * @param string           'default' The format to use. Defaults to 'default'.
			 * @param KDNA_Field_Address $this     An instance of the KDNA_Field_Address object.
			 */
			$address_display_format = apply_filters( 'kdnaform_address_display_format', 'default', $this );
			if ( $address_display_format == 'zip_before_city' ) {
				/*
                Sample:
                3333 Some Street
                suite 16
                2344 City, State
                Country
                */

				$addr_ary   = array();
				$addr_ary[] = $street_value;

				if ( ! empty( $street2_value ) ) {
					$addr_ary[] = $street2_value;
				}

				$zip_line = trim( $zip_value . ' ' . $city_value );
				$zip_line .= ! empty( $zip_line ) && ! empty( $state_value ) ? ", {$state_value}" : $state_value;
				$zip_line = trim( $zip_line );
				if ( ! empty( $zip_line ) ) {
					$addr_ary[] = $zip_line;
				}

				if ( ! empty( $country_value ) ) {
					$addr_ary[] = $country_value;
				}

				$address = implode( '<br />', $addr_ary );

			} else {
				$address = $street_value;
				$address .= ! empty( $address ) && ! empty( $street2_value ) ? $line_break . $street2_value : $street2_value;
				$address .= ! empty( $address ) && ( ! empty( $city_value ) || ! empty( $state_value ) ) ? $line_break . $city_value : $city_value;
				$address .= ! empty( $address ) && ! empty( $city_value ) && ! empty( $state_value ) ? ", $state_value" : $state_value;
				$address .= ! empty( $address ) && ! empty( $zip_value ) ? " $zip_value" : $zip_value;
				$address .= ! empty( $address ) && ! empty( $country_value ) ? $line_break . $country_value : $country_value;
			}

			// Adding map link.
			/**
			 * Disables the Google Maps link from displaying in the address field.
			 *
			 * @since 1.9
			 *
			 * @param bool false Determines if the map link should be disabled. Set to true to disable. Defaults to false.
			 */
			$map_link_disabled = apply_filters( 'kdnaform_disable_address_map_link', false );
			if ( ! empty( $address ) && $format == 'html' && ! $map_link_disabled ) {
				$address_qs = str_replace( $line_break, ' ', $address ); //replacing <br/> and \n with spaces
				$address_qs = urlencode( $address_qs );
				$address .= "<br/><a href='https://maps.google.com/maps?q={$address_qs}' target='_blank' class='map-it-link'>Map It</a>";
			}

			return $address;
		} else {
			return '';
		}
	}

	public function sanitize_settings() {
		parent::sanitize_settings();
		if ( $this->addressType ) {
			$this->addressType = wp_strip_all_tags( $this->addressType );
		}

		if ( $this->defaultCountry ) {
			$this->defaultCountry = wp_strip_all_tags( $this->defaultCountry );
		}

		if ( $this->defaultProvince ) {
			$this->defaultProvince = wp_strip_all_tags( $this->defaultProvince );
		}

		if ( $this->copyValuesOptionLabel ) {
			$this->copyValuesOptionLabel = wp_strip_all_tags( $this->copyValuesOptionLabel );
		}

	}

	public function get_value_export( $entry, $input_id = '', $use_text = false, $is_csv = false ) {
		if ( empty( $input_id ) ) {
			$input_id = $this->id;
		}

		if ( absint( $input_id ) == $input_id ) {
			$street_value  = str_replace( '  ', ' ', trim( rgar( $entry, $input_id . '.1' ) ) );
			$street2_value = str_replace( '  ', ' ', trim( rgar( $entry, $input_id . '.2' ) ) );
			$city_value    = str_replace( '  ', ' ', trim( rgar( $entry, $input_id . '.3' ) ) );
			$state_value   = str_replace( '  ', ' ', trim( rgar( $entry, $input_id . '.4' ) ) );
			$zip_value     = trim( rgar( $entry, $input_id . '.5' ) );
			$country_value = $this->get_country_code( trim( rgar( $entry, $input_id . '.6' ) ) );

			$address = $street_value;
			$address .= ! empty( $address ) && ! empty( $street2_value ) ? "  $street2_value" : $street2_value;
			$address .= ! empty( $address ) && ( ! empty( $city_value ) || ! empty( $state_value ) ) ? ", $city_value," : $city_value;
			$address .= ! empty( $address ) && ! empty( $city_value ) && ! empty( $state_value ) ? "  $state_value" : $state_value;
			$address .= ! empty( $address ) && ! empty( $zip_value ) ? "  $zip_value," : $zip_value;
			$address .= ! empty( $address ) && ! empty( $country_value ) ? "  $country_value" : $country_value;

			return $address;
		} else {

			return rgar( $entry, $input_id );
		}
	}

	/**
	 * Removes the "for" attribute in the field label. Inputs are only allowed one label (a11y) and the inputs already have labels.
	 *
	 * @since  2.4
	 * @access public
	 *
	 * @param array $form The Form Object currently being processed.
	 *
	 * @return string
	 */
	public function get_first_input_id( $form ) {
		return '';
	}

	// # FIELD FILTER UI HELPERS ---------------------------------------------------------------------------------------

	/**
	 * Returns the sub-filters for the current field.
	 *
	 * @since 2.4
	 *
	 * @return array
	 */
	public function get_filter_sub_filters() {
		$sub_filters = array();
		$inputs      = $this->inputs;

		foreach ( $inputs as $input ) {
			if ( rgar( $input, 'isHidden' ) ) {
				continue;
			}

			$sub_filters[] = array(
				'key'             => rgar( $input, 'id' ),
				'text'            => rgar( $input, 'customLabel', rgar( $input, 'label' ) ),
				'preventMultiple' => false,
				'operators'       => $this->get_filter_operators(),
			);
		}

		return $sub_filters;
	}

	/**
	 * Returns the filter operators for the current field.
	 *
	 * @since 2.4
	 *
	 * @return array
	 */
	public function get_filter_operators() {
		$operators   = parent::get_filter_operators();
		$operators[] = 'contains';

		return $operators;
	}
}

KDNA_Fields::register( new KDNA_Field_Address() );
