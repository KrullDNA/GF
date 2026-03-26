<?php

namespace KDNA_Forms\KDNA_Forms\Form_Switcher;

use KDNA_Forms\KDNA_Forms\Config\KDNA_Config_Service_Provider;
use KDNA_Forms\KDNA_Forms\Form_Switcher\Config\KDNA_Form_Switcher_Config;
use KDNA_Forms\KDNA_Forms\Form_Switcher\Endpoints\KDNA_Form_Switcher_Endpoint_Get_Forms;

use KDNA_Forms\KDNA_Forms\KDNA_Service_Container;
use KDNA_Forms\KDNA_Forms\KDNA_Service_Provider;

/**
 * Class KDNA_Form_Switcher_Service_Provider
 *
 * Service provider for the Form_Switcher Service.
 *
 * @package KDNA_Forms\KDNA_Forms\Form_Switcher;
 */
class KDNA_Form_Switcher_Service_Provider extends KDNA_Service_Provider {

	// Configs
	const FORM_SWITCHER_CONFIG = 'form_switcher_config';

	const ENDPOINT_GET_FORMS = 'endpoint_get_forms';

	/**
	 * Array mapping config class names to their container ID.
	 *
	 * @since 2.9.6
	 *
	 * @var string[]
	 */
	protected $configs = array(
		self::FORM_SWITCHER_CONFIG => KDNA_Form_Switcher_Config::class,
	);

	/**
	 * Register services to the container.
	 *
	 * @since 2.9.6
	 *
	 * @param KDNA_Service_Container $container
	 */
	public function register( KDNA_Service_Container $container ) {
		// Configs
		require_once( plugin_dir_path( __FILE__ ) . '/config/class-kdna-form-switcher-config.php' );
		$this->add_configs( $container );

		// Endpoints
		require_once( plugin_dir_path( __FILE__ ) . '/endpoints/class-kdna-form-switcher-endpoint-get-forms.php' );
		$this->add_endpoints( $container );
	}

	/**
	 * Initialize any actions or hooks.
	 *
	 * @since 2.9.6
	 *
	 * @param KDNA_Service_Container $container
	 *
	 * @return void
	 */
	public function init( KDNA_Service_Container $container ) {
		add_action( 'wp_ajax_' . KDNA_Form_Switcher_Endpoint_Get_Forms::ACTION_NAME, function () use ( $container ) {
			$container->get( self::ENDPOINT_GET_FORMS )->handle();
		} );
	}

	/**
	 * For each config defined in $configs, instantiate and add to container.
	 *
	 * @since 2.9.6
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

	/**
	 * For each endpoint defined in $endpoints, instantiate and add to container.
	 *
	 * @since 2.9.6
	 *
	 * @param KDNA_Service_Container $container
	 *
	 * @return void
	 */
	private function add_endpoints( KDNA_Service_Container $container ) {
		$container->add( self::ENDPOINT_GET_FORMS, function () {
			return new KDNA_Form_Switcher_Endpoint_Get_Forms();
		} );
	}

}
