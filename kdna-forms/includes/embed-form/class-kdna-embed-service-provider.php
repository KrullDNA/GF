<?php

namespace KDNA_Forms\KDNA_Forms\Embed_Form;

use KDNA_Forms\KDNA_Forms\Config\KDNA_Config_Service_Provider;
use KDNA_Forms\KDNA_Forms\Embed_Form\Config\KDNA_Embed_Config;
use KDNA_Forms\KDNA_Forms\Embed_Form\Config\KDNA_Embed_Config_I18N;
use KDNA_Forms\KDNA_Forms\Embed_Form\Config\KDNA_Embed_Endpoints_Config;
use KDNA_Forms\KDNA_Forms\Embed_Form\Dom\KDNA_Embed_Button;
use KDNA_Forms\KDNA_Forms\Embed_Form\Endpoints\KDNA_Embed_Endpoint_Create_With_Block;
use KDNA_Forms\KDNA_Forms\Embed_Form\Endpoints\KDNA_Embed_Endpoint_Get_Posts;
use KDNA_Forms\KDNA_Forms\KDNA_Service_Container;
use KDNA_Forms\KDNA_Forms\KDNA_Service_Provider;

/**
 * Class KDNA_Embed_Service_Provider
 *
 * Service provider for the Embed Form Service.
 *
 * @package KDNA_Forms\KDNA_Forms\Embed_Form;
 */
class KDNA_Embed_Service_Provider extends KDNA_Service_Provider {

		// Configs
		const EMBED_CONFIG           = 'embed_config';
		const EMBED_CONFIG_I18N      = 'embed_config_i18n';
		const EMBED_CONFIG_ENDPOINTS = 'embed_config_endpoints';

		// Endpoints
		const ENDPOINT_GET_POSTS         = 'endpoint_get_posts';
		const ENDPOINT_CREATE_WITH_BLOCK = 'endpoint_create_with_block';

	// DOM
	const DOM_EMBED_BUTTON = 'dom_embed_button';

	// Strings
	const ADD_BLOCK_PARAM = 'gfAddBlock';

	/**
	 * Array mapping config class names to their container ID.
	 *
	 * @since 2.6
	 *
	 * @var string[]
	 */
	protected $configs = array(
		self::EMBED_CONFIG           => KDNA_Embed_Config::class,
		self::EMBED_CONFIG_I18N      => KDNA_Embed_Config_I18N::class,
		self::EMBED_CONFIG_ENDPOINTS => KDNA_Embed_Endpoints_Config::class,
	);

	/**
	 * Register services to the container.
	 *
	 * @since 2.6
	 *
	 * @param KDNA_Service_Container $container
	 */
	public function register( KDNA_Service_Container $container ) {
		// Configs
		require_once( plugin_dir_path( __FILE__ ) . '/config/class-kdna-embed-config.php' );
		require_once( plugin_dir_path( __FILE__ ) . '/config/class-kdna-embed-config-i18n.php' );
		require_once( plugin_dir_path( __FILE__ ) . '/config/class-kdna-embed-endpoints-config.php' );

		// Endpoints
		require_once( plugin_dir_path( __FILE__ ) . '/endpoints/class-kdna-embed-endpoint-get-posts.php' );
		require_once( plugin_dir_path( __FILE__ ) . '/endpoints/class-kdna-embed-endpoint-create-with-block.php' );

		// Dom
		require_once( plugin_dir_path( __FILE__ ) . '/dom/class-kdna-embed-button.php' );

		$this->add_configs( $container );
		$this->add_endpoints( $container );
		$this->dom( $container );
	}

	/**
	 * Initiailize any actions or hooks.
	 *
	 * @since 2.6
	 *
	 * @param KDNA_Service_Container $container
	 *
	 * @return void
	 */
	public function init( KDNA_Service_Container $container ) {
		add_action( 'wp_ajax_' . KDNA_Embed_Endpoint_Get_Posts::ACTION_NAME, function () use ( $container ) {
			$container->get( self::ENDPOINT_GET_POSTS )->handle();
		} );

		add_action( 'wp_ajax_' . KDNA_Embed_Endpoint_Create_With_Block::ACTION_NAME, function () use ( $container ) {
			$container->get( self::ENDPOINT_CREATE_WITH_BLOCK )->handle();
		} );

		add_action( 'kdnaform_before_toolbar_buttons', function () use ( $container ) {
			$container->get( self::DOM_EMBED_BUTTON )->output_button();
		} );
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

	/**
	 * Register AJAX endpoints for the Embed UI.
	 *
	 * @since 2.6
	 *
	 * @param KDNA_Service_Container $container
	 *
	 * @return void
	 */
	private function add_endpoints( KDNA_Service_Container $container ) {
		$container->add( self::ENDPOINT_GET_POSTS, function () use ( $container ) {
			return new KDNA_Embed_Endpoint_Get_Posts();
		} );

		$container->add( self::ENDPOINT_CREATE_WITH_BLOCK, function () use ( $container ) {
			return new KDNA_Embed_Endpoint_Create_With_Block();
		} );
	}

	/**
	 * Register DOM-related services.
	 *
	 * @since 2.6
	 *
	 * @param KDNA_Service_Container $container
	 *
	 * @return void
	 */
	private function dom( KDNA_Service_Container $container ) {
		$container->add( self::DOM_EMBED_BUTTON, function() {
			return new KDNA_Embed_Button();
		});
	}

}
