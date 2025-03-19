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
    }

    /**
     * Enqueue the chat scripts and styles
     *
     * @since 1.0.0
     * @return void
     */
    public static function emmerce_chatbot_enqueue_scripts() {
        $active = esc_attr(get_option('emmerce_chat_active', '1'));
        if($active === '1') {

            $access_token = esc_attr(get_option('emmerce_access_token'));
            $token_expiration = esc_attr(get_option('emmerce_token_expiration'));

            if (time() > $token_expiration) {
                $access_token = self::refresh_access_token();
            }

            wp_enqueue_script(
                'emmerce-chatbot-js',
                plugin_dir_url(__FILE__) . 'dist/assets/index-CmHvqjyk.js',
                array(),
                '1.0.0',
                true
            );
    
            add_filter('script_loader_tag', function ($tag, $handle) {
                if ('emmerce-chatbot-js' === $handle) {
                    $tag = str_replace(' src=', ' defer src=', $tag);
                }
                return $tag;
            }, 10, 2);
    
            wp_enqueue_style(
                'emmerce-chatbot-css',
                plugin_dir_url(__FILE__) . 'dist/assets/index-CuhENZM8.css',
                array(),
                '1.0.0'
            );
    
            add_filter('style_loader_tag', function ($tag, $handle) {
                if ('emmerce-chatbot-css' === $handle) {
                    $tag = str_replace('rel="stylesheet"', 'rel="preload" as="style" onload="this.onload=null;this.rel=\'stylesheet\'"', $tag);
                }
                return $tag;
            }, 10, 2);
    
            wp_localize_script(
                'emmerce-chatbot-js',
                'wpChatbot',
                [
                    'position' => esc_attr(get_option('emmerce_chat_position', 'right')),
                    'accessToken' => $access_token
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
        $access_token = get_option('emmerce_access_token');
        $refresh_token = get_option('emmerce_refresh_token');

        ?>
        <div class="wrap">
            <h1>Emmerce Chat Settings</h1>
            <p>Emmerce Chat adds a chat widget to your website to help manage all your website chats together with your conversations from other platforms (Facebook, Whatsapp, Instagram...) from the Emmerce customer portal.</p>
            <form method="post" action="options.php">
                <?php
                settings_fields('emmerce_chatbot_settings_group');
                do_settings_sections('emmerce-chatbot-settings');

                if (empty($access_token) || empty($refresh_token)) {
                    add_settings_field('emmerce_username', 'Username', [__CLASS__, 'emmerce_username_callback'], 'emmerce-chatbot-settings', 'emmerce_api_settings');
                    add_settings_field('emmerce_password', 'Password', [__CLASS__, 'emmerce_password_callback'], 'emmerce-chatbot-settings', 'emmerce_api_settings');
                } else {
                    add_settings_field('emmerce_access_token_display', 'Access Token', [__CLASS__, 'emmerce_access_token_display_callback'], 'emmerce-chatbot-settings', 'emmerce_api_settings');
                    add_settings_field('emmerce_refresh_token_display', 'Refresh Token', [__CLASS__, 'emmerce_refresh_token_display_callback'], 'emmerce-chatbot-settings', 'emmerce_api_settings');
                }

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
        register_setting('emmerce_chatbot_settings_group', 'emmerce_username', [__CLASS__, 'fetch_access_token_on_save']);

        add_settings_section('emmerce_api_settings', 'API Credentials', [__CLASS__, 'emmerce_api_settings_callback'], 'emmerce-chatbot-settings');

        // Register these fields conditionally
        if (get_option('emmerce_access_token') && get_option('emmerce_refresh_token')) {
            add_settings_field('emmerce_access_token_display', 'Access Token', [__CLASS__, 'emmerce_access_token_display_callback'], 'emmerce-chatbot-settings', 'emmerce_api_settings');
            add_settings_field('emmerce_refresh_token_display', 'Refresh Token', [__CLASS__, 'emmerce_refresh_token_display_callback'], 'emmerce-chatbot-settings', 'emmerce_api_settings');
        } else {
            add_settings_field('emmerce_username_display', 'Emmerce Portal Username', [__CLASS__, 'emmerce_username_callback'], 'emmerce-chatbot-settings', 'emmerce_api_settings');
            add_settings_field('emmerce_password_display', 'Emmerce Portal Password', [__CLASS__, 'emmerce_password_callback'], 'emmerce-chatbot-settings', 'emmerce_api_settings');
        }

        register_setting('emmerce_chatbot_settings_group', 'emmerce_chat_position', 'sanitize_text_field');
        register_setting('emmerce_chatbot_settings_group', 'emmerce_chat_active', 'sanitize_text_field');

        add_settings_section('emmerce_widget_settings', 'Chat Widget Settings', [__CLASS__, 'emmerce_widget_settings_callback'], 'emmerce-chatbot-settings');
        add_settings_field('emmerce_chat_position', 'Chat Floater Position', [__CLASS__, 'emmerce_chat_position_callback'], 'emmerce-chatbot-settings', 'emmerce_widget_settings');
        add_settings_field('emmerce_chat_active', 'Activate/Deactivate Chat', [__CLASS__, 'emmerce_chat_active_callback'], 'emmerce-chatbot-settings', 'emmerce_widget_settings');

        add_action('admin_notices', [__CLASS__, 'emmerce_settings_notices']);
    }

    /**
     * API settings callback.
     *
     * @since 1.0.0
     * @return void
     */
    public static function emmerce_api_settings_callback() {
        $access_token = get_option('emmerce_access_token');
        $refresh_token = get_option('emmerce_refresh_token');

        if (!empty($access_token) && !empty($refresh_token)) {
            echo '<p class="notice notice-success">Emmerce Chatbot is ready!</p>';
        }else{
            echo '<p class="notice notice-warning">Please complete your Emmerce Chatbot Configuration</p>';
        }
    }

    /**
     * Username callback.
     *
     * @since 1.0.0
     * @return void
     */
    public static function emmerce_username_callback() {
        echo "<input type='text' name='emmerce_username' value='' />";
    }

    /**
     * Password callback.
     *
     * @since 1.0.0
     * @return void
     */
    public static function emmerce_password_callback() {
        echo "<input type='password' name='emmerce_password' value='' />";
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
     * Fetch access token on save.
     *
     * @since 1.0.0
     * @param string $username The username.
     * @return void.
     */
    public static function fetch_access_token_on_save($username) {
        $access_token = get_option('emmerce_access_token');
        $refresh_token = get_option('emmerce_refresh_token');

        if (!empty($access_token) && !empty($refresh_token)) {
            return $username;
        }

        $password = $_POST['emmerce_password'];

        $access_token_data = self::get_access_token_from_api($username, $password);

        if ($access_token_data && isset($access_token_data['access_token']) && isset($access_token_data['refresh_token'])) {
            update_option('emmerce_access_token', $access_token_data['access_token']);
            update_option('emmerce_refresh_token', $access_token_data['refresh_token']);
            update_option('emmerce_token_expiration', time() + 1744970113 - 1742378113);
            update_option('emmerce_refresh_expiration', time() + 1747562113 - 1742378113);

            wp_redirect(add_query_arg('emmerce_success', 'Updated', admin_url('admin.php?page=emmerce-chatbot-settings')));
            exit;
        } else {
            $error_message = urlencode("Failed to retrieve access token.");
            wp_redirect(add_query_arg('emmerce_error', $error_message, admin_url('admin.php?page=emmerce-chatbot-settings')));
            exit;
        }
    }

    /**
     * Get access token from API.
     *
     * @since 1.0.0
     * @param string $username The username.
     * @param string $password The password.
     * @return array|false The access token data or false on failure.
     */
    public static function get_access_token_from_api($username, $password) {
        $api_url = 'https://demoinfinity.emmerce.io/api/v1/auth-usr/login';

        $response = wp_remote_post($api_url, array(
            'headers' => array('Content-Type' => 'application/json'),
            'body' => json_encode(array(
                'email' => $username,
                'password' => $password,
            )),
        ));

        if (is_wp_error($response)) {
            return false;
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        if (isset($data['Details']['access']) && isset($data['Details']['refresh'])) {
            return array(
                'access_token' => $data['Details']['access'],
                'refresh_token' => $data['Details']['refresh'],
            );
        } else {
            return false;
        }
    }

    /**
     * Refresh the access token as need arises
     * @since 1.0.0
     * @return array|false The access token data or false on failure.
     */
    public static function refresh_access_token() {
        $refresh_token = get_option('emmerce_refresh_token');
        $refresh_expiration = get_option('emmerce_refresh_expiration');

        if (empty($refresh_token) || time() > $refresh_expiration) {
            return false;
        }

        $api_url = 'https://demoinfinity.emmerce.io/api/v1/auth-usr/token/refresh';

        $response = wp_remote_post($api_url, array(
            'headers' => array('Content-Type' => 'application/json'),
            'body' => json_encode(array(
                'refresh' => $refresh_token,
            )),
        ));

        if (is_wp_error($response)) {
            return false;
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        if (isset($data['access'])) {
            update_option('emmerce_access_token', $data['access']);
            update_option('emmerce_token_expiration', time() + 1747562113);
            return $data['access'];
        } else {
            return false;
        }
    }

    /**
     * Access token display callback.
     *
     * @since 1.0.0
     * @return void
     */
    public static function emmerce_access_token_display_callback() {
        $access_token = get_option('emmerce_access_token');
        echo "<input type='text' value='" . esc_attr(substr($access_token, 0, 50)) . "...' readonly />";
    }

    /**
     * Refresh token display callback.
     *
     * @since 1.0.0
     * @return void
     */
    public static function emmerce_refresh_token_display_callback() {
        $refresh_token = get_option('emmerce_refresh_token');
        echo "<input type='text' value='" . esc_attr(substr($refresh_token, 0, 50)) . "...' readonly />";
    }

}

EmmerceChatBot::init();