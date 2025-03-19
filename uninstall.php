<?php
/**
 * Uninstall Emmerce Chatbot
 *
 * @package EmmerceChatbot
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
        exit;
}

delete_option( 'emmerce_access_token' );
delete_option( 'emmerce_chat_active' );
delete_option( 'emmerce_chat_position' );
delete_option( 'emmerce_refresh_expiration' );
delete_option( 'emmerce_refresh_token' );
delete_option( 'emmerce_token_expiration' );
delete_option( 'emmerce_username' );