<?php

namespace KDNA_Forms\KDNA_Forms\Setup_Wizard\Config;

use KDNA_Forms\KDNA_Forms\Config\KDNA_Config;

/**
 * Config items for the Installation Wizard I18N
 *
 * @since 2.7
 */
class KDNA_Setup_Wizard_Config_I18N extends KDNA_Config {

	/**
	 * Script handle.
	 *
	 * @var string
	 */
	protected $name = 'kdnaform_admin_config';

	/**
	 * Handle of script to be localized.
	 *
	 * @var string
	 */
	protected $script_to_localize = 'kdnaform_kdnaforms_admin_vendors';

	/**
	 * Determine if the config should enqueue its data.
	 *
	 * @since 2.7
	 *
	 * @return bool
	 */
	public function should_enqueue() {
		return \KDNAForms::is_kdna_page();
	}

	/**
	 * Config data.
	 *
	 * @return array[]
	 */
	public function data() {
		return array(
			'components' => array(
				'setup_wizard' => array(
					'i18n' => array(
						// Buttons.
						'next'                  => __( 'Next', 'kdnaforms' ),
						'previous'              => __( 'Previous', 'kdnaforms' ),
						'close_button'          => __( 'Close', 'kdnaforms' ),
						'invalid_key'           => __( 'Invalid License Key', 'kdnaforms' ),
						'redirect_prompt'       => __( 'Back To Dashboard', 'kdnaforms' ),
						'toggle_fullscreen'     => __( 'Toggle Fullscreen', 'kdnaforms' ),

						// Screen 01.
						'welcome_title'         => __( 'Welcome to KDNA Forms', 'kdnaforms' ),
						'welcome_copy'          => __( 'Thank you for choosing KDNA Forms. We know you’re going to love our form builder and all it has to offer!', 'kdnaforms' ),
						'most_accessible'       => __( 'Create surveys and quizzes', 'kdnaforms' ),
						'column_layouts'        => __( 'Accept online payments', 'kdnaforms' ),
						'take_payments'         => __( 'Build custom business solutions', 'kdnaforms' ),
						'enter_license'         => __( 'Enter License Key', 'kdnaforms' ),
						'enter_license_plhdr'   => __( 'Paste your license key here', 'kdnaforms' ),
						'license_instructions'  => __( 'Enter your license key below to enable KDNA Forms.', 'kdnaforms' ),
						'activate_license'      => __( 'Activate License', 'kdnaforms' ),
						'key_validated'         => __( 'License Key Validated', 'kdnaforms' ),
						'check_license'         => __( 'Checking License', 'kdnaforms' ),
						'email_message_title'   => __( 'Get 20% Off KDNA Forms!', 'kdnaforms' ),
						'email_message'         => __( 'To continue installation enter your email below and get 20% off any new license.', 'kdnaforms' ),
						'email_message_plhldr'  => __( 'Email address', 'kdnaforms' ),
						'email_message_submit'  => __( 'Get the Discount', 'kdnaforms' ),
						'email_message_footer'  => __( 'I agree to the handling and storage of my data and to receive marketing communications from KDNA Forms.', 'kdnaforms' ),

						// Screen 02.
						'set_up_title'          => __( "Let's get you set up!", 'kdnaforms' ),
						'set_up_copy'           => __( 'Configure KDNA Forms to work in the way that you want.', 'kdnaforms' ),
						'for_client'            => __( 'Hide license information', 'kdnaforms' ),
						'hide_license'          => __( 'If you\'re installing KDNA Forms for a client, enable this setting to hide the license information.', 'kdnaforms' ),
						'enable_updates'        => __( 'Enable automatic updates', 'kdnaforms' ),
						'enable_updates_tag'    => __( 'Recommended', 'kdnaforms' ),
						'enable_updates_locked' => __( 'Feature Disabled', 'kdnaforms' ),
						'updates_recommended'   => __( 'We recommend you enable this feature to ensure KDNA Forms runs smoothly.', 'kdnaforms' ),
						'which_currency'        => __( 'Select a Currency', 'kdnaforms' ),

						// Screen 03.
						'personalize_title'     => __( 'Personalize your KDNA Forms experience', 'kdnaforms' ),
						'personalize_copy'      => __( 'Tell us about your site and how you’d like to use KDNA Forms.', 'kdnaforms' ),
						'describe_organization' => __( 'How would you best describe your website?', 'kdnaforms' ),
						'form_type'             => __( 'What types of forms do you want to create?', 'kdnaforms' ),
						'services_connect'      => __( 'Do you want to integrate your forms with any of these services?', 'kdnaforms' ),
						'other_label'           => __( 'Other', 'kdnaforms' ),
						'other_placeholder'     => __( 'Description', 'kdnaforms' ),

						// Screen 04.
						'help_improve_title'    => __( 'Help Make KDNA Forms Better!', 'kdnaforms' ),
						// translators: placeholders are markup to create a link.
						'help_improve_copy' => sprintf(
							esc_html__( 'We love improving the form building experience for everyone in our community. By enabling data collection, you can help us learn more about how our customers use KDNA Forms. %1$sLearn more...%2$s', 'kdnaforms' ),
							'<a target="_blank" href="https://docs.kdnaforms.com/about-additional-data-collection/">',
							'<span class="screen-reader-text">' . esc_html__( '(opens in a new tab)', 'kdnaforms' ) . '</span>&nbsp;<span class="kdnaform-icon kdnaform-icon--external-link" aria-hidden="true"></span></a>'
						),
						'no_thanks_button'      => __( 'No, Thanks.' ),
						'yes_button'            => __( 'Yes, Count Me In.' ),

						// Screen 05.
						'complete_title'        => __( 'Ready to Create Your First Form?', 'kdnaforms' ),
						'complete_message'      => __( 'Watch the video below to help you get started with KDNA Forms, or jump straight in and begin your form building journey!', 'kdnaforms' ),
						'create_form_button'    => __( 'Create Your First Form' ),
					),
				),
			),
		);
	}
}
