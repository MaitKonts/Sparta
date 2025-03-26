<?php
function meiko_user_profile_shortcode($atts) {
    global $wpdb; // Access WordPress's database object
    $output = '';
    $current_user_id = get_current_user_id();

    $attributes = shortcode_atts(
        array('user_id' => get_current_user_id()),
        $atts
    );

    $user_id = intval($attributes['user_id']);
    $user = get_userdata($user_id);

    // Handle the friend request submission
    if (isset($_POST['send_friend_request'])) {
        $receiver_friends = $wpdb->get_var($wpdb->prepare("SELECT friend_ids FROM {$wpdb->prefix}mk_players WHERE id = %d", $user_id));
        $receiver_friends_array = explode(',', $receiver_friends);

        if (in_array($current_user_id, $receiver_friends_array)) {
            $output .= '<script>alert("You are already friends with this user!");</script>';
        } else {
            $wpdb->insert(
                "{$wpdb->prefix}mk_friend_requests",
                array(
                    'sender_id' => $current_user_id,
                    'receiver_id' => $user_id
                )
            );
            $output .= 'Friend request sent!';
        }
    }

    if ($user !== false) {
        $name = $user->display_name;
        $joinDate = date("Y-m-d", strtotime($user->user_registered));

        // Fetch additional user information
        $user_extra_data = $wpdb->get_row($wpdb->prepare("SELECT money, health, score, house_name, car_name, education, faction, rank, description, username, avatar FROM {$wpdb->prefix}mk_players WHERE user_id = %d", $user_id), ARRAY_A);

        // Getting user avatar
        $avatar = esc_url($user_extra_data['avatar']);
        $avatar = "<img src='{$avatar}' alt='{$user->display_name}' width='200'>";  // Using custom avatar URL

        // Displaying profile
        $output = '<div class="meiko-profile">';
        $output .= "<div class='meiko-profile-info'>";
        $output .= "<h2>{$user->display_name}'s Profile</h2>";
        $output .= "<div class='meiko-profile-avatar'> $avatar</div>";
        $output .= "<p><strong>Rank:</strong> {$user_extra_data['rank']}</p>";
        $output .= "<p><strong>Join Date:</strong> {$joinDate}</p>";
        $output .= "<p><strong>Money:</strong> {$user_extra_data['money']}</p>";
        $output .= "<p><strong>Health:</strong> {$user_extra_data['health']}</p>";
        $output .= "<p><strong>Score:</strong> {$user_extra_data['score']}</p>";
        $output .= "<p><strong>House:</strong> {$user_extra_data['house_name']}</p>";
        $output .= "<p><strong>Car:</strong> {$user_extra_data['car_name']}</p>";
        $output .= "<p><strong>Education:</strong> {$user_extra_data['education']}</p>";
        $output .= "<p><strong>Faction:</strong> {$user_extra_data['faction']}</p>";
        $output .= "<p><strong>Description:</strong> {$user_extra_data['description']}</p>";
        $output .= '<button class="attack-user-btn" data-user-id="' . $user->ID . '">Attack User</button>';
        $output .= '<form class="friend-user-btn" method="post">
        <input type="submit" name="send_friend_request" value="Send Friend Request">
        </form>';
        $output .= '</div>'; // Closing the meiko-profile-info div

        $output .= '</div>'; // Closing the meiko-profile div

        return $output;

    } else {
        return "Invalid user!";
    }
}
function meiko_attack_user_callback() {
    global $wpdb; // Access WordPress's database object

    $current_user_id = get_current_user_id();
    $target_user_id = intval($_POST['target_user_id']);

    // Fetch friend_ids for the current user
    $friend_ids = $wpdb->get_var($wpdb->prepare("SELECT friend_ids FROM {$wpdb->prefix}mk_players WHERE user_id = %d", $current_user_id));
    $friend_ids_array = explode(',', $friend_ids);

    // Prevent users from attacking their friends
    if (in_array($target_user_id, $friend_ids_array)) {
        wp_send_json_error(array('message' => "You can't attack your friends!"));
        return;
    }

    // Prevent users from attacking themselves
    if ($current_user_id === $target_user_id) {
        wp_send_json_error(array('message' => "You can't attack yourself!"));
        return;
    }

    // Fetch moves, attack, defense, money, and health for the current user and target user from the mk_players table
    $current_user_data = $wpdb->get_row($wpdb->prepare("SELECT moves, total_attack, total_defense, money, health, faction FROM {$wpdb->prefix}mk_players WHERE user_id = %d", $current_user_id), ARRAY_A);
    $target_user_data = $wpdb->get_row($wpdb->prepare("SELECT total_attack, total_defense, money, health, faction FROM {$wpdb->prefix}mk_players WHERE user_id = %d", $target_user_id), ARRAY_A);

    // Check if factions are the same, and not "None" or NULL
    if ($current_user_data['faction'] == $target_user_data['faction'] && !in_array($current_user_data['faction'], array("None", NULL))) {
        wp_send_json_error(array('message' => "You can't attack your faction members!"));
        return;
    }

    // Check if the current user has at least 50 move points.
    if ($current_user_data['moves'] < 50) {
        wp_send_json_error(array('message' => "You don't have enough move points to attack!"));
        return;
    }

    // Check if the target user's health is less than 10
    if ($target_user_data['health'] < 10) {
        wp_send_json_error(array('message' => "This user's health is too low to be attacked!"));
        return;
    }

    // If current user's attack is greater than target user's defense, proceed
    if ($current_user_data['total_attack'] > $target_user_data['total_defense']) {
        // Calculate the percentage of money to steal based on the difference in points
        $difference = $current_user_data['total_attack'] - $target_user_data['total_defense'];
        $percentage_to_steal = $difference * 0.01;
        $amount_to_steal = min(($percentage_to_steal / 100) * $target_user_data['money'], $target_user_data['money']);

        // Transfer money
        $wpdb->update("{$wpdb->prefix}mk_players",
            array('money' => $current_user_data['money'] + $amount_to_steal),
            array('user_id' => $current_user_id)
        );

        $wpdb->update("{$wpdb->prefix}mk_players",
            array('money' => $target_user_data['money'] - $amount_to_steal),
            array('user_id' => $target_user_id)
        );

        // Deduct move points from the attacker and reduce target's health
        $new_health = $target_user_data['health'] - 10; // Reduce target's health by 10%

        // Deducting 50 moves from the current user (attacker)
        $wpdb->update("{$wpdb->prefix}mk_players",
            array('moves' => $current_user_data['moves'] - 50),
            array('user_id' => $current_user_id)
        );

        // Reduce the health of the target
        $wpdb->update("{$wpdb->prefix}mk_players",
            array('health' => max(0, $new_health)),
            array('user_id' => $target_user_id)
        );

        $wpdb->insert(
            "{$wpdb->prefix}mk_attack_log",
            array(
                'attacker_id' => $current_user_id,
                'defender_id' => $target_user_id,
                'amount_stolen' => $amount_to_steal,
                'attack_successful' => 1
            )
        );
        wp_send_json_success(array('message' => "Attack successful! You stole $amount_to_steal money!"));

    } else {
        // Failed attack, reduce attacker's health by 10%
        $new_health = $current_user_data['health'] - ($current_user_data['health'] * 0.10);
        // Deducting 50 moves from the current user (attacker) on failed attack
        $wpdb->update("{$wpdb->prefix}mk_players",
            array('moves' => $current_user_data['moves'] - 50, 'health' => max(0, $new_health)),
            array('user_id' => $current_user_id)
        );

        // Log the failed attack
        $wpdb->insert(
            "{$wpdb->prefix}mk_attack_log",
            array(
                'attacker_id' => $current_user_id,
                'defender_id' => $target_user_id,
                'attack_successful' => 0
            )
        );
        wp_send_json_error(array('message' => "Attack unsuccessful! Your attack power is less than the target's defense power. Your health was reduced!"));

    }
}
?>