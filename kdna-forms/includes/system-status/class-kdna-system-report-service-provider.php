<?php

use KDNA_Forms\KDNA_Forms\Config\KDNA_Config_Service_Provider;
use KDNA_Forms\KDNA_Forms\KDNA_Service_Container;
use KDNA_Forms\KDNA_Forms\KDNA_Service_Provider;

/**
 * Class System_Report_Service_Provider
 *
 * Service provider for the system report.
 *
 * @package KDNA_Forms\KDNA_Forms\System_Report;
 */
class KDNA_System_Report_Service_Provider extends KDNA_Service_Provider {
	const SYSTEM_REPORT = 'system_report';

	/**
	 * Register services to the container.
	 *
	 * @since 2.7.1
	 *
	 * @param KDNA_Service_Container $container
	 */
	public function register( KDNA_Service_Container $container ) {
		require_once( plugin_dir_path( __FILE__ ) . '/class-kdna-system-report.php' );

		$this->system_report( $container );
	}

	/**
	 * Initialize any actions or hooks.
	 *
	 * @since 2.7.1
	 *
	 * @param KDNA_Service_Container $container
	 *
	 * @return void
	 */
	public function init( KDNA_Service_Container $container ) {

		add_action( 'admin_init', function () use ( $container ) {
			if ( KDNAForms::get_page_query_arg() == 'gf_system_status' ) {
				$container->get( self::SYSTEM_REPORT )->remove_emoji_script();
			}
		} );

	}

	/**
	 * Register System Report services.
	 *
	 * @since 2.7.1
	 *
	 * @param KDNA_Service_Container $container
	 *
	 * @return void
	 */
	private function system_report( KDNA_Service_Container $container ) {

		$container->add( self::SYSTEM_REPORT, function () use ( $container ) {
			return new KDNA_System_Report();
		} );
	}

}
