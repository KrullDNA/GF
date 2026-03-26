<?php

namespace KDNA_Forms\KDNA_Forms\Form_Editor\Choices_UI\Config;

use KDNA_Forms\KDNA_Forms\Config\KDNA_Config;

/**
 * I18N items for the Choices UI.
 *
 * @since 2.6
 */
class GF_Dialog_Config_I18N extends KDNA_Config {

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
			'components' => array(
				'dialog' => array(
					'i18n' => array(
						'cancel' => esc_html__( 'Cancel', 'kdnaforms' ),
						'close'  => esc_html__( 'Close', 'kdnaforms' ),
						'ok'     => esc_html__( 'OK', 'kdnaforms' ),
					),
				),
			),
		);
	}

}
