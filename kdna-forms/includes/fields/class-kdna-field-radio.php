<?php

if ( ! class_exists( 'KDNAForms' ) ) {
	die();
}

require_once( plugin_dir_path( __FILE__ ) . 'field-decorator-choice/class-kdna-field-decorator-choice-radio-markup.php' );

class KDNA_Field_Radio extends KDNA_Field {

	public $type = 'radio';

	/**
	 * Indicates if this field supports state validation.
	 *
	 * @since 2.5.11
	 *
	 * @var bool
	 */
	protected $_supports_state_validation = true;

	public function get_form_editor_field_title() {
		return esc_attr__( 'Radio Buttons', 'kdnaforms' );
	}

	/**
	 * Returns the field's form editor description.
	 *
	 * @since 2.5
	 *
	 * @return string
	 */
	public function get_form_editor_field_description() {
		return esc_attr__( 'Allows users to select one option from a list.', 'kdnaforms' );
	}

	/**
	 * Returns the field's form editor icon.
	 *
	 * This could be an icon url or a kdnaform-icon class.
	 *
	 * @since 2.5
	 *
	 * @return string
	 */
	public function get_form_editor_field_icon() {
		return 'kdnaform-icon--radio-button';
	}

	function get_form_editor_field_settings() {
		return array(
			'conditional_logic_field_setting',
			'prepopulate_field_setting',
			'error_message_setting',
			'label_setting',
			'label_placement_setting',
			'admin_label_setting',
			'choices_setting',
			'rules_setting',
			'visibility_setting',
			'duplicate_setting',
			'description_setting',
			'css_class_setting',
			'other_choice_setting',
			'display_choices_columns_setting',
		);
	}

	public function is_conditional_logic_supported() {
		return true;
	}

	/**
	 * Determines if this field will be processed by the state validation.
	 *
	 * @since 2.5.11
	 *
	 * @return bool
	 */
	public function is_state_validation_supported() {
		if ( $this->enableOtherChoice && rgpost( "is_submit_{$this->formId}" ) && rgpost( "input_{$this->id}" ) == 'gf_other_choice' ) {
			return false;
		}

		return parent::is_state_validation_supported();
	}

	public function validate( $value, $form ) {
		if ( $this->isRequired && $this->enableOtherChoice && rgpost( "input_{$this->id}" ) == 'gf_other_choice' ) {
			if ( empty( $value ) || strtolower( $value ) == strtolower( KDNACommon::get_other_choice_value( $this ) ) ) {
				$this->failed_validation  = true;
				$this->validation_message = empty( $this->errorMessage ) ? esc_html__( 'This field is required.', 'kdnaforms' ) : $this->errorMessage;
			}
		}
	}

	public function get_first_input_id( $form ) {
		return '';
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

	public function get_field_input( $form, $value = '', $entry = null ) {

		if ( $this->type == 'image_choice' ) {
			$this->image_markup = new KDNA_Field_Decorator_Choice_Radio_Markup( $this );
			return $this->image_markup->get_field_input( $form, $value, $entry );
		}

		$form_id         = $form['id'];
		$is_entry_detail = $this->is_entry_detail();
		$is_form_editor  = $this->is_form_editor();

		$id            = $this->id;
		$field_id      = $is_entry_detail || $is_form_editor || $form_id == 0 ? "input_$id" : 'input_' . $form_id . "_$id";
		$disabled_text = $is_form_editor ? 'disabled="disabled"' : '';
		$tag           = KDNACommon::is_legacy_markup_enabled( $form ) ? 'ul' : 'div';

		return sprintf( "<div class='kdnainput_container kdnainput_container_radio'><{$tag} class='kdnafield_radio' id='%s'>%s</{$tag}></div>", $field_id, $this->get_radio_choices( $value, $disabled_text, $form_id ) );

	}

	public function get_radio_choices( $value = '', $disabled_text = '', $form_id = 0 ) {
		$choices = '';

		if ( is_array( $this->choices ) ) {
			$is_entry_detail    = $this->is_entry_detail();
			$is_form_editor     = $this->is_form_editor();
			$is_admin           = $is_entry_detail || $is_form_editor;

			$field_choices      = $this->choices;
			$needs_other_choice = $this->enableOtherChoice;
			$editor_limited     = false;

			$choice_id = 0;
			$count     = 1;
			// Determine max choices to show in the form editor if Display in columns setting is enabled.
			$max_choices = $this->enableDisplayInColumns === true ? 10 : 5;

			/**
			 * A filter that allows for the setting of the maximum number of choices shown in
			 * the form editor for choice based fields (radio, checkbox, image, and multiple choice).
			 *
			 * @since 2.9
			 *
			 * @param int    $max_choices_visible_count The default number of choices visible is 5.
			 * @param object $field                     The current field object.
			 */
			$max_choices_count = gf_apply_filters( array( 'kdnaform_field_choices_max_count_visible', $form_id ), $max_choices, $this );

			$tag = KDNACommon::is_legacy_markup_enabled( $form_id ) ? 'li' : 'div';

			foreach ( $field_choices as $choice ) {
				if ( rgar( $choice, 'isOtherChoice' ) ) {
					if ( ! $needs_other_choice ) {
						continue;
					}
					$needs_other_choice = false;
				}

				$choices .= $this->get_choice_html( $choice, $choice_id, $value, $disabled_text, $is_admin );

				if ( $is_form_editor && $count >= $max_choices_count ) {
					$editor_limited = true;
					break;
				}

				$count ++;
			}

			if ( $needs_other_choice ) {
				$other_choice    = array(
					'text'          => KDNACommon::get_other_choice_value( $this ),
					'value'         => 'gf_other_choice',
					'isSelected'    => false,
					'isOtherChoice' => true,
				);
				$field_choices[] = $other_choice;

				if ( ! $is_form_editor || ! $editor_limited ) {
					$choices .= $this->get_choice_html( $other_choice, $choice_id, $value, $disabled_text, $is_admin );
					$count ++;
				}
			}

			$total = sizeof( $field_choices );
			if ( $is_form_editor && ( $count < $total ) ) {
				$choices .= "<{$tag} class='kdnachoice_total'><span>" . sprintf( esc_html__( '%d of %d items shown. Edit choices to view all.', 'kdnaforms' ), $count, $total ) . "</span></{$tag}>";
			}
		}

		/**
		 * Allows the HTML for multiple choices to be overridden.
		 *
		 * @since unknown
		 *
		 * @param string $choices The choices HTML.
		 * @param object $field   The current field object.
		 */
		return gf_apply_filters( array( 'kdnaform_field_choices', $this->formId ), $choices, $this );
	}

	/**
	* Determine if we should add the aria description to a radio input.
	*
	* @since 2.5
	*
	* @param string $checked      The checked attribute or a blank string.
	* @param int    $choice_id    The choice number.
	*
	* @return string
	*/
	public function add_aria_description( $checked, $choice_id ) {

		// Determine if any choices are pre-selected.
		foreach ( $this['choices'] as $choice ) {
			$is_any_selected = rgar( $choice, 'isSelected' );
			if ( $is_any_selected ) {
				break;
			}
		}

		// Return true if any choices are pre-selected, or if no choices are pre-selected and this is the first choice.
		return ( ! $is_any_selected && $choice_id === 1 ) || $checked;

	}

	/**
	 * Returns the choice HTML.
	 *
	 * @since 2.4.17
	 * @since 2.7 Added `kdnachoice_other_control` class to Other choice text input.
	 *
	 * @param array  $choice        The choice properties.
	 * @param int    &$choice_id    The choice number.
	 * @param string $value         The current field value.
	 * @param string $disabled_text The disabled attribute or an empty string.
	 * @param bool   $is_admin      Indicates if this is the form editor or entry detail page.
	 *
	 * @return string
	 */
	public function get_choice_html( $choice, &$choice_id, $value, $disabled_text, $is_admin ) {
		$form_id = absint( $this->formId );

		if ( KDNACommon::is_legacy_markup_enabled( $form_id ) ) {
			return $this->get_legacy_choice_html( $choice, $choice_id, $value, $disabled_text, $is_admin );
		}

		if ( $is_admin || $form_id == 0 ) {
			$id = $this->id . '_' . $choice_id ++;
		} else {
			$id = $form_id . '_' . $this->id . '_' . $choice_id ++;
		}

		$field_value = ! empty( $choice['value'] ) || $this->enableChoiceValue ? $choice['value'] : $choice['text'];

		if ( $this->enablePrice ) {
			$price       = rgempty( 'price', $choice ) ? 0 : KDNACommon::to_number( rgar( $choice, 'price' ) );
			$field_value .= '|' . $price;
		}

		if ( rgblank( $value ) && rgget( 'view' ) != 'entry' ) {
			$checked = rgar( $choice, 'isSelected' ) ? "checked='checked'" : '';
		} else {
			$checked = KDNAFormsModel::choice_value_match( $this, $choice, $value ) ? "checked='checked'" : '';
		}

		$aria_describedby = $this->add_aria_description( $checked, $choice_id ) ? $this->get_aria_describedby() : '';

		$tabindex = $this->get_tabindex();
		$label    = sprintf( "<label for='choice_%s' id='label_%s' class='kdnaform-field-label kdnaform-field-label--type-inline'>%s</label>", $id, $id, $choice['text'] );

		// Handle 'other' choice.
		if ( $this->enableOtherChoice && rgar( $choice, 'isOtherChoice' ) ) {
			$input_disabled_text = $disabled_text;

			if ( $value == 'gf_other_choice' && rgpost( "input_{$this->id}_other" ) ) {
				$other_value = rgpost( "input_{$this->id}_other" );
			} elseif ( ! empty( $value ) && ! KDNAFormsModel::choices_value_match( $this, $this->choices, $value ) ) {
				$other_value = $value;
				$value       = 'gf_other_choice';
				$checked     = "checked='checked'";
			} else {
				if ( ! $input_disabled_text ) {
					$input_disabled_text = "disabled='disabled'";
				}
				$other_value = empty( $choice['text'] ) ? KDNACommon::get_other_choice_value( $this ) : $choice['text'];
			}

			$label .= "<br /><input id='input_{$this->formId}_{$this->id}_other' class='kdnachoice_other_control' name='input_{$this->id}_other' type='text' value='" . esc_attr( $other_value ) . "' aria-label='" . esc_attr__( 'Other Choice, please specify', 'kdnaforms' ) . "' $tabindex $input_disabled_text />";
		}

		$choice_markup = sprintf( "
			<div class='kdnachoice kdnachoice_$id'>
					<input class='kdnafield-choice-input' name='input_%d' type='radio' value='%s' %s id='choice_%s' onchange='gformToggleRadioOther( this )' %s $tabindex %s />
					%s
			</div>",
			$this->id, esc_attr( $field_value ), $checked, $id, $aria_describedby, $disabled_text, $label
		);

		/**
		 * Allows the HTML for a specific choice to be overridden.
		 *
		 * @since 1.9.6
		 * @since 1.9.12 Added the field specific version.
		 * @since 2.4.17 Moved from KDNA_Field_Radio::get_radio_choices().
		 *
		 * @param string         $choice_markup The choice HTML.
		 * @param array          $choice        The choice properties.
		 * @param KDNA_Field_Radio $field         The current field object.
		 * @param string         $value         The current field value.
		 */
		return gf_apply_filters( array( 'kdnaform_field_choice_markup_pre_render', $this->formId, $this->id ), $choice_markup, $choice, $this, $value );
	}

	/**
	 * Returns the choice HTML.
	 *
	 * @since 2.5
	 *
	 * @param array  $choice        The choice properties.
	 * @param int    &$choice_id    The choice number.
	 * @param string $value         The current field value.
	 * @param string $disabled_text The disabled attribute or an empty string.
	 * @param bool   $is_admin      Indicates if this is the form editor or entry detail page.
	 *
	 * @return string
	 */
	public function get_legacy_choice_html( $choice, &$choice_id, $value, $disabled_text, $is_admin ) {
		$form_id = absint( $this->formId );

		if ( $is_admin || $form_id == 0 ) {
			$id = $this->id . '_' . $choice_id ++;
		} else {
			$id = $form_id . '_' . $this->id . '_' . $choice_id ++;
		}

		$field_value = ! empty( $choice['value'] ) || $this->enableChoiceValue ? $choice['value'] : $choice['text'];

		if ( $this->enablePrice ) {
			$price       = rgempty( 'price', $choice ) ? 0 : KDNACommon::to_number( rgar( $choice, 'price' ) );
			$field_value .= '|' . $price;
		}

		if ( rgblank( $value ) && rgget( 'view' ) != 'entry' ) {
			$checked = rgar( $choice, 'isSelected' ) ? "checked='checked'" : '';
		} else {
			$checked = KDNAFormsModel::choice_value_match( $this, $choice, $value ) ? "checked='checked'" : '';
		}

		$tabindex    = $this->get_tabindex();
		$label       = sprintf( "<label for='choice_%s' id='label_%s' class='kdnaform-field-label kdnaform-field-label--type-inline'>%s</label>", $id, $id, $choice['text'] );
		$input_focus = '';

		// Handle 'other' choice.
		if ( $this->enableOtherChoice && rgar( $choice, 'isOtherChoice' ) ) {
			$other_default_value = empty( $choice['text'] ) ? KDNACommon::get_other_choice_value( $this ) : $choice['text'];

			$onfocus = ! $is_admin ? 'jQuery(this).prev("input")[0].click(); if(jQuery(this).val() == "' . $other_default_value . '") { jQuery(this).val(""); }' : '';
			$onblur  = ! $is_admin ? 'if(jQuery(this).val().replace(" ", "") == "") { jQuery(this).val("' . $other_default_value . '"); }' : '';

			$input_focus  = ! $is_admin ? "onfocus=\"jQuery(this).next('input').focus();\"" : '';
			$value_exists = KDNAFormsModel::choices_value_match( $this, $this->choices, $value );

			if ( $value == 'gf_other_choice' && rgpost( "input_{$this->id}_other" ) ) {
				$other_value = rgpost( "input_{$this->id}_other" );
			} elseif ( ! $value_exists && ! empty( $value ) ) {
				$other_value = $value;
				$value       = 'gf_other_choice';
				$checked     = "checked='checked'";
			} else {
				$other_value = $other_default_value;
			}

			$label = "<input class='small' id='input_{$this->formId}_{$this->id}_other' name='input_{$this->id}_other' type='text' value='" . esc_attr( $other_value ) . "' aria-label='" . esc_attr__( 'Other', 'kdnaforms' ) . "' onfocus='$onfocus' onblur='$onblur' $tabindex $disabled_text />";
		}

		$choice_markup = sprintf( "
			<li class='kdnachoice kdnachoice_$id'>
				<input name='input_%d' type='radio' value='%s' %s id='choice_%s' $tabindex %s %s />
				%s
			</li>",
			$this->id, esc_attr( $field_value ), $checked, $id, $disabled_text, $input_focus, $label
		);

		/**
		 * Allows the HTML for a specific choice to be overridden.
		 *
		 * @since 1.9.6
		 * @since 1.9.12 Added the field specific version.
		 * @since 2.4.17 Moved from KDNA_Field_Radio::get_radio_choices().
		 *
		 * @param string         $choice_markup The choice HTML.
		 * @param array          $choice        The choice properties.
		 * @param KDNA_Field_Radio $field         The current field object.
		 * @param string         $value         The current field value.
		 */
		return gf_apply_filters( array( 'kdnaform_field_choice_markup_pre_render', $this->formId, $this->id ), $choice_markup, $choice, $this, $value );
	}

	public function get_value_default() {
		return $this->is_form_editor() ? $this->defaultValue : KDNACommon::replace_variables_prepopulate( $this->defaultValue );
	}

	public function get_value_submission( $field_values, $get_from_post_global_var = true ) {

		$value = $this->get_input_value_submission( 'input_' . $this->id, $this->inputName, $field_values, $get_from_post_global_var );
		if ( $value == 'gf_other_choice' ) {
			//get value from text box
			$value = $this->get_input_value_submission( 'input_' . $this->id . '_other', $this->inputName, $field_values, $get_from_post_global_var );
		}

		return $value;
	}

	public function get_value_entry_list( $value, $entry, $field_id, $columns, $form ) {
		return $this->get_selected_choice_output( $value, rgar( $entry, 'currency' ) );
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
		return $this->get_selected_choice_output( $value, rgar( $entry, 'currency' ), $use_text );
	}

	/**
	 * Gets merge tag values.
	 *
	 * @since  Unknown
	 * @access public
	 *
	 * @uses KDNACommon::to_money()
	 * @uses KDNACommon::format_post_category()
	 * @uses KDNAFormsModel::is_field_hidden()
	 * @uses KDNAFormsModel::get_choice_text()
	 * @uses KDNACommon::format_variable_value()
	 * @uses KDNACommon::implode_non_blank()
	 *
	 * @param array|string $value      The value of the input.
	 * @param string       $input_id   The input ID to use.
	 * @param array        $entry      The Entry Object.
	 * @param array        $form       The Form Object
	 * @param string       $modifier   The modifier passed.
	 * @param array|string $raw_value  The raw value of the input.
	 * @param bool         $url_encode If the result should be URL encoded.
	 * @param bool         $esc_html   If the HTML should be escaped.
	 * @param string       $format     The format that the value should be.
	 * @param bool         $nl2br      If the nl2br function should be used.
	 *
	 * @return string The processed merge tag.
	 */
	public function get_value_merge_tag( $value, $input_id, $entry, $form, $modifier, $raw_value, $url_encode, $esc_html, $format, $nl2br ) {
		$modifiers       = $this->get_modifiers();
		$use_value       = in_array( 'value', $modifiers );
		$format_currency = ! $use_value && in_array( 'currency', $modifiers );
		$use_price       = $format_currency || ( ! $use_value && in_array( 'price', $modifiers ) );
		$image_url 	     = in_array( 'img_url', $modifiers );

		if ( is_array( $raw_value ) && (string) intval( $input_id ) != $input_id ) {
			$items = array( $input_id => $value ); // Float input Ids. (i.e. 4.1 ). Used when targeting specific checkbox items.
		} elseif ( is_array( $raw_value ) ) {
			$items = $raw_value;
		} else {
			$items = array( $input_id => $raw_value );
		}

		$ary = array();

		foreach ( $items as $input_id => $item ) {
			switch (true) {
				case $use_value:
					list( $val, $price ) = rgexplode( '|', $item, 2, true );
					break;

				case $use_price:
					list( $name, $val ) = rgexplode( '|', $item, 2, true );
					if ( $format_currency ) {
						$val = KDNACommon::to_money( $val, rgar( $entry, 'currency' ) );
					}
					break;

				case $image_url:
					$image_choice = new KDNA_Field_Image_Choice( $this );
					$val = $image_choice->get_merge_tag_img_url( $raw_value, $input_id, $entry, $form, $this );
					break;

				case $this->type == 'post_category':
					$use_id     = strtolower( $modifier ) == 'id';
					$item_value = KDNACommon::format_post_category( $item, $use_id );
					$val = KDNAFormsModel::is_field_hidden( $form, $this, array(), $entry ) ? '' : $item_value;
					break;

				default:
					$val = KDNAFormsModel::is_field_hidden( $form, $this, array(), $entry ) ? '' : KDNAFormsModel::get_choice_text( $this, $raw_value, $input_id );
					break;
			}

			$ary[] = KDNACommon::format_variable_value( $val, $url_encode, $esc_html, $format );
		}

		return KDNACommon::implode_non_blank( ', ', $ary );
	}

	public function get_value_save_entry( $value, $form, $input_name, $lead_id, $lead ) {

		if ( $this->enableOtherChoice && $value == 'gf_other_choice' ) {
			$value = rgpost( "input_{$this->id}_other" );
		}

		$value = $this->sanitize_entry_value( $value, $form['id'] );

		return $value;
	}

	public function allow_html() {
		return true;
	}

	public function get_value_export( $entry, $input_id = '', $use_text = false, $is_csv = false ) {
		if ( empty( $input_id ) ) {
			$input_id = $this->id;
		}

		$value = rgar( $entry, $input_id );

		return $is_csv ? $value : KDNACommon::selection_display( $value, $this, rgar( $entry, 'currency' ), $use_text );
	}

	/**
	 * Strip scripts and some HTML tags.
	 *
	 * @param string $value The field value to be processed.
	 * @param int $form_id The ID of the form currently being processed.
	 *
	 * @return string
	 */
	public function sanitize_entry_value( $value, $form_id ) {

		if ( is_array( $value ) ) {
			return '';
		}

		$allowable_tags = $this->get_allowable_tags( $form_id );

		if ( $allowable_tags !== true ) {
			$value = strip_tags( $value, $allowable_tags );
		}

		$allowed_protocols = wp_allowed_protocols();
		$value             = wp_kses_no_null( $value, array( 'slash_zero' => 'keep' ) );
		$value             = wp_kses_hook( $value, 'post', $allowed_protocols );
		$value             = wp_kses_split( $value, 'post', $allowed_protocols );

		return $value;
	}

	// # FIELD FILTER UI HELPERS ---------------------------------------------------------------------------------------

	/**
	 * Returns the filter operators for the current field.
	 *
	 * @since 2.4
	 *
	 * @return array
	 */
	public function get_filter_operators() {
		$operators = $this->type == 'product' ? array( 'is' ) : array( 'is', 'isnot', '>', '<' );

		return $operators;
	}

	/**
	 * Override to return null instead of the array of inputs in case this is a choice field.
	 *
	 * @since 2.9
	 *
	 * @return array|null
	 */
	public function get_entry_inputs() {
		return null;
	}

}

KDNA_Fields::register( new KDNA_Field_Radio() );
