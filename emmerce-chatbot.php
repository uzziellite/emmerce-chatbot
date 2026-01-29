<?php
/**
 * Plugin Name: Emmerce Chatbot
 * Plugin URI: https://github.com/uzziellite/emmerce-chatbot
 * Description: Adds a professional AI chatbot managed by Emmerce to your website to manage communication between you and your customers. You need to have a valid Emmerce account to use this plugin.
 * Version: 1.0.1
 * Author: Uzziel Lite
 * Author URI: https://github.com/uzziellite
 * Text Domain: emmerce-chatbot
 * Requires at least: 6.0
 * Tested up to: 6.9
 * License: GNU General Public License v3.0
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 */

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

final class EmmerceChatBot {

    /**
     * Allowed origins for the chatbot
     *
     * @since 1.0.0
     * @var array $allowed_origins
     */
    private static $allowed_origins = [
        'https://demoinfinity.emmerce.io',
        'https://infinity.emmerce.co.ke',
        'https://chat-proxy.emmerce.io'
    ];

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
        add_action('rest_api_init', [__CLASS__, 'emmerce_register_rest_endpoints']);
        add_filter('rest_authentication_errors', [__CLASS__, 'emmerce_handle_rest_authentication_errors']);
        add_filter( 'plugin_action_links_' . plugin_basename(__FILE__), [__CLASS__, 'plugin_settings_link'] );   
    }

    /**
     * Add plugin settings link to the admin
     * 
     * @since 1.0.1
     * @return void
     */
    public static function plugin_settings_link( $links ) {
        $settings_url = admin_url( 'admin.php?page=emmerce-chatbot-settings' );
        $settings_link = '<a href="' . esc_url( $settings_url ) . '">' . __( 'Configure', 'emmerce-chatbot' ) . '</a>';
        array_unshift( $links, $settings_link );
        return $links;
    }

    /**
     * Enqueue the chat scripts and styles depending on the mode the user is in
     * If WP_DEBUG is enabled, it will load the development scripts meaning 
     * the development server has to be active.
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
                        $tag = str_replace('<script ', '<script type="module" ', $tag);
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
                            '1.0.1',
                            true
                        );
        
                        add_filter('script_loader_tag', function ($tag, $handle, $src) {
                            if ('emmerce-chatbot-js' === $handle) {
                                $tag = str_replace('<script ', '<script type="module" ', $tag);;
                            }
                            return $tag;
                        }, 10, 3);
        
                        foreach ($css_files as $css_file) {
                            wp_enqueue_style(
                                'emmerce-chatbot-css-' . basename($css_file),
                                plugin_dir_url(__FILE__) . 'dist/' . $css_file,
                                array(),
                                '1.0.1'
                            );

                            
                        }
                    }
                }
            }
            
            $chat_nonce = wp_create_nonce('emmerce_chat_nonce');
            $current_user_id = get_current_user_id();
            wp_set_current_user(0); //ensure nonces are all the same for all users
            $ws_nonce = wp_create_nonce('emmerce_ws_nonce');
            wp_set_current_user($current_user_id);
            wp_localize_script(
                'emmerce-chatbot-js',
                'emmerceChatbot',
                [
                    'position'      => esc_attr(get_option('emmerce_chat_position', 'right')),
                    'ajaxurl'       => admin_url('admin-ajax.php'),
                    'debugMode'     => WP_DEBUG ? 1 : 0,
                    'snapSound'     => plugin_dir_url(__FILE__). 'src/media/snap.mp3',
                    'popSound'      => plugin_dir_url(__FILE__). 'src/media/pop.mp3',
                    'clientId'      => esc_attr(get_option('emmerce_chat_client_id')),
                    'accessUrl'     => 'https://chat-proxy.emmerce.io',
                    'nonce'         => $chat_nonce,
                    'wsNonce'       => $ws_nonce,
                    'businessName'  => get_bloginfo('name'),
                    'ws'            => 'wss://chat-proxy.emmerce.io'
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
            <h1><?php esc_html_e('Emmerce Chat Settings', 'emmerce-chatbot') ?></h1>
            <p>
                <?php esc_html_e('Emmerce Chat adds a chat widget to your website to help manage all your website chats together with your conversations from other platforms (Facebook, Whatsapp, Instagram...) from the Emmerce customer portal.', 'emmerce-chatbot') ?>
            </p>
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
            ?>
            <div class="notice notice-success emmerce">
                <p>
                    <?php esc_html_e("Emmerce Chatbot is ready!","emmerce-chatbot") ?>
                </p>
            </div>
            <?php
        }else{
            ?>
            <div class="notice notice-error emmerce">
                <p>
                    <?php esc_html_e("Please complete your Emmerce Chatbot Configuration","emmerce-chatbot") ?>
                </p>
            </div>
            <?php
        }
    }

    /**
     * Widget settings callback.
     *
     * @since 1.0.0
     * @return void
     */
    public static function emmerce_widget_settings_callback() {
        echo '<p>'. esc_html_e("Configure the chat widget appearance.","emmerce-chatbot") . '</p>';
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
        echo '<input type="number" min="1" name="emmerce_chat_client_id" value="'. esc_attr($client_id) .'" />';
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
            '1.0.1'
        );
    }

    /**
     * Settings saved or error notices.
     *
     * @since 1.0.0
     * @return void
     */
    public static function emmerce_settings_notices() {
        if ( isset( $_GET['_wpnonce'] ) && ! wp_verify_nonce( sanitize_key( $_GET['_wpnonce'] ), 'emmerce_settings_action' ) ) {
            return;
        }

        if ( ! empty( $_GET['emmerce_error'] ) ) {
            $error_message = sanitize_text_field( wp_unslash( $_GET['emmerce_error'] ) );
            
            ?>
            <div class="notice notice-error is-dismissible">
                <p><?php echo esc_html( $error_message ); ?></p>
            </div>
            <?php
        } 

        else if ( ! empty( $_GET['emmerce_success'] ) ) {
            ?>
            <div class="notice notice-success is-dismissible">
                <p><?php esc_html_e( 'Settings successfully updated.', 'emmerce-chatbot' ); ?></p>
            </div>
            <?php
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

        $data = isset( $_POST['data'] ) ? sanitize_text_field( wp_unslash( $_POST['data'] ) ) : '';

        $url = isset( $_POST['url'] ) ? esc_url_raw( wp_unslash( $_POST['url'] ) ) : '';
        
        $raw_method = isset( $_POST['method'] ) ? sanitize_text_field( wp_unslash( $_POST['method'] ) ) : '';

        $upper_method = strtoupper( $raw_method );

        $method = in_array( $upper_method, ['GET', 'POST'], true ) ? $upper_method : 'POST';

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
        $allowed_endpoints = self::$allowed_origins;

        $is_allowed = false;
        foreach ($allowed_endpoints as $allowed) {
            if (strpos($api_url, $allowed) === 0) {
                $is_allowed = true;
                break;
            }
        }

        if (!$is_allowed) {
            return new WP_Error('rest_forbidden', 'URL destination is not allowed.', array('status' => 403));
        }

        $args = array(
            'headers' => array(
                'Content-Type'  => 'application/json',
                'X-API-Key'     => $api_key,
            ),
            'timeout' => 15,
        );

        if ( $method === 'GET' ){
            $response = wp_remote_get($api_url, $args);
        } else if ($method === 'POST') {
            $args['body'] = str_replace('\\', '', $data);
            $response = wp_remote_post($api_url, $args);
        } else {
            return new WP_Error('invalid_method', 'Invalid request method.');
        }

        if (is_wp_error($response)) {
            return $response;
        }

        $body = wp_remote_retrieve_body($response);
        return json_decode($body, true);
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
            ?>
            <div class="notice notice-error emmerce">
                <p><?php esc_html_e('Please set your WP_DEBUG to false in your wp-config.php file to use the Emmerce Chatbot.', 'emmerce-chatbot'); ?></p>
            </div>
            <?php

        }
    }

    /**
     * Register the REST API route for the internal API key retrieval.
     *
     * This function registers a GET route at emmerce/v1/internal/api-key that
     * returns the internal API key for the Emmerce Chatbot.
     *
     * The permission callback is emmerce_verify_origin, which verifies that
     * the _wpnonce parameter matches the expected value.
     *
     * @since 1.0.0
     */
    public static function emmerce_register_rest_endpoints() {
        register_rest_route('emmerce/v1', '/internal/api-key', array(
            'methods' => 'GET',
            'callback' => [__CLASS__, 'emmerce_get_api_key'],
            'permission_callback' => [__CLASS__, 'emmerce_verify_origin'],
            'args' => array()
        ));
    }

    /**
     * Verify the nonce for a request.
     *
     * This function verifies that the nonce sent in the request matches the expected value.
     *
     * @param WP_REST_Request $request The request to verify.
     *
     * @return WP_Error|true WP_Error if the nonce is invalid, true otherwise.
     *
     * @since 1.0.1
     */
    public static function emmerce_verify_origin($request) {
        
        $current_route = $request->get_route();

        $required_path = '/emmerce/v1/internal/api-key';

        if ($current_route !== $required_path) {
            return new WP_Error(
                'rest_forbidden', 
                'Invalid endpoint access.', 
                array('status' => 403)
            );
        }
        
        return true;
    }
    
    /**
     * Retrieve the Emmerce API key.
     *
     * This function retrieves the Emmerce API key from the WordPress options table and returns it as a REST response.
     *
     * @param WP_REST_Request $request The request to handle.
     *
     * @return WP_Error|WP_REST_Response WP_Error if the API key is not found, WP_REST_Response containing the API key otherwise.
     *
     * @since 1.0.0
     */
    public static function emmerce_get_api_key($request) {
        $api_key = get_option('emmerce_api_key');
        
        if (empty($api_key)) {
            return new WP_Error(
                'no_api_key',
                'API key not configured yet.',
                array('status' => 409)
            );
        }
        
        return rest_ensure_response(array(
            'success' => true,
            'api_key' => $api_key
        ));
    }

    /**
     * Handles authentication errors when accessing the internal API key endpoint.
     *
     * If an authentication error has already been triggered and we're currently on the internal API key endpoint,
     * this function will clear the error and allow the request to proceed to the permission_callback.
     *
     * @param mixed $result The authentication error to handle.
     *
     * @return mixed The handled authentication error.
     *
     * @since 1.0.0
     */
    public static function emmerce_handle_rest_authentication_errors($result) {
        // If there's already an authentication error, and we're on our endpoint, clear it
        if ( ! empty( $result ) && ! empty( esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) ) ) ) {
            
            $request_uri = esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) );

            if ( strpos( $request_uri, '/emmerce/v1/internal/api-key' ) !== false ) {
                return true; 
            }
        }
        return $result;
    }
}

EmmerceChatBot::init();