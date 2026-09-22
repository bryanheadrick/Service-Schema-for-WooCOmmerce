<?php

/**
 * Class SSW_BootstrapTest
 *
 * @package Service_Schema_For_WooCommerce
 */

/**
 * Tests the plugin bootstrap guard.
 */
class SSW_BootstrapTest extends WP_UnitTestCase
{

	public function test_ssw_init_function_exists()
	{
		$this->assertTrue(function_exists('ssw_init'));
	}

	public function test_classes_loaded_when_woocommerce_active()
	{
		$this->assertTrue(class_exists('WooCommerce'), 'WooCommerce must be active in the test environment.');
		$this->assertTrue(class_exists('SSW_Product_Fields'));
		$this->assertTrue(class_exists('SSW_Admin_Settings'));
		$this->assertTrue(class_exists('SSW_Structured_Data'));
	}
}
