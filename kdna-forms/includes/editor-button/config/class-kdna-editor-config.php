<?php

namespace KDNA_Forms\KDNA_Forms\Editor_Button\Config;

use KDNA_Forms\KDNA_Forms\Config\KDNA_Config;
use KDNA_Forms\KDNA_Forms\Editor_Button\KDNA_Editor_Service_Provider;
use KDNA_Forms\KDNA_Forms\Editor_Button\Endpoints\KDNA_Editor_Save_Editor_Settings;

/**
 * Config items for the Editor Settings Button
 *
 * @since 2.8
 */
class KDNA_Editor_Config extends KDNA_Config {

	protected $name               = 'kdnaform_admin_config';
	protected $script_to_localize = 'kdnaform_kdnaforms_admin_vendors';

	/**
	 * Determine if the config should enqueue its data.
	 *
	 * @since 2.8
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
		return array(
			'components' => array(
				'editor_button' => array(
					'i18n' => array(
						'title'   	   	       => esc_html__( 'Editor Preferences', 'kdnaforms' ),
						'closeButtonAriaLabel' => esc_html__( 'Close button', 'kdnaforms' ),
						'description'   	   => esc_html__( 'Change options related to the form editor.', 'kdnaforms' ),
						'compactToggleLabel'   => esc_html__( 'Compact View', 'kdnaforms' ),
						'compactToggleText'    => esc_html__( 'Simplify the preview of form fields for a more streamlined editing experience.', 'kdnaforms' ),
						'idToggleLabel' 	   => esc_html__( 'Show Field IDs', 'kdnaforms' ),
						'idToggleText'         => esc_html__( 'Show the ID of each field in Compact View.', 'kdnaforms' ),
					),
					'endpoints' => $this->get_endpoints(),
					'compactViewEnabled' => KDNA_Editor_Service_Provider::is_compact_view_enabled( get_current_user_id(), rgget( 'id' ) ),
					'fieldIdEnabled' => KDNA_Editor_Service_Provider::is_field_id_enabled( get_current_user_id(), rgget( 'id' ) ),
					'form' => rgget( 'id' ),
				),
				'dropdown_menu' => array(
					'i18n' => array(
						'duplicateButtonLabel' => esc_html__( 'Duplicate', 'kdnaforms' ),
						'deleteButtonLabel'    => esc_html__( 'Delete', 'kdnaforms' ),
						'dropdownButtonLabel'  => esc_html__( 'Dropdown menu button', 'kdnaforms' ),
					),
				),
			),
		);
	}

	/**
	 * Gets the endpoints for saving the compact view settings.
	 *
	 * @since 2.8
	 *
	 * @return \array[][]
	 */
	private function get_endpoints() {
		return array(
			'save_editor_settings' => array(
				'action' => array(
					'value'   => KDNA_Editor_Save_Editor_Settings::ACTION_NAME,
					'default' => 'mock_endpoint',
				),
				'nonce'  => array(
					'value'   => wp_create_nonce( KDNA_Editor_Save_Editor_Settings::ACTION_NAME ),
					'default' => 'nonce',
				),
			),
		);
	}
}
