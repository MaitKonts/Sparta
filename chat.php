<?php

function meiko_chat_box() {
    return '<div class="card-border" id="meiko-chat-messages"></div>
            <input type="text" id="meiko-chat-input" />
            <button class="meiko_send_chat" id="meiko-send-message";">Send</button>';
}
add_shortcode('meiko_chat', 'meiko_chat_box');

function meiko_fetch_chat_messages() {
    global $wpdb;
    $table_name_chat = $wpdb->prefix . "mk_chat";
    $table_name_players = $wpdb->prefix . "mk_players";
    $table_name_ranks = $wpdb->prefix . "mk_ranks"; // Add this line

    $results = $wpdb->get_results("
        SELECT mc.message, mc.timestamp, p.username, p.rank, r.username_color
        FROM $table_name_chat mc
        JOIN wp_users u ON mc.user_id = u.ID
        JOIN $table_name_players p ON p.user_id = u.ID
        LEFT JOIN $table_name_ranks r ON r.rank_name = p.rank
        ORDER BY mc.timestamp DESC 
        LIMIT 10
    ", ARRAY_A);

    // DEBUG: Fetched chat messages
    error_log('Meiko Plugin: Fetched chat messages');

    wp_send_json_success($results);
}

// Define the AJAX callback for saving messages
function meiko_save_message_callback() {
    global $wpdb;

    if (!isset($_POST['message']) || empty($_POST['message'])) {
        wp_send_json_error(array('message' => 'Message content is empty.'));
    }

    $message_content = sanitize_text_field($_POST['message']);
    $user_id = get_current_user_id(); 

    $table_name = $wpdb->prefix . 'mk_chat';
    
    $result = $wpdb->insert($table_name, array(
        'user_id' => $user_id,
        'message' => $message_content,
        'timestamp' => current_time('mysql')
    ));

    if ($result === false) {
        error_log('Meiko Plugin: Error saving the message.');
        wp_send_json_error(array('message' => 'Error saving the message.'));
    } else {
        error_log('Meiko Plugin: Message saved successfully.');
        wp_send_json_success(array('message' => 'Message saved.'));
    }

    wp_die();
}

?>