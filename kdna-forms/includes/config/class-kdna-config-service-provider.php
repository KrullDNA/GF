<?php

namespace KDNA_Forms\KDNA_Forms\Config;

use KDNA_Forms\KDNA_Forms\Config\Items\KDNA_Config_Admin;
use KDNA_Forms\KDNA_Forms\Config\Items\KDNA_Config_Block_Editor;
use KDNA_Forms\KDNA_Forms\Config\Items\KDNA_Config_Global;
use KDNA_Forms\KDNA_Forms\Config\Items\KDNA_Config_I18n;
use KDNA_Forms\KDNA_Forms\Config\Items\KDNA_Config_Legacy_Check;
use KDNA_Forms\KDNA_Forms\Config\Items\KDNA_Config_Legacy_Check_Multi;
use KDNA_Forms\KDNA_Forms\Config\Items\KDNA_Config_Multifile;

use KDNA_Forms\KDNA_Forms\KDNA_Service_Container;
use KDNA_Forms\KDNA_Forms\KDNA_Service_Provider;

/**
 * Class KDNA_Config_Service_Provider
 *
 * Service provider for the Config Service.
 *
 * @package KDNA_Forms\KDNA_Forms\Config
 */
class KDNA_Config_Service_Provider extends KDNA_Service_Provider {

	// Organizational services
	const CONFIG_COLLECTION = 'config_collection';
	const DATA_PARSER       = 'data_parser';

	// Config services
	const I18N_CONFIG         = 'i18n_config';
	const ADMIN_CONFIG        = 'admin_config';
	const LEGACY_CONFIG       = 'legacy_config';
	const LEGACY_MULTI_CONFIG = 'legacy_multi_config';
	const MULTIFILE_CONFIG    = 'multifile_config';
	const GLOBAL_CONFIG       = 'global_config';

	/**
	 * Array mapping config class names to their container ID.
	 *
	 * @since 2.6
	 *
	 * @var string[]
	 */
	protected $configs = array(
		self::I18N_CONFIG         => KDNA_Config_I18n::class,
		self::ADMIN_CONFIG        => KDNA_Config_Admin::class,
		self::LEGACY_CONFIG       => KDNA_Config_Legacy_Check::class,
		self::LEGACY_MULTI_CONFIG => KDNA_Config_Legacy_Check_Multi::class,
		self::MULTIFILE_CONFIG    => KDNA_Config_Multifile::class,
	);

	/**
	 * Register services to the container.
	 *
	 * @since 2.6
	 *
	 * @param KDNA_Service_Container $container
	 */
	public function register( KDNA_Service_Container $container ) {

		// Include required files.
		require_once( plugin_dir_path( __FILE__ ) . 'class-kdna-config-collection.php' );
		require_once( plugin_dir_path( __FILE__ ) . 'class-kdna-config.php' );
		require_once( plugin_dir_path( __FILE__ ) . 'class-kdna-config-data-parser.php' );
		require_once( plugin_dir_path( __FILE__ ) . 'class-kdna-app-config.php' );
		require_once( plugin_dir_path( __FILE__ ) . 'items/class-kdna-config-global.php' );

		// Add to container
		$container->add( self::CONFIG_COLLECTION, function () {
			return new KDNA_Config_Collection();
		} );

		$container->add( self::DATA_PARSER, function () {
			return new KDNA_Config_Data_Parser();
		} );

		$container->add( self::GLOBAL_CONFIG, function () {
			return new KDNA_Config_Global();
		} );

		// Add configs to container.
		$this->register_config_items( $container );
		$this->register_configs_to_collection( $container );
	}

	/**
	 * Whether the config has been localized.
	 *
	 * @since 2.9.0
	 *
	 * @var bool
	 */
	private static $is_localized = false;

	/**
	 * Initiailize any actions or hooks.
	 *
	 * @since 2.6
	 *
	 * @param KDNA_Service_Container $container
	 *
	 * @return void
	 */
	public function init( KDNA_Service_Container $container ) {

		// Need to pass $this to callbacks; save as variable.
		$self = $this;

		add_action( 'wp_enqueue_scripts', function () use ( $container ) {
			// Only localize during wp_enqueue_scripts if none of the other more specific events have been fired.
			if ( ! self::$is_localized ) {
				$container->get( self::CONFIG_COLLECTION )->handle();
			}
		}, 9999 );

		add_action( 'admin_enqueue_scripts', function () use ( $container ) {
			// Only localize during admin_enqueue_scripts if none of the other more specific events have been fired.
			if ( ! self::$is_localized ) {
				$container->get( self::CONFIG_COLLECTION )->handle();
			}
		}, 9999 );

		add_action( 'kdnaform_output_config', function ( $form_ids = null ) use ( $container ) {
			$container->get( self::CONFIG_COLLECTION )->handle( true, $form_ids );
			self::$is_localized = true;
		} );

		add_action( 'kdnaform_post_enqueue_scripts', function ( $found_forms, $found_blocks, $post ) use ( $container ) {
			$form_ids = array_column( $found_forms, 'formId' );
			$container->get( self::CONFIG_COLLECTION )->handle( true, array( 'form_ids' => $form_ids ) );
			self::$is_localized = true;
		}, 10, 3);

		add_action( 'kdnaform_preview_init', function ( $form_id ) use ( $container ) {
			$form_ids = array( $form_id );
			$container->get( self::CONFIG_COLLECTION )->handle( true, array( 'form_ids' => $form_ids ) );
			self::$is_localized = true;
		}, 10, 2);

		add_action('wp_ajax_kdnaform_get_config', function () use ( $container ) {
			$container->get( self::CONFIG_COLLECTION )->handle_ajax();
		});

		add_action('wp_ajax_nopriv_kdnaform_get_config', function () use ( $container ) {
			$container->get( self::CONFIG_COLLECTION )->handle_ajax();
		});

		add_action( 'rest_api_init', function () use ( $container, $self ) {
			// check if we are in a test environment, if so register the mock data endpoint.
			if ( defined( 'KDNA_SCRIPT_DEBUG' ) && KDNA_SCRIPT_DEBUG ) {
				register_rest_route( 'kdnaforms/v2', '/tests/mock-data', array( // nosemgrep audit.php.wp.security.rest-route.permission-callback.return-true
					'methods'             => 'GET',
					'callback'            => array( $self, 'config_mocks_endpoint' ),
					'permission_callback' => function () {
						return true;
					},
				) );
			}
		} );

		// Add global config data to admin and theme.
		add_filter( 'kdnaform_localized_script_data_gform_admin_config', function ( $data ) use ( $self ) {
			return $self->add_global_config_data( $data );
		} );

		add_filter( 'kdnaform_localized_script_data_gform_theme_config', function ( $data ) use ( $self ) {
			return $self->add_global_config_data( $data );
		} );
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
	private function register_config_items( KDNA_Service_Container $container ) {
		require_once( plugin_dir_path( __FILE__ ) . '/items/class-kdna-config-i18n.php' );
		require_once( plugin_dir_path( __FILE__ ) . '/items/class-kdna-config-admin.php' );
		require_once( plugin_dir_path( __FILE__ ) . '/items/class-kdna-config-legacy-check.php' );
		require_once( plugin_dir_path( __FILE__ ) . '/items/class-kdna-config-legacy-check-multi.php' );
		require_once( plugin_dir_path( __FILE__ ) . '/items/class-kdna-config-multifile.php' );

		$parser = $container->get( self::DATA_PARSER );

		foreach ( $this->configs as $name => $class ) {
			$container->add( $name, function () use ( $class, $parser ) {
				return new $class( $parser );
			} );
		}
	}

	/**
	 * Register each config defined in $configs to the KDNA_Config_Collection.
	 *
	 * @since 2.6
	 *
	 * @param KDNA_Service_Container $container
	 *
	 * @return void
	 */
	public function register_configs_to_collection( KDNA_Service_Container $container ) {
		$collection = $container->get( self::CONFIG_COLLECTION );

		foreach ( $this->configs as $name => $config ) {
			$config_class = $container->get( $name );
			$collection->add_config( $config_class );
		}
	}

	/**
	 * Callback for the Config Mocks REST endpoint.
	 *
	 * @since 2.6
	 *
	 * @return array
	 */
	public function config_mocks_endpoint() {
		define( 'GFORMS_DOING_MOCK', true );
		$container = \KDNAForms::get_service_container();
		$data      = $container->get( self::CONFIG_COLLECTION )->handle( false );

		return $data;
	}

	/**
	 * Add global data to both admin and theme configs so that it is available everywhere
	 * within the system.
	 *
	 * @since 2.7
	 *
	 * @param $data
	 *
	 * @return array
	 */
	public function add_global_config_data( $data ) {
		$container = \KDNAForms::get_service_container();
		$global    = $container->get( self::GLOBAL_CONFIG )->data();

		return array_merge( $data, $global );
	}
}
