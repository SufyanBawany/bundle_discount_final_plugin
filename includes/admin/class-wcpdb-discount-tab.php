<?php

if (!defined('ABSPATH')) {
    exit;
}
class DiscountProductTab
{
    public function __construct()
    {
        add_filter('product_type_options', [$this, 'add_enable_discount_checkbox']);
        add_action('save_post_product', [$this, 'save_enable_discount_checkbox']);
        add_filter('woocommerce_product_data_tabs', [$this, 'maybe_add_discount_tab']);
        add_action('woocommerce_product_data_panels', [$this, 'output_discount_tab_content']);
        add_action('wcpdb_render_discount_fields', [$this, 'render_discount_fields']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_scripts']);
    }

    public function enqueue_admin_scripts($hook)
    {
        if ('post.php' !== $hook || get_post_type() !== 'product') {
            return;
        }
        wp_enqueue_script(
            'wcpdb-admin-scripts',
            WCPDB_PLUGIN_URL . 'assets/js/discount-offers-scripts.js',
            array('jquery'),
            '1.0.0',
            true
        );
    }

    public function add_enable_discount_checkbox($options)
    {
        $options['enable_discount_offers'] = array(
            'id'            => '_enable_discount_offers',
            'wrapper_class' => '',
            'label'         => __('Enable Discount Offers', 'woocommerce'),
            'description'   => __('Enable this to show discount tab for special offers.', 'woocommerce'),
            'default'       => 'no',
        );
        return $options;
    }

    public function save_enable_discount_checkbox($post_id)
    {
        if (!isset($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], 'update-post_' . $post_id)) {
            return;
        }
        if (!current_user_can('edit_product', $post_id)) {
            return;
        }
        $enabled = isset($_POST['_enable_discount_offers']) ? 'yes' : 'no';
        update_post_meta($post_id, '_enable_discount_offers', $enabled);
        update_post_meta($post_id, '_bundle_heading_1x', substr(sanitize_text_field($_POST['_bundle_heading_1x'] ?? ''), 0, 15));
        update_post_meta($post_id, '_bundle_quantity_1x', intval($_POST['_bundle_quantity_1x'] ?? 1));
        update_post_meta($post_id, '_discount_type_1x', sanitize_text_field($_POST['_discount_type_1x'] ?? 'percentage'));
        update_post_meta($post_id, '_discount_percentage_1x', floatval($_POST['_discount_percentage_1x'] ?? 20));
        update_post_meta($post_id, '_discount_amount_1x', floatval($_POST['_discount_amount_1x'] ?? 0));
        update_post_meta($post_id, '_timer_enabled_1x', isset($_POST['_timer_enabled_1x']) ? 'yes' : 'no');
        update_post_meta($post_id, '_timer_from_1x', sanitize_text_field($_POST['_timer_from_1x'] ?? ''));
        update_post_meta($post_id, '_timer_to_1x', sanitize_text_field($_POST['_timer_to_1x'] ?? ''));
        update_post_meta($post_id, '_timer_text_1x', sanitize_text_field($_POST['_timer_text_1x'] ?? 'Offer expires in:'));
        $sections_data = array();
        $quantities = isset($_POST['bundle_quantity']) ? $_POST['bundle_quantity'] : [];
        $percentages = isset($_POST['discount_percentage']) ? $_POST['discount_percentage'] : [];
        $amounts = isset($_POST['discount_amount']) ? $_POST['discount_amount'] : [];
        $types = isset($_POST['_discount_type']) ? $_POST['_discount_type'] : [];
        $timer_enabled = isset($_POST['timer_enabled']) ? $_POST['timer_enabled'] : [];
        $timer_from = isset($_POST['timer_from']) ? $_POST['timer_from'] : [];
        $timer_to = isset($_POST['timer_to']) ? $_POST['timer_to'] : [];
        $timer_text = isset($_POST['timer_text']) ? $_POST['timer_text'] : [];
        $max = max(count($quantities), count($percentages), count($amounts));
        for ($i = 0; $i < $max; $i++) {
            if (empty($quantities[$i]) || (empty($percentages[$i]) && empty($amounts[$i]))) continue;
            $section_data = array(
                'quantity' => intval($quantities[$i]),
                'discount_type' => isset($types[$i]) ? sanitize_text_field($types[$i]) : 'percentage',
                'discount_percentage' => isset($percentages[$i]) ? floatval($percentages[$i]) : 0,
                'discount_amount' => isset($amounts[$i]) ? floatval($amounts[$i]) : 0,
                'timer_enabled' => isset($timer_enabled[$i]) ? 'yes' : 'no',
                'timer_from' => isset($timer_from[$i]) ? sanitize_text_field($timer_from[$i]) : '',
                'timer_to' => isset($timer_to[$i]) ? sanitize_text_field($timer_to[$i]) : '',
                'timer_text' => isset($timer_text[$i]) ? sanitize_text_field($timer_text[$i]) : '',
            );
            $sections_data[] = $section_data;
        }
        if (!empty($sections_data)) {
            update_post_meta($post_id, '_additional_sections', $sections_data);
        } else {
            delete_post_meta($post_id, '_additional_sections');
        }
        $this->cleanup_old_section_meta($post_id);
    }

    private function cleanup_old_section_meta($post_id)
    {
        global $wpdb;
        $meta_keys = $wpdb->get_col(
            $wpdb->prepare(
                "SELECT meta_key FROM $wpdb->postmeta 
            WHERE post_id = %d 
            AND meta_key REGEXP '^_(bundle_heading|bundle_quantity|discount_type|discount_percentage|discount_amount|timer_enabled|timer_duration|timer_text)_[0-9]+$'",
                $post_id
            )
        );
        foreach ($meta_keys as $meta_key) {
            delete_post_meta($post_id, $meta_key);
        }
    }

    public function maybe_add_discount_tab($tabs)
    {
        $tabs['discount_offers'] = array(
            'label'    => __('Discount Offers', 'woocommerce'),
            'target'   => 'discount_offers_data',
            'class'    => array('discount_offers_tab'),
            'priority' => 90,
        );
        return $tabs;
    }

    public function output_discount_tab_content()
    {
        ?>
        <div id="discount_offers_data" class="panel woocommerce_options_panel">
            <div class="options_group">
                <?php do_action('wcpdb_render_discount_fields'); ?>
            </div>
        </div>
        <?php
    }

    public function render_discount_fields()
    {
        include_once WCPDB_PLUGIN_DIR . 'includes/views/admin/discount-tab-fields.php';
    }
}

new DiscountProductTab();