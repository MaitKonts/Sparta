<?php

function meiko_market_table_shortcode() {
    global $wpdb;

    $items = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}meiko_market_items");
    
    $output = '<div>
    <select id="item-filter" style="padding: 5px; margin-right: 10px;">
        <option value="all">All Items</option>
        <option value="food">Food</option>
        <option value="stocks">Stocks</option>
        <option value="plant">Plants</option>
        <option value="normal">Normal Items</option>
    </select>
    <button id="filter-button" style="padding: 5px 10px; margin-bottom: 5px; background-color: #007bff; color: #fff; border: none; border-radius: 4px; cursor: pointer;">
        Filter
    </button>
    </div>';

        $output .= '<table id="market-table">
        <thead>
            <tr>
                <th>Type</th> <!-- New Column -->
                <th>Name</th>
                <th>Price</th>
                <th>Statistics</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>';

    foreach ($items as $item) {
        $output .= '<tr class="market-item" data-type="' . esc_attr($item->type) . '">
            <td>' . esc_html(ucfirst($item->type)) . '</td> <!-- Displaying the Type --> 
            <td>' . esc_html($item->name) . '</td>
            <td>Money: ' . esc_html($item->price);

        // Only add "Moves" cost if it's greater than 0
        if ($item->moves_price > 0) {
            $output .= '  Moves: ' . esc_html($item->moves_price);
        }

        $output .= '</td>';
        
        // Statistics Tab
        if ($item->type === 'stocks') {
            $output .= '<td>Current Price: ' . esc_html($item->current_price) . '</td>';
        } elseif ($item->type === 'plant') {
            $output .= '<td>Seeds Price: ' . esc_html($item->seeds_price) . '</td>';
        } elseif ($item->type === 'food') {
            $output .= '<td></td>';
        } else {
            $output .= '<td>Defense: ' . esc_html($item->defense) . '  Attack: ' . esc_html($item->attack) . '</td>';
        }
        
        // Action Buttons
        $output .= '<td>';
        $output .= '<div style="display: flex; align-items: center;">';
        $output .= '<input type="number" name="quantity" value="1" min="1" class="meiko-item-quantity" style="margin-right: 10px;">';

        if ($item->type === 'plant') {
            // Plant-specific buttons
            $output .= '<button class="meiko-buy-seeds" data-item-id="' . esc_attr($item->id) . '" data-plant-name="' . esc_attr($item->name) . '" style="margin-right: 5px;">Buy Seeds</button>';
            $output .= '<button class="meiko-sell-plants" data-plant-name="' . esc_attr($item->name) . '">Sell Plants</button>';
        } elseif ($item->type === 'stocks') {
            // Stock-specific buttons
            $output .= '<button class="meiko-buy-item" data-item-id="' . esc_attr($item->id) . '" style="margin-right: 5px;">Buy</button>';
            $output .= '<button class="meiko-sell-stock" data-item-id="' . esc_attr($item->id) . '">Sell</button>';
        } else {
            // Default buy button for other types
            $output .= '<button class="meiko-buy-item" data-item-id="' . esc_attr($item->id) . '">Buy</button>';
        }

        $output .= '</div>';
        $output .= '</td></tr>';
    }

    $nonce = wp_create_nonce('meiko_buy_nonce');
    $output .= '</tbody></table>';
    $output .= '<script>var meikoBuyNonce = "' . $nonce . '";</script>';

    return $output;
}

function meiko_buy_item_callback() {
    global $wpdb;
    
    check_ajax_referer('meiko_buy_nonce', 'nonce');
    
    $item_id = intval($_POST['item_id']);
    $quantity = intval($_POST['quantity']);
    $user_id = get_current_user_id();

    $player_table = $wpdb->prefix . "mk_players";
    $player = $wpdb->get_row($wpdb->prepare("SELECT * FROM $player_table WHERE user_id = %d", $user_id));

    // Fetch item data from the custom table
    $item = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}meiko_market_items WHERE id = %d", $item_id));

    if (!$item) {
        wp_send_json_error(array('message' => 'Invalid item.'));
        return;
    }

    // Buying logic for stock items
    if ($item->type === "stocks") {
        if ($player->money >= $item->current_price * $quantity) {
            $money_left = $player->money - ($item->current_price * $quantity);
            
            // Deduct moves
            $new_moves_total = $player->moves - ($item->moves_price * $quantity);
    
            // Check if buying stocks would reduce the player's moves to less than 0
            if ($new_moves_total < 0) {
                wp_send_json_error(array('message' => 'Not enough moves.'));
                return;
            }
    
            // Deduct money and add stock to player's inventory
            $wpdb->update($player_table, array(
                'money' => $money_left
            ), array('user_id' => $user_id));

            // Check if the player has already bought this stock previously
            $existing_stock = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}mk_player_items WHERE player_id = %d AND item_id = %d", $player->id, $item_id));

            if ($existing_stock) {
                // Update quantity for existing record
                $new_quantity = $existing_stock->quantity + $quantity;
                $wpdb->update($wpdb->prefix . "mk_player_items", array('quantity' => $new_quantity), array('player_id' => $player->id, 'item_id' => $item_id));
            } else {
                // Insert new record
                $wpdb->insert($wpdb->prefix . "mk_player_items", array(
                    'player_id' => $player->id,
                    'item_id' => $item_id,
                    'quantity' => $quantity
                ));
            }
            // Deduct moves
            $wpdb->update($player_table, array(
                'moves' => $new_moves_total
            ), array('user_id' => $user_id));


            wp_send_json_success(array('message' => 'Stock purchased successfully!'));

        } else {
            wp_send_json_error(array('message' => 'Not enough money to purchase this stock.'));
        }

    } else {
        // Existing logic for food and normal items
        
        $item_price = $item->price * $quantity;
        $item_moves_price = $item->moves_price * $quantity;

        if ($player->money >= $item_price && $player->moves >= $item_moves_price) {
            $money_left = $player->money - $item_price;
            $moves_left = $player->moves - $item_moves_price;

            if ($item->type === "food") {
                // Logic for purchasing food items
                $wpdb->update($player_table, array(
                    'money' => $money_left,
                    'moves' => $moves_left,
                    'food'  => $player->food + $quantity
                ), array('user_id' => $user_id));

                wp_send_json_success(array('message' => 'Food purchased successfully!'));

            } else {
                // Logic for purchasing normal items
                $item_defense = $item->defense * $quantity;
                $item_attack = $item->attack * $quantity;
                
                $wpdb->update($player_table, array(
                    'money' => $money_left,
                    'moves' => $moves_left,
                    'defense' => $player->defense + $item_defense,
                    'attack' => $player->attack + $item_attack
                ), array('user_id' => $user_id));

                // Check if the player has already bought this item previously
                $existing_item = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}mk_player_items WHERE player_id = %d AND item_id = %d", $player->id, $item_id));

                if ($existing_item) {
                    // Update quantity for existing record
                    $new_quantity = $existing_item->quantity + $quantity;
                    $wpdb->update($wpdb->prefix . "mk_player_items", array('quantity' => $new_quantity), array('player_id' => $player->id, 'item_id' => $item_id));
                } else {
                    // Insert new record
                    $wpdb->insert($wpdb->prefix . "mk_player_items", array(
                        'player_id' => $player->id,
                        'item_id' => $item_id,
                        'quantity' => $quantity
                    ));
                }

                wp_send_json_success(array('message' => 'Item purchased successfully!'));
            }
        } else {
            wp_send_json_error(array('message' => 'Not enough resources to purchase this item.'));
        }
    }
}

function meiko_update_stock_prices() {
    global $wpdb;

    // Fetch all stocks
    $stocks = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}meiko_market_items WHERE type = 'stocks'");

    foreach ($stocks as $stock) {
        // Calculate new price based on a random percentage in the range of -20% to +20%
        $percentage = mt_rand(-20, 20) / 100;
        $price_change = $stock->price * $percentage;
        $new_price = $stock->price + $price_change;

        $wpdb->update(
            "{$wpdb->prefix}meiko_market_items",
            array('current_price' => $new_price),
            array('id' => $stock->id)
        );
    }

    wp_send_json_success(array('message' => 'Stock prices updated!'));
}

function meiko_sell_stock_callback() {
    global $wpdb;

    check_ajax_referer('meiko_sell_nonce', 'nonce'); // Verifies the request for security

    $item_id = intval($_POST['item_id']);
    $quantity = intval($_POST['quantity']);
    $user_id = get_current_user_id();

    if ($quantity <= 0) {
        wp_send_json_error(array('message' => 'Invalid quantity.'));
        return;
    }

    // Fetch the player record
    $player_table = $wpdb->prefix . "mk_players";
    $player = $wpdb->get_row($wpdb->prepare("SELECT * FROM $player_table WHERE user_id = %d", $user_id));

    if (!$player) {
        wp_send_json_error(array('message' => 'Player not found.'));
        return;
    }

    // Fetch the stock item details
    $item_table = $wpdb->prefix . "mk_player_items";
    $item = $wpdb->get_row($wpdb->prepare("SELECT * FROM $item_table WHERE player_id = %d AND item_id = %d", $player->id, $item_id));

    if (!$item || $item->quantity < $quantity) {
        wp_send_json_error(array('message' => 'You do not own enough of this stock to sell.'));
        return;
    }

    // Fetch the stock price
    $market_table = $wpdb->prefix . "meiko_market_items";
    $stock = $wpdb->get_row($wpdb->prepare("SELECT * FROM $market_table WHERE id = %d AND type = 'stocks'", $item_id));

    if (!$stock) {
        wp_send_json_error(array('message' => 'Invalid stock item.'));
        return;
    }

    $money_earned = $stock->current_price * $quantity;

    // Deduct the stock quantity or remove the record
    if ($item->quantity == $quantity) {
        // If selling all stocks, delete the record
        $wpdb->delete($item_table, array('id' => $item->id));
    } else {
        // Update the quantity
        $new_quantity = $item->quantity - $quantity;
        $wpdb->update($item_table, array('quantity' => $new_quantity), array('id' => $item->id));
    }

    if ($wpdb->last_error) {
        wp_send_json_error(array('message' => 'Database error while updating stock quantity.'));
        return;
    }

    // Add the money earned to the player's balance
    $new_money_total = $player->money + $money_earned;
    $wpdb->update($player_table, array('money' => $new_money_total), array('user_id' => $user_id));

    if ($wpdb->last_error) {
        wp_send_json_error(array('message' => 'Database error while updating player money.'));
        return;
    }

    wp_send_json_success(array('message' => 'Stock sold successfully! You earned ' . $money_earned . ' money.'));
}
add_action('wp_ajax_sell_stock', 'meiko_sell_stock_callback');


function meiko_buy_seeds_callback() {
    global $wpdb;

    // Debug start
    error_log("Buy seeds callback triggered");

    $user_id = get_current_user_id();
    $plant_name = sanitize_text_field($_POST['plant_name']);
    $amount = intval($_POST['amount']);

    // Debug received data
    error_log("Plant Name: $plant_name, Amount: $amount");

    // Fetch seed price
    $seed_price = $wpdb->get_var($wpdb->prepare("SELECT seeds_price FROM {$wpdb->prefix}meiko_market_items WHERE name = %s AND type = 'plant'", $plant_name));
    if ($seed_price === null) {
        wp_send_json_error(array('message' => 'Invalid plant name or item type.'));
        error_log("Invalid plant name or item type.");
        return;
    }

    $total_cost = $seed_price * $amount;
    $player_money = $wpdb->get_var($wpdb->prepare("SELECT money FROM {$wpdb->prefix}mk_players WHERE user_id = %d", $user_id));

    if ($player_money >= $total_cost) {
        $new_balance = $player_money - $total_cost;

        // Update player money
        $wpdb->update("{$wpdb->prefix}mk_players", array('money' => $new_balance), array('user_id' => $user_id));
        error_log("Player balance updated. New balance: $new_balance");

        // Update or insert seeds in mk_owned
        $owned_seeds = $wpdb->get_var($wpdb->prepare("SELECT seeds FROM {$wpdb->prefix}mk_owned WHERE player_id = %d AND plant_name = %s", $user_id, $plant_name));

        if ($owned_seeds === null) {
            $wpdb->insert("{$wpdb->prefix}mk_owned", array(
                'player_id' => $user_id,
                'plant_name' => $plant_name,
                'seeds' => $amount,
                'quantity' => 0
            ));
            error_log("New plant record created for $plant_name with $amount seeds.");
        } else {
            $new_seed_count = $owned_seeds + $amount;
            $wpdb->update("{$wpdb->prefix}mk_owned", array('seeds' => $new_seed_count), array('player_id' => $user_id, 'plant_name' => $plant_name));
            error_log("Updated seeds for $plant_name. New total: $new_seed_count.");
        }

        wp_send_json_success(array('message' => 'Seeds purchased successfully!'));
    } else {
        error_log("Insufficient funds. Player has $player_money, needs $total_cost.");
        wp_send_json_error(array('message' => 'Not enough money!'));
    }
}
add_action('wp_ajax_buy_seeds', 'meiko_buy_seeds_callback');

function meiko_sell_plants_callback() {
    global $wpdb;

    $user_id = get_current_user_id();
    $plant_name = sanitize_text_field($_POST['plant_name']);
    $amount = intval($_POST['amount']);

    // Fetch plant price
    $plant_price = $wpdb->get_var($wpdb->prepare("SELECT price FROM {$wpdb->prefix}meiko_market_items WHERE name = %s AND type = 'plant'", $plant_name));
    $total_money_earned = $plant_price * $amount;

    $owned_plants = $wpdb->get_var($wpdb->prepare("SELECT quantity FROM {$wpdb->prefix}mk_owned WHERE player_id = %d AND plant_name = %s", $user_id, $plant_name));

    if ($owned_plants >= $amount) {
        $new_quantity = $owned_plants - $amount;
        $wpdb->update("{$wpdb->prefix}mk_owned", array('quantity' => $new_quantity), array('player_id' => $user_id, 'plant_name' => $plant_name));

        $player_money = $wpdb->get_var($wpdb->prepare("SELECT money FROM {$wpdb->prefix}mk_players WHERE user_id = %d", $user_id));
        $new_balance = $player_money + $total_money_earned;
        $wpdb->update("{$wpdb->prefix}mk_players", array('money' => $new_balance), array('user_id' => $user_id));

        wp_send_json_success(array('message' => 'Plants sold successfully!'));
    } else {
        wp_send_json_error(array('message' => 'Not enough plants to sell!'));
    }
}
add_action('wp_ajax_sell_plants', 'meiko_sell_plants_callback');

?>