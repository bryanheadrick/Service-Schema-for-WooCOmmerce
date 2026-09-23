<?php

/**
 * Class PLBL_BootstrapTest
 *
 * @package Plumbline_Labs_Service_Schema_For_WooCommerce
 */

/**
 * Tests the plugin bootstrap guard.
 */
class PLBL_BootstrapTest extends WP_UnitTestCase
{

	public function test_plbl_init_function_exists()
	{
		$this->assertTrue(function_exists('plbl_init'));
	}

	public function test_classes_loaded_when_woocommerce_active()
	{
		$this->assertTrue(class_exists('WooCommerce'), 'WooCommerce must be active in the test environment.');
		$this->assertTrue(class_exists('PLBL_Product_Fields'));
		$this->assertTrue(class_exists('PLBL_Admin_Settings'));
		$this->assertTrue(class_exists('PLBL_Structured_Data'));
	}
}
