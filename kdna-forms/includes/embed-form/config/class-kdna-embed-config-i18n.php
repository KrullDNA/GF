<?php

namespace KDNA_Forms\KDNA_Forms\Embed_Form\Config;

use KDNA_Forms\KDNA_Forms\Config\KDNA_Config;

/**
 * Config items for the Embed Form I18N
 *
 * @since 2.6
 */
class KDNA_Embed_Config_I18N extends KDNA_Config {

	protected $name               = 'gform_admin_config';
	protected $script_to_localize = 'kdnaform_kdnaforms_admin_vendors';

	/**
	 * Determine if the config should enqueue its data.
	 *
	 * @since 2.6.2
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
				'embed_form' => array(
					'i18n' => array(
						'title'                                      => esc_html__( 'Embed Form', 'kdnaforms' ),
						'id'                                         => esc_html__( 'Form ID: %s', 'kdnaforms' ),
						'add_title'                                  => esc_html__( 'Add to Existing Content', 'kdnaforms' ),
						'add_post_type_choice_label'                 => esc_html__( '%1$sAdd to Existing Content:%2$s %3$s', 'kdnaforms' ),
						'add_dropdown_placeholder'                   => esc_html__( 'Select a %s', 'kdnaforms' ),
						'add_trigger_aria_text'                      => esc_html__( 'Select a post', 'kdnaforms' ),
						'add_search_aria_text'                       => esc_html__( 'Search all %ss', 'kdnaforms' ),
						'add_button_label'                           => esc_html__( 'Insert Form', 'kdnaforms' ),
						'create_title'                               => esc_html__( 'Create New', 'kdnaforms' ),
						'create_post_type_choice_label'              => esc_html__( '%1$sCreate New:%2$s %3$s', 'kdnaforms' ),
						'create_placeholder'                         => esc_html__( 'Enter %s Name', 'kdnaforms' ),
						'create_button_label'                        => esc_html__( 'Create', 'kdnaforms' ),
						'dialog_title'                               => esc_html__( 'Unsaved Changes', 'kdnaforms' ),
						'dialog_content'                             => esc_html__( 'Oops! You have unsaved changes in the form, before you can continue with embedding it please save your changes.', 'kdnaforms' ),
						'dialog_confirm_text'                        => esc_html__( 'Save Changes', 'kdnaforms' ),
						'dialog_confirm_saving'                      => esc_html__( 'Saving', 'kdnaforms' ),
						'dialog_cancel_text'                         => esc_html__( 'Cancel', 'kdnaforms' ),
						'dialog_close_title'                         => esc_html__( 'Close this dialog and return to form editor.', 'kdnaforms' ),
						'shortcode_title'                            => esc_html__( 'Not Using the Block Editor?', 'kdnaforms' ),
						'shortcode_description'                      => esc_html__( 'Copy and paste the shortcode within your page builder.', 'kdnaforms' ),
						'shortcode_button_label'                     => esc_html__( 'Copy Shortcode', 'kdnaforms' ),
						'shortcode_button_copied'                    => esc_html__( 'Copied', 'kdnaforms' ),
						'shortcode_helper'                           => esc_html__( '%1$sLearn more%2$s about the shortcode.', 'kdnaforms' ),
						'shortcode_external_link_screen_reader_text' => esc_html__( 'Opens in a new tab', 'kdnaforms' ),
					),
				),
			),
		);
	}
}
