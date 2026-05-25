<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class KDNALogging {

    private static $log_dir = null;

    public static function get_log_dir() {
        if ( null === self::$log_dir ) {
            $upload_dir = wp_upload_dir();
            self::$log_dir = trailingslashit( $upload_dir['basedir'] ) . 'kdna-logs/';
        }
        return self::$log_dir;
    }

    public static function is_logging_enabled() {
        return (bool) get_option( 'kdnaform_enable_logging', false );
    }

    public static function include_logger() {
        // No-op for backward compatibility.
    }

    public static function log_message( $slug, $message, $level = 'debug' ) {
        if ( ! self::is_logging_enabled() ) {
            return;
        }

        $dir = self::get_log_dir();
        if ( ! is_dir( $dir ) ) {
            wp_mkdir_p( $dir );
            file_put_contents( $dir . '.htaccess', 'deny from all' );
            file_put_contents( $dir . 'index.php', '<?php // silence' );
        }

        $file = $dir . sanitize_file_name( $slug ) . '.log';
        $timestamp = current_time( 'Y-m-d H:i:s' );
        $level_str = is_string( $level ) ? strtoupper( $level ) : ( $level <= 3 ? 'ERROR' : 'DEBUG' );
        $line = "[{$timestamp}] [{$level_str}] {$message}\n";

        file_put_contents( $file, $line, FILE_APPEND | LOCK_EX );
    }

    public static function is_enabled( $slug = '' ) {
        return self::is_logging_enabled();
    }

    public static function get_log_files() {
        $dir = self::get_log_dir();
        if ( ! is_dir( $dir ) ) {
            return array();
        }
        $files = glob( $dir . '*.log' );
        return $files ? $files : array();
    }

    public static function get_log_content( $slug, $lines = 200 ) {
        $file = self::get_log_dir() . sanitize_file_name( $slug ) . '.log';
        if ( ! file_exists( $file ) ) {
            return '';
        }
        $content = file_get_contents( $file );
        $all_lines = explode( "\n", $content );
        $tail = array_slice( $all_lines, -$lines );
        return implode( "\n", $tail );
    }

    public static function delete_log_files() {
        $files = self::get_log_files();
        foreach ( $files as $file ) {
            @unlink( $file );
        }
    }

    public static function enable_all_loggers() {
        // No-op for backward compatibility.
    }
}
