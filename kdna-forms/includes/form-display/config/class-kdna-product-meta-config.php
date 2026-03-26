<?php

namespace KDNA_Forms\KDNA_Forms\Form_Display\Config;

use KDNA_Forms\KDNA_Forms\Config\KDNA_Config;

/**
 * Form specific product meta config.
 *
 * @since 2.9.0
 */
class GF_Product_Meta_Config extends KDNA_Config {

	protected $name               = 'gform_theme_config';
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

		$product_metas = array();
		foreach ( $this->args['form_ids'] as $form_id ) {
			$product_metas[ $form_id ] = $this->get_product_meta( $form_id );
		}

		return array(
			'common' => array(
				'form' => array(
					'product_meta' => $product_metas,
				),
			),
		);
	}

	/**
	 * Enable ajax loading for the "kdnaform_theme_config/common/form/product_meta" config path.
	 *
	 * @since 2.9.0
	 *
	 * @param string $config_path The full path to the config item when stored in the browser's window object, for example: "kdnaform_theme_config/common/form/product_meta"
	 * @param array  $args        The args used to load the config data. This will be empty for generic config items. For form specific items will be in the format: array( 'form_ids' => array(123,222) ).
	 *
	 * @return bool Return true if the provided $config_path is the product_meta path. Return false otherwise.
	 */
	public function enable_ajax( $config_path, $args ) {
		if ( str_starts_with( $config_path, 'kdnaform_theme_config/common/form/product_meta' ) ) {
			return true;
		}
		return false;
	}

	/**
	 * Get the product meta for a form.
	 *
	 * @since 2.9.0
	 *
	 * @param int $form_id The form ID.
	 *
	 * @return array|null Returns the product meta for the form. Returns null if the form does not contain any product fields.
	 */
	private function get_product_meta( $form_id ) {

		$product_meta   = array();
		$products       = array();
		$form           = \KDNAFormDisplay::kdnaform_pre_render( \KDNAAPI::get_form( $form_id ), 'form_config' );
		$product_fields = \KDNAAPI::get_fields_by_type( $form, array( 'product' ) );
		if ( empty( $product_fields ) ) {
			return null;
		}

		foreach ( $product_fields as $field ) {
			$products[ $field->id ] = $this->clean_meta( $field );

			$options = array();
			$option_fields = \KDNACommon::get_product_fields_by_type( $form, array( 'option' ), $field->id );
			if ( empty( $option_fields ) ) {
				continue;
			}

			foreach ( $option_fields as $option_field ) {
				$options[ $option_field->id ] = $this->clean_meta( $option_field );
			}

			$products[ $field->id ]['options'] = $options;
		}

		if ( !empty( $products ) ) {
			$product_meta['products'] = $products;
		}

		$shipping_fields = \KDNAAPI::get_fields_by_type( $form, array( 'shipping' ) );
		if ( !empty( $shipping_fields ) ) {
			$product_meta['shipping'] = $this->clean_meta( $shipping_fields[0] );
		}

		$product_meta['hash'] = self::hash( $product_meta );

		return $product_meta;
	}

	/**
	 * Cleans the field metadata so that it only contains a set of whitelisted properties.
	 *
	 * @param array $data Metadata to be cleaned.
	 *
	 * @return array Returns the clean metadata, only containing a set of whitelisted keys.
	 * @since 2.9.0
	 *
	 */
	private function clean_meta( $data )
	{
		$whitelisted = array( 'id', 'label', 'choices', 'inputs', 'type', 'inputType', 'basePrice', 'disableQuantity' );

		// Convert to an associative array
		$data = (array) $data;

		// Filter the array to only include whitelisted properties
		return array_intersect_key( $data, array_flip( $whitelisted ) );
	}
}
