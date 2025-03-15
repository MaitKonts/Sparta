<?php

function meiko_fighting_system_shortcode() {
    global $wpdb;
    $output = '';
    
    $fighting_table = $wpdb->prefix . "mk_fighting";
    $player_table = $wpdb->prefix . "mk_players";

    $user_id = get_current_user_id();
    $player = $wpdb->get_row($wpdb->prepare("SELECT * FROM $player_table WHERE user_id = %d", $user_id));

    $current_time = current_time('mysql');
    if (isset($player->training_end_time) && $current_time < $player->training_end_time) {
        $cooldown_remaining = strtotime($player->training_end_time) - strtotime($current_time);
        $hours = floor($cooldown_remaining / 3600);
        $minutes = floor(($cooldown_remaining / 60) % 60);
        $seconds = $cooldown_remaining % 60;
        $output .= "<div>You're still training! Wait for {$hours}h {$minutes}m {$seconds}s to train again.</div>";
        return $output;  // Return here so the user doesn't see the training form.
    }

    if (isset($_POST['start_training'])) {
        $training_hours = intval($_POST['training_hours']);
        $fighting_style_id = intval($_POST['fighting_style']);
        
        // Get the selected fighting style details
        $fighting_style = $wpdb->get_row($wpdb->prepare("SELECT * FROM $fighting_table WHERE id = %d", $fighting_style_id));
        
        $money_required = $fighting_style->money_required * $training_hours;
        $moves_required = $fighting_style->moves_required * $training_hours;
        $food_required = $fighting_style->food_required * $training_hours;
        $fighting_gain = $fighting_style->fighting_gain * $training_hours;
        $attack_gain = $fighting_style->attack_gain * $training_hours;
        $defense_gain = $fighting_style->defense_gain * $training_hours;
        
        if ($player->money >= $money_required && $player->moves >= $moves_required && $player->food >= $food_required) {
            
            $training_end_time = date('Y-m-d H:i:s', strtotime("+$training_hours hours", strtotime($current_time)));
            $wpdb->update($player_table, 
                array(
                    'training_end_time' => $training_end_time,
                    'money' => $player->money - $money_required,
                    'moves' => $player->moves - $moves_required,
                    'food' => $player->food - $food_required,
                    'fighting' => $player->fighting + $fighting_gain,
                    'attack' => $player->attack + $attack_gain,
                    'defense' => $player->defense + $defense_gain,
                    'karate_fighting' => $player->karate_fighting + $fighting_style->karate_gain * $training_hours,
                    'kungfu_fighting' => $player->kungfu_fighting + $fighting_style->kungfu_gain * $training_hours,
                    'boxing_fighting' => $player->boxing_fighting + $fighting_style->boxing_gain * $training_hours,
                    'kravmaga_fighting' => $player->kravmaga_fighting + $fighting_style->kravmaga_gain * $training_hours,
                    'bjj_fighting' => $player->bjj_fighting + $fighting_style->bjj_gain * $training_hours,
                    'ninjutsu_fighting' => $player->ninjutsu_fighting + $fighting_style->ninjutsu_gain * $training_hours,
                ),
                array('user_id' => $user_id)
            );
            $output .= '<div>Training started!</div>';
        } else {
            $output .= '<div>Not enough resources to train for this duration.</div>';
        }
    }

    $fighting_shortcode_output = do_shortcode('[user_detail field="fighting"]');
    $output .= '<div>Your current score in overall ' . esc_html($fighting_shortcode_output) . '</div>';

    $output .= '<form method="post" action="">
        <div>
            <label for="fighting_style">Choose Fighting Style:</label>
            <select name="fighting_style" id="fighting_style">';
    
    // Fetch and display fighting styles from the mk_fighting table
    $fighting_styles = $wpdb->get_results("SELECT * FROM $fighting_table");
    foreach ($fighting_styles as $style) {
        $output .= '<option value="' . esc_attr($style->id) . '">' . esc_html($style->name) . '</option>';
    }

    $output .= '</select>
        </div>
        <div>
            <label for="training_hours">Choose hours of training:</label>
            <select name="training_hours" id="training_hours">';
    
    for ($i = 1; $i <= 6; $i++) {
        $output .= '<option value="' . esc_attr($i) . '">' . esc_html($i) . ' hours</option>';
    }

    $output .= '</select>
        </div>
        <div>
            <button class="meiko-fighting" type="submit" name="start_training">Start Training</button>
        </div>
    </form>';

    return $output;
}

function meiko_fighting_styles_submenu() {
    add_submenu_page(
        'meiko-market-items',            // Parent slug
        'Manage Fighting Styles',        // Page title
        'Fighting Styles',               // Menu title
        'manage_options',                // Capability required
        'meiko-fighting-styles',         // Menu slug
        'meiko_add_fighting_style_callback' // Callback function
    );
}

function meiko_fighting_cost_gain_shortcode() {
    global $wpdb;
    $fighting_table = $wpdb->prefix . "mk_fighting";
    $fighting_styles = $wpdb->get_results("SELECT * FROM $fighting_table");

    $output = '<table class="small-font-table">
        <tr>
            <th>Name</th>
            <th>Moves Required</th>
            <th>Money Required</th>
            <th>Food Required</th>
            <th>Karate Gain</th>
            <th>Kung Fu Gain</th>
            <th>Boxing Gain</th>
            <th>Krav Maga Gain</th>
            <th>BJJ Gain</th>
            <th>Ninjutsu Gain</th>
        </tr>';

    foreach ($fighting_styles as $style) {
        $output .= '<tr>
            <td>' . $style->name . '</td>
            <td>' . $style->moves_required . '</td>
            <td>' . $style->money_required . '</td>
            <td>' . $style->food_required . '</td>
            <td>' . $style->karate_gain . '</td>
            <td>' . $style->kungfu_gain . '</td>
            <td>' . $style->boxing_gain . '</td>
            <td>' . $style->kravmaga_gain . '</td>
            <td>' . $style->bjj_gain . '</td>
            <td>' . $style->ninjutsu_gain . '</td>
        </tr>';
    }

    $output .= '</table>';
    return $output;
}

function mk_challenge_form_shortcode() {
    global $wpdb;
    
    // Table names
    $styles_table = $wpdb->prefix . "mk_fighting_styles";
    $challenges_table = $wpdb->prefix . "mk_challenges";
    
    // Fetch fighting styles from database
    $fighting_styles = $wpdb->get_results("SELECT * FROM $styles_table");
    
    // Handling form submission
    if (isset($_POST['challenge_submit'])) {
        $current_user_id = get_current_user_id();
        $challenger_name = $wpdb->get_var( $wpdb->prepare(
            "SELECT username FROM {$wpdb->prefix}mk_players WHERE user_id = %d", 
            $current_user_id
        ) );
        $fighting_style_id = intval($_POST['style']);
        $amount = intval($_POST['amount']);
        
        // Check if the challenger has enough money
        $challenger_money = $wpdb->get_var($wpdb->prepare("SELECT money FROM {$wpdb->prefix}mk_players WHERE username = %s", $challenger_name));
        $challenger_health = $wpdb->get_var($wpdb->prepare("SELECT health FROM {$wpdb->prefix}mk_players WHERE username = %s", $challenger_name));
    
        if ($challenger_health < 20) {
            echo "Your health is too low to create a challenge.";
            return;
        }

        if ($challenger_money < $amount) {
            echo "You do not have enough money to create this challenge.";
            return;
        }
        
        // Deduct the amount from the challenger's account
        $wpdb->query($wpdb->prepare("UPDATE {$wpdb->prefix}mk_players SET money = money - %d WHERE username = %s", $amount, $challenger_name));
        
        // Insert challenge into database
        $wpdb->insert($challenges_table, [
            'challenger_name' => $challenger_name,
            'fighting_style_id' => $fighting_style_id,
            'amount' => $amount
        ]);
        
        echo "Challenge created successfully!";
    }

    $form = '<form action="" method="post">
    <label for="amount">Bet Amount: </label>
    <input type="number" id="amount" name="amount"><br>
    <label for="style">Fighting Style: </label>
    <select id="style" name="style">';
    
    foreach ($fighting_styles as $style) {
        $form .= '<option value="' . $style->id . '">' . $style->style_name . '</option>';
    }

    $form .= '</select><br>
        <input class="meiko-create-challenge" type="submit" name="challenge_submit" value="Create Challenge">
    </form>';

    return $form;
}

function mk_accept_fight() {
    global $wpdb;

    // Get challenge ID from AJAX request
    $challenge_id = intval($_POST['challenge_id']);
    
    // Table names
    $challenges_table = $wpdb->prefix . "mk_challenges";
    $styles_table = $wpdb->prefix . "mk_fighting_styles";
    $players_table = $wpdb->prefix . "mk_players";
    
    // Get challenge from database
    $challenge = $wpdb->get_row($wpdb->prepare("SELECT * FROM $challenges_table WHERE id = %d", $challenge_id));
    
    // Get the fighting style for this challenge
    $style = $wpdb->get_row($wpdb->prepare("SELECT * FROM $styles_table WHERE id = %d", $challenge->fighting_style_id));

    $logic_to_stat_mapping = [
        'ufc_logic' => 'fighting',
        'boxing_logic' => 'boxing_fighting',
        'karate_logic' => 'karate_fighting',
        'kungfu_logic' => 'kungfu_fighting',
        'kravmaga_logic' => 'kravmaga_fighting',
        'bjj_logic' => 'bjj_fighting',
        'ninjutsu_logic' => 'ninjutsu_fighting'
    ];

    // Determine which stat field in mk_players to use based on the logic_field in mk_fighting_styles
    $stat_field = $logic_to_stat_mapping[$style->logic_field];

    // Get players involved in this fight
    $challenger = $wpdb->get_row($wpdb->prepare("SELECT * FROM $players_table WHERE username = %s", $challenge->challenger_name));
    $accepter = wp_get_current_user();
    $accepter_money = $wpdb->get_var($wpdb->prepare("SELECT money FROM {$wpdb->prefix}mk_players WHERE username = %s", $accepter->user_login));
    $accepter_health = $wpdb->get_var($wpdb->prepare("SELECT health FROM {$wpdb->prefix}mk_players WHERE username = %s", $accepter->user_login));

    if ($challenge->challenger_name === $accepter->user_login) {
        wp_send_json_error(['message' => 'You cannot fight against yourself.']);
        return;
    }

    if ($accepter_money < $challenge->amount) {
        wp_send_json_error(['message' => 'You do not have enough money to accept this fight.']);
        return;
    }

    if ($accepter_health < 20) {
        wp_send_json_error(['message' => 'Your health is too low to accept this fight.']);
        return;
    }

    $accepter_stats = $wpdb->get_row($wpdb->prepare("SELECT * FROM $players_table WHERE username = %s", $accepter->user_login));

    // Extract the corresponding fighting stats for each player
    $challenger_stat = intval($challenger->$stat_field);
    $accepter_stat = intval($accepter_stats->$stat_field);
    
    if ($accepter_stat > $challenger_stat) {
        // Accepter wins
        $wpdb->query($wpdb->prepare("UPDATE $players_table SET money = money + %d WHERE username = %s", ($challenge->amount * 2), $accepter->user_login));
        $wpdb->query($wpdb->prepare("UPDATE $players_table SET money = money - %d, health = health - 20 WHERE username = %s", $challenge->amount, $challenge->challenger_name));
        $response_message = 'You won the fight!';
    } else {
        // Challenger wins
        $wpdb->query($wpdb->prepare("UPDATE $players_table SET money = money + %d WHERE username = %s", ($challenge->amount * 2), $challenge->challenger_name));
        $wpdb->query($wpdb->prepare("UPDATE $players_table SET money = money - %d, health = health - 20 WHERE username = %s", $challenge->amount, $accepter->user_login));
        $response_message = 'You lost the fight!';
    }

    // Remove the challenge from the database
    $wpdb->delete($challenges_table, ['id' => $challenge_id]);
    
    // Send a response back to the client
    wp_send_json_success(['message' => $response_message]);
}

function mk_challenges_shortcode() {
    global $wpdb;
    $challenges_table = $wpdb->prefix . "mk_challenges";
    $styles_table = $wpdb->prefix . "mk_fighting_styles";
    
    $challenges = $wpdb->get_results(
        "SELECT c.*, s.style_name 
         FROM $challenges_table c 
         INNER JOIN $styles_table s ON c.fighting_style_id = s.id"
    );
    
    $output = '<table class="fighting-challenge">
        <tr>
            <th>Player Name</th>
            <th>Fighting Style</th>
            <th>Amount</th>
            <th>Action</th>
        </tr>';
        
    foreach ($challenges as $challenge) {
        $output .= '<tr>
            <td>' . $challenge->challenger_name . '</td>
            <td>' . $challenge->style_name . '</td>
            <td>' . $challenge->amount . '</td>
            <td><button class="accept-fight-button" data-challenge-id="' . $challenge->id . '">Accept Fight</button></td>
        </tr>';
    }
    
    $output .= '</table>';
    
    return $output;
}
?>