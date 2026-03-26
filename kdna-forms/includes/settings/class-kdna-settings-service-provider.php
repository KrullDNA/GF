<?php

namespace KDNA_Forms\KDNA_Forms\Settings;

use KDNA_Forms\KDNA_Forms\Config\KDNA_Config_Service_Provider;
use KDNA_Forms\KDNA_Forms\Settings\Config\KDNA_Settings_Config_Admin;
use KDNA_Forms\KDNA_Forms\Settings\Config\KDNA_Settings_Config_I18N;

use KDNA_Forms\KDNA_Forms\KDNA_Service_Container;
use KDNA_Forms\KDNA_Forms\KDNA_Service_Provider;

use KDNACommon;

/**
 * Class KDNA_Settings_Service_Provider
 *
 * Service provider for the Settings Service.
 *
 * @package KDNA_Forms\KDNA_Forms\Settings;
 */
class KDNA_Settings_Service_Provider extends KDNA_Service_Provider {

	const SETTINGS = 'settings';

	// Configs
	const SETTINGS_CONFIG_I18N  = 'settings_config_i18n';
	const SETTINGS_CONFIG_ADMIN = 'settings_config_admin';

	// Encryption utils
	const SETTINGS_ENCRYPTION = 'settings_encryption';

	/**
	 * Array mapping config class names to their container ID.
	 *
	 * @since 2.6
	 *
	 * @var string[]
	 */
	protected $configs = array(
		self::SETTINGS_CONFIG_I18N  => KDNA_Settings_Config_I18N::class,
		self::SETTINGS_CONFIG_ADMIN => KDNA_Settings_Config_Admin::class,
	);

	/**
	 * Register services to the container.
	 *
	 * @since 2.6
	 *
	 * @param KDNA_Service_Container $container
	 */
	public function register( KDNA_Service_Container $container ) {
		// Settings class
		if ( ! KDNACommon::is_form_editor() ) { // Loading the settings API in the form editor causes some unwanted filters to run.
			require_once( plugin_dir_path( __FILE__ ) . '/class-settings.php' );
			$container->add( self::SETTINGS, function() {

				return new Settings();
			} );
		}

		// Encryption utils
		require_once( plugin_dir_path( __FILE__ ) . '/class-kdna-settings-encryption.php' );
		$container->add( self::SETTINGS_ENCRYPTION, function () {
			return new KDNA_Settings_Encryption();
		} );


		// Configs
		require_once( plugin_dir_path( __FILE__ ) . '/config/class-kdna-settings-config-i18n.php' );
		require_once( plugin_dir_path( __FILE__ ) . '/config/class-kdna-settings-config-admin.php' );

		$this->add_configs( $container );
	}

	/**
	 * Initialize any actions or hooks.
	 *
	 * @since 2.9.5
	 *
	 * @param KDNA_Service_Container $container
	 *
	 * @return void
	 */
	public function init( KDNA_Service_Container $container ) {
		add_filter( 'rest_user_query', function ( $prepared_args, $request ) use ( $container ) {
			return $container->get( self::SETTINGS )->remove_has_published_posts_from_api_user_query( $prepared_args, $request );
		}, 10, 2 );
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
