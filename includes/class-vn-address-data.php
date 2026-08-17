<?php
/**
 * Vietnamese administrative address data.
 *
 * Reads from a bundled snapshot by default (works immediately after install,
 * no configuration needed). If a central data server URL is configured in
 * settings, that's tried first (with a short timeout and a local WordPress
 * transient cache) and the bundled snapshot is used as a fallback whenever
 * the server is unreachable or misconfigured - the plugin never breaks
 * checkout because of it.
 *
 * Data source: VietMap (https://github.com/vietmap-company/vietnam_administrative_address),
 * used under the VietMap Administrative Data License (see assets/data/LICENSE-vietmap-data.txt).
 */

if (!defined('ABSPATH')) {
    exit;
}

class VN_Address_Data {

    private static $instance = null;
    private $cache = array();

    const UNREACHABLE_CACHE_SECONDS = 300;
    const SUCCESS_CACHE_SECONDS = 86400; // 1 day

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function load_json($filename) {
        if (isset($this->cache[$filename])) {
            return $this->cache[$filename];
        }

        $path = VN_ADDRESS_WC_PLUGIN_DIR . 'assets/data/' . $filename;
        if (!file_exists($path)) {
            return array();
        }

        $data = json_decode(file_get_contents($path), true);
        $data = is_array($data) ? $data : array();

        $this->cache[$filename] = $data;

        return $data;
    }

    public function get_server_url() {
        return trim(get_option('vn_address_wc_server_url', ''));
    }

    /**
     * Try the configured central data server first, falling back to the
     * bundled local snapshot if it's not configured, unreachable, or returns
     * something unexpected. Successful and failed lookups are both cached
     * (much longer for success) so a configured server isn't hit on every
     * page load and a down server doesn't add request latency to checkout.
     */
    private function fetch($remote_path, $fallback_callback) {
        $base = $this->get_server_url();

        if (empty($base)) {
            return call_user_func($fallback_callback);
        }

        $cache_key = 'vn_address_srv_' . md5($base . $remote_path);
        $cached = get_transient($cache_key);

        if ($cached !== false) {
            return $cached === 'unreachable' ? call_user_func($fallback_callback) : $cached;
        }

        $response = wp_remote_get(rtrim($base, '/') . $remote_path, array('timeout' => 5));

        if (is_wp_error($response) || 200 !== wp_remote_retrieve_response_code($response)) {
            set_transient($cache_key, 'unreachable', self::UNREACHABLE_CACHE_SECONDS);
            return call_user_func($fallback_callback);
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);

        if (!isset($body['success']) || !$body['success'] || !isset($body['data']) || !is_array($body['data'])) {
            set_transient($cache_key, 'unreachable', self::UNREACHABLE_CACHE_SECONDS);
            return call_user_func($fallback_callback);
        }

        set_transient($cache_key, $body['data'], self::SUCCESS_CACHE_SECONDS);

        return $body['data'];
    }

    /**
     * List of provinces for the given structure ('new' or 'old').
     */
    public function get_provinces($structure = 'new') {
        return $structure === 'old' ? $this->get_provinces_old() : $this->get_provinces_new();
    }

    /**
     * Wards for a given parent code. For the new structure the parent is a
     * province code; for the old structure it's a district code.
     */
    public function get_wards($parent_code, $structure = 'new') {
        return $structure === 'old' ? $this->get_wards_old($parent_code) : $this->get_wards_new($parent_code);
    }

    /**
     * List of provinces for the new (post 1/7/2025) 2-level structure.
     */
    public function get_provinces_new() {
        return $this->fetch('/api/v1/provinces?structure=new', function () {
            return $this->load_json('provinces-new.json');
        });
    }

    /**
     * Wards for a given new-structure province code.
     */
    public function get_wards_new($province_code) {
        return $this->fetch('/api/v1/wards/' . rawurlencode($province_code) . '?structure=new', function () use ($province_code) {
            $wards = $this->load_json('wards-new.json');
            return isset($wards[$province_code]) ? $wards[$province_code] : array();
        });
    }

    /**
     * All new-structure wards, grouped by parent province code. Used by
     * Block Checkout's client-side autocomplete, which needs the full set
     * to filter instantly as the customer types rather than round-tripping
     * per keystroke.
     */
    public function get_wards_new_bulk() {
        return $this->fetch('/api/v1/wards-bulk?structure=new', function () {
            return $this->load_json('wards-new.json');
        });
    }

    /**
     * Look up a single new-structure province's name by code.
     */
    public function get_province_new_name($province_code) {
        foreach ($this->get_provinces_new() as $province) {
            if ($province['code'] === $province_code) {
                return $province['name'];
            }
        }
        return '';
    }

    /**
     * Look up a single new-structure ward's name by code + parent province code.
     */
    public function get_ward_new_name($province_code, $ward_code) {
        foreach ($this->get_wards_new($province_code) as $ward) {
            if ($ward['code'] === $ward_code) {
                return $ward['name'];
            }
        }
        return '';
    }

    /**
     * List of provinces for the old (pre 1/7/2025) 3-level structure.
     */
    public function get_provinces_old() {
        return $this->fetch('/api/v1/provinces?structure=old', function () {
            return $this->load_json('provinces-old.json');
        });
    }

    /**
     * Districts for a given old-structure province code.
     */
    public function get_districts_old($province_code) {
        return $this->fetch('/api/v1/districts/' . rawurlencode($province_code), function () use ($province_code) {
            $districts = $this->load_json('districts-old.json');
            return isset($districts[$province_code]) ? $districts[$province_code] : array();
        });
    }

    /**
     * Wards for a given old-structure district code.
     */
    public function get_wards_old($district_code) {
        return $this->fetch('/api/v1/wards/' . rawurlencode($district_code) . '?structure=old', function () use ($district_code) {
            $wards = $this->load_json('wards-old.json');
            return isset($wards[$district_code]) ? $wards[$district_code] : array();
        });
    }

    /**
     * Candidate new-structure wards for a given old-structure ward code.
     *
     * Returns a list of { province_code, province_name, ward_code, ward_name }.
     * Most old wards map to exactly one new ward (deterministic). A small
     * minority (~3%) were split across multiple new wards during the 1/7/2025
     * merger and have more than one candidate here - callers should treat
     * those as needing manual review rather than guessing.
     */
    public function get_ward_mapping($old_ward_code) {
        return $this->fetch('/api/v1/ward-mapping/' . rawurlencode($old_ward_code), function () use ($old_ward_code) {
            $mapping = $this->load_json('ward-mapping-old-to-new.json');
            return isset($mapping[$old_ward_code]) ? $mapping[$old_ward_code] : array();
        });
    }

    /**
     * Clear any cached responses from the central data server.
     */
    public function clear_server_cache() {
        global $wpdb;
        $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
                $wpdb->esc_like('_transient_vn_address_srv_') . '%',
                $wpdb->esc_like('_transient_timeout_vn_address_srv_') . '%'
            )
        );
    }
}
