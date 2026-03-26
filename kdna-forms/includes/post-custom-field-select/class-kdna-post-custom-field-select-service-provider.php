<?php

namespace KDNA_Forms\KDNA_Forms\Post_Custom_Field_Select;

use KDNA_Forms\KDNA_Forms\Config\KDNA_Config_Service_Provider;
use KDNA_Forms\KDNA_Forms\KDNA_Service_Container;
use KDNA_Forms\KDNA_Forms\KDNA_Service_Provider;

defined( 'ABSPATH' ) || die();

/**
 * Class KDNA_Post_Custom_Field_Select_Service_Provider
 *
 * Service provider for the Post Custom Field Select functionality
 *
 * @since 2.9.20
 */
class KDNA_Post_Custom_Field_Select_Service_Provider extends KDNA_Service_Provider {

	const POST_CUSTOM_SELECT        = 'post_custom_select';
	const POST_CUSTOM_SELECT_CONFIG = 'post_custom_select_config';

	/**
	 * Register services to the container
	 *
	 * @since 2.9.20
	 *
	 * @param KDNA_Service_Container $container
	 */
	public function register( KDNA_Service_Container $container ) {
		require_once( plugin_dir_path( __FILE__ ) . 'class-kdna-post-custom-field-select.php' );
		require_once( plugin_dir_path( __FILE__ ) . 'class-kdna-post-custom-field-select-config.php' );

		$container->add( self::POST_CUSTOM_SELECT, function() {
			return new KDNA_Post_Custom_Field_Select();
		} );

		$container->add( self::POST_CUSTOM_SELECT_CONFIG, function() use ( $container ) {
			$data_parser = $container->get( KDNA_Config_Service_Provider::DATA_PARSER );
			return new KDNA_Post_Custom_Field_Select_Config( $data_parser );
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
		$container->get( self::POST_CUSTOM_SELECT )->init();

		if ( \KDNAForms::get_page() === 'form_editor' ) {
			$config_collection = $container->get( KDNA_Config_Service_Provider::CONFIG_COLLECTION );
			$config_collection->add_config( $container->get( self::POST_CUSTOM_SELECT_CONFIG ) );
		}
	}
}
