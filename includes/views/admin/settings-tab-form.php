<?php
/**
 * View for the admin settings tab form.
 *
 * All variables are passed through from the `settings_tab_content` method.
 */

// Initialize timer_from and timer_to to avoid undefined variable warnings
$timer_from = isset($timer_from) ? $timer_from : (isset($global_settings['timer_from']) ? $global_settings['timer_from'] : '');
$timer_to   = isset($timer_to)   ? $timer_to   : (isset($global_settings['timer_to'])   ? $global_settings['timer_to']   : '');

// Security check to prevent direct access
if (!defined('ABSPATH')) {
    exit;
}
?>
 
<form method="post" action="options.php" class="wcpdb-discount-offer-form" enctype="multipart/form-data" autocomplete="off">
    <?php wp_nonce_field('wcpdb_save_settings_nonce'); ?>

    <h2><?php _e('Global Discount Offer Settings', 'product-discount-offer'); ?></h2>
    <table class="form-table">
        <tbody>
            <tr>
                <th scope="row"><label for="wcpdb_enable_global_settings"><?php _e('Enable Global Settings', 'product-discount-offer'); ?></label></th>
                <td>
                    <input type="checkbox" id="wcpdb_enable_global_settings" name="wcpdb_enable_global_settings" value="yes" <?php checked($enable_global_settings, 'yes'); ?> />
                    <p class="description"><?php _e('Enable to use and edit global settings below.', 'product-discount-offer'); ?></p>
                </td>
            </tr>
        </tbody>
        <tbody id="wcpdb_global_settings_fields" <?php if ($enable_global_settings !== 'yes') echo 'style="pointer-events:none;opacity:0.6;"'; ?>>
            <tr>
                <th scope="row"><?php _e('Discount Type', 'product-discount-offer'); ?></th>
                <td>
                    <fieldset>
                        <legend class="screen-reader-text"><span><?php _e('Discount Type', 'product-discount-offer'); ?></span></legend>
                        <label>
                            <input type="radio" name="wcpdb_discount_type" value="percentage" class="wcpdb-global-discount-type-radio" <?php checked($discount_type, 'percentage'); ?>>
                            <?php _e('Discount Percentage', 'product-discount-offer'); ?>
                        </label>
                        <br>
                        <label>
                            <input type="radio" name="wcpdb_discount_type" value="fixed" class="wcpdb-global-discount-type-radio" <?php checked($discount_type, 'fixed'); ?>>
                            <?php _e('Discount Amount', 'product-discount-offer'); ?>
                        </label>
                    </fieldset>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="wcpdb_enable_timer_global"><?php _e('Enable Timer', 'product-discount-offer'); ?></label></th>
                <td>
                    <input type="checkbox" id="wcpdb_enable_timer_global" name="wcpdb_enable_timer" value="yes" <?php checked($enable_timer, 'yes'); ?> />
                    <p class="description"><?php _e('Enable a countdown timer for all discount offers.', 'product-discount-offer'); ?></p>
                </td>
            </tr>
            <tr class="wcpdb-global-timer-fields" style="<?php echo $enable_timer === 'yes' ? '' : 'display:none;'; ?>">
                <th scope="row"><label for="wcpdb_timer_from_global"><?php _e('Timer From', 'product-discount-offer'); ?></label></th>
                <td>
                    <input type="date" id="wcpdb_timer_from_global" name="wcpdb_timer_from" value="<?php echo esc_attr($timer_from); ?>" class="wcpdb-input" placeholder="YYYY-MM-DD" autocomplete="off" />
                    <p class="description"><?php _e('Set the start date for the global timer.', 'product-discount-offer'); ?></p>
                </td>
            </tr>
            <tr class="wcpdb-global-timer-fields" style="<?php echo $enable_timer === 'yes' ? '' : 'display:none;'; ?>">
                <th scope="row"><label for="wcpdb_timer_to_global"><?php _e('Timer To', 'product-discount-offer'); ?></label></th>
                <td>
                    <input type="date" id="wcpdb_timer_to_global" name="wcpdb_timer_to" value="<?php echo esc_attr($timer_to); ?>" class="wcpdb-input" placeholder="YYYY-MM-DD" autocomplete="off" />
                    <p class="description"><?php _e('Set the end date for the global timer.', 'product-discount-offer'); ?></p>
                </td>
            </tr>
            <tr class="wcpdb-global-timer-fields" style="<?php echo $enable_timer === 'yes' ? '' : 'display:none;'; ?>">
                <th scope="row"><label for="wcpdb_timer_text_global"><?php _e('Timer Text', 'product-discount-offer'); ?></label></th>
                <td>
                    <input type="text" id="wcpdb_timer_text_global" name="wcpdb_timer_text" value="<?php echo esc_attr($timer_text); ?>" class="wcpdb-input" placeholder="<?php _e('Hurry! Offer ends in:', 'product-discount-offer'); ?>" />
                    <p class="description"><?php _e('Text to display before the countdown timer.', 'product-discount-offer'); ?></p>
                </td>
            </tr>
        </tbody>
    </table>

    <div id="wcpdb_discount_sections_container">
    <?php
    if (!empty($sections)) {
        foreach ($sections as $index => $section) {
            $section = WCPDB_Discount_Offer_Settings_Tab::get_discount_section_data($index, $section);
            ?>
            <div class="wcpdb-section">
                <h3>
                    <?php _e('Discount Offer', 'product-discount-offer'); ?>
                    <button type="button" class="button wcpdb-remove-section-button" title="<?php _e('Remove this offer', 'product-discount-offer'); ?>">&times;</button>
                </h3>
                <table class="form-table">
                    <tbody>
                        <tr>
                            <th scope="row"><label for="wcpdb_discount_quantity_<?php echo esc_attr($section['index']); ?>"><?php _e('Quantity', 'product-discount-offer'); ?></label></th>
                            <td>
                                <input type="number" id="wcpdb_discount_quantity_<?php echo esc_attr($section['index']); ?>" name="wcpdb_discount_sections[<?php echo esc_attr($section['index']); ?>][quantity]" value="<?php echo esc_attr($section['quantity']); ?>" class="wcpdb-input" placeholder="0" min="0" />
                                <p class="description"><?php _e('Enter the quantity for this discount offer (0 = ignore).', 'product-discount-offer'); ?></p>
                            </td>
                        </tr>
                        <tr class="wcpdb-discount-percent-group">
                            <th scope="row"><label for="wcpdb_discount_percentage_<?php echo esc_attr($section['index']); ?>"><?php _e('Discount Percentage', 'product-discount-offer'); ?></label></th>
                            <td>
                                <input type="number" id="wcpdb_discount_percentage_<?php echo esc_attr($section['index']); ?>" name="wcpdb_discount_sections[<?php echo esc_attr($section['index']); ?>][discount_percentage]" value="<?php echo esc_attr($section['discount_percentage']); ?>" class="wcpdb-input" placeholder="0" step="any" min="0" max="100" />
                                <p class="description"><?php _e('Discount in percentage. e.g., 10 for 10%.', 'product-discount-offer'); ?></p>
                            </td>
                        </tr>
                        <tr class="wcpdb-discount-fixed-group">
                            <th scope="row"><label for="wcpdb_discount_amount_<?php echo esc_attr($section['index']); ?>"><?php _e('Discount Amount', 'product-discount-offer'); ?></label></th>
                            <td>
                                <input type="number" id="wcpdb_discount_amount_<?php echo esc_attr($section['index']); ?>" name="wcpdb_discount_sections[<?php echo esc_attr($section['index']); ?>][discount_amount]" value="<?php echo esc_attr($section['discount_amount']); ?>" class="wcpdb-input" placeholder="0" step="any" min="0" />
                                <p class="description"><?php _e('Fixed discount amount.', 'product-discount-offer'); ?></p>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <?php
        }
    }
    ?>
    </div>

    <script type="text/template" id="wcpdb-section-template">
        <div class="wcpdb-section">
            <h3>
                <?php _e('Discount Offer', 'product-discount-offer'); ?>
                <button type="button" class="button wcpdb-remove-section-button" title="<?php _e('Remove this offer', 'product-discount-offer'); ?>">&times;</button>
            </h3>
            <table class="form-table">
                <tbody>
                    <tr>
                        <th scope="row"><label for="wcpdb_discount_quantity___INDEX__"><?php _e('Quantity', 'product-discount-offer'); ?></label></th>
                        <td>
                            <input type="number" id="wcpdb_discount_quantity___INDEX__" name="wcpdb_discount_sections[__INDEX__][quantity]" value="" class="wcpdb-input" placeholder="0" min="0" />
                            <p class="description"><?php _e('Enter the quantity for this discount offer (0 = ignore).', 'product-discount-offer'); ?></p>
                        </td>
                    </tr>
                    <tr class="wcpdb-discount-percent-group">
                        <th scope="row"><label for="wcpdb_discount_percentage___INDEX__"><?php _e('Discount Percentage', 'product-discount-offer'); ?></label></th>
                        <td>
                            <input type="number" id="wcpdb_discount_percentage___INDEX__" name="wcpdb_discount_sections[__INDEX__][discount_percentage]" value="" class="wcpdb-input" placeholder="0" step="any" min="0" max="100" />
                            <p class="description"><?php _e('Discount in percentage. e.g., 10 for 10%.', 'product-discount-offer'); ?></p>
                        </td>
                    </tr>
                    <tr class="wcpdb-discount-fixed-group">
                        <th scope="row"><label for="wcpdb_discount_amount___INDEX__"><?php _e('Discount Amount', 'product-discount-offer'); ?></label></th>
                        <td>
                            <input type="number" id="wcpdb_discount_amount___INDEX__" name="wcpdb_discount_sections[__INDEX__][discount_amount]" value="" class="wcpdb-input" placeholder="0" step="any" min="0" />
                            <p class="description"><?php _e('Fixed discount amount.', 'product-discount-offer'); ?></p>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </script>

    <p>
        <button type="button" class="button button-secondary" id="wcpdb_add_new_section_button"><?php _e('Add Another Discount Offer', 'product-discount-offer'); ?></button>
    </p>
    <p class="submit">
        <input type="submit" name="wcpdb_save_settings" class="button-primary" value="<?php _e('Save changes', 'product-discount-offer'); ?>"/>
    </p>
</form>

<?php wp_die();