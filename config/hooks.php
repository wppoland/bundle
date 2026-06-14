<?php
/**
 * Boot order: services listed here are resolved from the container and have
 * their registerHooks() called during Plugin::boot(). Each must implement
 * Bundle\Contract\HasHooks.
 *
 * @package Bundle
 *
 * @return array<class-string>
 */

declare(strict_types=1);

use Bundle\Admin\ProductBundleBox;
use Bundle\Admin\Settings;
use Bundle\Service\BundleService;

defined('ABSPATH') || exit;

return [
    BundleService::class,
    ...(is_admin() ? [Settings::class, ProductBundleBox::class] : []),
];
