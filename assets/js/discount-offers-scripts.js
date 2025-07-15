jQuery(function ($) {
    // Main Discount Type toggle
    function toggleMainDiscount() {
        var v = $('input[name="_discount_type_1x"]:checked').val();
        $('#_discount_percentage_1x').closest('p.form-field').toggle(v === 'percentage');
        $('#_discount_amount_1x').closest('p.form-field').toggle(v === 'fixed');
    }
    toggleMainDiscount();
    $('input[name="_discount_type_1x"]').on('change', toggleMainDiscount);

    // Top fields apply to all
    function applyTopToAll() {
        var v = $('input[name="_discount_type_1x"]:checked').val(), tc = $('#_timer_enabled_1x').is(':checked');
        $('.percentage-field').toggle(v === 'percentage');
        $('.fixed-field').toggle(v === 'fixed');
        $('.timer-duration-field').toggle(tc).find('input').prop('disabled', !tc).val(tc ? $('.timer-duration-field input').val() : '');
    }
    $('input[name="_discount_type_1x"]').on('change', applyTopToAll);
    $('#_timer_enabled_1x').on('change', applyTopToAll);
    applyTopToAll();

    // Add new bundle section
    let sectionCount = 0;
    function addSection(d = {}) {
        sectionCount++;
        var v = d.discount_type || $('input[name="_discount_type_1x"]:checked').val() || 'percentage';
        var q = d.quantity || '', dp = d.discount_percentage || 0, da = d.discount_amount || 0;
        var sec = `<div class="bundle-section" data-section-id="${sectionCount}">
            <hr class="bundle-section-hr">
            <p class="form-field percentage-field" style="display:${v === 'percentage' ? 'block' : 'none'};">
                <label for="discount_percentage_${sectionCount}">Discount Percentage (%)</label>
                <input type="number" id="discount_percentage_${sectionCount}" name="discount_percentage[]" step="any" min="0" max="100" value="${dp}" />
                <span class="description">Enter the discount percentage (0-100)</span>
            </p>
            <p class="form-field fixed-field" style="display:${v === 'fixed' ? 'block' : 'none'};">
                <label for="discount_amount_${sectionCount}">Discount Amount</label>
                <input type="number" id="discount_amount_${sectionCount}" name="discount_amount[]" step="any" min="0" value="${da}" />
                <span class="description">Enter the fixed discount amount</span>
            </p>
            <p class="form-field bundle-form-field"><label for="bundle_quantity_${sectionCount}">Quantity</label>
                <input type="number" id="bundle_quantity_${sectionCount}" name="bundle_quantity[]" value="${q}" min="1" />
                <span class="description">This is the quantity of bundle products</span></p>
            <button type="button" class="button remove-section-btn" style="margin-bottom:10px;">Remove Section</button>
            </div>`;
        $('#bundle_sections_container').append(sec);
        initDynamic();
    }
    $('#add_bundle_section').on('click', function () { addSection(); });
    $(document).on('click', '.remove-section-btn', function () { $(this).closest('.bundle-section').remove(); });

    // Tab visibility
    var $cb = $('#_enable_discount_offers'), $tab = $('li.discount_offers_tab'), $panel = $('#discount_offers_data');
    function toggleTab() {
        $tab.toggle($cb.is(':checked'));
        if (!$cb.is(':checked') && $panel.is(':visible')) {
            $('.woocommerce_options_panel').hide();
            $('#general_product_data').show();
        }
    }
    toggleTab();
    $cb.on('change', toggleTab);

    // 1x section fields (minimal, just call main toggles)
    function init1xFields() {
        var $r = $('input[type="radio"][name="_discount_type_1x"]'),
            $pf = $('#_discount_percentage_1x').closest('p.form-field,.percentage-field'),
            $ff = $('#_discount_amount_1x').closest('p.form-field,.fixed-field'),
            $tc = $('#_timer_enabled_1x'), $td = $('#_timer_duration_1x'),
            $tdf = $td.closest('p.form-field,.timer-duration-field')
        function tf() { $pf.toggle($r.filter('[value="percentage"]').is(':checked')); $ff.toggle($r.filter('[value="fixed"]').is(':checked')); }
        function ttd() { $td.prop('disabled', !$tc.is(':checked')); $tdf.toggle($tc.is(':checked')); if (!$tc.is(':checked')) $td.val(''); }
        tf(); ttd();
        $r.on('change', tf); $tc.on('change', ttd);
    }
    init1xFields();

    // Timer fields show/hide logic for product-level
    function toggleProductTimerFields() {
        var enabled = $('#_timer_enabled_1x').is(':checked');
        $('.wcpdb-product-timer-fields').toggle(enabled);
        $('#_timer_from_1x').prop('disabled', !enabled);
        $('#_timer_to_1x').prop('disabled', !enabled);
        $('#_timer_text_1x').prop('disabled', !enabled);
    }
    $('#_timer_enabled_1x').on('change', toggleProductTimerFields);
    toggleProductTimerFields();
});