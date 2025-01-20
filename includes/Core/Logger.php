<?php
namespace IOVER\Core;

class Logger {
    private static $log_file;

    public static function init() {
        self::$log_file = WP_CONTENT_DIR . '/debug.log';
    }

    public static function log_error($message, $context = array()) {
        if (empty(self::$log_file)) {
            self::init();
        }

        $timestamp = current_time('Y-m-d H:i:s');
        $formatted_context = empty($context) ? '' : ' Context: ' . json_encode($context);
        $log_entry = "[{$timestamp}] IOVER Error: {$message}{$formatted_context}" . PHP_EOL;

        error_log($log_entry, 3, self::$log_file);
    }
}
