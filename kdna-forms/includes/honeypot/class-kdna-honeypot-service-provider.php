<?php
/**
 * Service Provider for Honeypot Service
 *
 * @package KDNA_Forms\KDNA_Forms\Honeypot
 */

namespace KDNA_Forms\KDNA_Forms\Honeypot;

use KDNA_Forms\KDNA_Forms\Config\KDNA_Config_Service_Provider;
use KDNA_Forms\KDNA_Forms\KDNA_Service_Container;
use KDNA_Forms\KDNA_Forms\KDNA_Service_Provider;
use KDNA_Forms\KDNA_Forms\Honeypot\Config\KDNA_Honeypot_Config;
use KDNA_Forms\KDNA_Forms\Util\KDNA_Util_Service_Provider;

/**
 * Class KDNA_Honeypot_Service_Provider
 *
 * Service provider for the Honeypot Service.
 */
class KDNA_Honeypot_Service_Provider extends KDNA_Service_Provider {

	const KDNA_HONEYPOT_HANDLER = 'kdna_honeypot_handler';

	// configs
	const GF_HONEYPOT_CONFIG = 'gf_honeypot_config';

	/**
	 * Array mapping config class names to their container ID.
	 *
	 * @since 2.6
	 *
	 * @var string[]
	 */
	protected $configs = array(
		self::GF_HONEYPOT_CONFIG => KDNA_Honeypot_Config::class,
	);

	/**
	 * Includes all related files and adds all containers.
	 *
	 * @param KDNA_Service_Container $container Container singleton object.
	 */
	public function register( KDNA_Service_Container $container ) {

		require_once plugin_dir_path( __FILE__ ) . 'class-kdna-honeypot-handler.php';
		require_once plugin_dir_path( __FILE__ ) . 'config/class-kdna-honeypot-config.php';

		$container->add(
			self::KDNA_HONEYPOT_HANDLER,
			function () {
				return new KDNA_Honeypot_Handler( \KDNACommon::get_base_url() );
			}
		);

		$this->add_configs( $container );
	}

	/**
	 * Initializes service.
	 *
	 * @param KDNA_Service_Container $container Service Container.
	 */
	public function init( KDNA_Service_Container $container ) {
		parent::init( $container );

		$honeypot_handler = $container->get( self::KDNA_HONEYPOT_HANDLER );

		// Maybe abort early. If configured not to create entry.
		add_filter( 'kdnaform_abort_submission_with_confirmation', array( $honeypot_handler, 'handle_abort_submission' ), 10, 2 );

		// Marks entry as spam.
		add_filter( 'kdnaform_entry_is_spam', array( $honeypot_handler, 'handle_entry_is_spam' ), 1, 2 );

		// Clear validation cache.
		add_action( 'kdnaform_after_submission', array( $honeypot_handler, 'handle_after_submission' ), 10, 2 );

		add_filter( 'kdnaform_entry_meta', array( $honeypot_handler, 'submission_speeds_entry_meta' ) );
		add_filter( 'kdnaform_entries_field_value', array( $honeypot_handler, 'submission_speeds_entries_field_value' ), 10, 4 );
		add_filter( 'gform_entry_detail_meta_boxes', array( $honeypot_handler, 'submission_speeds_entry_detail_meta_box' ), 10, 2 );
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
		foreach ( $this->configs as $name => $class ) {
			$container->add( $name, function () use ( $container, $class ) {
				return new $class( $container->get( KDNA_Config_Service_Provider::DATA_PARSER ) );
			} );

			$container->get( KDNA_Config_Service_Provider::CONFIG_COLLECTION )->add_config( $container->get( $name ) );
		}
	}
}
