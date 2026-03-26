<?php

namespace KDNA_Forms\KDNA_Forms\Environment_Config;

use KDNA_Forms\KDNA_Forms\KDNA_Service_Container;
use KDNA_Forms\KDNA_Forms\KDNA_Service_Provider;
use KDNA_Forms\KDNA_Forms\Util\KDNA_Util_Service_Provider;

/**
 * Class KDNA_Environment_Config_Service_Provider
 *
 * Service provider for the Environment_Config Service.
 *
 * @package KDNA_Forms\KDNA_Forms\Environment_Config;
 */
class KDNA_Environment_Config_Service_Provider extends KDNA_Service_Provider {

	const GF_ENVIRONMENT_CONFIG_HANDLER = 'gf_environment_config_handler';

	/**
	 * Register services to the container.
	 *
	 * @since 2.7
	 *
	 * @param KDNA_Service_Container $container Service Container.
	 */
	public function register( KDNA_Service_Container $container ) {
		require_once plugin_dir_path( __FILE__ ) . 'class-kdna-environment-config-handler.php';

		$container->add(
			self::GF_ENVIRONMENT_CONFIG_HANDLER,
			function () use ( $container ) {
				return new KDNA_Environment_Config_Handler( $container->get( KDNA_Util_Service_Provider::GF_CACHE ) );
			}
		);
	}

	/**
	 * Initiailize any actions or hooks.
	 *
	 * @since 2.7
	 *
	 * @param KDNA_Service_Container $container Service Container.
	 *
	 * @return void
	 */
	public function init( KDNA_Service_Container $container ) {

		$handler = $container->get( self::GF_ENVIRONMENT_CONFIG_HANDLER );

		// Gets environment license key.
		add_filter( 'pre_option_kdna_forms_key', array( $handler, 'maybe_override_kdna_forms_key' ) );

		// Maybe bypass installation wizard.
		add_filter( 'pre_option_kdnaform_pending_installation', array( $handler, 'maybe_override_kdnaform_pending_installation' ) );

		// Maybe hides license key setting and license key details.
		add_filter( 'kdnaform_plugin_settings_fields', array( $handler, 'maybe_hide_setting' ) );

		// Maybe hide plugin auto update messages.
		add_filter( 'init', array( $handler, 'maybe_hide_plugin_page_message' ), 20 );
		add_filter( 'kdnaform_updates_list', array( $handler, 'maybe_hide_update_page_message' ), 20 );
	}
}
