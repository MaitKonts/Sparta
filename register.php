<?php
function meiko_registration_form() {
    $output =     '<div class="meiko-registration-container">
        <form class="meiko-registration-form" method="post" action="">
            <div class="form-group">
                <label for="username">Username:</label>
                <input type="text" name="username" required>
            </div>
            <div class="form-group">
                <label for="email">E-mail:</label>
                <input type="email" name="email" required>
            </div>
            <div class="form-group">
                <label for="password">Password:</label>
                <input type="password" name="password" required>
            </div>
            <div class="form-group">
                <label for="repeat_password">Repeat Password:</label>
                <input type="password" name="repeat_password" required>
            </div>
            <button class="register_user" type="submit" name="register_user">Register</button>
        </form>
    </div>';

    if (isset($_POST['register_user'])) {
        if (is_user_logged_in()) {
            return $output . '<div class="error">You are already registered and logged in!</div>';
        }

        $username = sanitize_text_field($_POST['username']);
        $email = sanitize_email($_POST['email']);
        $password = $_POST['password'];
        $repeat_password = $_POST['repeat_password'];

        // Check if passwords match
        if ($password !== $repeat_password) {
            return $output . '<div class="error">Passwords do not match.</div>';
        }

        // Create a new user
        $user_id = wp_create_user($username, $password, $email);

        // Check for errors
        if (is_wp_error($user_id)) {
            return $output . '<div class="error">' . $user_id->get_error_message() . '</div>';
        }

        // Add user to players table
        global $wpdb;
        $table_name = $wpdb->prefix . "mk_players";
        $wpdb->insert($table_name, array('user_id' => $user_id, 'username' => $username));

        // Calculate score after registration
        meiko_calculate_score($user_id);

        return '<div class="success">Registration successful!</div>';
    }

    return $output;
}
?>