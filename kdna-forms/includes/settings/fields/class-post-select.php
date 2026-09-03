<?php

namespace KDNA_Forms\KDNA_Forms\Settings\Fields;

use KDNA_Forms\KDNA_Forms\Settings\Fields;

defined( 'ABSPATH' ) || die();

class Post_Select extends Select {

	/**
	 * Field type.
	 *
	 * @since 2.6.2
	 *
	 * @var string
	 */
	public $type = 'post_select';

	/**
	 * Post type.
	 *
	 * @since 2.6.2
	 *
	 * @var string
	 */
	public $post_type = 'page';


	// # RENDER METHODS ------------------------------------------------------------------------------------------------

	/**
	 * Render field.
	 *
	 * @since 2.6.2
	 *
	 * @return string
	 */
	public function markup() {

		// Display description.
		$html = $this->get_description();

		$html .= '<span class="' . esc_attr( $this->get_container_classes() ) . '">';

		// Get post type details.
		$post_type = get_post_type_object( $this->post_type );

		if ( ! $post_type ) {

			$html .= esc_html( sprintf( __( 'The requested post type %s does not exist.', 'kdnaforms' ), $this->post_type ) );

		} else {
			$post_singular = $post_type->labels->singular_name;
			$post_plural   = $post_type->labels->name;

			$html .= sprintf(
				'<article class="kform-dropdown" data-js="kform-settings-field-select" data-post-type="%1$s">
				    <span class="kform-visually-hidden" id="gform-%2$s-label">
						%3$s
				    </span>

				    <button
						type="button"
						aria-expanded="false"
						aria-haspopup="listbox"
						aria-labelledby="gform-%2$s-label gform-%2$s-control"
						class="kform-dropdown__control %6$s"
						data-js="kform-dropdown-control"
						id="gform-%2$s-control"
				    >
						<span
							class="kform-dropdown__control-text"
							data-js="kform-dropdown-control-text"
						>
				            %3$s
				        </span>
						<span class="kform-spinner kform-dropdown__spinner"></span>
						<span class="kform-icon kform-icon--chevron kform-dropdown__chevron"></span>
				    </button>
				    <div
						aria-labelledby="gform-%2$s-label"
						class="kform-dropdown__container"
						role="listbox"
						data-js="kform-dropdown-container"
						tabindex="-1"
				    >
						<div class="kform-dropdown__search">
							<label for="kform-settings-field__%2$s-search" class="kform-visually-hidden">
								%4$s
							</label>
							<input
								id="kform-settings-field__%2$s-search"
								type="text"
								class="kform-input kform-dropdown__search-input"
								placeholder="%4$s"
								data-js="kform-dropdown-search"
							/>
							<span class="kform-icon kform-icon--search kform-dropdown__search-icon"></span>
						</div>

						<div class="kform-dropdown__list-container">
				        <ul class="kform-dropdown__list" data-js="kform-dropdown-list"></ul>
				      </div>
				    </div>
				    <input type="hidden" data-js="kdna-post-select-input" name="_kdnaform_setting_%2$s" id="%2$s" value="%5$s"/>
				</article>',
				$this->post_type,
				esc_attr( $this->name ), // field name, used in HTML attributes
				esc_html( $this->get_dropdown_label( $post_singular ) ), // form switcher label
				esc_html( $this->get_search_label( $post_plural ) ), // label for search field
				esc_attr( $this->get_value() ),
				empty( $this->get_value() ) ? 'kform-dropdown__control--placeholder' : ''
			);

		}

		// If field failed validation, add error icon.
		$html .= $this->get_error_icon();

		$html .= '</span>';

		return $html;

	}

	/**
	 * Get the label for the dropdown.
	 *
	 * @since 2.6.2
	 *
	 * @param string $singular Post type name (singular)
	 *
	 * @return string
	 */
	public function get_dropdown_label( $singular ) {
		if ( empty( $this->get_value() ) ) {
			// Translators: singular post type name (e.g. 'post').
			return sprintf( __( 'Select a %s', 'kdnaforms' ), $singular );
		}

		$post_id = $this->get_value();

		return get_the_title( $post_id );
	}

	/**
	 * Get the label for the search field.
	 *
	 * @since 2.6.2
	 *
	 * @param string $plural Post type name (plural)
	 *
	 * @return string
	 */
	public function get_search_label( $plural ) {
		// Translators: plural post type name (e.g. 'post's).
		return sprintf( __( 'Search all %s', 'kdnaforms' ), $plural );
	}


}

Fields::register( 'post_select', '\KDNA_Forms\KDNA_Forms\Settings\Fields\Post_Select' );
