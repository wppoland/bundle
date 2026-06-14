# Bundle – Product Bundles for WooCommerce

A "frequently bought together" / product-bundle box for WooCommerce. An admin
links N products to a product plus an optional bundle discount; "Add bundle to
cart" adds all linked items, and the discount is applied as a cart fee or a
per-item adjustment. Bundle definitions are stored as product meta — no custom
tables.

Thin adapter over [`wppoland/storefront-kit`](https://github.com/wppoland/storefront-kit)
(`WPPoland\StorefrontKit\Bundle\ProductBundleEngine`), wired with closures.

## Develop

```bash
composer install
composer cs        # PHPCS (WordPress security/i18n subset)
composer analyse   # PHPStan level 6
```

WordPress.org Plugin Check runs in CI via wp-env + WooCommerce.

## Usage

1. Activate the plugin (WooCommerce must be active).
2. Edit a product → **Bundle** tab → enter bundled product IDs + an optional
   discount %.
3. Tune global behaviour under **WooCommerce → Bundle** (discount mode, labels,
   visibility).

## License

GPL-2.0-or-later.
