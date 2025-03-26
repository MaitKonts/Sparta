<?php
/* ---- User Stats ---- */
function display_user_detail($atts) {
    $atts = shortcode_atts(array('field' => ''), $atts, 'user_detail');

    if (!is_user_logged_in()) {
        return;
    }

    $user_id = get_current_user_id();
    global $wpdb;
    $table_name = $wpdb->prefix . "mk_players";
    $player = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE user_id = %d", $user_id));

    // Enhanced debugging
    error_log("Trying to fetch data for user ID: $user_id");
    error_log(print_r($player, true));

    if ($player && isset($player->{$atts['field']})) {
        if ($atts['field'] === 'rank') {
            $rankColor = ''; // Default color if rank color is not found
            $rankName = $player->{$atts['field']};
            $rankColor = $wpdb->get_var("SELECT username_color FROM {$wpdb->prefix}mk_ranks WHERE rank_name = '$rankName'");
            return 'Rank: <span style="color: ' . esc_attr($rankColor) . ';">' . esc_html($rankName) . '</span>';
        } else {
            return ucfirst($atts['field']) . ': ' . $player->{$atts['field']};
        }
    }

    return "Detail not found.";
}

/* ---- Score Calculation ---- */
function meiko_calculate_score($user_id) {
    global $wpdb;
    $player_table_name = $wpdb->prefix . "mk_players";

    $player = $wpdb->get_row($wpdb->prepare("SELECT * FROM $player_table_name WHERE user_id = %d", $user_id));

    if (!$player) {
        return;
    }

    // Calculate item score
    $items = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->prefix}mk_player_items WHERE player_id = %d", $player->id));
    $last_error = $wpdb->last_error;
    if (!empty($last_error)) {
        error_log("Database Error: $last_error");
    }
    $total_item_score = 0;
    foreach ($items as $item) {
        $total_item_score += $item->item_score * $item->quantity;
    }

    // Get house score based on house_name
    $house = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}mk_houses WHERE name = %s", $player->house_name));
    if (!empty($last_error)) {
        error_log("Database Error: $last_error");
    }
    $house_score = $house ? $house->house_score : 0;

    // Get car score based on car_name
    $car = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}mk_cars WHERE name = %s", $player->car_name));
    if (!empty($last_error)) {
        error_log("Database Error: $last_error");
    }
    $car_score = $car ? $car->car_score : 0;

    // Get attack pack score based on attack_pack_level
    $attack_pack = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}mk_attack_packs WHERE name = %s", $player->attack_pack_level));
    if (!empty($last_error)) {
        error_log("Database Error: $last_error");
    }
    $attack_pack_score = $attack_pack ? $attack_pack->attack_score : 0;

    // Get defense pack score based on defense_pack_level
    $defense_pack = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}mk_defense_packs WHERE name = %s", $player->defense_pack_level));
    if (!empty($last_error)) {
        error_log("Database Error: $last_error");
    }
    $defense_pack_score = $defense_pack ? $defense_pack->defense_score : 0;

    // Calculate scores from computers, hitmen, and guards
    $computer_score = $player->computers * 10;
    $hitmen_score = $player->hitmen * 10;
    $guards_score = $player->guards * 10;
    $education_score = $player->education;
    $money_score = $player->money * 0.1;
    $bank_score = $player->current_bank_balance * 0.1;
    $fighting_score = $player->fighting;

    // Calculate the total score
    $score = $house_score + $car_score + $attack_pack_score + $defense_pack_score + $total_item_score + $computer_score + $hitmen_score + $guards_score + $education_score + $money_score + $bank_score + $fighting_score;
    error_log("Calculated score for user ID $user_id: $score");
    // Update the player's score in the database
    $updated = $wpdb->update($player_table_name, array('score' => $score), array('id' => $player->id));
    if ($updated === false) {
        error_log("Error updating score for user ID $user_id");
    } else if ($updated == 0) {
        error_log("No change in score for user ID $user_id");
    } else {
        error_log("Updated score for user ID $user_id");
    }}

/* ---- Moves Calculation ---- */
function meiko_add_moves_to_players() {
    global $wpdb;
    $table_name = $wpdb->prefix . "mk_players";

    $mk_players = $wpdb->get_results("SELECT * FROM $table_name");

    foreach ($mk_players as $player) {
        // Calculate new moves and health for the player
        $new_moves = min(2000, $player->moves + 50);
        $new_health = min(100, $player->health + 5);

        // Update the player data in the database
        $wpdb->update($table_name, 
                      array('moves' => $new_moves, 'health' => $new_health), 
                      array('id' => $player->id));
    }
}

function handle_moves_update() {
    if (is_user_logged_in()) {
        meiko_add_moves_to_players();
        echo "Moves and health updated!";
    } else {
        echo "User not logged in!";
    }
    wp_die();
}


function meiko_schedule_moves_event() {
    if (!wp_next_scheduled('meiko_cron_updates')) {
        wp_schedule_event(time(), 'five_minutes', 'meiko_cron_updates');
    }
}
register_activation_hook(__FILE__, 'meiko_schedule_moves_event');

function meiko_cron_intervals($schedules) {
    $schedules['ten_minutes'] = array(
        'interval' => 300,  // 10 minutes in seconds
        'display'  => __('Every 5 Minutes'),
    );
    return $schedules;
}

function meiko_display_timer_shortcode() {
    ob_start();
    ?>
    <div id="timerWrapper">
        Time until next update: <span id="timerDisplay"></span>
    </div>
    <?php
    return ob_get_clean();
}

function update_scores() {
    error_log("Received AJAX call to update scores");
    global $wpdb;
    $player_table_name = $wpdb->prefix . "mk_players";

    // Fetch all players
    $players = $wpdb->get_results("SELECT * FROM $player_table_name");

    foreach ($players as $player) {
        meiko_calculate_score($player->user_id);
    }
}

function display_user_item($atts) {
    $atts = shortcode_atts(array('item_name' => ''), $atts, 'display_user_item');

    if (empty($atts['item_name'])) {
        return "Item name not provided.";
    }

    if (!is_user_logged_in()) {
        return 'You must be logged in to view your items.';
    }

    $user_id = get_current_user_id();
    global $wpdb;

    // Get the player's ID based on the user ID
    $players_table_name = $wpdb->prefix . "mk_players";
    $player = $wpdb->get_row($wpdb->prepare("SELECT * FROM $players_table_name WHERE user_id = %d", $user_id));

    if (!$player) {
        return "Player not found.";
    }

    // Now fetch the item and its quantity owned by the player
    $player_items_table_name = $wpdb->prefix . "mk_player_items";
    $market_items_table_name = $wpdb->prefix . "meiko_market_items";

    $item = $wpdb->get_row($wpdb->prepare("
        SELECT m.name as item_name, p.quantity as item_quantity
        FROM $player_items_table_name p
        JOIN $market_items_table_name m ON p.item_id = m.id
        WHERE p.player_id = %d AND m.name = %s
    ", $player->user_id, $atts['item_name']));

    if (!$item) {
        return "You have 0 " . esc_html($atts['item_name']);
    }

    // Construct the output
    $output = esc_html($item->item_name) . ': ' . intval($item->item_quantity);

    return $output;
}

// Add the shortcode
add_shortcode('display_user_item', 'display_user_item');

function update_user_last_activity() {
    if (is_user_logged_in()) {
        $current_user_id = get_current_user_id();
        update_user_meta($current_user_id, 'last_activity_timestamp', current_time('timestamp'));
    }
}
add_action('wp_loaded', 'update_user_last_activity');

function display_active_users_shortcode() {
    $two_minutes_ago = current_time('timestamp') - (1 * MINUTE_IN_SECONDS);
    
    $user_query = new WP_User_Query(array(
        'meta_key'     => 'last_activity_timestamp',
        'meta_value'   => $two_minutes_ago,
        'meta_compare' => '>',
    ));
    
    $active_users = $user_query->get_results();
    $active_users_count = count($active_users);

    $last_registered_users = get_users(array(
        'orderby' => 'registered',
        'order'   => 'DESC',
        'number'  => 1,
    ));

    $last_registered_username = !empty($last_registered_users) ? $last_registered_users[0]->user_login : 'None';
    
    $active_users_page_url = get_option('meiko_active_users_page', '#');
    
    return "Active users: <a href='{$active_users_page_url}' class='meiko_active_count'>{$active_users_count}</a><br>Newest user: {$last_registered_username}";

}
add_shortcode('meiko_user_info', 'display_active_users_shortcode');

function display_active_players_list_shortcode() {
    $two_minutes_ago = current_time('timestamp') - (1 * MINUTE_IN_SECONDS);
    
    // Query for users who have activity in the last 1 minutes
    $user_query = new WP_User_Query(array(
        'meta_key'     => 'last_activity_timestamp',
        'meta_value'   => $two_minutes_ago,
        'meta_compare' => '>',
    ));
    
    $active_users = $user_query->get_results();

    if (empty($active_users)) {
        return "No active users currently.";
    }

    $output = '<ul class="active-users-list">';
    foreach ($active_users as $player) {
        $profile_url = get_author_posts_url($player->ID);
        $output .= '<li><a href="' . esc_url($profile_url) . '">' . esc_html($player->user_login) . '</a></li>';
    }
    $output .= '</ul>';

    return $output;
}
add_shortcode('active_players_list', 'display_active_players_list_shortcode');

function meiko_calculate_player_stats_in_faction() {
    global $wpdb;
    $players_table = $wpdb->prefix . "mk_players";
    $factions_table = $wpdb->prefix . "mk_factions";
    
    // Fetch all players, including those without a faction
    $all_players = $wpdb->get_results("SELECT * FROM $players_table");
    
    foreach ($all_players as $player) {
        
        // Check if player is in a valid faction
        if (isset($player->faction) && !empty($player->faction) && $player->faction !== 'None') {
            
            // Get the faction data using the name stored in the player's faction column
            $faction_data = $wpdb->get_row($wpdb->prepare("SELECT * FROM $factions_table WHERE name = %s", $player->faction), ARRAY_A);
            
            // Check if faction data exists for the player
            if ($faction_data !== null) {
                
                $new_attack = $player->attack + $faction_data['attack'] + $player->hitmen_attack;
                $new_defense = $player->defense + $faction_data['defense'] + $player->guards_defense;
                
                $wpdb->update(
                    $players_table,
                    array(
                        'total_attack' => $new_attack,
                        'total_defense' => $new_defense,
                    ),
                    array('id' => $player->id),
                    array('%d', '%d'),
                    array('%d')
                );
                
            } else {
                error_log("No faction data found for player with ID: " . $player->id . ", Faction Name: " . $player->faction);
            }
            
        } else {
            // If player is not in a faction, set total_attack and total_defense equal to attack and defense
            $new_attack_b = $player->attack + $player->hitmen_attack;
            $new_defense_b = $player->defense + $player->guards_defense;

            $wpdb->update(
                $players_table,
                array(
                    'total_attack' => $new_attack_b,
                    'total_defense' => $new_defense_b,
                ),
                array('id' => $player->id),
                array('%d', '%d'),
                array('%d')
            );
        }
        
    }

    return "Player stats updated successfully!";
}
?>