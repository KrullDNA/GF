<?php


namespace KDNA_Forms\KDNA_Forms\Splash_Page;

use KDNA_Forms\KDNA_Forms\Config\KDNA_Config_Service_Provider;
use KDNA_Forms\KDNA_Forms\Splash_Page\Config\KDNA_Splash_Page_Config;
use KDNA_Forms\KDNA_Forms\KDNA_Service_Container;
use KDNA_Forms\KDNA_Forms\KDNA_Service_Provider;
use KDNA_Forms\KDNA_Forms\Splash_Page_Template_Tags\KDNA_Splash_Page_Template_Tags;

/**
 * Class Splash_Page_Service_Provider
 *
 * Service provider for the splash page.
 *
 * @package KDNA_Forms\KDNA_Forms\Splash_Page;
 */
class KDNA_Splash_Page_Service_Provider extends KDNA_Service_Provider {
	const SPLASH_PAGE               = 'splash_page';
	const SPLASH_PAGE_TEMPLATE_TAGS = 'splash_page_template_tags';

	/**
	 * Register services to the container.
	 *
	 * @since 2.6
	 *
	 * @param KDNA_Service_Container $container
	 */
	public function register( KDNA_Service_Container $container ) {
		require_once( plugin_dir_path( __FILE__ ) . 'class-kdna-splash-page-template-tags.php' );
		require_once( plugin_dir_path( __FILE__ ) . '/class-kdna-splash-page.php' );

		$this->splash_page( $container );
	}

	/**
	 * Initialize any actions or hooks.
	 *
	 * @since 2.6
	 *
	 * @param KDNA_Service_Container $container
	 *
	 * @return void
	 */
	public function init( KDNA_Service_Container $container ) {

		add_action( 'admin_enqueue_scripts', function () use ( $container ) {
			$container->get( self::SPLASH_PAGE )->splash_page_styles();
		} );

		add_filter( 'admin_body_class', function ( $classes ) use ( $container ) {
			return $container->get( self::SPLASH_PAGE )->body_class( $classes );
		}, 10, 1 );

		add_filter( 'admin_title', function ( $title ) use ( $container ) {
			return $container->get( self::SPLASH_PAGE )->admin_title( $title );
		}, 10, 1 );

		add_filter( 'kdnaform_system_status_menu', function ( $subviews ) use ( $container ) {
			return $container->get( self::SPLASH_PAGE )->system_status_link( $subviews );
		}, 10, 1 );

		add_action( 'kdnaform_system_status_page_about', function () use ( $container ) {
			$container->get( self::SPLASH_PAGE )->about_page();
		} );

		add_action( 'kdnaform_post_upgrade', function ( $version, $from_db_version, $force_upgrade ) use ( $container ) {
			$container->get( self::SPLASH_PAGE )->set_upgrade_transient( $version, $from_db_version, $force_upgrade );
		}, 10, 3 );

		add_action( 'admin_footer', function () use ( $container ) {
			echo $container->get( self::SPLASH_PAGE )->about_page_modal(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}, 10, 0 );
	}

	/**
	 * Register Splash Page services.
	 *
	 * @since 2.6
	 *
	 * @param KDNA_Service_Container $container
	 *
	 * @return void
	 */
	private function splash_page( KDNA_Service_Container $container ) {
		$container->add( self::SPLASH_PAGE_TEMPLATE_TAGS, function () {
			return new KDNA_Splash_Page_Template_Tags();
		} );

		$container->add( self::SPLASH_PAGE, function () use ( $container ) {
			$tags = $container->get( self::SPLASH_PAGE_TEMPLATE_TAGS );

			return new KDNA_Splash_Page( $tags );
		} );
	}

}
