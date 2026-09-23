<?php

/**
 * Fired when the plugin is uninstalled.
 *
 * @package Plumbline_Labs_Service_Schema_For_WooCommerce
 */

if (! defined('WP_UNINSTALL_PLUGIN')) {
	exit;
}

delete_option('plbl_default_provider');
delete_option('plbl_default_service_type');
delete_option('plbl_default_area_served');

global $wpdb;

$wpdb->delete($wpdb->postmeta, array('meta_key' => '_plbl_is_service')); // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
$wpdb->delete($wpdb->postmeta, array('meta_key' => '_plbl_service_provider')); // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
$wpdb->delete($wpdb->postmeta, array('meta_key' => '_plbl_service_type')); // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
$wpdb->delete($wpdb->postmeta, array('meta_key' => '_plbl_service_area_served')); // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
