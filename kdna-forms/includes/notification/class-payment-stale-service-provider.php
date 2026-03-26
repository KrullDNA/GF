<?php
/**
 * Service Provider for Payment Stale Notification Service
 *
 * @package KDNA_Forms\KDNA_Forms\Notification
 */

namespace KDNA_Forms\KDNA_Forms\Notification;

use KDNA_Forms\KDNA_Forms\KDNA_Service_Container;
use KDNA_Forms\KDNA_Forms\KDNA_Service_Provider;
use KDNA_Forms\KDNA_Forms\Notification\Payment_Stale_Handler;

/**
 * Class Payment_Stale_Service_Provider
 *
 * Service provider for Payment Stale Notification Service.
 */
class Payment_Stale_Service_Provider extends KDNA_Service_Provider {

	const PAYMENT_STALE_HANDLER = 'gf_ajax_handler';

	/**
	 * Includes all related files and adds all containers.
	 *
	 * @param KDNA_Service_Container $container Container singleton object.
	 */
	public function register( KDNA_Service_Container $container ) {

		require_once plugin_dir_path( __FILE__ ) . 'class-payment-stale-handler.php';

		// Registering handler
		$container->add(
			self::PAYMENT_STALE_HANDLER,
			function () {
				return new Payment_Stale_Handler();
			}
		);
	}

	/**
	 * Initializes service.
	 *
	 * @param KDNA_Service_Container $container Service Container.
	 */
	public function init( KDNA_Service_Container $container ) {
		parent::init( $container );

		$handler = $container->get( self::PAYMENT_STALE_HANDLER );

		// Enables executing the cron job for debugging purposes. Requires a "_execute_stale_payment_cron" query string parameter and KDNA_DEBUG to be defined as true.
		add_action( 'admin_init', [ $handler, 'maybe_execute_cron' ] );

		// Handles logic when an entry's payment status changes.
		add_action( 'kdnaform_post_payment_status_change', [ $handler, 'handle_payment_status_change' ] );

		// Deactivate cron when Gravity Form is uninstalled.
		add_action( 'kdnaform_uninstalling', [ $handler, 'deactivate_cron' ] );
	}
}
