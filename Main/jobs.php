<?php

function meiko_jobs_shortcode() {
    global $wpdb;
    $mk_jobs = $wpdb->get_results("SELECT * FROM " . $wpdb->prefix . "mk_jobs");
    $user_id = get_current_user_id();
    $player = $wpdb->get_row($wpdb->prepare("SELECT * FROM " . $wpdb->prefix . "mk_players WHERE user_id = %d", $user_id));

    $player_job = $wpdb->get_row($wpdb->prepare("SELECT * FROM " . $wpdb->prefix . "mk_jobs WHERE name = %s", $player->current_job));
    $output = '<div>Your current job: ' . $player->current_job . ' - Maximum bank balance: ' . $player_job->max_bank_balance . '</div>';

    $output .= '<form class="meiko-jobs" method="post" action="">
        <select name="selected_job">';
    foreach ($mk_jobs as $job) {
        $output .= '<option value="' . $job->id . '">' . $job->name . ' - Required Education: ' . $job->required_education . ' - 10 Min-Income: ' . $job->income . ' </option>';
    }
    $output .= '</select><br><br>
        <button type="submit" name="join_job">Join Job</button>
        <button type="submit" name="quit_job">Quit Job</button>
    </form>';

    if (isset($_POST['join_job'])) {
        $selected_job = $wpdb->get_row($wpdb->prepare("SELECT * FROM " . $wpdb->prefix . "mk_jobs WHERE id = %d", $_POST['selected_job']));
        if ($player->education >= $selected_job->required_education) {
            $wpdb->update($wpdb->prefix . "mk_players", array('current_job' => $selected_job->name), array('user_id' => $user_id));
            $output .= '<div>Successfully joined ' . $selected_job->name . '</div>';
        } else {
            $output .= '<div>Not enough education to join this job.</div>';
        }
        $output .= "<script> window.location = window.location.href + '?meiko_reload=true'; </script>";
    }

    if (isset($_POST['quit_job'])) {
        $wpdb->update($wpdb->prefix . "mk_players", array('current_job' => 'Unemployed'), array('user_id' => $user_id));
        $output .= '<div>You are now unemployed.</div>';
        $output .= "<script> window.location = window.location.href + '?meiko_reload=true'; </script>";

    }

    return $output;
}

// The function that handles the AJAX request
function update_player_income() {
    global $wpdb;

    // Get all players and their current jobs
    $players = $wpdb->get_results("SELECT * FROM " . $wpdb->prefix . "mk_players");

    // Loop through each player
    foreach ($players as $player) {
        // Get the job details for this player's current job
        $job = $wpdb->get_row($wpdb->prepare("SELECT * FROM " . $wpdb->prefix . "mk_jobs WHERE name = %s", $player->current_job));

        // Calculate the new money amount for the player
        $new_money_amount = $player->money + $job->income;

        // Update the player's money in the mk_players table
        $wpdb->update($wpdb->prefix . "mk_players", array('money' => $new_money_amount), array('user_id' => $player->user_id));
    }

    // Send a response back to the JavaScript
    echo 'Player incomes updated successfully!';
    wp_die(); // All ajax handlers should die when finished
}
?>