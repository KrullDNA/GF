<?php

namespace KDNA_Forms\KDNA_Forms\Config\Items;

use KDNA_Forms\KDNA_Forms\Config\KDNA_Config;
use KDNA_Forms\KDNA_Forms\Config\KDNA_Config_Collection;
use KDNA_Forms\KDNA_Forms\Config\KDNA_Configurator;

/**
 * Config items for Multi Legacy Check (mostly just data from a filter).
 *
 * @since 2.6
 */
class KDNA_Config_Legacy_Check_Multi extends KDNA_Config {

	protected $name               = 'gf_legacy_multi';
	protected $script_to_localize = 'kdnaform_kdnaforms';

	/**
	 * Config data.
	 *
	 * @return array[]
	 */
	public function data() {
		/**
		 * Allows users to filter the legacy checks for any form on the page.
		 *
		 * @since 2.5
		 *
		 * @param array
		 */
		return apply_filters( 'kdnaform_gf_legacy_multi', array() );
	}
}