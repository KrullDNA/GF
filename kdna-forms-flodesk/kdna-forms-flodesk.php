<?php
/**
 * Plugin Name: KDNA Forms Flodesk Add-On
 * Description: Integrates KDNA Forms with Flodesk for email marketing
 * Version: 1.0.0
 * Author: KrullDNA
 * Requires: KDNA Forms plugin
 * Text Domain: kdna-forms-flodesk
 * License: GPL-2.0+
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'KDNA_FLODESK_ADDON_VERSION', '1.0.0' );
define( 'KDNA_FLODESK_ADDON_PATH', plugin_dir_path( __FILE__ ) );
define( 'KDNA_FLODESK_ADDON_URL', plugin_dir_url( __FILE__ ) );

add_action( 'kdnaform_loaded', 'kdna_flodesk_addon_load' );

function kdna_flodesk_addon_load() {

    if ( ! class_exists( 'KDNAAddOn' ) ) {
        return;
    }

    KDNAForms::include_feed_addon_framework();

    require_once KDNA_FLODESK_ADDON_PATH . 'class-kdna-flodesk-api.php';
    require_once KDNA_FLODESK_ADDON_PATH . 'class-kdna-flodesk-addon.php';

    KDNAAddOn::register( 'KDNA_Flodesk_AddOn' );
}

add_action( 'admin_notices', 'kdna_flodesk_addon_check_dependency' );

function kdna_flodesk_addon_check_dependency() {
    if ( ! class_exists( 'KDNAAddOn' ) ) {
        echo '<div class="notice notice-error"><p>';
        echo esc_html__(
            'KDNA Forms Flodesk Add-On requires the KDNA Forms plugin to be installed and activated.',
            'kdna-forms-flodesk'
        );
        echo '</p></div>';
    }
}
