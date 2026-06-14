<?php

declare(strict_types=1);

namespace Bundle\Service;

use Bundle\Contract\HasHooks;
use WPPoland\StorefrontKit\Bundle\ProductBundleEngine;

defined('ABSPATH') || exit;

/**
 * Thin adapter over the storefront-kit {@see ProductBundleEngine}.
 *
 * Injects this plugin's text-domain ('bundle'), request/nonce keys, the bundle
 * product-meta key and asset URLs into the namespace-neutral engine, and
 * supplies the closures it needs: an enabled-check, resolved settings, a reader
 * for the per-product bundle definition (stored as product meta — no custom
 * table) and a template renderer for the bundle box. All bundle orchestration
 * (render, add-to-cart, discount fee / per-item adjustment) lives in the kit;
 * this class only supplies localisation, option storage, the meta key and the
 * front-end stylesheet.
 */
final class BundleService implements HasHooks
{
    private const OPTION = 'bundle_settings';

    /** Product meta key holding the bundle definition (items + discount %). */
    public const META_BUNDLE = '_bundle_definition';

    private ?ProductBundleEngine $engine = null;

    public function __construct()
    {
        // The engine ships with storefront-kit >= 1.5.0. When present, wire it
        // with this plugin's text-domain / request keys / meta key. Otherwise
        // leave the service inert (see registerHooks()).
        if (! class_exists(ProductBundleEngine::class)) {
            return;
        }

        $this->engine = new ProductBundleEngine(
            requestKey: 'bundle_add',
            nonceAction: 'bundle_add_bundle',
            cartFlag: '_bundle_parent',
            boxTemplate: 'single-product/bundle-box',
            labels: [
                'box_title'  => __('Frequently bought together', 'bundle'),
                'add_bundle' => __('Add bundle to cart', 'bundle'),
                'fee_label'  => __('Bundle discount', 'bundle'),
                'add_failed' => __('Some bundled products could not be added to the cart.', 'bundle'),
            ],
            isEnabled: fn (): bool => $this->isEnabled(),
            settings: fn (): array => $this->settings(),
            productMeta: fn (\WC_Product $product): mixed => $this->bundleMeta($product),
            renderTemplate: function (string $template, array $context): void {
                $this->renderTemplate($template, $context);
            },
        );
    }

    public function registerHooks(): void
    {
        if ($this->engine instanceof ProductBundleEngine) {
            $this->engine->registerHooks();
            add_action('wp_enqueue_scripts', [$this, 'enqueueAssets']);

            return;
        }

        // TODO: storefront-kit < 1.5.0 has no ProductBundleEngine. Bump the
        // `wppoland/storefront-kit` constraint (composer update) to enable
        // product bundles. No hooks are registered until the engine is present.
    }

    public function enqueueAssets(): void
    {
        if (! $this->isEnabled() || ! is_product()) {
            return;
        }

        wp_enqueue_style(
            'bundle',
            BUNDLE_URL . 'assets/css/bundle.css',
            [],
            \Bundle\VERSION,
        );
    }

    private function isEnabled(): bool
    {
        return (bool) ($this->settings()['enabled'] ?? false);
    }

    /**
     * Read the raw bundle definition stored as product meta.
     *
     * Returns the stored array (`items` => list<int>, `discount_percent` =>
     * float) or null when no bundle is configured. The engine normalises it.
     *
     * @return array<string, mixed>|null
     */
    private function bundleMeta(\WC_Product $product): ?array
    {
        $raw = $product->get_meta(self::META_BUNDLE);

        return is_array($raw) && $raw !== [] ? $raw : null;
    }

    /**
     * Stored settings merged over packaged defaults.
     *
     * @return array<string, mixed>
     */
    private function settings(): array
    {
        $stored = get_option(self::OPTION, []);

        if (! is_array($stored)) {
            $stored = [];
        }

        /** @var array<string, mixed> $defaults */
        $defaults = require BUNDLE_DIR . 'config/defaults.php';

        return array_merge($defaults, $stored);
    }

    /**
     * @param array<string, mixed> $context
     */
    private function renderTemplate(string $template, array $context): void
    {
        $file = BUNDLE_DIR . 'templates/' . $template . '.php';

        if (! is_readable($file)) {
            return;
        }

        extract($context, EXTR_SKIP);
        require $file;
    }
}
