<?php
/**
 * Admin Settings Class
 */

if (!defined('ABSPATH')) {
    exit;
}

class VN_Address_Admin {

    /**
     * Orders processed per convert_batch() AJAX round-trip. Kept small so a
     * single request's execution time and memory footprint stay well under
     * typical hosting limits regardless of total order count.
     */
    const CONVERT_BATCH_SIZE = 50;

    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        add_action('admin_notices', array($this, 'maybe_show_block_checkout_notice'));
        add_action('wp_ajax_vn_address_convert_count', array($this, 'ajax_convert_count'));
        add_action('wp_ajax_vn_address_convert_batch', array($this, 'ajax_convert_batch'));
        add_action('wp_ajax_vn_address_test_server', array($this, 'ajax_test_server'));
        add_action('wp_ajax_vn_address_clear_server_cache', array($this, 'ajax_clear_server_cache'));
    }

    /**
     * The plugin's address fields are added via the classic (shortcode) checkout
     * field filters and only work on the Classic Checkout. WooCommerce's block-based
     * Checkout (the default for new stores) does not use those filters, so the
     * custom province/district/ward fields never appear there.
     */
    public function is_using_block_checkout() {
        if (!function_exists('wc_get_page_id') || !function_exists('has_block')) {
            return false;
        }

        $checkout_page_id = wc_get_page_id('checkout');

        return $checkout_page_id > 0 && has_block('woocommerce/checkout', $checkout_page_id);
    }

    public function maybe_show_block_checkout_notice() {
        if (!current_user_can('manage_woocommerce') || !$this->is_using_block_checkout()) {
            return;
        }

        if (class_exists('VN_Address_Blocks') && VN_Address_Blocks::get_instance()->is_supported()) {
            // Block Checkout is supported (WooCommerce 8.9+); no warning needed,
            // but the address fields there only cover the new (post 1/7/2025) structure.
            return;
        }

        ?>
        <div class="notice notice-warning">
            <p>
                <strong><?php esc_html_e('VN Address for WooCommerce', 'vn-address-for-woocommerce'); ?>:</strong>
                <?php
                printf(
                    /* translators: %s: link to WooCommerce Settings > Advanced > Features */
                    esc_html__('Your Checkout page uses the WooCommerce block-based checkout, and your WooCommerce version is too old for this plugin\'s block checkout support. Update WooCommerce to 8.9+, or enable "Cart and checkout shortcodes" under %s to use the Classic Checkout instead.', 'vn-address-for-woocommerce'),
                    '<a href="' . esc_url(admin_url('admin.php?page=wc-settings&tab=advanced&section=features')) . '">' . esc_html__('WooCommerce > Settings > Advanced > Features', 'vn-address-for-woocommerce') . '</a>'
                );
                ?>
            </p>
        </div>
        <?php
    }
    
    public function add_admin_menu() {
        add_submenu_page(
            'woocommerce',
            __('Vietnam Address for WooCommerce', 'vn-address-for-woocommerce'),
            __('Vietnam Address', 'vn-address-for-woocommerce'),
            'manage_woocommerce',
            'vn-address-settings',
            array($this, 'render_settings_page')
        );
    }
    
    public function register_settings() {
        register_setting('vn_address_wc_settings', 'vn_address_wc_structure', array(
            'sanitize_callback' => array($this, 'sanitize_structure'),
            'default' => 'new',
        ));
        register_setting('vn_address_wc_settings', 'vn_address_wc_server_url', array(
            'sanitize_callback' => 'esc_url_raw',
        ));
    }

    /**
     * Only 'new' and 'old' are valid values for this setting; anything else
     * (a tampered request, a stale form) falls back to 'new'.
     */
    public function sanitize_structure($value) {
        return in_array($value, array('new', 'old'), true) ? $value : 'new';
    }
    
    public function enqueue_admin_scripts($hook) {
        if ('woocommerce_page_vn-address-settings' !== $hook) {
            return;
        }
        
        wp_enqueue_style(
            'vn-address-admin-css',
            VN_ADDRESS_WC_PLUGIN_URL . 'assets/css/admin.css',
            array(),
            VN_ADDRESS_WC_VERSION
        );
        
        wp_enqueue_script(
            'vn-address-admin-js',
            VN_ADDRESS_WC_PLUGIN_URL . 'assets/js/admin.js',
            array('jquery'),
            VN_ADDRESS_WC_VERSION,
            true
        );
        
        wp_localize_script('vn-address-admin-js', 'vnAddressAdmin', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('vn_address_admin_nonce'),
            'i18n' => array(
                'convert_confirm' => __('This will convert all orders with old address structure. Continue?', 'vn-address-for-woocommerce'),
                'converting' => __('Converting...', 'vn-address-for-woocommerce'),
                'conversion_results' => __('Conversion Results:', 'vn-address-for-woocommerce'),
                'converted' => __('Converted:', 'vn-address-for-woocommerce'),
                'needs_review' => __('Needs review:', 'vn-address-for-woocommerce'),
                'failed' => __('Failed:', 'vn-address-for-woocommerce'),
                'errors' => __('Errors:', 'vn-address-for-woocommerce'),
                'conversion_failed' => __('Conversion failed. Please try again.', 'vn-address-for-woocommerce'),
                'no_orders_to_convert' => __('No orders to convert.', 'vn-address-for-woocommerce'),
                'convert_now' => __('Convert Now', 'vn-address-for-woocommerce'),
                'testing' => __('Testing...', 'vn-address-for-woocommerce'),
                'test_server' => __('Test Connection', 'vn-address-for-woocommerce'),
                'connection_error' => __('Connection error', 'vn-address-for-woocommerce'),
                'clear_cache_confirm' => __('Are you sure you want to clear the cached server data?', 'vn-address-for-woocommerce'),
                'clearing' => __('Clearing...', 'vn-address-for-woocommerce'),
                'clear_cache' => __('Clear Cache', 'vn-address-for-woocommerce'),
                'error_clearing_cache' => __('Error clearing cache', 'vn-address-for-woocommerce'),
            ),
        ));
    }
    
    public function render_settings_page() {
        ?>
        <div class="wrap vn-address-settings">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

            <?php if ($this->is_using_block_checkout() && !(class_exists('VN_Address_Blocks') && VN_Address_Blocks::get_instance()->is_supported())) : ?>
                <div class="notice notice-warning inline">
                    <p>
                        <?php
                        printf(
                            /* translators: %s: link to WooCommerce Settings > Advanced > Features */
                            esc_html__('Your Checkout page uses the WooCommerce block-based checkout, and your WooCommerce version is too old for this plugin\'s block checkout support. Update WooCommerce to 8.9+, or enable "Cart and checkout shortcodes" under %s.', 'vn-address-for-woocommerce'),
                            '<a href="' . esc_url(admin_url('admin.php?page=wc-settings&tab=advanced&section=features')) . '">' . esc_html__('WooCommerce > Settings > Advanced > Features', 'vn-address-for-woocommerce') . '</a>'
                        );
                        ?>
                    </p>
                </div>
            <?php elseif ($this->is_using_block_checkout()) : ?>
                <div class="notice notice-info inline">
                    <p>
                        <?php esc_html_e('Your Checkout page uses the WooCommerce block-based checkout. Customers will see Province/City and Ward/Commune fields for the current (post 1/7/2025) address structure. The legacy structure with District is only available on the Classic Checkout.', 'vn-address-for-woocommerce'); ?>
                    </p>
                </div>
            <?php endif; ?>

            <div class="vn-address-container">
                <div class="vn-address-main">
                    <form method="post" action="options.php">
                        <?php
                        settings_fields('vn_address_wc_settings');
                        do_settings_sections('vn_address_wc_settings');
                        ?>
                        
                        <div class="vn-address-section">
                            <h2><?php esc_html_e('Address Settings', 'vn-address-for-woocommerce'); ?></h2>

                            <table class="form-table">
                                <tr>
                                    <th scope="row">
                                        <label for="vn_address_wc_structure"><?php esc_html_e('Default Address Structure', 'vn-address-for-woocommerce'); ?></label>
                                    </th>
                                    <td>
                                        <select id="vn_address_wc_structure" name="vn_address_wc_structure">
                                            <option value="new" <?php selected(get_option('vn_address_wc_structure'), 'new'); ?>>
                                                <?php esc_html_e('New structure (After 1/7/2025) - 34 provinces', 'vn-address-for-woocommerce'); ?>
                                            </option>
                                            <option value="old" <?php selected(get_option('vn_address_wc_structure'), 'old'); ?>>
                                                <?php esc_html_e('Old structure (Before 1/7/2025) - 63 provinces', 'vn-address-for-woocommerce'); ?>
                                            </option>
                                        </select>
                                        <p class="description">
                                            <?php
                                            printf(
                                                /* translators: %s: "Learn more" link to the plugin homepage */
                                                esc_html__('Choose the default address structure for the checkout form. Customers can change it when placing an order. %s', 'vn-address-for-woocommerce'),
                                                '<a href="https://jungdev.com/plugins/vn-address-for-woocommerce" target="_blank" rel="noopener noreferrer">' . esc_html__('Learn more', 'vn-address-for-woocommerce') . '</a>'
                                            );
                                            ?>
                                        </p>
                                    </td>
                                </tr>

                                <tr>
                                    <th scope="row">
                                        <label for="vn_address_wc_server_url"><?php esc_html_e('API Server', 'vn-address-for-woocommerce'); ?></label>
                                    </th>
                                    <td>
                                        <input type="url"
                                               id="vn_address_wc_server_url"
                                               name="vn_address_wc_server_url"
                                               value="<?php echo esc_attr(VN_Address_Data::get_instance()->get_server_url()); ?>"
                                               class="regular-text" />
                                        <button type="button" class="button button-secondary" id="test-server">
                                            <?php esc_html_e('Test Connection', 'vn-address-for-woocommerce'); ?>
                                        </button>
                                        <button type="button" class="button button-secondary" id="clear-server-cache">
                                            <?php esc_html_e('Clear Cache', 'vn-address-for-woocommerce'); ?>
                                        </button>
                                        <span id="server-status"></span>
                                        <p class="description">
                                            <?php esc_html_e('Keep the default address filled in here to get administrative address changes (province/ward renames, boundary updates) as soon as they\'re published, without needing to update the plugin itself. Checkout still works even if this is left blank or the server is temporarily unreachable - the plugin automatically falls back to the data bundled with the plugin.', 'vn-address-for-woocommerce'); ?>
                                        </p>
                                        <p class="description">
                                            <?php
                                            printf(
                                                /* translators: %s: link to the VietMap open data source */
                                                esc_html__('Administrative data provided by VietMap: %s.', 'vn-address-for-woocommerce'),
                                                '<a href="https://github.com/vietmap-company/vietnam_administrative_address" target="_blank" rel="noopener noreferrer">vietmap-company/vietnam_administrative_address</a>'
                                            );
                                            ?>
                                        </p>
                                        <p class="description">
                                            <?php
                                            printf(
                                                /* translators: %s: link to the self-hostable API server source code on GitHub */
                                                esc_html__('Prefer to run your own copy instead of relying on ours? The server is open source: %s.', 'vn-address-for-woocommerce'),
                                                '<a href="https://github.com/jungdevtoday/vn-address-api-server" target="_blank" rel="noopener noreferrer">github.com/jungdevtoday/vn-address-api-server</a>'
                                            );
                                            ?>
                                        </p>
                                    </td>
                                </tr>
                            </table>
                        </div>

                        <?php submit_button(__('Save Settings', 'vn-address-for-woocommerce')); ?>
                    </form>

                    <div class="vn-address-section">
                        <h2><?php esc_html_e('Old-to-new address conversion tool', 'vn-address-for-woocommerce'); ?></h2>
                        <p class="description">
                            <?php esc_html_e('Converts existing orders from the old address structure to the new structure by matching each order against the bundled old-to-new conversion table (from VietMap\'s published administrative mapping data, not a live API call - this runs entirely on your server, with no network requests, even if an API Server is configured above). Most wards convert automatically; a small number that were split between multiple new wards during the merger are flagged for manual review instead of being guessed. Nothing is overwritten: the original address on every order stays exactly as submitted, and the converted result is saved alongside it.', 'vn-address-for-woocommerce'); ?>
                        </p>

                        <div id="converter-status"></div>

                        <button type="button" class="button button-primary" id="convert-orders">
                            <?php esc_html_e('Convert Now', 'vn-address-for-woocommerce'); ?>
                        </button>

                        <div id="conversion-progress" style="display: none; margin-top: 15px;">
                            <div class="vn-progress-bar">
                                <div class="vn-progress-fill" style="width: 0%;"></div>
                            </div>
                            <p class="vn-progress-text">0%</p>
                        </div>

                        <div id="conversion-results" style="margin-top: 15px;"></div>
                    </div>
                </div>

                <div class="vn-address-sidebar">
                    <div class="vn-address-widget">
                        <h3><?php esc_html_e('Address Data', 'vn-address-for-woocommerce'); ?></h3>
                        <?php $this->render_data_status(); ?>
                        <p class="description">
                            <?php
                            printf(
                                /* translators: %s: link to the VietMap data source */
                                esc_html__('Bundled with the plugin, no external API required. Source: %s.', 'vn-address-for-woocommerce'),
                                '<a href="https://github.com/vietmap-company/vietnam_administrative_address" target="_blank" rel="noopener noreferrer">VietMap</a>'
                            );
                            ?>
                        </p>
                    </div>

                    <div class="vn-address-widget">
                        <h3><?php esc_html_e('Support', 'vn-address-for-woocommerce'); ?></h3>
                        <p><?php esc_html_e('Need help? Contact us.', 'vn-address-for-woocommerce'); ?></p>
                        <a href="https://jungdev.com" target="_blank" rel="noopener noreferrer" class="button button-secondary">
                            <?php esc_html_e('Contact Support', 'vn-address-for-woocommerce'); ?>
                        </a>
                    </div>

                    <div class="vn-address-widget">
                        <h3><?php esc_html_e('Plugin Information', 'vn-address-for-woocommerce'); ?></h3>
                        <p>
                            <strong><?php esc_html_e('Version:', 'vn-address-for-woocommerce'); ?></strong> <?php echo esc_html(VN_ADDRESS_WC_VERSION); ?><br>
                            <strong><?php esc_html_e('Structure:', 'vn-address-for-woocommerce'); ?></strong> <?php echo get_option('vn_address_wc_structure') === 'new' ? esc_html__('New (34 provinces)', 'vn-address-for-woocommerce') : esc_html__('Old (63 provinces)', 'vn-address-for-woocommerce'); ?><br>
                            <strong><?php esc_html_e('Author:', 'vn-address-for-woocommerce'); ?></strong> jungdev<br>
                            <strong><?php esc_html_e('Website:', 'vn-address-for-woocommerce'); ?></strong> <a href="https://jungdev.com" target="_blank" rel="noopener noreferrer">jungdev.com</a>
                        </p>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }

    private function render_data_status() {
        $data = VN_Address_Data::get_instance();
        $province_count = count($data->get_provinces_new());
        $ward_count = 0;
        foreach ($data->get_provinces_new() as $province) {
            $ward_count += count($data->get_wards_new($province['code']));
        }
        $server_url = $data->get_server_url();
        ?>
        <p>
            <?php
            printf(
                /* translators: 1: number of provinces, 2: number of wards */
                esc_html__('%1$d provinces, %2$d wards loaded (new structure).', 'vn-address-for-woocommerce'),
                (int) $province_count,
                (int) $ward_count
            );
            ?>
        </p>
        <p>
            <?php
            printf(
                /* translators: %s: configured server URL */
                esc_html__('API Server: %s (falls back to bundled data automatically if unreachable).', 'vn-address-for-woocommerce'),
                '<code>' . esc_html($server_url) . '</code>'
            );
            ?>
        </p>
        <?php
    }
    
    public function ajax_test_server() {
        check_ajax_referer('vn_address_admin_nonce', 'nonce');

        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(array('message' => __('Permission denied', 'vn-address-for-woocommerce')));
        }

        $url = isset($_POST['url']) ? esc_url_raw(wp_unslash($_POST['url'])) : '';

        if (empty($url)) {
            $url = VN_Address_Data::DEFAULT_SERVER_URL;
        }

        $response = wp_remote_get(rtrim($url, '/') . '/api/v1/provinces?structure=new', array('timeout' => 8));

        if (is_wp_error($response)) {
            wp_send_json_error(array('message' => $response->get_error_message()));
        }

        if (200 !== wp_remote_retrieve_response_code($response)) {
            wp_send_json_error(array(
                /* translators: %d: HTTP status code */
                'message' => sprintf(__('Server responded with HTTP %d', 'vn-address-for-woocommerce'), wp_remote_retrieve_response_code($response)),
            ));
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);

        if (!isset($body['success']) || !$body['success'] || empty($body['data']) || !is_array($body['data'])) {
            wp_send_json_error(array('message' => __('Server responded, but not in the expected format', 'vn-address-for-woocommerce')));
        }

        wp_send_json_success(array(
            'message' => __('Connection successful!', 'vn-address-for-woocommerce'),
            'count' => count($body['data']),
        ));
    }

    public function ajax_clear_server_cache() {
        check_ajax_referer('vn_address_admin_nonce', 'nonce');

        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(array('message' => __('Permission denied', 'vn-address-for-woocommerce')));
        }

        VN_Address_Data::get_instance()->clear_server_cache();

        wp_send_json_success(array(
            'message' => __('Cache cleared successfully!', 'vn-address-for-woocommerce'),
        ));
    }

    /**
     * Report how many orders still need conversion, so the client can size
     * its progress bar before starting the batch loop.
     */
    public function ajax_convert_count() {
        check_ajax_referer('vn_address_admin_nonce', 'nonce');

        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(array('message' => __('Permission denied', 'vn-address-for-woocommerce')));
        }

        $total = VN_Address_Converter::get_instance()->count_eligible_orders();

        wp_send_json_success(array('total' => $total));
    }

    /**
     * Convert one bounded batch of orders. The client calls this repeatedly
     * until the response reports no orders remaining, so a single request
     * never has to process more than BATCH_SIZE orders - this keeps the
     * conversion safe from PHP execution-time and memory limits no matter
     * how many old-structure orders a store has.
     */
    public function ajax_convert_batch() {
        check_ajax_referer('vn_address_admin_nonce', 'nonce');

        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(array('message' => __('Permission denied', 'vn-address-for-woocommerce')));
        }

        $result = VN_Address_Converter::get_instance()->convert_batch(self::CONVERT_BATCH_SIZE);

        wp_send_json_success($result);
    }
}
