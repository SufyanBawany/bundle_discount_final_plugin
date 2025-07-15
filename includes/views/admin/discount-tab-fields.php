<?php
/**
 * View for the discount fields in the product data tab.
 *
 * All variables are passed through from the `render_discount_fields` method.
 */
if (!defined('ABSPATH')) {
    exit;
}
global $post;
// Discount Type
woocommerce_wp_radio([
    'id'      => '_discount_type_1x',
    'label'   => __('Discount Type', 'woocommerce'),
    'options' => [
        'percentage' => __('Discount Percentage', 'woocommerce'),
        'fixed'      => __('Fixed Amount', 'woocommerce'),
    ],
    'value'   => get_post_meta(get_the_ID(), '_discount_type_1x', true),
]);
// Discount Percentage
woocommerce_wp_text_input([
    'id' => '_discount_percentage_1x',
    'label' => __('Discount Percentage', 'woocommerce'),
    'type' => 'number',
    'custom_attributes' => [
        'step' => 'any',
        'min' => '0',
        'max' => '100'
    ],
    'desc_tip' => true,
    'description' => __('Enter the discount percentage (0-100)', 'woocommerce'),
]);
// Fixed Amount
woocommerce_wp_text_input([
    'id' => '_discount_amount_1x',
    'label' => __('Fixed Amount', 'woocommerce'),
    'type' => 'number',
    'custom_attributes' => [
        'step' => 'any',
        'min' => '0'
    ],
    'desc_tip' => true,
    'description' => __('Enter the fixed discount amount', 'woocommerce'),
]);
// Timer Enabled
woocommerce_wp_checkbox([
    'id' => '_timer_enabled_1x',
    'label' => __('Enable Timer', 'woocommerce'),
    'desc_tip' => true,
    'description' => __('Enable countdown timer for this section', 'woocommerce'),
]);
?>
<div class="wcpdb-product-timer-fields">
<?php
woocommerce_wp_text_input([
    'id' => '_timer_from_1x',
    'label' => __('Timer From', 'woocommerce'),
    'desc_tip' => true,
    'description' => __('Set the start date for the timer (YYYY-MM-DD)', 'woocommerce'),
    'placeholder' => 'YYYY-MM-DD',
    'type' => 'date',
    'class' => 'wcpdb-input',
    'autocomplete' => 'off',
    'value' => get_post_meta(get_the_ID(), '_timer_from_1x', true),
]);
woocommerce_wp_text_input([
    'id' => '_timer_to_1x',
    'label' => __('Timer To', 'woocommerce'),
    'desc_tip' => true,
    'description' => __('Set the end date for the timer (YYYY-MM-DD)', 'woocommerce'),
    'placeholder' => 'YYYY-MM-DD',
    'type' => 'date',
    'class' => 'wcpdb-input',
    'autocomplete' => 'off',
    'value' => get_post_meta(get_the_ID(), '_timer_to_1x', true),
]);
woocommerce_wp_text_input([
    'id' => '_timer_text_1x',
    'label' => __('Countdown Timer Text', 'woocommerce'),
    'desc_tip' => true,
    'description' => __('Text to display before the countdown timer', 'woocommerce'),
    'placeholder' => '',
    'value' => get_post_meta(get_the_ID(), '_timer_text_1x', true),
]);
?>
</div>
<h3><?php _e('Product Bundle Section', 'woocommerce'); ?></h3>
<?php
woocommerce_wp_text_input([
    'id' => '_bundle_quantity_1x',
    'label' => __('Quantity of bundle', 'woocommerce'),
    'type' => 'number',
    'custom_attributes' => [
        'step' => '1',
    ],
    'desc_tip' => true,
    'description' => __('This is the quantity of bundle products', 'woocommerce'),
    'value' => get_post_meta(get_the_ID(), '_bundle_quantity_1x', true)
]);
$additional_sections = get_post_meta($post->ID, '_additional_sections', true);
?>
<div id="bundle_sections_container">
<?php
if (!empty($additional_sections) && is_array($additional_sections)) {
    $sectionCount = 0;
    foreach ($additional_sections as $section) {
        $sectionCount++;
        $v = isset($section['discount_type']) ? esc_attr($section['discount_type']) : 'percentage';
        $q = isset($section['quantity']) ? esc_attr($section['quantity']) : '';
        $dp = isset($section['discount_percentage']) ? esc_attr($section['discount_percentage']) : '';
        $da = isset($section['discount_amount']) ? esc_attr($section['discount_amount']) : '';
        ?>
        <div class="bundle-section" data-section-id="<?php echo $sectionCount; ?>">
            <hr class="bundle-section-hr">
            <p class="form-field percentage-field" style="display:<?php echo ($v === 'percentage') ? 'block' : 'none'; ?>;">
                <label for="discount_percentage_<?php echo $sectionCount; ?>">Discount Percentage (%)</label>
                <input type="number" id="discount_percentage_<?php echo $sectionCount; ?>" name="discount_percentage[]" step="any" min="0" max="100" value="<?php echo $dp; ?>" />
                <span class="description">Enter the discount percentage (0-100)</span>
            </p>
            <p class="form-field fixed-field" style="display:<?php echo ($v === 'fixed') ? 'block' : 'none'; ?>;">
                <label for="discount_amount_<?php echo $sectionCount; ?>">Discount Amount</label>
                <input type="number" id="discount_amount_<?php echo $sectionCount; ?>" name="discount_amount[]" step="any" min="0" value="<?php echo $da; ?>" />
                <span class="description">Enter the fixed discount amount</span>
            </p>
            <p class="form-field bundle-form-field"><label for="bundle_quantity_<?php echo $sectionCount; ?>">Quantity</label>
                <input type="number" id="bundle_quantity_<?php echo $sectionCount; ?>" name="bundle_quantity[]" value="<?php echo $q; ?>" min="1" />
                <span class="description">This is the quantity of bundle products</span></p>
            <button type="button" class="button remove-section-btn" style="margin-bottom:10px;">Remove Section</button>
        </div>
        <?php
    }
}
?>
</div>
<p>
    <button type="button" class="button" id="add_bundle_section"><?php _e('Add New Section', 'woocommerce'); ?></button>
</p>
<?php
do_action('wcpdb_after_timer_text');
if (!empty($additional_sections)) {
    echo '<script>
        window.wcpdbSavedSections = ' . json_encode($additional_sections) . ';
    </script>';
} 