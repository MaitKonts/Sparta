<?php
function meiko_create_faction_shortcode($atts) {
    global $wpdb;
    $current_user_id = get_current_user_id();
    $players_table = $wpdb->prefix . "mk_players";
    $factions_table = $wpdb->prefix . "mk_factions";

    $player_data = $wpdb->get_row($wpdb->prepare("SELECT moves, money, faction FROM $players_table WHERE user_id = %d", $current_user_id), ARRAY_A);

    if ($player_data['faction'] !== 'None' && $player_data['faction'] !== null) {
        return "";
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['faction_name'])) {
        $faction_name = sanitize_text_field($_POST['faction_name']);

        if ($player_data['moves'] < 500 || $player_data['money'] < 10000) {
            return "You do not have enough moves or money to create a faction.";
        }

        $wpdb->insert(
            $factions_table,
            array(
                'name' => $faction_name,
                'faction_leader' => $current_user_id,
            )
        );
        // Get the last inserted faction_id
        $faction_id = $wpdb->insert_id;

        // Create a new post of type 'faction_profile' for this faction
        $faction_post_id = wp_insert_post(array(
            'post_title' => $faction_name,
            'post_type' => 'faction_profile',
            'post_status' => 'publish',
            'meta_input' => array(
                'faction_id' => $faction_id,
            ),
        ));

        $wpdb->update(
            $players_table,
            array('moves' => $player_data['moves'] - 500, 'money' => $player_data['money'] - 10000, 'faction' => $faction_name),
            array('user_id' => $current_user_id)
        );
        
        return "Faction created successfully!";
    } else {
        return '<form class="create_faction_form" method="post" action="">
                    <label for="faction_name">Enter Faction Name: </label>
                    <input type="text" name="faction_name" required />
                    <button type="submit">Create Faction</button>
                </form>';
    }
}

function meiko_factions_leaderboard() {
    global $wpdb;
    $factions_table = $wpdb->prefix . "mk_factions";
    $players_table = $wpdb->prefix . "mk_players";
    
    $factions = $wpdb->get_results(
        "SELECT f.*, p.username AS leader_name 
         FROM $factions_table AS f 
         INNER JOIN $players_table AS p ON f.faction_leader = p.user_id 
         ORDER BY f.score DESC"
    );

    $output = '<div class="factions-leaderboard-container">
        <table class="factions-leaderboard-table">
            <thead>
                <tr>
                    <th>Faction</th>
                    <th>Score</th>
                    <th>Leader</th>
                    <th>Members</th>
                </tr>
            </thead>
            <tbody>';

    foreach ($factions as $faction) {
        $faction_slug = sanitize_title($faction->name);
        $faction_profile_url = get_site_url() . '/faction_profile/' . $faction_slug;
        
        // Faction avatar (default if empty)
        $faction_avatar = !empty($faction->avatar) ? esc_url($faction->avatar) : get_template_directory_uri() . '/images/default-avatar.png';

        // Count faction members
        $members_count = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $players_table WHERE faction = %s", $faction->name));

        $output .= '<tr>
            <td>
                <img class="faction-avatar" src="' . $faction_avatar . '" alt="Faction Avatar">
                <a href="' . esc_url($faction_profile_url) . '">' . esc_html($faction->name) . '</a>
            </td>
            <td>' . esc_html($faction->score) . '</td>
            <td>' . esc_html($faction->leader_name) . '</td>
            <td>' . esc_html($members_count) . '/10</td>
        </tr>';
    }
    
    $output .= '</tbody></table></div>';
    
    return $output;
}


function meiko_faction_profile_shortcode($atts) {
    global $wpdb; // Access WordPress's database object
    global $post; // Access the global $post object

    $attributes = shortcode_atts(array('faction_id' => 0), $atts);
    $faction_id = get_post_meta($post->ID, 'faction_id', true);
    $current_user_id = get_current_user_id();
    $message = '';

    // Fetch the faction data
    $faction_data = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}mk_factions WHERE id = %d", $faction_id), ARRAY_A);

    if ($faction_data !== null) {
        $creationDate = date("Y-m-d", strtotime($faction_data['creation_date']));
        $leader_name = get_user_by('id', $faction_data['faction_leader'])->display_name;

        // Fetch faction's avatar
        $faction_avatar = esc_url($faction_data['avatar']);
        $faction_avatar_tag = "<img src='{$faction_avatar}' alt='{$faction_data['name']}' width='200'/>"; // Using custom avatar URL

        // Displaying profile
        $output = '<div class="meiko-faction-profile">';

        if (!empty($message)) {
            $output .= "<div class='meiko-message'>$message</div>";
        }

        $output .= "<div class='meiko-faction-profile-info'>";
        $output .= "<h3>{$faction_data['name']}'s Profile</h3>";
        $output .= $faction_avatar_tag; // Display faction's avatar
        $output .= "<p><strong>Faction Name:</strong> {$faction_data['name']}</p>";
        $output .= "<p><strong>Creation Date:</strong> {$creationDate}</p>";
        $output .= "<p><strong>Faction Score:</strong> {$faction_data['score']}</p>";
        $output .= "<p><strong>Faction Leader:</strong> {$leader_name}</p>";

        // Add the Join Request form
        $output .= '<form method="post" action="">
            <input type="hidden" name="faction_name" value="' . esc_attr($faction_data['name']) . '" />
            <button type="submit" name="join_faction">Request to Join Faction</button>
        </form>';


        $output .= '<div class="meiko-faction-members">';

        // Faction Members Table
        $faction_members = $wpdb->get_results($wpdb->prepare(
            "SELECT user_id, username, score, money FROM {$wpdb->prefix}mk_players WHERE faction = %s ORDER BY score DESC",
            $faction_data['name']
        ));
        

        $output .= '<h3>Members</h3>';
        $output .= '<table>
            <thead>
                <tr>
                    <th>Username</th>
                    <th>Score</th>
                    <th>Money</th>
                </tr>
            </thead>
            <tbody>';

        foreach ($faction_members as $player) {
            $profile_url = get_author_posts_url($player->user_id);
            $output .= '<tr>
                <td><a href="' . esc_url($profile_url) . '">' . esc_html($player->username) . '</a></td>
                <td>' . esc_html($player->score) . '</td>
                <td>' . esc_html($player->money) . '</td>
            </tr>';
        }

        $output .= '</tbody></table>';
        $output .= '</div>'; // Closing the meiko-faction-members div

        $output .= '</div>'; // Closing the meiko-faction-profile div

        // Additional CSS
        $output .= '<style>
            .meiko-faction-profile-info,
            .meiko-faction-members {
                clear: both;
            }
        </style>';

        return $output;
    } else {
        return "Invalid faction!";
    }
}

function meiko_display_current_faction_shortcode($atts) {
    global $wpdb;
    require_once(ABSPATH . 'wp-admin/includes/file.php');
    $current_user_id = get_current_user_id();
    $players_table = $wpdb->prefix . "mk_players";
    $factions_table = $wpdb->prefix . "mk_factions";
    $requests_table = $wpdb->prefix . 'mk_faction_join_requests';

    $player_data = $wpdb->get_row($wpdb->prepare("SELECT faction, money FROM $players_table WHERE user_id = %d", $current_user_id), ARRAY_A);

    if ($player_data['faction'] === 'None' || $player_data['faction'] === null) {
        return "You are currently not in a faction.";
    }

    $faction_data = $wpdb->get_row($wpdb->prepare("SELECT * FROM $factions_table WHERE name = %s", $player_data['faction']), ARRAY_A);

    $leader_name = get_user_by('id', $faction_data['faction_leader'])->display_name;

    // Handle money donation
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['donate_money'])) {
        $donation_amount = intval($_POST['donation_amount']);
        if ($donation_amount > 0 && $donation_amount <= $player_data['money']) {
            // Deduct money from the player
            $wpdb->query($wpdb->prepare("UPDATE $players_table SET money = money - %d WHERE user_id = %d", $donation_amount, $current_user_id));
            // Add money to the faction
            $wpdb->query($wpdb->prepare("UPDATE $factions_table SET money = money + %d WHERE name = %s", $donation_amount, $player_data['faction']));
            // Message to show donation is successful
            echo 'You have successfully donated $' . $donation_amount;
        } else {
            echo 'Invalid amount.';
        }
    }
        
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['leave_faction'])) {
        if ($faction_data['faction_leader'] == $current_user_id) {
            $wpdb->delete($factions_table, array('name' => $player_data['faction']));
        }

        meiko_remove_faction_bonus_from_player($current_user_id);
        
        $wpdb->update($players_table, array('faction' => 'None'), array('user_id' => $current_user_id));
        
        return "You have left the faction.";
    }
    

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['buy_attack_equipment'])) {
            $equipment_quantity = intval($_POST['attack_equipment_quantity']);
            $total_cost = $equipment_quantity * 100;
            $new_total_equipment = $faction_data['attack_equipment'] + $faction_data['defense_equipment'] + $equipment_quantity;
            
            if ($equipment_quantity <= 0) {
                echo 'Invalid quantity.';
            } elseif ($new_total_equipment > $faction_data['max_equipment']) {
                echo 'You have reached max equipment.';
            } elseif ($faction_data['money'] < $total_cost) {
                echo "Your faction doesn't have enough money.";
            } else {
                $wpdb->query($wpdb->prepare("UPDATE $factions_table SET money = money - %d, attack_equipment = attack_equipment + %d, attack = attack + %d * 10 WHERE name = %s", $total_cost, $equipment_quantity, $equipment_quantity, $player_data['faction']));
                echo 'Successfully purchased attack equipment.';
            }
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['buy_defense_equipment'])) {
            $equipment_quantity = intval($_POST['defense_equipment_quantity']);
            $total_cost = $equipment_quantity * 100;
            $new_total_equipment = $faction_data['attack_equipment'] + $faction_data['defense_equipment'] + $equipment_quantity;

            if ($equipment_quantity <= 0) {
                echo 'Invalid quantity.';
            } elseif ($new_total_equipment > $faction_data['max_equipment']) {
                echo 'You have reached max equipment.';
            } elseif ($faction_data['money'] < $total_cost) {
                echo "Your faction doesn't have enough money.";
            } else {
                $wpdb->query($wpdb->prepare("UPDATE $factions_table SET money = money - %d, defense_equipment = defense_equipment + %d, defense = defense + %d * 10 WHERE name = %s", $total_cost, $equipment_quantity, $equipment_quantity, $player_data['faction']));
                echo 'Successfully purchased defense equipment.';
            }
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_faction_avatar'])) {
            if (!empty($_FILES['faction_avatar_file']['name'])) {
                $uploaded_file = $_FILES['faction_avatar_file'];
                
                $upload_overrides = array('test_form' => false);
                $movefile = wp_handle_upload($uploaded_file, $upload_overrides);
                
                if ($movefile && !isset($movefile['error'])) {
                    $faction_avatar = $movefile['url'];
                    
                    // Update faction avatar in `mk_factions` table
                    $wpdb->update(
                        $factions_table,
                        array('avatar' => $faction_avatar),
                        array('name' => $faction_data['name'])
                    );
                    
                    echo 'Faction avatar uploaded successfully.';
                } else {
                    echo 'Error uploading faction avatar: ' . $movefile['error'];
                }
            } else {
                echo 'Please select an avatar image to upload.';
            }
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accept_request'])) {
            $user_id_to_accept = intval($_POST['user_id']);
            
            // Update player's faction in `mk_players` table
            $wpdb->update(
                $players_table,
                array('faction_name' => $faction_data['name']),
                array('user_id' => $user_id_to_accept),
                array('%s'),
                array('%d')
            );

            // Remove request from `mk_faction_join_requests` table
            $wpdb->delete(
                $requests_table,
                array('id' => $user_id_to_accept),
                array('%d')
            );

            meiko_apply_faction_bonus_to_player($user_id_to_accept);
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['refuse_request'])) {
            $user_id_to_refuse = intval($_POST['user_id']);
            $wpdb->delete($requests_table, array('id' => $user_id_to_refuse), array('%d'));
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_leader'])) {
            $new_leader_id = intval($_POST['new_leader']);
        
            // Check if faction has enough money
            if ($faction_data['money'] < 150000) {
                echo '<p class="error-message">The faction does not have enough money to change the leader.</p>';
            } else {
                // Deduct 1000 from the faction money
                $wpdb->query($wpdb->prepare(
                    "UPDATE $factions_table SET money = money - 150000 WHERE name = %s",
                    $faction_data['name']
                ));
        
                // Update the faction leader in the database
                $wpdb->update(
                    $factions_table,
                    array('faction_leader' => $new_leader_id),
                    array('name' => $faction_data['name']),
                    array('%d'),
                    array('%s')
                );
        
                // Log action
                echo '<p class="success-message">The faction leader has been successfully changed.</p>';
        
                // Optionally reload the page to reflect changes
                echo '<script>location.reload();</script>';
            }
        }

    // Handle kicking a member
    if (isset($_POST['kick_member']) && $faction_data['faction_leader'] == $current_user_id) {
        $user_id_to_kick = intval($_POST['user_id']);
        $wpdb->update($players_table, array('faction' => 'None'), array('user_id' => $user_id_to_kick));
        echo 'Member has been successfully kicked from the faction.';
    }
        
    // Faction Avatar
    $faction_avatar = esc_url($faction_data['avatar']);
    $faction_avatar_tag = "<img src='{$faction_avatar}' alt='" . esc_attr($faction_data['name']) . "' width='200'/>";

    // Start Output
    $output = '<div class="meiko-current-faction-profile">';
    $output .= "<h2>Your Current Faction: " . esc_html($faction_data['name']) . "</h2>";
    $output .= $faction_avatar_tag;

    // Tab Headers
    $output .= '<div class="meiko-faction-tabs">';
    $output .= '<button class="tablinks" onclick="openTab(event, \'faction-info\')" id="defaultTab">Faction Info</button>';

    // Add Manage Faction Tab for Leaders
    if ((int) $faction_data['faction_leader'] === $current_user_id) {
        $output .= '<button class="tablinks" onclick="openTab(event, \'manage-faction\')">Manage Faction</button>';
    }

    // Always add Members tab
    $output .= '<button class="tablinks" onclick="openTab(event, \'members\')">Members</button>';
    $output .= '</div>';

    // Tab Content
    // Faction Info Tab
    $output .= '<div id="faction-info" class="tabcontent">';
    $output .= "<p><strong>Faction Name:</strong> " . esc_html($faction_data['name']) . "</p>";
    $output .= "<p><strong>Creation Date:</strong> " . esc_html($faction_data['creation_date']) . "</p>";
    $output .= "<p><strong>Faction Leader:</strong> " . esc_html($leader_name) . "</p>";
    $output .= "<p><strong>Faction Score:</strong> " . esc_html($faction_data['score']) . "</p>";
    $output .= "<p><strong>Faction Money:</strong> $" . esc_html($faction_data['money']) . "</p>";
    $output .= "<p><strong>Faction Attack:</strong> " . esc_html($faction_data['attack']) . "</p>";
    $output .= "<p><strong>Faction Defense:</strong> " . esc_html($faction_data['defense']) . "</p>";
    // Donation Form
    $output .= '<h4 class="donate">Donate to Faction</h4>';
    $output .= '<form method="post" action="">';
    $output .= '<label for="donation_amount">Amount: </label>';
    $output .= '<input type="number" min="1" name="donation_amount" id="donation_amount" />';
    $output .= '<button type="submit" name="donate_money">Donate</button>';
    $output .= '</form>';
    $output .= '</div>';

    // Manage Faction Tab (Visible to Leader Only)
    if ($faction_data['faction_leader'] == $current_user_id) {
        $output .= '<div id="manage-faction" class="tabcontent">';
        $output .= '<h3>Manage Faction</h3>';
        $output .= "<p><strong>Faction Money:</strong> $" . esc_html($faction_data['money']) . "</p>";
        $output .= "<p><strong>Faction Max Equpiment:</strong> " . esc_html($faction_data['max_equipment']) . "</p>";
        $output .= "<p><strong>Faction Attack Equipment:</strong> " . esc_html($faction_data['attack_equipment']) . "</p>";
        $output .= "<p><strong>Faction Defense Equipment:</strong> " . esc_html($faction_data['defense_equipment']) . "</p>";
        // Buy Equipment Forms
        $output .= '<form class="facattack" method="post" action="">';
        $output .= '<label for="attack_equipment_quantity">Buy Attack Equipment: </label>';
        $output .= '<input type="number" min="1" name="attack_equipment_quantity" />';
        $output .= '<button type="submit" name="buy_attack_equipment">Buy</button>';
        $output .= '</form>';

        $output .= '<form method="post" action="">';
        $output .= '<label for="defense_equipment_quantity">Buy Defense Equipment: </label>';
        $output .= '<input type="number" min="1" name="defense_equipment_quantity" />';
        $output .= '<button type="submit" name="buy_defense_equipment">Buy</button>';
        $output .= '</form>';

        // Upload Avatar Form
        $output .= '<form class="facavatar" method="post" enctype="multipart/form-data" action="">';
        $output .= '<label for="faction_avatar_file">Upload Avatar: </label>';
        $output .= '<input type="file" name="faction_avatar_file" accept=".png">';
        $output .= '<button type="submit" name="upload_faction_avatar">Upload</button>';
        $output .= '</form>';
        
        // Change Leader Form
        $faction_members = $wpdb->get_results($wpdb->prepare(
            "SELECT user_id, username FROM {$wpdb->prefix}mk_players WHERE faction = %s AND user_id != %d ORDER BY username ASC",
            $faction_data['name'],
            $current_user_id
        ));

        $output .= '<h4 class="newleader">Change Faction Leader (Cost: $150000)</h4>';
        if (!empty($faction_members)) {
            $output .= '<form method="post" action="">';
            $output .= '<label for="new_leader">Select New Leader:</label>';
            $output .= '<select name="new_leader" id="new_leader" required>';
            foreach ($faction_members as $member) {
                $output .= '<option value="' . esc_attr($member->user_id) . '">' . esc_html($member->username) . '</option>';
            }
            $output .= '</select>';
            $output .= '<button type="submit" name="change_leader">Change Leader</button>';
            $output .= '</form>';
        } else {
            $output .= '<p>No eligible members available to transfer leadership.</p>';
        }

        // Join Requests
        $join_requests = $wpdb->get_results($wpdb->prepare("SELECT * FROM $requests_table WHERE faction_name = %s", $faction_data['name']), ARRAY_A);

        $output .= '<h4 class="joinreq">Join Requests</h4>';
        if (!empty($join_requests)) {
            foreach ($join_requests as $request) {
                $player_name = get_user_by('id', $request['user_id'])->display_name;
                $output .= '<p>';
                $output .= esc_html($player_name);
                $output .= '<form method="post" style="display: inline-block; margin-left: 10px;">';
                $output .= '<input type="hidden" name="user_id" value="' . esc_attr($request['user_id']) . '">';
                $output .= '<button type="submit" name="accept_request" style="background-color: #28a745; color: white; padding: 5px 10px; border: none; cursor: pointer; border-radius: 3px;">Accept</button>';
                $output .= '<button type="submit" name="refuse_request" style="background-color: #dc3545; color: white; padding: 5px 10px; border: none; cursor: pointer; border-radius: 3px;">Decline</button>';
                $output .= '</form>';
                $output .= '</p>';
            }
        } else {
            $output .= '<p>No join requests at the moment.</p>';
        }
    }
    $output .= '</div>';
    /// Members Tab
    $output .= '<div id="members" class="tabcontent">';
    $output .= '<h3>Faction Members</h3>';

    // Fetch faction members
    $faction_members = $wpdb->get_results($wpdb->prepare(
        "SELECT user_id, username, score, money 
        FROM {$wpdb->prefix}mk_players 
        WHERE faction = %s 
        ORDER BY score DESC",
        $faction_data['name']
    ));

    // Ensure the content is rendered for both leader and members
    if (!empty($faction_members)) {
        $output .= '<table>';
        $output .= '<thead>
            <tr>
                <th>Username</th>
                <th>Score</th>
                <th>Money</th>';
        // Add Actions column for the leader
        if ((int) $faction_data['faction_leader'] === $current_user_id) {
            $output .= '<th>Actions</th>';
        }
        $output .= '</tr>
        </thead>';
        $output .= '<tbody>';

        foreach ($faction_members as $member) {
            $profile_url = get_author_posts_url($member->user_id);
            $is_leader = ($member->user_id == $faction_data['faction_leader']);
            $output .= '<tr' . ($is_leader ? ' style="font-weight: bold;"' : '') . '>
                <td><a href="' . esc_url($profile_url) . '">' . esc_html($member->username) . '</a></td>
                <td>' . esc_html($member->score) . '</td>
                <td>' . esc_html($member->money) . '</td>';

            // Add Actions column with Kick button or empty cell
            if ((int) $faction_data['faction_leader'] === $current_user_id) {
                $output .= '<td class="kick-faction-button">';
                // Only show Kick button for members who are not the leader
                if ($member->user_id !== $faction_data['faction_leader']) {
                    $output .= '<form method="post" action="" style="display: inline-block;">';
                    $output .= '<input type="hidden" name="user_id" value="' . esc_attr($member->user_id) . '">';
                    $output .= '<button type="submit" name="kick_member" class="kick-button">Kick</button>';
                    $output .= '</form>';
                }
                $output .= '</td>';
            }

            $output .= '</tr>';
        }

        $output .= '</tbody></table>';
    } else {
        $output .= '<p>No members found for this faction.</p>';
    }

    $output .= '</div>'; // Close Members Tab


    // JavaScript for Tabs
    $output .= '<script>
    function openTab(evt, tabName) {
        // Hide all tabcontent elements
        let tabcontent = document.getElementsByClassName("tabcontent");
        for (let i = 0; i < tabcontent.length; i++) {
            tabcontent[i].style.display = "none";
        }

        // Remove active class from all tablinks
        let tablinks = document.getElementsByClassName("tablinks");
        for (let i = 0; i < tablinks.length; i++) {
            tablinks[i].className = tablinks[i].className.replace(" active", "");
        }

        // Show the current tab and add the active class to the button that opened it
        document.getElementById(tabName).style.display = "block";
        evt.currentTarget.className += " active";
    }

    // Automatically open the first tab on page load
    document.addEventListener("DOMContentLoaded", () => {
        document.getElementById("defaultTab").click();
    });
    </script>';

    return $output;
}

function meiko_register_faction_profile_cpt() {
    $labels = array(
        'name'                  => _x('Faction Profiles', 'Post type general name', 'textdomain'),
        'singular_name'         => _x('Faction Profile', 'Post type singular name', 'textdomain'),
        'menu_name'             => _x('Faction Profiles', 'Admin Menu text', 'textdomain'),
        'name_admin_bar'        => _x('Faction Profile', 'Add New on Toolbar', 'textdomain'),
        'add_new'               => __('Add New', 'textdomain'),
        'add_new_item'          => __('Add New Faction Profile', 'textdomain'),
        'new_item'              => __('New Faction Profile', 'textdomain'),
        'edit_item'             => __('Edit Faction Profile', 'textdomain'),
        'view_item'             => __('View Faction Profile', 'textdomain'),
        'all_items'             => __('All Faction Profiles', 'textdomain'),
        'search_items'          => __('Search Faction Profiles', 'textdomain'),
        'not_found'             => __('No faction profiles found.', 'textdomain'),
        'not_found_in_trash'    => __('No faction profiles found in Trash.', 'textdomain'),
    );

    $args = array(
        'labels'                => $labels,
        'public'                => true,
        'publicly_queryable'    => true,
        'show_ui'               => true,
        'show_in_menu'          => 'meiko-plugin',
        'query_var'             => true,
        'rewrite'               => array('slug' => 'faction_profile'),
        'capability_type'       => 'post',
        'has_archive'           => true,
        'hierarchical'          => false,
        'menu_position'         => null,
        'supports'              => array('title', 'editor', 'author', 'thumbnail', 'excerpt', 'comments'),
    );

    register_post_type('faction_profile', $args);
}

function meiko_handle_join_faction() {
    global $wpdb;
    $current_user_id = get_current_user_id();
    $requests_table = $wpdb->prefix . 'mk_faction_join_requests';
    $players_table = $wpdb->prefix . 'mk_players';
    $factions_table = $wpdb->prefix . 'mk_factions';

    // Handle Sending of Join Request
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['join_faction'])) {
        $faction_name = sanitize_text_field($_POST['faction_name']);
    
        // Check if the player is already in a faction
        $player_faction = $wpdb->get_var($wpdb->prepare("SELECT faction FROM $players_table WHERE user_id = %d", $current_user_id));
        if ($player_faction && $player_faction != 'None') {
            return 'You are already in a faction. You cannot send a request to join another faction.';
        }

        // Check if the player has already requested to join this faction
        $existing_request = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $requests_table WHERE user_id = %d AND faction_name = %s", $current_user_id, $faction_name));
        if ($existing_request > 0) {
            return 'You have already sent a request to join this faction.';
        }
        
        // Check current number of members in the faction
        $current_members_count = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $players_table WHERE faction = %s", $faction_name));
    
        // If the current member count is less than 10, allow sending the request
        if ($current_members_count < 10) {
            $wpdb->insert($requests_table, [
                'user_id' => $current_user_id,
                'faction_name' => $faction_name
            ]);
        
            return 'Your request to join the faction has been sent.';
        } else {
            return 'This faction has reached the maximum limit of members. You cannot send a request to join.';
        }
    }

    // Handle Acceptance of Join Request
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accept_request'])) {
        $user_id_to_accept = intval($_POST['user_id']);
    
        $faction = $wpdb->get_row($wpdb->prepare("SELECT * FROM $factions_table WHERE faction_leader = %d", $current_user_id), ARRAY_A);
    
        if ($faction) {
            // Check current number of members in the faction
            $current_members_count = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $players_table WHERE faction = %s", $faction['name']));
        
            // If the current member count is less than 10, add the new member
            if ($current_members_count < 10) {
                $wpdb->update($players_table, ['faction' => $faction['name']], ['user_id' => $user_id_to_accept]);
                $wpdb->delete($requests_table, ['user_id' => $user_id_to_accept]);
            
                return 'Player has been added to your faction.';
            } else {
                return 'This faction has reached the maximum limit of members.';
            }
        } else {
            return 'You are not authorized to accept this request.';
        }
    }


    // Handle Refusal of Join Request
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['refuse_request'])) {
        $user_id_to_refuse = intval($_POST['user_id']);
        
        $faction = $wpdb->get_row($wpdb->prepare("SELECT * FROM $factions_table WHERE faction_leader = %d", $current_user_id), ARRAY_A);
        
        if ($faction) {
            $wpdb->delete($requests_table, ['user_id' => $user_id_to_refuse]);
            
            return 'Join request has been refused.';
        } else {
            return 'You are not authorized to refuse this request.';
        }
    }

    return null;
}

function update_faction_scores() {
    global $wpdb;

    // Get a list of all factions
    $factions_table = $wpdb->prefix . "mk_factions";
    $players_table = $wpdb->prefix . "mk_players";
    $factions = $wpdb->get_results("SELECT * FROM $factions_table");

    foreach ($factions as $faction) {
        // Get players in this faction
        $players = $wpdb->get_results($wpdb->prepare("SELECT * FROM $players_table WHERE faction = %s", $faction->name));

        // Calculate 10% of each player's score and sum it up
        $total_score = 0;
        foreach ($players as $player) {
            $total_score += $player->score * 0.1;
        }

        // Update the faction's score in the mk_factions table
        $wpdb->update(
            $factions_table,
            array('score' => $total_score),
            array('id' => $faction->id),
            array('%d'),
            array('%d')
        );
    }
}

function meiko_apply_faction_bonus_to_player($player_id) {
    global $wpdb;
    $players_table = $wpdb->prefix . "mk_players";
    $factions_table = $wpdb->prefix . "mk_factions";
    
    $player_data = $wpdb->get_row($wpdb->prepare("SELECT * FROM $players_table WHERE user_id = %d", $player_id), ARRAY_A);
    $faction_data = $wpdb->get_row($wpdb->prepare("SELECT * FROM $factions_table WHERE name = %s", $player_data['faction']), ARRAY_A);
    
    $new_total_attack = $player_data['total_attack'] + $faction_data['attack'];
    $new_total_defense = $player_data['total_defense'] + $faction_data['defense'];
    
    $wpdb->update(
        $players_table,
        array('total_attack' => $new_total_attack, 'total_defense' => $new_total_defense),
        array('user_id' => $player_id)
    );
}

function meiko_remove_faction_bonus_from_player($player_id) {
    global $wpdb;
    $players_table = $wpdb->prefix . "mk_players";
    $factions_table = $wpdb->prefix . "mk_factions";
    
    $player_data = $wpdb->get_row($wpdb->prepare("SELECT * FROM $players_table WHERE user_id = %d", $player_id), ARRAY_A);
    $faction_data = $wpdb->get_row($wpdb->prepare("SELECT * FROM $factions_table WHERE name = %s", $player_data['faction']), ARRAY_A);
    
    // Update player's total_attack and total_defense by subtracting the faction's bonuses
    $new_total_attack = $player_data['total_attack'] - $faction_data['attack'];
    $new_total_defense = $player_data['total_defense'] - $faction_data['defense'];
    
    $wpdb->update(
        $players_table,
        array('total_attack' => $new_total_attack, 'total_defense' => $new_total_defense),
        array('user_id' => $player_id)
    );
}

function update_faction_max_equipment() {
    global $wpdb;
    $factions_table = $wpdb->prefix . "mk_factions";
    
    $factions = $wpdb->get_results("SELECT * FROM $factions_table", ARRAY_A);
    
    foreach ($factions as $faction) {
        $new_max_equipment = round($faction['score'] * 0.10);
        $wpdb->update(
            $factions_table,
            array('max_equipment' => $new_max_equipment),
            array('id' => $faction['id']),
            array('%d'),
            array('%d')
        );
    }
    
    echo 'Successfully updated max equipment for all factions based on their scores.';
    wp_die(); // All ajax handlers should die when finished
}
?>