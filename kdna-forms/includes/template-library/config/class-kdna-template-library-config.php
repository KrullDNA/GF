<?php

namespace KDNA_Forms\KDNA_Forms\Template_Library\Config;

use KDNA_Forms\KDNA_Forms\Config\KDNA_Config;
use KDNA_Forms\KDNA_Forms\Config\KDNA_Config_Data_Parser;
// use KDNA_Forms\KDNA_Forms\License\KDNA_License_API_Connector; // Removed - license functionality removed.
use KDNA_Forms\KDNA_Forms\Template_Library\Endpoints\GF_Create_Form_Template_Library_Endpoint;
use KDNA_Forms\KDNA_Forms\Template_Library\Templates\GF_Templates_Store;

/**
 * Config items for Template_Library.
 *
 * @since
 */
class KDNA_Template_Library_Config extends KDNA_Config {

	/**
	 * The object name for this config.
	 *
	 * @since 2.7
	 *
	 * @var string
	 */
	protected $name = 'kdnaform_admin_config';

	/**
	 * The ID of the script to localize the data to.
	 *
	 * @since 2.7
	 *
	 * @var string
	 */
	protected $script_to_localize = 'kdnaform_kdnaforms_admin_vendors';

	/**
	 * The templates' data store to retrieve the templates' data from.
	 *
	 * @since 2.7
	 *
	 * @var GF_Templates_Store $templates_repos
	 */
	protected $templates_store;

	/**
	 * Config class constructor.
	 *
	 * @since 2.7
	 *
	 * @param KDNA_Config_Data_Parser $parser          Data Parser.
	 * @param GF_Templates_Store    $templates_store The templates' data store to retrieve the templates' data from.
	 * @param mixed                 $license_api     Deprecated - license functionality removed.
	 */
	public function __construct( KDNA_Config_Data_Parser $parser, GF_Templates_Store $templates_store, $license_api = null ) {
		parent::__construct( $parser );
		$this->templates_store = $templates_store;
	}

	public function should_enqueue() {
		$current_page = \KDNAForms::get_page_query_arg();
		$gf_pages     = array( 'gf_edit_forms', 'gf_new_form' );

		return in_array( $current_page, $gf_pages );
	}

	/**
	 * Config data.
	 *
	 * @return array[]
	 */
	public function data() {
		// License check removed - plugin is now free.
		$bypassTemplateLibrary = apply_filters('kdnaform_bypass_template_library', false);

		return array(
			'components' => array(
				'template_library' => array(
					'endpoints' 			=> $this->get_endpoints(),
					'i18n'      			=> array(
						'description'                => __( 'Form Description', 'kdnaforms' ),
						'title'                      => __( 'Form Title', 'kdnaforms' ),
						'titlePlaceholder'           => __( 'Enter the form title', 'kdnaforms' ),
						'required'                   => __( 'Required', 'kdnaforms' ),
						'useTemplate'                => __( 'Use Template', 'kdnaforms' ),
						'closeButton'                => __( 'Close', 'kdnaforms' ),
						/* translators: title of template */
						'useTemplateWithTitle'       => __( 'Use Template %s', 'kdnaforms' ),
						'createActiveText'           => __( 'Creating Form', 'kdnaforms' ),
						'missingTitle'               => __( 'Please enter a valid form title.', 'kdnaforms' ),
						'duplicateTitle'             => __( 'Please enter a unique form title.', 'kdnaforms' ),
						'failedRequest'              => __( 'There was an issue creating your form.', 'kdnaforms' ),
						'failedRequestDialogTitle'   => __( 'Import failed.', 'kdnaforms' ),
						'importErrorCloseText'       => __( 'Close.', 'kdnaforms' ),
						/* translators: title of template */
						'previewWithTitle'           => __( 'Preview %s', 'kdnaforms' ),
						'cancel'                     => __( 'Cancel', 'kdnaforms' ),
						'blankForm'                  => __( 'Blank Form', 'kdnaforms' ),
						'createForm'                 => __( 'Create Blank Form', 'kdnaforms' ),
						'blankFormTitle'             => __( 'New Blank Form', 'kdnaforms' ),
						'blankFormDescription'       => __( 'A new blank form', 'kdnaforms' ),
						'formDescriptionPlaceHolder' => __( 'A form description goes here', 'kdnaforms' ),
						'heading'                    => __( 'Explore Form Templates', 'kdnaforms' ),
						'subheading'                 => __( 'Quickly create an amazing form by using a pre-made template, or start from scratch to tailor your form to your specific needs.', 'kdnaforms' ),
						'upgradeTag'                 => __( 'Upgrade', 'kdnaforms' ),
						'upgradeAlert'               => array(
							'value' => '',
							'default' => '',
						),
					),
					'data'      			=> array(
						'thumbnail_url' => \KDNACommon::get_image_url( 'template-library/' ),
						'layout'        => 'full-screen',
						'templates'     => $bypassTemplateLibrary ? array() : array_values( $this->get_templates() ),
						'licenseType'   => '', // License functionality removed.
						'defaults'      => array(
							'isLibraryOpen'             => \KDNAForms::get_page_query_arg() === 'gf_new_form',
							'flyoutOpen'                => (bool)$bypassTemplateLibrary,
							'flyoutFooterButtonLabel'   => $bypassTemplateLibrary ? __( 'Create Form', 'kdnaforms' ) : '',
							'flyoutTitleValue'          => '',
							'flyoutDescriptionValue'    => '',
							'selectedTemplate'          => array(
								'title' 	  => __( 'New Form', 'kdnaforms' ),
								'description' => __( 'A new form', 'kdnaforms' ),
								'id' 		  => 'blank',
							),
							'flyoutTitleErrorState'     => false,
							'flyoutTitleErrorMessage'   => '',
							'importError'               => false,
							'flyoutPrimaryLoadingState' => false,
							'bypassTemplateLibrary' => $bypassTemplateLibrary,
						),
					),
				),
			),
		);
	}

	/**
	 * Returns the endpoints for handling form creation in the template library.
	 *
	 * @since 2.7
	 *
	 * @return \array[][]
	 */
	private function get_endpoints() {
		return array(
			'create_from_template' => array(
				'action' => array(
					'value'   => GF_Create_Form_Template_Library_Endpoint::ACTION_NAME,
					'default' => 'mock_endpoint',
				),
				'nonce'  => array(
					'value'   => wp_create_nonce( GF_Create_Form_Template_Library_Endpoint::ACTION_NAME ),
					'default' => 'nonce',
				),
			),
		);
	}

	/**
	 * Gets a list of the available templates from the data store.
	 *
	 * @since 2.7
	 *
	 * @return array
	 */
	private function get_templates() {
		return $this->templates_store->all();
	}
	
}
