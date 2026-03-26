<?php
/**
 * Service Provider for Duplicate Submission Service
 *
 * @package KDNA_Forms\KDNA_Forms\Duplicate_Submissions
 */

namespace KDNA_Forms\KDNA_Forms\Duplicate_Submissions;

use KDNA_Forms\KDNA_Forms\KDNA_Service_Container;
use KDNA_Forms\KDNA_Forms\KDNA_Service_Provider;
use KDNA_Forms\KDNA_Forms\Util\KDNA_Util_Service_Provider;

/**
 * Class KDNA_License_Service_Provider
 *
 * Service provider for the Duplicate Submission Service.
 */
class KDNA_Duplicate_Submissions_Service_Provider extends KDNA_Service_Provider {

	const GF_DUPLICATE_SUBMISSION_HANDLER = 'gf_duplicate_submission_handler';

	/**
	 * Includes all related files and adds all containers.
	 *
	 * @param KDNA_Service_Container $container Container singleton object.
	 */
	public function register( KDNA_Service_Container $container ) {
		\KDNAForms::include_gravity_api();

		require_once plugin_dir_path( __FILE__ ) . 'class-kdna-duplicate-submissions-handler.php';

		$container->add(
			self::GF_DUPLICATE_SUBMISSION_HANDLER,
			function () {
				return new KDNA_Duplicate_Submissions_Handler( \KDNACommon::get_base_url() );
			}
		);
	}

	/**
	 * Initializes service.
	 *
	 * @param KDNA_Service_Container $container Service Container.
	 */
	public function init( KDNA_Service_Container $container ) {
		parent::init( $container );

		$duplicate_submission_handler = $container->get( self::GF_DUPLICATE_SUBMISSION_HANDLER );

		add_action( 'kdnaform_enqueue_scripts', array( $duplicate_submission_handler, 'maybe_enqueue_scripts' ) );
		add_action( 'wp_loaded', array( $duplicate_submission_handler, 'maybe_handle_safari_redirect' ), 8, 0 );
	}
}
