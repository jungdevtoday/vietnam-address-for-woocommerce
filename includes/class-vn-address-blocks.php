<?php
/**
 * WooCommerce Block Checkout support.
 *
 * Registers Vietnamese province/ward fields via the Additional Checkout Fields
 * API (WooCommerce 8.9+) so they appear in the block-based Checkout, and mirrors
 * submitted values into the same order-meta keys the Classic Checkout uses so
 * the rest of the plugin (admin order display, address formatting) works the
 * same way regardless of which checkout a customer used.
 *
 * Scope: the new (post 1/7/2025) 2-level Province -> Ward structure only. The
 * legacy 3-level (Province -> District -> Ward) structure remains available
 * through the Classic Checkout.
 */

if (!defined('ABSPATH')) {
    exit;
}

class VN_Address_Blocks {

    private static $instance = null;

    const FIELD_PROVINCE = 'vn-address/province';
    const FIELD_WARD = 'vn-address/ward';
    const FIELD_WARD_CODE = 'vn-address/ward-code';

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('woocommerce_init', array($this, 'register_fields'));
        add_action('woocommerce_blocks_validate_location_address_fields', array($this, 'validate_address_fields'), 10, 2);
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('woocommerce_store_api_checkout_order_processed', array($this, 'normalize_order_meta'));
        add_filter('woocommerce_get_country_locale', array($this, 'adjust_vn_locale_fields'));
        add_action('wp_ajax_vn_address_get_wards_bulk', array($this, 'ajax_get_wards_bulk'));
        add_action('wp_ajax_nopriv_vn_address_get_wards_bulk', array($this, 'ajax_get_wards_bulk'));
    }

    /**
     * Serves the full new-structure ward list (grouped by province) for the
     * Block Checkout autocomplete. Goes through VN_Address_Data so a
     * configured central server (if any) is used here too, not just for
     * Classic Checkout.
     */
    public function ajax_get_wards_bulk() {
        check_ajax_referer('vn_address_nonce', 'nonce');
        wp_send_json_success(VN_Address_Data::get_instance()->get_wards_new_bulk());
    }

    /**
     * Hide WooCommerce's native city/state/postcode fields: our Province/Ward
     * fields replace them, same as the Classic Checkout does.
     *
     * Applied to every country's locale rules, not just 'VN'. This filter is
     * keyed by whatever country happens to be selected in the Country/Region
     * field, and this plugin has no way to guarantee that's actually 'VN'
     * (a fresh WooCommerce store defaults to its own base country, e.g. the
     * US) - scoping the override to 'VN' only meant these fields stayed
     * fully visible, and visibly overlapped our own Province/Ward fields,
     * on any store whose selected country locale wasn't Vietnam.
     */
    public function adjust_vn_locale_fields($locales) {
        foreach (array_keys($locales) as $country_code) {
            foreach (array('city', 'state', 'postcode') as $field) {
                $locales[$country_code][$field] = array_merge(
                    isset($locales[$country_code][$field]) ? $locales[$country_code][$field] : array(),
                    array('required' => false, 'hidden' => true)
                );
            }
        }

        return $locales;
    }

    public function is_supported() {
        return function_exists('woocommerce_register_additional_checkout_field');
    }

    public function register_fields() {
        if (!$this->is_supported()) {
            return;
        }

        $data = VN_Address_Data::get_instance();

        $province_options = array();
        foreach ($data->get_provinces_new() as $province) {
            $province_options[] = array(
                'value' => $province['code'],
                'label' => trim($province['type'] . ' ' . $province['name']),
            );
        }

        woocommerce_register_additional_checkout_field(array(
            'id' => self::FIELD_PROVINCE,
            'label' => __('Province/City', 'vietnam-address-for-woocommerce'),
            'location' => 'address',
            'type' => 'select',
            'required' => true,
            'options' => $province_options,
        ));

        woocommerce_register_additional_checkout_field(array(
            'id' => self::FIELD_WARD,
            'label' => __('Ward/Commune', 'vietnam-address-for-woocommerce'),
            'location' => 'address',
            'type' => 'text',
            'required' => true,
        ));

        woocommerce_register_additional_checkout_field(array(
            'id' => self::FIELD_WARD_CODE,
            'label' => __('Ward code', 'vietnam-address-for-woocommerce'),
            'location' => 'address',
            'type' => 'text',
            'required' => true,
        ));
    }

    public function validate_address_fields(\WP_Error $errors, $fields) {
        if (!isset($fields[self::FIELD_PROVINCE]) || !isset($fields[self::FIELD_WARD_CODE])) {
            return;
        }

        $province_code = $fields[self::FIELD_PROVINCE];
        $ward_code = $fields[self::FIELD_WARD_CODE];

        if (empty($province_code) || empty($ward_code)) {
            return;
        }

        $data = VN_Address_Data::get_instance();
        $ward_name = $data->get_ward_new_name($province_code, $ward_code);

        if (empty($ward_name)) {
            $errors->add(
                'vn_address_invalid_ward',
                __('Please select a valid Ward/Commune from the list for the chosen Province/City.', 'vietnam-address-for-woocommerce')
            );
        }
    }

    public function enqueue_scripts() {
        if (!$this->is_supported() || !$this->is_using_block_checkout() || !(is_checkout() || is_account_page())) {
            return;
        }

        wp_enqueue_style(
            'vn-address-checkout-css',
            VN_ADDRESS_WC_PLUGIN_URL . 'assets/css/checkout.css',
            array(),
            VN_ADDRESS_WC_VERSION
        );

        wp_enqueue_script(
            'vn-address-checkout-blocks-js',
            VN_ADDRESS_WC_PLUGIN_URL . 'assets/js/checkout-blocks.js',
            array('wp-data'),
            VN_ADDRESS_WC_VERSION,
            true
        );

        wp_localize_script('vn-address-checkout-blocks-js', 'vnAddressBlocks', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('vn_address_nonce'),
            'fieldIds' => array(
                'province' => self::FIELD_PROVINCE,
                'ward' => self::FIELD_WARD,
                'wardCode' => self::FIELD_WARD_CODE,
            ),
            'i18n' => array(
                'selectProvinceFirst' => __('Select a Province/City first', 'vietnam-address-for-woocommerce'),
                'noResults' => __('No matches found', 'vietnam-address-for-woocommerce'),
            ),
        ));
    }

    public function is_using_block_checkout() {
        if (!function_exists('wc_get_page_id') || !function_exists('has_block')) {
            return false;
        }

        $checkout_page_id = wc_get_page_id('checkout');

        return $checkout_page_id > 0 && has_block('woocommerce/checkout', $checkout_page_id);
    }

    /**
     * Mirror the block-checkout additional field values into the same order
     * meta keys the Classic Checkout uses, for both billing and shipping.
     */
    public function normalize_order_meta($order) {
        if (!$order instanceof WC_Order) {
            return;
        }

        $data = VN_Address_Data::get_instance();
        $changed = false;

        foreach (array('billing', 'shipping') as $group) {
            $province_code = $order->get_meta('_wc_' . $group . '/' . self::FIELD_PROVINCE, true);
            $ward_code = $order->get_meta('_wc_' . $group . '/' . self::FIELD_WARD_CODE, true);
            $ward_name = $order->get_meta('_wc_' . $group . '/' . self::FIELD_WARD, true);

            if (empty($province_code)) {
                continue;
            }

            $province_name = $data->get_province_new_name($province_code);

            $order->update_meta_data('_' . $group . '_province', $province_code);
            $order->update_meta_data('_' . $group . '_province_name', $province_name);

            if (!empty($ward_code)) {
                $order->update_meta_data('_' . $group . '_ward', $ward_code);
                $order->update_meta_data('_' . $group . '_ward_name', $ward_name);
            }

            $order->update_meta_data('_address_structure', 'new');

            $changed = true;
        }

        if ($changed) {
            $order->save();
        }
    }
}
