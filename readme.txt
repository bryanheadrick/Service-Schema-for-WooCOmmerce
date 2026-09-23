=== Plumbline Labs Service Schema For WooCommerce ===
Contributors: bryanheadrick
Tags: woocommerce, schema, structured-data, seo, service
Requires at least: 6.0
Tested up to: 7.1
Requires PHP: 7.4
WC requires at least: 8.0
WC tested up to: 10.0
Stable tag: 0.1.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Mark WooCommerce products as services and output schema.org Service structured data instead of Product.

== Description ==

Plumbline Labs Service Schema For WooCommerce adds a "This is a service" option to Simple and Variable products. When enabled, the plugin changes the product's JSON-LD structured data `@type` from `Product` to `Service` (https://schema.org/Service), and lets you specify a provider, service type, and area served — either per product or as site-wide defaults under WooCommerce > Settings > Products.

This plugin makes no external network requests and collects no data.

== Installation ==

1. Upload the plugin files to the `/wp-content/plugins/plumbline-labs-service-schema-for-woocommerce` directory, or install through the WordPress plugins screen directly.
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

= 0.1.1 =
* Rebranded to Plumbline Labs Service Schema For WooCommerce; all functions, classes, constants, stored options, and product meta keys now use the `plbl_` prefix.

= 0.1.0 =
* Initial release.
