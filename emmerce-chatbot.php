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
    }

    /**
     * Enqueue the chat scripts and styles
     *
     * @since 1.0.0
     * @return void
     */
    public static function emmerce_chatbot_enqueue_scripts() {
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
            ]
        );
    }

    /**
     * Inject the chatbot container after the page has loaded.
     *
     * @since 1.0.1
     * @return void
     */
    public static function emmerce_chatbot_inject_container() {
        echo '<div id="emmerce-chatbot-root"></div>';
    }
}

EmmerceChatBot::init();
