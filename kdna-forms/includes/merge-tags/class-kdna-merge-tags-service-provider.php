<?php

namespace KDNA_Forms\KDNA_Forms\Merge_Tags;

use KDNA_Forms\KDNA_Forms\Config\KDNA_Config_Service_Provider;
use KDNA_Forms\KDNA_Forms\Merge_Tags\Config\KDNA_Merge_Tags_Config_I18N;

use KDNA_Forms\KDNA_Forms\KDNA_Service_Container;
use KDNA_Forms\KDNA_Forms\KDNA_Service_Provider;

/**
 * Class KDNA_Merge_Tags_Service_Provider
 *
 * Service provider for the Merge_Tags Service.
 *
 * @package KDNA_Forms\KDNA_Forms\Merge_Tags;
 */
class KDNA_Merge_Tags_Service_Provider extends KDNA_Service_Provider {

	// Configs
	const MERGE_TAGS_CONFIG_I18N = 'merge_tags_config_i18n';

	/**
	 * Array mapping config class names to their container ID.
	 *
	 * @since 2.6
	 *
	 * @var string[]
	 */
	protected $configs = array(
		self::MERGE_TAGS_CONFIG_I18N => KDNA_Merge_Tags_Config_I18N::class,
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
		require_once( plugin_dir_path( __FILE__ ) . '/config/class-kdna-merge-tags-config-i18n.php' );

		$this->add_configs( $container );
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