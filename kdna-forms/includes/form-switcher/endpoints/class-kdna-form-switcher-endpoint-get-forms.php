<?php

namespace KDNA_Forms\KDNA_Forms\Form_Switcher\Endpoints;

use KDNAFormsModel;
use KDNAForms;

/**
 * AJAX Endpoint for getting a form based on a search query.
 *
 * @since   2.9.6
 *
 * @package KDNA_Forms\KDNA_Forms\Form_Switcher\Endpoints
 */
class KDNA_Form_Switcher_Endpoint_Get_Forms {

	// Strings
	const ACTION_NAME = 'kdna_form_switcher_get_forms';

	// Parameters
	const PARAM_SEARCH = 'search';

	// Defaults
	const DEFAULT_SEARCH = '';

	/**
	 * Handle the AJAX request.
	 *
	 * @since 2.9.6
	 *
	 * @return void
	 */
	public function handle() {
		check_ajax_referer( self::ACTION_NAME );

		$search = rgpost( self::PARAM_SEARCH ) ? rgpost( self::PARAM_SEARCH ) : self::DEFAULT_SEARCH;

		$forms = KDNAFormsModel::search_forms( $search );

		/* Filters the form switcher search results.
		 *
		 * @since 2.9.6
		 *
		 * @param array $forms The list of forms retrieved by the search.
		 */
		$forms = (array) apply_filters( 'kdnaform_form_switcher_forms', $forms );

		$results = array();

		foreach ( $forms as $form ) {
			if ( is_numeric( $form ) ) {
				$form = KDNAFormsModel::get_form( $form );
			}

			if ( ! is_object( $form ) ) {
				continue;
			}

			$results[] = [
				'value'      => $form->id,
				'label'      => $form->title,
				'attributes' => [
					KDNAForms::get_form_switcher_results_page_attr( $form->id ),
					KDNAForms::get_form_switcher_subview_attr( $form->id ),
					"title = '" . esc_attr( $form->title ) . "'",
				]
			];
		}

		wp_send_json_success( $results );
	}

}
