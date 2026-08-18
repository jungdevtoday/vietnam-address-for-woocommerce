<?php
/**
 * Address Converter Class
 *
 * Converts order addresses from the old (pre 1/7/2025) 3-level structure to
 * the new (post 1/7/2025) 2-level structure by looking each ward up in the
 * bundled old-to-new mapping table (assets/data/ward-mapping-old-to-new.json,
 * built from VietMap's published mapping spreadsheet - not a live API call
 * of any kind, and never goes over the network even if a central data
 * server is configured, since this is a bulk batch job that may touch
 * hundreds of distinct wards in a single run).
 *
 * About 97% of old wards map to exactly one new ward and are converted
 * automatically; the remaining ~3% were split across multiple new wards
 * during the merger and are flagged for manual review rather than guessed
 * at. The original address fields are never modified or deleted - the
 * converted result is written to separate "_new"-suffixed meta keys, so the
 * source data a conversion was based on is always still there.
 */

if (!defined('ABSPATH')) {
    exit;
}

class VN_Address_Converter {

    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        // Constructor
    }

    /**
     * Look up the new-structure ward for an old-structure ward code.
     *
     * @return array{status: string, data?: array, candidates?: array}
     */
    private function resolve_new_ward($ward_code) {
        // Always local, never over the network - see get_ward_mapping_local()
        // for why: this runs as a bulk batch job, not a single lookup.
        $candidates = VN_Address_Data::get_instance()->get_ward_mapping_local($ward_code);

        if (empty($candidates)) {
            return array('status' => 'not_found');
        }

        if (count($candidates) > 1) {
            return array('status' => 'ambiguous', 'candidates' => $candidates);
        }

        return array('status' => 'matched', 'data' => $candidates[0]);
    }

    /**
     * Apply a resolved conversion result to an order's meta. Does not save().
     *
     * Every branch, including "not found", writes _converted_to_new_structure
     * so the order stops matching the NOT-EXISTS eligibility query used by
     * convert_batch(). Without a terminal state for unresolvable orders,
     * repeated batches would keep re-selecting the same failed orders forever.
     */
    private function apply_resolution($order, $resolution) {
        if ($resolution['status'] === 'matched') {
            $new_data = $resolution['data'];
            $order->update_meta_data('_billing_province_new', $new_data['province_code']);
            $order->update_meta_data('_billing_province_name_new', $new_data['province_name']);
            $order->update_meta_data('_billing_ward_new', $new_data['ward_code']);
            $order->update_meta_data('_billing_ward_name_new', $new_data['ward_name']);
            $order->update_meta_data('_converted_to_new_structure', 'yes');
            $order->update_meta_data('_conversion_date', current_time('mysql'));
            $order->delete_meta_data('_billing_ward_candidates_new');
        } elseif ($resolution['status'] === 'ambiguous') {
            $order->update_meta_data('_converted_to_new_structure', 'ambiguous');
            $order->update_meta_data('_conversion_date', current_time('mysql'));
            $order->update_meta_data('_billing_ward_candidates_new', wp_json_encode($resolution['candidates']));
        } else {
            $order->update_meta_data('_converted_to_new_structure', 'failed');
            $order->update_meta_data('_conversion_date', current_time('mysql'));
        }
    }

    /**
     * Shared WC_Order_Query args identifying orders still eligible for
     * conversion: old-structure billing address, not yet resolved.
     */
    private function eligible_orders_query_args() {
        return array(
            'status' => array('any'),
            'meta_query' => array(
                array(
                    'key' => '_billing_province',
                    'compare' => 'EXISTS',
                ),
                array(
                    'key' => '_billing_district',
                    'compare' => 'EXISTS',
                ),
                array(
                    'key' => '_converted_to_new_structure',
                    'compare' => 'NOT EXISTS',
                ),
            ),
        );
    }

    /**
     * Count orders still eligible for conversion, without loading full order
     * objects (used to size the progress bar before batching starts).
     */
    public function count_eligible_orders() {
        $ids = wc_get_orders(array_merge($this->eligible_orders_query_args(), array(
            'limit' => -1,
            'return' => 'ids',
        )));

        return count($ids);
    }

    /**
     * Convert one bounded batch of eligible orders.
     *
     * Called repeatedly (once per AJAX round-trip) until 'remaining' reaches
     * 0. Bounding each call to $batch_size orders keeps a single request's
     * time and memory use small and predictable regardless of how many
     * orders a store has in total - a store with 50,000 old-structure orders
     * is handled the same way as one with 5, just over more requests.
     */
    public function convert_batch($batch_size = 50) {
        $orders = wc_get_orders(array_merge($this->eligible_orders_query_args(), array(
            'limit' => $batch_size,
        )));

        $converted = 0;
        $ambiguous = 0;
        $failed = 0;
        $errors = array();

        foreach ($orders as $order) {
            $order_id = $order->get_id();
            $ward_code = $order->get_meta('_billing_ward', true);

            if (empty($ward_code)) {
                $order->update_meta_data('_converted_to_new_structure', 'failed');
                $order->update_meta_data('_conversion_date', current_time('mysql'));
                $order->save();
                $failed++;
                $errors[] = sprintf(
                    /* translators: %d: order ID. */
                    __('Order #%d: missing address data', 'vietnam-address-for-woocommerce'),
                    $order_id
                );
                continue;
            }

            $resolution = $this->resolve_new_ward($ward_code);
            $this->apply_resolution($order, $resolution);
            $order->save();

            if ($resolution['status'] === 'matched') {
                $converted++;
            } elseif ($resolution['status'] === 'ambiguous') {
                $ambiguous++;
                $errors[] = sprintf(
                    /* translators: %d: order ID. */
                    __('Order #%d: multiple possible new wards, needs manual review', 'vietnam-address-for-woocommerce'),
                    $order_id
                );
            } else {
                $failed++;
                $errors[] = sprintf(
                    /* translators: %d: order ID. */
                    __('Order #%d: no matching ward found in the conversion table', 'vietnam-address-for-woocommerce'),
                    $order_id
                );
            }
        }

        return array(
            'processed' => count($orders),
            'converted' => $converted,
            'ambiguous' => $ambiguous,
            'failed' => $failed,
            'errors' => $errors,
            // A batch full to $batch_size means more orders might remain;
            // a partial batch means the eligible set is exhausted. Avoids
            // an extra COUNT-style query on every single batch call.
            'has_more' => count($orders) === $batch_size,
        );
    }

}
