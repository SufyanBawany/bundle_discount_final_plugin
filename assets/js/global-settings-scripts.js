jQuery(document).ready(function($) {
    function updateGlobalSettingsState() {
        var enabled = $('#wcpdb_enable_global_settings').is(':checked');
        var $fields = $('#wcpdb_global_settings_fields, #wcpdb_discount_sections_container, p.toolbar');
        $fields.css({
            'pointer-events': enabled ? 'auto' : 'none',
            'opacity': enabled ? '1' : '0.6'
        });
    }
    $('#wcpdb_enable_global_settings').on('change', updateGlobalSettingsState);
    updateGlobalSettingsState();

    function toggleTimerFields() {
        var enabled = $('#wcpdb_enable_timer_global').is(':checked');
        $('.wcpdb-global-timer-fields').toggle(enabled);
    }
    $('#wcpdb_enable_timer_global').on('change', toggleTimerFields);
    toggleTimerFields();

    function toggleDiscountFields() {
        var discountType = $('.wcpdb-global-discount-type-radio:checked').val();
        if (discountType === 'percentage') {
            $('.wcpdb-discount-percent-group').show().find('input').prop('disabled', false);
            $('.wcpdb-discount-fixed-group').hide().find('input').prop('disabled', true);
        } else {
            $('.wcpdb-discount-percent-group').hide().find('input').prop('disabled', true);
            $('.wcpdb-discount-fixed-group').show().find('input').prop('disabled', false);
        }
    }
    $(document).on('change', '.wcpdb-global-discount-type-radio', toggleDiscountFields);
    toggleDiscountFields();

    var sectionTemplate = '';
    if ($('#wcpdb_discount_sections_container .wcpdb-section').length > 0) {
        sectionTemplate = $('#wcpdb_discount_sections_container .wcpdb-section:first')[0].outerHTML;
    }
    $('#wcpdb_add_new_section_button').on('click', function() {
        var $container = $('#wcpdb_discount_sections_container');
        var $sections = $container.find('.wcpdb-section');
        var $clone;
        var newIndex = Date.now();
        if ($sections.length > 0) {
            $clone = $sections.last().clone();
        } else {
            var template = $('#wcpdb-section-template').html();
            if (!template) return;
            $clone = $(template.replace(/__INDEX__/g, newIndex));
        }
        $clone.find('input, select, textarea').each(function() {
            var $input = $(this);
            if ($input.attr('name')) {
                $input.attr('name', $input.attr('name').replace(/\[\d+\]/, '[' + newIndex + ']').replace(/__INDEX__/g, newIndex));
            }
            if ($input.attr('id')) {
                $input.attr('id', $input.attr('id').replace(/_\d+/, '_' + newIndex).replace(/__INDEX__/g, newIndex));
            }
            if ($input.is(':checkbox') || $input.is(':radio')) {
                $input.prop('checked', false);
            } else {
                $input.val('');
            }
        });
        $clone.find('label').each(function() {
            var $label = $(this);
            if ($label.attr('for')) {
                $label.attr('for', $label.attr('for').replace(/_\d+/, '_' + newIndex).replace(/__INDEX__/g, newIndex));
            }
        });
        $clone.find('.wcpdb-section-index').text('');
        $container.append($clone);
        toggleDiscountFields();
    });
    $(document).on('click', '.wcpdb-remove-section-button', function() {
        $(this).closest('.wcpdb-section').remove();
    });
});
function initializeDateCountdowns() {
    jQuery('.wcpdb-timer.countdown').each(function() {
        var $timer = jQuery(this);
        var fromDateStr = $timer.data('timer-from');
        var toDateStr = $timer.data('timer-to');
        if (!toDateStr) return;
        var now = new Date();
        var fromDate = fromDateStr ? new Date(fromDateStr.replace(/-/g, '/')) : null;
        var toDate = new Date(toDateStr.replace(/-/g, '/'));
        if (fromDate && now < fromDate) {
            $timer.closest('.wcpdb-offer-timer-info').hide();
            return;
        }
        if (now >= toDate) {
            $timer.closest('.wcpdb-offer-timer-info').hide();
            return;
        }
        function updateCountdown() {
            var now = new Date();
            if (now < fromDate) {
                $timer.closest('.wcpdb-offer-timer-info').hide();
                return;
            }
            var diff = toDate - now;
            if (diff <= 0) {
                $timer.closest('.wcpdb-offer-timer-info').hide();
                clearInterval(timerInterval);
                return;
            }
            var totalSeconds = Math.floor(diff / 1000);
            var days = Math.floor(totalSeconds / 86400);
            var hours = Math.floor((totalSeconds % 86400) / 3600);
            var minutes = Math.floor((totalSeconds % 3600) / 60);
            var seconds = totalSeconds % 60;
            var dayLabel = days === 1 ? 'Day' : 'Days';
            var hourLabel = hours === 1 ? 'Hr' : 'Hrs';
            var minLabel = 'Mins';
            var secLabel = 'Secs';
            var html = '<div class="wcpdb-global-countdown">' +
                '<div class="wcpdb-timer-box"><span class="wcpdb-timer-num">' + days + '</span><span class="wcpdb-timer-label">' + dayLabel + '</span></div>' +
                '<div class="wcpdb-timer-box"><span class="wcpdb-timer-num">' + (hours < 10 ? '0' : '') + hours + '</span><span class="wcpdb-timer-label">' + hourLabel + '</span></div>' +
                '<div class="wcpdb-timer-box"><span class="wcpdb-timer-num">' + (minutes < 10 ? '0' : '') + minutes + '</span><span class="wcpdb-timer-label">' + minLabel + '</span></div>' +
                '<div class="wcpdb-timer-box"><span class="wcpdb-timer-num">' + (seconds < 10 ? '0' : '') + seconds + '</span><span class="wcpdb-timer-label">' + secLabel + '</span></div>' +
                '</div>';
            $timer.html(html);
        }
        updateCountdown();
        var timerInterval = setInterval(updateCountdown, 1000);
    });
}
jQuery(document).ready(function() {
    initializeDateCountdowns();
});