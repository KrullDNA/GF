<?php

if ( ! class_exists( 'KDNAForms' ) ) {
	die();
}

/**
 * Loads the KDNA Forms REST API add-on.
 *
 * Includes the main class, registers it with KDNAAddOn, and initialises.
 *
 * @since 2.4-beta-1
 */
class KDNA_REST_API_Bootstrap {

	/**
	 * Loads the required files.
	 *
	 * @since 2.4-beta-1
	 *
	 */
	public static function load_rest_api() {


		$dir = plugin_dir_path( __FILE__ );

		// Requires the class file
		require_once $dir . 'class-kdna-rest-api.php';

		require_once $dir . 'includes/class-results-cache.php';


		if ( ! class_exists( 'WP_REST_Controller' ) ) {
			require_once $dir . 'includes/controllers/class-wp-rest-controller.php';
		}

		require_once $dir . 'includes/controllers/class-kdna-rest-controller.php';

		require_once $dir . 'includes/controllers/class-controller-form-entries.php';
		require_once $dir . 'includes/controllers/class-controller-form-results.php';
		require_once $dir . 'includes/controllers/class-controller-form-submissions.php';
		require_once $dir . 'includes/controllers/class-controller-form-submissions-validation.php';
		require_once $dir . 'includes/controllers/class-controller-form-feeds.php';
		require_once $dir . 'includes/controllers/class-controller-feeds.php';
		require_once $dir . 'includes/controllers/class-controller-entries.php';
		require_once $dir . 'includes/controllers/class-controller-entry-notes.php';
		require_once $dir . 'includes/controllers/class-controller-notes.php';
		require_once $dir . 'includes/controllers/class-controller-entry-notifications.php';
		require_once $dir . 'includes/controllers/class-controller-entry-properties.php';
		require_once $dir . 'includes/controllers/class-controller-forms.php';
		require_once $dir . 'includes/controllers/class-controller-form-field-filters.php';
		require_once $dir . 'includes/controllers/class-controller-feed-properties.php';

		return KDNA_REST_API::get_instance();
	}
}

KDNA_REST_API_Bootstrap::load_rest_api();
