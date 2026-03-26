<?php

namespace KDNA_Forms\KDNA_Forms\Ajax\Config;

use KDNA_Forms\KDNA_Forms\Config\KDNA_Config;
use KDNAForms;

/**
 * Config items for Ajax operations
 *
 * @since 2.9.0
 */
class KDNA_Ajax_Config extends KDNA_Config {

	protected $name               = 'gform_theme_config';
	protected $script_to_localize = 'kdnaform_kdnaforms_theme';

	/**
	 * Config data.
	 *
	 * @return array[]
	 */
	public function data() {
		$preview_query_string = \KDNACommon::is_preview() ? '?gf_ajax_page=preview' : '';
		return array(
			'common' => array(
				'form' => array(
					'ajax' => array(
						'ajaxurl'               => admin_url( 'admin-ajax.php' ) . $preview_query_string,
						'ajax_submission_nonce' => wp_create_nonce( 'kdnaform_ajax_submission' ),
						'i18n' => array(
							/* Translators: This is used to announce the current step of a multipage form, 1. first step number, 2. total steps number, example: Step 1 of 5 */
							'step_announcement' => esc_html__( 'Step %1$s of %2$s, %3$s', 'kdnaforms' ),
							'unknown_error'     => esc_html__( 'There was an unknown error processing your request. Please try again.', 'kdnaforms' ),
						),
					),
				),
			),
		);
	}
}
