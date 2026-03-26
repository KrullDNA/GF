<?php

namespace KDNA_Forms\KDNA_Forms\Settings\Config;

use KDNA_Forms\KDNA_Forms\Config\KDNA_Config;

/**
 * Config items for the Settings I18N
 *
 * @since 2.6
 */
class KDNA_Settings_Config_I18N extends KDNA_Config {

	protected $name               = 'gform_admin_config';
	protected $script_to_localize = 'kdnaform_kdnaforms_admin_vendors';


	/**
	 * Config data.
	 *
	 * @return array[]
	 */
	public function data() {
		return array(
			'form_settings' => array(
				'loader' => array(
					'i18n' => array(
						'loaderText' => __( 'Loading', 'kdnaforms' ),
					),
				),
			),
			'field_map' => array(
				'i18n' => array(
					'add'                 => __( 'Add', 'kdnaforms' ),
					'add_custom_key'      => __( 'Add Custom Key', 'kdnaforms' ),
					'add_custom_value'    => __( 'Add Custom Value', 'kdnaforms' ),
					'delete'              => __( 'Delete', 'kdnaforms' ),
					'remove_custom_value' => __( 'Remove Custom Value', 'kdnaforms' ),
					'select_a_field'      => __( 'Select a Field', 'kdnaforms' ),
				),
			),
			'components' => array(
				'color_picker' => array(
					'i18n' => array(
						'apply' => __( 'Apply', 'kdnaforms' ),
						'hex'   => __( 'Hex', 'kdnaforms' ),
					),
				),
				'swatch' => array(
					'i18n' => array(
						'swatch' => __( 'swatch', 'kdnaforms' ),
					),
				),
				'file_upload' => array(
					'i18n' => array(
						'click_to_upload' => __( 'Click to upload', 'kdnaforms' ),
						'drag_n_drop'     => __( 'or drag and drop', 'kdnaforms' ),
						'max'             => __( 'max.', 'kdnaforms' ),
						'or'              => __( 'or', 'kdnaforms' ),
						'replace'         => __( 'Replace', 'kdnaforms' ),
						'delete'          => __( 'Delete', 'kdnaforms' ),
					),
				),
			),
		);
	}
}
