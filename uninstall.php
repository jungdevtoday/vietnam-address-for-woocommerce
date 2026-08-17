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

$wpdb->query(
    $wpdb->prepare(
        "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
        $wpdb->esc_like('_transient_vn_address_srv_') . '%',
        $wpdb->esc_like('_transient_timeout_vn_address_srv_') . '%'
    )
);

$timestamp = wp_next_scheduled('vn_address_wc_warm_cache');
if ($timestamp) {
    wp_unschedule_event($timestamp, 'vn_address_wc_warm_cache');
}
