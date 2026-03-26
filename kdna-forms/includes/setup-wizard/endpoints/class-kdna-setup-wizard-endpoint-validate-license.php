<?php

namespace KDNA_Forms\KDNA_Forms\Setup_Wizard\Endpoints;

/**
 * AJAX Endpoint for validating a license key.
 * License functionality removed - plugin is now free.
 *
 * @since   2.7
 *
 * @package KDNA_Forms\KDNA_Forms\Setup_wizard\Endpoints
 */
class KDNA_Setup_Wizard_Endpoint_Validate_License {

	// Strings
	const ACTION_NAME = 'gf_setup_wizard_validate_license';

	// Parameters
	const PARAM_LICENSE = 'license';

	public function __construct( $license_api = null ) {
		// License functionality removed - plugin is now free.
	}

	/**
	 * Handle the AJAX request. Always returns success since license is no longer required.
	 *
	 * @since 2.6
	 *
	 * @return void
	 */
	public function handle() {
		check_ajax_referer( self::ACTION_NAME );

		// License functionality removed - always return success.
		wp_send_json_success( 'free' );
	}

}
