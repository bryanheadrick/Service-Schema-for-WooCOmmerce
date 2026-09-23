<?php

/**
 * Class PLBL_StructuredDataTest
 *
 * @package Plumbline_Labs_Service_Schema_For_WooCommerce
 */

/**
 * Tests the Service structured data override.
 */
class PLBL_StructuredDataTest extends WP_UnitTestCase
{

	public function test_non_service_product_markup_is_unchanged()
	{
		$product = new WC_Product_Simple();
		$product->set_regular_price('10.00');
		$product->save();

		$markup = array(
			'@type' => 'Product',
			'sku'   => 'ABC123',
			'gtin'  => '0012345678905',
		);

		$filtered = apply_filters('woocommerce_structured_data_product', $markup, $product);

		$this->assertSame($markup, $filtered);
	}

	public function test_service_product_type_is_rewritten_and_ids_removed()
	{
		$product = new WC_Product_Simple();
		$product->set_regular_price('100.00');
		$product->update_meta_data('_plbl_is_service', 'yes');
		$product->update_meta_data('_plbl_service_provider', 'Acme Plumbing');
		$product->update_meta_data('_plbl_service_type', 'Plumbing');
		$product->update_meta_data('_plbl_service_area_served', 'Greater Boston Area');
		$product->save();

		$markup = array(
			'@type' => 'Product',
			'sku'   => 'ABC123',
			'gtin'  => '0012345678905',
			'name'  => 'Drain Cleaning',
		);

		$filtered = apply_filters('woocommerce_structured_data_product', $markup, $product);

		$this->assertSame('Service', $filtered['@type']);
		$this->assertArrayNotHasKey('sku', $filtered);
		$this->assertArrayNotHasKey('gtin', $filtered);
		$this->assertSame('Drain Cleaning', $filtered['name']);
		$this->assertSame(
			array(
				'@type' => 'Organization',
				'name'  => 'Acme Plumbing',
			),
			$filtered['provider']
		);
		$this->assertSame('Plumbing', $filtered['serviceType']);
		$this->assertSame('Greater Boston Area', $filtered['areaServed']);
	}

	public function test_service_product_falls_back_to_site_defaults()
	{
		update_option('plbl_default_provider', 'Default Co');
		update_option('plbl_default_service_type', 'Consulting');
		update_option('plbl_default_area_served', 'United States');

		$product = new WC_Product_Simple();
		$product->set_regular_price('100.00');
		$product->update_meta_data('_plbl_is_service', 'yes');
		$product->save();

		$filtered = apply_filters('woocommerce_structured_data_product', array('@type' => 'Product'), $product);

		$this->assertSame('Default Co', $filtered['provider']['name']);
		$this->assertSame('Consulting', $filtered['serviceType']);
		$this->assertSame('United States', $filtered['areaServed']);

		delete_option('plbl_default_provider');
		delete_option('plbl_default_service_type');
		delete_option('plbl_default_area_served');
	}

	public function test_service_product_falls_back_to_site_title_when_no_provider_anywhere()
	{
		delete_option('plbl_default_provider');

		$product = new WC_Product_Simple();
		$product->set_regular_price('100.00');
		$product->update_meta_data('_plbl_is_service', 'yes');
		$product->save();

		$filtered = apply_filters('woocommerce_structured_data_product', array('@type' => 'Product'), $product);

		$this->assertSame(get_bloginfo('name'), $filtered['provider']['name']);
	}

	public function test_service_type_and_area_served_omitted_when_never_set()
	{
		delete_option('plbl_default_service_type');
		delete_option('plbl_default_area_served');

		$product = new WC_Product_Simple();
		$product->set_regular_price('100.00');
		$product->update_meta_data('_plbl_is_service', 'yes');
		$product->save();

		$filtered = apply_filters('woocommerce_structured_data_product', array('@type' => 'Product'), $product);

		$this->assertArrayNotHasKey('serviceType', $filtered);
		$this->assertArrayNotHasKey('areaServed', $filtered);
	}
}
