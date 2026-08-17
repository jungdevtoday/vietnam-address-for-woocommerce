<?php
/**
 * Plugin Name: VN Address for WooCommerce
 * Plugin URI: https://jungdev.com/plugins/vn-address-for-woocommerce
 * Description: Integrates Vietnamese administrative addresses into WooCommerce with a bundled Province/District/Ward dataset. Supports converting addresses from the old structure to the new one.
 * Version: 1.0.0
 * Author: jungdev
 * Author URI: https://jungdev.com
 * Text Domain: vn-address-for-woocommerce
 * Domain Path: /languages
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * WC requires at least: 5.0
 * WC tested up to: 11.0.1
 * Requires Plugins: woocommerce
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Define plugin constants
define('VN_ADDRESS_WC_VERSION', '1.0.0');
define('VN_ADDRESS_WC_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('VN_ADDRESS_WC_PLUGIN_URL', plugin_dir_url(__FILE__));
define('VN_ADDRESS_WC_PLUGIN_BASENAME', plugin_basename(__FILE__));

// Check if WooCommerce is active
if (!in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {
    add_action('admin_notices', 'vn_address_wc_woocommerce_missing_notice');
    return;
}

function vn_address_wc_woocommerce_missing_notice() {
    ?>
    <div class="error">
        <p><?php _e('VN Address for WooCommerce requires WooCommerce to be installed and activated.', 'vn-address-for-woocommerce'); ?></p>
    </div>
    <?php
}

// Declare HPOS compatibility
add_action('before_woocommerce_init', function() {
    if (class_exists(\Automattic\WooCommerce\Utilities\FeaturesUtil::class)) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('custom_order_tables', __FILE__, true);
    }
});

// Include required files
require_once VN_ADDRESS_WC_PLUGIN_DIR . 'includes/class-vn-address-data.php';
require_once VN_ADDRESS_WC_PLUGIN_DIR . 'includes/class-vn-address-admin.php';
require_once VN_ADDRESS_WC_PLUGIN_DIR . 'includes/class-vn-address-checkout.php';
require_once VN_ADDRESS_WC_PLUGIN_DIR . 'includes/class-vn-address-blocks.php';
require_once VN_ADDRESS_WC_PLUGIN_DIR . 'includes/class-vn-address-converter.php';

/**
 * Main plugin class
 */
class VN_Address_WooCommerce {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        $this->init_hooks();
    }
    
    private function init_hooks() {
        add_action('plugins_loaded', array($this, 'load_textdomain'));
        add_action('init', array($this, 'init'));
        
        // Admin hooks
        if (is_admin()) {
            VN_Address_Admin::get_instance();
        }
        
        // Frontend hooks
        VN_Address_Checkout::get_instance();
        VN_Address_Blocks::get_instance();

        // Converter
        VN_Address_Converter::get_instance();
    }
    
    public function load_textdomain() {
        add_filter('load_textdomain_mofile', array($this, 'default_to_vietnamese_mofile'), 10, 2);
        load_plugin_textdomain('vn-address-for-woocommerce', false, dirname(VN_ADDRESS_WC_PLUGIN_BASENAME) . '/languages');
    }

    /**
     * Most customers run Vietnamese-language sites, so fall back to the
     * Vietnamese translation (instead of the English source strings) when
     * the site's locale has no dedicated translation of its own. Sites
     * explicitly set to a locale we do ship (fr_FR, de_DE, ja) keep using
     * that translation as normal.
     */
    public function default_to_vietnamese_mofile($mofile, $domain) {
        if ('vn-address-for-woocommerce' !== $domain || file_exists($mofile)) {
            return $mofile;
        }

        $vi_mofile = VN_ADDRESS_WC_PLUGIN_DIR . 'languages/vn-address-for-woocommerce-vi.mo';

        return file_exists($vi_mofile) ? $vi_mofile : $mofile;
    }
    
    public function init() {
        // Register AJAX actions
        add_action('wp_ajax_vn_address_get_provinces', array($this, 'ajax_get_provinces'));
        add_action('wp_ajax_nopriv_vn_address_get_provinces', array($this, 'ajax_get_provinces'));
        
        add_action('wp_ajax_vn_address_get_districts', array($this, 'ajax_get_districts'));
        add_action('wp_ajax_nopriv_vn_address_get_districts', array($this, 'ajax_get_districts'));
        
        add_action('wp_ajax_vn_address_get_wards', array($this, 'ajax_get_wards'));
        add_action('wp_ajax_nopriv_vn_address_get_wards', array($this, 'ajax_get_wards'));
    }
    
    public function ajax_get_provinces() {
        check_ajax_referer('vn_address_nonce', 'nonce');

        $structure = isset($_POST['structure']) ? sanitize_text_field($_POST['structure']) : 'new';
        $provinces = VN_Address_Data::get_instance()->get_provinces($structure);

        wp_send_json_success($provinces);
    }

    public function ajax_get_districts() {
        check_ajax_referer('vn_address_nonce', 'nonce');

        $province_code = isset($_POST['province_code']) ? sanitize_text_field($_POST['province_code']) : '';

        if (empty($province_code)) {
            wp_send_json_error(array('message' => __('Province code is required', 'vn-address-for-woocommerce')));
        }

        $districts = VN_Address_Data::get_instance()->get_districts_old($province_code);

        wp_send_json_success($districts);
    }

    public function ajax_get_wards() {
        check_ajax_referer('vn_address_nonce', 'nonce');

        $structure = isset($_POST['structure']) ? sanitize_text_field($_POST['structure']) : 'new';
        $parent_code = isset($_POST['parent_code']) ? sanitize_text_field($_POST['parent_code']) : '';

        if (empty($parent_code)) {
            wp_send_json_error(array('message' => __('Parent code is required', 'vn-address-for-woocommerce')));
        }

        $wards = VN_Address_Data::get_instance()->get_wards($parent_code, $structure);

        wp_send_json_success($wards);
    }
}

// Initialize the plugin
function vn_address_woocommerce() {
    return VN_Address_WooCommerce::get_instance();
}

vn_address_woocommerce();

// Activation hook
register_activation_hook(__FILE__, 'vn_address_wc_activate');
function vn_address_wc_activate() {
    // Create default options
    if (!get_option('vn_address_wc_structure')) {
        add_option('vn_address_wc_structure', 'new');
    }
    if (!get_option('vn_address_wc_enable_converter')) {
        add_option('vn_address_wc_enable_converter', 'no');
    }
}

// Deactivation hook
register_deactivation_hook(__FILE__, 'vn_address_wc_deactivate');
function vn_address_wc_deactivate() {
    // Cleanup if needed
}
