<?php
/**
 * Checkout Integration Class
 */

if (!defined('ABSPATH')) {
    exit;
}

class VN_Address_Checkout {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        // Enqueue scripts
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        
        // Modify checkout fields
        add_filter('woocommerce_checkout_fields', array($this, 'modify_checkout_fields'));
        add_filter('woocommerce_billing_fields', array($this, 'modify_billing_fields'));
        add_filter('woocommerce_shipping_fields', array($this, 'modify_shipping_fields'));
        
        // Save custom fields
        add_action('woocommerce_checkout_update_order_meta', array($this, 'save_custom_fields'));
        
        // Display custom fields in order
        add_action('woocommerce_admin_order_data_after_billing_address', array($this, 'display_custom_fields_in_admin'), 10, 1);
        add_action('woocommerce_order_details_after_customer_details', array($this, 'display_custom_fields_in_frontend'), 10, 1);
        
        // Format address
        add_filter('woocommerce_order_formatted_billing_address', array($this, 'format_billing_address'), 10, 2);
        add_filter('woocommerce_order_formatted_shipping_address', array($this, 'format_shipping_address'), 10, 2);
    }
    
    public function enqueue_scripts() {
        if (is_checkout() || is_account_page()) {
            wp_enqueue_style(
                'vn-address-checkout-css',
                VN_ADDRESS_WC_PLUGIN_URL . 'assets/css/checkout.css',
                array(),
                VN_ADDRESS_WC_VERSION
            );
            
            // 'selectWoo' is WooCommerce's own bundled fork of Select2 (renamed
            // to avoid clashing with a theme's or another plugin's own Select2
            // copy). WooCommerce already enqueues it unconditionally on cart,
            // checkout, and account pages, so depending on it here adds no
            // extra payload - it's already loading either way.
            wp_enqueue_script(
                'vn-address-checkout-js',
                VN_ADDRESS_WC_PLUGIN_URL . 'assets/js/checkout.js',
                array('jquery', 'selectWoo'),
                VN_ADDRESS_WC_VERSION,
                true
            );
            
            wp_localize_script('vn-address-checkout-js', 'vnAddress', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('vn_address_nonce'),
                'structure' => get_option('vn_address_wc_structure', 'new'),
                'enableSelect2' => 'yes' === get_option('vn_address_wc_enable_select2', 'yes'),
                'i18n' => array(
                    'select_province' => __('Select Province/City', 'vn-address-for-woocommerce'),
                    'select_district' => __('Select District', 'vn-address-for-woocommerce'),
                    'select_ward' => __('Select Ward', 'vn-address-for-woocommerce'),
                    'required' => __('required', 'vn-address-for-woocommerce'),
                ),
            ));
        }
    }
    
    public function modify_checkout_fields($fields) {
        // Get structure from POST or use default from settings. This only
        // affects which fields are rendered for this request (old vs new
        // structure); it never writes anything, so no nonce is needed here.
        $default_structure = get_option('vn_address_wc_structure', 'new');
        // phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.MissingUnslash
        $structure = isset($_POST['address_structure']) ? sanitize_text_field(wp_unslash($_POST['address_structure'])) : $default_structure;
        
        // Remove unnecessary default fields
        $fields_to_remove = array(
            'billing_company', 
            'billing_address_2', 
            'billing_postcode', 
            'billing_state', 
            'billing_city',
            'billing_country' // Hide country field
        );
        foreach ($fields_to_remove as $field) {
            if (isset($fields['billing'][$field])) {
                unset($fields['billing'][$field]);
            }
        }
        
        $fields_to_remove_shipping = array(
            'shipping_company', 
            'shipping_address_2', 
            'shipping_postcode', 
            'shipping_state', 
            'shipping_city',
            'shipping_country' // Hide country field
        );
        foreach ($fields_to_remove_shipping as $field) {
            if (isset($fields['shipping'][$field])) {
                unset($fields['shipping'][$field]);
            }
        }
        
        // Update existing field labels and classes
        if (isset($fields['billing']['billing_first_name'])) {
            $fields['billing']['billing_first_name']['label'] = __('Your Name', 'vn-address-for-woocommerce');
            $fields['billing']['billing_first_name']['placeholder'] = esc_attr__('Enter your full name', 'vn-address-for-woocommerce');
            $fields['billing']['billing_first_name']['class'] = array('form-row-wide');
            $fields['billing']['billing_first_name']['priority'] = 10;
        }

        // Remove last name field
        if (isset($fields['billing']['billing_last_name'])) {
            unset($fields['billing']['billing_last_name']);
        }

        if (isset($fields['billing']['billing_phone'])) {
            $fields['billing']['billing_phone']['label'] = __('Phone Number', 'vn-address-for-woocommerce');
            $fields['billing']['billing_phone']['placeholder'] = esc_attr__('Enter your phone number', 'vn-address-for-woocommerce');
            $fields['billing']['billing_phone']['class'] = array('form-row-first');
            $fields['billing']['billing_phone']['priority'] = 20;
        }

        if (isset($fields['billing']['billing_email'])) {
            $fields['billing']['billing_email']['label'] = __('Email', 'vn-address-for-woocommerce');
            $fields['billing']['billing_email']['placeholder'] = esc_attr__('Enter your email address', 'vn-address-for-woocommerce');
            $fields['billing']['billing_email']['class'] = array('form-row-last');
            $fields['billing']['billing_email']['priority'] = 30;
        }

        // Add structure selector
        $fields['billing']['address_structure'] = array(
            'type' => 'select',
            'label' => __('Choose Address Entry Method', 'vn-address-for-woocommerce'),
            'required' => false,
            'class' => array('form-row-wide', 'vn-address-structure-selector'),
            'priority' => 35,
            'default' => $default_structure,
            'options' => array(
                'new' => __('New address (Province/City → Ward)', 'vn-address-for-woocommerce'),
                'old' => __('Old address (Province/City → District → Ward)', 'vn-address-for-woocommerce'),
            ),
        );

        // Billing fields
        if (isset($fields['billing'])) {
            // Province
            $fields['billing']['billing_province'] = array(
                'type' => 'select',
                'label' => __('Province/City', 'vn-address-for-woocommerce'),
                'required' => true,
                'class' => array('form-row-first', 'vn-address-province', 'update_totals_on_change'),
                'priority' => 40,
                'options' => array('' => __('Select Province/City', 'vn-address-for-woocommerce')),
            );

            // District (for old structure)
            $fields['billing']['billing_district'] = array(
                'type' => 'select',
                'label' => __('District', 'vn-address-for-woocommerce'),
                'required' => false, // Will be set to true via JS when old structure is selected
                'class' => array('form-row-last', 'vn-address-district', 'vn-address-old-only', 'update_totals_on_change'),
                'priority' => 50,
                'options' => array('' => __('Select District', 'vn-address-for-woocommerce')),
            );

            // Ward
            $fields['billing']['billing_ward'] = array(
                'type' => 'select',
                'label' => __('Ward', 'vn-address-for-woocommerce'),
                'required' => true,
                'class' => array('form-row-last', 'vn-address-ward', 'update_totals_on_change'),
                'priority' => 60,
                'options' => array('' => __('Select Ward', 'vn-address-for-woocommerce')),
            );

            // Address 1
            if (isset($fields['billing']['billing_address_1'])) {
                $fields['billing']['billing_address_1']['label'] = __('Detailed Address', 'vn-address-for-woocommerce');
                $fields['billing']['billing_address_1']['placeholder'] = esc_attr__('House number, street name...', 'vn-address-for-woocommerce');
                $fields['billing']['billing_address_1']['priority'] = 70;
            }
        }
        
        // Shipping fields (same structure)
        if (isset($fields['shipping'])) {
            if (isset($fields['shipping']['shipping_first_name'])) {
                unset($fields['shipping']['shipping_first_name']);
            }
            if (isset($fields['shipping']['shipping_last_name'])) {
                unset($fields['shipping']['shipping_last_name']);
            }
            
            $fields['shipping']['shipping_province'] = array(
                'type' => 'select',
                'label' => __('Province/City', 'vn-address-for-woocommerce'),
                'required' => true,
                'class' => array('form-row-first', 'vn-address-province', 'update_totals_on_change'),
                'priority' => 40,
                'options' => array('' => __('Select Province/City', 'vn-address-for-woocommerce')),
            );

            $fields['shipping']['shipping_district'] = array(
                'type' => 'select',
                'label' => __('District', 'vn-address-for-woocommerce'),
                'required' => false, // Will be set to true via JS when old structure is selected
                'class' => array('form-row-last', 'vn-address-district', 'vn-address-old-only', 'update_totals_on_change'),
                'priority' => 50,
                'options' => array('' => __('Select District', 'vn-address-for-woocommerce')),
            );

            $fields['shipping']['shipping_ward'] = array(
                'type' => 'select',
                'label' => __('Ward', 'vn-address-for-woocommerce'),
                'required' => true,
                'class' => array('form-row-last', 'vn-address-ward', 'update_totals_on_change'),
                'priority' => 60,
                'options' => array('' => __('Select Ward', 'vn-address-for-woocommerce')),
            );

            if (isset($fields['shipping']['shipping_address_1'])) {
                $fields['shipping']['shipping_address_1']['label'] = __('Detailed Address', 'vn-address-for-woocommerce');
                $fields['shipping']['shipping_address_1']['placeholder'] = esc_attr__('House number, street name...', 'vn-address-for-woocommerce');
                $fields['shipping']['shipping_address_1']['priority'] = 70;
            }
        }

        // Order notes
        if (isset($fields['order']['order_comments'])) {
            $fields['order']['order_comments']['label'] = __('Order Notes', 'vn-address-for-woocommerce');
            $fields['order']['order_comments']['placeholder'] = esc_attr__('Notes about your order, e.g. delivery time or more detailed delivery instructions.', 'vn-address-for-woocommerce');
        }
        
        return $fields;
    }
    
    public function modify_billing_fields($fields) {
        // Change First Name label
        if (isset($fields['billing_first_name'])) {
            $fields['billing_first_name']['label'] = __('Your Name', 'vn-address-for-woocommerce');
        }
        return $fields;
    }
    
    public function modify_shipping_fields($fields) {
        return $fields;
    }
    
    /**
     * Hooked to woocommerce_checkout_update_order_meta, which only fires
     * from inside WC_Checkout::process_checkout() after WooCommerce has
     * already verified its own "woocommerce-process-checkout" nonce - so
     * this deliberately doesn't duplicate that check.
     */
    public function save_custom_fields($order_id) {
        $order = wc_get_order($order_id);
        if (!$order) {
            return;
        }

        // phpcs:disable WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.MissingUnslash
        $structure = isset($_POST['address_structure']) ? sanitize_text_field(wp_unslash($_POST['address_structure'])) : get_option('vn_address_wc_structure', 'new');

        // Save structure used
        $order->update_meta_data('_address_structure', $structure);

        // Billing address
        if (isset($_POST['billing_province'])) {
            $order->update_meta_data('_billing_province', sanitize_text_field(wp_unslash($_POST['billing_province'])));
            $order->update_meta_data('_billing_province_name', sanitize_text_field(wp_unslash($_POST['billing_province_text'] ?? '')));
        }

        if ($structure === 'old' && isset($_POST['billing_district'])) {
            $order->update_meta_data('_billing_district', sanitize_text_field(wp_unslash($_POST['billing_district'])));
            $order->update_meta_data('_billing_district_name', sanitize_text_field(wp_unslash($_POST['billing_district_text'] ?? '')));
        }

        if (isset($_POST['billing_ward'])) {
            $order->update_meta_data('_billing_ward', sanitize_text_field(wp_unslash($_POST['billing_ward'])));
            $order->update_meta_data('_billing_ward_name', sanitize_text_field(wp_unslash($_POST['billing_ward_text'] ?? '')));
        }

        // Shipping address
        if (isset($_POST['shipping_province'])) {
            $order->update_meta_data('_shipping_province', sanitize_text_field(wp_unslash($_POST['shipping_province'])));
            $order->update_meta_data('_shipping_province_name', sanitize_text_field(wp_unslash($_POST['shipping_province_text'] ?? '')));
        }

        if ($structure === 'old' && isset($_POST['shipping_district'])) {
            $order->update_meta_data('_shipping_district', sanitize_text_field(wp_unslash($_POST['shipping_district'])));
            $order->update_meta_data('_shipping_district_name', sanitize_text_field(wp_unslash($_POST['shipping_district_text'] ?? '')));
        }

        if (isset($_POST['shipping_ward'])) {
            $order->update_meta_data('_shipping_ward', sanitize_text_field(wp_unslash($_POST['shipping_ward'])));
            $order->update_meta_data('_shipping_ward_name', sanitize_text_field(wp_unslash($_POST['shipping_ward_text'] ?? '')));
        }
        // phpcs:enable WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.MissingUnslash

        $order->save();
    }

    public function display_custom_fields_in_admin($order) {
        echo '<div class="vn-address-order-meta">';
        echo '<h3>' . esc_html__('Vietnamese Address Information', 'vn-address-for-woocommerce') . '</h3>';

        // Billing
        echo '<h4>' . esc_html__('Billing Address', 'vn-address-for-woocommerce') . '</h4>';
        echo '<p>';
        if ($province_name = $order->get_meta('_billing_province_name', true)) {
            echo '<strong>' . esc_html__('Province/City:', 'vn-address-for-woocommerce') . '</strong> ' . esc_html($province_name) . '<br>';
        }
        if ($ward_name = $order->get_meta('_billing_ward_name', true)) {
            echo '<strong>' . esc_html__('Ward:', 'vn-address-for-woocommerce') . '</strong> ' . esc_html($ward_name);
        }
        echo '</p>';

        // Shipping
        if ($order->has_shipping_address()) {
            echo '<h4>' . esc_html__('Shipping Address', 'vn-address-for-woocommerce') . '</h4>';
            echo '<p>';
            if ($province_name = $order->get_meta('_shipping_province_name', true)) {
                echo '<strong>' . esc_html__('Province/City:', 'vn-address-for-woocommerce') . '</strong> ' . esc_html($province_name) . '<br>';
            }
            if ($ward_name = $order->get_meta('_shipping_ward_name', true)) {
                echo '<strong>' . esc_html__('Ward:', 'vn-address-for-woocommerce') . '</strong> ' . esc_html($ward_name);
            }
            echo '</p>';
        }

        echo '</div>';
    }

    public function display_custom_fields_in_frontend($order) {
        $structure = get_option('vn_address_wc_structure', 'new');

        if ($province_name = $order->get_meta('_billing_province_name', true)) {
            echo '<p class="vn-address-province">' . esc_html($province_name) . '</p>';
        }
        if ($structure === 'old' && $district_name = $order->get_meta('_billing_district_name', true)) {
            echo '<p class="vn-address-district">' . esc_html($district_name) . '</p>';
        }
        if ($ward_name = $order->get_meta('_billing_ward_name', true)) {
            echo '<p class="vn-address-ward">' . esc_html($ward_name) . '</p>';
        }
    }

    public function format_billing_address($address, $order) {
        if ($province_name = $order->get_meta('_billing_province_name', true)) {
            $address['state'] = $province_name;
        }
        if ($ward_name = $order->get_meta('_billing_ward_name', true)) {
            $address['city'] = $ward_name;
        }

        return $address;
    }

    public function format_shipping_address($address, $order) {
        if ($province_name = $order->get_meta('_shipping_province_name', true)) {
            $address['state'] = $province_name;
        }
        if ($ward_name = $order->get_meta('_shipping_ward_name', true)) {
            $address['city'] = $ward_name;
        }

        return $address;
    }
}