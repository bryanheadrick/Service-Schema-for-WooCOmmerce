<?php

/**
 * Fired when the plugin is uninstalled.
 *
 * @package CatManStudios_Systems_Service_Schema_For_WooCommerce
 */

if (! defined('WP_UNINSTALL_PLUGIN')) {
	exit;
}

delete_option('cmss_default_provider');
delete_option('cmss_default_service_type');
delete_option('cmss_default_area_served');

global $wpdb;

$wpdb->delete($wpdb->postmeta, array('meta_key' => '_cmss_is_service')); // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
$wpdb->delete($wpdb->postmeta, array('meta_key' => '_cmss_service_provider')); // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
$wpdb->delete($wpdb->postmeta, array('meta_key' => '_cmss_service_type')); // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
$wpdb->delete($wpdb->postmeta, array('meta_key' => '_cmss_service_area_served')); // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
