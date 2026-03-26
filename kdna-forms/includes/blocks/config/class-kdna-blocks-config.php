<?php

namespace KDNA_Forms\KDNA_Forms\Blocks\Config;

use KDNASettings;
use KDNA_Forms\KDNA_Forms\Config\KDNA_Config;
use KDNA_Forms\KDNA_Forms\Config\KDNA_Config_Data_Parser;
use \KDNACommon;
use \KDNAAPI;
use \KDNAFormDisplay;

/**
 * Config items for Blocks.
 *
 * @since
 */
class KDNA_Blocks_Config extends KDNA_Config {

	protected $name               = 'gform_admin_config';
	protected $script_to_localize = 'kdnaform_kdnaforms_admin_vendors';
	protected $attributes         = array();

	public function __construct( KDNA_Config_Data_Parser $parser, array $attributes ) {
		parent::__construct( $parser );
		$this->attributes = $attributes;
	}

	public function should_enqueue() {
		return KDNACommon::is_block_editor_page();
	}

	/**
	 * Get list of forms for Block control.
	 *
	 * @since 2.4.10
	 *
	 * @return array
	 */
	public function get_forms() {

		// Initialize forms array.
		$forms = array();

		// Load KDNAFormDisplay class.
		if ( ! class_exists( 'KDNAFormDisplay' ) ) {
			require_once KDNACommon::get_base_path() . '/form_display.php';
		}

		// Get form objects.
		$form_objects = KDNAAPI::get_forms( true, false, 'title', 'ASC' );

		// Loop through forms, add conditional logic check.
		foreach ( $form_objects as $form ) {
			$forms[] = array(
				'id'                  => $form['id'],
				'title'               => $form['title'],
				'hasConditionalLogic' => KDNAFormDisplay::has_conditional_logic( $form ),
				'isLegacyMarkup'      => KDNACommon::is_legacy_markup_enabled( $form ),
				'hasImageChoices'     => KDNAFormDisplay::has_image_choices( $form ),
			);
		}

		/**
		 * Modify the list of available forms displayed in the Form block.
		 *
		 * @since 2.4.23
		 *
		 * @param array $forms A collection of active forms on site.
		 */
		return apply_filters( 'kdnaform_block_form_forms', $forms );

	}

	/**
	 * Config data.
	 *
	 * @return array[]
	 */
	public function data() {
		$attributes = apply_filters( 'kdnaform_form_block_attributes', $this->attributes );

		$orbital_default = KDNASettings::is_orbital_default();

		return array(
			'block_editor' => array(
				'kdnaforms/form' => array(
					'data' => array(
						'attributes'     => $attributes,
						'adminURL'   	 => admin_url( 'admin.php' ),
						'forms'      	 => $this->get_forms(),
						'preview'        => KDNACommon::get_base_url() . '/images/kdna_block_preview.svg',
						'orbitalDefault' => $orbital_default,
						'block_docs_url' => 'https://docs.kdnaforms.com/kdna-forms-gutenberg-block/',
						'styles'     	 => array(
							'defaults' => \KDNAForms::get_service_container()->get( \KDNA_Forms\KDNA_Forms\Form_Display\GF_Form_Display_Service_Provider::BLOCK_STYLES_DEFAULTS ),
						),
					),
					'i18n' => array(
						'accent'                                    => esc_html__( 'Accent', 'kdnaforms' ),
						'advanced'                                  => esc_html__( 'Advanced', 'kdnaforms' ),
						'ajax'                                      => esc_html__( 'AJAX', 'kdnaforms' ),
						'appearance'                                => esc_html__( 'Appearance', 'kdnaforms' ),
						'background'                                => esc_html__( 'Background', 'kdnaforms' ),
						'border'                                    => esc_html__( 'Border', 'kdnaforms' ),
						'border_radius'                             => esc_html__( 'Border Radius', 'kdnaforms' ),
						'button_styles'                             => esc_html__( 'Button Styles', 'kdnaforms' ),
						'cancel'                                    => esc_html__( 'Cancel', 'kdnaforms' ),
						'card'                                      => esc_html__( 'Card', 'kdnaforms' ),
						'circle'                                    => esc_html__( 'Circle', 'kdnaforms' ),
						'close'                                     => esc_html__( 'Close', 'kdnaforms' ),
						'colors'                                    => esc_html__( 'Colors', 'kdnaforms' ),
						'copy_and_paste_not_available'              => esc_html__( 'Copy / Paste Not Available', 'kdnaforms' ),
						'copy_and_paste_requires_secure_connection' => esc_html__( 'Copy and paste functionality requires a secure connection. Reload this page using an HTTPS URL and try again.', 'kdnaforms' ),
						'copy_form_styles'                          => esc_html__( 'Copy Form Styles', 'kdnaforms' ),
						'custom_colors'                             => esc_html__( 'Custom Colors', 'kdnaforms' ),
						'default_colors'                            => esc_html__( 'Default Colors', 'kdnaforms' ),
						'description_styles'                        => esc_html__( 'Description Styles', 'kdnaforms' ),
						'edit_form'                                 => esc_html__( 'Edit Form', 'kdnaforms' ),
						'field_values'                              => esc_html__( 'Field Values', 'kdnaforms' ),
						'font_size'                                 => esc_html__( 'Font Size', 'kdnaforms' ),
						'form'                                      => esc_html__( 'Form', 'kdnaforms' ),
						'form_id'                                   => esc_html__( 'Form ID: %s', 'kdnaforms' ),
						'form_settings'                             => esc_html__( 'Form Settings', 'kdnaforms' ),
						'form_styles'                               => esc_html__( 'Form Styles', 'kdnaforms' ),
						'form_theme'                                => esc_html__( 'Form Theme', 'kdnaforms' ),
						'form_style_options_not_available'          => esc_html__( 'Form style options are not available for forms that use %1$slegacy mode%2$s.', 'kdnaforms' ),
						'kdna_forms'                             => esc_html__( 'KDNA Forms', 'kdnaforms' ),
						'kdna_forms_25_theme'                    => esc_html__( 'KDNA Forms 2.5 Theme', 'kdnaforms' ),
						'image_choice_styles'                       => esc_html__( 'Image Choice Styles', 'kdnaforms' ),
						'inherit_from_default'                      => esc_html__( 'Inherit from default (%s)', 'kdnaforms' ),
						'input_styles'                              => esc_html__( 'Input Styles', 'kdnaforms' ),
						'insert_kdnaform_block_title'                  => esc_html__( 'Add Block To Page', 'kdnaforms' ),
						'insert_kdnaform_block_content'                => esc_html__( 'Click or drag the KDNA Forms Block into the page to insert the form you selected. %1$sLearn More.%2$s', 'kdnaforms' ),
						'in_pixels'                                 => esc_html__( 'In pixels.', 'kdnaforms' ),
						'invalid_form_styles'                       => esc_html__( 'Invalid Form Styles', 'kdnaforms' ),
						'learn_more_orbital'                        => esc_html__( 'Learn more about configuring your form to use Orbital.', 'kdnaforms' ),
						'label_styles'                              => esc_html__( 'Label Styles', 'kdnaforms' ),
						'large'                                     => esc_html__( 'Large', 'kdnaforms' ),
						'medium'                                    => esc_html__( 'Medium', 'kdnaforms' ),
						'no_card'                                   => esc_html__( 'No Card', 'kdnaforms' ),
						'ok'                                        => esc_html__( 'OK', 'kdnaforms' ),
						'orbital_theme'                             => esc_html__( 'Orbital Theme', 'kdnaforms' ),
						'paste_form_styles'                         => esc_html__( 'Paste Form Styles', 'kdnaforms' ),
						'paste_not_available'                       => esc_html__( 'Paste Not Available', 'kdnaforms' ),
						'please_ensure_correct_format'              => esc_html__( 'Please ensure the form styles you are trying to paste are in the correct format.', 'kdnaforms' ),
						'preview'                                   => esc_html__( 'Preview', 'kdnaforms' ),
						'reset_defaults'                            => esc_html__( 'Reset Defaults', 'kdnaforms' ),
						'restore_defaults'                          => esc_html__( 'Restore Defaults', 'kdnaforms' ),
						'restore_default_styles'                    => esc_html__( 'Restore Default Styles', 'kdnaforms' ),
						'select_a_form'                             => esc_html__( 'Select a Form', 'kdnaforms' ),
						'select_and_display_form'                   => esc_html__( 'Select and display one of your forms.', 'kdnaforms' ),
						'show_form_description'                     => esc_html__( 'Show Form Description', 'kdnaforms' ),
						'show_form_title'                           => esc_html__( 'Show Form Title', 'kdnaforms' ),
						'size'                                      => esc_html__( 'Size', 'kdnaforms' ),
						'small'                                     => esc_html__( 'Small', 'kdnaforms' ),
						'style'                                     => esc_html__( 'Style', 'kdnaforms' ),
						'square'                                    => esc_html__( 'Square', 'kdnaforms' ),
						'tabindex'                                  => esc_html__( 'Tabindex', 'kdnaforms' ),
						'text'                                      => esc_html__( 'Text', 'kdnaforms' ),
						'theme_colors'                              => esc_html__( 'Theme Colors', 'kdnaforms' ),
						'the_accent_color_is_used'                  => esc_html__( 'The accent color is used for aspects such as checkmarks and dropdown choices.', 'kdnaforms' ),
						'the_background_color_is_used'              => esc_html__( 'The background color is used for various form elements, such as buttons and progress bars.', 'kdnaforms' ),
						'the_selected_form_deleted'                 => esc_html__( 'The selected form has been deleted or trashed. Please select a new form.', 'kdnaforms' ),
						'this_will_restore_defaults'                => esc_html__( 'This will restore your form styles back to their default values and cannot be undone. Are you sure you want to continue?', 'kdnaforms' ),
						'you_must_have_one_form'                    => esc_html__( 'You must have at least one form to use the block.', 'kdnaforms' ),
						'your_browser_no_permission_to_paste'       => __( 'Your browser does not have permission to paste from the clipboard. <p>Please navigate to <strong>about:config</strong> and change the preference <strong>dom.events.asyncClipboard.readText</strong> to <strong>true</strong>.', 'kdnaforms' ),
						'external_link_opens_in_new_tab'            => esc_html__( '(opens in a new tab)', 'kdnaforms' ),
					),
				),

			)
		);
	}
}
