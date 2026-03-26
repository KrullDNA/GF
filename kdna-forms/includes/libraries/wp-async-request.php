<?php
/**
 * @depecated 2.9.8
 * @remove-in 4.0
 */
_deprecated_file( __FILE__, '2.9.8', KDNA_PLUGIN_DIR_PATH . 'includes/async/class-wp-async-request.php (KDNA_Forms\KDNA_Forms\Async\WP_Async_Request)' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
if ( ! class_exists( 'WP_Async_Request' ) ) {
	require_once KDNA_PLUGIN_DIR_PATH . 'includes/async/class-wp-async-request.php';
	class_alias( KDNA_Forms\KDNA_Forms\Async\WP_Async_Request::class, 'WP_Async_Request', false );
}
