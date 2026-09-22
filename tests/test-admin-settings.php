<?php

/**
 * Class SSW_AdminSettingsTest
 *
 * @package Service_Schema_For_WooCommerce
 */

/**
 * Tests the Service Schema settings section registration.
 */
class SSW_AdminSettingsTest extends WP_UnitTestCase
{

	public function test_section_is_registered()
	{
		$sections = apply_filters('woocommerce_get_sections_products', array());

		$this->assertArrayHasKey('service_schema', $sections);
	}

	public function test_fields_are_registered_only_for_own_section()
	{
		$fields = apply_filters('woocommerce_get_settings_products', array(), 'service_schema');

		$ids = wp_list_pluck($fields, 'id');

		$this->assertContains('service_schema_wc_default_provider', $ids);
		$this->assertContains('service_schema_wc_default_service_type', $ids);
		$this->assertContains('service_schema_wc_default_area_served', $ids);

		$other_section_fields = apply_filters('woocommerce_get_settings_products', array(), '');
		$other_ids             = wp_list_pluck($other_section_fields, 'id');

		$this->assertNotContains('service_schema_wc_default_provider', $other_ids);
	}

	public function test_default_option_values_are_empty_strings()
	{
		delete_option('service_schema_wc_default_provider');
		delete_option('service_schema_wc_default_service_type');
		delete_option('service_schema_wc_default_area_served');

		$this->assertSame('', get_option('service_schema_wc_default_provider', ''));
		$this->assertSame('', get_option('service_schema_wc_default_service_type', ''));
		$this->assertSame('', get_option('service_schema_wc_default_area_served', ''));
	}
}
