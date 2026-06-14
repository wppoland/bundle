=== Bundle – Product Bundles for WooCommerce ===
Contributors: wppoland
Tags: woocommerce, product bundles, frequently bought together, upsell, discount
Requires at least: 6.5
Tested up to: 7.0
Requires PHP: 8.1
Stable tag: 0.2.0
Requires Plugins: woocommerce
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Sell groups of products together as a bundle with an optional bundle discount.

== Description ==

Bundle adds a "frequently bought together" box to your WooCommerce product pages. Link any number of products to a product, set an optional bundle discount, and let customers add the whole bundle to the cart in one click.

* A bundle box under the product summary that lists the bundled products.
* "Add bundle to cart" adds the main product plus every linked item at once.
* Optional bundle discount, applied either as a single cart fee or as a per-item price adjustment.
* An optional savings line that shows the bundle total and the amount saved.
* A `[bundle]` shortcode to place the bundle box anywhere — use `[bundle id="123"]` to target a specific product.
* Editable box title, button label and discount-line label, all translatable.
* Bundle definitions are stored as product meta — no custom database tables.
* Clean uninstall: removes its options and bundle definitions when deleted.
* Lightweight, accessible markup with a small, themeable stylesheet — dark-mode aware, no layout shift, no jQuery.
* Helpful inline tips on every setting and clear empty states throughout.

Configure global behaviour under WooCommerce → Bundle. Link products and set the discount per product in the product editor's "Bundle" tab. Turn off "Show on product page" to render the box only where you drop the `[bundle]` shortcode.

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

= Can I place the bundle box somewhere other than under the product summary? =

Yes. Use the `[bundle]` shortcode anywhere the current product is known, or `[bundle id="123"]` to render a specific product's bundle. Turn off "Show on product page" under WooCommerce → Bundle to use the shortcode only.

= What happens when I delete the plugin? =

Bundle removes its settings option and every stored bundle definition on uninstall, leaving no leftover data.

== Screenshots ==

1. Bundle – Product Bundles for WooCommerce in action.

== Changelog ==

= 0.2.0 =
* Polish: refreshed settings page with a clean card layout and an accessible "?" help bubble on every option.
* Polish: modern, themeable storefront box — fluid sizing, dark-mode support, reserved image space (no layout shift) and a clearer savings badge.
* A11y: keyboard-operable help affordances, visible focus styles and reduced-motion support throughout.
* Robustness: deleted or missing bundled products are skipped gracefully; the box hides itself when nothing remains to bundle.
* New: `[bundle]` shortcode to render the bundle box anywhere, with an optional `id` attribute to target a specific product.
* New: "Show savings" option that displays the bundle total and the amount saved on the box.
* New: the admin-saved box title, button label and discount-line label are now used by the bundle box (previously the box ignored them).
* New: clean uninstall removes the plugin's options and all stored bundle definitions.
* I18n: regenerated the translation template with the new strings.

= 0.1.0 =
* Initial release.
