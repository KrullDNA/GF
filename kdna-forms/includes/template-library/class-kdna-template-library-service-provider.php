<?php

namespace KDNA_Forms\KDNA_Forms\Template_Library;

use KDNA_Forms\KDNA_Forms\Config\KDNA_Config_Service_Provider;
// use KDNA_Forms\KDNA_Forms\License\KDNA_License_Service_Provider; // Removed - license functionality removed.
use KDNA_Forms\KDNA_Forms\Template_Library\Config\KDNA_Template_Library_Config;
use KDNA_Forms\KDNA_Forms\Template_Library\Endpoints\GF_Create_Form_Template_Library_Endpoint;
use KDNA_Forms\KDNA_Forms\Template_Library\Templates\KDNA_Template_Library_File_Store;
use KDNA_Forms\KDNA_Forms\Template_Library\Templates\KDNA_Template_Library_Array_Store;
use KDNA_Forms\KDNA_Forms\Template_Library\Templates\GF_Templates_Store;



use KDNA_Forms\KDNA_Forms\KDNA_Service_Container;
use KDNA_Forms\KDNA_Forms\KDNA_Service_Provider;

/**
 * Class KDNA_Template_Library_Service_Provider
 *
 * Service provider for the Template_Library Service.
 *
 * @package KDNA_Forms\KDNA_Forms\Template_Library;
 */
class KDNA_Template_Library_Service_Provider extends KDNA_Service_Provider {

	// Configs.
	const TEMPLATE_LIBRARY_CONFIG = 'template_library_config';

	/**
	 * Array mapping config class names to their container ID.
	 *
	 * @since 2.7
	 *
	 * @var string[]
	 */
	protected $configs = array(
		self::TEMPLATE_LIBRARY_CONFIG => KDNA_Template_Library_Config::class,
	);

	// Endpoint label.
	const ENDPOINT_CREATE_FROM_TEMPLATE = 'create_from_template';

	/**
	 * The endpoint class names and their corresponding string keys in the service container.
	 *
	 * @since 2.7
	 *
	 * @var string[]
	 */
	protected $endpoints = array(
		self::ENDPOINT_CREATE_FROM_TEMPLATE => GF_Create_Form_Template_Library_Endpoint::class,
	);


	/**
	 * The data store configuration.
	 *
	 * @var array $template_data_configurations The data store configuration.
	 */
	protected $template_data_configurations;

	/**
	 * Register services to the container.
	 *
	 * @since
	 *
	 * @param KDNA_Service_Container $container The service container.
	 */
	public function register( KDNA_Service_Container $container ) {
		// Templates store.
		require_once plugin_dir_path( __FILE__ ) . '/templates/class-kdna-template-library-templates-store.php';
		require_once plugin_dir_path( __FILE__ ) . '/templates/class-kdna-template-library-file-store.php';
		require_once plugin_dir_path( __FILE__ ) . '/templates/class-kdna-template-library-array-store.php';
		require_once plugin_dir_path( __FILE__ ) . '/templates/class-kdna-template-library-template.php';
		// Configs.
		require_once plugin_dir_path( __FILE__ ) . '/config/class-kdna-template-library-config.php';
		// Endpoints.
		require_once plugin_dir_path( __FILE__ ) . '/endpoints/class-kdna-create-form-template-endpoint.php';

		$this->template_data_configurations = array(
			'data_store' => array(
				'type'   => KDNA_Template_Library_Array_Store::class,
				'config' => array(
					'uri' => \KDNACommon::get_base_path() . '/includes/template-library/templates/templates.php',
				),
			),
		);

		$this->add_data_store( $container );
		$this->add_configs( $container );
		$this->add_endpoints( $container );
		$this->register_template_library_app();
	}

	private function register_template_library_app() {
		$dev_min = defined( 'KDNA_SCRIPT_DEBUG' ) && KDNA_SCRIPT_DEBUG ? '' : '.min';

		$args = array(
			'app_name'     => 'template_library',
			'script_name'  => 'kdnaform_kdnaforms_admin_vendors',
			'object_name'  => 'kdnaform_admin_config',
			'chunk'        => './template-library',
			'enqueue'      => array( $this, 'should_enqueue_library' ),
			'css'          => array(
				'handle' => 'template_library_styles',
				'src'    => \KDNACommon::get_base_url() . "/assets/css/dist/template-library{$dev_min}.css",
				'deps'   => array( 'kdnaform_admin_components' ),
				'ver'    => defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? filemtime( \KDNACommon::get_base_path() . "/assets/css/dist/template-library{$dev_min}.css" ) : \KDNAForms::$version,
			),
			'root_element' => 'gf-template-library',
		);

		$this->register_app( $args );
	}

	public function should_enqueue_library() {
		$current_page = \KDNAForms::get_page_query_arg();
		$gf_pages     = array( 'gf_edit_forms', 'gf_new_form' );

		if ( $current_page === 'gf_edit_forms' ) {
			return empty( rgget( 'id' ) );
		}

		return in_array( $current_page, $gf_pages );
	}

	/**
	 * Initialize any actions or hooks.
	 *
	 * @since 2.7
	 *
	 * @param KDNA_Service_Container $container The service container.
	 *
	 * @return void
	 */
	public function init( KDNA_Service_Container $container ) {
		// add hooks or filters here.
		add_action(
			'wp_ajax_' . GF_Create_Form_Template_Library_Endpoint::ACTION_NAME,
			function () use ( $container ) {
				$container->get( self::ENDPOINT_CREATE_FROM_TEMPLATE )->handle();
			}
		);
	}

	/**
	 * Adds the templates' data store service.
	 *
	 * @since 2.7
	 *
	 * @param KDNA_Service_Container $container The service container.
	 */
	public function add_data_store( KDNA_Service_Container $container ) {
		$container->add(
			$this->template_data_configurations['data_store']['type'],
			function () use ( $container ) {
				return new $this->template_data_configurations['data_store']['type']( $this->template_data_configurations['data_store']['config'] );
			}
		);
	}

	/**
	 * For each config defined in $configs, instantiate and add to container.
	 *
	 * @since 2.7
	 *
	 * @param KDNA_Service_Container $container The service container.
	 */
	private function add_configs( KDNA_Service_Container $container ) {
		foreach ( $this->configs as $name => $class ) {
			$container->add(
				$name,
				function () use ( $container, $class ) {
					return new $class(
						$container->get( KDNA_Config_Service_Provider::DATA_PARSER ),
						$container->get( $this->template_data_configurations['data_store']['type'] ),
						null // License functionality removed.
					);
				}
			);

			$container->get( KDNA_Config_Service_Provider::CONFIG_COLLECTION )->add_config( $container->get( $name ) );
		}
	}

	/**
	 * Register Creating Forms Endpoints.
	 *
	 * @since 2.7
	 *
	 * @param KDNA_Service_Container $container The service container.
	 */
	private function add_endpoints( KDNA_Service_Container $container ) {
		foreach ( $this->endpoints as $name => $class ) {
			$container->add(
				$name,
				function () use ( $container, $class ) {
					return new $class(
						$container->get( $this->template_data_configurations['data_store']['type'] )
					);
				}
			);
		}
	}
}

