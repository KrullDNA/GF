<?php
/**
 * KDNA Forms Style Asset
 *
 * @since 2.5
 * @package kdnaforms
 */

if ( ! class_exists( 'KDNAForms' ) ) {
	die();
}

/**
 * Class KDNA_Style_Asset
 */
class KDNA_Style_Asset extends KDNA_Asset {

	/**
	 * Enqueue the asset.
	 *
	 * @since 2.5
	 *
	 * @return void
	 */
	public function enqueue_asset() {
		wp_enqueue_style( $this->handle, $this->url );
	}

	/**
	 * Print the asset.
	 *
	 * @since 2.5
	 *
	 * @return void
	 */
	public function print_asset() {
		$this->enqueue_asset();
		wp_print_styles( $this->handle );
	}
}