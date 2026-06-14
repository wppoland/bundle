<?php
/**
 * Service wiring. Returns a closure that registers every service in the
 * container. Keep services thin; product logic lives in storefront-kit engines
 * instantiated here with this plugin's text-domain / option prefix / asset URLs.
 *
 * @package Bundle
 */

declare(strict_types=1);

use Bundle\Admin\ProductBundleBox;
use Bundle\Admin\Settings;
use Bundle\Container;
use Bundle\Migrator;
use Bundle\Service\BundleService;

defined('ABSPATH') || exit;

return static function (Container $c): void {
    $c->singleton(Migrator::class, static fn (): Migrator => new Migrator());

    // Thin adapter over the storefront-kit ProductBundleEngine.
    $c->singleton(BundleService::class, static fn (): BundleService => new BundleService());

    // Admin (only needed in wp-admin context).
    if (is_admin()) {
        $c->singleton(Settings::class, static fn (): Settings => new Settings());
        $c->singleton(ProductBundleBox::class, static fn (): ProductBundleBox => new ProductBundleBox());
    }
};
