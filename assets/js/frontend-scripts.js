jQuery(function($) {
    // Countdown timer logic for .wcpdb-timer
    function initializeCountdowns() {
        $('.wcpdb-timer').each(function() {
            const $timer = $(this);
            const duration = $timer.data('duration');
            if (!duration) return;
            const [hours, minutes, seconds] = duration.split(':').map(Number);
            let totalSeconds = (hours * 3600) + (minutes * 60) + seconds;
            updateTimerDisplay($timer, totalSeconds);
            const timerInterval = setInterval(function() {
                totalSeconds--;
                if (totalSeconds <= 0) {
                    clearInterval(timerInterval);
                    $timer.text('00:00:00');
                    return;
                }
                updateTimerDisplay($timer, totalSeconds);
            }, 1000);
        });
    }
    function updateTimerDisplay($timer, totalSeconds) {
        const hours = Math.floor(totalSeconds / 3600);
        const minutes = Math.floor((totalSeconds % 3600) / 60);
        const seconds = totalSeconds % 60;
        const display = hours.toString().padStart(2, '0') + ':' +
                       minutes.toString().padStart(2, '0') + ':' +
                       seconds.toString().padStart(2, '0');
        $timer.text(display);
    }
    $(document).ready(function() {
        initializeCountdowns();
    });
}); 