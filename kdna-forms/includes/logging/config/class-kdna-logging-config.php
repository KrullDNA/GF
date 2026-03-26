<?php

namespace KDNA_Forms\KDNA_Forms\Logging\Config;

use KDNA_Forms\KDNA_Forms\Config\KDNA_Config;

/**
 * Config items for client side logger
 *
 * @since 2.9.26
 */
class GF_Logging_Config extends KDNA_Config {

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
					'logging' => array(
						'is_enabled' => \KDNALogging::is_enabled( 'kdnaforms-browser' ),
					),
				),
			),
		);
	}
}
