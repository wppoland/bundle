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

    /** Request key carrying the product id on the add-bundle form. */
    public const REQUEST_KEY = 'bundle_add';

    /** Nonce action guarding the add-bundle request. */
    public const NONCE_ACTION = 'bundle_add_bundle';

    /** Cart-item flag marking a line as part of a bundle (parent product id). */
    public const CART_FLAG = '_bundle_parent';

    /** Bundle box template, relative to the templates/ directory. */
    public const BOX_TEMPLATE = 'single-product/bundle-box';

    /** Shortcode tag rendering a bundle box for a given (or current) product. */
    public const SHORTCODE = 'bundle';

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
            requestKey: self::REQUEST_KEY,
            nonceAction: self::NONCE_ACTION,
            cartFlag: self::CART_FLAG,
            boxTemplate: self::BOX_TEMPLATE,
            labels: $this->labels(),
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
        if (! $this->engine instanceof ProductBundleEngine) {
            // storefront-kit < 1.5.0 has no ProductBundleEngine. Bump the
            // `wppoland/storefront-kit` constraint (composer update) to enable
            // product bundles. No hooks are registered until the engine exists.
            return;
        }

        $this->engine->registerHooks();
        add_action('wp_enqueue_scripts', [$this, 'enqueueAssets']);
        add_shortcode(self::SHORTCODE, [$this, 'renderShortcode']);
    }

    public function enqueueAssets(): void
    {
        if (! $this->isEnabled() || (! is_product() && ! $this->postHasShortcode())) {
            return;
        }

        wp_enqueue_style(
            'bundle',
            BUNDLE_URL . 'assets/css/bundle.css',
            [],
            \Bundle\VERSION,
        );
    }

    /**
     * `[bundle]` / `[bundle id="123"]` — render a bundle box for the given
     * product (defaults to the current product in the loop). Lets merchants
     * place the box anywhere (page, post, block) rather than only after the
     * product summary. Returns an empty string when the product has no bundle.
     *
     * @param array<string, mixed>|string $atts
     */
    public function renderShortcode(array|string $atts = []): string
    {
        if (! $this->engine instanceof ProductBundleEngine || ! $this->isEnabled()) {
            return '';
        }

        $atts = shortcode_atts(['id' => 0], is_array($atts) ? $atts : [], self::SHORTCODE);
        $productId = absint($atts['id']);

        if ($productId === 0) {
            $productId = (int) get_the_ID();
        }

        $product = $productId > 0 ? wc_get_product($productId) : null;

        if (! $product instanceof \WC_Product) {
            return '';
        }

        $bundle = $this->engine->getBundle($product);

        if ($bundle['items'] === []) {
            return '';
        }

        ob_start();
        $this->renderTemplate(self::BOX_TEMPLATE, [
            'product'     => $product,
            'bundle'      => $bundle,
            'action_url'  => $this->addUrl($product),
            'nonce_field' => wp_create_nonce(self::NONCE_ACTION),
            'request_key' => self::REQUEST_KEY,
            'box_title'   => $this->labels()['box_title'],
            'add_label'   => $this->labels()['add_bundle'],
            'settings'    => $this->settings(),
        ]);

        return (string) ob_get_clean();
    }

    /**
     * Build the add-bundle URL (carries the request key + a fresh nonce). Mirrors
     * the engine's own action URL so the shortcode-rendered form posts the same
     * way the auto-rendered box does.
     */
    private function addUrl(\WC_Product $product): string
    {
        return add_query_arg(
            [
                self::REQUEST_KEY => $product->get_id(),
                '_wpnonce'        => wp_create_nonce(self::NONCE_ACTION),
            ],
            (string) $product->get_permalink(),
        );
    }

    /**
     * Whether the queried post embeds the `[bundle]` shortcode, so the stylesheet
     * is enqueued on pages that place the box outside the product template.
     */
    private function postHasShortcode(): bool
    {
        $post = get_post();

        return $post instanceof \WP_Post && has_shortcode((string) $post->post_content, self::SHORTCODE);
    }

    /**
     * Localised fallback labels for the bundle box, falling back to the
     * merchant's saved chrome where set.
     *
     * @return array{box_title: string, add_bundle: string, fee_label: string, add_failed: string}
     */
    private function labels(): array
    {
        $settings = $this->settings();

        return [
            'box_title'  => $this->label($settings, 'box_title', __('Frequently bought together', 'bundle')),
            'add_bundle' => $this->label($settings, 'add_label', __('Add bundle to cart', 'bundle')),
            'fee_label'  => $this->label($settings, 'fee_label', __('Bundle discount', 'bundle')),
            'add_failed' => $this->label($settings, 'add_failed_text', __('Some bundled products could not be added to the cart.', 'bundle')),
        ];
    }

    /**
     * @param array<string, mixed> $settings
     */
    private function label(array $settings, string $key, string $default): string
    {
        $value = isset($settings[$key]) ? trim((string) $settings[$key]) : '';

        return $value !== '' ? $value : $default;
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
