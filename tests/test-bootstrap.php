<?php

/**
 * Class CMSS_BootstrapTest
 *
 * @package CatManStudios_Systems_Service_Schema_For_WooCommerce
 */

/**
 * Tests the plugin bootstrap guard.
 */
class CMSS_BootstrapTest extends WP_UnitTestCase
{

	public function test_cmss_init_function_exists()
	{
		$this->assertTrue(function_exists('cmss_init'));
	}

	public function test_classes_loaded_when_woocommerce_active()
	{
		$this->assertTrue(class_exists('WooCommerce'), 'WooCommerce must be active in the test environment.');
		$this->assertTrue(class_exists('CMSS_Product_Fields'));
		$this->assertTrue(class_exists('CMSS_Admin_Settings'));
		$this->assertTrue(class_exists('CMSS_Structured_Data'));
	}
}
