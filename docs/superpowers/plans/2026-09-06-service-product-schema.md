# Service Product Schema Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add a "Service" flag to WooCommerce Simple/Variable products that outputs schema.org `Service` structured data (instead of `Product`) via the `woocommerce_structured_data_product` filter, with per-product and global-default fields for `provider`, `serviceType`, and `areaServed`.

**Architecture:** A single-purpose WordPress plugin with four collaborating pieces: (1) a product-meta checkbox + tab that store `_is_service` and three service-detail fields on `WC_Product`, (2) a WooCommerce Settings > Products section storing site-wide defaults for those fields, (3) a `woocommerce_structured_data_product` filter callback that rewrites the JSON-LD markup when `_is_service` is set, and (4) a `plugins_loaded` guard that no-ops safely (with a contextual admin notice) when WooCommerce isn't active. All WooCommerce integration follows existing core conventions exactly (verified against WooCommerce 10.x source in this environment) rather than reinventing them.

**Tech Stack:** PHP 5.6+ (per existing `.phpcs.xml.dist` `testVersion`), WordPress Plugin API, WooCommerce 10.x hooks/classes, PHPUnit + `WP_UnitTestCase` (existing scaffold), PHPCS with `WordPress` ruleset.

**Spec:** `docs/superpowers/specs/2026-09-06-service-product-schema-design.md`

## Global Constraints

- PHP compatibility floor: 5.6 (per `.phpcs.xml.dist` `testVersion="5.6-"` — do not use PHP 7+-only syntax such as scalar type hints, `??=`, arrow functions, or typed properties).
- Minimum supported WP version for PHPCS: 4.6 (per `.phpcs.xml.dist` `minimum_supported_wp_version`).
- Text domain must be exactly `service-schema-for-woocommerce` everywhere (matches plugin slug; `.phpcs.xml.dist` currently has a placeholder `my-plugin` text domain that must be corrected — see Task 1).
- PHPCS `PrefixAllGlobals` prefix must be updated from placeholder `my-plugin` to the real prefix used by this plugin's functions/classes/globals (see Task 1) — all global-scope function names, not just classes, must use this prefix.
- License: GPLv2 or later, declared in both the plugin header and `readme.txt` (no code changes needed if already correct — verify in Task 1).
- Zero external HTTP calls, telemetry, or "phone home" behavior anywhere in this plugin.
- No auto-injected frontend credit/backlink in JSON-LD output or elsewhere.
- Any admin notice must be contextual (only where relevant) and either dismissible or self-resolving (e.g. disappears once WooCommerce is active) — never a sitewide persistent nag.
- All settings/meta input sanitized on save (`sanitize_text_field()`); all output escaped (`esc_html()`, `esc_attr()`, or `wp_kses_post()` as appropriate) — PHPCS `WordPress` ruleset enforces this and CI (`.circleci/config.yml`) will fail on violations.
- Service flag applies only to Simple and Variable product types (not Grouped/External).
- Checking "Service" forces `virtual = true` on the product (Service implies Virtual); stock management, sale pricing, and tax class remain fully available and untouched.

---

## File Structure

- `service-schema-for-woocommerce.php` — bootstrap only: header, WooCommerce-active guard (`plugins_loaded`), requires `includes/*`, instantiates the three feature classes.
- `includes/class-ssw-product-fields.php` — registers the `_is_service` checkbox (General tab), the new "Service" tab + panel, the field-save logic (`woocommerce_admin_process_product_object`), and the admin JS enqueue.
- `includes/class-ssw-admin-settings.php` — registers the "Service Schema" section and its three default fields under WooCommerce > Settings > Products.
- `includes/class-ssw-structured-data.php` — the `woocommerce_structured_data_product` filter callback and the field-resolution helper (product meta → settings default → hardcoded fallback).
- `assets/js/admin-product-service-tab.js` — vanilla JS toggling `.show_if_service` panel/tab visibility based on the `_is_service` checkbox state; enqueued only on the product edit screen.
- `tests/test-product-fields.php` — tests for Task 3.
- `tests/test-admin-settings.php` — tests for Task 4.
- `tests/test-structured-data.php` — tests for Task 5.
- `tests/bootstrap.php` — modified to load WooCommerce so `WC_Product`/`WC_Structured_Data` classes exist in tests.
- `.phpcs.xml.dist` — modified to replace placeholder prefixes/text domain.
- `readme.txt` — modified to remove WP-CLI scaffold placeholder boilerplate and describe the real plugin (Task 6).

---

## Task 1: Fix scaffold placeholders (PHPCS config, plugin header, readme.txt)

**Files:**
- Modify: `.phpcs.xml.dist:33,39`
- Modify: `service-schema-for-woocommerce.php:1-13`
- Modify: `readme.txt` (full rewrite of placeholder sections)
- Test: none (config/metadata only; verified via PHPCS run, not PHPUnit)

**Interfaces:**
- Consumes: nothing.
- Produces: the prefix `ssw` (function/hook prefix) and `SSW_` (class prefix) that every subsequent task's code must use to pass PHPCS `WordPress.NamingConventions.PrefixAllGlobals`. Text domain constant used everywhere: `'service-schema-for-woocommerce'`.

- [ ] **Step 1: Update PHPCS prefixes and text domain**

In `.phpcs.xml.dist`, change:
```xml
<rule ref="WordPress.NamingConventions.PrefixAllGlobals">
    <properties>
        <property name="prefixes" type="array" value="ssw,SSW_"/>
    </properties>
</rule>
<rule ref="WordPress.WP.I18n">
    <properties>
        <property name="text_domain" type="array" value="service-schema-for-woocommerce"/>
    </properties>
</rule>
```

- [ ] **Step 2: Fix the plugin header**

In `service-schema-for-woocommerce.php`, the header currently reads (verify current state first — Author/Plugin URI may already be partially filled in from prior manual edits):
```php
/**
 * Plugin Name:     Service Schema For Woocommerce
 * Plugin URI:      https://bryanheadrick.com
 * Description:     PLUGIN DESCRIPTION HERE
 * Author:          bryanheadrick
 * Author URI:      https://bryanheadrick.com
 * Text Domain:     service-schema-for-woocommerce
 * Domain Path:     /languages
 * Version:         0.1.0
 *
 * @package         Service_Schema_For_Woocommerce
 *
 * Requires Plugins: woocommerce
 * License:          GPL v2 or later
 * License URI:      https://www.gnu.org/licenses/gpl-2.0.html
 */
```
Replace `Description:     PLUGIN DESCRIPTION HERE` with:
```
 * Description:     Adds a "Service" option to WooCommerce products and outputs schema.org Service structured data instead of Product for those items.
```
Add the `Requires Plugins: woocommerce`, `License`, and `License URI` lines shown above if not already present (WordPress 6.5+ supports the `Requires Plugins` header for declaring a hard plugin dependency shown in the Plugins list UI).

- [ ] **Step 3: Rewrite readme.txt**

Replace the entire placeholder body with:
```
=== Service Schema for WooCommerce ===
Contributors: bryanheadrick
Tags: woocommerce, schema, structured-data, seo, service
Requires at least: 6.0
Tested up to: 6.8
Requires PHP: 5.6
WC requires at least: 8.0
WC tested up to: 10.0
Stable tag: 0.1.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Mark WooCommerce products as services and output schema.org Service structured data instead of Product.

== Description ==

Service Schema for WooCommerce adds a "This is a service" option to Simple and Variable products. When enabled, the plugin changes the product's JSON-LD structured data `@type` from `Product` to `Service` (https://schema.org/Service), and lets you specify a provider, service type, and area served — either per product or as site-wide defaults under WooCommerce > Settings > Products.

This plugin makes no external network requests and collects no data.

== Installation ==

1. Upload the plugin files to the `/wp-content/plugins/service-schema-for-woocommerce` directory, or install through the WordPress plugins screen directly.
1. Activate the plugin through the 'Plugins' screen in WordPress.
1. Requires WooCommerce to be installed and active.
1. Edit a Simple or Variable product, check "This is a service" in the General tab, and fill in the new Service tab fields.
1. Optionally set site-wide defaults under WooCommerce > Settings > Products > Service Schema.

== Frequently Asked Questions ==

= Does this create a new product type? =

No. "Service" is a flag on existing Simple and Variable products, similar to the built-in "Virtual" checkbox — it does not add a new entry to the product type dropdown.

= Does this work with Grouped or External/Affiliate products? =

No, only Simple and Variable products support the Service flag.

== Changelog ==

= 0.1.0 =
* Initial release.
```

- [ ] **Step 4: Commit**

```bash
git add .phpcs.xml.dist service-schema-for-woocommerce.php readme.txt
git commit -m "chore: replace scaffold placeholders with real plugin metadata"
```

---

## Task 2: WooCommerce-active guard and plugin bootstrap

**Files:**
- Modify: `service-schema-for-woocommerce.php` (append bootstrap logic after the header)
- Test: `tests/test-bootstrap.php`

**Interfaces:**
- Produces: function `ssw_init()` (global scope, hooked to `plugins_loaded`) which requires `includes/class-ssw-product-fields.php`, `includes/class-ssw-admin-settings.php`, `includes/class-ssw-structured-data.php`, and instantiates `new SSW_Product_Fields()`, `new SSW_Admin_Settings()`, `new SSW_Structured_Data()` only when `class_exists( 'WooCommerce' )` is true. Also produces `ssw_missing_woocommerce_notice()` (global scope, hooked to `admin_notices` only when WooCommerce is missing).
- Consumes: nothing yet (later tasks' classes are consumed here once they exist — Steps 3-5 below stub them minimally so this task is independently testable, then Tasks 3-5 fill in real behavior).

- [ ] **Step 1: Write the failing test**

Create `tests/test-bootstrap.php`:
```php
<?php
/**
 * Class SSW_BootstrapTest
 *
 * @package Service_Schema_For_Woocommerce
 */

/**
 * Tests the plugin bootstrap guard.
 */
class SSW_BootstrapTest extends WP_UnitTestCase {

	public function test_ssw_init_function_exists() {
		$this->assertTrue( function_exists( 'ssw_init' ) );
	}

	public function test_classes_loaded_when_woocommerce_active() {
		$this->assertTrue( class_exists( 'WooCommerce' ), 'WooCommerce must be active in the test environment (see Task 7).' );
		$this->assertTrue( class_exists( 'SSW_Product_Fields' ) );
		$this->assertTrue( class_exists( 'SSW_Admin_Settings' ) );
		$this->assertTrue( class_exists( 'SSW_Structured_Data' ) );
	}
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `studio wp eval-file vendor/bin/phpunit tests/test-bootstrap.php` is not the right invocation for this scaffold — use the standard command already wired by `phpunit.xml.dist`:

Run: `./vendor/bin/phpunit --filter SSW_BootstrapTest`
Expected: FAIL — `ssw_init` undefined / classes undefined.

- [ ] **Step 3: Write the bootstrap implementation**

Append to `service-schema-for-woocommerce.php` (after the doc header block):
```php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

define( 'SSW_PLUGIN_FILE', __FILE__ );
define( 'SSW_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );

/**
 * Boots the plugin once all plugins have loaded, guarding on WooCommerce being active.
 */
function ssw_init() {
	if ( ! class_exists( 'WooCommerce' ) ) {
		add_action( 'admin_notices', 'ssw_missing_woocommerce_notice' );
		return;
	}

	require_once SSW_PLUGIN_DIR . 'includes/class-ssw-product-fields.php';
	require_once SSW_PLUGIN_DIR . 'includes/class-ssw-admin-settings.php';
	require_once SSW_PLUGIN_DIR . 'includes/class-ssw-structured-data.php';

	new SSW_Product_Fields();
	new SSW_Admin_Settings();
	new SSW_Structured_Data();
}
add_action( 'plugins_loaded', 'ssw_init' );

/**
 * Prints a contextual, non-persistent notice when WooCommerce is not active.
 */
function ssw_missing_woocommerce_notice() {
	$screen = get_current_screen();

	if ( ! $screen || ! in_array( $screen->id, array( 'plugins', 'plugins-network' ), true ) ) {
		return;
	}

	printf(
		'<div class="notice notice-error"><p>%s</p></div>',
		esc_html__( 'Service Schema for WooCommerce requires WooCommerce to be installed and active.', 'service-schema-for-woocommerce' )
	);
}
```

Create minimal stub files so this task's test can pass independently of Tasks 3-5 (each later task replaces the stub's body, not its class name — so the "Interfaces" contract holds):

`includes/class-ssw-product-fields.php`:
```php
<?php
/**
 * Registers Service product fields.
 *
 * @package Service_Schema_For_Woocommerce
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Adds the Service checkbox and tab to the product data panel.
 */
class SSW_Product_Fields {

	/**
	 * Registers hooks.
	 */
	public function __construct() {
		// Populated in Task 3.
	}
}
```

`includes/class-ssw-admin-settings.php`:
```php
<?php
/**
 * Registers the Service Schema settings section.
 *
 * @package Service_Schema_For_Woocommerce
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Adds the Service Schema section to WooCommerce > Settings > Products.
 */
class SSW_Admin_Settings {

	/**
	 * Registers hooks.
	 */
	public function __construct() {
		// Populated in Task 4.
	}
}
```

`includes/class-ssw-structured-data.php`:
```php
<?php
/**
 * Overrides structured data output for service products.
 *
 * @package Service_Schema_For_Woocommerce
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Rewrites JSON-LD markup to schema.org Service for flagged products.
 */
class SSW_Structured_Data {

	/**
	 * Registers hooks.
	 */
	public function __construct() {
		// Populated in Task 5.
	}
}
```

- [ ] **Step 4: Run test to verify it passes**

Run: `./vendor/bin/phpunit --filter SSW_BootstrapTest`
Expected: PASS (2 tests) — note this requires Task 7 (WooCommerce loaded in test bootstrap) to be done first for `class_exists('WooCommerce')` to be true; if run before Task 7, `test_classes_loaded_when_woocommerce_active` is expected to fail with a clear assertion message pointing at Task 7. Do Task 7 before this step if executing tasks out of order.

- [ ] **Step 5: Commit**

```bash
git add service-schema-for-woocommerce.php includes/class-ssw-product-fields.php includes/class-ssw-admin-settings.php includes/class-ssw-structured-data.php tests/test-bootstrap.php
git commit -m "feat: add plugin bootstrap with WooCommerce-active guard"
```

---

## Task 3: Service checkbox, Service tab, and field persistence

**Files:**
- Modify: `includes/class-ssw-product-fields.php` (replace stub body)
- Create: `assets/js/admin-product-service-tab.js`
- Test: `tests/test-product-fields.php`

**Interfaces:**
- Consumes: nothing new.
- Produces: postmeta keys `_is_service` (`'yes'`/`'no'`), `_service_provider` (string), `_service_type` (string), `_service_area_served` (string) on any product post. These exact meta keys are consumed by Task 5's field-resolution helper.

- [ ] **Step 1: Write the failing test**

Create `tests/test-product-fields.php`:
```php
<?php
/**
 * Class SSW_ProductFieldsTest
 *
 * @package Service_Schema_For_Woocommerce
 */

/**
 * Tests Service field registration and persistence.
 */
class SSW_ProductFieldsTest extends WP_UnitTestCase {

	public function test_is_service_defaults_to_no() {
		$product = new WC_Product_Simple();
		$product->save();

		$this->assertSame( 'no', $product->get_meta( '_is_service', true ) );
	}

	public function test_saving_service_checkbox_sets_meta_and_forces_virtual() {
		$product = new WC_Product_Simple();
		$product->save();

		$_POST['_is_service']          = 'yes';
		$_POST['_service_provider']    = 'Acme Plumbing';
		$_POST['_service_type']        = 'Plumbing';
		$_POST['_service_area_served'] = 'Greater Boston Area';

		do_action( 'woocommerce_admin_process_product_object', $product );
		$product->save();

		$saved = wc_get_product( $product->get_id() );

		$this->assertSame( 'yes', $saved->get_meta( '_is_service', true ) );
		$this->assertTrue( $saved->get_virtual() );
		$this->assertSame( 'Acme Plumbing', $saved->get_meta( '_service_provider', true ) );
		$this->assertSame( 'Plumbing', $saved->get_meta( '_service_type', true ) );
		$this->assertSame( 'Greater Boston Area', $saved->get_meta( '_service_area_served', true ) );

		unset( $_POST['_is_service'], $_POST['_service_provider'], $_POST['_service_type'], $_POST['_service_area_served'] );
	}

	public function test_unchecking_service_does_not_force_virtual_off() {
		$product = new WC_Product_Simple();
		$product->set_virtual( true );
		$product->save();

		$_POST = array(); // Simulate the checkbox being unchecked (absent from $_POST).

		do_action( 'woocommerce_admin_process_product_object', $product );
		$product->save();

		$saved = wc_get_product( $product->get_id() );

		$this->assertSame( 'no', $saved->get_meta( '_is_service', true ) );
		$this->assertTrue( $saved->get_virtual(), 'Unchecking Service must not force Virtual back off, since a merchant may have set Virtual independently.' );
	}

	public function test_product_data_tabs_includes_service_tab() {
		$tabs = apply_filters( 'woocommerce_product_data_tabs', array() );

		$this->assertArrayHasKey( 'service', $tabs );
		$this->assertSame( 'service_product_data', $tabs['service']['target'] );
	}
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `./vendor/bin/phpunit --filter SSW_ProductFieldsTest`
Expected: FAIL — meta not set, tab not registered.

- [ ] **Step 3: Implement `SSW_Product_Fields`**

Replace the body of `includes/class-ssw-product-fields.php`:
```php
<?php
/**
 * Registers Service product fields.
 *
 * @package Service_Schema_For_Woocommerce
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Adds the Service checkbox and tab to the product data panel.
 */
class SSW_Product_Fields {

	/**
	 * Registers hooks.
	 */
	public function __construct() {
		add_action( 'woocommerce_product_options_general_product_data', array( $this, 'render_service_checkbox' ) );
		add_filter( 'woocommerce_product_data_tabs', array( $this, 'add_service_tab' ) );
		add_action( 'woocommerce_product_data_panels', array( $this, 'render_service_panel' ) );
		add_action( 'woocommerce_admin_process_product_object', array( $this, 'save_fields' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_script' ) );
	}

	/**
	 * Renders the "This is a service" checkbox in the General tab.
	 */
	public function render_service_checkbox() {
		woocommerce_wp_checkbox(
			array(
				'id'          => '_is_service',
				'label'       => __( 'Service', 'service-schema-for-woocommerce' ),
				'description' => __( 'This is a service (implies Virtual; outputs schema.org Service structured data).', 'service-schema-for-woocommerce' ),
			)
		);
	}

	/**
	 * Adds the Service tab, shown only for Simple/Variable products when the checkbox is checked.
	 *
	 * @param array $tabs Existing product data tabs.
	 * @return array
	 */
	public function add_service_tab( $tabs ) {
		$tabs['service'] = array(
			'label'    => __( 'Service', 'service-schema-for-woocommerce' ),
			'target'   => 'service_product_data',
			'class'    => array( 'show_if_simple', 'show_if_variable', 'show_if_service' ),
			'priority' => 25,
		);

		return $tabs;
	}

	/**
	 * Renders the Service tab panel fields.
	 */
	public function render_service_panel() {
		global $post;

		echo '<div id="service_product_data" class="panel woocommerce_options_panel">';

		echo '<div class="options_group">';

		woocommerce_wp_text_input(
			array(
				'id'          => '_service_provider',
				'label'       => __( 'Provider', 'service-schema-for-woocommerce' ),
				'desc_tip'    => true,
				'description' => __( 'Leave blank to use the site-wide default from WooCommerce > Settings > Products.', 'service-schema-for-woocommerce' ),
			)
		);

		woocommerce_wp_text_input(
			array(
				'id'          => '_service_type',
				'label'       => __( 'Service Type', 'service-schema-for-woocommerce' ),
				'desc_tip'    => true,
				'description' => __( 'E.g. "Plumbing" or "Consulting". Leave blank to use the site-wide default.', 'service-schema-for-woocommerce' ),
			)
		);

		woocommerce_wp_text_input(
			array(
				'id'          => '_service_area_served',
				'label'       => __( 'Area Served', 'service-schema-for-woocommerce' ),
				'desc_tip'    => true,
				'description' => __( 'E.g. "Greater Boston Area". Leave blank to use the site-wide default.', 'service-schema-for-woocommerce' ),
			)
		);

		echo '</div>';
		echo '</div>';

		unset( $post );
	}

	/**
	 * Persists Service fields, forcing Virtual on when Service is checked.
	 *
	 * @param WC_Product $product Product object being saved.
	 */
	public function save_fields( $product ) {
		$is_service = isset( $_POST['_is_service'] ) ? 'yes' : 'no'; // phpcs:ignore WordPress.Security.NonceVerification.Missing -- core's own product save handler verifies the nonce before this hook fires.

		$product->update_meta_data( '_is_service', $is_service );

		if ( 'yes' === $is_service ) {
			$product->set_virtual( true );
		}

		$product->update_meta_data(
			'_service_provider',
			isset( $_POST['_service_provider'] ) ? sanitize_text_field( wp_unslash( $_POST['_service_provider'] ) ) : '' // phpcs:ignore WordPress.Security.NonceVerification.Missing
		);

		$product->update_meta_data(
			'_service_type',
			isset( $_POST['_service_type'] ) ? sanitize_text_field( wp_unslash( $_POST['_service_type'] ) ) : '' // phpcs:ignore WordPress.Security.NonceVerification.Missing
		);

		$product->update_meta_data(
			'_service_area_served',
			isset( $_POST['_service_area_served'] ) ? sanitize_text_field( wp_unslash( $_POST['_service_area_served'] ) ) : '' // phpcs:ignore WordPress.Security.NonceVerification.Missing
		);
	}

	/**
	 * Enqueues the admin JS that toggles the Service tab's visibility.
	 *
	 * @param string $hook Current admin page hook suffix.
	 */
	public function enqueue_admin_script( $hook ) {
		if ( ! in_array( $hook, array( 'post.php', 'post-new.php' ), true ) ) {
			return;
		}

		global $post;

		if ( ! $post || 'product' !== $post->post_type ) {
			return;
		}

		wp_enqueue_script(
			'ssw-admin-product-service-tab',
			plugins_url( 'assets/js/admin-product-service-tab.js', SSW_PLUGIN_FILE ),
			array( 'jquery' ),
			'0.1.0',
			true
		);
	}
}
```

- [ ] **Step 4: Add the admin JS toggle**

Create `assets/js/admin-product-service-tab.js`:
```js
/**
 * Toggles the Service tab's visibility based on the "_is_service" checkbox,
 * mirroring how WooCommerce core toggles .show_if_virtual internally.
 */
( function ( $ ) {
	'use strict';

	function toggleServiceTab() {
		var isChecked = $( '#_is_service' ).is( ':checked' );
		$( '.show_if_service' ).toggle( isChecked );
	}

	$( function () {
		toggleServiceTab();
		$( '#_is_service' ).on( 'change', toggleServiceTab );
	} );
} )( jQuery );
```

Note: `set_props()`-style bulk assignment (as core uses for `_virtual`) is not used here since `update_meta_data()` on individual custom fields is the correct API for extension-added meta that isn't a native `WC_Product` data prop.

- [ ] **Step 5: Run test to verify it passes**

Run: `./vendor/bin/phpunit --filter SSW_ProductFieldsTest`
Expected: PASS (4 tests).

- [ ] **Step 6: Commit**

```bash
git add includes/class-ssw-product-fields.php assets/js/admin-product-service-tab.js tests/test-product-fields.php
git commit -m "feat: add Service checkbox, tab, and field persistence"
```

---

## Task 4: Service Schema settings section

**Files:**
- Modify: `includes/class-ssw-admin-settings.php` (replace stub body)
- Test: `tests/test-admin-settings.php`

**Interfaces:**
- Consumes: nothing new.
- Produces: wp_options rows `service_schema_wc_default_provider`, `service_schema_wc_default_service_type`, `service_schema_wc_default_area_served` (all strings, default `''`). These exact option names are consumed by Task 5's field-resolution helper.

- [ ] **Step 1: Write the failing test**

Create `tests/test-admin-settings.php`:
```php
<?php
/**
 * Class SSW_AdminSettingsTest
 *
 * @package Service_Schema_For_Woocommerce
 */

/**
 * Tests the Service Schema settings section registration.
 */
class SSW_AdminSettingsTest extends WP_UnitTestCase {

	public function test_section_is_registered() {
		$sections = apply_filters( 'woocommerce_get_sections_products', array() );

		$this->assertArrayHasKey( 'service_schema', $sections );
	}

	public function test_fields_are_registered_only_for_own_section() {
		$fields = apply_filters( 'woocommerce_get_settings_products', array(), 'service_schema' );

		$ids = wp_list_pluck( $fields, 'id' );

		$this->assertContains( 'service_schema_wc_default_provider', $ids );
		$this->assertContains( 'service_schema_wc_default_service_type', $ids );
		$this->assertContains( 'service_schema_wc_default_area_served', $ids );

		$other_section_fields = apply_filters( 'woocommerce_get_settings_products', array(), '' );
		$other_ids             = wp_list_pluck( $other_section_fields, 'id' );

		$this->assertNotContains( 'service_schema_wc_default_provider', $other_ids );
	}

	public function test_default_option_values_are_empty_strings() {
		delete_option( 'service_schema_wc_default_provider' );
		delete_option( 'service_schema_wc_default_service_type' );
		delete_option( 'service_schema_wc_default_area_served' );

		$this->assertSame( '', get_option( 'service_schema_wc_default_provider', '' ) );
		$this->assertSame( '', get_option( 'service_schema_wc_default_service_type', '' ) );
		$this->assertSame( '', get_option( 'service_schema_wc_default_area_served', '' ) );
	}
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `./vendor/bin/phpunit --filter SSW_AdminSettingsTest`
Expected: FAIL — section/fields not present.

- [ ] **Step 3: Implement `SSW_Admin_Settings`**

Replace the body of `includes/class-ssw-admin-settings.php`:
```php
<?php
/**
 * Registers the Service Schema settings section.
 *
 * @package Service_Schema_For_Woocommerce
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Adds the Service Schema section to WooCommerce > Settings > Products.
 */
class SSW_Admin_Settings {

	/**
	 * Section id used across the section list and settings filters.
	 *
	 * @var string
	 */
	const SECTION_ID = 'service_schema';

	/**
	 * Registers hooks.
	 */
	public function __construct() {
		add_filter( 'woocommerce_get_sections_products', array( $this, 'add_section' ) );
		add_filter( 'woocommerce_get_settings_products', array( $this, 'add_settings' ), 10, 2 );
	}

	/**
	 * Adds the "Service Schema" section to the Products settings tab.
	 *
	 * @param array $sections Existing sections, keyed by section id.
	 * @return array
	 */
	public function add_section( $sections ) {
		$sections[ self::SECTION_ID ] = __( 'Service Schema', 'service-schema-for-woocommerce' );

		return $sections;
	}

	/**
	 * Adds default-value fields to the Service Schema section only.
	 *
	 * @param array  $settings   Existing settings for the current section.
	 * @param string $section_id Section currently being rendered/saved.
	 * @return array
	 */
	public function add_settings( $settings, $section_id ) {
		if ( self::SECTION_ID !== $section_id ) {
			return $settings;
		}

		return array(
			array(
				'title' => __( 'Service Schema', 'service-schema-for-woocommerce' ),
				'type'  => 'title',
				'desc'  => __( 'Default values used for Service products that leave these fields blank.', 'service-schema-for-woocommerce' ),
				'id'    => 'service_schema_wc_options',
			),
			array(
				'title'   => __( 'Default Provider Name', 'service-schema-for-woocommerce' ),
				'desc'    => __( 'Falls back to your site title if left blank.', 'service-schema-for-woocommerce' ),
				'id'      => 'service_schema_wc_default_provider',
				'type'    => 'text',
				'default' => '',
				'css'     => 'min-width: 300px;',
			),
			array(
				'title'   => __( 'Default Service Type', 'service-schema-for-woocommerce' ),
				'desc'    => __( 'E.g. "Plumbing" or "Consulting". Left out of the structured data if blank.', 'service-schema-for-woocommerce' ),
				'id'      => 'service_schema_wc_default_service_type',
				'type'    => 'text',
				'default' => '',
				'css'     => 'min-width: 300px;',
			),
			array(
				'title'   => __( 'Default Area Served', 'service-schema-for-woocommerce' ),
				'desc'    => __( 'E.g. "Greater Boston Area". Left out of the structured data if blank.', 'service-schema-for-woocommerce' ),
				'id'      => 'service_schema_wc_default_area_served',
				'type'    => 'text',
				'default' => '',
				'css'     => 'min-width: 300px;',
			),
			array(
				'type' => 'sectionend',
				'id'   => 'service_schema_wc_options',
			),
		);
	}
}
```

- [ ] **Step 4: Run test to verify it passes**

Run: `./vendor/bin/phpunit --filter SSW_AdminSettingsTest`
Expected: PASS (3 tests).

- [ ] **Step 5: Commit**

```bash
git add includes/class-ssw-admin-settings.php tests/test-admin-settings.php
git commit -m "feat: add Service Schema defaults section to WooCommerce Products settings"
```

---

## Task 5: Structured data output override

**Files:**
- Modify: `includes/class-ssw-structured-data.php` (replace stub body)
- Test: `tests/test-structured-data.php`

**Interfaces:**
- Consumes: postmeta `_is_service`, `_service_provider`, `_service_type`, `_service_area_served` (from Task 3); options `service_schema_wc_default_provider`, `service_schema_wc_default_service_type`, `service_schema_wc_default_area_served` (from Task 4).
- Produces: filtered `$markup` array passed through `woocommerce_structured_data_product` — no other code depends on this output directly (it's the final consumer in this feature).

- [ ] **Step 1: Write the failing test**

Create `tests/test-structured-data.php`:
```php
<?php
/**
 * Class SSW_StructuredDataTest
 *
 * @package Service_Schema_For_Woocommerce
 */

/**
 * Tests the Service structured data override.
 */
class SSW_StructuredDataTest extends WP_UnitTestCase {

	public function test_non_service_product_markup_is_unchanged() {
		$product = new WC_Product_Simple();
		$product->set_regular_price( '10.00' );
		$product->save();

		$markup = array(
			'@type' => 'Product',
			'sku'   => 'ABC123',
			'gtin'  => '0012345678905',
		);

		$filtered = apply_filters( 'woocommerce_structured_data_product', $markup, $product );

		$this->assertSame( $markup, $filtered );
	}

	public function test_service_product_type_is_rewritten_and_ids_removed() {
		$product = new WC_Product_Simple();
		$product->set_regular_price( '100.00' );
		$product->update_meta_data( '_is_service', 'yes' );
		$product->update_meta_data( '_service_provider', 'Acme Plumbing' );
		$product->update_meta_data( '_service_type', 'Plumbing' );
		$product->update_meta_data( '_service_area_served', 'Greater Boston Area' );
		$product->save();

		$markup = array(
			'@type' => 'Product',
			'sku'   => 'ABC123',
			'gtin'  => '0012345678905',
			'name'  => 'Drain Cleaning',
		);

		$filtered = apply_filters( 'woocommerce_structured_data_product', $markup, $product );

		$this->assertSame( 'Service', $filtered['@type'] );
		$this->assertArrayNotHasKey( 'sku', $filtered );
		$this->assertArrayNotHasKey( 'gtin', $filtered );
		$this->assertSame( 'Drain Cleaning', $filtered['name'] );
		$this->assertSame(
			array(
				'@type' => 'Organization',
				'name'  => 'Acme Plumbing',
			),
			$filtered['provider']
		);
		$this->assertSame( 'Plumbing', $filtered['serviceType'] );
		$this->assertSame( 'Greater Boston Area', $filtered['areaServed'] );
	}

	public function test_service_product_falls_back_to_site_defaults() {
		update_option( 'service_schema_wc_default_provider', 'Default Co' );
		update_option( 'service_schema_wc_default_service_type', 'Consulting' );
		update_option( 'service_schema_wc_default_area_served', 'United States' );

		$product = new WC_Product_Simple();
		$product->set_regular_price( '100.00' );
		$product->update_meta_data( '_is_service', 'yes' );
		$product->save();

		$filtered = apply_filters( 'woocommerce_structured_data_product', array( '@type' => 'Product' ), $product );

		$this->assertSame( 'Default Co', $filtered['provider']['name'] );
		$this->assertSame( 'Consulting', $filtered['serviceType'] );
		$this->assertSame( 'United States', $filtered['areaServed'] );

		delete_option( 'service_schema_wc_default_provider' );
		delete_option( 'service_schema_wc_default_service_type' );
		delete_option( 'service_schema_wc_default_area_served' );
	}

	public function test_service_product_falls_back_to_site_title_when_no_provider_anywhere() {
		delete_option( 'service_schema_wc_default_provider' );

		$product = new WC_Product_Simple();
		$product->set_regular_price( '100.00' );
		$product->update_meta_data( '_is_service', 'yes' );
		$product->save();

		$filtered = apply_filters( 'woocommerce_structured_data_product', array( '@type' => 'Product' ), $product );

		$this->assertSame( get_bloginfo( 'name' ), $filtered['provider']['name'] );
	}

	public function test_service_type_and_area_served_omitted_when_never_set() {
		delete_option( 'service_schema_wc_default_service_type' );
		delete_option( 'service_schema_wc_default_area_served' );

		$product = new WC_Product_Simple();
		$product->set_regular_price( '100.00' );
		$product->update_meta_data( '_is_service', 'yes' );
		$product->save();

		$filtered = apply_filters( 'woocommerce_structured_data_product', array( '@type' => 'Product' ), $product );

		$this->assertArrayNotHasKey( 'serviceType', $filtered );
		$this->assertArrayNotHasKey( 'areaServed', $filtered );
	}
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `./vendor/bin/phpunit --filter SSW_StructuredDataTest`
Expected: FAIL — `@type` stays `Product`, no `provider`/`serviceType`/`areaServed` keys added.

- [ ] **Step 3: Implement `SSW_Structured_Data`**

Replace the body of `includes/class-ssw-structured-data.php`:
```php
<?php
/**
 * Overrides structured data output for service products.
 *
 * @package Service_Schema_For_Woocommerce
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Rewrites JSON-LD markup to schema.org Service for flagged products.
 */
class SSW_Structured_Data {

	/**
	 * Registers hooks.
	 */
	public function __construct() {
		add_filter( 'woocommerce_structured_data_product', array( $this, 'rewrite_markup' ), 20, 2 );
	}

	/**
	 * Rewrites Product markup to Service markup when the product is flagged as a service.
	 *
	 * @param array      $markup  Structured data markup built by WC_Structured_Data.
	 * @param WC_Product $product Product the markup was built for.
	 * @return array
	 */
	public function rewrite_markup( $markup, $product ) {
		if ( 'yes' !== $product->get_meta( '_is_service', true ) ) {
			return $markup;
		}

		$markup['@type'] = 'Service';

		unset( $markup['sku'], $markup['gtin'] );

		$provider = $this->resolve_field( $product, '_service_provider', 'service_schema_wc_default_provider' );

		if ( '' === $provider ) {
			$provider = get_bloginfo( 'name' );
		}

		$markup['provider'] = array(
			'@type' => 'Organization',
			'name'  => $provider,
		);

		$service_type = $this->resolve_field( $product, '_service_type', 'service_schema_wc_default_service_type' );

		if ( '' !== $service_type ) {
			$markup['serviceType'] = $service_type;
		}

		$area_served = $this->resolve_field( $product, '_service_area_served', 'service_schema_wc_default_area_served' );

		if ( '' !== $area_served ) {
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
	private function resolve_field( $product, $meta_key, $option_key ) {
		$value = $product->get_meta( $meta_key, true );

		if ( '' !== $value ) {
			return $value;
		}

		return get_option( $option_key, '' );
	}
}
```

Note (from research): `WC_Structured_Data::generate_product_data()` only calls `set_data()` — and therefore only fires this filter — when the product has at least one of `offers`, `aggregateRating`, or `review` populated (WooCommerce core, `includes/class-wc-structured-data.php:516-519`). Every test above sets a `regular_price`, which guarantees `offers` is populated and the filter fires; a service product with no price set at all would not emit structured data, matching the same behavior a priceless Simple/Virtual product already has today (no plugin-side workaround needed).

- [ ] **Step 4: Run test to verify it passes**

Run: `./vendor/bin/phpunit --filter SSW_StructuredDataTest`
Expected: PASS (5 tests).

- [ ] **Step 5: Commit**

```bash
git add includes/class-ssw-structured-data.php tests/test-structured-data.php
git commit -m "feat: rewrite structured data to schema.org Service for flagged products"
```

---

## Task 6: PHPCS clean pass

**Files:**
- Modify: any file flagged by PHPCS (expected: minor spacing/docblock fixes only, given the code above already follows WordPress conventions).
- Test: none (static analysis, not PHPUnit).

**Interfaces:**
- Consumes: all files from Tasks 1-5.
- Produces: nothing new — this task only cleans up violations.

- [ ] **Step 1: Run PHPCS**

Run: `./vendor/bin/phpcs`
Expected: some findings (likely alignment/short-description docblock nitpicks). Read the output.

- [ ] **Step 2: Fix reported violations**

Apply the exact fixes PHPCS reports, file by file, re-running `./vendor/bin/phpcs` after each file until it reports "no violations".

- [ ] **Step 3: Re-run the full PHPUnit suite to confirm no regressions from PHPCS fixes**

Run: `./vendor/bin/phpunit`
Expected: PASS (all tests across Tasks 2-5, plus `test-sample.php` still excluded per `phpunit.xml.dist:14`).

- [ ] **Step 4: Commit**

```bash
git add -A
git commit -m "style: fix PHPCS violations"
```

---

## Task 7: Load WooCommerce in the test bootstrap

**Files:**
- Modify: `tests/bootstrap.php`

**Interfaces:**
- Consumes: nothing.
- Produces: `WC_Product`, `WC_Product_Simple`, `WC_Structured_Data`, and the `WooCommerce` class being available to every test in this suite, and WooCommerce's own install routine having run so `wc_get_product()` etc. work against real tables.

**Note on sequencing:** this task has no code dependency on Tasks 1-6, but every test in Tasks 2-5 requires WooCommerce classes to exist — do this task **first**, before running any test from Tasks 2-6, even though it's listed last for narrative reasons (it's plumbing, not a feature).

- [ ] **Step 1: Modify `tests/bootstrap.php` to load WooCommerce**

Change the `_manually_load_plugin` function to load WooCommerce before this plugin:
```php
/**
 * Manually load WooCommerce and the plugin being tested.
 */
function _manually_load_plugin() {
	$woocommerce_plugin_file = WP_PLUGIN_DIR . '/woocommerce/woocommerce.php';

	if ( ! file_exists( $woocommerce_plugin_file ) ) {
		echo 'WooCommerce must be installed in wp-content/plugins/woocommerce for these tests to run.' . PHP_EOL; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		exit( 1 );
	}

	require $woocommerce_plugin_file;
	require dirname( dirname( __FILE__ ) ) . '/service-schema-for-woocommerce.php';
}
```

- [ ] **Step 2: Verify the test environment has WooCommerce installed**

Run: `ls "$(getenv WP_TESTS_DIR 2>/dev/null || echo /tmp/wordpress-tests-lib)"` is not reliable across shells — instead just run the suite and observe:

Run: `./vendor/bin/phpunit --filter SSW_BootstrapTest`
Expected: if WooCommerce isn't present at `WP_PLUGIN_DIR . '/woocommerce/woocommerce.php'` in the PHPUnit test install (a separate WP install from the Studio site, per `bin/install-wp-tests.sh`), this fails with the explicit "WooCommerce must be installed..." message from Step 1. If that happens, symlink or copy this environment's WooCommerce plugin into the test install's `wp-content/plugins/woocommerce` directory, then re-run.

- [ ] **Step 3: Commit**

```bash
git add tests/bootstrap.php
git commit -m "test: load WooCommerce in the PHPUnit bootstrap"
```

---

## Task 8: End-to-end manual verification in Studio

**Files:** none (manual verification only, no code changes expected unless a bug is found — if one is, return to the relevant task above, add a regression test, and fix it there rather than patching ad hoc here).

- [ ] **Step 1: Confirm WooCommerce is active in the Studio site**

Run: `studio wp plugin list --status=active --format=csv`
Expected: `woocommerce` appears in the list.

- [ ] **Step 2: Activate this plugin**

Run: `studio wp plugin activate service-schema-for-woocommerce`
Expected: "Plugin 'service-schema-for-woocommerce' activated."

- [ ] **Step 3: Create a Simple Service product and verify admin behavior**

In wp-admin, add a new product, set it to Simple product type, set a regular price, check "Service" in the General tab, confirm the Shipping tab disappears and a new "Service" tab appears; fill in Provider/Service Type/Area Served in that tab; publish.

- [ ] **Step 4: Verify frontend structured data**

Run: `studio status` to get the site URL, visit the product page, view source, and confirm the `<script type="application/ld+json">` block contains `"@type":"Service"`, the `provider`/`serviceType`/`areaServed` values entered in Step 3, and no `sku`/`gtin` keys.

- [ ] **Step 5: Verify defaults fallback**

In wp-admin, go to WooCommerce > Settings > Products > Service Schema, set a Default Provider Name; create a second Service product leaving all three Service tab fields blank; verify its frontend JSON-LD `provider.name` matches the default.

- [ ] **Step 6: Verify non-service products are unaffected**

View a pre-existing or new non-service product's frontend JSON-LD and confirm `@type` is still `Product`.

- [ ] **Step 7: Verify Variable Service products**

Create a Variable product, check Service, add at least one variation with a price; confirm variations/pricing UI still functions normally and the frontend JSON-LD for that product still shows `@type: Service`.

---

## Self-Review

**Spec coverage:**
- `_is_service` flag, Simple/Variable only, implies Virtual → Task 3. ✓
- Service tab with Provider/Service Type/Area Served → Task 3. ✓
- Settings > Products > Service Schema defaults section → Task 4. ✓
- `woocommerce_structured_data_product` override (priority 20, `WC_Brands`-style), `@type` swap, `sku`/`gtin` removal, `provider`/`serviceType`/`areaServed` resolution with fallback chain (product meta → setting default → site title for provider only) → Task 5. ✓
- Stock management/sale pricing untouched → no task modifies these; confirmed by omission (Task 3 only touches `_is_service` and the three service-detail fields). ✓
- WordPress.org guideline checklist (placeholders, license, no phone-home, contextual notices, sanitization) → Task 1 (metadata/readme) and Task 2 (notice pattern); sanitization enforced throughout Task 3-5 code and verified in Task 6. ✓
- Verification plan from spec → Task 8 maps 1:1 to the spec's numbered verification steps. ✓

**Placeholder scan:** no "TBD"/"TODO" strings; every code step has complete, runnable code; no "similar to Task N" shortcuts — Task 3-5 test/implementation code is written out in full even where patterns repeat.

**Type consistency:** `_is_service` is consistently a string `'yes'`/`'no'` (never boolean) across Task 3's save logic, Task 5's `get_meta()` check, and all tests. `resolve_field()` in Task 5 always returns a string (never `false`/`null`), matching the `'' !== $value` checks used consistently. Option ids (`service_schema_wc_default_*`) match exactly between Task 4's registration and Task 5's `get_option()` calls. Tab `target` (`service_product_data`) matches between Task 3's `add_service_tab()` and `render_service_panel()`'s wrapping `<div id="...">`.
