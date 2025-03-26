<?php
function meiko_settings_shortcode() {
    require_once(ABSPATH . 'wp-admin/includes/file.php');

    if (!is_user_logged_in()) {
        return 'Please log in to access your settings.';
    }

    if( isset($_POST['new_username']) ) {
        global $wpdb;
        $new_username = sanitize_text_field($_POST['new_username']);
        $current_user_id = get_current_user_id();
        
        $wpdb->update(
            $wpdb->prefix . 'mk_players',
            array('username' => $new_username),
            array('user_id' => $current_user_id)
        );
    }
    
    if (isset($_FILES['avatar_file'])) {
        $uploaded_file = $_FILES['avatar_file'];
        
        $upload_overrides = array('test_form' => false);
        $movefile = wp_handle_upload($uploaded_file, $upload_overrides);
        
        if ($movefile && !isset($movefile['error'])) {
            global $wpdb;
            $avatar = $movefile['url'];
            $current_user_id = get_current_user_id();
            
            $wpdb->update(
                $wpdb->prefix . 'mk_players',
                array('avatar' => $avatar),
                array('user_id' => $current_user_id)
            );
        } else {
            echo $movefile['error'];
        }
    }

    if( isset($_POST['new_email']) ) {
        $user_id = get_current_user_id();
        wp_update_user( array( 'ID' => $user_id, 'user_email' => $_POST['new_email'] ) );
    }
    
    if( isset($_POST['new_password']) ) {
        $user_id = get_current_user_id();
        wp_set_password( $_POST['new_password'], $user_id );
    }

    if (isset($_POST['player_description'])) {
        global $wpdb;
        $description = sanitize_textarea_field($_POST['player_description']);
        $current_user_id = get_current_user_id();
    
        $wpdb->update(
            $wpdb->prefix . 'mk_players',
            array('description' => $description),
            array('user_id' => $current_user_id)
        );
    }

    ob_start(); // Begin output buffering

    ?>

    <!-- Change Username Form -->
    <form class="new_username" method="post">
        <label>New Username: </label>
        <input type="text" name="new_username">
        <input type="submit" value="Change Username">
    </form>

    <form class="new_avatar" method="post" enctype="multipart/form-data">
        <label>Avatar: </label>
        <input type="hidden" name="avatar" id="avatar">
        <input type="file" name="avatar_file" id="avatar_file" accept=".png" onchange="checkFileSize(this)">
        <input type="submit" value="Upload Avatar">
    </form>

    <script>
        function checkFileSize(input) {
            if (input.files[0].size > 2097152) {
                alert("The file must be less than 2MB.");
                input.value = "";
            }
        }
    </script>

    <!-- Change Email Form -->
    <form class="new_email" method="post">
        <label>New Email: </label>
        <input type="email" name="new_email">
        <input type="submit" value="Change Email">
    </form>

    <!-- Change Password Form -->
    <form class="new_password" method="post">
        <label>New Password: </label>
        <input type="password" name="new_password">
        <input type="submit" value="Change Password">
    </form>

    <!-- Change Description Form -->
    <form class="new_description" method="post">
        <label>Description: </label>
        <textarea name="player_description" rows="5" cols="40"></textarea>
        <input type="submit" value="Save Description">
    </form>

    <?php

    $output = ob_get_clean(); // End output buffering and clear buffer
    return $output;
}
?>