<?php
function meiko_send_message_shortcode() {
    global $wpdb;
    $output = '';
    $current_user_id = get_current_user_id();

    if (isset($_POST['send_message'])) {
        $receiver_username = sanitize_text_field($_POST['receiver_username']);
        $title = sanitize_text_field($_POST['title']);
        $message = sanitize_textarea_field($_POST['message']);

        // Get receiver ID from mk_players table
        $receiver_id = $wpdb->get_var($wpdb->prepare("SELECT id FROM {$wpdb->prefix}mk_players WHERE username = %s", $receiver_username));

        if ($receiver_id) {
            $wpdb->insert(
                "{$wpdb->prefix}mk_email",
                array(
                    'sender_id' => $current_user_id,
                    'receiver_id' => $receiver_id,
                    'title' => $title,
                    'message' => $message
                )
            );
            $output .= 'Message sent successfully!';
        } else {
            $output .= 'Receiver not found!';
        }
    }

    $output .= '<form class="send_message_form" method="post">
        Receiver Username: <input type="text" name="receiver_username" required><br>
        Title: <input type="text" name="title" required><br>
        Message: <textarea name="message" required></textarea><br>
        <input type="submit" name="send_message" value="Send">
    </form>';

    return $output;
}


add_shortcode('meiko_send_message', 'meiko_send_message_shortcode');

function meiko_display_messages_shortcode() {
    global $wpdb;
    $current_user_id = get_current_user_id();
    $messages = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->prefix}mk_email WHERE receiver_id = %d ORDER BY sent_date DESC", $current_user_id));

    $output = '<table border="1" cellspacing="0" cellpadding="5">';
    $output .= '<thead><tr><th>Title</th><th>Sender</th><th>Sent Date</th></tr></thead><tbody>';
    foreach ($messages as $message) {
        $sender_name = $wpdb->get_var($wpdb->prepare("SELECT username FROM {$wpdb->prefix}mk_players WHERE id = %d", $message->sender_id));
        $highlight = $message->is_read ? '' : 'style="background-color: #f2f2f2;"';
        $output .= "<tr $highlight><td><a href='javascript:void(0);' onclick='showModal(\"$message->title\", \"$sender_name\", \"$message->sent_date\", \"$message->message\");'>$message->title</a></td><td>$sender_name</td><td>$message->sent_date</td></tr>";

        // Mark message as read
        $wpdb->update("{$wpdb->prefix}mk_email", array('is_read' => 1), array('id' => $message->id));
    }
    $output .= '</tbody></table>';

    // Modal structure
    $output .= '
    <div id="messageModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal()">&times;</span>
            <h2 id="modalTitle"></h2>
            <p><strong>Sender:</strong> <span id="modalSender"></span></p>
            <p><strong>Sent Date:</strong> <span id="modalDate"></span></p>
            <p id="modalMessage"></p>
        </div>
    </div>';

    // JavaScript for modal
    $output .= '
    <script>
        var modal = document.getElementById("messageModal");

        function showModal(title, sender, date, message) {
            document.getElementById("modalTitle").innerText = title;
            document.getElementById("modalSender").innerText = sender;
            document.getElementById("modalDate").innerText = date;
            document.getElementById("modalMessage").innerText = message;
            modal.style.display = "block";
        }

        function closeModal() {
            modal.style.display = "none";
        }

        window.onclick = function(event) {
            if (event.target == modal) {
                closeModal();
            }
        }
    </script>';

    return $output;
}



add_shortcode('meiko_display_messages', 'meiko_display_messages_shortcode');

function meiko_display_attack_logs_shortcode() {
    global $wpdb;
    $current_user_id = get_current_user_id();
    $logs = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->prefix}mk_attack_log WHERE attacker_id = %d OR defender_id = %d ORDER BY attack_date DESC", $current_user_id, $current_user_id));

    $output = '<table border="1" cellspacing="0" cellpadding="5">';
    $output .= '<thead><tr><th class="log_attacker">Attacker</th><th class="log_defender">Defender</th><th class="log_amount">Amount Stolen</th><th class="log_sus">Attack Successful</th><th class="log_date">Date</th></tr></thead><tbody>';
    foreach ($logs as $log) {
        $attacker_name = get_userdata($log->attacker_id)->display_name;
        $defender_name = get_userdata($log->defender_id)->display_name;
        $attacker_profile_url = get_author_posts_url($log->attacker_id);
        $defender_profile_url = get_author_posts_url($log->defender_id);
        $successful = $log->attack_successful ? 'Yes' : 'No';
        $output .= "<tr><td class='log_attacker'><a href='$attacker_profile_url'>$attacker_name</td><td class='log_defender'><a href='$defender_profile_url'>$defender_name</td><td class='log_amount'>$log->amount_stolen</td><td class='log_sus'>$successful</td><td class='log_date'>$log->attack_date</td></tr>";
    }
    $output .= '</tbody></table>';

    return $output;
}
add_shortcode('meiko_attack_logs', 'meiko_display_attack_logs_shortcode');
?>