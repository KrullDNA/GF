<?php

namespace KDNA_Forms\KDNA_Forms\Setup_Wizard\Config;

use KDNA_Forms\KDNA_Forms\Config\KDNA_Config;

/**
 * Config items for Setup_Wizard.
 *
 * @since
 */
class KDNA_Setup_Wizard_Config extends KDNA_Config {

	const INVALID_KEY_COOKIE = 'gf_setup_wizard_invalid_key';

	protected $name               = 'kdnaform_admin_config';
	protected $script_to_localize = 'kdnaform_kdnaforms_admin_vendors';

	/**
	 * Determine if the config should enqueue its data.
	 *
	 * @since 2.6.2
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
					'data' => array(
						'dashboard_url' => get_dashboard_url(),
						'redirect_url'  => get_dashboard_url() . 'admin.php?page=gf_edit_forms',
						'video_id'      => 'KiYWpQYTD8a1Hbmb19KLKC',
						'defaults'      => array(
							'activeStep'        => empty( \KDNACommon::get_key() ) ? 1 : 2,
							'autoUpdate'        => true,
							'currency'          => 'USD',
							'dataCollection'    => false,
							'email'             => '',
							'emailConsent'      => false,
							'formTypes'         => $this->get_form_types_options(),
							'formTypesOther'    => '',
							'hideLicense'       => false,
							'innerDialogOpen'   => false,
							'isOpen'            => true,
							'licenseKey'        => '',
							'organization'      => '',
							'organizationOther' => '',
							'services'          => $this->get_services_options(),
							'servicesOther'     => '',
						),
						'options'  => array(
							'currencies'           => \RGCurrency::get_grouped_currency_options( false ),
							'organization'         => $this->get_organization_options(),
							'invalidKeyCookieName' => self::INVALID_KEY_COOKIE,
							'hasLicense'           => ! empty( \KDNACommon::get_key() ),
							'isSettingsPage'       => \KDNAForms::get_page_query_arg() == 'gf_settings',
						),
						'shouldDisplay' => $this->get_should_display(),
					),
				),
			),
		);
	}

	private function get_should_display() {
		// Don't display on the system status page.
		if ( \KDNAForms::get_page_query_arg() == 'gf_system_status' ) {
			return false;
		}

		if ( defined( 'KDNA_DISPLAY_SETUP_WIZARD' ) && KDNA_DISPLAY_SETUP_WIZARD ) {
			return true;
		}

		return (bool) get_option( 'kdnaform_pending_installation' );
	}

	private function get_form_types_options() {
		return array(
			array(
				'initialChecked'  => false,
				'labelAttributes' => array(
					'label'  => __( 'Contact Form', 'kdnaforms' ),
					'size'   => 'text-sm',
					'weight' => 'regular',
				),
				'name'            => 'form_types',
				'size'            => 'size-md',
				'value'           => 'contact',
			),
			array(
				'initialChecked'  => false,
				'labelAttributes' => array(
					'label'  => __( 'Conversational Form', 'kdnaforms' ),
					'size'   => 'text-sm',
					'weight' => 'regular',
				),
				'name'            => 'form_types',
				'size'            => 'size-md',
				'value'           => 'conversational',
			),
			array(
				'initialChecked'  => false,
				'labelAttributes' => array(
					'label'  => __( 'Survey', 'kdnaforms' ),
					'size'   => 'text-sm',
					'weight' => 'regular',
				),
				'name'            => 'form_types',
				'size'            => 'size-md',
				'value'           => 'survey',
			),
			array(
				'initialChecked'  => false,
				'labelAttributes' => array(
					'label'  => __( 'Payment Form', 'kdnaforms' ),
					'size'   => 'text-sm',
					'weight' => 'regular',
				),
				'name'            => 'form_types',
				'size'            => 'size-md',
				'value'           => 'payment',
			),
			array(
				'initialChecked'  => false,
				'labelAttributes' => array(
					'label'  => __( 'Subscription Form', 'kdnaforms' ),
					'size'   => 'text-sm',
					'weight' => 'regular',
				),
				'name'            => 'form_types',
				'size'            => 'size-md',
				'value'           => 'subscription',
			),
			array(
				'initialChecked'  => false,
				'labelAttributes' => array(
					'label'  => __( 'Donation Form', 'kdnaforms' ),
					'size'   => 'text-sm',
					'weight' => 'regular',
				),
				'name'            => 'form_types',
				'size'            => 'size-md',
				'value'           => 'donation',
			),
			array(
				'initialChecked'  => false,
				'labelAttributes' => array(
					'label'  => __( 'Customer Service Form', 'kdnaforms' ),
					'size'   => 'text-sm',
					'weight' => 'regular',
				),
				'name'            => 'form_types',
				'size'            => 'size-md',
				'value'           => 'customer-service',
			),
			array(
				'initialChecked'  => false,
				'labelAttributes' => array(
					'label'  => __( 'Registration Form', 'kdnaforms' ),
					'size'   => 'text-sm',
					'weight' => 'regular',
				),
				'name'            => 'form_types',
				'size'            => 'size-md',
				'value'           => 'registration',
			),
			array(
				'initialChecked'  => false,
				'labelAttributes' => array(
					'label'  => __( 'Custom Form', 'kdnaforms' ),
					'size'   => 'text-sm',
					'weight' => 'regular',
				),
				'name'            => 'form_types',
				'size'            => 'size-md',
				'value'           => 'custom',
			),
			array(
				'initialChecked'  => false,
				'labelAttributes' => array(
					'label'  => __( 'Other', 'kdnaforms' ),
					'size'   => 'text-sm',
					'weight' => 'regular',
				),
				'name'            => 'form_types',
				'size'            => 'size-md',
				'value'           => 'other',
			),
		);
	}

	private function get_organization_options() {
		return array(
			array(
				'value'                  => '',
				'label'                  => __( 'Select a Website Type', 'kdnaforms' ),
				'customOptionAttributes' => array(
					'disabled' => true,
				),
			),
			array(
				'value' => 'blog',
				'label' => __( 'Blog', 'kdnaforms' ),
			),
			array(
				'value' => 'personal-business',
				'label' => __( 'Personal Business/Services', 'kdnaforms' ),
			),
			array(
				'value' => 'small-medium-business',
				'label' => __( 'Small/Medium Business', 'kdnaforms' ),
			),
			array(
				'value' => 'enterprise',
				'label' => __( 'Enterprise', 'kdnaforms' ),
			),
			array(
				'value' => 'ecommerce',
				'label' => __( 'eCommerce', 'kdnaforms' ),
			),
			array(
				'value' => 'education',
				'label' => __( 'Education', 'kdnaforms' ),
			),
			array(
				'value' => 'nonprofit',
				'label' => __( 'Nonprofit', 'kdnaforms' ),
			),
			array(
				'value' => 'other',
				'label' => __( 'Other', 'kdnaforms' ),
			),
		);
	}

	private function get_services_options() {
		return array(
			array(
				'initialChecked'  => false,
				'labelAttributes' => array(
					'label'  => __( 'Email Marketing Platform', 'kdnaforms' ),
					'size'   => 'text-sm',
					'weight' => 'regular',
				),
				'name'            => 'form_types',
				'size'            => 'size-md',
				'value'           => 'email-marketing',
			),
			array(
				'initialChecked'  => false,
				'labelAttributes' => array(
					'label'  => __( 'CRM', 'kdnaforms' ),
					'size'   => 'text-sm',
					'weight' => 'regular',
				),
				'name'            => 'form_types',
				'size'            => 'size-md',
				'value'           => 'crm',
			),
			array(
				'initialChecked'  => false,
				'labelAttributes' => array(
					'label'  => __( 'Payment Processor', 'kdnaforms' ),
					'size'   => 'text-sm',
					'weight' => 'regular',
				),
				'name'            => 'form_types',
				'size'            => 'size-md',
				'value'           => 'payment-processor',
			),
			array(
				'initialChecked'  => false,
				'labelAttributes' => array(
					'label'  => __( 'Anti Spam Services', 'kdnaforms' ),
					'size'   => 'text-sm',
					'weight' => 'regular',
				),
				'name'            => 'form_types',
				'size'            => 'size-md',
				'value'           => 'anti-spam',
			),
			array(
				'initialChecked'  => false,
				'labelAttributes' => array(
					'label'  => __( 'Accounting Software', 'kdnaforms' ),
					'size'   => 'text-sm',
					'weight' => 'regular',
				),
				'name'            => 'form_types',
				'size'            => 'size-md',
				'value'           => 'accounting',
			),
			array(
				'initialChecked'  => false,
				'labelAttributes' => array(
					'label'  => __( 'Other', 'kdnaforms' ),
					'size'   => 'text-sm',
					'weight' => 'regular',
				),
				'name'            => 'form_types',
				'size'            => 'size-md',
				'value'           => 'other',
			),
		);
	}

}
