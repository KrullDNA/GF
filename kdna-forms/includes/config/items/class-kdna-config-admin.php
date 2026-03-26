<?php

namespace KDNA_Forms\KDNA_Forms\Config\Items;

use KDNA_Forms\KDNA_Forms\Config\KDNA_Config_Collection;
use KDNA_Forms\KDNA_Forms\Config\KDNA_Config;
use KDNA_Forms\KDNA_Forms\Config\KDNA_Configurator;

/**
 * Config items for Admin I18N
 *
 * @since 2.6
 */
class KDNA_Config_Admin extends KDNA_Config {

	protected $name               = 'gform_admin_config';
	protected $script_to_localize = 'kdnaform_kdnaforms_admin_vendors';

	/**
	 * Whether we should enqueue this data.
	 *
	 * @since 2.6
	 *
	 * @return bool|mixed
	 */
	public function should_enqueue() {
		return is_admin();
	}

	/**
	 * Config data.
	 *
	 * @return array[]
	 */
	public function data() {
		return array(
			'data' => array(
				'is_block_editor' => \KDNACommon::is_block_editor_page(),
			),
			'i18n' => array(
				'form_admin'   => array(
					'toggle_feed_inactive' => esc_html__( 'Inactive', 'kdnaforms' ),
					'toggle_feed_active'   => esc_html__( 'Active', 'kdnaforms' ),
				),
				'shortcode_ui' => array(
					'edit_form'   => esc_html__( 'Edit Form', 'kdnaforms' ),
					'insert_form' => esc_html__( 'Insert Form', 'kdnaforms' ),
				),
			),
		);
	}
}
