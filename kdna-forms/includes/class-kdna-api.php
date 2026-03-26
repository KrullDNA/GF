<?php

if ( ! class_exists( 'KDNAForms' ) ) {
	die();
}

if ( ! class_exists( 'KDNA_Api' ) ) {

	/**
	 * Stub API class - KDNA Forms is a free plugin, no license management needed.
	 *
	 * @package    KDNA Forms
	 * @since      1.0.0
	 */
	class KDNA_Api {

		private static $instance = null;

		public static function get_instance() {
			if ( null == self::$instance ) {
				self::$instance = new self;
			}
			return self::$instance;
		}

		public function register_current_site( $license_key = '', $is_md5 = false ) {
			return true;
		}

		public function update_current_site( $new_license_key_md5 = '' ) {
			return true;
		}

		public function deregister_current_site() {
			return true;
		}

		public function check_license( $key = '' ) {
			return array(
				'is_valid_key' => '1',
				'version'      => KDNAForms::$version,
				'url'          => '',
				'is_error'     => '0',
			);
		}

		public function get_plugins_info() {
			return array();
		}

		public function update_site_data() {
			return;
		}

		public function get_key() {
			return '';
		}

		public function prepare_response_body( $raw_response, $as_array = false ) {
			if ( is_wp_error( $raw_response ) ) {
				return $raw_response;
			}
			return json_decode( wp_remote_retrieve_body( $raw_response ), $as_array );
		}

		public function purge_site_credentials() {
			return;
		}

		public function request( $resource, $body, $method = 'POST', $options = array() ) {
			return new WP_Error( 'not_available', 'KDNA Forms is a free plugin.' );
		}

		public function get_site_key() {
			return '';
		}

		public function get_site_secret() {
			return '';
		}

		public function get_gravity_api_url() {
			return '';
		}

		public function is_site_registered() {
			return true;
		}

		public function is_legacy_registration() {
			return false;
		}

		public function send_email_to_hubspot( $email ) {
			return true;
		}
	}

	function gapi() {
		return KDNA_Api::get_instance();
	}

	gapi();
}
