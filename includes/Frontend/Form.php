<?php
namespace IOVER\Frontend;

class Form {
    public function __construct() {
        add_action('iover_render_form', array($this, 'render_form'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_ajax_iover_process_upload', array($this, 'process_upload'));
    }

    public function enqueue_scripts() {
        wp_enqueue_style('iover-style', IOVER_PLUGIN_URL . 'assets/css/style.css', array(), IOVER_VERSION);
        wp_enqueue_script('iover-script', IOVER_PLUGIN_URL . 'assets/js/script.js', array('jquery'), IOVER_VERSION, true);
        
        wp_localize_script('iover-script', 'ioverAjax', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('iover-ajax-nonce'),
        ));
    }

    public function render_form() {
        if (!is_user_logged_in()) {
            return;
        }

        $allow_prompt_edit = get_option('iover_allow_prompt_edit');
        $system_prompt = get_option('iover_system_prompt');
        ?>
        <div class="iover-form-container">
            <form id="iover-upload-form" method="post" enctype="multipart/form-data">
                <?php wp_nonce_field('iover_upload', 'iover_nonce'); ?>
                
                <div class="iover-form-group">
                    <label for="iover-image"><?php esc_html_e('Upload Image', 'iover'); ?></label>
                    <input type="file" id="iover-image" name="image" accept="image/*" required />
                </div>

                <?php if ($allow_prompt_edit): ?>
                <div class="iover-form-group">
                    <label for="iover-prompt"><?php esc_html_e('System Prompt', 'iover'); ?></label>
                    <textarea id="iover-prompt" name="prompt" rows="4"><?php echo esc_textarea($system_prompt); ?></textarea>
                </div>
                <?php endif; ?>

                <div class="iover-form-group">
                    <button type="submit" class="button button-primary">
                        <?php esc_html_e('Verify Image', 'iover'); ?>
                    </button>
                </div>
            </form>

            <div id="iover-response" class="iover-response" style="display: none;">
                <h3><?php esc_html_e('DCBot AI Response', 'iover'); ?></h3>
                <div class="iover-response-content"></div>
            </div>
        </div>
        <?php
    }

    public function process_upload() {
        check_ajax_referer('iover-ajax-nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => __('You must be logged in to use this feature.', 'iover')));
        }

        if (!isset($_FILES['image'])) {
            wp_send_json_error(array('message' => __('No image was uploaded.', 'iover')));
        }

        $file = $_FILES['image'];
        $allowed_types = array('image/jpeg', 'image/png', 'image/gif');

        if (!in_array($file['type'], $allowed_types)) {
            wp_send_json_error(array('message' => __('Invalid file type. Please upload an image.', 'iover')));
        }

        $upload = wp_handle_upload($file, array('test_form' => false));

        if (isset($upload['error'])) {
            wp_send_json_error(array('message' => $upload['error']));
        }

        // Get the prompt
        $prompt = isset($_POST['prompt']) ? sanitize_textarea_field($_POST['prompt']) : get_option('iover_system_prompt');

        // Initialize API client
        $api_client = new \IOVER\API\Client();
        
        try {
            $response = $api_client->process_image($upload['file'], $prompt);
            
            if (isset($response['error'])) {
                wp_send_json_error(array('message' => $response['error']));
            }
            
            wp_send_json_success($response);
        } catch (\Exception $e) {
            wp_send_json_error(array('message' => $e->getMessage()));
        }

        // Clean up the uploaded file
        @unlink($upload['file']);
    }
}
