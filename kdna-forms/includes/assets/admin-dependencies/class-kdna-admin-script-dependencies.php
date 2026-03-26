<?php

namespace KDNA_Forms\KDNA_Forms\Assets\Admin_Dependencies;

use KDNA_Forms\KDNA_Forms\Assets\GF_Dependencies;
use KDNA_Forms\KDNA_Forms\KDNA_Service_Container;
use KDNA_Forms\KDNA_Forms\KDNA_Service_Provider;

/**
 * Class GF_Admin_Script_Dependencies
 *
 * @since 2.6
 *
 * @package KDNA_Forms\KDNA_Forms\Assets\Admin_Dependencies;
 */
class GF_Admin_Script_Dependencies extends GF_Dependencies {

	/**
	 * Items to enqueue globally in admin.
	 *
	 * @since 2.6
	 *
	 * @var string[]
	 */
	protected $items = array(
		'kdnaform_kdnaforms_admin',
	);

	/**
	 * Enqueue the item by handle.
	 *
	 * @since 2.6
	 *
	 * @param $handle
	 *
	 * @return void
	 */
	protected function do_enqueue( $handle ) {
		wp_enqueue_script( $handle );
	}

	/**
	 * Whether the global scripts should enqueue.
	 *
	 * @since 2.6
	 *
	 * @return bool
	 */
	protected function should_enqueue() {
		/***
		 * The newer JavaScript added in 2.5 is now enqueued globally in the admin.
		 * We implemented this as we are now using code splitting to only inject JavaScript
		 * dynamically as it is needed, and to also allow our addons easy access to the core libraries
		 * we use.
		 *
		 * This filter allows users to make our admin scripts only load on KDNA Forms admin screens.
		 * Setting it to false may cause unexpected behavior/feature loss in some addons or core.
		 *
		 * @since 2.6.0
		 *
		 * @param bool true Load admin scripts globally?
		 *
		 * @return bool
		 */
		return apply_filters( 'kdnaform_load_admin_scripts_globally', true );
	}
}