<?php
/**
 * KDNA Forms reCAPTCHA Bootstrap
 *
 * Loads and registers the reCAPTCHA integration as a built-in feature.
 *
 * @package KDNA_Forms
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	die();
}

add_action( 'kdnaform_loaded', 'kdna_recaptcha_load', 5 );

function kdna_recaptcha_load() {
	if ( ! method_exists( 'KDNAAddOn', 'register' ) ) {
		return;
	}

	require_once dirname( __FILE__ ) . '/class-kdna-recaptcha.php';

	KDNAAddOn::register( 'KDNA_Forms\KDNA_Forms_RECAPTCHA\KDNA_Recaptcha' );
}
