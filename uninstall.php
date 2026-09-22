<?php

/**
 * Fired when the plugin is uninstalled.
 *
 * @package Service_Schema_For_WooCommerce
 */

if (! defined('WP_UNINSTALL_PLUGIN')) {
	exit;
}

delete_option('service_schema_wc_default_provider');
delete_option('service_schema_wc_default_service_type');
delete_option('service_schema_wc_default_area_served');

global $wpdb;

$wpdb->delete($wpdb->postmeta, array('meta_key' => '_is_service')); // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
$wpdb->delete($wpdb->postmeta, array('meta_key' => '_service_provider')); // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
$wpdb->delete($wpdb->postmeta, array('meta_key' => '_service_type')); // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
$wpdb->delete($wpdb->postmeta, array('meta_key' => '_service_area_served')); // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
