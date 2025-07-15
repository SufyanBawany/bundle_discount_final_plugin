<?php
if (!defined('ABSPATH')) {
    exit;
}
if (!function_exists('esc_html')) {
    function esc_html($text) {
        return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
    }
}
if (!function_exists('current_time')) {
    function current_time($type) {
        return time();
    }
}
?>
<div class="wcpdb-offer-wrapper">
    <?php
    $timer_active = false;
    $timer_text = '';
    $timer_to = '';
    $timer_from = '';
    if (
        $source === 'custom' &&
        !empty($sections[0]['timer_enabled']) &&
        $sections[0]['timer_enabled'] === 'yes' &&
        !empty($sections[0]['timer_to'])
    ) {
        $timer_to = esc_html($sections[0]['timer_to']) . ' 23:59:59';
        $timer_from = isset($sections[0]['timer_from']) ? esc_html($sections[0]['timer_from']) : '';
        $timer_text = isset($sections[0]['timer_text']) ? esc_html($sections[0]['timer_text']) : '';
        $now = date('Y-m-d H:i:s', (function_exists('current_time') ? current_time('timestamp') : time()));
        $timer_from_full = $timer_from . ' 00:00:00';
        $timer_to_full = $sections[0]['timer_to'] . ' 23:59:59';
        if ($now >= $timer_from_full && $now <= $timer_to_full) {
            $timer_active = true;
        }
    }
    if (
        $source === 'global' &&
        !empty($global_settings['global']['enable_timer']) &&
        !empty($global_settings['global']['timer_to'])
    ) {
        $timer_to = esc_html($global_settings['global']['timer_to']) . ' 23:59:59';
        $timer_from = isset($global_settings['global']['timer_from']) ? esc_html($global_settings['global']['timer_from']) : '';
        $timer_text = isset($global_settings['global']['timer_text']) ? esc_html($global_settings['global']['timer_text']) : '';
        $now = date('Y-m-d H:i:s', (function_exists('current_time') ? current_time('timestamp') : time()));
        $timer_from_full = $timer_from . ' 00:00:00';
        $timer_to_full = $global_settings['global']['timer_to'] . ' 23:59:59';
        if ($now >= $timer_from_full && $now <= $timer_to_full) {
            $timer_active = true;
        }
    }
    ?>
    <div class="onsale">
        <?php if ($timer_active && $timer_text): ?>
            <?php echo $timer_text . ' '; ?>
        <?php endif; ?>
        <span class="wcpdb-timer countdown"
            data-timer-to="<?php echo $timer_to; ?>"
            data-timer-from="<?php echo $timer_from ? $timer_from . ' 00:00:00' : ''; ?>">
        </span>
    </div>
    <?php if ($source === 'global') {
        $timer_enabled = $global_settings['global']['enable_timer'] ?? 'no';
        if ($timer_enabled !== 'yes') {
            $sections = [];
        }
    }
    if ($source === 'custom' && !empty($sections)) {
        $main_timer_enabled = isset($sections[0]['timer_enabled']) && $sections[0]['timer_enabled'] === 'yes';
        $main_timer_from = isset($sections[0]['timer_from']) ? $sections[0]['timer_from'] : '';
        $main_timer_to = isset($sections[0]['timer_to']) ? $sections[0]['timer_to'] : '';
        $now = date('Y-m-d H:i:s', (function_exists('current_time') ? current_time('timestamp') : time()));
        if ($main_timer_enabled !== true) {
            $sections = [];
        } elseif ($main_timer_enabled && $main_timer_from && $main_timer_to) {
            $main_timer_from_full = $main_timer_from . ' 00:00:00';
            $main_timer_to_full = $main_timer_to . ' 23:59:59';
            if ($now < $main_timer_from_full || $now > $main_timer_to_full) {
                $sections = [];
            }
        }
    }
    ?>
    <?php if (!empty($sections)) : ?>
        <table class="shop_table woocommerce-table woocommerce-cart-form__contents">
            <thead class="woocommerce-cart-form__header">
                <tr class="woocommerce-cart-form__row">
                    <th class="woocommerce-cart-form__cell"><?php _e('Item', 'product-discount-offer'); ?></th>
                    <th class="woocommerce-cart-form__cell"><?php _e('Qty', 'product-discount-offer'); ?></th>
                    <th class="woocommerce-cart-form__cell"><?php _e('Disc', 'product-discount-offer'); ?></th>
                    <th class="woocommerce-cart-form__cell"><?php _e('Price', 'product-discount-offer'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($sections as $section) :
                    $quantity = isset($section['quantity']) ? intval($section['quantity']) : 1;
                    if ($quantity <= 0) continue;
                    $product_price = floatval($product->get_price());
                    $original_price = $product_price * $quantity;
                    $discount_value = 0;
                    $discount_badge_text = '';
                    if (!empty($section['discount_percentage'])) {
                        $discount_value = floatval($section['discount_percentage']);
                        $discounted_unit_price = $product_price - ($product_price * ($discount_value / 100));
                        $discount_badge_text = $discount_value . '%';
                    } elseif (!empty($section['discount_amount'])) {
                        $discount_value = floatval($section['discount_amount']);
                        $discounted_unit_price = $product_price - $discount_value;
                        $discount_badge_text = 'Rs.' . number_format($discount_value, 0);
                    } else {
                        $discounted_unit_price = $product_price;
                        $discount_badge_text = '';
                    }
                    $discounted_price = $discounted_unit_price * $quantity;
                    ?>
                <tr class="woocommerce-cart-form__row">
                    <td class="woocommerce-cart-form__cell">
                        <?php echo $product->get_image('woocommerce_thumbnail'); ?>
                    </td>
                    <td class="woocommerce-cart-form__cell">
                        x<?php echo esc_html($quantity); ?>
                    </td>
                    <td class="woocommerce-cart-form__cell">
                        <span class="wcpdb-discount-badge">-<?php echo esc_html($discount_badge_text); ?></span>
                    </td>
                    <td class="woocommerce-cart-form__cell">
                        <del><?php echo WCPDB_Display_Fields::format_pkr_price($original_price); ?></del><br>
                        <ins><?php echo WCPDB_Display_Fields::format_pkr_price($discounted_price); ?></ins>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div> 