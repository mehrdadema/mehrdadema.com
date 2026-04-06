<?php
/**
 * Uninstall – runs when the plugin is deleted from the WordPress admin.
 * Removes all plugin-created database tables and options.
 */

if ( ! defined( 'ABSPATH' ) || ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

global $wpdb;

$wpdb->query( 'DROP TABLE IF EXISTS ' . $wpdb->prefix . 'cubeportfolio' );
$wpdb->query( 'DROP TABLE IF EXISTS ' . $wpdb->prefix . 'cubeportfolio_items' );

delete_option( 'cubeportfolio_version' );
delete_option( 'cubeportfolio_settings' );
