<?php
/**
 * Service Provider for Form saving
 *
 * Handles all CRUD operations related to editing forms via different kinds of editors.
 *
 * @package KDNA_Forms\KDNA_Forms\Save_Form
 */

namespace KDNA_Forms\KDNA_Forms\Save_Form;

use KDNA_Forms\KDNA_Forms\Config\KDNA_Config_Service_Provider;
use KDNA_Forms\KDNA_Forms\Save_Form\Config\KDNA_Form_Editor_Form_Save_Config;
use KDNA_Forms\KDNA_Forms\Save_Form\Config\GF_Admin_Form_Save_Config;
use KDNA_Forms\KDNA_Forms\Save_Form\Endpoints\KDNA_Save_Form_Endpoint_Admin;
use KDNA_Forms\KDNA_Forms\Save_Form\Endpoints\KDNA_Save_Form_Endpoint_Form_Editor;
use KDNA_Forms\KDNA_Forms\KDNA_Service_Container;
use KDNA_Forms\KDNA_Forms\KDNA_Service_Provider;
use KDNA_Forms\KDNA_Forms\Util\KDNA_Util_Service_Provider;

/**
 * Service Provider for Form saving
 *
 * Handles all CRUD operations related to editing forms via different kinds of editors.
 *
 * @since 2.6
 *
 * Service provider for the Duplicate Submission Service.
 */
class KDNA_Save_Form_Service_Provider extends KDNA_Service_Provider {

	// Configs names, used as keys for the configuration classes in the service container.
	const ADMIN_SAVE_CONFIG = 'admin_save_config';

	/**
	 * The configuration class names and their corresponding string keys in the service container.
	 *
	 * @since 2.6
	 *
	 * @var string[]
	 */
	protected $configs = array(
		self::ADMIN_SAVE_CONFIG => GF_Admin_Form_Save_Config::class,
	);

	// Endpoint names, used as keys for the endpoint classes in the service container.
	// keys are the same names for the ajax actions.
	const ENDPOINT_ADMIN_SAVE = 'admin_save_form';

	/**
	 * The endpoint class names and their corresponding string keys in the service container.
	 *
	 * @since 2.6
	 *
	 * @var string[]
	 */
	protected $endpoints = array(
		self::ENDPOINT_ADMIN_SAVE => KDNA_Save_Form_Endpoint_Admin::class,
	);

	// The CRUD handler key in the service container.
	const GF_FORM_CRUD_HANDLER = 'kdna_form_crud_handler';
	const GF_SAVE_FROM_HELPER  = 'gf_save_form_helper';

	/**
	 * Includes all related files and adds all containers.
	 *
	 * @since 2.6
	 *
	 * @param KDNA_Service_Container $container Container singleton object.
	 */
	public function register( KDNA_Service_Container $container ) {
		require_once plugin_dir_path( __FILE__ ) . 'config/class-kdna-admin-form-save-config.php';
		require_once plugin_dir_path( __FILE__ ) . 'endpoints/class-kdna-save-form-endpoint-admin.php';
		require_once plugin_dir_path( __FILE__ ) . 'class-kdna-form-crud-handler.php';
		require_once plugin_dir_path( __FILE__ ) . 'class-kdna-save-form-helper.php';

		$container->add(
			self::GF_FORM_CRUD_HANDLER,
			function () use ( $container ) {
				return new KDNA_Form_CRUD_Handler(
					array(
						KDNA_Util_Service_Provider::GF_FORMS_MODEL => $container->get( KDNA_Util_Service_Provider::GF_FORMS_MODEL ),
						KDNA_Util_Service_Provider::RG_FORMS_MODEL => $container->get( KDNA_Util_Service_Provider::RG_FORMS_MODEL ),
						KDNA_Util_Service_Provider::GF_COMMON => $container->get( KDNA_Util_Service_Provider::GF_COMMON ),
						KDNA_Util_Service_Provider::GF_API   => $container->get( KDNA_Util_Service_Provider::GF_API ),
						KDNA_Util_Service_Provider::GF_FORMS => $container->get( KDNA_Util_Service_Provider::GF_FORMS ),
					)
				);
			}
		);

		$container->add(
			self::GF_SAVE_FROM_HELPER,
			function () use ( $container ) {
				return new KDNA_Save_Form_Helper(
					array(
						KDNA_Util_Service_Provider::GF_FORMS => $container->get( KDNA_Util_Service_Provider::GF_FORMS ),
					)
				);
			}
		);

		$this->add_configs( $container );
		$this->add_endpoints( $container );

	}

	/**
	 * Register configuration classes.
	 *
	 * @since 2.6
	 *
	 * @param KDNA_Service_Container $container
	 */
	public function add_configs( KDNA_Service_Container $container ) {
		foreach ( $this->configs as $name => $class ) {
			$container->add(
				$name,
				function () use ( $container, $class ) {
					return new $class(
						$container->get( KDNA_Config_Service_Provider::DATA_PARSER ),
						array(
							KDNA_Util_Service_Provider::GF_FORMS => $container->get( KDNA_Util_Service_Provider::GF_FORMS ),
							KDNA_Util_Service_Provider::GF_API   => $container->get( KDNA_Util_Service_Provider::GF_API ),
						)
					);
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
							KDNA_Util_Service_Provider::GF_FORMS_MODEL            => $container->get( KDNA_Util_Service_Provider::GF_FORMS_MODEL ),
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
			'kdnaform_ajax_actions',
			function( $ajax_actions ) {
				$ajax_actions[] = KDNA_Save_Form_Endpoint_Admin::ACTION_NAME;

				return $ajax_actions;
			}
		);

		add_action(
			'wp_ajax_' . KDNA_Save_Form_Endpoint_Admin::ACTION_NAME,
			function () use ( $container ) {
				$container->get( self::ENDPOINT_ADMIN_SAVE )->handle();
			}
		);
	}

}
