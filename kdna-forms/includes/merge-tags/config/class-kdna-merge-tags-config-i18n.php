<?php

namespace KDNA_Forms\KDNA_Forms\Merge_Tags\Config;

use KDNA_Forms\KDNA_Forms\Config\KDNA_Config;

/**
 * Config items for the Merge Tags I18N
 *
 * @since 2.6
 */
class KDNA_Merge_Tags_Config_I18N extends KDNA_Config {

	protected $name               = 'gform_admin_config';
	protected $script_to_localize = 'kdnaform_kdnaforms_admin_vendors';

	/**
	 * Config data.
	 *
	 * @return array[]
	 */
	public function data() {
		return array(
			'components' => array(
				'merge_tags' => array(
					'i18n' => array(
						'insert_merge_tags' => __( 'Insert Merge Tags', 'kdnaforms' ),
						'search_merge_tags' => __( 'Search Merge Tags', 'kdnaforms' ),
					),
				),
			),
		);
	}
}
