<?php

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Custom Discount Offer Settings Tab in WooCommerce Admin
 */
class WCPDB_Discount_Offer_Settings_Tab
{

    public function __construct()
    {
        add_filter('woocommerce_settings_tabs_array', array($this, 'add_settings_tab'), 50);
        add_action('woocommerce_settings_tabs_discount_offer', array($this, 'settings_tab_content'));
        add_action('admin_init', array($this, 'save_settings'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_settings_assets'));
       }

    public function add_settings_tab($tabs)
    {
        $tabs['discount_offer'] = __('Discount Offer', 'product-discount-offer');
        return $tabs;
    }

    /**
     * Output the settings tab HTML
     */
    public function settings_tab_content()
    {
        // Load all settings
        $enable_global_settings = get_option('wcpdb_enable_global_settings', 'no');
        $discount_settings = get_option('wcpdb_discount_offer_settings', []);
        $global_settings = $discount_settings['global'] ?? [];
        $discount_type = $global_settings['discount_type'] ?? 'percentage';
        $enable_timer = $global_settings['enable_timer'] ?? 'no';
        $timer_text = $global_settings['timer_text'] ?? '';
        $sections = $discount_settings['sections'] ?? [];
        include_once WCPDB_PLUGIN_DIR . 'includes/views/admin/settings-tab-form.php';
    }

    /**
     * Save settings (custom logic for global settings and sections)
     */
    public function save_settings()
    {
        if (isset($_POST['wcpdb_save_settings']) && isset($_POST['_wpnonce']) && wp_verify_nonce($_POST['_wpnonce'], 'wcpdb_save_settings_nonce')) {
            if (!current_user_can('manage_woocommerce')) {
                wp_die(__('You do not have permission to update these settings.', 'product-discount-offer'));
            }
            $discount_settings = array();
            $enable_global_settings = isset($_POST['wcpdb_enable_global_settings']) && $_POST['wcpdb_enable_global_settings'] === 'yes' ? 'yes' : 'no';
            update_option('wcpdb_enable_global_settings', $enable_global_settings);
            $discount_type = isset($_POST['wcpdb_discount_type']) ? sanitize_text_field($_POST['wcpdb_discount_type']) : 'percentage';
            $discount_settings['global'] = array(
                'discount_type'  => $discount_type,
                'enable_timer'   => isset($_POST['wcpdb_enable_timer']) ? 'yes' : 'no',
                'timer_from'     => isset($_POST['wcpdb_timer_from']) ? sanitize_text_field($_POST['wcpdb_timer_from']) : '',
                'timer_to'       => isset($_POST['wcpdb_timer_to']) ? sanitize_text_field($_POST['wcpdb_timer_to']) : '',
                'timer_text'     => isset($_POST['wcpdb_timer_text']) ? sanitize_text_field($_POST['wcpdb_timer_text']) : '',
            );
            if (isset($_POST['wcpdb_discount_sections']) && is_array($_POST['wcpdb_discount_sections'])) {
                $sanitized_sections = [];
                foreach ($_POST['wcpdb_discount_sections'] as $index => $section) {
                    if ($index === '__INDEX__') continue;
                    $quantity = isset($section['quantity']) ? intval($section['quantity']) : 0;
                    if ($quantity <= 0) continue;
                    $sanitized_section = [
                        'quantity'         => $quantity,
                        'discount_percentage' => '',
                        'discount_amount'     => '',
                        'index'               => $index,
                    ];
                    if ($discount_type === 'percentage') {
                        $discount_percentage = isset($section['discount_percentage']) ? floatval($section['discount_percentage']) : 0;
                        if ($discount_percentage <= 0) continue;
                        $sanitized_section['discount_percentage'] = $discount_percentage;
                    } else {
                        $discount_amount = isset($section['discount_amount']) ? floatval($section['discount_amount']) : 0;
                        if ($discount_amount <= 0) continue;
                        $sanitized_section['discount_amount'] = $discount_amount;
                    }
                    $sanitized_sections[] = $sanitized_section;
                }
                $discount_settings['sections'] = $sanitized_sections;
            } else {
                $discount_settings['sections'] = [];
            }
            update_option('wcpdb_discount_offer_settings', $discount_settings);
            wp_safe_redirect(admin_url('admin.php?page=wc-settings&tab=discount_offer&settings-saved=true'));
            exit;
        }
    }

    /**
     * Output the settings tab CSS and JS
     */
    public function enqueue_settings_assets($hook)
    {
        // Only for WooCommerce settings tab
        if (
            ($hook === 'woocommerce_page_wc-settings' || $hook === 'toplevel_page_wc-settings')
            && isset($_GET['tab']) && $_GET['tab'] === 'discount_offer'
        ) {
            wp_enqueue_script('wcpdb-settings-scripts', WCPDB_PLUGIN_URL . 'assets/js/global-settings-scripts.js', null, true);
        }
    }

    public static function get_discount_section_data($index, $section = [])
    {
        return wp_parse_args(
            $section, [
            'quantity'            => 0,
            'discount_percentage' => '',
            'discount_amount'     => '',
            'index'               => $index,
            ]
        );
    }
}

new WCPDB_Discount_Offer_Settings_Tab();