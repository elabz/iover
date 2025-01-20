<?php
namespace IOVER\Core;

class CORS {
    public function __construct() {
        add_action('init', array($this, 'handle_cors'));
        add_action('rest_api_init', array($this, 'handle_cors'));
        add_action('admin_init', array($this, 'handle_cors'));
        
        // Handle CORS for admin-ajax.php
        add_action('wp_ajax_nopriv_iover_process_upload', array($this, 'handle_cors'), 1);
        add_action('wp_ajax_iover_process_upload', array($this, 'handle_cors'), 1);
    }

    public function handle_cors() {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            // In development, allow all local origins
            $origin = isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : '';
            
            if (empty($origin)) {
                // If no origin, try to construct it from host
                $origin = isset($_SERVER['HTTP_HOST']) ? 'http://' . $_SERVER['HTTP_HOST'] : '';
            }
            
            if (!empty($origin)) {
                header("Access-Control-Allow-Origin: " . $origin);
                header("Access-Control-Allow-Methods: POST, GET, OPTIONS, PUT, DELETE");
                header("Access-Control-Allow-Credentials: true");
                header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept, Authorization");
                
                if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
                    status_header(200);
                    exit();
                }
            }
            
            // Log CORS debug info
            error_log('IOVER CORS Debug - Origin: ' . $origin);
            error_log('IOVER CORS Debug - Request Method: ' . $_SERVER['REQUEST_METHOD']);
            if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD'])) {
                error_log('IOVER CORS Debug - Requested Method: ' . $_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD']);
            }
        } else {
            // In production, only allow specific origins
            $allowed_origins = array(
                'http://localhost',
                'http://localhost:8080',
                'http://127.0.0.1',
                'http://127.0.0.1:8080'
            );
            
            $origin = isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : '';
            
            if (in_array($origin, $allowed_origins)) {
                header("Access-Control-Allow-Origin: " . $origin);
                header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
                header("Access-Control-Allow-Credentials: true");
                header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept");
                
                if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
                    status_header(200);
                    exit();
                }
            }
        }
    }
}
