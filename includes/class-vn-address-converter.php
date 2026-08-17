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
        }
    }

    /**
     * Convert all eligible orders (old structure, not yet converted).
     */
    public function convert_all_orders() {
        $args = array(
            'limit' => -1,
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

        $orders = wc_get_orders($args);

        if (empty($orders)) {
            return array(
                'success' => true,
                'message' => __('No orders to convert', 'vn-address-for-woocommerce'),
                'converted' => 0,
                'ambiguous' => 0,
                'failed' => 0,
            );
        }

        $converted = 0;
        $ambiguous = 0;
        $failed = 0;
        $errors = array();

        foreach ($orders as $order) {
            $order_id = $order->get_id();
            $ward_code = $order->get_meta('_billing_ward', true);

            if (empty($ward_code)) {
                $failed++;
                continue;
            }

            $resolution = $this->resolve_new_ward($ward_code);
            $this->apply_resolution($order, $resolution);

            if ($resolution['status'] === 'matched') {
                $order->save();
                $converted++;
            } elseif ($resolution['status'] === 'ambiguous') {
                $order->save();
                $ambiguous++;
                $errors[] = sprintf(
                    /* translators: %d: order ID. */
                    __('Order #%d: multiple possible new wards, needs manual review', 'vn-address-for-woocommerce'),
                    $order_id
                );
            } else {
                $failed++;
                $errors[] = sprintf(
                    /* translators: %d: order ID. */
                    __('Order #%d: no matching ward found in the conversion table', 'vn-address-for-woocommerce'),
                    $order_id
                );
            }
        }

        return array(
            'success' => true,
            'message' => sprintf(
                /* translators: 1: number converted, 2: number needing manual review, 3: number failed. */
                __('Conversion completed. Converted: %1$d, Needs review: %2$d, Failed: %3$d', 'vn-address-for-woocommerce'),
                $converted,
                $ambiguous,
                $failed
            ),
            'converted' => $converted,
            'ambiguous' => $ambiguous,
            'failed' => $failed,
            'errors' => $errors,
        );
    }

    /**
     * Convert a single order.
     */
    public function convert_order($order_id) {
        $order = wc_get_order($order_id);

        if (!$order) {
            return array(
                'success' => false,
                'message' => __('Order not found', 'vn-address-for-woocommerce'),
            );
        }

        $ward_code = $order->get_meta('_billing_ward', true);

        if (empty($ward_code)) {
            return array(
                'success' => false,
                'message' => __('Missing address data', 'vn-address-for-woocommerce'),
            );
        }

        $resolution = $this->resolve_new_ward($ward_code);
        $this->apply_resolution($order, $resolution);

        if ($resolution['status'] === 'not_found') {
            return array(
                'success' => false,
                'message' => __('No matching ward found in the conversion table', 'vn-address-for-woocommerce'),
            );
        }

        $order->save();

        if ($resolution['status'] === 'ambiguous') {
            return array(
                'success' => true,
                'status' => 'ambiguous',
                'message' => __('Multiple possible new wards found; needs manual review', 'vn-address-for-woocommerce'),
                'candidates' => $resolution['candidates'],
            );
        }

        return array(
            'success' => true,
            'status' => 'matched',
            'message' => __('Order converted successfully', 'vn-address-for-woocommerce'),
            'data' => $resolution['data'],
        );
    }

    /**
     * Convert a batch of orders by ID.
     */
    public function batch_convert_orders($order_ids) {
        $converted = 0;
        $ambiguous = 0;
        $failed = 0;

        foreach ($order_ids as $order_id) {
            $order = wc_get_order($order_id);

            if (!$order) {
                $failed++;
                continue;
            }

            $ward_code = $order->get_meta('_billing_ward', true);

            if (empty($ward_code)) {
                $failed++;
                continue;
            }

            $resolution = $this->resolve_new_ward($ward_code);
            $this->apply_resolution($order, $resolution);
            $order->save();

            if ($resolution['status'] === 'matched') {
                $converted++;
            } elseif ($resolution['status'] === 'ambiguous') {
                $ambiguous++;
            } else {
                $failed++;
            }
        }

        if ($converted === 0 && $ambiguous === 0 && $failed === 0) {
            return array(
                'success' => false,
                'message' => __('No valid orders to convert', 'vn-address-for-woocommerce'),
            );
        }

        return array(
            'success' => true,
            'converted' => $converted,
            'ambiguous' => $ambiguous,
            'failed' => $failed,
        );
    }
}
