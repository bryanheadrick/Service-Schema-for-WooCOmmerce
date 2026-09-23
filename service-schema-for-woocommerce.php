<?php

/**
 * Plugin Name:     Plumbline Labs Service Schema For WooCommerce
 * Description:     Adds a "Service" option to WooCommerce products and outputs schema.org Service structured data instead of Product for those items.
 * Author:          Plumbline Labs
 * Author URI:      https://bryanheadrick.com
 * Text Domain:     service-schema-for-woocommerce
 * Domain Path:     /languages
 * Version:         1.1.0
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
		esc_html__('Plumbline Labs Service Schema For WooCommerce requires WooCommerce to be installed and active.', 'service-schema-for-woocommerce')
	);
}
