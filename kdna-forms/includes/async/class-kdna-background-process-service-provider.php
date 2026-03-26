<?php
/**
 * Service provider for async (background) processors.
 *
 * @package KDNA_Forms\KDNA_Forms
 */

namespace KDNA_Forms\KDNA_Forms\Async;

use KDNA_Forms\KDNA_Forms\KDNA_Service_Container;
use KDNA_Forms\KDNA_Forms\KDNA_Service_Provider;
use KDNA_Forms\KDNA_Forms\Telemetry\KDNA_Telemetry_Processor;
use KDNAForms;
use KDNA_Background_Upgrader;
use KDNA_Feed_Processor;

if ( ! class_exists( 'KDNAForms' ) ) {
	die();
}

/**
 * Class KDNA_Background_Processing_Service_Provider
 *
 * @since 2.6.9
 */
class KDNA_Background_Process_Service_Provider extends KDNA_Service_Provider {

	const UPGRADER = 'upgrade_processor';
	const FEEDS = 'feeds_processor';
	const NOTIFICATIONS = 'notifications_processor';
	const TELEMETRY = 'telemetry_processor';

	/**
	 * The names and classes of the async (background) processors.
	 *
	 * @since 2.6.9
	 *
	 * @var string[]
	 */
	protected $processors = array(
		self::UPGRADER      => KDNA_Background_Upgrader::class,
		self::FEEDS         => KDNA_Feed_Processor::class,
		self::NOTIFICATIONS => GF_Notifications_Processor::class,
		self::TELEMETRY     => KDNA_Telemetry_Processor::class,
	);

	/**
	 * Initializing the processors and adding them to the container as services.
	 *
	 * @since 2.6.9
	 *
	 * @param KDNA_Service_Container $container
	 */
	public function register( KDNA_Service_Container $container ) {
		KDNAForms::init_background_upgrader();
		require_once KDNA_PLUGIN_DIR_PATH . 'includes/addon/class-kdna-feed-processor.php';
		require_once KDNA_PLUGIN_DIR_PATH . 'includes/async/class-kdna-notifications-processor.php';
		require_once KDNA_PLUGIN_DIR_PATH . 'includes/telemetry/class-kdna-telemetry-processor.php';

		foreach ( $this->processors as $name => $class ) {
			$container->add( $name, function () use ( $name, $class ) {
				if ( $name === self::UPGRADER ) {
					return KDNAForms::$background_upgrader;
				}

				$callback = array( $class, 'get_instance' );
				if ( is_callable( $callback ) ) {
					return call_user_func( $callback );
				}

				return new $class();
			} );
		}
	}

}
