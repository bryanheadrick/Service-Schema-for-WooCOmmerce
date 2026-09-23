<?php

/**
 * Registers Service product fields.
 *
 * @package Plumbline_Labs_Service_Schema_For_WooCommerce
 */

if (! defined('ABSPATH')) {
	exit;
}

/**
 * Adds the Service checkbox and tab to the product data panel.
 */
class PLBL_Product_Fields
{

	/**
	 * Registers hooks.
	 */
	public function __construct()
	{
		add_filter('product_type_options', array($this, 'add_service_checkbox_option'));
		add_filter('woocommerce_product_data_tabs', array($this, 'add_service_tab'));
		add_action('woocommerce_product_data_panels', array($this, 'render_service_panel'));
		add_action('woocommerce_admin_process_product_object', array($this, 'save_fields'));
		add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_script'));
		add_action('woocommerce_new_product', array($this, 'set_default_service_meta'));
	}

	/**
	 * Defaults a newly created product's `_plbl_is_service` meta to 'no' so the
	 * value is always a real 'yes'/'no' string rather than unset/empty.
	 *
	 * @param int $product_id Newly created product ID.
	 */
	public function set_default_service_meta($product_id)
	{
		add_post_meta($product_id, '_plbl_is_service', 'no', true);
	}

	/**
	 * Adds the "Service" checkbox alongside Virtual/Downloadable, next to the product type dropdown.
	 *
	 * @param array $options Existing product type options.
	 * @return array
	 */
	public function add_service_checkbox_option($options)
	{
		$options['plbl_is_service'] = array(
			'id'            => '_plbl_is_service',
			'wrapper_class' => 'show_if_simple show_if_variable',
			'label'         => __('Service', 'plumbline-labs-service-schema-for-woocommerce'),
			'description'   => __('This is a service (implies Virtual; outputs schema.org Service structured data).', 'plumbline-labs-service-schema-for-woocommerce'),
			'default'       => 'no',
		);

		return $options;
	}

	/**
	 * Adds the Service tab, shown only for Simple/Variable products when the checkbox is checked.
	 *
	 * @param array $tabs Existing product data tabs.
	 * @return array
	 */
	public function add_service_tab($tabs)
	{
		$tabs['plbl_service'] = array(
			'label'    => __('Service', 'plumbline-labs-service-schema-for-woocommerce'),
			'target'   => 'plbl_service_product_data',
			'class'    => array('show_if_plbl_service'),
			'priority' => 25,
		);

		return $tabs;
	}

	/**
	 * Renders the Service tab panel fields.
	 */
	public function render_service_panel()
	{
		echo '<div id="plbl_service_product_data" class="panel woocommerce_options_panel">';

		echo '<div class="options_group">';

		woocommerce_wp_text_input(
			array(
				'id'          => '_plbl_service_provider',
				'label'       => __('Provider', 'plumbline-labs-service-schema-for-woocommerce'),
				'desc_tip'    => true,
				'description' => __('Leave blank to use the site-wide default from WooCommerce > Settings > Products.', 'plumbline-labs-service-schema-for-woocommerce'),
			)
		);

		woocommerce_wp_text_input(
			array(
				'id'          => '_plbl_service_type',
				'label'       => __('Service Type', 'plumbline-labs-service-schema-for-woocommerce'),
				'desc_tip'    => true,
				'description' => __('E.g. "Plumbing" or "Consulting". Leave blank to use the site-wide default.', 'plumbline-labs-service-schema-for-woocommerce'),
			)
		);

		woocommerce_wp_text_input(
			array(
				'id'          => '_plbl_service_area_served',
				'label'       => __('Area Served', 'plumbline-labs-service-schema-for-woocommerce'),
				'desc_tip'    => true,
				'description' => __('E.g. "Greater Boston Area". Leave blank to use the site-wide default.', 'plumbline-labs-service-schema-for-woocommerce'),
			)
		);

		echo '</div>';
		echo '</div>';
	}

	/**
	 * Persists Service fields, forcing Virtual on when Service is checked.
	 *
	 * @param WC_Product $product Product object being saved.
	 */
	public function save_fields($product)
	{
		$is_service = isset($_POST['_plbl_is_service']) ? 'yes' : 'no'; // phpcs:ignore WordPress.Security.NonceVerification.Missing -- core's own product save handler verifies the nonce before this hook fires.

		$product->update_meta_data('_plbl_is_service', $is_service);

		if ('yes' === $is_service) {
			$product->set_virtual(true);
		}

		$product->update_meta_data(
			'_plbl_service_provider',
			isset($_POST['_plbl_service_provider']) ? sanitize_text_field(wp_unslash($_POST['_plbl_service_provider'])) : '' // phpcs:ignore WordPress.Security.NonceVerification.Missing
		);

		$product->update_meta_data(
			'_plbl_service_type',
			isset($_POST['_plbl_service_type']) ? sanitize_text_field(wp_unslash($_POST['_plbl_service_type'])) : '' // phpcs:ignore WordPress.Security.NonceVerification.Missing
		);

		$product->update_meta_data(
			'_plbl_service_area_served',
			isset($_POST['_plbl_service_area_served']) ? sanitize_text_field(wp_unslash($_POST['_plbl_service_area_served'])) : '' // phpcs:ignore WordPress.Security.NonceVerification.Missing
		);
	}

	/**
	 * Enqueues the admin JS that toggles the Service tab's visibility.
	 *
	 * @param string $hook Current admin page hook suffix.
	 */
	public function enqueue_admin_script($hook)
	{
		if (! in_array($hook, array('post.php', 'post-new.php'), true)) {
			return;
		}

		global $post;

		if (! $post || 'product' !== $post->post_type) {
			return;
		}

		wp_enqueue_script(
			'plbl-admin-product-service-tab',
			plugins_url('assets/js/admin-product-service-tab.js', PLBL_PLUGIN_FILE),
			array('jquery'),
			'0.1.1',
			true
		);
	}
}
