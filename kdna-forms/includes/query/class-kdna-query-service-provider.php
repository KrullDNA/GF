<?php

namespace KDNA_Forms\KDNA_Forms\Query;

use KDNA_Forms\KDNA_Forms\KDNA_Service_Container;
use KDNA_Forms\KDNA_Forms\KDNA_Service_Provider;

use KDNA_Forms\KDNA_Forms\Query\JSON_Handlers\KDNA_String_JSON_Handler;
use KDNA_Forms\KDNA_Forms\Query\JSON_Handlers\KDNA_Query_JSON_Handler;

/**
 * Class KDNA_Query_Service_Provider
 *
 * Service provider for the Query Service.
 *
 * @package KDNA_Forms\KDNA_Forms\Query;
 */
class KDNA_Query_Service_Provider extends KDNA_Service_Provider {
	const JSON_STRING_HANDLER = 'json_string_handler';
	const JSON_QUERY_HANDLER  = 'json_query_handler';

	/**
	 * Register services to the container.
	 *
	 * @since 
	 *
	 * @param KDNA_Service_Container $container
	 */
	public function register( KDNA_Service_Container $container ) {
		require_once( plugin_dir_path( __FILE__ ) . '/json-handlers/class-kdna-json-handler.php' );
		require_once( plugin_dir_path( __FILE__ ) . '/json-handlers/class-kdna-query-json-handler.php' );
		require_once( plugin_dir_path( __FILE__ ) . '/json-handlers/class-kdna-string-json-handler.php' );

		$container->add( self::JSON_STRING_HANDLER, function() {
			return new KDNA_String_JSON_Handler();
		});

		$container->add( self::JSON_QUERY_HANDLER, function() {
			return new KDNA_Query_JSON_Handler();
		});
	}
	
}

