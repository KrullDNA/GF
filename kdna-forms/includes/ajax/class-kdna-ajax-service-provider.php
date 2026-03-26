<?php
/**
 * Service Provider for AJAX Service
 *
 * @package KDNA_Forms\KDNA_Forms\Ajax
 */

namespace KDNA_Forms\KDNA_Forms\Ajax;

use KDNA_Forms\KDNA_Forms\Config\KDNA_Config_Service_Provider;
use KDNA_Forms\KDNA_Forms\KDNA_Service_Container;
use KDNA_Forms\KDNA_Forms\KDNA_Service_Provider;
use KDNA_Forms\KDNA_Forms\Ajax\Config\KDNA_Ajax_Config;

/**
 * Class KDNA_Ajax_Service_Provider
 *
 * Service provider for the Ajax Service.
 */
class KDNA_Ajax_Service_Provider extends KDNA_Service_Provider {

	const GF_AJAX_HANDLER = 'gf_ajax_handler';
	const GF_AJAX_CONFIG  = 'gf_ajax_config';

	/**
	 * Includes all related files and adds all containers.
	 *
	 * @param KDNA_Service_Container $container Container singleton object.
	 */
	public function register( KDNA_Service_Container $container ) {

		require_once plugin_dir_path( __FILE__ ) . 'class-kdna-ajax-handler.php';
		require_once plugin_dir_path( __FILE__ ) . 'config/class-kdna-ajax-config.php';

		// Registering handler
		$container->add(
			self::GF_AJAX_HANDLER,
			function () {
				return new KDNA_Ajax_Handler();
			}
		);

		// Registering config
		$container->add(
			self::GF_AJAX_CONFIG,
			function () use ( $container ) {
				return new KDNA_Ajax_Config( $container->get( KDNA_Config_Service_Provider::DATA_PARSER ) );
			}
		);
		$container->get( KDNA_Config_Service_Provider::CONFIG_COLLECTION )->add_config( $container->get( self::GF_AJAX_CONFIG ) );

	}

	/**
	 * Initializes service.
	 *
	 * @param KDNA_Service_Container $container Service Container.
	 */
	public function init( KDNA_Service_Container $container ) {
		parent::init( $container );

		$ajax_handler = $container->get( self::GF_AJAX_HANDLER );

		// Register nonce lifespan hook.
		add_filter( 'nonce_life', array( $ajax_handler, 'nonce_life' ), 10, 2 );

		// Register AJAX submission.
		add_action( 'wp_ajax_kdnaform_submit_form', array( $ajax_handler, 'submit_form' ) );
		add_action( 'wp_ajax_nopriv_kdnaform_submit_form', array( $ajax_handler, 'submit_form' ) );
	}
}
