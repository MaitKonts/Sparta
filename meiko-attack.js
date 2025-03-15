jQuery(document).ready(function($) {
    $('.attack-user-btn').on('click', function() {
        var userId = $(this).data('user-id');
        
        $.ajax({
            url: MeikoAjax.ajax_url,
            type: 'POST',
            data: {
                action: 'meiko_attack_user',
                target_user_id: userId
            },
            success: function(response) {
                alert(response.data.message);
            }
        });
    });
});
