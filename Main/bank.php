<?php
function bank_system_func() {
    $user_id = get_current_user_id();
    global $wpdb;
    $table_name = $wpdb->prefix . "mk_players";
    $player = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE user_id = %d", $user_id));
    $player_job = $wpdb->get_row($wpdb->prepare("SELECT * FROM " . $wpdb->prefix . "mk_jobs WHERE name = %s", $player->current_job));
    $max_transfer = 0.2 * $player->score;  // 20% of the player's score

    $output = '';

    // Trading Moves for Money Logic
    if (isset($_POST['trade_moves'])) {
        $moves_to_trade = intval($_POST['moves_to_trade']);
        if ($moves_to_trade <= $player->moves) {
            $money_gained = $moves_to_trade * 100;
            $wpdb->update(
                $table_name,
                array(
                    'moves' => $player->moves - $moves_to_trade,
                    'money' => $player->money + $money_gained
                ),
                array('id' => $player->id)
            );
            $output .= '<div>Successfully traded moves for money!</div>';
        } else {
            $output .= '<div>Not enough moves to trade.</div>';
        }
        $output .= "<script> window.location = window.location.href + '?meiko_reload=true'; </script>";
    }

    // Display current balance
    $output .= '<div>Your current bank balance: ' . $player->current_bank_balance . '</div>'; // Display bank balance
    $output .= '<div>Your maximum bank balance: ' . $player_job->max_bank_balance . '</div>'; // Display bank balance
    $output .= '<div>Your maximum transfer amount: ' . $max_transfer . '</div>';

    // Deposit Form
    $output .= '
    <form class="meiko-deposit" method="post">
        <label for="deposit_amount">Deposit Amount:</label>
        <input type="number" name="deposit_amount" id="deposit_amount">
        <button type="submit" name="deposit">Deposit</button>
    </form>';

    // Withdraw Form
    $output .= '
    <form class="meiko-withdraw" method="post">
        <label for="withdraw_amount">Withdraw Amount:</label>
        <input type="number" name="withdraw_amount" id="withdraw_amount">
        <button type="submit" name="withdraw">Withdraw</button>
    </form>';

    // Display the Trade Form
    $output .= '<form class="meiko-bank" method="post">
        <label for="moves_to_trade">Moves to trade:</label>
        <input type="number" name="moves_to_trade" required>
        <button type="submit" name="trade_moves">Trade for Money</button>
    </form>';

    $output .= '<form class="meiko-transfer" method="post">
        <label for="transfer_amount">Amount to transfer:</label>
        <input type="number" name="transfer_amount" required>
        <br>
        <label for="receiver_username">Receiver Username:</label>
        <input type="text" name="receiver_username" required>
        <button type="submit" name="transfer_money">Transfer Money</button>
    </form>';

    // Transfer Money Logic
    if (isset($_POST['transfer_money'])) {
        $transfer_amount = floatval($_POST['transfer_amount']);
        $receiver_username = sanitize_text_field($_POST['receiver_username']);  // Make sure to sanitize input

        if ($transfer_amount <= $max_transfer && $transfer_amount <= $player->money) {
            if ($player->moves >= 100) {  // Check if the player has enough moves
                $receiver = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE username = %s", $receiver_username));

                if ($receiver) {
                    // Deduct money from sender, add to receiver, and deduct 100 moves from sender
                    $wpdb->update($table_name, array('money' => $player->money - $transfer_amount, 'moves' => $player->moves - 100), array('id' => $player->id));
                    $wpdb->update($table_name, array('money' => $receiver->money + $transfer_amount), array('id' => $receiver->id));

                    $output .= '<div>Successfully transferred money to ' . esc_html($receiver_username) . '!</div>';
                } else {
                    $output .= '<div>Error: Receiver not found!</div>';
                }
            } else {
                $output .= '<div>Error: You need at least 100 moves to transfer money!</div>';
            }
        } else {
            $output .= '<div>Error: You can only transfer up to ' . $max_transfer . ' or the amount you have!</div>';
        }

        $output .= "<script> window.location = window.location.href + '?meiko_reload=true'; </script>";
    }

    // Deposit Logic
    if (isset($_POST['deposit'])) {
        $deposit_amount = floatval($_POST['deposit_amount']); // Ensure it's a valid number
        $deposit_message = deposit_to_bank($deposit_amount);
        $output .= '<div>' . $deposit_message . '</div>';

        $output .= "<script> window.location = window.location.href + '?meiko_reload=true'; </script>";
    }

    if (isset($_POST['withdraw'])) {
        $withdraw_amount = floatval($_POST['withdraw_amount']);
        if ($withdraw_amount <= $player->current_bank_balance) {
            $new_balance = $player->current_bank_balance - $withdraw_amount;
            $new_player_money = $player->money + $withdraw_amount;  // Add the withdrawn amount to the player's money
            
            $wpdb->update(
                $table_name,
                array(
                    'current_bank_balance' => $new_balance,
                    'money' => $new_player_money  // Update the player's money
                ),
                array('id' => $player->id)
            );
            $output .= '<div>Successfully withdrew ' . $withdraw_amount . '</div>';
        } else {
            $output .= '<div>Error: Insufficient funds in bank!</div>';
        }

        $output .= "<script> window.location = window.location.href + '?meiko_reload=true'; </script>";
    }

    return $output;
}

// Function to handle depositing to the bank
function deposit_to_bank($amount) {
    global $wpdb;
    $user_id = get_current_user_id();
    $table_name = $wpdb->prefix . "mk_players";
    $player = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE user_id = %d", $user_id));
    $player_job = $wpdb->get_row($wpdb->prepare("SELECT * FROM " . $wpdb->prefix . "mk_jobs WHERE name = %s", $player->current_job));

    $max_possible_deposit = $player_job->max_bank_balance - $player->current_bank_balance;

    if ($amount > $max_possible_deposit) {
        return "Error: You can't deposit more than your maximum bank balance! Maximum deposit allowed: " . $max_possible_deposit;
    }

    if ($amount <= $player->money) {
        $new_balance = $player->current_bank_balance + $amount; 
        $new_player_money = $player->money - $amount;  // Subtract the deposit amount from the player's money
        
        $wpdb->update(
            $table_name,
            array(
                'current_bank_balance' => $new_balance,
                'money' => $new_player_money  // Update the player's money
            ),
            array('id' => $player->id)
        );
        
        return "Successfully deposited " . $amount;
    } else {
        return "Error: Insufficient funds!";
    }
}
?>