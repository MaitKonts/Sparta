var serverTime = serverData.server_time;
var interval = 5 * 60;  // 5 minutes in seconds
var timeRemaining = interval - (Math.floor(serverTime) % interval);

function updateTimerDisplay() {
    var minutes = Math.floor(timeRemaining / 60);
    var seconds = timeRemaining % 60;

    if (document.getElementById('timerDisplay')) {
        document.getElementById('timerDisplay').textContent = minutes + ":" + (seconds < 10 ? "0" : "") + seconds;
    }
}

setInterval(function() {
    updateTimerDisplay();
    timeRemaining--;

    if (timeRemaining <= 0) {
        timeRemaining = interval;

        // Send AJAX request to update all game logic
        jQuery.post(MeikoAjax.ajax_url, { action: 'update_moves' }, function(response) { console.log(response); });
        jQuery.post(MeikoAjax.ajax_url, { action: 'meiko_update_stock_prices' }, function(response) { console.log(response); });
        jQuery.post(MeikoAjax.ajax_url, { action: 'update_scores' }, function(response) { console.log(response); });
        jQuery.post(MeikoAjax.ajax_url, { action: 'update_faction_scores' }, function(response) { console.log(response); });
        jQuery.post(MeikoAjax.ajax_url, { action: 'meiko_calculate_player_stats_in_faction' }, function(response) { console.log(response); });
        jQuery.post(MeikoAjax.ajax_url, { action: 'update_player_income' }, function(response) { console.log(response); });
        jQuery.post(MeikoAjax.ajax_url, { action: 'update_faction_max_equipment' }, function(response) { console.log(response); });
    }
}, 1000);

