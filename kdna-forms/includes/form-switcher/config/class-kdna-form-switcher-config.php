<?php

namespace KDNA_Forms\KDNA_Forms\Form_Switcher\Config;

use KDNA_Forms\KDNA_Forms\Config\KDNA_Config;
use KDNAForms;

/**
 * Config items for Form_Switcher.
 *
 * @since 2.9.6
 */
class KDNA_Form_Switcher_Config extends KDNA_Config {

	protected $name               = 'kdnaform_admin_config';
	protected $script_to_localize = 'kdnaform_kdnaforms_admin_vendors';

	/**
	 * Only enqueue in the admin.
	 *
	 * @since 2.9.6
	 *
	 * @return bool
	 */
	public function should_enqueue() {
		return KDNAForms::is_kdna_page();
	}

	/**
	 * Config data.
	 *
	 * @since 2.9.6
	 *
	 * @return array[]
	 */
	public function data() {
		return [
			'components' => [
				'form_switcher' => [
					'endpoints' => $this->get_endpoints(),
				],
			],
		];
	}

	/**
	 * Get the endpoints for the Form Switcher.
	 *
	 * @since 2.9.6
	 *
	 * @return array
	 */
	public function get_endpoints() {
		return [
			'get_forms' => [
				'action' => [
					'value'   => 'kdna_form_switcher_get_forms',
					'default' => 'mock_endpoint',
				],
				'nonce' => [
					'value'   => wp_create_nonce( 'kdna_form_switcher_get_forms' ),
					'default' => 'nonce',
				],
			],

		];
	}

}
