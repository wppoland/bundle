<?php
/**
 * Constants needed by PHPStan to analyse the plugin without bootstrapping WordPress.
 *
 * @package Bundle
 */

declare(strict_types=1);

namespace {
    if (! defined('ABSPATH')) {
        define('ABSPATH', '/tmp/wordpress/');
    }
    if (! defined('BUNDLE_DIR')) {
        define('BUNDLE_DIR', '/tmp/bundle/');
    }
    if (! defined('BUNDLE_URL')) {
        define('BUNDLE_URL', 'https://example.test/wp-content/plugins/bundle/');
    }
}

namespace Bundle {
    if (! defined('Bundle\\VERSION')) {
        define('Bundle\\VERSION', '0.1.0');
    }
    if (! defined('Bundle\\PLUGIN_FILE')) {
        define('Bundle\\PLUGIN_FILE', '/tmp/bundle/bundle.php');
    }
}
