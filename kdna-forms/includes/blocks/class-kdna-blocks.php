<?php

defined( 'ABSPATH' ) or die();

// Load core Block class.
require_once( plugin_dir_path( __FILE__ ) . 'class-kdna-block.php' );

/**
 * Handles management of KDNA Forms editor blocks.
 *
 * @since 2.4.10
 *
 * Class KDNA_Blocks
 */
class KDNA_Blocks {

	/**
	 * Registered KDNA Forms editor blocks.
	 *
	 * @since 2.4.10
	 * @var   KDNA_Block[]
	 */
	private static $_blocks = array();

	/**
	 * Register a block type.
	 *
	 * @since  2.4.10
	 *
	 * @param KDNA_Block $block Block class.
	 *
	 * @return bool|WP_Error
	 */
	public static function register( $block ) {

		if ( ! is_subclass_of( $block, 'KDNA_Block' ) ) {
			return new WP_Error( 'block_not_subclass', 'Must be a subclass of KDNA_Block' );
		}

		// Get block type.
		$block_type = $block->get_type();

		if ( empty( $block_type ) ) {
			return new WP_Error( 'block_type_undefined', 'The type must be set' );
		}

		if ( isset( self::$_blocks[ $block_type ] ) ) {
			return new WP_Error( 'block_already_registered', 'Block type already registered: ' . $block_type );
		}

		// Register block.
		self::$_blocks[ $block_type ] = $block;

		// Initialize block.
		call_user_func( array( $block, 'init' ) );

		return true;

	}

	/**
	 * Get instance of block.
	 *
	 * @since  2.4.10
	 *
	 * @param string $block_type Block type.
	 *
	 * @return KDNA_Block|bool
	 */
	public static function get( $block_type ) {

		return isset( self::$_blocks[ $block_type ] ) ? self::$_blocks[ $block_type ] : false;

	}

	/**
	 * Returns an array of registered block types.
	 *
	 * @since 2.4.18
	 *
	 * @return array
	 */
	public static function get_all_types() {
		return array_keys( self::$_blocks );
	}

}

new KDNA_Blocks();
