<?php
function meiko_force_logout_banned_users() {
    if (is_user_logged_in()) {
        global $wpdb;
        $table_name = $wpdb->prefix . "mk_players";
        $user_id = get_current_user_id();
        $banned = $wpdb->get_var($wpdb->prepare("SELECT banned FROM $table_name WHERE user_id = %d", $user_id));

        if ($banned) {
            wp_logout();
            wp_redirect(home_url());
            exit;
        }
    }
}
add_action('init', 'meiko_force_logout_banned_users');

function meiko_login_form() {
    $message = get_transient('meiko_login_message');
    if ($message) {
        echo '<div class="message">' . $message . '</div>';
        delete_transient('meiko_login_message');
    }

    $nonce_field = wp_nonce_field('meiko_login_action', 'meiko_login_nonce', true, false);

    $output = '<div class="meiko-login-container">
        <form class="meiko-login-form" method="post" action="">
          <div class="input-container">
            <div class="input-content">
                <div class="input-dist">
                    <div class="input-type">
                        <input class="input-is" type="text" name="username" placeholder="Username" required>
                        
                        <input class="input-is" type="password" name="password" placeholder="Password" required>
                        
                        ' . $nonce_field . '
                        </div>
                </div>
            </div>
            </div>
            <button class="login_user" type="submit" name="login_user">Login</button>
        </form>
    </div>';

    return $output;
}

function meiko_process_login_form() {
    if (isset($_POST['login_user']) && wp_verify_nonce($_POST['meiko_login_nonce'], 'meiko_login_action')) {
        if (is_user_logged_in()) {
            set_transient('meiko_login_message', 'You are already logged in!', 60);
        } else {
            $credentials = array(
                'user_login'    => sanitize_text_field($_POST['username']),
                'user_password' => $_POST['password'],
                'remember'      => true
            );

            $user = wp_signon($credentials, false);
        
            if (is_wp_error($user)) {
                set_transient('meiko_login_message', $user->get_error_message(), 60);
            } else {
                global $wpdb;
                $table_name = $wpdb->prefix . "mk_players";
                $banned = $wpdb->get_var($wpdb->prepare("SELECT banned FROM $table_name WHERE user_id = %d", $user->ID));

                if ($banned) {
                    set_transient('meiko_login_message', 'Your account is banned.', 60);
                    wp_logout();
                    wp_redirect(home_url());
                    exit;
                }
                set_transient('meiko_login_message', 'Successfully logged in!', 60);
                wp_redirect(home_url());
                exit;
            }
        }
    }
}


function meiko_redirect_after_login() {
    // If our session flag is set, handle the redirect and then clear the flag
    if (isset($_SESSION['meiko_login_success']) && $_SESSION['meiko_login_success'] === true) {
        unset($_SESSION['meiko_login_success']); // Clear the flag
        wp_redirect(home_url());
        exit;
    }
}

function meiko_logout_button() {
    // Check if the user is not logged in
    if (!is_user_logged_in()) {
        return 'You are not logged in!';
    }
    
    $current_user = wp_get_current_user();
    $user_id = $current_user->ID;
    
    global $wpdb;
    $table_name_players = $wpdb->prefix . "mk_players";
    $player = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name_players WHERE user_id = %d", $user_id));

    // Use the "username" field from mk_players table
    $username = ($player && isset($player->username)) ? esc_html($player->username) : '';

    $redirect_url = home_url(); // Redirecting to the homepage after logout
    $logout_url = wp_logout_url($redirect_url);
    
    return '<a href="' . esc_url($logout_url) . '" class="logout-button">Logout</a>';
}
?>