<?php

declare(strict_types=1);

namespace Bundle\Admin;

defined('ABSPATH') || exit;

use Bundle\Contract\HasHooks;

/**
 * Admin settings page registered as a WooCommerce submenu ("Bundle").
 *
 * Stores settings in the `bundle_settings` option (array): the master toggle,
 * the bundle box title and add-to-cart label, and how the discount is applied
 * (single cart fee vs. per-item price adjustment). All output is escaped; all
 * input is sanitised on save. Per-product bundle definitions live on the product
 * itself (see {@see ProductBundleBox}), not here.
 */
final class Settings implements HasHooks
{
    private const OPTION = 'bundle_settings';
    private const PAGE   = 'bundle-settings';

    public function registerHooks(): void
    {
        add_action('admin_menu', [$this, 'addMenuPage']);
        add_action('admin_init', [$this, 'registerSettings']);
    }

    public function addMenuPage(): void
    {
        add_submenu_page(
            'woocommerce',
            __('Bundle Settings', 'bundle'),
            __('Bundle', 'bundle'),
            'manage_woocommerce',
            self::PAGE,
            [$this, 'renderPage'],
        );
    }

    public function registerSettings(): void
    {
        register_setting(
            self::PAGE,
            self::OPTION,
            [
                'type'              => 'array',
                'sanitize_callback' => [$this, 'sanitize'],
            ],
        );

        // The menu uses manage_woocommerce; align the options.php save capability
        // so shop managers (not just admins with manage_options) can save.
        add_filter(
            'option_page_capability_' . self::PAGE,
            static fn (): string => 'manage_woocommerce',
        );
    }

    public function renderPage(): void
    {
        if (! current_user_can('manage_woocommerce')) {
            return;
        }

        $settings = $this->settings();
        $mode     = (string) ($settings['discount_mode'] ?? 'fee');
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            <p>
                <?php esc_html_e('Configure the "frequently bought together" bundle box. Link products and set a discount on each product in the product editor under the "Bundle" tab.', 'bundle'); ?>
            </p>
            <p class="description">
                <?php esc_html_e('Place the bundle box anywhere with the [bundle] shortcode, or [bundle id="123"] to target a specific product. Turn off "Show on product page" below to use the shortcode only.', 'bundle'); ?>
            </p>
            <form method="post" action="options.php">
                <?php settings_fields(self::PAGE); ?>

                <table class="form-table" role="presentation">
                    <tbody>
                        <tr>
                            <th scope="row"><?php esc_html_e('Enable bundles', 'bundle'); ?></th>
                            <td>
                                <label for="bundle_enabled">
                                    <input
                                        type="checkbox"
                                        id="bundle_enabled"
                                        name="<?php echo esc_attr(self::OPTION); ?>[enabled]"
                                        value="1"
                                        <?php checked((bool) ($settings['enabled'] ?? false), true); ?>
                                    />
                                    <?php esc_html_e('Show the bundle box on product pages and apply bundle discounts.', 'bundle'); ?>
                                </label>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php esc_html_e('Show on product page', 'bundle'); ?></th>
                            <td>
                                <label for="bundle_show_on_single">
                                    <input
                                        type="checkbox"
                                        id="bundle_show_on_single"
                                        name="<?php echo esc_attr(self::OPTION); ?>[show_on_single]"
                                        value="1"
                                        <?php checked((bool) ($settings['show_on_single'] ?? true), true); ?>
                                    />
                                    <?php esc_html_e('Render the bundle box below the product summary.', 'bundle'); ?>
                                </label>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php esc_html_e('Show bundled items', 'bundle'); ?></th>
                            <td>
                                <label for="bundle_show_items">
                                    <input
                                        type="checkbox"
                                        id="bundle_show_items"
                                        name="<?php echo esc_attr(self::OPTION); ?>[show_items]"
                                        value="1"
                                        <?php checked((bool) ($settings['show_items'] ?? true), true); ?>
                                    />
                                    <?php esc_html_e('List the bundled products (with thumbnails) inside the box.', 'bundle'); ?>
                                </label>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php esc_html_e('Show savings', 'bundle'); ?></th>
                            <td>
                                <label for="bundle_show_savings">
                                    <input
                                        type="checkbox"
                                        id="bundle_show_savings"
                                        name="<?php echo esc_attr(self::OPTION); ?>[show_savings]"
                                        value="1"
                                        <?php checked((bool) ($settings['show_savings'] ?? true), true); ?>
                                    />
                                    <?php esc_html_e('Show the bundle total and the amount saved on the bundle box.', 'bundle'); ?>
                                </label>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php esc_html_e('Discount mode', 'bundle'); ?></th>
                            <td>
                                <select
                                    id="bundle_discount_mode"
                                    name="<?php echo esc_attr(self::OPTION); ?>[discount_mode]"
                                >
                                    <option value="fee" <?php selected($mode, 'fee'); ?>>
                                        <?php esc_html_e('Single cart fee (one negative line)', 'bundle'); ?>
                                    </option>
                                    <option value="per_item" <?php selected($mode, 'per_item'); ?>>
                                        <?php esc_html_e('Per-item price adjustment', 'bundle'); ?>
                                    </option>
                                </select>
                                <p class="description">
                                    <?php esc_html_e('How the bundle discount is applied when bundled items are in the cart.', 'bundle'); ?>
                                </p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="bundle_box_title"><?php esc_html_e('Box title', 'bundle'); ?></label>
                            </th>
                            <td>
                                <input
                                    type="text"
                                    id="bundle_box_title"
                                    name="<?php echo esc_attr(self::OPTION); ?>[box_title]"
                                    value="<?php echo esc_attr((string) ($settings['box_title'] ?? '')); ?>"
                                    class="regular-text"
                                />
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="bundle_add_label"><?php esc_html_e('Add-to-cart label', 'bundle'); ?></label>
                            </th>
                            <td>
                                <input
                                    type="text"
                                    id="bundle_add_label"
                                    name="<?php echo esc_attr(self::OPTION); ?>[add_label]"
                                    value="<?php echo esc_attr((string) ($settings['add_label'] ?? '')); ?>"
                                    class="regular-text"
                                />
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="bundle_fee_label"><?php esc_html_e('Discount line label', 'bundle'); ?></label>
                            </th>
                            <td>
                                <input
                                    type="text"
                                    id="bundle_fee_label"
                                    name="<?php echo esc_attr(self::OPTION); ?>[fee_label]"
                                    value="<?php echo esc_attr((string) ($settings['fee_label'] ?? '')); ?>"
                                    class="regular-text"
                                />
                                <p class="description"><?php esc_html_e('Shown on the cart fee line (fee mode).', 'bundle'); ?></p>
                            </td>
                        </tr>
                    </tbody>
                </table>

                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }

    /**
     * Sanitises the submitted settings before save, preserving defaults for any
     * field not on the form.
     *
     * @param mixed $raw
     * @return array<string, mixed>
     */
    public function sanitize(mixed $raw): array
    {
        if (! is_array($raw)) {
            $raw = [];
        }

        $defaults = $this->settings();

        $mode = isset($raw['discount_mode']) ? sanitize_text_field((string) $raw['discount_mode']) : 'fee';

        if (! in_array($mode, ['fee', 'per_item'], true)) {
            $mode = 'fee';
        }

        $boxTitle = isset($raw['box_title']) ? sanitize_text_field((string) $raw['box_title']) : '';
        $addLabel = isset($raw['add_label']) ? sanitize_text_field((string) $raw['add_label']) : '';
        $feeLabel = isset($raw['fee_label']) ? sanitize_text_field((string) $raw['fee_label']) : '';

        return array_merge($defaults, [
            'enabled'        => ! empty($raw['enabled']),
            'show_on_single' => ! empty($raw['show_on_single']),
            'show_items'     => ! empty($raw['show_items']),
            'show_savings'   => ! empty($raw['show_savings']),
            'discount_mode'  => $mode,
            'box_title'      => $boxTitle !== '' ? $boxTitle : (string) ($defaults['box_title'] ?? __('Frequently bought together', 'bundle')),
            'add_label'      => $addLabel !== '' ? $addLabel : (string) ($defaults['add_label'] ?? __('Add bundle to cart', 'bundle')),
            'fee_label'      => $feeLabel !== '' ? $feeLabel : (string) ($defaults['fee_label'] ?? __('Bundle discount', 'bundle')),
        ]);
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
}
