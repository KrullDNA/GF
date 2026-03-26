<?php
/**
 * Minimal stub for the removed WP_Async_Request class.
 *
 * @package KDNAForms
 */

namespace KDNA_Forms\KDNA_Forms\Async;

if ( ! class_exists( 'KDNA_Forms\KDNA_Forms\Async\WP_Async_Request' ) ) {

	/**
	 * No-op async request stub.
	 */
	class WP_Async_Request {

		public function __construct() {
			// No-op.
		}

		public function dispatch() {
			return null;
		}

		public function data( $data ) {
			return $this;
		}
	}
}
