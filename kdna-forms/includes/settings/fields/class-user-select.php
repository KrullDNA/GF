<?php

namespace KDNA_Forms\KDNA_Forms\Settings\Fields;

use KDNA_Forms\KDNA_Forms\Settings\Fields;

defined( 'ABSPATH' ) || die();

class User_Select extends Select {

	/**
	 * Field type.
	 *
	 * @since 2.9.5
	 *
	 * @var string
	 */
	public $type = 'user_select';


	// # RENDER METHODS ------------------------------------------------------------------------------------------------

	/**
	 * Render field.
	 *
	 * @since 2.9.5
	 *
	 * @return string
	 */
	public function markup() {

		// Display description.
		$html = $this->get_description();

		$html .= '<span class="' . esc_attr( $this->get_container_classes() ) . '">';

		$html .= sprintf(
			'<article class="kform-dropdown" data-js="kform-settings-field-user-select">
			    <span class="kform-visually-hidden" id="kform-%1$s-label">
					%2$s
			    </span>
			
			    <button
					type="button"
					aria-expanded="false"
					aria-haspopup="listbox"
					aria-labelledby="kform-%1$s-label kform-%1$s-control"
					class="kform-dropdown__control %1$s"
					data-js="kform-dropdown-control"
					id="kform-%1$s-control"
			    >
					<span
						class="kform-dropdown__control-text"
						data-js="kform-dropdown-control-text"
					>
			            %2$s
			        </span>
					<span class="kform-spinner kform-dropdown__spinner"></span>
					<span class="kform-icon kform-icon--chevron kform-dropdown__chevron"></span>
			    </button>
			    <div
					aria-labelledby="kform-%1$s-label"
					class="kform-dropdown__container"
					role="listbox"
					data-js="kform-dropdown-container"
					tabindex="-1"
			    >
					<div class="kform-dropdown__search">
						<label for="kform-settings-field__%1$s-search" class="kform-visually-hidden">
							%3$s
						</label>
						<input
							id="kform-settings-field__%1$s-search"
							type="text"
							class="kform-input kform-dropdown__search-input"
							placeholder="%2$s"
							data-js="kform-dropdown-search"
						/>
						<span class="kform-icon kform-icon--search kform-dropdown__search-icon"></span>
					</div>
			
			      <div class="kform-dropdown__list-container">
			        <ul class="kform-dropdown__list" data-js="kform-dropdown-list"></ul>
			      </div>
			    </div>
			    <input type="hidden" data-js="kdna-user-select-input" name="_kdnaform_setting_%1$s" id="%1$s" value="%4$s"/>
			</article>',
			esc_attr( $this->name ), // field name, used in HTML attributes
			esc_html( $this->get_dropdown_label() ), // form switcher label
			esc_html__( 'Search users', 'kdnaforms' ), // label for search field
			esc_attr( $this->get_value() )
		);


		// If field failed validation, add error icon.
		$html .= $this->get_error_icon();

		$html .= '</span>';

		return $html;

	}

	/**
	 * Get the label for the dropdown.
	 *
	 * @since 2.9.5
	 *
	 * @return string
	 */
	public function get_dropdown_label() {
		if ( empty( $this->get_value() ) ) {
			return __( 'Select a user', 'kdnaforms' );
		}

		if ( 'logged-in-user' === $this->get_value() ) {
			return __( 'Logged In User', 'kdnaforms' );
		}

		$user_id = $this->get_value();
		$user = get_user_by( 'id', $user_id );

		return esc_attr( $user->display_name );
	}

}

Fields::register( 'user_select', '\KDNA_Forms\KDNA_Forms\Settings\Fields\User_Select' );
