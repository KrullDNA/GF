<?php

namespace KDNA_Forms\KDNA_Forms\Honeypot\Config;

use KDNA_Forms\KDNA_Forms\Config\KDNA_Config;
use KDNAForms;

/**
 * Config items for the Honeypot Field
 *
 * @since 2.6
 */
class KDNA_Honeypot_Config extends KDNA_Config {

	protected $name               = 'kdnaform_theme_config';
	protected $script_to_localize = 'kdnaform_kdnaforms_theme';

	/**
	 * Config data.
	 *
	 * @return array[]
	 */
	public function data() {
		return array(
			'common' => array(
				'form' => array(
					'honeypot' => array(
						'version_hash' => wp_hash( KDNAForms::$version ),
					),
				),
			),
		);
	}
}
