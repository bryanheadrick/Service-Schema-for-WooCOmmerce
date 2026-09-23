<?php

/**
 * Plugin Name:     Plumbline Labs Service Schema For WooCommerce
 * Description:     Adds a "Service" option to WooCommerce products and outputs schema.org Service structured data instead of Product for those items.
 * Author:          Plumbline Labs
 * Author URI:      https://bryanheadrick.com
 * Text Domain:     plumbline-labs-service-schema-for-woocommerce
 * Domain Path:     /languages
 * Version:         0.1.1
 * Requires PHP:    7.4
 *
 * @package         Plumbline_Labs_Service_Schema_For_WooCommerce
 *
 * Requires Plugins: woocommerce
 * License:          GPL v2 or later
 * License URI:      https://www.gnu.org/licenses/gpl-2.0.html
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License, version 2, as
 * published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see https://www.gnu.org/licenses/gpl-2.0.html.
 */

if (! defined('ABSPATH')) {
	exit; // Exit if accessed directly.
}

define('PLBL_PLUGIN_FILE', __FILE__);
define('PLBL_PLUGIN_DIR', plugin_dir_path(__FILE__));

register_activation_hook(__FILE__, 'plbl_migrate_legacy_data');

/**
 * One-time migration from the pre-rebrand `ssw`/unprefixed keys to the
 * `plbl_`-prefixed equivalents. Safe to run more than once: it only acts
 * on rows still stored under the legacy keys.
 */
function plbl_migrate_legacy_data()
{
	global $wpdb;

	$legacy_to_new_meta = array(
		'_is_service'          => '_plbl_is_service',
		'_service_provider'    => '_plbl_service_provider',
		'_service_type'        => '_plbl_service_type',
		'_service_area_served' => '_plbl_service_area_served',
	);

	foreach ($legacy_to_new_meta as $legacy_key => $new_key) {
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT post_id, meta_value FROM {$wpdb->postmeta} WHERE meta_key = %s", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$legacy_key
			)
		); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

		foreach ($rows as $row) {
			update_post_meta($row->post_id, $new_key, $row->meta_value);
			delete_post_meta($row->post_id, $legacy_key);
		}
	}

	$legacy_to_new_option = array(
		'service_schema_wc_default_provider'     => 'plbl_default_provider',
		'service_schema_wc_default_service_type' => 'plbl_default_service_type',
		'service_schema_wc_default_area_served'  => 'plbl_default_area_served',
	);

	foreach ($legacy_to_new_option as $legacy_key => $new_key) {
		$value = get_option($legacy_key, null);

		if (null !== $value) {
			update_option($new_key, $value);
			delete_option($legacy_key);
		}
	}
}

/**
 * Boots the plugin once all plugins have loaded, guarding on WooCommerce being active.
 */
function plbl_init()
{
	if (! class_exists('WooCommerce')) {
		add_action('admin_notices', 'plbl_missing_woocommerce_notice');
		return;
	}

	require_once PLBL_PLUGIN_DIR . 'includes/class-plbl-product-fields.php';
	require_once PLBL_PLUGIN_DIR . 'includes/class-plbl-admin-settings.php';
	require_once PLBL_PLUGIN_DIR . 'includes/class-plbl-structured-data.php';

	new PLBL_Product_Fields();
	new PLBL_Admin_Settings();
	new PLBL_Structured_Data();
}
add_action('plugins_loaded', 'plbl_init');

/**
 * Prints a contextual, non-persistent notice when WooCommerce is not active.
 */
function plbl_missing_woocommerce_notice()
{
	$screen = get_current_screen();

	if (! $screen || ! in_array($screen->id, array('plugins', 'plugins-network'), true)) {
		return;
	}

	printf(
		'<div class="notice notice-error"><p>%s</p></div>',
		esc_html__('Plumbline Labs Service Schema For WooCommerce requires WooCommerce to be installed and active.', 'plumbline-labs-service-schema-for-woocommerce')
	);
}
