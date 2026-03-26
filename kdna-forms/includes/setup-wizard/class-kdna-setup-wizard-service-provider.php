<?php

namespace KDNA_Forms\KDNA_Forms\Setup_Wizard;

use KDNA_Forms\KDNA_Forms\Config\KDNA_Config_Service_Provider;
use KDNA_Forms\KDNA_Forms\Embed_Form\Config\KDNA_Setup_Wizard_Endpoints_Config;
use KDNA_Forms\KDNA_Forms\Setup_Wizard\Config\KDNA_Setup_Wizard_Config;

use KDNA_Forms\KDNA_Forms\KDNA_Service_Container;
use KDNA_Forms\KDNA_Forms\KDNA_Service_Provider;
use KDNA_Forms\KDNA_Forms\Setup_Wizard\Config\KDNA_Setup_Wizard_Config_I18N;
use KDNA_Forms\KDNA_Forms\Setup_Wizard\Endpoints\KDNA_Setup_Wizard_Endpoint_Save_Prefs;
use KDNA_Forms\KDNA_Forms\Setup_Wizard\Endpoints\KDNA_Setup_Wizard_Endpoint_Validate_License;
// use KDNA_Forms\KDNA_Forms\License\KDNA_License_Service_Provider; // Removed - license functionality removed.

/**
 * Class KDNA_Setup_Wizard_Service_Provider
 *
 * Service provider for the Setup_Wizard Service.
 *
 * @package KDNA_Forms\KDNA_Forms\Setup_Wizard;
 */
class KDNA_Setup_Wizard_Service_Provider extends KDNA_Service_Provider {

	// Configs
	const SETUP_WIZARD_CONFIG           = 'setup_wizard_config';
	const SETUP_WIZARD_ENDPOINTS_CONFIG = 'setup_wizard_endpoints_config';
	const SETUP_WIZARD_CONFIG_I18N      = 'setup_wizard_config_i18n';

	// Endpoints
	const SAVE_PREFS_ENDPOINT       = 'setup_wizard_save_prefs_endpoint';
	const VALIDATE_LICENSE_ENDPOINT = 'setup_wizard_validate_license_endpoint';

	/**
	 * Array mapping config class names to their container ID.
	 *
	 * @since
	 *
	 * @var string[]
	 */
	protected $configs = array(
		self::SETUP_WIZARD_CONFIG           => KDNA_Setup_Wizard_Config::class,
		self::SETUP_WIZARD_ENDPOINTS_CONFIG => KDNA_Setup_Wizard_Endpoints_Config::class,
		self::SETUP_WIZARD_CONFIG_I18N      => KDNA_Setup_Wizard_Config_I18N::class,
	);

	/**
	 * Register services to the container.
	 *
	 * @since
	 *
	 * @param KDNA_Service_Container $container
	 */
	public function register( KDNA_Service_Container $container ) {
		// Configs
		require_once( plugin_dir_path( __FILE__ ) . '/config/class-kdna-setup-wizard-config.php' );
		require_once( plugin_dir_path( __FILE__ ) . '/config/class-kdna-setup-wizard-endpoints-config.php' );
		require_once( plugin_dir_path( __FILE__ ) . '/config/class-kdna-setup-wizard-config-i18n.php' );

		// Endpoints
		require_once( plugin_dir_path( __FILE__ ) . '/endpoints/class-kdna-setup-wizard-endpoint-save-prefs.php' );
		require_once( plugin_dir_path( __FILE__ ) . '/endpoints/class-kdna-setup-wizard-endpoint-validate-license.php' );

		$container->add( self::SAVE_PREFS_ENDPOINT, function () {
			$api = new \Gravity_Api();
			return new KDNA_Setup_Wizard_Endpoint_Save_Prefs( $api );
		} );

		$container->add( self::VALIDATE_LICENSE_ENDPOINT, function () use ( $container ) {
			return new KDNA_Setup_Wizard_Endpoint_Validate_License(); // License functionality removed.
		} );

		$this->register_wizard_app( $container );
		$this->add_configs( $container );
	}

	private function register_wizard_app( KDNA_Service_Container $container ) {
		$dev_min = defined( 'KDNA_SCRIPT_DEBUG' ) && KDNA_SCRIPT_DEBUG ? '' : '.min';

		$args = array(
			'app_name'     => 'setup_wizard',
			'script_name'  => 'kdnaform_kdnaforms_admin_vendors',
			'object_name'  => 'kdnaform_admin_config',
			'chunk'        => './setup-wizard',
			'enqueue'      => array( $this, 'should_enqueue_setup_wizard' ),
			'css'          => array(
				'handle' => 'setup_wizard_styles',
				'src'    => \KDNACommon::get_base_url() . "/assets/css/dist/setup-wizard{$dev_min}.css",
				'deps'   => array( 'kdnaform_admin_components' ),
				'ver'    => defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? filemtime( \KDNACommon::get_base_path() . "/assets/css/dist/setup-wizard{$dev_min}.css" ) : \KDNAForms::$version,
			),
			'root_element' => 'setup-wizard',
		);

		$this->register_app( $args );
	}

	public function should_enqueue_setup_wizard() {
		if ( ! \KDNAForms::is_kdna_page() ) {
			return false;
		}

		$page = \KDNAForms::get_page_query_arg();

		// Don't display on the system status page.
		if ( $page == 'gf_system_status' ) {
			return false;
		}

		if ( $page == 'gf_settings' && rgar( $_COOKIE, KDNA_Setup_Wizard_Config::INVALID_KEY_COOKIE ) ) {
			return false;
		}

		if ( defined( 'KDNA_DISPLAY_SETUP_WIZARD' ) && KDNA_DISPLAY_SETUP_WIZARD ) {
			return true;
		}

		return (bool) get_option( 'kdnaform_pending_installation' );
	}

	/**
	 * Initialize any actions or hooks.
	 *
	 * @since
	 *
	 * @param KDNA_Service_Container $container
	 *
	 * @return void
	 */
	public function init( KDNA_Service_Container $container ) {
		add_action( 'wp_ajax_' . KDNA_Setup_Wizard_Endpoint_Save_Prefs::ACTION_NAME, function () use ( $container ) {
			$container->get( self::SAVE_PREFS_ENDPOINT )->handle();
		} );

		add_action( 'wp_ajax_' . KDNA_Setup_Wizard_Endpoint_Validate_License::ACTION_NAME, function () use ( $container ) {
			$container->get( self::VALIDATE_LICENSE_ENDPOINT )->handle();
		} );
	}

	/**
	 * For each config defined in $configs, instantiate and add to container.
	 *
	 * @since
	 *
	 * @param KDNA_Service_Container $container
	 *
	 * @return void
	 */
	private function add_configs( KDNA_Service_Container $container ) {
		foreach ( $this->configs as $name => $class ) {
			$container->add( $name, function () use ( $container, $class ) {
				return new $class( $container->get( KDNA_Config_Service_Provider::DATA_PARSER ) );
			} );

			$container->get( KDNA_Config_Service_Provider::CONFIG_COLLECTION )->add_config( $container->get( $name ) );
		}
	}

}

