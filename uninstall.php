<?php
/**
 * Uninstall cleanup for Bundle – Product Bundles for WooCommerce.
 *
 * Runs only when the plugin is deleted from the Plugins screen. Removes the
 * settings option, the migration version marker and every per-product bundle
 * definition meta row. No custom tables are created, so none are dropped.
 *
 * @package Bundle
 */

declare(strict_types=1);

defined('WP_UNINSTALL_PLUGIN') || exit;

// Settings + migration marker.
delete_option('bundle_settings');
delete_option('bundle_db_version');

// Per-product bundle definitions, stored as the `_bundle_definition` post meta.
// delete_post_meta_by_key() removes every row for the key in one call; this is
// the canonical WP helper, so no direct $wpdb query is needed.
if (function_exists('delete_post_meta_by_key')) {
    delete_post_meta_by_key('_bundle_definition');
}
