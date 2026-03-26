<?php
/**
 * KDNA Forms Abstract Asset
 *
 * Provides base functionality for enqueueable/printable assets.
 *
 * @since 2.5
 * @package kdnaforms
 */

if ( ! class_exists( 'KDNAForms' ) ) {
	die();
}

/**
 * Class KDNA_Asset
 */
abstract class KDNA_Asset {

	/**
	 * @var string $handle
	 */
	protected $handle;

	/**
	 * @var string $url
	 */
	protected $url;

	/**
	 * KDNA_Asset constructor.
	 *
	 * @param string $handle
	 * @param string $url
	 */
	public function __construct( $handle, $url = '' ) {
		$this->handle = $handle;
		$this->url    = $url;
	}

	/**
	 * Handle enqueueing the asset this class represents (e.g., using wp_enqueue_script() or wp_enqueue_style())
	 *
	 * @return void
	 */
	abstract public function enqueue_asset();

	/**
	 * Handle printing the asset this class represents (e.g., using wp_print_scripts() or wp_print_styles())
	 *
	 * @return void
	 */
	abstract public function print_asset();

}
