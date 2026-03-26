<?php

namespace KDNA_Forms\KDNA_Forms\Editor_Button\Endpoints;

use KDNA_Forms\KDNA_Forms\Config\KDNA_Config;

/**
 * AJAX Endpoint for saving the compact view settings.
 *
 * @since   2.8
 *
 * @package KDNA_Forms\KDNA_Forms\Editor_Button\Endpoints
 */
class KDNA_Editor_Save_Editor_Settings {

	// Strings
	const ACTION_NAME = 'gf_save_editor_settings';

	/**
	 * Handle the AJAX request.
	 *
	 * @since 2.8
	 *
	 * @return void
	 */
	public function handle() {
		check_ajax_referer( self::ACTION_NAME );

		$form  = intval( rgpost( 'form' ) );
		$user  = get_current_user_id();
		$name  = rgpost( 'name' );
		$value = rgpost( 'value' );
		$value = ( $value == 'enable' ) ? 'enable' : 'disable';

		update_user_meta( $user, 'kdnaform_' . $name . '_' . $form, $value );
	}

}
