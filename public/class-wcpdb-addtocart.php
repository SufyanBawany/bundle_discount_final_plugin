<?php
/**
 * Handle Add to Cart functionality with discounts
 *
 * @package WCPDB
 */

// If this file is called directly, abort.
if (!defined('ABSPATH')) {
    exit;
}

// Check if WooCommerce is active
if (!in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {
    return;
}

class WCPDB_AddToCart
{
    
    public function __construct()
    {

        // Main cart item filters and discount logic
        add_filter('woocommerce_before_calculate_totals', [$this, 'apply_discount_to_cart_items'], 999, 1);
        add_filter('woocommerce_get_cart_item_from_session', [$this, 'get_cart_item_from_session'], 999, 2);
        add_filter('woocommerce_add_cart_item_data', [$this, 'add_discount_data_to_cart_item'], 10, 3);
    
        // Pricing filters for product display (e.g., on product page, shop loop)
        add_filter('woocommerce_product_get_price', [$this, 'get_discounted_price'], 999, 2);
        add_filter('woocommerce_product_get_regular_price', [$this, 'get_discounted_price'], 999, 2);
        add_filter('woocommerce_product_variation_get_price', [$this, 'get_discounted_price'], 999, 2);
        add_filter('woocommerce_product_variation_get_regular_price', [$this, 'get_discounted_price'], 999, 2);

        // Price display adjustments in the cart and checkout pages
        add_filter('woocommerce_cart_item_price', [$this, 'update_cart_item_price_display'], 999, 3);
        add_filter('woocommerce_cart_item_subtotal', [$this, 'update_cart_item_subtotal_display'], 999, 3);

        // Add to cart flow validation and actions
        add_action('woocommerce_add_to_cart', [$this, 'after_add_to_cart'], 999, 6);

        // Enqueue scripts and styles for frontend
        add_action('wp_enqueue_scripts', [$this, 'enqueue_scripts']);

        // AJAX handlers for dynamic price updates on the frontend (if applicable)
        add_action('wp_ajax_wcpdb_get_discounted_price', [$this, 'ajax_get_discounted_price']);
        add_action('wp_ajax_nopriv_wcpdb_get_discounted_price', [$this, 'ajax_get_discounted_price']);
    }

  

    /**
     * Enqueue JavaScript scripts for cart-related functionality.
     */
    public function enqueue_scripts()
    {
        if (is_cart() || is_checkout() || is_product()) {
            wp_enqueue_script(
                'wcpdb-cart-scripts',
                WCPDB_PLUGIN_URL . 'assets/js/cart-scripts.js',
                ['jquery'], // Dependency on jQuery
                '1.0.0', // Version number
                true // Enqueue in footer
            );
            
            // Localize script to pass AJAX URL and nonce
            wp_localize_script(
                'wcpdb-cart-scripts', 'wcpdb_ajax', [
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce'    => wp_create_nonce('wcpdb_ajax_nonce')
                ]
            );
        }
    }

    public function after_add_to_cart($cart_item_key, $product_id, $quantity, $variation_id, $variation, $cart_item_data)
    {
        error_log("After add to cart for product: " . $product_id . " with cart item key: " . $cart_item_key);
    }

    /**
     * Retrieves bundle sections from product meta-data.
     * This defines the discount tiers and types set by the admin.
     */
    private function get_bundle_sections($product_id)
    {
        $sections = [];
    
        // 🔁 If 0 is passed, treat as global fallback
        if ($product_id === 0) {
            $global_settings = get_option('wcpdb_discount_offer_settings', []);
            $global_discount_type = $global_settings['global']['discount_type'] ?? 'percentage';
            $global_sections = get_option('wcpdb_global_discount_rules', []);
    
            if (!empty($global_sections) && is_array($global_sections)) {
                foreach ($global_sections as $section_data) {
                    $quantity = isset($section_data['quantity']) ? intval($section_data['quantity']) : 0;
                    if ($quantity > 0) {
                        $discount_value = 0;
                        if ($global_discount_type === 'percentage') {
                            $discount_value = floatval($section_data['discount_percentage'] ?? 0);
                        } else {
                            $discount_value = floatval($section_data['discount_amount'] ?? 0);
                        }

                        if ($discount_value > 0) {
                            $sections[] = [
                                'quantity'         => $quantity,
                                'discount_type'    => $global_discount_type,
                                'discount_value'   => $discount_value,
                            ];
                        }
                    }
                }
            }
            return $sections;
        }
    
        // ✅ Otherwise, treat as product-specific
        $enabled = get_post_meta($product_id, '_enable_discount_offers', true);
    
        // Always include the base section (1x)
        $first_section = [
            'quantity'            => intval(get_post_meta($product_id, '_bundle_quantity_1x', true)) ?: 1,
            'discount_type'       => get_post_meta($product_id, '_discount_type_1x', true) ?: 'percentage',
            'discount_percentage' => floatval(get_post_meta($product_id, '_discount_percentage_1x', true)) ?: 0,
            'discount_amount'     => floatval(get_post_meta($product_id, '_discount_amount_1x', true)) ?: 0,
        ];
        $sections[] = $first_section;
    
        // Additional tiered sections
        $additional_sections = get_post_meta($product_id, '_additional_sections', true);
        if (!empty($additional_sections) && is_array($additional_sections)) {
            foreach ($additional_sections as $section_data) {
                if (!empty($section_data['quantity']) && intval($section_data['quantity']) > 0) {
                    $sections[] = [
                        'quantity'            => intval($section_data['quantity']),
                        'discount_type'       => $section_data['discount_type'] ?? 'percentage',
                        'discount_percentage' => floatval($section_data['discount_percentage'] ?? 0),
                        'discount_amount'     => floatval($section_data['discount_amount'] ?? 0),
                    ];
                }
            }
        }
    
        return $sections;
    }

    /**
     * Determines the most applicable discount based on product ID and quantity.
     * It finds the highest quantity tier that the current quantity meets or exceeds.
     */
    public function get_applicable_discount($product_id, $quantity)
    {
        // 1. Check product-level discount first
        $is_custom_enabled = get_post_meta($product_id, '_enable_discount_offers', true) === 'yes';
        if ($is_custom_enabled) {
            // Product-level timer logic
            $timer_enabled = get_post_meta($product_id, '_timer_enabled_1x', true) === 'yes';
            $timer_from = get_post_meta($product_id, '_timer_from_1x', true);
            $timer_to = get_post_meta($product_id, '_timer_to_1x', true);
            $now = date('Y-m-d H:i:s', (function_exists('current_time') ? current_time('timestamp') : time()));
            if ($timer_enabled && $timer_from && $timer_to) {
                $timer_from_full = $timer_from . ' 00:00:00';
                $timer_to_full = $timer_to . ' 23:59:59';
                if ($now < $timer_from_full || $now > $timer_to_full) {
                    return false; // Product-level timer not active
                }
            } elseif ($timer_enabled) {
                return false; // Timer enabled but not set properly
            }
            // Get product-level discount sections
            $sections = $this->get_bundle_sections($product_id);
            if (empty($sections)) {
                return false;
            }
            usort($sections, fn($a, $b) => $b['quantity'] - $a['quantity']);
            foreach ($sections as $section) {
                if ($quantity >= $section['quantity']) {
                    $discount_type = $section['discount_type'] ?? 'percentage';
                    $discount_value = ($discount_type === 'percentage')
                        ? floatval($section['discount_percentage'] ?? 0)
                        : floatval($section['discount_amount'] ?? 0);
                    return [
                        'type'  => $discount_type,
                        'value' => $discount_value,
                        'quantity_matched' => $section['quantity'],
                    ];
                }
            }
            return false;
        }
        // 2. Fallback to global logic (as before)
        $is_global_enabled = get_option('wcpdb_enable_global_settings', 'no') === 'yes';
        if (!$is_global_enabled) {
            return false; // Global discount not enabled
        }
        $global_settings = get_option('wcpdb_discount_offer_settings', []);
        
        // Check if timer is enabled
        $timer_enabled = $global_settings['global']['enable_timer'] ?? 'no';
        if ($timer_enabled !== 'yes') {
            return false; // Timer disabled = no discount
        }
        
        $timer_from = $global_settings['global']['timer_from'] ?? '';
        $timer_to = $global_settings['global']['timer_to'] ?? '';
        // --- Updated logic: treat timer_to as end of day ---
        $now = date('Y-m-d H:i:s', (function_exists('current_time') ? current_time('timestamp') : time()));
        if ($timer_from && $timer_to) {
            $timer_from_full = $timer_from . ' 00:00:00';
            $timer_to_full = $timer_to . ' 23:59:59';
            if ($now < $timer_from_full || $now > $timer_to_full) {
                return false; // Timer not active
            }
        } else {
            return false; // Timer not set
        }
        // Now, get global discount sections and apply as before
        $sections = $global_settings['sections'] ?? [];
        if (empty($sections)) {
            return false;
        }
        usort($sections, fn($a, $b) => $b['quantity'] - $a['quantity']);
        foreach ($sections as $section) {
            if ($quantity >= $section['quantity']) {
                $discount_type = $global_settings['global']['discount_type'] ?? 'percentage';
                $discount_value = ($discount_type === 'percentage')
                    ? floatval($section['discount_percentage'] ?? 0)
                    : floatval($section['discount_amount'] ?? 0);
                return [
                    'type'  => $discount_type,
                    'value' => $discount_value,
                    'quantity_matched' => $section['quantity'],
                ];
            }
        }
        return false;
    }
    
    /**
     * Restores custom cart item data from the session.
     * This is crucial for retaining our 'wcpdb_discount' data across page loads.
     */
    public function get_cart_item_from_session($cart_item, $values)
    {
        if (isset($values['wcpdb_discount'])) {
            $cart_item['wcpdb_discount'] = $values['wcpdb_discount'];
        }
        return $cart_item;
    }

    /**
     * Updates the display of a single cart item's price.
     * Shows only the discounted price (no strikethrough/original price) on cart and checkout pages.
     */
    public function update_cart_item_price_display($price, $cart_item, $cart_item_key)
    {
        // Get the current price that was set by apply_discount_to_cart_items
        $current_display_price = floatval($cart_item['data']->get_price());
        return wc_price($current_display_price);
    }

    /**
     * Updates the display of a cart item's subtotal.
     * Shows only the discounted subtotal (no strikethrough/original subtotal) on cart and checkout pages.
     */
    public function update_cart_item_subtotal_display($subtotal, $cart_item, $cart_item_key)
    {
        $current_display_price_per_item = floatval($cart_item['data']->get_price());
        $discounted_subtotal_display = $current_display_price_per_item * $cart_item['quantity'];
        return wc_price($discounted_subtotal_display);
    }

    /**
     * Calculates the discounted price based on original price and discount data.
     */
    private function calculate_discounted_price($original_price, $discount_data)
    {
        // Ensure original_price is a float to prevent TypeError
        $original_price = floatval($original_price);

        if (!isset($discount_data['type']) || !isset($discount_data['value'])) {
            return $original_price; // Return original if discount data is incomplete
        }

        $discounted_price = $original_price;
        if ($discount_data['type'] === 'percentage') {
            $discounted_price = $original_price - ($original_price * ($discount_data['value'] / 100));
        } elseif ($discount_data['type'] === 'fixed') {
            $discounted_price = $original_price - $discount_data['value'];
        }
        
        // Ensure price doesn't go below zero
        return max(0, $discounted_price);
    }

    /**
     * Applies discounts to cart items before total calculation.
     * This is the core logic for dynamically adjusting prices in the cart.
     */
    public function apply_discount_to_cart_items($cart)
    {
        foreach ($cart->get_cart() as $cart_item_key => $cart_item) {
            unset($cart->cart_contents[$cart_item_key]['wcpdb_discount']);
        }

        if (is_admin() && !defined('DOING_AJAX')) {
            return;
        }
    
        if (did_action('woocommerce_before_calculate_totals') > 1) {
            return;
        }
    
        foreach ($cart->get_cart() as $cart_item_key => $cart_item) {
            $product_id = $cart_item['variation_id'] ?: $cart_item['product_id'];
            $quantity = $cart_item['quantity'];

            $discount_data = $this->get_applicable_discount($product_id, $quantity);

            if ($discount_data && $discount_data['value'] > 0) {
                
                $cart->cart_contents[$cart_item_key]['wcpdb_discount'] = $discount_data;
                $original_price = floatval($cart_item['data']->get_price('edit'));
                $discounted_price = $this->calculate_discounted_price($original_price, $discount_data);
                $cart_item['data']->set_price($discounted_price);
            } else {
                
                // Remove discount if not valid (timer not active, global disabled, or product-level disabled)
                unset($cart->cart_contents[$cart_item_key]['wcpdb_discount']);
                $original_product = wc_get_product($product_id);
                if ($original_product) {
                    $cart_item['data']->set_price($original_product->get_price('edit'));
                }
                }
        }
    }

    public function ajax_get_discounted_price()
    {
        check_ajax_referer('wcpdb_ajax_nonce', 'nonce');

        $product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;
        $quantity   = isset($_POST['quantity']) ? intval($_POST['quantity']) : 1;

        if ($product_id <= 0 || $quantity <= 0) {
            wp_send_json_error('Invalid product ID or quantity.');
        }

        $product = wc_get_product($product_id);
        if (!$product) {
            wp_send_json_error('Product not found.');
        }

        // Ensure original_price is a float here before passing to calculate_discounted_price
        $original_price = floatval($product->get_regular_price()); 
        $discount_data = $this->get_applicable_discount($product_id, $quantity);

        if ($discount_data) {
            $discounted_price = $this->calculate_discounted_price($original_price, $discount_data);
            wp_send_json_success(
                [
                'discounted_price_html' => wc_price($discounted_price),
                'original_price_html'   => wc_price($original_price),
                'discount_applied'      => true
                ]
            );
        } else {
            wp_send_json_success(
                [
                'discounted_price_html' => wc_price($original_price),
                'original_price_html'   => '',
                'discount_applied'      => false
                ]
            );
        }
    }

    public function add_discount_data_to_cart_item($cart_item_data, $product_id, $variation_id)
    {
        $product_to_get_price_from = $variation_id ? wc_get_product($variation_id) : wc_get_product($product_id);
        $regular_price = $product_to_get_price_from ? floatval($product_to_get_price_from->get_regular_price()) : 0;

        $id_for_discount_check = $variation_id ? $variation_id : $product_id;

        $initial_discount_data = $this->get_applicable_discount($id_for_discount_check, 1); // Check for 1 quantity initially

        $cart_item_data['wcpdb_discount'] = [
            'base_price_before_discount' => $regular_price,
            'type'                       => $initial_discount_data ? $initial_discount_data['type'] : '',
            'value'                      => $initial_discount_data ? $initial_discount_data['value'] : 0,
            'quantity_matched'           => $initial_discount_data ? $initial_discount_data['quantity_matched'] : 0,
        ];

        return $cart_item_data;
    }

    public function get_discounted_price($price, $product)
    {
        return $price;
    }
}

new WCPDB_AddToCart();