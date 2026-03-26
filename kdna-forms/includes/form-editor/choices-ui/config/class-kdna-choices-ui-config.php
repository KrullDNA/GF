<?php

namespace KDNA_Forms\KDNA_Forms\Form_Editor\Choices_UI\Config;

use KDNA_Forms\KDNA_Forms\Config\KDNA_Config;

/**
 * Data config items for the Choices UI.
 *
 * @since 2.6
 */
class GF_Choices_UI_Config extends KDNA_Config {

	protected $name               = 'kdnaform_admin_config';
	protected $script_to_localize = 'kdnaform_kdnaforms_admin_vendors';


	public function should_enqueue() {
		return \KDNACommon::is_form_editor();
	}

	/**
	 * Config data.
	 *
	 * @return array[]
	 */
	public function data() {
		return array(
			'form_editor' => array(
				'choices_ui' => array(
					'data' => array(),
				),
			),
		);
	}

}
