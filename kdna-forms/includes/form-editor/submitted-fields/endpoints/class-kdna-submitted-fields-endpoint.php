<?php

namespace KDNA_Forms\KDNA_Forms\Form_Editor\Submitted_Fields\Endpoints;

defined( 'ABSPATH' ) || die();

use KDNACommon;

/**
 * Class GF_Submitted_Fields_Endpoint
 *
 * Endpoint for retrieving submitted fields data for the form editor.
 *
 * @since 2.9.20
 *
 * @package KDNA_Forms\KDNA_Forms\Form_Editor\Submitted_Fields\Endpoints
 */
class GF_Submitted_Fields_Endpoint {

	/**
	 * The action name for this endpoint.
	 *
	 * @since 2.9.20
	 *
	 * @var string
	 */
	const ACTION_NAME = 'gf_get_submitted_fields';

	/**
	 * The nonce action for this endpoint.
	 *
	 * @since 2.9.20
	 *
	 * @var string
	 */
	const NONCE_ACTION = 'gf_get_submitted_fields';

	/**
	 * @var array
	 */
	private $dependencies;

	/**
	 * @since 2.9.20
	 *
	 * @param array $dependencies
	 */
	public function __construct( $dependencies ) {
		$this->dependencies = $dependencies;
	}

	/**
	 * Handle the endpoint request.
	 *
	 * @since 2.9.20
	 *
	 * @return void
	 */
	public function handle() {
		if ( ! KDNACommon::current_user_can_any( 'kdnaforms_edit_forms' ) ) {
			wp_send_json_error( array( 'message' => __( 'You do not have permission to perform this action.', 'kdnaforms' ) ), 403 );
		}

		if ( ! wp_verify_nonce( rgpost( 'nonce' ), self::NONCE_ACTION ) ) {
			wp_send_json_error( array( 'message' => __( 'Security check failed.', 'kdnaforms' ) ), 403 );
		}

		$form_id = absint( rgpost( 'form_id' ) );
		if ( ! $form_id ) {
			wp_send_json_error( array( 'message' => __( 'Invalid form ID.', 'kdnaforms' ) ), 400 );
		}

		$kdna_forms_model = $this->dependencies['kdna_forms_model'];
		$fields_string  = $kdna_forms_model::get_submitted_fields( $form_id );
		$fields         = empty( $fields_string ) ? array() : array_map( 'intval', explode( ',', $fields_string ) );

		wp_send_json_success( array(
			'fields'  => $fields,
			'form_id' => $form_id,
		) );
	}
}
