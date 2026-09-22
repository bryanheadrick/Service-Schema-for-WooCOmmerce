# Service Product Schema — Design

## Context

The `service-schema-for-woocommerce` plugin is currently an empty WP-CLI scaffold (plugin header + boilerplate readme only, no logic). The goal: let WooCommerce merchants mark a product as a **Service** and have the site's structured data output `https://schema.org/Service` instead of `https://schema.org/Product` for that item, via the `woocommerce_structured_data_product` filter.

This plugin is intended for submission to the wordpress.org plugin repository, so the design and implementation must follow the [WordPress.org Detailed Plugin Guidelines](https://developer.wordpress.org/plugins/wordpress-org/detailed-plugin-guidelines/).

Key product decision (from user clarification): **"Service" is a flag layered on existing product types (like the built-in "Virtual" checkbox), not a new entry in the product-type dropdown.** This means a merchant can have a Simple Service or a Variable Service. This was validated against WooCommerce internals research: Virtual/Downloadable are plain boolean postmeta on the base `WC_Product` class, not separate registered types — confirming "flag on Simple/Variable" is the standard, lower-risk pattern here, since a fully new registered product type would require patching several hardcoded `show_if_*`/`hide_if_*` template spots in WooCommerce core's admin templates for no functional benefit (the only real behavioral difference needed is the JSON-LD `@type`).

## Research Findings (WooCommerce internals, cited for implementation)

- **`_virtual` flag**: plain boolean data prop on `WC_Product` (`abstracts/abstract-wc-product.php:102-103`), stored as `_virtual` postmeta, read via `is_virtual()` (line ~1685). No separate `WC_Product_Virtual` class exists.
- **Admin tab visibility**: Shipping tab already carries `hide_if_virtual` in its `class` array (`includes/admin/meta-boxes/class-wc-meta-box-product-data.php`, `get_product_data_tabs()`). If our Service checkbox also sets `_virtual = yes`, Shipping auto-hides with zero extra admin JS/CSS.
- **Structured data hook**: `WC_Structured_Data::generate_product_data()` (`includes/class-wc-structured-data.php:194-522`) builds the full markup array, then applies `apply_filters( 'woocommerce_structured_data_product', $markup, $product )` at line 521. This is hooked to `woocommerce_single_product_summary` at priority 60 (constructor, line 42).
- **Direct precedent**: `WC_Brands::add_structured_data()` (`includes/class-wc-brands.php:49,482`) hooks this same filter at priority 20 and mutates `$markup` to add a `brand` key — exact pattern to follow for adding Service-specific keys without duplicating any of WooCommerce's markup-generation logic.
- **Settings > Products extension**: add a section via `woocommerce_get_sections_products` filter (fires in `WC_Settings_Page`, suffixed by page id); add fields via `woocommerce_get_settings_products` filter, checking the passed `$section_id` parameter against your own section id. Field arrays need a `type => 'title'` entry, then field entries, then a matching `type => 'sectionend'` entry. Saving/loading is fully automatic via WooCommerce core (`WC_Admin_Settings::save_fields()` / `get_option()`) — the field's `id` **is** the `wp_options` row name with no automatic prefixing, so this plugin must self-prefix all option ids (e.g. `service_schema_wc_default_provider`) to avoid collisions.
- **Product type auto-derivation**: not used here since we're not registering a new type — noted only because it confirms the "flag" approach is the intentionally simpler path.

## Design

### 1. Core flag: `_is_service` product meta

- New checkbox in the product data General tab (or alongside Virtual/Downloadable), label "This is a service", stored as post meta `_is_service` (`yes`/`no`), following the exact pattern WooCommerce uses for `_virtual`/`_downloadable` (`woocommerce_product_options_general_product_data` action, `WC_Meta_Box_Product_Data`).
- Available on **Simple** and **Variable** product types only (matches "still want Simple/Variable service"). Not shown for Grouped/External.
- Checking "Service" also force-sets `_virtual = yes` on save (Service implies Virtual, per user decision) — this is what gets the Shipping tab to auto-hide for free via WooCommerce's existing `hide_if_virtual` handling. Implementation: on `woocommerce_admin_process_product_object` (or equivalent save hook), if `_is_service` is `yes`, call `$product->set_virtual( true )`.
- Stock management, sale pricing, and tax class remain fully available and untouched (per user decision) — no forced changes there.

### 2. Per-product Service tab

- New tab in the product data panel, id `service_product_data`, added via the `woocommerce_product_data_tabs` filter, with `class => array( 'show_if_service' )` — shown only when a product has `_is_service = yes` (client-side toggle mirrors how `show_if_virtual` etc. already work; since `service` isn't a registered product *type*, this will need a small admin JS snippet toggling `.show_if_service` based on the checkbox state — the one piece of custom JS this design needs, scoped to the product edit screen only).
- Tab content (rendered via `woocommerce_product_data_panels` action): three text fields, each optional (blank = fall back to the global default from Settings > Products):
  - **Provider** (text) → schema.org `Service.provider` (as `Organization`)
  - **Service Type** (text) → schema.org `Service.serviceType`
  - **Area Served** (text) → schema.org `Service.areaServed` (output as plain string, per user decision — no structured Place/radius support in this version)
- Stored as postmeta: `_service_provider`, `_service_type`, `_service_area_served`.

### 3. Global defaults: WooCommerce > Settings > Products > "Service Schema" section

- New section added via `woocommerce_get_sections_products` (section id e.g. `service_schema`).
- Fields added via `woocommerce_get_settings_products`, filtering on `$section_id === 'service_schema'`:
  - **Default Provider Name** (text; option id `service_schema_wc_default_provider`) — falls back to the site title (`get_bloginfo( 'name' )`) if left blank, not just an empty value.
  - **Default Service Type** (text; option id `service_schema_wc_default_service_type`) — optional, no fallback if blank (field omitted from output).
  - **Default Area Served** (text; option id `service_schema_wc_default_area_served`) — optional, same as above.
- All option ids self-prefixed with `service_schema_wc_` to avoid collisions (per research finding — WooCommerce does not namespace third-party settings fields automatically).

### 4. Structured data output override

- New class/function hooked to `woocommerce_structured_data_product` at priority 20 (matching the `WC_Brands` precedent), receiving `( $markup, $product )`.
- If `$product` has postmeta `_is_service === 'yes'`:
  - Set `$markup['@type'] = 'Service'`.
  - `unset( $markup['sku'], $markup['gtin'] )` — Product-specific identifiers that don't apply to a Service.
  - Resolve provider name: per-product `_service_provider` meta, else the Settings default, else site title. Add `$markup['provider'] = array( '@type' => 'Organization', 'name' => $resolved_provider_name )`.
  - Resolve `_service_type` (product meta, else Settings default, else omit key). Add as `$markup['serviceType']` if resolved to a non-empty string.
  - Resolve `_service_area_served` the same way. Add as `$markup['areaServed']` (plain string) if resolved to a non-empty string.
  - Leave `name`, `url`, `description`, `image`, `offers`, `aggregateRating`, `review` untouched — all valid on schema.org Service as-is.
- All output values passed through `wp_kses_post()` / `esc_html()`-equivalent sanitization consistent with how `WC_Structured_Data` already escapes its own fields (matching existing WooCommerce conventions rather than introducing a different escaping style).

### File/class structure

- `service-schema-for-woocommerce.php` — bootstrap: header (fix placeholder fields — real plugin name, description, author, License: GPLv2 or later, License URI), plugin-activation guard (require WooCommerce active, contextual dismissible admin notice if not — no global nag), loads includes.
- `includes/class-ssw-product-fields.php` — registers the `_is_service` checkbox, the Service tab + its fields, and the save/sanitize logic (including force-set virtual).
- `includes/class-ssw-admin-settings.php` — registers the Settings > Products "Service Schema" section and fields.
- `includes/class-ssw-structured-data.php` — the `woocommerce_structured_data_product` filter callback.
- `assets/js/admin-product-service-tab.js` — minimal vanilla JS (no jQuery dependency needed beyond what's already loaded) to toggle `.show_if_service` panel visibility based on the checkbox; enqueued only on the product edit screen.

### WordPress.org guideline compliance checklist

- Fix all placeholder plugin-header fields (Plugin URI, Author, Description) and complete `readme.txt` (Contributors, Tags relevant to schema/structured-data/woocommerce/service, Tested up to, Stable tag matching plugin header Version) before submission — currently all placeholders.
- Declare `License: GPLv2 or later` + `License URI` in the plugin header and readme.txt.
- Zero external HTTP calls, analytics, or "phone home" behavior — everything here is local admin UI + local filter logic.
- No auto-injected frontend credit/backlink in the JSON-LD or elsewhere.
- The "WooCommerce not active" notice (if added) must be contextual (plugins/admin screen only) and self-dismiss once WooCommerce is active — not a sitewide persistent nag.
- All enqueued JS bundled locally, no CDN assets.
- Sanitize all settings/meta on save (`sanitize_text_field()`), escape all output (`esc_html()`/`wp_kses_post()` as appropriate) per existing WooCommerce conventions.

## Verification Plan

1. `studio wp plugin list --status=active --format=csv` to confirm WooCommerce is active in this Studio site.
2. Activate this plugin: `studio wp plugin activate service-schema-for-woocommerce`.
3. Create a Simple product, check "This is a service", fill in Provider/Service Type/Area Served in the new Service tab, save — confirm Shipping tab disappears (Virtual auto-set) and Service tab appears/disappears correctly when toggling the checkbox.
4. Set global defaults in WooCommerce > Settings > Products > Service Schema; create a second service product leaving fields blank; confirm it falls back to those defaults (and to site title for Provider if that's also blank).
5. View both products on the frontend; inspect page source to confirm `@type: "Service"`, correct `provider`/`serviceType`/`areaServed`, and absence of `sku`/`gtin`.
6. Confirm a normal (non-service) product's structured data is completely unaffected (`@type: "Product"` as before).
7. Test on a Variable product with Service checked: confirm variations/pricing still work normally and structured data still emits `Service`.
8. Run `phpcs` against `.phpcs.xml.dist` (already present in the repo) to catch escaping/sanitization issues before submission.
