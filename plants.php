<?php

function meiko_grow_plant_shortcode() {
    global $wpdb;
    $user_id = get_current_user_id();
    $current_time = current_time('timestamp');

    $player = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}mk_players WHERE user_id = %d", $user_id));

    // Check if the user is already growing a plant
    if (isset($player->plant_end_time) && $current_time < strtotime($player->plant_end_time)) {
        $cooldown_remaining = strtotime($player->plant_end_time) - $current_time;
        $hours = floor($cooldown_remaining / 3600);
        $minutes = floor(($cooldown_remaining / 60) % 60);
        $seconds = $cooldown_remaining % 60;
        $output = "<div>You're still growing a plant! Wait for {$hours}h {$minutes}m {$seconds}s until it's fully grown.</div>";

        // Display the watering timer
        $watering_remaining = strtotime($player->watering_time) - $current_time;
        $water_hours = floor($watering_remaining / 3600);
        $water_minutes = floor(($watering_remaining / 60) % 60);
        $water_seconds = $watering_remaining % 60;

        // Check if the watering timer has reached 0 and if the player is within the 30-minute grace period
        if ($watering_remaining <= 0 && $watering_remaining > -1800) {
            $output .= "<div>Water your plant quickly! You have less than 30 minutes or it will spoil.</div>";
        } elseif ($watering_remaining <= -1800) {
            // The plant spoils if not watered in time
            $wpdb->update("{$wpdb->prefix}mk_owned", array('seeds_growing' => 0), array('player_id' => $user_id));

            // Display the clean up button
            $output .= '<div>Your plant has spoiled because you didn\'t water it in time!</div>';
            $output .= '<form method="post" class="meiko-clean-up-form">';
            $output .= '<button class="mk-clean-up" type="submit" name="clean_up">Clean Up</button>';
            $output .= '</form>';

            // Handle the form submission for cleaning up the spoiled plant
            if (isset($_POST['clean_up'])) {
                // Reset the watering_time in mk_players
                $wpdb->update(
                    "{$wpdb->prefix}mk_players",
                    array('watering_time' => '0000-00-00 00:00:00', 'plant_end_time' => '0000-00-00 00:00:00'),
                    array('user_id' => $user_id)
                );
                return "<div>You've cleaned up the spoiled plant. You can now start growing a new one!</div>";
            }

            return $output;
        } else {
            $output .= "<div>Time left to water the plant: {$water_hours}h {$water_minutes}m {$water_seconds}s</div>";
        }

        $plant_name = $wpdb->get_var($wpdb->prepare("SELECT plant_name FROM {$wpdb->prefix}mk_owned WHERE player_id = %d AND seeds_growing > 0", $user_id));
        // Display the "Water Plant" button
        $output .= '<form method="post" class="meiko-water-plant-form">
        <input type="hidden" name="watering_plant_name" value="' . esc_attr($plant_name) . '">
        <button class="mk-water" type="submit" name="water">Water Plant</button>
        </form>';

        // Handle the watering logic
        if (isset($_POST['water'])) {
            error_log("Water plant button pressed.");
            $player = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}mk_players WHERE user_id = %d", $user_id));
            // Fetch the plant that the user is currently growing
            $plant_name = $wpdb->get_var($wpdb->prepare("SELECT plant_name FROM {$wpdb->prefix}mk_owned WHERE player_id = %d AND seeds_growing > 0", $user_id));
            error_log("Plant Name: $plant_name");

            if ($plant_name) {
                // Fetch the watering_time for the specific plant the user is growing
                $plant_watering_time = $wpdb->get_var($wpdb->prepare("SELECT watering_time FROM {$wpdb->prefix}mk_plants WHERE name = %s", $plant_name));
                error_log("Plant Watering Time: $plant_watering_time");

                $current_watering_time = strtotime($player->watering_time);
                error_log("Current Watering Time: " . date('Y-m-d H:i:s', $current_watering_time));

                // Calculate the next watering time
                $next_watering_time = date('Y-m-d H:i:s', $current_time + ($plant_watering_time * 3600)); // Convert hours to seconds
                
                // Update the watering_time in the mk_players table
                $result = $wpdb->update("{$wpdb->prefix}mk_players", array('watering_time' => $next_watering_time), array('user_id' => $user_id));

                // Check if the update was successful
                if ($result === false) {
                    error_log("Error: Failed to update watering time in the database.");
                } else {
                    error_log("Watering time updated successfully.");
                }
            } else {
                error_log("Error: No plant is currently being grown.");
            }
        }

        return $output;
    }


    // Handle the form submission for growing plants
    if (isset($_POST['grow_plants'])) {
        $plant_name = sanitize_text_field($_POST['plant_name']);
        $amount = intval($_POST['amount']);

        // Check if the user has enough seeds
        $owned_seeds = $wpdb->get_var($wpdb->prepare("SELECT seeds FROM {$wpdb->prefix}mk_owned WHERE player_id = %d AND plant_name = %s", $user_id, $plant_name));

        if ($owned_seeds < $amount) {
            return "<div>You don't have enough seeds to grow this plant!</div>";
        }

        // Deduct seeds from the user and add to seeds_growing
        $new_seed_count = $owned_seeds - $amount;
        $wpdb->update("{$wpdb->prefix}mk_owned", array('seeds' => $new_seed_count, 'seeds_growing' => $amount), array('player_id' => $user_id, 'plant_name' => $plant_name));

        // Update plant_end_time and watering_time in mk_players
        $grow_time = $wpdb->get_var($wpdb->prepare("SELECT grow_time FROM {$wpdb->prefix}mk_plants WHERE name = %s", $plant_name));
        $watering_time = $wpdb->get_var($wpdb->prepare("SELECT watering_time FROM {$wpdb->prefix}mk_plants WHERE name = %s", $plant_name));

        $plant_end_time = date('Y-m-d H:i:s', strtotime("+$grow_time hours", $current_time));
        $next_watering_time = date('Y-m-d H:i:s', strtotime("+$watering_time hours", $current_time));

        $wpdb->update("{$wpdb->prefix}mk_players", 
            array(
                'plant_end_time' => $plant_end_time,
                'watering_time' => $next_watering_time
            ),
            array('user_id' => $user_id)
        );
    }


    // Handle successful growth
    $just_started_growing = isset($_POST['grow_plants']);
    if (!$just_started_growing && isset($player->plant_end_time) && $current_time >= strtotime($player->plant_end_time)) {
        if (isset($player->plant_end_time) && $current_time >= strtotime($player->plant_end_time)) {
            echo "Plants have finished growing.";
            $plant_name = $wpdb->get_var($wpdb->prepare("SELECT plant_name FROM {$wpdb->prefix}mk_owned WHERE player_id = %d AND seeds_growing > 0", $user_id));
            
            // Fetch the current quantity
            $current_quantity = $wpdb->get_var($wpdb->prepare("SELECT quantity FROM {$wpdb->prefix}mk_owned WHERE player_id = %d AND plant_name = %s", $user_id, $plant_name));
            
            // Get the current seeds_growing value
            $seeds_growing = $wpdb->get_var($wpdb->prepare("SELECT seeds_growing FROM {$wpdb->prefix}mk_owned WHERE player_id = %d AND plant_name = %s", $user_id, $plant_name));

            // Update seeds_growing to 0 and increase quantity
            $new_quantity = $current_quantity + $seeds_growing;
            $updated = $wpdb->update("{$wpdb->prefix}mk_owned", array('quantity' => $new_quantity, 'seeds_growing' => 0), array('player_id' => $user_id, 'plant_name' => $plant_name));
            }
    }
    // Form Implementation
    $plants = $wpdb->get_results("SELECT name FROM {$wpdb->prefix}mk_plants");
    $output = '<form method="post" class="meiko-grow-plant-form">';
    $output .= '<select name="plant_name">';
    foreach ($plants as $plant) {
        $output .= '<option value="' . esc_attr($plant->name) . '">' . esc_html($plant->name) . '</option>';
    }
    $output .= '</select>';
    $output .= '<input type="number" name="amount" placeholder="Number of plants">';
    $output .= '<button class="mk-grow" type="submit" name="grow_plants">Grow Plants</button>';
    $output .= '</form>';

    return $output;
}


// Add the shortcode for for growing the plants that we are gonna add later
add_shortcode('meiko_grow_plant', 'meiko_grow_plant_shortcode');

function display_user_plants_shortcode($atts) {
    if (!is_user_logged_in()) {
        return 'You must be logged in to view this detail.';
    }

    $user_id = get_current_user_id();
    global $wpdb;

    // Fetch all plants and seeds owned by the user
    $table_name = $wpdb->prefix . "mk_owned";
    $plant_data = $wpdb->get_results($wpdb->prepare("SELECT plant_name, quantity, seeds, seeds_growing FROM $table_name WHERE player_id = %d", $user_id), ARRAY_A);

    if (empty($plant_data)) {
        return '<div>You do not own any plants or seeds.</div>';
    }

    // Generate the HTML for the table
    $output = '<table class="user-plants-table">';
    $output .= '<thead>
        <tr>
            <th>Plant Name</th>
            <th>Quantity Owned</th>
            <th>Seeds</th>
            <th>Seeds Growing</th>
        </tr>
    </thead>';
    $output .= '<tbody>';

    foreach ($plant_data as $plant) {
        $output .= '<tr>';
        $output .= '<td>' . esc_html($plant['plant_name']) . '</td>';
        $output .= '<td>' . esc_html($plant['quantity']) . '</td>';
        $output .= '<td>' . esc_html($plant['seeds']) . '</td>';
        $output .= '<td>' . esc_html($plant['seeds_growing']) . '</td>';
        $output .= '</tr>';
    }

    $output .= '</tbody>';
    $output .= '</table>';

    return $output;
}


function meiko_marketplace_shortcode() {
    global $wpdb;
    
    $user_id = get_current_user_id();
    $plants = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}mk_plants", ARRAY_A);

    $output = '<div class="meiko-plant-marketplace">';

    foreach ($plants as $plant) {
        $output .= '<div class="meiko-plant-item">';

        $output .= '<h3>' . esc_html($plant['name']) . '</h3>';
        $output .= '<p>Plant price: ' . esc_html($plant['price']) . '</p>';
        $output .= '<p>Seed price: ' . esc_html($plant['seed_price']) . '</p>';
        $output .= '<p>Watering time (h): ' . esc_html($plant['watering_time']) . '</p>';
        $output .= '<p>Growing time (h): ' . esc_html($plant['grow_time']) . '</p>';

        // Buy Seeds Form
        $output .= "<form method='post' class='buy-seeds-form'>";
        $output .= "<input type='hidden' name='action' value='buy_seeds'>";
        $output .= "<input type='hidden' name='plant_name' value='" . esc_attr($plant['name']) . "'>";
        $output .= "<label>Buy seeds: </label><input type='number' name='amount' min='1' />";
        $output .= "<input type='submit' value='Buy' />";
        $output .= "</form>";

        // Sell Plants Form
        $output .= "<form method='post' class='sell-plants-form'>";
        $output .= "<input type='hidden' name='action' value='sell_plants'>";
        $output .= "<input type='hidden' name='plant_name' value='" . esc_attr($plant['name']) . "'>";
        $output .= "<label>Sell plants: </label><input type='number' name='amount' min='1' />";
        $output .= "<input type='submit' value='Sell' />";
        $output .= "</form>";

        $output .= '</div>';
    }

    $output .= '</div>';

    return $output;
}
add_shortcode('meiko_marketplace', 'meiko_marketplace_shortcode');

?>