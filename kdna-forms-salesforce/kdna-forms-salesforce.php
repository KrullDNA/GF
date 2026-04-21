<?php
/**
 * Plugin Name: KDNA Forms Salesforce Add-On
 * Description: Integrates KDNA Forms with Salesforce for lead and contact management
 * Version: 1.0.0
 * Author: KrullDNA
 * Requires: KDNA Forms plugin
 * Text Domain: kdna-forms-salesforce
 * License: GPL-2.0+
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'KDNA_SF_ADDON_VERSION', '1.0.0' );
define( 'KDNA_SF_ADDON_PATH', plugin_dir_path( __FILE__ ) );
define( 'KDNA_SF_ADDON_URL', plugin_dir_url( __FILE__ ) );

add_action( 'kdnaform_loaded', 'kdna_sf_addon_load' );

function kdna_sf_addon_load() {

    if ( ! class_exists( 'KDNAAddOn' ) ) {
        return;
    }

    KDNAForms::include_feed_addon_framework();

    require_once KDNA_SF_ADDON_PATH . 'class-kdna-salesforce-api.php';
    require_once KDNA_SF_ADDON_PATH . 'class-kdna-salesforce-addon.php';

    KDNAAddOn::register( 'KDNA_Salesforce_AddOn' );
}

add_action( 'admin_notices', 'kdna_sf_addon_check_dependency' );

function kdna_sf_addon_check_dependency() {
    if ( ! class_exists( 'KDNAAddOn' ) ) {
        echo '<div class="notice notice-error"><p>';
        echo esc_html__(
            'KDNA Forms Salesforce Add-On requires the KDNA Forms plugin to be installed and activated.',
            'kdna-forms-salesforce'
        );
        echo '</p></div>';
    }
}
