<?php

/**
 * Class CMSS_ProductFieldsTest
 *
 * @package CatManStudios_Systems_Service_Schema_For_WooCommerce
 */

/**
 * Tests Service field registration and persistence.
 */
class CMSS_ProductFieldsTest extends WP_UnitTestCase
{

	public function test_is_service_defaults_to_no()
	{
		$product = new WC_Product_Simple();
		$product->save();

		$this->assertSame('no', $product->get_meta('_cmss_is_service', true));
	}

	public function test_saving_service_checkbox_sets_meta_and_forces_virtual()
	{
		$product = new WC_Product_Simple();
		$product->save();

		$_POST['_cmss_is_service']          = 'yes';
		$_POST['_cmss_service_provider']    = 'Acme Plumbing';
		$_POST['_cmss_service_type']        = 'Plumbing';
		$_POST['_cmss_service_area_served'] = 'Greater Boston Area';

		do_action('woocommerce_admin_process_product_object', $product);
		$product->save();

		$saved = wc_get_product($product->get_id());

		$this->assertSame('yes', $saved->get_meta('_cmss_is_service', true));
		$this->assertTrue($saved->get_virtual());
		$this->assertSame('Acme Plumbing', $saved->get_meta('_cmss_service_provider', true));
		$this->assertSame('Plumbing', $saved->get_meta('_cmss_service_type', true));
		$this->assertSame('Greater Boston Area', $saved->get_meta('_cmss_service_area_served', true));

		unset($_POST['_cmss_is_service'], $_POST['_cmss_service_provider'], $_POST['_cmss_service_type'], $_POST['_cmss_service_area_served']);
	}

	public function test_unchecking_service_does_not_force_virtual_off()
	{
		$product = new WC_Product_Simple();
		$product->set_virtual(true);
		$product->save();

		$_POST = array(); // Simulate the checkbox being unchecked (absent from $_POST).

		do_action('woocommerce_admin_process_product_object', $product);
		$product->save();

		$saved = wc_get_product($product->get_id());

		$this->assertSame('no', $saved->get_meta('_cmss_is_service', true));
		$this->assertTrue($saved->get_virtual(), 'Unchecking Service must not force Virtual back off, since a merchant may have set Virtual independently.');
	}

	public function test_product_data_tabs_includes_service_tab()
	{
		$tabs = apply_filters('woocommerce_product_data_tabs', array());

		$this->assertArrayHasKey('cmss_service', $tabs);
		$this->assertSame('cmss_service_product_data', $tabs['cmss_service']['target']);
	}

	public function test_product_type_options_includes_service_checkbox()
	{
		$options = apply_filters('product_type_options', array());

		$this->assertArrayHasKey('cmss_is_service', $options);
		$this->assertSame('_cmss_is_service', $options['cmss_is_service']['id']);
		$this->assertNotEmpty($options['cmss_is_service']['description']);
	}
}
