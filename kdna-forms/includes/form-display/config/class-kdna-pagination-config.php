<?php

namespace KDNA_Forms\KDNA_Forms\Form_Display\Config;

use KDNA_Forms\KDNA_Forms\Config\KDNA_Config;

/**
 * Form specific product meta config.
 *
 * @since 2.9.0
 */
class GF_Pagination_Config extends KDNA_Config {

	protected $name               = 'kdnaform_theme_config';
	protected $script_to_localize = 'kdnaform_kdnaforms_theme';

	/**
	 * Config data.
	 *
	 * @return array[]
	 */
	public function data() {

		if ( ! rgar( $this->args, 'form_ids' ) ) {
			return array();
		}

		$pagination = array();
		foreach ( $this->args['form_ids'] as $form_id ) {
			$form = \KDNAFormDisplay::kdnaform_pre_render( \KDNAAPI::get_form( $form_id ), 'form_config' );
			$pagination[ $form_id ] = rgar( $form, 'pagination' );
		}

		return array(
			'common' => array(
				'form' => array(
					'pagination' => $pagination,
				),
			),
		);
	}

	/**
	 * Enable ajax loading for the "kdnaform_theme_config/common/form/pagination" config path.
	 *
	 * @since 2.9.0
	 *
	 * @param string $config_path The full path to the config item when stored in the browser's window object, for example: "kdnaform_theme_config/common/form/product_meta"
	 * @param array  $args        The args used to load the config data. This will be empty for generic config items. For form specific items will be in the format: array( 'form_ids' => array(123,222) ).
	 *
	 * @return bool Return true if the provided $config_path is the product_meta path. Return false otherwise.
	 */
	public function enable_ajax( $config_path, $args ) {
		if ( str_starts_with( $config_path, 'kdnaform_theme_config/common/form/pagination' ) ) {
			return true;
		}
		return false;
	}
}
