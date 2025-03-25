<?php
/**
 * Plugin Name: Emmerce Chatbot
 * Plugin URI: https://emmerce.io
 * Description: Adds a professional AI chatbot to your website to manage communication between you and your customers.
 * Version: 1.0.0
 * Author: Uzziel Kibet
 * Author URI: https://github.com/uzziellite
 * Text Domain: emmerce-chatbot
 * Domain Path: /i18n/languages/
 * Requires at least: 4.2
 * Tested up to: 6.7
 * License: GNU General Public License v3.0
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 */

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

final class EmmerceChatBot {
    /**
     * Setup the chatbot
     *
     * @since 1.0.0
     * @return void
     */
    public static function init() {
        add_action('wp_enqueue_scripts', [__CLASS__, 'emmerce_chatbot_enqueue_scripts']);
        add_action('wp_footer', [__CLASS__, 'emmerce_chatbot_inject_container']);
        add_action('admin_menu', [__CLASS__, 'emmerce_chatbot_admin_menu']);
        add_action('admin_init', [__CLASS__, 'emmerce_chatbot_register_settings']);
        add_action('admin_enqueue_scripts', [__CLASS__, 'emmerce_chatbot_enqueue_admin_styles']);
        add_action('wp_loaded', [__CLASS__, 'emmerce_register_ajax_endpoints']);
        add_action('admin_notices', [__CLASS__, 'show_wp_debug_warning']);
    }

    /**
     * Enqueue the chat scripts and styles depending on the mode the user is in
     * If WP_DEBUG is enabled, it will load the development scripts meaning the dev
     * server has to be active.
     *
     * @since 1.0.0
     * @return void
     */
    public static function emmerce_chatbot_enqueue_scripts() {
        $active = esc_attr(get_option('emmerce_chat_active', '1'));
        if($active === '1') {

            if (WP_DEBUG) {
                wp_enqueue_script(
                    'emmerce-chatbot-js',
                    'http://localhost:5173/src/main.js',
                    array(),
                    null,
                    true
                );

                add_filter('script_loader_tag', function ($tag, $handle, $src) {
                    if ('emmerce-chatbot-js' === $handle) {
                        $tag = '<script type="module" src="' . esc_url($src) . '"></script>';
                    }
                    return $tag;
                }, 10, 3);
            } else {
                $manifest_path = plugin_dir_path(__FILE__) . 'dist/.vite/manifest.json';
                if (file_exists($manifest_path)) {
                    $manifest = json_decode(file_get_contents($manifest_path), true);
        
                    if (isset($manifest['src/main.js'])) {
                        $js_file = $manifest['src/main.js']['file'];
                        $css_files = $manifest['src/main.js']['css'] ?? [];
        
                        wp_enqueue_script(
                            'emmerce-chatbot-js',
                            plugin_dir_url(__FILE__) . 'dist/' . $js_file,
                            array(),
                            null,
                            true
                        );
        
                        add_filter('script_loader_tag', function ($tag, $handle, $src) {
                            if ('emmerce-chatbot-js' === $handle) {
                                $tag = '<script type="module" defer src="' . esc_url($src) . '"></script>';
                            }
                            return $tag;
                        }, 10, 3);
        
                        foreach ($css_files as $css_file) {
                            wp_enqueue_style(
                                'emmerce-chatbot-css-' . basename($css_file),
                                plugin_dir_url(__FILE__) . 'dist/' . $css_file,
                                array(),
                                null
                            );

                            
                        }
                    }
                }
            }
    
            wp_localize_script(
                'emmerce-chatbot-js',
                'emmerceChatbot',
                [
                    'position'      => esc_attr(get_option('emmerce_chat_position', 'right')),
                    'ajaxurl'       => admin_url('admin-ajax.php'),
                    'debugMode'     => WP_DEBUG,
                    'snapSound'     => plugin_dir_url(__FILE__). 'src/media/snap.mp3',
                    'popSound'      => plugin_dir_url(__FILE__). 'src/media/pop.mp3',
                    'clientId'      => esc_attr(get_option('emmerce_chat_client_id')),
                    'accessUrl'     => WP_DEBUG ? 'https://demoinfinity.emmerce.io/api/v1' : 'https://infinity.emmerce.co.ke/api/v1',
                    'nonce'         => wp_create_nonce('emmerce_chat_nonce'),
                    'businessName'  => get_bloginfo('name'),
                    'api_key'       => esc_attr(get_option('emmerce_api_key')),
                    'ws'            => WP_DEBUG ? 'wss://demoinfinity.emmerce.io/ws' : 'wss://infinity.emmerce.co.ke/ws'
                ]
            );
        }
    }

    /**
     * Inject the chatbot container after the page has loaded.
     *
     * @since 1.0.0
     * @return void
     */
    public static function emmerce_chatbot_inject_container() {
        $active = esc_attr(get_option('emmerce_chat_active', '1'));
        if($active === '1'){
            echo '<div id="emmerce-chatbot-root"></div>';
        }
    }

    /**
     * Add admin menu for chatbot settings.
     *
     * @since 1.0.0
     * @return void
     */
    public static function emmerce_chatbot_admin_menu() {
        add_menu_page(
            'Emmerce Chat Settings',
            'Emmerce Chat',
            'manage_options',
            'emmerce-chatbot-settings',
            [__CLASS__, 'emmerce_chatbot_settings_page'],
            'dashicons-format-chat',
            20
        );
    }

    /**
     * Render the settings page.
     *
     * @since 1.0.0
     * @return void
     */
    public static function emmerce_chatbot_settings_page() {
        ?>
        <div class="wrap">
            <h1>Emmerce Chat Settings</h1>
            <p>Emmerce Chat adds a chat widget to your website to help manage all your website chats together with your conversations from other platforms (Facebook, Whatsapp, Instagram...) from the Emmerce customer portal.</p>
            <form method="post" action="options.php">
                <?php
                settings_fields('emmerce_chatbot_settings_group');
                do_settings_sections('emmerce-chatbot-settings');

                add_settings_field('emmerce_api_key_display', 'API Key', [__CLASS__, 'emmerce_api_key_display_callback'], 'emmerce-chatbot-settings', 'emmerce_api_settings');

                submit_button();
                ?>
            </form>
        </div>
        <?php
    }

    /**
     * Register settings.
     *
     * @since 1.0.0
     * @return void
     */
    public static function emmerce_chatbot_register_settings() {
        
        add_settings_section('emmerce_api_settings', 'API Settings', [__CLASS__, 'emmerce_api_settings_callback'], 'emmerce-chatbot-settings');

        add_settings_field('emmerce_api_key_display', 'API Key', [__CLASS__, 'emmerce_api_key_callback'], 'emmerce-chatbot-settings', 'emmerce_api_settings');

        register_setting('emmerce_chatbot_settings_group', 'emmerce_chat_position', 'sanitize_text_field');
        register_setting('emmerce_chatbot_settings_group', 'emmerce_chat_active', 'sanitize_text_field');
        register_setting('emmerce_chatbot_settings_group', 'emmerce_chat_client_id', 'sanitize_text_field');
        register_setting('emmerce_chatbot_settings_group', 'emmerce_api_key', 'sanitize_text_field');

        add_settings_section('emmerce_widget_settings', 'Chat Widget Settings', [__CLASS__, 'emmerce_widget_settings_callback'], 'emmerce-chatbot-settings');
        add_settings_field('emmerce_chat_position', 'Chat Floater Position', [__CLASS__, 'emmerce_chat_position_callback'], 'emmerce-chatbot-settings', 'emmerce_widget_settings');
        add_settings_field('emmerce_chat_active', 'Activate/Deactivate Chat', [__CLASS__, 'emmerce_chat_active_callback'], 'emmerce-chatbot-settings', 'emmerce_widget_settings');
        add_settings_field('emmerce_chat_client_id', 'Client ID', [__CLASS__, 'emmerce_chat_client_id_callback'], 'emmerce-chatbot-settings', 'emmerce_widget_settings');

        add_action('admin_notices', [__CLASS__, 'emmerce_settings_notices']);
    }

    /**
     * API settings callback.
     *
     * @since 1.0.0
     * @return void
     */
    public static function emmerce_api_settings_callback() {
        $api_key = get_option('emmerce_api_key');

        if (!empty($api_key)) {
            echo '<p class="notice notice-success">Emmerce Chatbot is ready!</p>';
        }else{
            echo '<p class="notice notice-warning">Please complete your Emmerce Chatbot Configuration</p>';
        }
    }

    /**
     * Widget settings callback.
     *
     * @since 1.0.0
     * @return void
     */
    public static function emmerce_widget_settings_callback() {
        echo '<p>Configure the chat widget appearance.</p>';
    }

    /**
     * Chat position callback.
     *
     * @since 1.0.0
     * @return void
     */
    public static function emmerce_chat_position_callback() {
        $position = esc_attr(get_option('emmerce_chat_position', 'right'));
        echo "<select name='emmerce_chat_position'>";
        echo "<option value='right' " . selected($position, 'right', false) . ">Right</option>";
        echo "<option value='left' " . selected($position, 'left', false) . ">Left</option>";
        echo "</select>";
    }

    /**
     * Chat active callback.
     *
     * @since 1.0.0
     * @return void
     */
    public static function emmerce_chat_active_callback() {
        $active = esc_attr(get_option('emmerce_chat_active', '1'));
        echo "<label class='switch'>";
        echo "<input type='checkbox' name='emmerce_chat_active' value='1' " . checked($active, '1', false) . ">";
        echo "<span class='slider round'></span>";
        echo "</label>";
    }

    /**
     * Client Id callback
     * @since 1.0.0
     * @return void
     */
    public static function emmerce_chat_client_id_callback() {
        $client_id = esc_attr(get_option('emmerce_chat_client_id'));
        echo '<input type="number" min="1" name="emmerce_chat_client_id" value="'.$client_id.'" />';
    }

    /**
     * Enqueue admin styles.
     *
     * @since 1.0.0
     * @return void
     */
    public static function emmerce_chatbot_enqueue_admin_styles() {
        wp_enqueue_style(
            'emmerce-chatbot-admin-css',
            plugin_dir_url(__FILE__) . 'src/admin-style.css',
            array(),
            '1.0.0'
        );
    }

    /**
     * Settings saved or error notices.
     *
     * @since 1.0.0
     * @return void
     */
    public static function emmerce_settings_notices() {
        if (isset($_GET['emmerce_error']) && $_GET['emmerce_error']) {
            $error_message = sanitize_text_field(urldecode($_GET['emmerce_error']));
            echo '<div class="notice notice-error is-dismissible"><p>' . esc_html($error_message) . '</p></div>';
        } else if (isset($_GET['emmerce_success']) && $_GET['emmerce_success']) {
            echo '<div class="notice notice-success is-dismissible"><p>Settings successfully updated.</p></div>';
        }
    }

    /**
     * API Key display callback.
     *
     * @since 1.0.0
     * @return void
     */
    public static function emmerce_api_key_callback() {
        $api_key = get_option('emmerce_api_key');
        echo "<input type='text' name='emmerce_api_key' value='" . esc_attr($api_key) . "' />";
    }

    /**
     * API Key display callback.
     *
     * @since 1.0.0
     * @return void
     */
    public static function emmerce_api_key_display_callback() {
        $api_key = get_option('emmerce_api_key');
        echo "<input type='text' value='" . esc_attr($api_key) . "' />";
    }

    /**
     * Register AJAX endpoints.
     *
     * @since 1.0.0
     * @return void
     */
    public static function emmerce_register_ajax_endpoints() {
        add_action('wp_ajax_emmerce_chat_message', [__CLASS__, 'emmerce_chat_message_callback']);
        add_action('wp_ajax_nopriv_emmerce_chat_message', [__CLASS__, 'emmerce_chat_message_callback']);
    }

    /**
     * AJAX callback for sending chat messages.
     *
     * @since 1.0.0
     * @return void
     */
    public static function emmerce_chat_message_callback() {
        if (!check_ajax_referer('emmerce_chat_nonce', 'security', false)) {
            wp_send_json_error('Invalid security token.');
            wp_die();
        }

        $api_key = get_option('emmerce_api_key');
        if (empty($api_key)) {
            wp_send_json_error('API Key not found.');
            wp_die();
        }

        $data       = $_POST['data'];
        $url        = sanitize_url($_POST['url']);
        $method     = sanitize_text_field($_POST['method']);

        $api_response = self::send_message_to_api($api_key, $data, $url, $method);

        if (is_wp_error($api_response)) {
            wp_send_json_error($api_response->get_error_message());
            wp_die();
        }

        wp_send_json_success($api_response);
        wp_die();
    }

    /**
     * Wrapper that Sends message to API and relays back the full communication
     *
     * @since 1.0.0
     * @param string $api_key The API Key.
     * @param string $message The message to send.
     * @return array|WP_Error The API response or WP_Error on failure.
     */
    public static function send_message_to_api($api_key, $data, $api_url, $method = "GET") {
        
        if( $method === 'GET' ){
            $response = wp_remote_get($api_url, array(
                'headers' => array(
                    'Content-Type'  => 'application/json',
                    'X-API-Key'     => $api_key,
                )
            ));
        } else if ($method === 'POST') {
            $response = wp_remote_post($api_url, array(
                'headers' => array(
                    'Content-Type'  => 'application/json',
                    'X-API-Key'     => $api_key,
                ),
                'body' => str_replace('\\', '', $data)
            ));
        }

        if (is_wp_error($response)) {
            return $response;
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        return $data;
    }

    /**
     * Show a persistent warning to the Admin when WP_DEBUG is true because
     * this chatbot will not work as expected. Technically there is a 
     * high chance that the development server has not been set.
     * 
     * @since 1.0.0
     * @return void
     */
    public static function show_wp_debug_warning(){
        if (WP_DEBUG) {
            echo '<style>
            .notice-error.emmerce p {
                font-weight: bold;
                font-size: 16px;
            }
            </style>';

            ?>
            <div class="notice notice-error emmerce">
                <p><?php _e('WP_DEBUG is enabled. Emmerce Chatbot may not work as expected.', 'emmerce-chatbot'); ?></p>
            </div>
            <?php

        }
    }

}

EmmerceChatBot::init();