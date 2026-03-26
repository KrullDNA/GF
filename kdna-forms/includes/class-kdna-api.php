<?php
/**
 * Minimal stub for the removed Gravity API / KDNA API class.
 *
 * Provides a no-op gapi() function so that any remaining calls
 * do not produce fatal errors.
 *
 * @package KDNAForms
 */

if ( ! defined( 'GRAVITY_API_URL' ) ) {
	define( 'GRAVITY_API_URL', '' );
}

if ( ! class_exists( 'KDNA_Api_Stub' ) ) {

	/**
	 * No-op API stub.
	 */
	class KDNA_Api_Stub {

		public function __call( $name, $arguments ) {
			return null;
		}

		public static function __callStatic( $name, $arguments ) {
			return null;
		}
	}
}

if ( ! function_exists( 'gapi' ) ) {
	/**
	 * Returns a no-op API stub object.
	 *
	 * @return KDNA_Api_Stub
	 */
	function gapi() {
		static $instance;
		if ( null === $instance ) {
			$instance = new KDNA_Api_Stub();
		}
		return $instance;
	}
}
