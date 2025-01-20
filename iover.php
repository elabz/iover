<?php
/**
 * Plugin Name: Image Object Verification using Multimodal LLMs
 * Plugin URI: https://github.com/elabz/iover
 * Description: A WordPress plugin for verifying images contain specific objects using multimodal AI (OpenAI, Anthropic, or Ollama).
 * Version: 1.0.0
 * Author: Dmitriy Abaimov / DCBOT
 * Author URI: https://dcbot.es
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: iover
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Define plugin constants
define('IOVER_VERSION', '1.0.0');
define('IOVER_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('IOVER_PLUGIN_URL', plugin_dir_url(__FILE__));

// Autoload classes
spl_autoload_register(function ($class) {
    $prefix = 'IOVER\\';
    $base_dir = IOVER_PLUGIN_DIR . 'includes/';

    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }

    $relative_class = substr($class, $len);
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';

    if (file_exists($file)) {
        require $file;
    }
});

use IOVER\API\Client;
use IOVER\Core\CORS;
use IOVER\Core\Logger;
use IOVER\Frontend\Form;
use IOVER\Admin\Settings;

// Initialize the plugin
function iover_init() {
    Logger::init();
    // Load text domain for internationalization
    load_plugin_textdomain('iover', false, dirname(plugin_basename(__FILE__)) . '/languages');
    
    // Initialize plugin components
    if (is_admin()) {
        new IOVER\Admin\Settings();
    }
    new IOVER\Frontend\Form();
    new IOVER\API\Client();
}
add_action('plugins_loaded', 'iover_init');

// Activation hook
register_activation_hook(__FILE__, 'iover_activate');
function iover_activate() {
    // Set default options
    $default_options = array(
        'system_prompt' => 'Describe only if the image contains the specified objects. Ignore other objects.',
        'allow_prompt_edit' => false,
        'active_api' => 'openai',
        'api_key_openai' => '',
        'api_key_anthropic' => '',
        'api_key_ollama' => '',
        'api_url_ollama' => 'http://localhost:11434/api/generate',
    );

    foreach ($default_options as $option => $value) {
        if (get_option('iover_' . $option) === false) {
            add_option('iover_' . $option, $value);
        }
    }
}

// Deactivation hook
register_deactivation_hook(__FILE__, 'iover_deactivate');
function iover_deactivate() {
    // Cleanup if needed
}

// Register shortcode
function iover_shortcode() {
    if (!is_user_logged_in()) {
        return '<p>' . esc_html__('You must be logged in to use this feature.', 'iover') . '</p>';
    }
    
    ob_start();
    do_action('iover_render_form');
    return ob_get_clean();
}
add_shortcode('iover_uploader', 'iover_shortcode');
