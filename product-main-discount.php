<?php

/* 
Plugin Name: Bulk Discount Offer 
Description: It offers discount for customers who buy products in bulk.
Version: 1.0.0
Author: Sufyan Bawany
Text Domain: bulk-discount-offer
Domain Path: /languages
*/

if(!defined('ABSPATH')) {
    exit;//ye direct access allow nahi karega
}

// Define plugin constants
define('WCPDB_VERSION', '1.0.0');
define('WCPDB_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('WCPDB_PLUGIN_URL', plugin_dir_url(__FILE__));

// Check if WooCommerce is active
if (!in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {
    return;
}

// Load required files
require_once WCPDB_PLUGIN_DIR . 'includes/admin/class-wcpdb-settings-tab.php';
require_once WCPDB_PLUGIN_DIR . 'includes/admin/class-wcpdb-discount-tab.php';
require_once WCPDB_PLUGIN_DIR . 'public/class-wcpdb-display-fields.php';
require_once WCPDB_PLUGIN_DIR . 'public/class-wcpdb-addtocart.php';
?>