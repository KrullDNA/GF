<?php

namespace KDNA_Forms\KDNA_Forms\Assets\Admin_Dependencies;

use KDNA_Forms\KDNA_Forms\Assets\GF_Dependencies;
use KDNA_Forms\KDNA_Forms\KDNA_Service_Container;
use KDNA_Forms\KDNA_Forms\KDNA_Service_Provider;

/**
 * Class KDNA_Admin_Style_Dependencies
 *
 * @since 2.6
 *
 * @package KDNA_Forms\KDNA_Forms\Assets\Admin_Dependencies;
 */
class KDNA_Admin_Style_Dependencies extends GF_Dependencies {

	/**
	 * Items to enqueue globally in admin.
	 *
	 * @since 2.6
	 *
	 * @var string[]
	 */
	protected $items = array(
		'kdnaform_common_css_utilities',
		'kdnaform_common_icons',
		'kdnaform_admin_icons',
		'kdnaform_admin_components',
		'kdnaform_admin_css_utilities',
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
		wp_enqueue_style( $handle );
	}

}
