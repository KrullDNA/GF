<?php
/**
 * @depecated 2.9.8
 * @remove-in 4.0
 */
_deprecated_file( __FILE__, '2.9.8', KDNA_PLUGIN_DIR_PATH . 'includes/async/class-kdna-background-process.php (KDNA_Forms\KDNA_Forms\Async\KDNA_Background_Process)' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
if ( ! class_exists( 'KDNA_Background_Process' ) ) {
	require_once KDNA_PLUGIN_DIR_PATH . 'includes/async/class-kdna-background-process.php';
	class_alias( KDNA_Forms\KDNA_Forms\Async\KDNA_Background_Process::class, 'KDNA_Background_Process', false );
}
