<?php

namespace KDNA_Forms\KDNA_Forms\Save_Form\Config;

use KDNA_Forms\KDNA_Forms\Config;
use KDNA_Forms\KDNA_Forms\Save_Form\Endpoints\KDNA_Save_Form_Endpoint_Admin;

class GF_Admin_Form_Save_Config extends Config\KDNA_Config {

	const JSON_START_STRING = 'GFORMS_SAVE_REQUEST_JSON_START';
	const JSON_END_STRING   = 'GFORMS_SAVE_REQUEST_JSON_END';

	/**
	 * The object name for this config.
	 *
	 * @since 2.6
	 *
	 * @var string
	 */
	protected $name = 'gform_admin_config';

	/**
	 * The ID of the script to localize the data to.
	 *
	 * @since 2.6
	 *
	 * @var string
	 */
	protected $script_to_localize = 'kdnaform_kdnaforms_admin_vendors';

	/**
	 * An instance of the KDNAForms class to use for calling common static functions.
	 *
	 * @since 2.6
	 *
	 * @var \KDNAForms
	 */
	protected $kdna_forms;

	/**
	 * An instance of the KDNAAPI class to use for calling static GForms API functions.
	 *
	 * @since 2.6
	 *
	 * @var \KDNAAPI
	 */
	protected $gf_api;

	/**
	 * GF_Admin_Form_Save_Config constructor.
	 *
	 * @since 2.6
	 *
	 * @param Config\KDNA_Config_Data_Parser $parser       Parses a given data array to return either Live or Mock values.
	 * @param array                        $dependencies Array of dependency objects.
	 */
	public function __construct( Config\KDNA_Config_Data_Parser $parser, $dependencies ) {
		$this->kdna_forms = $dependencies['kdna_forms'];
		$this->gf_api   = $dependencies['gf_api'];
		parent::__construct( $parser );
	}

	public function should_enqueue() {
		return \KDNAForms::is_kdna_page();
	}

	public function data() {
		$kdna_forms = $this->kdna_forms;
		return array(
			'admin_save_form' => array(
				'data'      => array(
					'is_form_editor'  => $kdna_forms::get_page() === 'form_editor',
					'is_quick_editor' => false,
					'form'            => $this->get_form(),
					'json_containers' => array(
						GF_Admin_Form_Save_Config::JSON_START_STRING,
						GF_Admin_Form_Save_Config::JSON_END_STRING,
					),
				),
				'endpoints' => $this->get_endpoints(),
			),
		);
	}

	/**
	 * Retrieves the form if an ID found in the the query parameters.
	 *
	 * @since 2.6
	 *
	 * @return false|array
	 */
	private function get_form() {
		$kdna_forms = $this->kdna_forms;
		$gf_api   = $this->gf_api;
		$form_id  = $kdna_forms::get_page() === 'form_editor' ? rgget( 'id' ) : rgget( 'form_id' );
		if ( $form_id ) {
			return $gf_api::get_form( $form_id );
		}

		return false;
	}

	/**
	 * Returns the endpoints for saving the form in the admin area.
	 *
	 * @since 2.6
	 *
	 * @return \array[][]
	 */
	private function get_endpoints() {
		return array(
			'admin_save_form' => array(
				'action' => array(
					'value'   => KDNA_Save_Form_Endpoint_Admin::ACTION_NAME,
					'default' => 'mock_endpoint',
				),
				'nonce'  => array(
					'value'   => wp_create_nonce( KDNA_Save_Form_Endpoint_Admin::ACTION_NAME ),
					'default' => 'nonce',
				),
			),
		);
	}
}
