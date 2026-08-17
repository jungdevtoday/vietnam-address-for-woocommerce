<?php
/**
 * Uninstall handler.
 *
 * Removes plugin options, cached transients, and any pending cron event.
 * Order meta written by the converter (_billing_*_new, etc.) is left in
 * place, since it is order data the store owner may still want to keep.
 */

if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

global $wpdb;

delete_option('vn_address_wc_structure');
delete_option('vn_address_wc_server_url');

// Bulk-deleting transients by name pattern has no dedicated WP function;
// this is the standard accepted pattern for it. Not cached since it's a
// one-time delete, not a repeated read.
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
$wpdb->query(
    $wpdb->prepare(
        "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
        $wpdb->esc_like('_transient_vn_address_srv_') . '%',
        $wpdb->esc_like('_transient_timeout_vn_address_srv_') . '%'
    )
);

$vn_address_wc_next_warmup = wp_next_scheduled('vn_address_wc_warm_cache');
if ($vn_address_wc_next_warmup) {
    wp_unschedule_event($vn_address_wc_next_warmup, 'vn_address_wc_warm_cache');
}
