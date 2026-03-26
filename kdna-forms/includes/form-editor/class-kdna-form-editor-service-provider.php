<?php

namespace KDNA_Forms\KDNA_Forms\Form_Editor;

use KDNA_Forms\KDNA_Forms\Config\KDNA_Config_Service_Provider;
use KDNA_Forms\KDNA_Forms\Form_Editor\Choices_UI\Config\GF_Choices_UI_Config;
use KDNA_Forms\KDNA_Forms\Form_Editor\Choices_UI\Config\GF_Choices_UI_Config_I18N;
use KDNA_Forms\KDNA_Forms\Form_Editor\Choices_UI\Config\GF_Dialog_Config_I18N;
use KDNA_Forms\KDNA_Forms\Form_Editor\Save_Form\Config\KDNA_Form_Editor_Form_Save_Config;
use KDNA_Forms\KDNA_Forms\Form_Editor\Save_Form\Endpoints\KDNA_Save_Form_Endpoint_Form_Editor;
use KDNA_Forms\KDNA_Forms\Form_Editor\Submitted_Fields\Endpoints\GF_Submitted_Fields_Endpoint;
use KDNA_Forms\KDNA_Forms\Form_Editor\Renderer\KDNA_Form_Editor_Renderer;
use KDNA_Forms\KDNA_Forms\KDNA_Service_Container;
use KDNA_Forms\KDNA_Forms\KDNA_Service_Provider;
use KDNA_Forms\KDNA_Forms\Util\KDNA_Util_Service_Provider;
use KDNA_Forms\KDNA_Forms\Save_Form\KDNA_Save_Form_Service_Provider;

/**
 * Class KDNA_Embed_Service_Provider
 *
 * Service provider for the Form Editor Services.
 *
 * @package KDNA_Forms\KDNA_Forms\Form_Editor;
 */
class KDNA_Form_Editor_Service_Provider extends KDNA_Service_Provider {

	// Configs
	const CHOICES_UI_CONFIG       = 'embed_config';
	const CHOICES_UI_CONFIG_I18N  = 'embed_config_i18n';
	const DIALOG_CONFIG_I18N      = 'dialog_config_i18n';
	const FORM_EDITOR_SAVE_CONFIG = 'form_editor_save_config';
	const FORM_EDITOR_RENDERER    = 'form_editor_renderer';

	/**
	 * Array mapping config class names to their container ID.
	 *
	 * @since 2.6
	 *
	 * @var string[]
	 */
	protected $configs = array(
		self::CHOICES_UI_CONFIG       => GF_Choices_UI_Config::class,
		self::CHOICES_UI_CONFIG_I18N  => GF_Choices_UI_Config_I18N::class,
		self::DIALOG_CONFIG_I18N      => GF_Dialog_Config_I18N::class,
		self::FORM_EDITOR_SAVE_CONFIG => KDNA_Form_Editor_Form_Save_Config::class,
	);

	// Configs names, used as keys for the configuration classes in the service container.



	// Endpoint names, used as keys for the endpoint classes in the service container.
	// keys are the same names for the ajax actions.
	const ENDPOINT_FORM_EDITOR_SAVE  = 'form_editor_save_form';
	const ENDPOINT_SUBMITTED_FIELDS  = 'gf_get_submitted_fields';

	/**
	 * The endpoint class names and their corresponding string keys in the service container.
	 *
	 * @since 2.6
	 *
	 * @var string[]
	 */
	protected $endpoints = array(
		self::ENDPOINT_FORM_EDITOR_SAVE => KDNA_Save_Form_Endpoint_Form_Editor::class,
		self::ENDPOINT_SUBMITTED_FIELDS => GF_Submitted_Fields_Endpoint::class,
	);

	public function register( KDNA_Service_Container $container ) {
		// Dialog Alert Config
		require_once( plugin_dir_path( __FILE__ ) . '/dialog-alert/config/class-kdna-dialog-config-i18n.php' );

		// Choices UI Configs
		require_once( plugin_dir_path( __FILE__ ) . '/choices-ui/config/class-kdna-choices-ui-config.php' );
		require_once( plugin_dir_path( __FILE__ ) . '/choices-ui/config/class-kdna-choices-ui-config-i18n.php' );

		// Form Saver Configs
		require_once plugin_dir_path( __FILE__ ) . 'save-form/config/class-kdna-form-editor-form-save-config.php';
		require_once plugin_dir_path( __FILE__ ) . 'save-form/endpoints/class-kdna-save-form-endpoint-form-editor.php';

		// Submitted Fields Endpoint
		require_once plugin_dir_path( __FILE__ ) . 'submitted-fields/endpoints/class-kdna-submitted-fields-endpoint.php';

		// Editor Renderers.
		require_once plugin_dir_path( __FILE__ ) . 'renderer/class-kdna-form-editor-renderer.php';

		$this->add_configs( $container );
		$this->add_endpoints( $container );
		$container->add( self::FORM_EDITOR_RENDERER, new KDNA_Form_Editor_Renderer() );
	}

	/**
	 * For each config defined in $configs, instantiate and add to container.
	 *
	 * @since 2.6
	 *
	 * @param KDNA_Service_Container $container
	 *
	 * @return void
	 */
	private function add_configs( KDNA_Service_Container $container ) {
		$deps = array(
			KDNA_Config_Service_Provider::DATA_PARSER => $container->get( KDNA_Config_Service_Provider::DATA_PARSER ),
			KDNA_Util_Service_Provider::GF_FORMS      => $container->get( KDNA_Util_Service_Provider::GF_FORMS ),
			KDNA_Util_Service_Provider::GF_API        => $container->get( KDNA_Util_Service_Provider::GF_API ),
		);
		foreach ( $this->configs as $name => $class ) {
			$container->add(
				$name,
				function () use ( $container, $class, $deps ) {
					return new $class( $container->get( KDNA_Config_Service_Provider::DATA_PARSER ), $deps );
				}
			);

			$container->get( KDNA_Config_Service_Provider::CONFIG_COLLECTION )->add_config( $container->get( $name ) );
		}
	}


	/**
	 * Register Form Saving Endpoints.
	 *
	 * @since 2.6
	 *
	 * @param KDNA_Service_Container $container
	 *
	 * @return void
	 */
	private function add_endpoints( KDNA_Service_Container $container ) {
		foreach ( $this->endpoints as $name => $class ) {
			$container->add(
				$name,
				function () use ( $container, $class ) {
					return new $class(
						array(
							KDNA_Save_Form_Service_Provider::GF_FORM_CRUD_HANDLER => $container->get( KDNA_Save_Form_Service_Provider::GF_FORM_CRUD_HANDLER ),
							KDNA_Util_Service_Provider::GF_FORMS_MODEL => $container->get( KDNA_Util_Service_Provider::GF_FORMS_MODEL ),
							KDNA_Util_Service_Provider::GF_FORMS => $container->get( KDNA_Util_Service_Provider::GF_FORMS ),
						)
					);
				}
			);
		}
	}

	/**
	 * Initialize any actions or hooks required for handling form saving..
	 *
	 * @since 2.6
	 *
	 * @param KDNA_Service_Container $container
	 */
	public function init( KDNA_Service_Container $container ) {

		add_filter(
			'kdnaform_is_form_editor',
			function ( $is_editor ) {
				if ( KDNA_Save_Form_Endpoint_Form_Editor::ACTION_NAME === rgpost( 'action' ) ) {
					return true;
				}

				return $is_editor;
			}
		);

		add_filter(
			'kdnaform_ajax_actions',
			function( $ajax_actions ) {
				$ajax_actions[] = KDNA_Save_Form_Endpoint_Form_Editor::ACTION_NAME;
				$ajax_actions[] = GF_Submitted_Fields_Endpoint::ACTION_NAME;

				return $ajax_actions;
			}
		);

		add_action(
			'wp_ajax_' . KDNA_Save_Form_Endpoint_Form_Editor::ACTION_NAME,
			function () use ( $container ) {
				$container->get( self::ENDPOINT_FORM_EDITOR_SAVE )->handle();
			}
		);

		add_action(
			'wp_ajax_' . GF_Submitted_Fields_Endpoint::ACTION_NAME,
			function () use ( $container ) {
				$container->get( self::ENDPOINT_SUBMITTED_FIELDS )->handle();
			}
		);

	}

}
