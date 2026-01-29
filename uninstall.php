<?php
/**
 * Uninstall Emmerce Chatbot
 *
 * @package EmmerceChatbot
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
        exit;
}

delete_option( 'emmerce_api_key' );
delete_option( 'emmerce_chat_active' );
delete_option( 'emmerce_chat_client_id' );
delete_option( 'emmerce_chat_position' );