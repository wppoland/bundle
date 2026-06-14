<?php
/**
 * "Frequently bought together" bundle box, rendered by the storefront-kit
 * ProductBundleEngine on `woocommerce_after_single_product_summary`.
 *
 * Lists the bundled products (when enabled) and a single button that adds the
 * whole bundle — the main product plus every linked item — to the cart. The
 * engine applies the configured discount (cart fee or per-item) afterwards.
 *
 * @var \WC_Product                                          $product
 * @var array{items: list<int>, discount_percent: float}     $bundle
 * @var string                                               $action_url  Add-bundle URL (carries request key + nonce).
 * @var string                                               $nonce_field Nonce value for the add action.
 * @var string                                               $request_key Request parameter name carrying the product id.
 * @var string                                               $box_title
 * @var string                                               $add_label
 * @var array<string, mixed>                                 $settings
 *
 * @package Bundle/Templates
 */

declare(strict_types=1);

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Template-scope variables supplied by the engine via extract().

defined('ABSPATH') || exit;

$bundle_show_items   = (bool) ($settings['show_items'] ?? true);
$bundle_show_savings = (bool) ($settings['show_savings'] ?? true);
$bundle_percent      = (float) ($bundle['discount_percent'] ?? 0.0);

// Resolve every linked item once. Missing/deleted products are skipped so the
// box never renders a broken thumbnail or a dangling row. We also tally the
// combined list price (main product + each resolvable item) for the savings line.
$bundle_items   = [];
$bundle_total   = (float) $product->get_price('edit');

foreach ((array) ($bundle['items'] ?? []) as $bundle_item_id) {
    $bundle_item = wc_get_product((int) $bundle_item_id);

    if (! $bundle_item instanceof \WC_Product) {
        continue;
    }

    $bundle_items[] = $bundle_item;
    $bundle_total  += (float) $bundle_item->get_price('edit');
}

// Nothing resolvable to bundle with — render nothing rather than a lone product
// with an "Add bundle" button that would behave like a normal add-to-cart.
if ($bundle_items === []) {
    return;
}

$bundle_savings = $bundle_percent > 0.0
    ? round($bundle_total * ($bundle_percent / 100), wc_get_price_decimals())
    : 0.0;
?>
<section class="bundle-box" aria-labelledby="bundle-box-title">
    <h2 id="bundle-box-title" class="bundle-box__title"><?php echo esc_html($box_title); ?></h2>

    <?php if ($bundle_show_items) : ?>
        <ul class="bundle-box__items">
            <li class="bundle-box__item bundle-box__item--main">
                <?php echo wp_kses_post($product->get_image('woocommerce_gallery_thumbnail')); ?>
                <span class="bundle-box__item-name"><?php echo esc_html($product->get_name()); ?></span>
            </li>
            <?php foreach ($bundle_items as $bundle_item) : ?>
                <li class="bundle-box__item">
                    <span class="bundle-box__plus" aria-hidden="true">+</span>
                    <?php echo wp_kses_post($bundle_item->get_image('woocommerce_gallery_thumbnail')); ?>
                    <span class="bundle-box__item-name">
                        <?php echo esc_html($bundle_item->get_name()); ?>
                    </span>
                    <?php $bundle_item_price = $bundle_item->get_price_html(); ?>
                    <?php if (is_string($bundle_item_price) && $bundle_item_price !== '') : ?>
                        <span class="bundle-box__item-price">
                            <?php echo wp_kses_post($bundle_item_price); ?>
                        </span>
                    <?php endif; ?>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>

    <?php if ($bundle_percent > 0.0) : ?>
        <p class="bundle-box__discount">
            <span aria-hidden="true">&#10022;</span>
            <?php
            printf(
                /* translators: %s: discount percentage. */
                esc_html__('Buy together and save %s%%', 'bundle'),
                esc_html(wc_format_localized_decimal($bundle_percent))
            );
            ?>
        </p>

        <?php if ($bundle_show_savings && $bundle_savings > 0.0) : ?>
            <p class="bundle-box__savings">
                <?php
                printf(
                    /* translators: 1: total bundle price, 2: amount saved. */
                    wp_kses_post(__('Bundle total <strong>%1$s</strong> — you save <strong>%2$s</strong>.', 'bundle')),
                    wp_kses_post(wc_price($bundle_total)),
                    wp_kses_post(wc_price($bundle_savings))
                );
                ?>
            </p>
        <?php endif; ?>
    <?php endif; ?>

    <form class="bundle-box__form" method="post" action="<?php echo esc_url($action_url); ?>">
        <input type="hidden" name="<?php echo esc_attr($request_key); ?>" value="<?php echo esc_attr((string) $product->get_id()); ?>" />
        <input type="hidden" name="_wpnonce" value="<?php echo esc_attr($nonce_field); ?>" />
        <button type="submit" class="button alt bundle-box__add">
            <?php echo esc_html($add_label); ?>
        </button>
    </form>
</section>
<?php
// phpcs:enable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
