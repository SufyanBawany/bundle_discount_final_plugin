<?php
/**
 * Display Fields for Product Discount Bundle
 *
 * @package WCPDB
 */

// If this file is called directly, abort.
if (!defined('ABSPATH')) {
    exit;
}


/**
 * Class WCPDB_Display_Fields
 * Handles the display of bundle options on the product page
 */
class WCPDB_Display_Fields
{
    /**
     * Constructor
     */
    public function __construct()
    {
        // Only proceed if WooCommerce is active
        if (!$this->is_woocommerce_active()) {
            return;
        }

        // Display bundle options before add to cart button
        add_action('woocommerce_before_add_to_cart_button', array($this, 'display_bundle_options'));
        
        // Enqueue frontend styles and scripts
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_assets'));
    }

    /**
     * Check if WooCommerce is active
     *
     * @return bool
     */
    private function is_woocommerce_active()
    {
        return in_array(
            'woocommerce/woocommerce.php',
            apply_filters('active_plugins', get_option('active_plugins'))
        );
    }

    public function enqueue_frontend_assets()
    {
        if (is_product()) {
            wp_enqueue_style('wcpdb-global-styles', WCPDB_PLUGIN_URL . 'assets/css/global-styles.css', array(), '1.0.0');
            wp_enqueue_script('wcpdb-frontend-scripts', WCPDB_PLUGIN_URL . 'assets/js/frontend-scripts.js', array('jquery'), '1.0.1', true);
            wp_enqueue_script('wcpdb-global-settings-scripts', WCPDB_PLUGIN_URL . 'assets/js/global-settings-scripts.js', array('jquery'), '1.0.1', true);
        }
    }

    /**
     * Format price in PKR
     */
    public static function format_pkr_price($price)
    {
        return wc_price($price);
    }

    /**
     * Display bundle options
     */
    public function display_bundle_options()
    {
        global $product;

        $product_id = $product->get_id();
        $is_custom_enabled = get_post_meta($product_id, '_enable_discount_offers', true) === 'yes';
        $sections = [];
        $source = 'none'; // To track where the settings came from (custom or global)
        $global_settings = [];

        if ($is_custom_enabled) {
            $sections = $this->get_bundle_sections($product_id);
            
            // Check if product-level timer is enabled
            if (!empty($sections)) {
                $timer_enabled = $sections[0]['timer_enabled'] ?? 'no';
                if ($timer_enabled !== 'yes') {
                    $sections = []; // Timer disabled = no sections
                }
            }
            
            $source = 'custom';
        } else {
            $is_global_enabled = get_option('wcpdb_enable_global_settings', 'no') === 'yes';
            if ($is_global_enabled) {
                $global_settings = get_option('wcpdb_discount_offer_settings', []);
                // Ensure timer fields are always present in $global_settings['global']
                if (!isset($global_settings['global'])) {
                    $global_settings['global'] = [];
                }
                
                // Check if timer is enabled
                $timer_enabled = $global_settings['global']['enable_timer'] ?? 'no';
                if ($timer_enabled !== 'yes') {
                    $sections = []; // Timer disabled = no sections
                    $source = 'global';
                } else {
                    $global_settings['global']['timer_enabled'] = $global_settings['global']['timer_enabled'] ?? '';
                    $global_settings['global']['timer_to'] = $global_settings['global']['timer_to'] ?? '';
                    $global_settings['global']['timer_text'] = $global_settings['global']['timer_text'] ?? '';
                    $raw_global_sections = $global_settings['sections'] ?? [];
                    $discount_type = $global_settings['global']['discount_type'] ?? 'percentage';
                    $valid_global_sections = [];

                    if (!empty($raw_global_sections) && is_array($raw_global_sections)) {
                        foreach ($raw_global_sections as $section_data) {
                            $quantity = isset($section_data['quantity']) ? intval($section_data['quantity']) : 0;
                            if ($quantity <= 0) continue;

                            $is_valid = false;
                            if ($discount_type === 'percentage') {
                                $discount_percentage = isset($section_data['discount_percentage']) ? floatval($section_data['discount_percentage']) : 0;
                                if ($discount_percentage > 0) {
                                    $is_valid = true;
                                }
                            } else { // 'fixed'
                                $discount_amount = isset($section_data['discount_amount']) ? floatval($section_data['discount_amount']) : 0;
                                if ($discount_amount > 0) {
                                    $is_valid = true;
                                }
                            }

                            if ($is_valid) {
                                $valid_global_sections[] = $section_data;
                            }
                        }
                    }
                    
                    $sections = $valid_global_sections;
                    $source = 'global';
                }
            }
        }

        if (empty($sections)) {
            return;
        }

        // All data is prepared, now load the view
        include WCPDB_PLUGIN_DIR . 'includes/views/public/product-page-offers.php';
    }


    private function get_bundle_sections($product_id)
    {
        $sections = [];

        // --- Validate and add the first/base section ---
        $first_section_qty = intval(get_post_meta($product_id, '_bundle_quantity_1x', true));
        $first_section_type = get_post_meta($product_id, '_discount_type_1x', true) ?: 'percentage';
        
        $is_first_valid = false;
        if ($first_section_qty > 0) {
            if ($first_section_type === 'percentage') {
                if (floatval(get_post_meta($product_id, '_discount_percentage_1x', true)) > 0) {
                    $is_first_valid = true;
                }
            } else { // 'fixed'
                if (floatval(get_post_meta($product_id, '_discount_amount_1x', true)) > 0) {
                    $is_first_valid = true;
                }
            }
        }

        if ($is_first_valid) {
            $sections[] = [
                'quantity'            => $first_section_qty,
                'discount_type'       => $first_section_type,
                'discount_percentage' => floatval(get_post_meta($product_id, '_discount_percentage_1x', true)),
                'discount_amount'     => floatval(get_post_meta($product_id, '_discount_amount_1x', true)),
                'timer_enabled'       => get_post_meta($product_id, '_timer_enabled_1x', true) ?: 'no',
                'timer_from'          => get_post_meta($product_id, '_timer_from_1x', true),
                'timer_to'            => get_post_meta($product_id, '_timer_to_1x', true),
                'timer_text'          => get_post_meta($product_id, '_timer_text_1x', true),
                'discount_title'      => get_post_meta($product_id, '_bundle_heading_1x', true)
            ];
        }

        // --- Validate and add additional sections ---
        $additional_sections = get_post_meta($product_id, '_additional_sections', true);
        if (!empty($additional_sections) && is_array($additional_sections)) {
            foreach ($additional_sections as $section_data) {
                $add_qty = intval($section_data['quantity'] ?? 0);
                $add_type = $section_data['discount_type'] ?? 'percentage';
                $add_percentage = floatval($section_data['discount_percentage'] ?? 0);
                $add_amount = floatval($section_data['discount_amount'] ?? 0);
                $is_add_valid = false;
                if ($add_qty > 0) {
                    if ($add_type === 'percentage') {
                        if ($add_percentage > 0) {
                            $is_add_valid = true;
                        }
                    } else { // 'fixed'
                        if ($add_amount > 0) {
                            $is_add_valid = true;
                        }
                    }
                }
                if ($is_add_valid) {
                    // Always set a complete heading
                    if (!empty($section_data['discount_title'])) {
                        $section_data['discount_title'] = $section_data['discount_title'];
                    } else {
                        $discount_val = ($add_type === 'percentage') ? $add_percentage . '%' : 'Rs.' . $add_amount;
                        $section_data['discount_title'] = 'buy ' . $add_qty . ' & get ' . $discount_val . ' discount';
                    }
                    // Add timer_from and timer_to to section_data if not present
                    $section_data['timer_from'] = $section_data['timer_from'] ?? '';
                    $section_data['timer_to'] = $section_data['timer_to'] ?? '';
                    $sections[] = $section_data;
                }
            }
        }
        return $sections;
    }

    public static function calculate_discounted_price($original_price, $discount_type, $discount_value)
    {
        $original_price = floatval($original_price);
        $discount_value = floatval($discount_value);
        $discounted_price = $original_price;

        if ($discount_type === 'percentage' && $discount_value > 0) {
            $discounted_price = $original_price - ($original_price * ($discount_value / 100));
        } elseif ($discount_type === 'fixed' && $discount_value > 0) {
            $discounted_price = $original_price - $discount_value;
        }
        
        return max(0, $discounted_price);
    }
}

// Initialize the class
new WCPDB_Display_Fields();