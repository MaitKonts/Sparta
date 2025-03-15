<?php
function meiko_ranks_page_content() {
    global $wpdb;

    $ranks_table = $wpdb->prefix . "mk_ranks";
    $players_table = $wpdb->prefix . "mk_players";

    if (isset($_GET['delete_rank'])) {
        $rank_id = intval($_GET['delete_rank']);
    
        // Delete the rank from the database
        $wpdb->delete($ranks_table, array('id' => $rank_id));
    
        echo '<div class="notice notice-success is-dismissible"><p>Rank deleted successfully.</p></div>';
    }

    // Handle form submission
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {

        if (isset($_POST['meiko_add_rank'])) {
            $rank_name = sanitize_text_field($_POST['rank_name']);
            $see_admin_bar = isset($_POST['see_admin_bar']) ? 1 : 0;
            $username_color = sanitize_text_field($_POST['username_color']);
            $view_permissions = isset($_POST['view_permissions']) ? implode(",", $_POST['view_permissions']) : "";
            $published_on_store = isset($_POST['published_on_store']) ? 1 : 0;
            $description = sanitize_text_field($_POST['description']);
            $tokens_price = intval($_POST['tokens_price']);

            // Insert into DB
            $wpdb->insert($ranks_table, array(
                'rank_name' => $rank_name,
                'see_admin_bar' => $see_admin_bar,
                'username_color' => $username_color,
                'view_permissions' => $view_permissions,
                'published_on_store' => $published_on_store,
                'description' => $description,
                'tokens_price' => $tokens_price,
            ));

        } elseif (isset($_POST['meiko_edit_rank'])) {
            $rank_id = intval($_POST['rank_id']);
            $rank_name = sanitize_text_field($_POST['rank_name']);
            $see_admin_bar = isset($_POST['see_admin_bar']) ? 1 : 0;
            $username_color = sanitize_text_field($_POST['username_color']);
            $view_permissions = isset($_POST['view_permissions']) ? implode(",", $_POST['view_permissions']) : "";

            // Update rank in DB
            $wpdb->update($ranks_table, array(
                'rank_name' => $rank_name,
                'see_admin_bar' => $see_admin_bar,
                'username_color' => $username_color,
                'view_permissions' => $view_permissions,
            ), array('id' => $rank_id));
        }
    }
    
    if (isset($_GET['edit_rank'])) {
        $rank_id = intval($_GET['edit_rank']);
        $rank = $wpdb->get_row($wpdb->prepare("SELECT * FROM $ranks_table WHERE id = %d", $rank_id));
        $pages = get_pages();

        if ($rank) {
            echo '<h2>Edit Rank</h2>';
            echo '<form method="post">';
            echo '<input type="hidden" name="rank_id" value="' . esc_attr($rank_id) . '">';
                
            echo '<label for="rank_name">Rank Name:</label>';
            echo '<input type="text" name="rank_name" value="' . esc_attr($rank->rank_name) . '" required>';
            echo '<br>';
    
            echo '<label for="see_admin_bar">See Admin Bar:</label>';
            echo '<input type="checkbox" name="see_admin_bar"' . ($rank->see_admin_bar ? ' checked' : '') . '>';
            echo '<br>';
    
            echo '<label for="username_color">Username Color:</label>';
            echo '<input type="color" name="username_color" value="' . esc_attr($rank->username_color) . '">';
            echo '<br>';

            echo '<label for="published_on_store">Published On Store:</label>';
            echo '<input type="checkbox" name="published_on_store"' . ($rank->published_on_store ? ' checked' : '') . '>';
            echo '<br>';

            echo '<label for="description">Description:</label>';
            echo '<textarea name="description">' . esc_textarea($rank->description) . '</textarea>';
            echo '<br>';

            echo '<label for="tokens_price">Tokens Price:</label>';
            echo '<input type="number" name="tokens_price" value="' . esc_attr($rank->tokens_price) . '" required>';
            echo '<br>';
    
            echo '<label>View Permissions:</label><br>';
            $selected_permissions = explode(",", $rank->view_permissions);
            foreach (get_pages() as $page) {
                echo '<input type="checkbox" name="view_permissions[]" value="' . $page->ID . '"' . (in_array($page->ID, $selected_permissions) ? ' checked' : '') . '> ' . $page->post_title . '<br>';
            }
            echo '<br>';
    
            echo '<input type="submit" name="meiko_edit_rank" value="Update Rank">';
            echo '</form>';
        } else {
            echo '<p>Rank not found.</p>';
        }
    }

    // Display Ranks in Table with Edit Options
    echo '<h2>Manage Ranks</h2>';
    $ranks = $wpdb->get_results("SELECT * FROM $ranks_table");

    echo '<table class="wp-list-table widefat fixed striped">';
    echo '<thead><tr><th>Name</th><th>Color</th><th>Permissions</th><th>Actions</th></tr></thead>';
    echo '<tbody>';
    foreach ($ranks as $rank) {
        echo '<tr>';
        echo '<td>' . esc_html($rank->rank_name) . '</td>';
        echo '<td style="background-color:' . esc_attr($rank->username_color) . '"></td>';
        echo '<td>';
        
        $permission_ids = explode(",", $rank->view_permissions);
        $permission_titles = array();

        foreach ($permission_ids as $permission_id) {
            $page = get_post($permission_id);
            if ($page) {
                $permission_titles[] = $page->post_title;
            }
        }
        echo esc_html(implode(", ", $permission_titles));

        echo '</td>';
        echo '<td><a href="?page=meiko-ranks&edit_rank=' . esc_attr($rank->id) . '">Edit</a> | <a href="?page=meiko-ranks&delete_rank=' . esc_attr($rank->id) . '" onclick="return confirm(\'Are you sure you want to delete this rank?\');">Delete</a></td>';
        echo '</tr>';
    }
    echo '</tbody>';
    echo '</table>';

    // Display the form to add a new rank
    echo '<h2>Add New Rank</h2>';
    $pages = get_pages();
    echo '<form method="post">';
    echo '<label for="rank_name">Rank Name:</label>';
    echo '<input type="text" name="rank_name" required>';
    echo '<br>';

    echo '<label for="see_admin_bar">See Admin Bar:</label>';
    echo '<input type="checkbox" name="see_admin_bar">';
    echo '<br>';

    echo '<label for="username_color">Username Color:</label>';
    echo '<input type="color" name="username_color">';
    echo '<br>';

    echo '<label>View Permissions:</label><br>';
    foreach ($pages as $page) {
        echo '<input type="checkbox" name="view_permissions[]" value="' . $page->ID . '"> ' . $page->post_title . '<br>';
    }
    echo '<br>';

    echo '<label for="published_on_store">Published On Store:</label>';
    echo '<input type="checkbox" name="published_on_store">';
    echo '<br>';

    echo '<label for="description">Description:</label>';
    echo '<textarea name="description"></textarea>';
    echo '<br>';

    echo '<label for="tokens_price">Tokens Price:</label>';
    echo '<input type="number" name="tokens_price" required>';
    echo '<br>';

    echo '<input type="submit" name="meiko_add_rank" value="Add Rank">';
    echo '</form>';
}

function meiko_get_username_display($user_id) {
    global $wpdb;
    $players_table = $wpdb->prefix . "mk_players";
    $ranks_table = $wpdb->prefix . "mk_ranks";

    if (!$user_id) {
        // Handle guest users
        $rank_name = 'Guest';
        $rank = $wpdb->get_row($wpdb->prepare("SELECT * FROM $ranks_table WHERE rank_name = %s", $rank_name));
        $username = 'Guest'; // You may want to use a placeholder username for guests

        if ($rank) {
            return '<span style="color:'. esc_attr($rank->username_color) .'">'. esc_html($username) .'</span>';
        }

        return esc_html($username);
    }

    $user = $wpdb->get_row($wpdb->prepare("SELECT * FROM $players_table WHERE user_id = %d", $user_id));

    if (!$user) {
        return false; // Or handle this case as required
    }

    $rank = $wpdb->get_row($wpdb->prepare("SELECT * FROM $ranks_table WHERE rank_name = %s", $user->rank));

    if (!$rank) {
        return esc_html($user->username);
    }

    return '<span style="color:'. esc_attr($rank->username_color) .'">'. esc_html($user->username) .'</span>';
}


function meiko_restrict_access() {
    global $wpdb;
    $ranks_table = $wpdb->prefix . "mk_ranks";

    $current_user_id = get_current_user_id();
    $players_table = $wpdb->prefix . "mk_players";
    $user = $wpdb->get_row($wpdb->prepare("SELECT * FROM $players_table WHERE user_id = %d", $current_user_id));

    if ($user) {
        $rank = $wpdb->get_row($wpdb->prepare("SELECT * FROM $ranks_table WHERE rank_name = %s", $user->rank));

        if ($rank) {
            if (strpos($rank->view_permissions, 'no_dashboard') !== false && is_admin()) {
                wp_redirect(home_url());
                exit();
            }
        }
    }
}
add_action('init', 'meiko_restrict_access');

function meiko_hide_admin_bar($show) {
    if (!is_user_logged_in()) {
        return false; // Hide admin bar for guests
    }

    global $wpdb;
    $players_table = $wpdb->prefix . "mk_players";

    $current_user_id = get_current_user_id();
    $user = $wpdb->get_row($wpdb->prepare("SELECT * FROM $players_table WHERE user_id = %d", $current_user_id));

    if ($user) {
        $rank = $user->rank;
        $ranks_to_hide = array('Member', 'V.I.P', 'Elite', 'Elder');

        if (in_array($rank, $ranks_to_hide)) {
            return false; // Hide admin bar
        }
    }

    return $show; // Otherwise, keep the previous state
}
add_filter('show_admin_bar', 'meiko_hide_admin_bar');

function meiko_restrict_page_access() {
    global $wpdb;

    // Define the URL for the start page (replace with your actual start page URL)
    $start_page_url = home_url('/start');

    $ranks_table = $wpdb->prefix . "mk_ranks";
    $players_table = $wpdb->prefix . "mk_players";

    $current_page_id = get_queried_object_id();
    $current_user_id = get_current_user_id();

    // Determine the user's rank
    if ($current_user_id) {
        $user_rank = $wpdb->get_var($wpdb->prepare(
            "SELECT rank FROM $players_table WHERE user_id = %d", 
            $current_user_id
        ));
    } else {
        // Assign 'Guest' rank if user is not logged in
        $user_rank = 'Guest';
    }

    // Ensure rank is set to 'Guest' if undefined
    if (!$user_rank) {
        $user_rank = 'Guest';
    }

    // Allow access to all `/author/` pages
    if (is_author()) {
        return; // Skip further checks for `/author/` pages
    }

    // Allow access to `/faction_profile/` pages
    if (is_singular('faction_profile')) {
        return; // Skip further checks for `/faction_profile/` pages
    }

    // Fetch permissions for the rank
    $view_permissions = $wpdb->get_var($wpdb->prepare(
        "SELECT view_permissions FROM $ranks_table WHERE rank_name = %s", 
        $user_rank
    ));

    // If no permissions are found for the rank, redirect to the start page
    if (!$view_permissions) {
        wp_redirect($start_page_url);
        exit;
    }

    // Split permissions into an array of allowed page IDs
    $allowed_page_ids = explode(",", $view_permissions);

    // Check if the current page is allowed
    if (!in_array($current_page_id, $allowed_page_ids)) {
        wp_redirect($start_page_url);
        exit;
    }
}

add_action('template_redirect', 'meiko_restrict_page_access');

// Filter to change the menu based on user status
function meiko_custom_menu_args($args) {
    if (!is_user_logged_in()) {
        $guest_menu_id = get_option('meiko_guest_menu', '');
        if ($guest_menu_id) {
            $args['menu'] = $guest_menu_id; // Replace menu ID
        }
    }
    return $args;
}

add_filter('wp_nav_menu_args', 'meiko_custom_menu_args');

function meiko_token_shop_shortcode() {
    global $wpdb;
    $ranks_table = $wpdb->prefix . "mk_ranks";
    $output = "";

    $ranks = $wpdb->get_results("SELECT * FROM $ranks_table WHERE published_on_store = 1");

    $output .= '<table>';
    $output .= '<tr><th class="rank-header">Rank</th><th class="description-header">Description</th><th class="price-header">Tokens Price</th><th class="action-header">Action</th></tr>';
    foreach ($ranks as $rank) {
        $output .= '<tr>';
        $output .= '<td class="rank-name">' . esc_html($rank->rank_name) . '</td>';
        $output .= '<td class="rank-description">' . esc_html($rank->description) . '</td>';
        $output .= '<td class="tokens_price">' . esc_html($rank->tokens_price) . '</td>';
        $output .= '<td><button class="meiko-buy-rank" data-rank-name="' . esc_attr($rank->rank_name) . '">Buy</button></td>';
        $output .= '</tr>';
    }
    $output .= '</table>';

    return $output;
}
add_shortcode('meiko_token_shop', 'meiko_token_shop_shortcode');

function meiko_purchase_rank() {
    global $wpdb;

    $players_table = $wpdb->prefix . "mk_players";
    $ranks_table = $wpdb->prefix . "mk_ranks";

    $current_user_id = get_current_user_id();

    if (!$current_user_id) {
        wp_send_json_error("You must be logged in to purchase a rank.");
        return;
    }

    if (!isset($_POST['rank_name'])) {
        wp_send_json_error("Rank not specified.");
        return;
    }

    $rank_name = sanitize_text_field($_POST['rank_name']);

    $rank_details = $wpdb->get_row($wpdb->prepare("SELECT * FROM $ranks_table WHERE rank_name = %s", $rank_name));

    if (!$rank_details) {
        wp_send_json_error("Rank not found.");
        return;
    }

    if (!$rank_details->published_on_store) {
        wp_send_json_error("This rank is not available for purchase.");
        return;
    }

    $player_details = $wpdb->get_row($wpdb->prepare("SELECT * FROM $players_table WHERE user_id = %d", $current_user_id));

    if (!$player_details) {
        wp_send_json_error("Player not found.");
        return;
    }

    if ($player_details->tokens < $rank_details->tokens_price) {
        wp_send_json_error("You don't have enough tokens to purchase this rank.");
        return;
    }

    $new_token_count = $player_details->tokens - $rank_details->tokens_price;

    $update_data = array(
        'tokens' => $new_token_count,
        'rank' => $rank_name
    );

    $update_where = array('user_id' => $current_user_id);

    $wpdb->update($players_table, $update_data, $update_where);

    wp_send_json_success("Successfully purchased rank!");
    exit();
}

add_action('wp_ajax_meiko_purchase_rank', 'meiko_purchase_rank');
?>
