<?php

namespace KDNA_Forms\KDNA_Forms\Form_Editor\Save_Form\Config;

use KDNA_Forms\KDNA_Forms\Config\KDNA_Config;
use KDNA_Forms\KDNA_Forms\Save_Form\Config\GF_Admin_Form_Save_Config;
use KDNA_Forms\KDNA_Forms\Form_Editor\Save_Form\Endpoints\KDNA_Save_Form_Endpoint_Form_Editor;

class KDNA_Form_Editor_Form_Save_Config extends GF_Admin_Form_Save_Config {

	protected $name = 'gform_admin_config';

	protected $script_to_localize = 'kdnaform_kdnaforms_admin_vendors';

	public function data() {

		return array(
			'form_editor_save_form' => array(
				'data'      => array(
					'selectors'        => $this->get_selectors(),
					'domEvents'        => $this->get_dom_events(),
					'animationDelay'   => '1000',
					'json_containers'  => array(
						GF_Admin_Form_Save_Config::JSON_START_STRING,
						GF_Admin_Form_Save_Config::JSON_END_STRING,
					),
					'urls'             => array(
						'formPreview' => trailingslashit( site_url() ) . '?gf_page=preview&id=%s',
					),
					'registeredAddons' => \KDNAAddOn::get_registered_addons(),
				),
				'endpoints' => $this->get_endpoints(),
				'i18n'      => array(
					'formUpdated'                      => __( 'Form Updated', 'kdnaforms' ),
					'viewForm'                         => __( 'View Form', 'kdnaforms' ),
					'genericError'                     => __( 'An error occurred while saving the form.', 'kdnaforms' ),
					'networkError'                     => __( 'Request failed due to a network error. Please check your internet connection.', 'kdnaforms' ),
					'genericSuccess'                   => __( 'Form was updated successfully.', 'kdnaforms' ),
					'saveInProgress'                   => __( 'Saving', 'kdnaforms' ),
					'saveForm'                         => __( 'Save Form', 'kdnaforms' ),
					'saved'                            => __( 'Saved', 'kdnaforms' ),
					'ajaxErrorDialogCancelButtonText'  => __( 'Cancel', 'kdnaforms' ),
					'ajaxErrorDialogCloseButtonTitle'  => __( 'Close', 'kdnaforms' ),
					'ajaxErrorDialogConfirmButtonText' => __( 'Save', 'kdnaforms' ),
					'ajaxErrorDialogContent'           => __( 'There was an error saving your form. To avoid losing your work, click the Save button to save your form and reload the page.' ),
					'ajaxErrorDialogTitle'             => __( 'Save Error.', 'kdnaforms' ),
				),
			),
		);
	}

	/**
	 * Gets the selectors for the UI elements in the form editor.
	 *
	 * @since 2.6
	 *
	 * @return array
	 */
	private function get_selectors() {
		return array(
			'successNotification'  => '.gf_editor_status',
			'failureNotification'  => '.gf_editor_error',
			'saveInProgress'       => '.save-in-progress',
			'stateElements'        => array(
				'.update-form-ajax',
			),
			'saveAnimationButtons' => array(
				'.update-form-ajax',
			),
		);
	}

	/**
	 * Gets the endpoints for saving the form in the form editor.
	 *
	 * @since 2.6
	 *
	 * @return \array[][]
	 */
	private function get_endpoints() {
		return array(
			'form_editor_save_form' => array(
				'action' => array(
					'value'   => KDNA_Save_Form_Endpoint_Form_Editor::ACTION_NAME,
					'default' => 'mock_endpoint',
				),
				'nonce'  => array(
					'value'   => wp_create_nonce( KDNA_Save_Form_Endpoint_Form_Editor::ACTION_NAME ),
					'default' => 'nonce',
				),
			),
		);
	}

	/**
	 * The Dom Events in the form editor and what events they should trigger.
	 *
	 * @since 2.6
	 *
	 * @return \string[][]
	 */
	private function get_dom_events() {
		return array(
			array(
				'name'            => 'SaveRequested',
				'action'          => 'click',
				'elementSelector' => '.update-form-ajax',
			),
			array(
				'name'            => 'SaveRequested',
				'action'          => 'keydown',
				'elementSelector' => 'document',
				'keys'            => array(
					83,
					17,
					16,
				),
			),
		);
	}

}
