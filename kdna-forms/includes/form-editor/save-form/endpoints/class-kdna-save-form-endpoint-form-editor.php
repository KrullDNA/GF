<?php

namespace KDNA_Forms\KDNA_Forms\Form_Editor\Save_Form\Endpoints;

use KDNA_Forms\KDNA_Forms\Save_Form\KDNA_Form_CRUD_Handler;
use KDNA_Forms\KDNA_Forms\Save_Form\KDNA_Save_Form_Service_Provider;
use KDNA_Forms\KDNA_Forms\Save_Form\Endpoints\KDNA_Save_Form_Endpoint_Admin;
use KDNA_Forms\KDNA_Forms\Form_Editor\KDNA_Form_Editor_Service_Provider;
use KDNA_Forms\KDNA_Forms\Form_Editor\Renderer\KDNA_Form_Editor_Renderer;
use KDNA_Forms\KDNA_Forms\Util\KDNA_Util_Service_Provider;
/**
 * AJAX Endpoint for Saving the form in the main form editor.
 *
 * @since 2.6
 *
 * @package KDNA_Forms\KDNA_Forms\Save_Form\Endpoints
 */
class KDNA_Save_Form_Endpoint_Form_Editor extends KDNA_Save_Form_Endpoint_Admin {

	// AJAX action name.
	const ACTION_NAME = 'form_editor_save_form';

	/**
	 * Handles a successful operation and returns the desired response.
	 *
	 * @since 2.6
	 *
	 * @param array $result The result of the operation.
	 *
	 * @return mixed
	 */
	protected function get_success_status_response( $result ) {

		$kdna_forms                 = $this->kdna_forms;
		$editor_renderer          = $kdna_forms::get_service_container()->get( KDNA_Form_Editor_Service_Provider::FORM_EDITOR_RENDERER );
		$form_detail              = $kdna_forms::get_service_container()->get( KDNA_Util_Service_Provider::GF_FORM_DETAIL );
		$result['updated_markup'] = $editor_renderer::render_form_editor( $this->form_id, $form_detail );

		return  parent::get_success_status_response( $result );

	}


}
