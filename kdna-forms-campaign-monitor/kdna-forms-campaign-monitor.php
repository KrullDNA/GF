<?php
/**
 * Plugin Name: KDNA Forms Campaign Monitor Add-On
 * Description: Integrates KDNA Forms with Campaign Monitor for email marketing
 * Version: 1.0.0
 * Author: KrullDNA
 * Requires: KDNA Forms plugin
 * Text Domain: kdna-forms-cm
 * License: GPL-2.0+
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Define plugin constants.
define( 'KDNA_CM_ADDON_VERSION', '1.0.0' );
define( 'KDNA_CM_ADDON_PATH', plugin_dir_path( __FILE__ ) );
define( 'KDNA_CM_ADDON_URL', plugin_dir_url( __FILE__ ) );

/**
 * Load the Campaign Monitor Add-On after KDNA Forms is loaded.
 */
add_action( 'kdnaform_loaded', 'kdna_cm_addon_load' );

function kdna_cm_addon_load() {

    // Check that KDNAAddOn class exists (KDNA Forms is active).
    if ( ! class_exists( 'KDNAAddOn' ) ) {
        return;
    }

    // Load the feed addon framework so KDNAFeedAddOn is available.
    KDNAForms::include_feed_addon_framework();

    // Load required files.
    require_once KDNA_CM_ADDON_PATH . 'class-kdna-cm-api.php';
    require_once KDNA_CM_ADDON_PATH . 'class-kdna-cm-addon.php';

    // Register the add-on with KDNA Forms.
    KDNAAddOn::register( 'KDNA_CM_AddOn' );
}

/**
 * Display admin notice if KDNA Forms is not active.
 */
add_action( 'admin_notices', 'kdna_cm_addon_check_dependency' );

function kdna_cm_addon_check_dependency() {
    if ( ! class_exists( 'KDNAAddOn' ) ) {
        echo '<div class="notice notice-error"><p>';
        echo esc_html__(
            'KDNA Forms Campaign Monitor Add-On requires the KDNA Forms plugin to be installed and activated.',
            'kdna-forms-cm'
        );
        echo '</p></div>';
    }
}
