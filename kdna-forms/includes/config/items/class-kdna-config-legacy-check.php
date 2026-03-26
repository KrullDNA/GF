<?php

namespace KDNA_Forms\KDNA_Forms\Config\Items;

use KDNA_Forms\KDNA_Forms\Config\KDNA_Config;
use KDNA_Forms\KDNA_Forms\Config\KDNA_Config_Collection;
use KDNA_Forms\KDNA_Forms\Config\KDNA_Configurator;

/**
 * Config items for Theme Legacy Checks.
 *
 * @since 2.6
 */
class KDNA_Config_Legacy_Check extends KDNA_Config {

	protected $name               = 'gf_legacy';
	protected $script_to_localize = 'kdnaform_layout_editor';

	/**
	 * Determine if the config should enqueue its data.
	 *
	 * @since 2.7
	 *
	 * @return bool
	 */
	public function should_enqueue() {
		return \KDNACommon::is_form_editor();
	}

	/**
	 * Config data.
	 *
	 * @return array[]
	 */
	public function data() {
		$form = \KDNAFormsModel::get_form_meta( rgget( 'id' ) );

		return array(
			'is_legacy' => array(
				'value'   => \KDNACommon::is_legacy_markup_enabled( $form ),
				'default' => 0,
			),
		);
	}
}