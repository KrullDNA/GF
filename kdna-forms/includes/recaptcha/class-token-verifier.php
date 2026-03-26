<?php
/**
 * reCAPTCHA Token Verifier
 *
 * @package KDNA_Forms
 * @since 1.0.0
 */

namespace KDNA_Forms\KDNA_Forms_RECAPTCHA;

if ( ! defined( 'ABSPATH' ) ) {
	die();
}

class Token_Verifier {

	const CLASSIC_VERIFY_URL    = 'https://www.google.com/recaptcha/api/siteverify';
	const ENTERPRISE_VERIFY_URL = 'https://recaptchaenterprise.googleapis.com/v1/';

	/** @var KDNA_Recaptcha */
	private $addon;

	public function __construct( $addon ) {
		$this->addon = $addon;
	}

	/**
	 * Verify a reCAPTCHA submission token.
	 *
	 * @param string $token The reCAPTCHA token.
	 * @return array|WP_Error Verification result with score.
	 */
	public function verify_submission( $token ) {
		if ( empty( $token ) ) {
			return new \WP_Error( 'empty_token', 'No reCAPTCHA token provided.' );
		}

		// Check cache.
		$cache_key = 'recaptcha_' . $token;
		$cached = \KDNACache::get( $cache_key );
		if ( $cached !== false ) {
			return $cached;
		}

		$connection_type = $this->addon->get_connection_type();

		if ( $connection_type === 'enterprise' ) {
			$result = $this->verify_enterprise( $token );
		} else {
			$result = $this->verify_classic( $token );
		}

		if ( ! is_wp_error( $result ) ) {
			\KDNACache::set( $cache_key, $result, true, 3600 );
		}

		return $result;
	}

	/**
	 * Verify using classic reCAPTCHA v3 API.
	 */
	private function verify_classic( $token ) {
		$secret_key = $this->addon->get_secret_key();

		if ( empty( $secret_key ) ) {
			return new \WP_Error( 'missing_secret', 'reCAPTCHA secret key is not configured.' );
		}

		$response = wp_remote_post( self::CLASSIC_VERIFY_URL, array(
			'timeout' => 30,
			'body'    => array(
				'secret'   => $secret_key,
				'response' => $token,
			),
		) );

		if ( is_wp_error( $response ) ) {
			$this->addon->log_error( __METHOD__ . '(): HTTP error: ' . $response->get_error_message() );
			return $response;
		}

		$response_code = wp_remote_retrieve_response_code( $response );

		if ( $response_code === 429 ) {
			$this->addon->log_debug( __METHOD__ . '(): Quota limit reached.' );
			return array( 'score' => 'disabled (quota limit)', 'success' => true );
		}

		$body = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( empty( $body ) ) {
			return new \WP_Error( 'invalid_response', 'Invalid response from reCAPTCHA API.' );
		}

		if ( ! rgar( $body, 'success' ) ) {
			$errors = rgar( $body, 'error-codes', array() );
			$this->addon->log_error( __METHOD__ . '(): Verification failed: ' . implode( ', ', $errors ) );
			return new \WP_Error( 'verification_failed', 'reCAPTCHA verification failed.', $errors );
		}

		$result = array(
			'success'  => true,
			'score'    => rgar( $body, 'score', 0 ),
			'action'   => rgar( $body, 'action', '' ),
			'hostname' => rgar( $body, 'hostname', '' ),
		);

		// Validate hostname.
		if ( ! $this->validate_hostname( rgar( $result, 'hostname' ) ) ) {
			$this->addon->log_error( __METHOD__ . '(): Hostname mismatch.' );
			return new \WP_Error( 'hostname_mismatch', 'reCAPTCHA hostname does not match.' );
		}

		return $result;
	}

	/**
	 * Verify using reCAPTCHA Enterprise API.
	 */
	private function verify_enterprise( $token ) {
		$site_key = $this->addon->get_site_key();
		$settings = $this->addon->get_plugin_settings();
		$project_number = rgar( $settings, 'project_number', '' );
		$access_token = rgar( $settings, 'access_token', '' );

		if ( empty( $project_number ) || empty( $access_token ) ) {
			return new \WP_Error( 'missing_config', 'reCAPTCHA Enterprise is not properly configured.' );
		}

		$url = self::ENTERPRISE_VERIFY_URL . 'projects/' . $project_number . '/assessments';

		$body = array(
			'event' => array(
				'token'          => $token,
				'siteKey'        => $site_key,
				'expectedAction' => 'submit',
			),
		);

		$response = wp_remote_post( $url, array(
			'timeout' => 30,
			'headers' => array(
				'Content-Type'  => 'application/json',
				'Authorization' => 'Bearer ' . $access_token,
			),
			'body' => wp_json_encode( $body ),
		) );

		if ( is_wp_error( $response ) ) {
			$this->addon->log_error( __METHOD__ . '(): HTTP error: ' . $response->get_error_message() );
			return $response;
		}

		$response_code = wp_remote_retrieve_response_code( $response );

		if ( $response_code === 429 ) {
			return array( 'score' => 'disabled (quota limit)', 'success' => true );
		}

		$data = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( empty( $data ) ) {
			return new \WP_Error( 'invalid_response', 'Invalid response from reCAPTCHA Enterprise API.' );
		}

		$token_valid = rgars( $data, 'tokenProperties/valid' );

		if ( ! $token_valid ) {
			return new \WP_Error( 'token_invalid', 'reCAPTCHA token is invalid.' );
		}

		$result = array(
			'success'       => true,
			'score'         => rgars( $data, 'riskAnalysis/score', 0 ),
			'action'        => rgars( $data, 'tokenProperties/action', '' ),
			'hostname'      => rgars( $data, 'tokenProperties/hostname', '' ),
			'assessment_id' => rgar( $data, 'name', '' ),
		);

		return $result;
	}

	/**
	 * Validate the hostname from the reCAPTCHA response.
	 */
	private function validate_hostname( $hostname ) {
		$valid_hostnames = apply_filters( 'kdnaform_recaptcha_valid_hostnames', array( wp_parse_url( home_url(), PHP_URL_HOST ) ) );

		if ( empty( $hostname ) || empty( $valid_hostnames ) ) {
			return true;
		}

		return in_array( $hostname, $valid_hostnames, true );
	}
}
