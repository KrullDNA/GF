<?php

namespace KDNA_Forms\KDNA_Forms\Post_Custom_Field_Select;

use KDNA_Forms\KDNA_Forms\Config\KDNA_Config;
use KDNA_Forms\KDNA_Forms\Config\KDNA_Config_Data_Parser;
use KDNAFormsModel;

defined( 'ABSPATH' ) || die();

/**
 * Config items for the Post Custom Field Select dropdown
 *
 * @since 2.9.20
 */
class KDNA_Post_Custom_Field_Select_Config extends KDNA_Config {

	protected $name               = 'gform_admin_config';
	protected $script_to_localize = 'kdnaform_kdnaforms_admin_vendors';

	/**
	 * Constructor
	 *
	 * @since 2.9.20
	 *
	 * @param KDNA_Config_Data_Parser $data_parser
	 */
	public function __construct( KDNA_Config_Data_Parser $data_parser ) {
		parent::__construct( $data_parser );
	}

	/**
	 * Only enqueue in the form editor
	 *
	 * @since 2.9.20
	 *
	 * @return bool
	 */
	public function should_enqueue() {
		return \KDNAForms::get_page() === 'form_editor';
	}

	/**
	 * Config data for the post custom field select dropdown
	 *
	 * @since 2.9.20
	 *
	 * @return array
	 */
	public function data() {
		return array(
			'components' => array(
				'post_custom_select' => array(
					'endpoints' => array(
						'get' => array(
							'action' => 'gf_get_custom_fields',
							'nonce'  => wp_create_nonce( 'gf_get_custom_fields' ),
						),
					),
					'data'           => $this->get_initial_custom_fields(),
					'strings'        => array(
						'search_placeholder'  => esc_html__( 'Search custom field names...', 'kdnaforms' ),
						'trigger_placeholder' => esc_html__( 'Select a custom field name', 'kdnaforms' ),
						'trigger_aria_text'   => esc_html__( 'Default custom field name', 'kdnaforms' ),
						'search_aria_text'    => esc_html__( 'Search for custom field names', 'kdnaforms' ),
					),
				),
			),
		);
	}

	/**
	 * Get the initial list of custom fields for the dropdown
	 *
	 * @since 2.9.20
	 *
	 * @return array
	 */
	private function get_initial_custom_fields() {

		$results = KDNAFormsModel::get_custom_field_names( 10 );

		foreach( $results as &$result ) {
			$result = array(
				'value' => $result,
				'label' => $result,
			);
		}

		return $results;
	}
}
