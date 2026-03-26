<?php

namespace KDNA_Forms\KDNA_Forms\Assets\Theme_Dependencies;

use KDNA_Forms\KDNA_Forms\Assets\GF_Dependencies;
use KDNA_Forms\KDNA_Forms\KDNA_Service_Container;
use KDNA_Forms\KDNA_Forms\KDNA_Service_Provider;

/**
 * Class GF_Theme_Script_Dependencies
 *
 * @since 2.6
 *
 * @package KDNA_Forms\KDNA_Forms\Assets\Theme_Dependencies;
 */
class GF_Theme_Script_Dependencies extends GF_Dependencies {

	/**
	 * Items to enqueue globally in admin.
	 *
	 * @since 2.6
	 *
	 * @var string[]
	 */
	protected $items = array(
		'kdnaform_kdnaforms_theme',
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

}
