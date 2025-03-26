<?php
// Function to add the main admin menu for the Meiko Plugin
function meiko_main_admin_menu() {
    add_menu_page(
        'Meiko Plugin',           // Page title
        'Meiko Plugin',           // Menu title
        'manage_options',         // Capability required
        'meiko-plugin',           // Menu slug
        '',                       // We won't define a callback function here yet. If needed, you can specify a function to display the main menu content.
        'dashicons-admin-generic' // Icon URL
    );
    // DEBUG: Main admin menu added
    error_log('Meiko Plugin: Main admin menu added');
}

// Function to add submenus to the main Meiko Plugin admin menu
function meiko_add_submenus() {
    // Manage Main submenu
    add_submenu_page(
        'meiko-plugin', 
        'Meiko Settings', 
        'Meiko Settings', 
        'manage_options', 
        'meiko_settings', 
        'meiko_settings_callback' 
    );
    // Meiko Crypto submenu
    add_submenu_page(
        'meiko-plugin',
        'Meiko Crypto Settings',
        'Meiko Crypto',
        'manage_options',
        'meiko-crypto',
        'meiko_crypto_settings_page'
    );

    // Meiko Ranks submenu
    add_submenu_page(
        'meiko-plugin',
        'Meiko Ranks',
        'Meiko Ranks',
        'manage_options',
        'meiko-ranks',
        'meiko_ranks_page_content'
    );

    // Manage Market Items submenu
    add_submenu_page(
        'meiko-plugin',
        'Manage Market Items',
        'Market Items',
        'manage_options',
        'meiko-market-items',
        'meiko_add_market_item_callback'
    );

    add_submenu_page(
        'meiko-plugin',
        'Manage Plants',
        'Meiko Plants',
        'manage_options',
        'meiko-plants',
        'meiko_manage_plants_callback'
    );

    add_submenu_page(
        'meiko-plugin',
        'Manage Fighting',
        'Meiko Fighting',
        'manage_options',
        'meiko-fighting',
        'meiko_add_fighting_style_callback'
    );

    add_submenu_page(
        'meiko-plugin',
        'Manage Banned Players',
        'Ban Players',
        'manage_options',
        'meiko-ban-players',
        'meiko_ban_players_callback'
    );
    
    // Add a new submenu for game reports
    add_submenu_page(
        'meiko-plugin',
        'Game Reports',
        'Game Reports',
        'manage_options',
        'meiko-game-reports',
        'meiko_game_reports_callback'
    );


    // DEBUG: Submenus added
    error_log('Meiko Plugin: Submenus added');
}

function meiko_settings_callback() {
    // Process the form if it's submitted
    if (isset($_POST['meiko_save_settings']) && wp_verify_nonce($_POST['meiko_settings_nonce'], 'meiko_settings_action')) {
        update_option('meiko_active_users_page', esc_url($_POST['active_users_page']));
        update_option('meiko_guest_page', esc_url($_POST['guest_page']));
        update_option('meiko_guest_menu', sanitize_text_field($_POST['guest_menu'])); // New field for Guest Menu
        echo "<div class='updated'><p>Settings saved.</p></div>";
    }
    
    $active_users_page = get_option('meiko_active_users_page', '');
    $guest_page = get_option('meiko_guest_page', '');
    $guest_menu = get_option('meiko_guest_menu', ''); // Retrieve the Guest Menu option

    echo '<form method="post" action="">';
    
    // Nonce field for security
    wp_nonce_field('meiko_settings_action', 'meiko_settings_nonce');
    
    echo '<h2>Meiko Settings</h2>';
    
    // Active Users Page
    echo '<label for="active_users_page">Active Users Page:</label>';
    echo '<select name="active_users_page">';
    
    $pages = get_pages();
    foreach ($pages as $page) {
        $selected = ($active_users_page == get_permalink($page->ID)) ? 'selected' : '';
        echo "<option value='" . esc_url(get_permalink($page->ID)) . "' $selected>" . esc_html($page->post_title) . "</option>";
    }
    
    echo '</select><br><br>';

    // Guest Page
    echo '<label for="guest_page">Guest Page:</label>';
    echo '<select name="guest_page">';
    
    foreach ($pages as $page) {
        $selected = ($guest_page == get_permalink($page->ID)) ? 'selected' : '';
        echo "<option value='" . esc_url(get_permalink($page->ID)) . "' $selected>" . esc_html($page->post_title) . "</option>";
    }
    
    echo '</select><br><br>';

    // Guest Menu (new field)
    echo '<label for="guest_menu">Guest Menu:</label>';
    echo '<select name="guest_menu">';
    
    $menus = wp_get_nav_menus(); // Retrieve all menus
    foreach ($menus as $menu) {
        $selected = ($guest_menu == $menu->term_id) ? 'selected' : '';
        echo "<option value='" . esc_attr($menu->term_id) . "' $selected>" . esc_html($menu->name) . "</option>";
    }
    
    echo '</select>';
    
    echo '<br><br><input type="submit" name="meiko_save_settings" value="Save Settings">';
    
    echo '</form>';
}

function meiko_manage_plants_callback() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'mk_plants';

    // Handle form submission for adding or updating a plant
    if (isset($_POST['meiko_manage_plants_nonce']) && wp_verify_nonce($_POST['meiko_manage_plants_nonce'], 'meiko_manage_plants')) {
        $name = sanitize_text_field($_POST['name']);
        $grow_time = intval($_POST['grow_time']);
        $watering_time = intval($_POST['watering_time']);
        $price = floatval($_POST['price']);
        $seed_price = floatval($_POST['seed_price']);

        if (isset($_POST['update_id'])) {
            $update_id = intval($_POST['update_id']);
            $wpdb->update($table_name, array(
                'name' => $name,
                'grow_time' => $grow_time,
                'watering_time' => $watering_time,
                'price' => $price,
                'seed_price' => $seed_price
            ), array('id' => $update_id));
            echo '<div class="updated"><p>Plant Updated Successfully!</p></div>';
        } else {
            $wpdb->insert($table_name, array(
                'name' => $name,
                'grow_time' => $grow_time,
                'watering_time' => $watering_time,
                'price' => $price,
                'seed_price' => $seed_price
            ));
            echo '<div class="updated"><p>Plant Added Successfully!</p></div>';
        }
    }

    // Handle plant deletion
    if (isset($_GET['delete'])) {
        $delete_id = intval($_GET['delete']);
        $wpdb->delete($table_name, array('id' => $delete_id));
        echo '<div class="updated"><p>Plant Deleted Successfully!</p></div>';
    }

    // Fetch all plants for display
    $plants = $wpdb->get_results("SELECT * FROM $table_name");

    ?>
    <div class="wrap">
        <h2>Manage Plants</h2>

        <!-- Display existing plants in a table -->
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Grow Time</th>
                    <th>Watering Time</th>
                    <th>Price</th>
                    <th>Seed Price</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($plants as $plant) : ?>
                    <tr>
                        <td><?php echo esc_html($plant->name); ?></td>
                        <td><?php echo esc_html($plant->grow_time); ?></td>
                        <td><?php echo esc_html($plant->watering_time); ?></td>
                        <td><?php echo esc_html($plant->price); ?></td>
                        <td><?php echo esc_html($plant->seed_price); ?></td>
                        <td>
                            <a href="?page=meiko-plants&edit=<?php echo esc_attr($plant->id); ?>">Edit</a> | 
                            <a href="?page=meiko-plants&delete=<?php echo esc_attr($plant->id); ?>" onclick="return confirm('Are you sure you want to delete this plant?');">Delete</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <!-- Add or update plant form -->
        <h2><?php echo isset($_GET['edit']) ? 'Edit Plant' : 'Add New Plant'; ?></h2>
        <form method="post">
            <?php
            wp_nonce_field('meiko_manage_plants', 'meiko_manage_plants_nonce');
            $edit_id = isset($_GET['edit']) ? intval($_GET['edit']) : 0;
            if ($edit_id) {
                $plant = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $edit_id));
                if ($plant) {
                    $name = esc_attr($plant->name);
                    $grow_time = esc_attr($plant->grow_time);
                    $watering_time = esc_attr($plant->watering_time);
                    $price = esc_attr($plant->price);
                    $seed_price = esc_attr($plant->seed_price);
                }
            } else {
                $name = '';
                $grow_time = '';
                $watering_time = '';
                $price = '';
                $seed_price = '';
            }
            ?>
            <input type="hidden" name="update_id" value="<?php echo $edit_id; ?>">
            <table class="form-table">
                <tr valign="top">
                    <th scope="row">Name:</th>
                    <td><input type="text" name="name" value="<?php echo $name; ?>" required /></td>
                </tr>
                <tr valign="top">
                    <th scope="row">Grow Time:</th>
                    <td><input type="number" name="grow_time" value="<?php echo $grow_time; ?>" required /></td>
                </tr>
                <tr valign="top">
                    <th scope="row">Watering Time:</th>
                    <td><input type="number" name="watering_time" value="<?php echo $watering_time; ?>" required /></td>
                </tr>
                <tr valign="top">
                    <th scope="row">Price:</th>
                    <td><input type="number" step="0.01" name="price" value="<?php echo $price; ?>" required /></td>
                </tr>
                <tr valign="top">
                    <th scope="row">Seed Price:</th>
                    <td><input type="number" step="0.01" name="seed_price" value="<?php echo $seed_price; ?>" required /></td>
                </tr>
            </table>
            <?php submit_button(isset($_GET['edit']) ? 'Update Plant' : 'Add Plant'); ?>
        </form>
    </div>
    <?php
}

function meiko_add_fighting_style_callback() {
    global $wpdb;

    // Check if the form was submitted
    if (isset($_POST['meiko_add_fighting_style_nonce']) && wp_verify_nonce($_POST['meiko_add_fighting_style_nonce'], 'meiko_add_fighting_style')) {

        // Collect and sanitize data from the submitted form
        $data = array(
            'name'           => sanitize_text_field($_POST['name']),
            'moves_required' => intval($_POST['moves_required']),
            'money_required' => intval($_POST['money_required']),
            'food_required'  => intval($_POST['food_required']),
            'attack_gain'    => intval($_POST['attack_gain']),
            'defense_gain'   => intval($_POST['defense_gain']),
            'fighting_gain'  => intval($_POST['fighting_gain']),
            'karate_gain' => intval($_POST['karate_gain']),
            'kungfu_gain' => intval($_POST['kungfu_gain']),
            'boxing_gain' => intval($_POST['boxing_gain']),
            'kravmaga_gain' => intval($_POST['kravmaga_gain']),
            'bjj_gain' => intval($_POST['bjj_gain']),
            'ninjutsu_gain' => intval($_POST['ninjutsu_gain'])
        );

        // Insert a new row into the mk_fighting table
        $fighting_table = $wpdb->prefix . "mk_fighting";
        $wpdb->insert($fighting_table, $data);

        echo '<div class="updated"><p>Fighting Style Added Successfully!</p></div>';
    }

    // Form for adding a new fighting style
    ?>
    <div class="wrap">
        <h2>Add New Fighting Style</h2>
        <form method="post">
            <?php wp_nonce_field('meiko_add_fighting_style', 'meiko_add_fighting_style_nonce'); ?>
            <table class="form-table">
                <tr valign="top">
                    <th scope="row">Name:</th>
                    <td><input type="text" name="name" required /></td>
                </tr>
                <tr valign="top">
                    <th scope="row">Moves Required:</th>
                    <td><input type="number" name="moves_required" required /></td>
                </tr>
                <tr valign="top">
                    <th scope="row">Money Required:</th>
                    <td><input type="number" name="money_required" required /></td>
                </tr>
                <tr valign="top">
                    <th scope="row">Food Required:</th>
                    <td><input type="number" name="food_required" required /></td>
                </tr>
                <tr valign="top">
                    <th scope="row">Attack Gain:</th>
                    <td><input type="number" name="attack_gain" required /></td>
                </tr>
                <tr valign="top">
                    <th scope="row">Defense Gain:</th>
                    <td><input type="number" name="defense_gain" required /></td>
                </tr>
                <tr valign="top">
                    <th scope="row">Fighting Gain:</th>
                    <td><input type="number" name="fighting_gain" required /></td>
                </tr>
                <tr valign="top">
                    <th scope="row">Karate Gain:</th>
                    <td><input type="number" name="karate_gain" required /></td>
                </tr>
                <tr valign="top">
                    <th scope="row">Kung Fu Gain:</th>
                    <td><input type="number" name="kungfu_gain" required /></td>
                </tr>
                <tr valign="top">
                    <th scope="row">Boxing Gain:</th>
                    <td><input type="number" name="boxing_gain" required /></td>
                </tr>
                <tr valign="top">
                    <th scope="row">Kravmaga Gain:</th>
                    <td><input type="number" name="kravmaga_gain" required /></td>
                </tr>
                <tr valign="top">
                    <th scope="row">BJJ Gain:</th>
                    <td><input type="number" name="bjj_gain" required /></td>
                </tr>
                <tr valign="top">
                    <th scope="row">Ninjutsu Gain:</th>
                    <td><input type="number" name="ninjutsu_gain" required /></td>
                </tr>
            </table>
            <?php submit_button(); ?>
        </form>
    </div>
    <?php
}

//Ban Callback
function meiko_ban_players_callback() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'mk_players';

    // Handle ban/unban actions
    if (isset($_POST['ban_user'])) {
        $user_id = intval($_POST['user_id']);
        $wpdb->update($table_name, ['banned' => 1], ['user_id' => $user_id]);
        echo "<div class='updated'><p>User banned successfully.</p></div>";
    }
    if (isset($_POST['unban_user'])) {
        $user_id = intval($_POST['user_id']);
        $wpdb->update($table_name, ['banned' => 0], ['user_id' => $user_id]);
        echo "<div class='updated'><p>User unbanned successfully.</p></div>";
    }

    // Get all users
    $users = $wpdb->get_results("SELECT * FROM $table_name");

    echo "<h2>Manage Banned Players</h2>";
    echo "<table class='wp-list-table widefat fixed striped'>";
    echo "<thead><tr><th>Username</th><th>Status</th><th>Action</th></tr></thead>";
    echo "<tbody>";

    foreach ($users as $user) {
        $status = $user->banned ? "Banned" : "Active";
        $action = $user->banned
            ? "<form method='post'><input type='hidden' name='user_id' value='$user->user_id'><button type='submit' name='unban_user'>Unban</button></form>"
            : "<form method='post'><input type='hidden' name='user_id' value='$user->user_id'><button type='submit' name='ban_user'>Ban</button></form>";

        echo "<tr><td>{$user->username}</td><td>{$status}</td><td>{$action}</td></tr>";
    }

    echo "</tbody></table>";
}

// Callback function for the new menu item
function meiko_add_market_item_callback() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'meiko_market_items';

    // Handle form submission
    if (isset($_POST['meiko_add_item_nonce']) && wp_verify_nonce($_POST['meiko_add_item_nonce'], 'meiko_add_item')) {
        $name = sanitize_text_field($_POST['name']);
        $price = floatval($_POST['price']);
        $current_price = floatval($_POST['current_price']);
        $moves_price = floatval($_POST['moves_price']);
        $defense = intval($_POST['defense']);
        $attack = intval($_POST['attack']);
        $type = sanitize_text_field($_POST['type']);

        // Insert new market item into the database
        $wpdb->insert($table_name, array(
            'name' => $name,
            'price' => $price,
            'current_price' => $current_price,
            'moves_price' => $moves_price,
            'defense' => $defense,
            'attack' => $attack,
            'type' => $type
        ));

        echo '<div class="updated"><p>Market item added successfully!</p></div>';
    }

    // Fetch all market items for display
    $market_items = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}meiko_market_items");

    echo '<h2>Manage Market Items</h2>';

    // Display existing market items in a table
    echo '<table class="wp-list-table widefat fixed striped">';
    echo '<thead><tr><th>Name</th><th>Price</th><th>Current Price</th><th>Moves Price</th><th>Defense</th><th>Attack</th><th>Item Score</th><th>Type</th><th>Actions</th></tr></thead>';
    echo '<tbody>';
    foreach ($market_items as $item) {
        echo '<tr>';
        echo '<td>' . esc_html($item->name) . '</td>';
        echo '<td>' . esc_html($item->price) . '</td>';
        echo '<td>' . esc_html($item->current_price) . '</td>';
        echo '<td>' . esc_html($item->moves_price) . '</td>';
        echo '<td>' . esc_html($item->defense) . '</td>';
        echo '<td>' . esc_html($item->attack) . '</td>';
        echo '<td>' . esc_html($item->item_score) . '</td>';
        echo '<td>' . esc_html($item->type) . '</td>';
        echo '<td><a href="?page=meiko-market-items&edit=' . esc_attr($item->id) . '">Edit</a> | <a href="?page=meiko-market-items&delete=' . esc_attr($item->id) . '">Delete</a></td>';
        echo '</tr>';
    }
    echo '</tbody>';
    echo '</table>';

    echo '<h2>Add New Market Item</h2>';
    ?>
    <form method="post">
        <?php wp_nonce_field('meiko_add_item', 'meiko_add_item_nonce'); ?>
        <table class="form-table">
            <tr valign="top">
                <th scope="row">Name:</th>
                <td><input type="text" name="name" required /></td>
            </tr>
            <tr valign="top">
                <th scope="row">Price (in money):</th>
                <td><input type="number" name="price" required /></td>
            </tr>
            <tr valign="top">
                <th scope="row">Current Price:</th>
                <td><input type="number" name="current_price" required /></td>
            </tr>
            <tr valign="top">
                <th scope="row">Price (in moves):</th>
                <td><input type="number" name="moves_price" required /></td>
            </tr>
            <tr valign="top">
                <th scope="row">Defense Points:</th>
                <td><input type="number" name="defense" required /></td>
            </tr>
            <tr valign="top">
                <th scope="row">Attack Points:</th>
                <td><input type="number" name="attack" required /></td>
            </tr>
            <tr valign="top">
                <th scope="row">Item Score:</th>
                <td><input type="number" name="item_score" required /></td>
            </tr>
            <tr valign="top">
                <th scope="row">Type:</th>
                <td>
                    <select name="type">
                        <option value="normal_item">Normal Item</option>
                        <option value="food">Food</option>
                        <option value="stocks">Stocks</option>
                    </select>
                </td>
            </tr>
        </table>
        <?php submit_button('Add Market Item'); ?>
    </form>
    <?php
}

function meiko_game_reports_callback() {
    global $wpdb;
    $table_players = $wpdb->prefix . 'mk_players';
    $table_attacks = $wpdb->prefix . 'mk_attack_log';

    // Fetch statistics
    $total_players = $wpdb->get_var("SELECT COUNT(*) FROM $table_players");
    $active_players = $wpdb->get_var("SELECT COUNT(*) FROM $table_players WHERE banned = 0");
    $banned_players = $wpdb->get_var("SELECT COUNT(*) FROM $table_players WHERE banned = 1");

    // Get top players
    $top_money = $wpdb->get_results("SELECT username, money FROM $table_players ORDER BY money DESC LIMIT 5");
    $top_scores = $wpdb->get_results("SELECT username, score FROM $table_players ORDER BY score DESC LIMIT 5");
    $top_battles = $wpdb->get_results("SELECT attacker, COUNT(*) AS battles FROM $table_attacks GROUP BY attacker ORDER BY battles DESC LIMIT 5");

    echo "<h2>Game Reports</h2>";
    echo "<p><strong>Total Players:</strong> $total_players</p>";
    echo "<p><strong>Active Players:</strong> $active_players</p>";
    echo "<p><strong>Banned Players:</strong> $banned_players</p>";

    echo "<h3>Top 5 Players by Money</h3><ul>";
    foreach ($top_money as $player) {
        echo "<li>{$player->username} - $ {$player->money}</li>";
    }
    echo "</ul>";

    echo "<h3>Top 5 Players by Score</h3><ul>";
    foreach ($top_scores as $player) {
        echo "<li>{$player->username} - Score: {$player->score}</li>";
    }
    echo "</ul>";

    echo "<h3>Top 5 Most Battles Won</h3><ul>";
    foreach ($top_battles as $player) {
        echo "<li>{$player->attacker} - {$player->battles} battles</li>";
    }
    echo "</ul>";
}

?>