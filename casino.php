<?php

global $wpdb;
$current_user_id = get_current_user_id();
$players_table = $wpdb->prefix . "mk_players";
$player_data = $wpdb->get_row($wpdb->prepare("SELECT money FROM $players_table WHERE user_id = %d", $current_user_id), ARRAY_A);

function meiko_casino($atts) {
    global $wpdb;
    $current_user_id = get_current_user_id();
    $players_table = $wpdb->prefix . "mk_players";
    
    $player_data = $wpdb->get_row($wpdb->prepare("SELECT money FROM $players_table WHERE user_id = %d", $current_user_id), ARRAY_A);
    ob_start();
    ?>
    
    <div class="meiko-casino">
        
        <div class="meiko-casino-crash">
            <h3>Crash Game</h3>
            <form class="crash-form" id="crash-form" method="post" action="">
                <label for="crash_bet">Bet Amount: </label>
                <input type="number" min="1" name="crash_bet" id="crash_bet" />
                <button type="button" id="start-crash">Start Game</button>
                <button type="button" id="cashout" disabled>Cash Out</button>
            </form>
            <h4>Multiplier: <span id="multiplier">1.00x</span></h4>
        </div>


        <h4>Your Current Balance: $<?php echo esc_html(isset($player_data['money']) ? $player_data['money'] : '0'); ?></h4>
    </div>
    
    <?php
    return ob_get_clean();
}

function place_crash_bet() {
    check_ajax_referer('meiko_casino_nonce', 'casino_security');
    if (!is_user_logged_in()) {
        echo json_encode(array('error' => 'User is not logged in.'));
        wp_die();
    }

    global $wpdb;
    $current_user_id = get_current_user_id();
    $players_table = $wpdb->prefix . "mk_players";

    if (!isset($_POST['bet_amount']) || !is_numeric($_POST['bet_amount'])) {
        echo json_encode(array('error' => 'Invalid bet amount.'));
        wp_die();
    }

    $bet_amount = floatval($_POST['bet_amount']);
    $player_data = $wpdb->get_row($wpdb->prepare("SELECT money FROM $players_table WHERE user_id = %d", $current_user_id), ARRAY_A);
    $current_balance = floatval($player_data['money']);

    if ($bet_amount <= 0 || $bet_amount > $current_balance) {
        echo json_encode(array('error' => 'Invalid bet amount.'));
        wp_die();
    }

    $new_balance = max(0, $current_balance - $bet_amount);
    update_user_meta($current_user_id, 'current_bet', $bet_amount);

    $wpdb->update(
        $players_table,
        array('money' => $new_balance),
        array('user_id' => $current_user_id),
        array('%f'),
        array('%d')
    );

    // Generate a random crash multiplier (e.g., between 1.10x and 10.00x)
    // Add a house edge (e.g., 5%)
    $house_edge = 0.05;
    
    $random = mt_rand(1, 100);
    if ($random <= 50) {
        $crash_multiplier = round((mt_rand(100, 200) / 100) * (1 - $house_edge), 2); // 50% chance for 1.10x - 2.00x
    } elseif ($random <= 80) {
        $crash_multiplier = round((mt_rand(201, 400) / 100) * (1 - $house_edge), 2); // 30% chance for 2.01x - 4.00x
    } else {
        $crash_multiplier = round((mt_rand(401, 500) / 100) * (1 - $house_edge), 2); // 20% chance for 4.01x - 5.00x
    }
    update_user_meta($current_user_id, 'crash_multiplier', $crash_multiplier);

    echo json_encode(array(
        'new_balance' => $new_balance,
        'crash_multiplier' => $crash_multiplier // Return crash multiplier to the client
    ));

    wp_die();
}


function cash_out_crash() {
    check_ajax_referer('meiko_casino_nonce', 'casino_security');
    if (!is_user_logged_in()) {
        echo json_encode(array('error' => 'User is not logged in.'));
        wp_die();
    }

    global $wpdb;
    $current_user_id = get_current_user_id();
    $players_table = $wpdb->prefix . "mk_players";
    
    if (!isset($_POST['multiplier']) || !is_numeric($_POST['multiplier'])) {
        echo json_encode(array('error' => 'Invalid multiplier.'));
        wp_die();
    }
    
    $multiplier = floatval($_POST['multiplier']);
    $crash_multiplier = floatval(get_user_meta($current_user_id, 'crash_multiplier', true));

    // Check if the game has crashed
    if ($multiplier > $crash_multiplier) {
        echo json_encode(array('error' => 'The game has already crashed.'));
        wp_die();
    }

    $player_data = $wpdb->get_row($wpdb->prepare("SELECT money FROM $players_table WHERE user_id = %d", $current_user_id), ARRAY_A);
    $current_balance = floatval($player_data['money']);
    $bet_amount = floatval(get_user_meta($current_user_id, 'current_bet', true));
    
    $winnings = $bet_amount * $multiplier;
    $new_balance = max(0, $current_balance + $winnings);
    
    $wpdb->update(
        $players_table,
        array('money' => $new_balance),
        array('user_id' => $current_user_id),
        array('%f'),
        array('%d')
    );
    
    delete_user_meta($current_user_id, 'current_bet');
    delete_user_meta($current_user_id, 'crash_multiplier');
    
    echo json_encode(array('new_balance' => $new_balance));
    
    wp_die();
}


function play_roulette_animation() {
    if (!is_user_logged_in()) {
        echo json_encode(array('error' => 'User is not logged in.'));
        wp_die();
    }

    global $wpdb;
    $current_user_id = get_current_user_id();
    $players_table = $wpdb->prefix . "mk_players";

    if (!isset($_POST['bet_amount']) || !is_numeric($_POST['bet_amount']) || 
        !isset($_POST['chosen_color']) || !in_array($_POST['chosen_color'], array('red', 'black', 'green'))) {
        echo json_encode(array('error' => 'Invalid input.'));
        wp_die();
    }

    $bet_amount = floatval($_POST['bet_amount']);
    $chosen_color = sanitize_text_field($_POST['chosen_color']);

    $player_data = $wpdb->get_row($wpdb->prepare("SELECT money FROM $players_table WHERE user_id = %d", $current_user_id), ARRAY_A);
    $current_balance = floatval($player_data['money']);

    if ($bet_amount <= 0 || $bet_amount > $current_balance) {
        echo json_encode(array('error' => 'Invalid bet amount.'));
        wp_die();
    }

    $result_number = rand(1, 49); // Random result number
    $result_color = ($result_number % 2 == 0) ? 'black' : 'red';
    if ($result_number == 14) $result_color = 'green'; // Special case for green

    $new_balance = $current_balance - $bet_amount;
    if ($chosen_color == $result_color) {
        $winnings = ($result_color == 'green') ? $bet_amount * 14 : $bet_amount * 2;
        $new_balance += $winnings;
    }
    
    $new_balance = max(0, $new_balance);

    $wpdb->update(
        $players_table,
        array('money' => $new_balance),
        array('user_id' => $current_user_id),
        array('%f'),
        array('%d')
    );

    echo json_encode(array(
        'success' => true,
        'new_balance' => $new_balance,
        'result_number' => $result_number,
        'result_color' => $result_color
    ));
    
    wp_die();
    error_log('Meiko Casino: Played roulette animation');
}

/*
                      //<h3>Roulette Game</h3>
        //<div class="roulette-container">
            //<div class="roulette-indicator" id="roulette-indicator"></div>
            //<div class="roulette-line" id="roulette-wheel">
                $colors = ['red', 'black'];
                $numbers = range(1, 49); // 1-49

                // Generate the roulette numbers twice for seamless looping
                for ($i = 0; $i < 2; $i++) {
                    foreach ($numbers as $number) {
                        $color = ($number % 2 == 0) ? 'black' : 'red';
                        if ($number == 14) $color = 'green'; // Special case for green
                        echo '<div class="roulette-number ' . esc_attr($color) . '" data-number="' . esc_attr($number) . '">' . esc_html($number) . '</div>';
                    }
                }
                ?>
            </div>
        </div>
        
        <form class="roulette-form" method="post" action="">
            <label for="roulette_bet">Bet Amount: </label>
            <input type="number" min="1" name="roulette_bet" id="roulette_bet" />
            <label for="color">Choose a color: </label>
            <select name="color" id="color">
                <option value="red">Red</option>
                <option value="black">Black</option>
                <option value="green">Green</option>
            </select>
            <button type="button" id="play_roulette_animation">Play Roulette</button>
        </form>*/
?>
