<?php

if ( ! class_exists( 'KDNAForms' ) ) {
	die();
}

class KDNA_REST_API {
	/**
	 * Contains an instance of this class, if available.
	 *
	 * @since 2.4-beta-1
	 *
	 * @var object $_instance If available, contains an instance of this class
	 */
	private static $_instance = null;

	/**
	 * Returns an instance of this class, and stores it in the $_instance property.
	 *
	 * @since 2.4-beta-1
	 *
	 * @return KDNA_REST_API $_instance An instance of the KDNA_REST_API class
	 */
	public static function get_instance() {
		if ( self::$_instance == null ) {
			self::$_instance = new KDNA_REST_API();
		}

		return self::$_instance;
	}

	/**
	 * @since 2.4-beta-1
	 */
	private function __clone() {
	} /* do nothing */

	/**
	 * KDNA_REST_API constructor.
	 *
	 * @since 2.4-beta-1
	 */
	public function __construct() {
		add_action( 'rest_api_init', array( $this, 'register_rest_routes' ) );
	}

	/**
	 * Register REST API routes
	 *
	 * @since 2.4-beta-1
	 */
	public function register_rest_routes() {
		$controllers = array(
			'KDNA_REST_Entries_Controller',
			'KDNA_REST_Entry_Properties_Controller',
			'KDNA_REST_Entry_Notifications_Controller',
			'KDNA_REST_Notes_Controller',
			'KDNA_REST_Entry_Notes_Controller',
			'KDNA_REST_Form_Entries_Controller',
			'KDNA_REST_Form_Results_Controller',
			'KDNA_REST_Form_Submissions_Controller',
			'KDNA_REST_Form_Submissions_Validation_Controller',
			'KDNA_REST_Forms_Controller',
			'KDNA_REST_Feeds_Controller',
			'KDNA_REST_Form_Feeds_Controller',
			'KDNA_REST_Form_Field_Filters_Controller',
			'KDNA_REST_Feed_Properties_Controller',
		);

		foreach ( $controllers as $controller ) {
			$controller_obj = new $controller();
			$controller_obj->register_routes();
		}
	}

}
