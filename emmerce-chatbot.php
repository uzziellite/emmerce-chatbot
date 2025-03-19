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
                    'apiUrl' => 'http://your-django-backend.com/api',
                    'wsUrl' => 'ws://your-django-backend.com/ws/chat/',
                    'position' => esc_attr(get_option('emmerce_chat_position', 'right')),
                    'accessToken' => esc_attr(get_option('emmerce_access_token'))
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
        register_setting('emmerce_chatbot_settings_group', 'emmerce_username');
        register_setting('emmerce_chatbot_settings_group', 'emmerce_hashed_password', [__CLASS__, 'sanitize_password']);
        add_settings_section('emmerce_api_settings', 'API Credentials', [__CLASS__, 'emmerce_api_settings_callback'], 'emmerce-chatbot-settings');
        add_settings_field('emmerce_username', 'Username', [__CLASS__, 'emmerce_username_callback'], 'emmerce-chatbot-settings', 'emmerce_api_settings');
        add_settings_field('emmerce_password', 'Password', [__CLASS__, 'emmerce_password_callback'], 'emmerce-chatbot-settings', 'emmerce_api_settings');
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
        echo '<p>Enter your Emmerce API credentials below.</p>';
    }

    /**
     * Username callback.
     *
     * @since 1.0.0
     * @return void
     */
    public static function emmerce_username_callback() {
        $username = esc_attr(get_option('emmerce_username'));
        echo "<input type='text' name='emmerce_username' value='$username' />";
    }

    /**
     * Password callback.
     *
     * @since 1.0.0
     * @return void
     */
    public static function emmerce_password_callback() {
        echo "<input type='password' name='emmerce_password' value='' />";
        echo "<p class='description'>Enter password to update it. (For security reasons, this field will remain blank after saving)</p>";
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
     * Sanitize password and hash it. Helps to protect the clients' Emmerce
     * Dashboard by giving the hacker an extra task of cracking this password
     * in case the WordPress site is breached.
     *
     * @since 1.0.0
     * @param string $password The password to sanitize.
     * @return string The hashed password.
     */
    public static function sanitize_password($password) {
        if (!empty($password)) {
            return wp_hash_password($password);
        }
        return get_option('emmerce_hashed_password');
    }

    /**
     * Settings saved or error notices.
     *
     * @since 1.0.0
     * @return void
     */
    public static function emmerce_settings_notices() {
        if (isset($_GET['settings-updated']) && $_GET['settings-updated']) {
            if (isset($_GET['emmerce_error']) && $_GET['emmerce_error']) {
                $error_message = sanitize_text_field(urldecode($_GET['emmerce_error']));
                echo '<div class="notice notice-error is-dismissible"><p>' . esc_html($error_message) . '</p></div>';
            } else {
                echo '<div class="notice notice-success is-dismissible"><p>Settings successfully updated.</p></div>';
            }
        }
    }

    /**
     * Get the access token for making subsequent API connections
     * @since 1.0.0
     * @return string
     */
    public static function get_access_token() {
        $username = get_option('emmerce_username');
        $hashed_password = get_option('emmerce_hashed_password');
        $password = $_POST['emmerce_password'];
        if (wp_check_password($password, $hashed_password)) {
            //your code here.
        } else {
            //your code here.
            $error_message = urlencode("Incorrect Password");
            wp_redirect(add_query_arg('emmerce_error', $error_message, admin_url('admin.php?page=emmerce-chatbot-settings')));
            exit;
        }
    }

}

EmmerceChatBot::init();