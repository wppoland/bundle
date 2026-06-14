=== Bundle – Product Bundles for WooCommerce ===
Contributors: wppoland
Tags: woocommerce, product bundles, frequently bought together, upsell, discount
Requires at least: 6.5
Tested up to: 7.0
Requires PHP: 8.1
Stable tag: 0.1.0
Requires Plugins: woocommerce
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Sell groups of products together as a bundle with an optional bundle discount.

== Description ==

Bundle adds a "frequently bought together" box to your WooCommerce product pages. Link any number of products to a product, set an optional bundle discount, and let customers add the whole bundle to the cart in one click.

* A bundle box under the product summary that lists the bundled products.
* "Add bundle to cart" adds the main product plus every linked item at once.
* Optional bundle discount, applied either as a single cart fee or as a per-item price adjustment.
* Bundle definitions are stored as product meta — no custom database tables.
* Lightweight, accessible markup and a single small stylesheet (no jQuery).

Configure global behaviour under WooCommerce → Bundle. Link products and set the discount per product in the product editor's "Bundle" tab.

== Installation ==

1. Upload the plugin to `/wp-content/plugins/bundle`, or install via Plugins → Add New.
2. Activate it. WooCommerce must be active.
3. Edit a product, open the "Bundle" tab, enter the bundled product IDs and an optional discount, then save.
4. Adjust global options under WooCommerce → Bundle.

== Frequently Asked Questions ==

= Does it require WooCommerce? =

Yes. WooCommerce must be installed and active.

= How is the discount applied? =

Choose between a single negative cart fee (one line in the cart) or a per-item price adjustment on each bundled product. Set this under WooCommerce → Bundle.

= Does it create custom database tables? =

No. Bundle definitions are stored as product meta.

== Screenshots ==

1. Bundle – Product Bundles for WooCommerce in action.

== Changelog ==

= 0.1.0 =
* Initial release.
