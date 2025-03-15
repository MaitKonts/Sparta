jQuery(document).ready(function($) {
    function fetchMessages() {
        $.ajax({
            url: meiko_chat_params.ajax_url,
            type: 'post',
            data: {
                action: 'meiko_fetch_messages'
            },
            success: function(response) {
                if (response.success) {
                    var messages = response.data;
                    var chatBox = $("#meiko-chat-messages");
                    chatBox.empty();
                    messages.forEach(function(msg) {
                        var time = new Date(msg.timestamp).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
                        var rankColor = ''; // Default color if rank color is not found
                        if (msg.username_color) {
                            rankColor = 'style="color: ' + msg.username_color + ';"';
                        }
                        chatBox.append('<p><strong>[' + time + '] [<span ' + rankColor + '>' + msg.rank + '</span>] <span ' + rankColor + '>' + msg.username + ':</span></strong> ' + msg.message + '</p>');
                    });
                } else {
                    console.error('Error fetching messages.');
                }
            }
        });
    }

    function sendMessage() {
        var message = $("#meiko-chat-input").val();

        if (message.trim() === "") return; // Prevent sending empty messages

        $.ajax({
            url: meiko_chat_params.ajax_url,
            type: 'post',
            data: {
                action: 'meiko_save_message',
                message: message
            },
            success: function(response) {
                if (response.success) {
                    $("#meiko-chat-input").val(''); // Clear the input
                    fetchMessages(); // Fetch the updated messages
                } else {
                    console.error('Error saving message:', response.data.message);
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX error:', error);
            }
        });
    }

    // Trigger sending message on button click
    $("#meiko-send-message").click(function() {
        sendMessage();
    });

    // Trigger sending message on Enter key press
    $("#meiko-chat-input").on("keypress", function(event) {
        if (event.which === 13 || event.keyCode === 13) { // Check for Enter key
            event.preventDefault(); // Prevent default form submission
            sendMessage(); // Send the message
        }
    });

    // Fetch messages initially and then periodically
    fetchMessages();
    setInterval(fetchMessages, 5000);  // Fetches messages every 5 seconds
});
