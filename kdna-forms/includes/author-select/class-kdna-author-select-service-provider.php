<?php

namespace KDNA_Forms\KDNA_Forms\Author_Select;

use KDNA_Forms\KDNA_Forms\Config\KDNA_Config_Service_Provider;
use KDNA_Forms\KDNA_Forms\KDNA_Service_Container;
use KDNA_Forms\KDNA_Forms\KDNA_Service_Provider;

defined( 'ABSPATH' ) || die();

/**
 * Class KDNA_Author_Select_Service_Provider
 *
 * Service provider for the Author Select functionality
 *
 * @since 2.9.20
 */
class KDNA_Author_Select_Service_Provider extends KDNA_Service_Provider {

	const AUTHOR_SELECT        = 'author_select';
	const AUTHOR_SELECT_CONFIG = 'author_select_config';

	/**
	 * Register services to the container
	 *
	 * @since 2.9.20
	 *
	 * @param KDNA_Service_Container $container
	 */
	public function register( KDNA_Service_Container $container ) {
		require_once( plugin_dir_path( __FILE__ ) . 'class-kdna-author-select.php' );
		require_once( plugin_dir_path( __FILE__ ) . 'class-kdna-author-select-config.php' );

		$container->add( self::AUTHOR_SELECT, function() {
			return new KDNA_Author_Select();
		} );

		$container->add( self::AUTHOR_SELECT_CONFIG, function() use ( $container ) {
			$data_parser = $container->get( KDNA_Config_Service_Provider::DATA_PARSER );
			return new KDNA_Author_Select_Config( $data_parser );
		} );
	}

	/**
	 * Initialize any actions or hooks
	 *
	 * @since 2.9.20
	 *
	 * @param KDNA_Service_Container $container
	 */
	public function init( KDNA_Service_Container $container ) {
		$container->get( self::AUTHOR_SELECT )->init();

		if ( \KDNAForms::get_page() === 'form_editor' ) {
			$config_collection = $container->get( KDNA_Config_Service_Provider::CONFIG_COLLECTION );
			$config_collection->add_config( $container->get( self::AUTHOR_SELECT_CONFIG ) );
		}
	}
}