<?php
function meiko_school_system_shortcode() {
    $output = '';

    global $wpdb;
    $user_id = get_current_user_id();
    $player_table = $wpdb->prefix . "mk_players";
    $player = $wpdb->get_row($wpdb->prepare("SELECT * FROM $player_table WHERE user_id = %d", $user_id));

    if (!$player) {
        return "Error: Player data not found.";
    }

    $current_time = current_time('mysql');

    if (isset($player->last_study_end_time) && $current_time < $player->last_study_end_time) {
        $cooldown_remaining = strtotime($player->last_study_end_time) - strtotime($current_time);
        $hours = (int)floor($cooldown_remaining / 3600);
        $minutes = (int)floor(($cooldown_remaining / 60) % 60);
        $seconds = (int)($cooldown_remaining % 60);

        $output .= "<div>You're still studying! Wait for {$hours}h {$minutes}m {$seconds}s to study again.</div>";
        return $output;
    }

    if (isset($_POST['start_study'])) {
        $study_hours = (int)$_POST['study_hours'];
        $study_end_time = date('Y-m-d H:i:s', strtotime("+$study_hours hours", strtotime($current_time)));

        $money_required = 8000 * $study_hours;
        $moves_required = 100 * $study_hours;
        $food_required = 100 * $study_hours;
        $education_gain = 50 * $study_hours;

        if ($player->money >= $money_required && $player->moves >= $moves_required && $player->food >= $food_required) {
            $wpdb->update($player_table, 
                array(
                    'money' => $player->money - $money_required,
                    'moves' => $player->moves - $moves_required,
                    'food' => $player->food - $food_required,
                    'education' => $player->education + $education_gain,
                    'last_study_end_time' => $study_end_time  // Update this instead of last_study_time and study_duration
                ),
                array('user_id' => $user_id)
            );
            $output .= "You are studying for $study_hours hours!";
        } else {
            $output .= '<div>Not enough resources to study for this duration.</div>';
        }
    }

    $education_shortcode_output = do_shortcode('[user_detail field="education"]');
    $output .= '<div>Your current ' . $education_shortcode_output . '</div>';

    $output .= '<form class="meiko-study" method="post" action="">
        <label for="study_hours">Choose hours of study:</label>
        <select name="study_hours">';

    for ($i = 1; $i <= 6; $i++) {
        $output .= '<option value="' . $i . '">' . $i . ' hours</option>';
    }

    $output .= '</select>
        <button type="submit" name="start_study">Start Studying</button>
    </form>';

    return $output;
}
?>