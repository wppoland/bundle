<?php

declare(strict_types=1);

namespace Bundle\Admin;

use Bundle\Contract\HasHooks;
use Bundle\Service\BundleService;

defined('ABSPATH') || exit;

/**
 * Adds a "Product bundle" panel to the WooCommerce product data meta box, where
 * the merchant links N other products to this product plus an optional bundle
 * discount %. The definition is saved as the `_bundle_definition` product meta
 * read back by {@see BundleService}. No custom table.
 *
 * Product ids are entered as a comma-separated list (kept dependency-free; the
 * PRO add-on upgrades this to a product search field). All input is sanitised on
 * save behind a nonce; all output is escaped.
 */
final class ProductBundleBox implements HasHooks
{
    private const NONCE_ACTION = 'bundle_save_definition';
    private const NONCE_FIELD  = 'bundle_definition_nonce';

    public function registerHooks(): void
    {
        add_action('woocommerce_product_data_panels', [$this, 'renderPanel']);
        add_filter('woocommerce_product_data_tabs', [$this, 'addTab']);
        add_action('woocommerce_process_product_meta', [$this, 'save']);
    }

    /**
     * Register the "Bundle" product-data tab.
     *
     * @param array<string, array<string, mixed>> $tabs
     * @return array<string, array<string, mixed>>
     */
    public function addTab(array $tabs): array
    {
        $tabs['bundle'] = [
            'label'    => __('Bundle', 'bundle'),
            'target'   => 'bundle_product_data',
            'class'    => ['show_if_simple', 'show_if_variable'],
            'priority' => 65,
        ];

        return $tabs;
    }

    public function renderPanel(): void
    {
        global $post;

        $product = $post instanceof \WP_Post ? wc_get_product($post->ID) : null;
        $items   = '';
        $percent = '';

        if ($product instanceof \WC_Product) {
            $raw = $product->get_meta(BundleService::META_BUNDLE);

            if (is_array($raw)) {
                $items   = implode(', ', array_map('absint', (array) ($raw['items'] ?? [])));
                $percent = isset($raw['discount_percent']) ? (string) (float) $raw['discount_percent'] : '';
            }
        }
        ?>
        <div id="bundle_product_data" class="panel woocommerce_options_panel hidden">
            <?php wp_nonce_field(self::NONCE_ACTION, self::NONCE_FIELD); ?>
            <div class="options_group">
                <p class="form-field">
                    <label for="bundle_items"><?php esc_html_e('Bundled product IDs', 'bundle'); ?></label>
                    <input
                        type="text"
                        id="bundle_items"
                        name="bundle_items"
                        class="short"
                        style="width:50%;"
                        value="<?php echo esc_attr($items); ?>"
                        placeholder="e.g. 42, 108, 256"
                    />
                    <span class="description">
                        <?php esc_html_e('Comma-separated product IDs to sell alongside this product.', 'bundle'); ?>
                    </span>
                </p>
                <p class="form-field">
                    <label for="bundle_discount_percent"><?php esc_html_e('Bundle discount (%)', 'bundle'); ?></label>
                    <input
                        type="number"
                        id="bundle_discount_percent"
                        name="bundle_discount_percent"
                        class="short"
                        min="0"
                        max="100"
                        step="0.01"
                        value="<?php echo esc_attr($percent); ?>"
                    />
                    <span class="description">
                        <?php esc_html_e('Optional discount applied when the whole bundle is added to the cart.', 'bundle'); ?>
                    </span>
                </p>
            </div>
        </div>
        <?php
    }

    public function save(int $postId): void
    {
        $nonce = isset($_POST[self::NONCE_FIELD])
            ? sanitize_text_field(wp_unslash($_POST[self::NONCE_FIELD]))
            : '';

        if (! wp_verify_nonce($nonce, self::NONCE_ACTION)) {
            return;
        }

        if (! current_user_can('edit_product', $postId)) {
            return;
        }

        $product = wc_get_product($postId);

        if (! $product instanceof \WC_Product) {
            return;
        }

        $rawItems = isset($_POST['bundle_items'])
            ? sanitize_text_field(wp_unslash($_POST['bundle_items']))
            : '';

        $items = [];

        foreach (explode(',', $rawItems) as $candidate) {
            $itemId = absint(trim($candidate));

            if ($itemId > 0 && $itemId !== $postId && ! in_array($itemId, $items, true)) {
                $items[] = $itemId;
            }
        }

        $percent = isset($_POST['bundle_discount_percent'])
            ? (float) sanitize_text_field(wp_unslash($_POST['bundle_discount_percent']))
            : 0.0;

        $percent = max(0.0, min(100.0, $percent));

        if ($items === []) {
            $product->delete_meta_data(BundleService::META_BUNDLE);
        } else {
            $product->update_meta_data(BundleService::META_BUNDLE, [
                'items'            => $items,
                'discount_percent' => $percent,
            ]);
        }

        $product->save();
    }
}
