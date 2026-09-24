<?php

/**
 * Registers the Service Schema settings section.
 *
 * @package CatManStudios_Systems_Service_Schema_For_WooCommerce
 */

if (! defined('ABSPATH')) {
	exit;
}

/**
 * Adds the Service Schema section to WooCommerce > Settings > Products.
 */
class CMSS_Admin_Settings
{

	/**
	 * Section id used across the section list and settings filters.
	 *
	 * @var string
	 */
	const SECTION_ID = 'cmss_service_schema';

	/**
	 * Registers hooks.
	 */
	public function __construct()
	{
		add_filter('woocommerce_get_sections_products', array($this, 'add_section'));
		add_filter('woocommerce_get_settings_products', array($this, 'add_settings'), 10, 2);
	}

	/**
	 * Adds the "Service Schema" section to the Products settings tab.
	 *
	 * @param array $sections Existing sections, keyed by section id.
	 * @return array
	 */
	public function add_section($sections)
	{
		$sections[self::SECTION_ID] = __('Service Schema', 'catmanstudios-systems-service-schema-for-woocommerce');

		return $sections;
	}

	/**
	 * Adds default-value fields to the Service Schema section only.
	 *
	 * @param array  $settings   Existing settings for the current section.
	 * @param string $section_id Section currently being rendered/saved.
	 * @return array
	 */
	public function add_settings($settings, $section_id)
	{
		if (self::SECTION_ID !== $section_id) {
			return $settings;
		}

		return array(
			array(
				'title' => __('Service Schema', 'catmanstudios-systems-service-schema-for-woocommerce'),
				'type'  => 'title',
				'desc'  => __('Default values used for Service products that leave these fields blank.', 'catmanstudios-systems-service-schema-for-woocommerce'),
				'id'    => 'cmss_service_schema_options',
			),
			array(
				'title'   => __('Default Provider Name', 'catmanstudios-systems-service-schema-for-woocommerce'),
				'desc'    => __('Falls back to your site title if left blank.', 'catmanstudios-systems-service-schema-for-woocommerce'),
				'id'      => 'cmss_default_provider',
				'type'    => 'text',
				'default' => '',
				'css'     => 'min-width: 300px;',
			),
			array(
				'title'   => __('Default Service Type', 'catmanstudios-systems-service-schema-for-woocommerce'),
				'desc'    => __('E.g. "Plumbing" or "Consulting". Left out of the structured data if blank.', 'catmanstudios-systems-service-schema-for-woocommerce'),
				'id'      => 'cmss_default_service_type',
				'type'    => 'text',
				'default' => '',
				'css'     => 'min-width: 300px;',
			),
			array(
				'title'   => __('Default Area Served', 'catmanstudios-systems-service-schema-for-woocommerce'),
				'desc'    => __('E.g. "Greater Boston Area". Left out of the structured data if blank.', 'catmanstudios-systems-service-schema-for-woocommerce'),
				'id'      => 'cmss_default_area_served',
				'type'    => 'text',
				'default' => '',
				'css'     => 'min-width: 300px;',
			),
			array(
				'type' => 'sectionend',
				'id'   => 'cmss_service_schema_options',
			),
		);
	}
}
