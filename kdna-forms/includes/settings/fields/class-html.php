<?php

namespace KDNA_Forms\KDNA_Forms\Settings\Fields;

use KDNA_Forms\KDNA_Forms\Settings\Fields;

defined( 'ABSPATH' ) || die();

class HTML extends Base {

	/**
	 * Field type.
	 *
	 * @since 2.5
	 *
	 * @var string
	 */
	public $type = 'html';





	// # RENDER METHODS ------------------------------------------------------------------------------------------------

	/**
	 * Render field.
	 *
	 * @since 2.5
	 *
	 * @return string
	 */
	public function markup() {
		$html = rgobj( $this, 'html' );

		if ( is_callable( $html ) ) {
			return call_user_func( $html );
		}

		// Prepare markup.
		return $html;
	}

}

Fields::register( 'html', '\KDNA_Forms\KDNA_Forms\Settings\Fields\HTML' );
