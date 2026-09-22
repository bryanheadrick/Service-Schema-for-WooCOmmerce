<?php

/**
 * Overrides structured data output for service products.
 *
 * @package Service_Schema_For_WooCommerce
 */

if (! defined('ABSPATH')) {
	exit;
}

/**
 * Rewrites JSON-LD markup to schema.org Service for flagged products.
 */
class SSW_Structured_Data
{

	/**
	 * Registers hooks.
	 */
	public function __construct()
	{
		add_filter('woocommerce_structured_data_product', array($this, 'rewrite_markup'), 20, 2);
		add_filter('woocommerce_structured_data_type_for_page', array($this, 'add_service_data_type'));
	}

	/**
	 * Ensures WC_Structured_Data::output_structured_data() doesn't drop our
	 * rewritten markup: it buckets queued data by strtolower( @type ) and
	 * only prints buckets present in this whitelist, which core hardcodes
	 * to 'product' (never 'service') for product pages.
	 *
	 * @param array $types Structured data types allowed for the current page.
	 * @return array
	 */
	public function add_service_data_type($types)
	{
		if (in_array('product', $types, true)) {
			$types[] = 'service';
		}

		return $types;
	}

	/**
	 * Rewrites Product markup to Service markup when the product is flagged as a service.
	 *
	 * @param array      $markup  Structured data markup built by WC_Structured_Data.
	 * @param WC_Product $product Product the markup was built for.
	 * @return array
	 */
	public function rewrite_markup($markup, $product)
	{
		if ('yes' !== $product->get_meta('_is_service', true)) {
			return $markup;
		}

		$markup['@type'] = 'Service';

		unset($markup['sku'], $markup['gtin']);

		$provider = $this->resolve_field($product, '_service_provider', 'service_schema_wc_default_provider');

		if ('' === $provider) {
			$provider = get_bloginfo('name');
		}

		$markup['provider'] = array(
			'@type' => 'Organization',
			'name'  => $provider,
		);

		$service_type = $this->resolve_field($product, '_service_type', 'service_schema_wc_default_service_type');

		if ('' !== $service_type) {
			$markup['serviceType'] = $service_type;
		}

		$area_served = $this->resolve_field($product, '_service_area_served', 'service_schema_wc_default_area_served');

		if ('' !== $area_served) {
			$markup['areaServed'] = $area_served;
		}

		return $markup;
	}

	/**
	 * Resolves a service field: per-product meta first, then the site-wide default option.
	 *
	 * @param WC_Product $product    Product to read meta from.
	 * @param string     $meta_key   Product meta key.
	 * @param string     $option_key wp_options key for the site-wide default.
	 * @return string
	 */
	private function resolve_field($product, $meta_key, $option_key)
	{
		$value = $product->get_meta($meta_key, true);

		if ('' !== $value) {
			return $value;
		}

		return get_option($option_key, '');
	}
}
