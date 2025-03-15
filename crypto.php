<?php

// Function to handle the Meiko Crypto settings page
function meiko_crypto_settings_page(){
    global $wpdb;
    $table_name = $wpdb->prefix . 'mk_crypto';

    // Check if form was submitted
    if(isset($_POST['submit'])){
        // Security check
        if(!wp_verify_nonce($_POST['_wpnonce'], 'add_crypto_nonce')){
            echo "Security check failed!";
            exit;
        }

        $crypto_name = sanitize_text_field($_POST['crypto_name']);
        $value = floatval($_POST['value']);
        $mining_speed = floatval($_POST['mining_speed_per_1_computer']);
        $moves_required = intval($_POST['moves_required_to_mine']);

        if ($value <= 0 || $mining_speed <= 0 || $moves_required <= 0) {
            echo "Invalid input values!";
            return;
        }

        // Insert new crypto into database
        $wpdb->insert($table_name, array(
            'crypto_name' => $crypto_name,
            'value' => $value,
            'mining_speed_per_1_computer' => $mining_speed,
            'moves_required_to_mine' => $moves_required
        ));
        error_log("Meiko Plugin: Added new crypto - $crypto_name");
    }

    // Display existing cryptocurrencies in a table
    $cryptos = $wpdb->get_results("SELECT * FROM $table_name");

    ?>
    <div class="wrap">
        <h2>Meiko Crypto Settings</h2>
        <form method="post" action="">
            <?php wp_nonce_field('add_crypto_nonce'); ?>
            <table class="form-table">
                <tr valign="top">
                    <th scope="row">Crypto Name</th>
                    <td><input type="text" name="crypto_name" required /></td>
                </tr>
                <tr valign="top">
                    <th scope="row">Value</th>
                    <td><input type="number" name="value" step="0.01" required /></td>
                </tr>
                <tr valign="top">
                    <th scope="row">Mining Speed Per 1 Computer (per hour)</th>
                    <td><input type="number" name="mining_speed_per_1_computer" step="0.01" required /></td>
                </tr>
                <tr valign="top">
                    <th scope="row">Moves Required To Mine</th>
                    <td><input type="number" name="moves_required_to_mine" required /></td>
                </tr>
            </table>
            <p class="submit">
                <input type="submit" name="submit" id="submit" class="button button-primary" value="Add New Crypto">
            </p>
        </form>

        <!-- Display existing cryptocurrencies in a table -->
        <h2>Existing Cryptocurrencies</h2>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th>Crypto Name</th>
                    <th>Value</th>
                    <th>Mining Speed (per hour)</th>
                    <th>Moves Required To Mine</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($cryptos as $crypto) : ?>
                <tr>
                    <td><?php echo esc_html($crypto->crypto_name); ?></td>
                    <td><?php echo esc_html($crypto->value); ?></td>
                    <td><?php echo esc_html($crypto->mining_speed_per_1_computer); ?></td>
                    <td><?php echo esc_html($crypto->moves_required_to_mine); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php
}

// Buy Computer Shortcode
function buy_computer_shortcode(){
    global $wpdb;
    $current_user_id = get_current_user_id();
    $players_table = $wpdb->prefix . 'mk_players';
    $output = "";

    $player = $wpdb->get_row($wpdb->prepare("SELECT * FROM $players_table WHERE user_id = %d", $current_user_id));

    if(!$player){
        return "Player data not found.";
    }

    if(isset($_POST['buy_computer'])){
        if(!wp_verify_nonce($_POST['_wpnonce'], 'buy_computer_nonce')){
            return "Security check failed!";
        }

        if($player->money >= 5000 && $player->moves >= 50){
            $updated_money = $player->money - 5000;
            $updated_moves = $player->moves - 50;
            $updated_computers = $player->computers + 1;

            $wpdb->update(
                $players_table,
                array('money' => $updated_money, 'moves' => $updated_moves, 'computers' => $updated_computers),
                array('user_id' => $current_user_id)
            );

            $output .= "<p>Successfully bought a computer!</p>";
            $player->money = $updated_money;
            $player->moves = $updated_moves;
            $player->computers = $updated_computers;
        } else {
            $output .= "<p>You don't have enough resources to buy a computer.</p>";
        }
    }

    $output .= "
    <form class='buy_computer_form' method='post' action=''>
        " . wp_nonce_field('buy_computer_nonce', '_wpnonce', true, false) . "
        <p>You have {$player->computers} computers.</p>
        <p>1 Computer costs 5000 money and 50 moves.</p>
        <input type='submit' name='buy_computer' value='Buy Computer'>
    </form>
    ";
    return $output;
}

function mine_crypto_shortcode(){
    global $wpdb;
    $output = "";

    $player_table = $wpdb->prefix . "mk_players";
    $crypto_table = $wpdb->prefix . "mk_crypto";
    $owned_crypto_table = $wpdb->prefix . "mk_owned_crypto";

    $user_id = get_current_user_id();
    $player = $wpdb->get_row($wpdb->prepare("SELECT * FROM $player_table WHERE user_id = %d", $user_id));
    $current_time = current_time('mysql');

    if (isset($player->mining_end_time) && $current_time < $player->mining_end_time) {
        $cooldown_remaining = strtotime($player->mining_end_time) - strtotime($current_time);
        $hours = floor($cooldown_remaining / 3600);
        $minutes = floor(($cooldown_remaining / 60) % 60);
        $seconds = $cooldown_remaining % 60;
        $output .= "<div>You're still mining! Wait for {$hours}h {$minutes}m {$seconds}s to mine again.</div>";
        return $output;
    }

    if (isset($_POST['submit_mine_crypto'])) {
        $selected_crypto = $_POST['selected_crypto'];
        $duration = intval($_POST['duration']);

        $crypto_details = $wpdb->get_row($wpdb->prepare("SELECT * FROM $crypto_table WHERE crypto_name = %s", $selected_crypto));
        $moves_required = $crypto_details->moves_required_to_mine * $duration;

        if ($player->moves >= $moves_required) {
            $mining_end_time = date('Y-m-d H:i:s', strtotime("+$duration hours", strtotime($current_time)));

            $wpdb->update($player_table, 
                array(
                    'mining_end_time' => $mining_end_time,
                    'moves' => $player->moves - $moves_required
                ),
                array('user_id' => $user_id)
            );
            $mining_speed = $crypto_details->mining_speed_per_1_computer;
            $mined_amount = $mining_speed * $player->computers * $duration;
    
            $current_pending = $wpdb->get_var($wpdb->prepare(
                "SELECT pending_crypto FROM $owned_crypto_table WHERE player_id = %d AND crypto_name = %s", $player->id, $selected_crypto
            ));
    
            if ($current_pending !== null) {
                // Update existing record
                $wpdb->update($owned_crypto_table, 
                    array('pending_crypto' => $current_pending + $mined_amount),
                    array('player_id' => $player->id, 'crypto_name' => $selected_crypto)
                );
            } else {
                // Insert new record
                $wpdb->insert(
                    $owned_crypto_table,
                    array('player_id' => $player->id, 'crypto_name' => $selected_crypto, 'pending_crypto' => $mined_amount)
                );
            }
    
            $output .= "You are mining $mined_amount $selected_crypto!";
        } else {
            $output .= "<div>Not enough moves to mine for this duration.</div>";
        }
    }

    $pending_crypto = $wpdb->get_var($wpdb->prepare(
        "SELECT pending_crypto FROM {$wpdb->prefix}mk_owned_crypto WHERE player_id = %d AND pending_crypto > 0",
        $player->id
    ));
    
    if ($pending_crypto > 0) {
        $output .= do_shortcode('[withdraw_crypto_shortcode]');
    } else {
        // Display the form
        $cryptos = $wpdb->get_col("SELECT crypto_name FROM {$wpdb->prefix}mk_crypto");
        $output .= '<form class="mine_crypto_button" method="post">
                    <select name="selected_crypto">';
    
        foreach ($cryptos as $crypto) {
            $output .= '<option value="' . esc_attr($crypto) . '">' . esc_html($crypto) . '</option>';
        }
    
        $output .= '</select>
                    <select name="duration">';
        for ($i = 1; $i <= 6; $i++) {
            $output .= '<option value="' . $i . '">' . $i . ' hours</option>';
        }
        $output .= '</select>
                    <input type="submit" name="submit_mine_crypto" value="Mine crypto">
                    </form>';
    }
    
                            
    return $output;
}
function sell_crypto_shortcode() {
    global $wpdb;
    $output = '';

    $player_table = $wpdb->prefix . "mk_players";
    $crypto_table = $wpdb->prefix . "mk_crypto";
    $owned_crypto_table = $wpdb->prefix . "mk_owned_crypto";

    $user_id = get_current_user_id();
    $player = $wpdb->get_row($wpdb->prepare("SELECT * FROM $player_table WHERE user_id = %d", $user_id));

    if (isset($_POST['sell_crypto'])) {
        $selected_crypto = $_POST['selected_crypto'];
        $quantity_to_sell = intval($_POST['quantity']);
        
        $crypto_value = $wpdb->get_var($wpdb->prepare("SELECT value FROM $crypto_table WHERE crypto_name = %s", $selected_crypto));
        
        $current_quantity = $wpdb->get_var($wpdb->prepare("SELECT amount FROM $owned_crypto_table WHERE player_id = %d AND crypto_name = %s", $player->id, $selected_crypto));
        
        if ($current_quantity && $current_quantity >= $quantity_to_sell) {
            $wpdb->update($owned_crypto_table, 
                array('amount' => $current_quantity - $quantity_to_sell),
                array('player_id' => $player->id, 'crypto_name' => $selected_crypto)
            );

            $wpdb->update($player_table,
                array('money' => $player->money + ($crypto_value * $quantity_to_sell)),
                array('user_id' => $user_id)
            );

            $output .= "Successfully sold $quantity_to_sell $selected_crypto!";
        } else {
            $output .= "<div>You don't have enough $selected_crypto to sell.</div>";
        }
    }

    // Fetching the cryptos owned by the user
    $owned_cryptos = $wpdb->get_col($wpdb->prepare("SELECT crypto_name FROM $owned_crypto_table WHERE player_id = %d", $player->id));

    $output .= '<form class="sell_crypto_button" method="post">
                <select name="selected_crypto">';

    foreach($owned_cryptos as $crypto) {
        $output .= '<option value="' . esc_attr($crypto) . '">' . esc_html($crypto) . '</option>';
    }

    $output .= '</select>
                <input type="number" name="quantity" placeholder="Quantity" min="1" required>
                <input type="submit" name="sell_crypto" value="Sell">
                </form>';

    return $output;
}

function withdraw_crypto_shortcode(){
    global $wpdb;
    $output = "";

    $player_table = $wpdb->prefix . "mk_players";
    $owned_crypto_table = $wpdb->prefix . "mk_owned_crypto";
    
    $user_id = get_current_user_id();
    $player = $wpdb->get_row($wpdb->prepare("SELECT * FROM $player_table WHERE user_id = %d", $user_id));
    
    if(!$player) {
        return "Player data not found.";
    }

    // Fetch the crypto details being mined
    $mining_details = $wpdb->get_row($wpdb->prepare("SELECT * FROM $owned_crypto_table WHERE player_id = %d AND pending_crypto > 0", $player->id));

    // If there's a mined crypto to be withdrawn and the mining end time is past
    $current_time = current_time('mysql');
    if($mining_details && $current_time >= $player->mining_end_time){
        
        // Withdraw the pending_crypto into the player's account and reset pending_crypto to 0
        $total_crypto = $mining_details->amount + $mining_details->pending_crypto;
        
        $wpdb->update(
            $owned_crypto_table,
            array('amount' => $total_crypto, 'pending_crypto' => 0),
            array('player_id' => $player->id, 'crypto_name' => $mining_details->crypto_name)
        );

        $wpdb->update(
            $player_table,
            array('mining_end_time' => null),
            array('user_id' => $user_id)
        );

        $output .= "Successfully credited {$mining_details->pending_crypto} {$mining_details->crypto_name} to your account!";
    } else {
        $output .= "There's no mined crypto to be withdrawn at the moment.";
    }
    
    return $output;
}
add_shortcode('withdraw_crypto_shortcode', 'withdraw_crypto_shortcode');

// Shortcode to display all owned cryptocurrencies
function display_owned_crypto_shortcode() {
    if (!is_user_logged_in()) {
        return 'You must be logged in to view your cryptocurrencies.';
    }

    global $wpdb;
    $user_id = get_current_user_id();
    $owned_crypto_table = $wpdb->prefix . "mk_owned_crypto";

    // Fetch all owned cryptocurrencies for the logged-in user
    $owned_cryptos = $wpdb->get_results($wpdb->prepare(
        "SELECT crypto_name, amount, pending_crypto FROM $owned_crypto_table WHERE player_id = %d",
        $user_id
    ));

    // Check if the user owns any cryptocurrencies
    if (empty($owned_cryptos)) {
        return 'You do not own any cryptocurrencies.';
    }

    // Start building the HTML table
    $output = '<table class="wp-list-table widefat fixed striped">';
    $output .= '<thead>
                    <tr>
                        <th>Crypto Name</th>
                        <th>Owned</th>
                        <th>Pending</th>
                    </tr>
                </thead>';
    $output .= '<tbody>';

    // Populate the table rows
    foreach ($owned_cryptos as $crypto) {
        $output .= '<tr>';
        $output .= '<td>' . esc_html($crypto->crypto_name) . '</td>';
        $output .= '<td>' . esc_html($crypto->amount) . '</td>';
        $output .= '<td>' . esc_html($crypto->pending_crypto) . '</td>';
        $output .= '</tr>';
    }

    $output .= '</tbody></table>';

    return $output;
}

// Register the shortcode
add_shortcode('owned_crypto', 'display_owned_crypto_shortcode');

?>