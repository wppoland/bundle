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

    /** @var string|null Set to the settings hook suffix so assets load only there. */
    private ?string $hookSuffix = null;

    public function registerHooks(): void
    {
        add_action('admin_menu', [$this, 'addMenuPage']);
        add_action('admin_init', [$this, 'registerSettings']);
        add_action('admin_enqueue_scripts', [$this, 'enqueueAssets']);
    }

    public function addMenuPage(): void
    {
        $this->hookSuffix = add_submenu_page(
            'woocommerce',
            __('Bundle Settings', 'bundle'),
            __('Bundle', 'bundle'),
            'manage_woocommerce',
            self::PAGE,
            [$this, 'renderPage'],
        ) ?: null;
    }

    /**
     * Enqueue the settings-page polish (card styling + accessible help bubbles).
     * Loaded only on this plugin's settings screen.
     */
    public function enqueueAssets(string $hook): void
    {
        if ($this->hookSuffix === null || $hook !== $this->hookSuffix) {
            return;
        }

        wp_enqueue_style(
            'bundle-admin',
            BUNDLE_URL . 'assets/css/admin.css',
            [],
            \Bundle\VERSION,
        );

        wp_enqueue_script(
            'bundle-admin',
            BUNDLE_URL . 'assets/js/admin.js',
            [],
            \Bundle\VERSION,
            ['in_footer' => true],
        );
    }

    /**
     * Render a "?" help affordance and the accessible popover bubble it controls.
     * The bubble is a native [popover] wired via aria-describedby, so the help
     * text is always reachable by keyboard and screen readers.
     */
    private function help(string $id, string $text): string
    {
        return sprintf(
            '<button type="button" class="bundle-help" aria-describedby="%1$s" aria-label="%2$s">?</button>'
            . '<span id="%1$s" class="bundle-help-pop" popover role="tooltip">%3$s</span>',
            esc_attr($id),
            esc_attr__('More information', 'bundle'),
            esc_html($text),
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
        $option   = self::OPTION;

        // Allowed HTML for the help affordances (button + popover span).
        $help_html = [
            'button' => [
                'type'             => true,
                'class'            => true,
                'aria-describedby' => true,
                'aria-label'       => true,
            ],
            'span'   => [
                'id'      => true,
                'class'   => true,
                'popover' => true,
                'role'    => true,
            ],
        ];
        ?>
        <div class="wrap bundle-settings">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            <p class="bundle-settings__intro">
                <?php esc_html_e('Configure the "frequently bought together" box that lets shoppers add a product and its companions to the cart in one click. Link the companion products and set an optional discount on each product in the product editor, under the "Bundle" tab.', 'bundle'); ?>
            </p>
            <p class="bundle-settings__intro description">
                <?php
                printf(
                    /* translators: 1: [bundle] shortcode, 2: [bundle id="123"] shortcode. */
                    esc_html__('Prefer to place the box yourself? Use the %1$s shortcode on any page, or %2$s to target a specific product, then turn off "Show on product page" below.', 'bundle'),
                    '<code>[bundle]</code>',
                    '<code>[bundle id="123"]</code>'
                );
                ?>
            </p>

            <form method="post" action="options.php">
                <?php settings_fields(self::PAGE); ?>

                <div class="bundle-settings__card">
                    <table class="form-table" role="presentation">
                        <tbody>
                            <tr>
                                <th scope="row">
                                    <?php esc_html_e('Enable bundles', 'bundle'); ?>
                                    <?php echo wp_kses($this->help('bundle_help_enabled', __('Master switch. When off, no bundle box is shown anywhere and no bundle discount is applied — your products keep selling normally.', 'bundle')), $help_html); ?>
                                </th>
                                <td>
                                    <label for="bundle_enabled">
                                        <input type="checkbox" id="bundle_enabled" name="<?php echo esc_attr($option); ?>[enabled]" value="1" <?php checked((bool) ($settings['enabled'] ?? false), true); ?> />
                                        <?php esc_html_e('Show the bundle box on product pages and apply bundle discounts.', 'bundle'); ?>
                                    </label>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <?php esc_html_e('Show on product page', 'bundle'); ?>
                                    <?php echo wp_kses($this->help('bundle_help_single', __('Automatically render the box just below the product summary on single product pages. Turn this off if you only want to place the box manually with the [bundle] shortcode.', 'bundle')), $help_html); ?>
                                </th>
                                <td>
                                    <label for="bundle_show_on_single">
                                        <input type="checkbox" id="bundle_show_on_single" name="<?php echo esc_attr($option); ?>[show_on_single]" value="1" <?php checked((bool) ($settings['show_on_single'] ?? true), true); ?> />
                                        <?php esc_html_e('Render the bundle box below the product summary.', 'bundle'); ?>
                                    </label>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <?php esc_html_e('Show bundled items', 'bundle'); ?>
                                    <?php echo wp_kses($this->help('bundle_help_items', __('Display each companion product with its thumbnail and price inside the box, so shoppers see exactly what they are getting. Turn off for a compact box with just the button.', 'bundle')), $help_html); ?>
                                </th>
                                <td>
                                    <label for="bundle_show_items">
                                        <input type="checkbox" id="bundle_show_items" name="<?php echo esc_attr($option); ?>[show_items]" value="1" <?php checked((bool) ($settings['show_items'] ?? true), true); ?> />
                                        <?php esc_html_e('List the bundled products (with thumbnails) inside the box.', 'bundle'); ?>
                                    </label>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <?php esc_html_e('Show savings', 'bundle'); ?>
                                    <?php echo wp_kses($this->help('bundle_help_savings', __('Show the bundle total and the exact amount saved (e.g. "you save $12.00"). A clear saving figure is a strong nudge to buy the whole bundle.', 'bundle')), $help_html); ?>
                                </th>
                                <td>
                                    <label for="bundle_show_savings">
                                        <input type="checkbox" id="bundle_show_savings" name="<?php echo esc_attr($option); ?>[show_savings]" value="1" <?php checked((bool) ($settings['show_savings'] ?? true), true); ?> />
                                        <?php esc_html_e('Show the bundle total and the amount saved on the bundle box.', 'bundle'); ?>
                                    </label>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <?php esc_html_e('Discount mode', 'bundle'); ?>
                                    <?php echo wp_kses($this->help('bundle_help_mode', __('How the discount appears in the cart. "Single cart fee" adds one negative line ("Bundle discount −$12.00") — simplest and clearest. "Per-item" lowers the price of each bundled product instead, which suits stores that need the discount reflected on every line and in per-product tax.', 'bundle')), $help_html); ?>
                                </th>
                                <td>
                                    <select id="bundle_discount_mode" name="<?php echo esc_attr($option); ?>[discount_mode]">
                                        <option value="fee" <?php selected($mode, 'fee'); ?>><?php esc_html_e('Single cart fee (one negative line)', 'bundle'); ?></option>
                                        <option value="per_item" <?php selected($mode, 'per_item'); ?>><?php esc_html_e('Per-item price adjustment', 'bundle'); ?></option>
                                    </select>
                                    <p class="description"><?php esc_html_e('Choose how the bundle discount is shown when bundled items are in the cart. Most stores want the single cart fee.', 'bundle'); ?></p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label for="bundle_box_title"><?php esc_html_e('Box title', 'bundle'); ?></label>
                                    <?php echo wp_kses($this->help('bundle_help_title', __('The heading shown at the top of the bundle box. Keep it short and benefit-led, e.g. "Frequently bought together" or "Complete the set".', 'bundle')), $help_html); ?>
                                </th>
                                <td>
                                    <input type="text" id="bundle_box_title" name="<?php echo esc_attr($option); ?>[box_title]" value="<?php echo esc_attr((string) ($settings['box_title'] ?? '')); ?>" class="regular-text" placeholder="<?php esc_attr_e('Frequently bought together', 'bundle'); ?>" />
                                    <p class="description"><?php esc_html_e('Leave blank to use the default "Frequently bought together".', 'bundle'); ?></p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label for="bundle_add_label"><?php esc_html_e('Add-to-cart label', 'bundle'); ?></label>
                                    <?php echo wp_kses($this->help('bundle_help_add', __('Text on the button that adds the whole bundle to the cart. An action-led label such as "Add all to cart" or "Buy the set" outperforms a generic one.', 'bundle')), $help_html); ?>
                                </th>
                                <td>
                                    <input type="text" id="bundle_add_label" name="<?php echo esc_attr($option); ?>[add_label]" value="<?php echo esc_attr((string) ($settings['add_label'] ?? '')); ?>" class="regular-text" placeholder="<?php esc_attr_e('Add bundle to cart', 'bundle'); ?>" />
                                    <p class="description"><?php esc_html_e('Leave blank to use the default "Add bundle to cart".', 'bundle'); ?></p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label for="bundle_fee_label"><?php esc_html_e('Discount line label', 'bundle'); ?></label>
                                    <?php echo wp_kses($this->help('bundle_help_fee', __('The wording of the discount line in the cart and at checkout when "Single cart fee" mode is used, e.g. "Bundle discount". Shoppers see this next to the saved amount.', 'bundle')), $help_html); ?>
                                </th>
                                <td>
                                    <input type="text" id="bundle_fee_label" name="<?php echo esc_attr($option); ?>[fee_label]" value="<?php echo esc_attr((string) ($settings['fee_label'] ?? '')); ?>" class="regular-text" placeholder="<?php esc_attr_e('Bundle discount', 'bundle'); ?>" />
                                    <p class="description"><?php esc_html_e('Shown on the cart fee line (fee mode). Leave blank to use the default "Bundle discount".', 'bundle'); ?></p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

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
