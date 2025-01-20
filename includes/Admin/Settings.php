<?php
namespace IOVER\Admin;

class Settings {
    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
    }

    public function add_admin_menu() {
        add_options_page(
            __('Image Object Verification Settings', 'iover'),
            __('Image Object Verification', 'iover'),
            'manage_options',
            'iover-settings',
            array($this, 'render_settings_page')
        );
    }

    public function register_settings() {
        register_setting('iover_settings', 'iover_system_prompt');
        register_setting('iover_settings', 'iover_allow_prompt_edit');
        register_setting('iover_settings', 'iover_api_key_openai');
        register_setting('iover_settings', 'iover_api_key_anthropic');
        register_setting('iover_settings', 'iover_api_key_ollama');
        register_setting('iover_settings', 'iover_api_url_ollama');
        register_setting('iover_settings', 'iover_active_api');
        register_setting('iover_settings', 'iover_ollama_model');

        add_settings_section(
            'iover_main_section',
            __('Main Settings', 'iover'),
            array($this, 'render_section_info'),
            'iover-settings'
        );

        // Add settings fields
        add_settings_field(
            'iover_system_prompt',
            __('System Prompt', 'iover'),
            array($this, 'render_system_prompt_field'),
            'iover-settings',
            'iover_main_section'
        );

        add_settings_field(
            'iover_allow_prompt_edit',
            __('Allow Prompt Editing', 'iover'),
            array($this, 'render_allow_prompt_edit_field'),
            'iover-settings',
            'iover_main_section'
        );

        add_settings_field(
            'iover_active_api',
            __('Active API', 'iover'),
            array($this, 'render_active_api_field'),
            'iover-settings',
            'iover_main_section'
        );

        add_settings_field(
            'iover_api_keys',
            __('API Keys', 'iover'),
            array($this, 'render_api_keys_fields'),
            'iover-settings',
            'iover_main_section'
        );
    }

    public function render_settings_page() {
        if (!current_user_can('manage_options')) {
            return;
        }
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            <form action="options.php" method="post">
                <?php
                settings_fields('iover_settings');
                do_settings_sections('iover-settings');
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }

    public function render_section_info() {
        echo '<p>' . esc_html__('Configure the Image Object Verification plugin settings below.', 'iover') . '</p>';
    }

    public function render_system_prompt_field() {
        $value = get_option('iover_system_prompt');
        ?>
        <textarea name="iover_system_prompt" rows="4" cols="50" class="large-text"><?php echo esc_textarea($value); ?></textarea>
        <p class="description"><?php esc_html_e('Enter the default system prompt for image verification.', 'iover'); ?></p>
        <?php
    }

    public function render_allow_prompt_edit_field() {
        $value = get_option('iover_allow_prompt_edit');
        ?>
        <input type="checkbox" name="iover_allow_prompt_edit" value="1" <?php checked(1, $value); ?> />
        <p class="description"><?php esc_html_e('Allow users to edit the system prompt on the front-end.', 'iover'); ?></p>
        <?php
    }

    public function render_active_api_field() {
        $value = get_option('iover_active_api');
        ?>
        <select name="iover_active_api">
            <option value="openai" <?php selected('openai', $value); ?>><?php esc_html_e('OpenAI', 'iover'); ?></option>
            <option value="anthropic" <?php selected('anthropic', $value); ?>><?php esc_html_e('Anthropic', 'iover'); ?></option>
            <option value="ollama" <?php selected('ollama', $value); ?>><?php esc_html_e('Ollama', 'iover'); ?></option>
        </select>
        <?php
    }

    public function render_api_keys_fields() {
        $openai_key = get_option('iover_api_key_openai');
        $anthropic_key = get_option('iover_api_key_anthropic');
        $ollama_key = get_option('iover_api_key_ollama');
        $ollama_url = get_option('iover_api_url_ollama');
        $ollama_model = get_option('iover_ollama_model');
        ?>
        <table>
            <tr>
                <th scope="row"><?php esc_html_e('OpenAI API Key:', 'iover'); ?></th>
                <td><input type="password" name="iover_api_key_openai" value="<?php echo esc_attr($openai_key); ?>" class="regular-text" /></td>
            </tr>
            <tr>
                <th scope="row"><?php esc_html_e('Anthropic API Key:', 'iover'); ?></th>
                <td><input type="password" name="iover_api_key_anthropic" value="<?php echo esc_attr($anthropic_key); ?>" class="regular-text" /></td>
            </tr>
            <tr>
                <th scope="row"><?php esc_html_e('Ollama API Key:', 'iover'); ?></th>
                <td><input type="password" name="iover_api_key_ollama" value="<?php echo esc_attr($ollama_key); ?>" class="regular-text" /></td>
            </tr>
            <tr>
                <th scope="row"><?php esc_html_e('Ollama API URL:', 'iover'); ?></th>
                <td><input type="text" name="iover_api_url_ollama" value="<?php echo esc_attr($ollama_url); ?>" class="regular-text" /></td>
            </tr>
            <tr>
                <th scope="row"><?php esc_html_e('Ollama Model Name:', 'iover'); ?></th>
                <td><input type="text" name="iover_ollama_model" value="<?php echo esc_attr($ollama_model); ?>" class="regular-text" />
                <p class="description"><?php esc_html_e('Specify the Ollama model to use (e.g., llava, llava:13b, etc.)', 'iover'); ?></p></td>
            </tr>
        </table>
        <?php
    }
}
