<?php
/**
 * Plugin Name: KDNA Forms Stripe
 * Plugin URI: https://kdnaforms.com
 * Description: Take one-off and recurring card payments through Stripe, with optional early bird pricing that switches to the full price on a date you choose.
 * Version: 1.1.4
 * Requires at least: 6.5
 * Requires PHP: 7.4
 * Author: KrullDNA
 * Author URI: https://krulldna.com
 * License: GPL-2.0+
 * Text Domain: kdnaforms-stripe
 *
 * @package KDNAFormsStripe
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'KDNA_STRIPE_VERSION', '1.1.4' );
define( 'KDNA_STRIPE_PATH', plugin_dir_path( __FILE__ ) );
define( 'KDNA_STRIPE_URL', plugin_dir_url( __FILE__ ) );

/**
 * The minimum core version. The payment framework and its transaction tables
 * arrived in 2.1.0; anything older cannot run this add-on.
 */
define( 'KDNA_STRIPE_MIN_KDNA_FORMS', '3.5.0' );

add_action( 'kdnaform_loaded', 'kdna_stripe_load' );

/**
 * Boots the add-on once KDNA Forms itself has loaded.
 *
 * @since 1.0.0
 *
 * @return void
 */
function kdna_stripe_load() {

	if ( ! method_exists( 'KDNAForms', 'include_payment_addon_framework' ) ) {
		return;
	}

	KDNAForms::include_payment_addon_framework();

	if ( ! class_exists( 'KDNAPaymentAddOn' ) ) {
		return;
	}

	require_once KDNA_STRIPE_PATH . 'class-kdna-stripe-api.php';
	require_once KDNA_STRIPE_PATH . 'class-kdna-field-stripe-card.php';
	require_once KDNA_STRIPE_PATH . 'class-kdna-stripe.php';

	KDNAAddOn::register( 'KDNA_Stripe' );
}

add_action( 'admin_notices', 'kdna_stripe_dependency_notice' );

/**
 * Explains why the add-on is inactive, when it is.
 *
 * @since 1.0.0
 *
 * @return void
 */
function kdna_stripe_dependency_notice() {

	if ( ! current_user_can( 'activate_plugins' ) ) {
		return;
	}

	if ( ! class_exists( 'KDNAForms' ) ) {
		$message = __( 'KDNA Forms Stripe needs KDNA Forms to be installed and active.', 'kdnaforms-stripe' );
	} elseif ( version_compare( KDNAForms::$version, KDNA_STRIPE_MIN_KDNA_FORMS, '<' ) ) {
		$message = sprintf(
			/* translators: %s: the minimum required version of KDNA Forms. */
			__( 'KDNA Forms Stripe needs KDNA Forms %s or newer.', 'kdnaforms-stripe' ),
			KDNA_STRIPE_MIN_KDNA_FORMS
		);
	} elseif ( ! method_exists( 'KDNAForms', 'include_payment_addon_framework' ) ) {
		$message = __( 'KDNA Forms Stripe needs the payment add-on framework, which this version of KDNA Forms does not provide.', 'kdnaforms-stripe' );
	} else {
		return;
	}

	printf( '<div class="notice notice-error"><p>%s</p></div>', esc_html( $message ) );
}
