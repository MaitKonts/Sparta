<?php
/**
 * Plugin Name: Meiko
 * Description: An Original RegumWeb Plugin
 * Version: 0.1.3
 * Author: RegumWeb
 */

// Prevent direct access to the file
if (!defined('ABSPATH')) {
    exit;
}

// Start session if not already started
if (!session_id()) {
    session_start();
    // DEBUG: Session started
    error_log('Meiko Plugin: Session started');
}

// Base plugin directory
$plugin_dir = plugin_dir_path(__FILE__);

// List of required files
$required_files = [
    'tables', 'market', 'login', 'register', 'ad-packs', 'bank', 'housing',
    'jobs', 'leaderboard', 'profile', 'school', 'score', 'chat', 'cars', 'email',
    'fighting', 'faction', 'radio', 'casino', 'settings', 'ranks', 'crypto', 'admin', 'plants', 'friends'
];

// Include necessary files
foreach ($required_files as $file) {
    $path = $plugin_dir . 'includes/functions/' . $file . '.php';
    
    if (file_exists($path)) {
        require_once $path;
    } else {
        // DEBUG: File missing
        error_log("Meiko Plugin: Missing file - $file.php");
    }
}

// Activation hooks
$activation_hooks = [
    'meiko_create_players_table', 'meiko_create_jobs_table', 'meiko_create_pack_tables',
    'meiko_create_players_items_table', 'meiko_create_houses_table', 'meiko_create_cars_table',
    'meiko_create_ranks_table', 'meiko_create_chat_table', 'meiko_create_market_items_table',
    'meiko_create_fighting_items_table', 'mk_create_fighting_tables', 'meiko_create_factions_table',
    'meiko_create_plant_tables', 'create_mk_email_table',
    'meiko_create_faction_join_requests_table', 'meiko_create_crypto_table', 'create_mk_owned_crypto', 'create_mk_attack_log_table', 'create_mk_friend_requests_table'
];

foreach ($activation_hooks as $hook) {
    register_activation_hook(__FILE__, $hook);
}

// Deactivation hook
register_deactivation_hook(__FILE__, 'meiko_deactivate');

add_filter('cron_schedules', 'meiko_cron_intervals');

add_shortcode('user_detail', 'display_user_detail');
add_shortcode('meiko_leaderboard_all', 'meiko_all_players_leaderboard');
add_shortcode('meiko_leaderboard_top_10', 'meiko_top_10_players_leaderboard');
add_shortcode('meiko_register', 'meiko_registration_form');
add_shortcode('meiko_login', 'meiko_login_form');
add_shortcode('meiko_bank_system', 'bank_system_func');
add_shortcode('meiko_market_table', 'meiko_market_table_shortcode');
add_shortcode('meiko_housing', 'meiko_housing_shortcode');
add_shortcode('meiko_cars', 'meiko_car_shortcode');
add_shortcode('meiko_school_system', 'meiko_school_system_shortcode');
add_shortcode('meiko_attack_pack', 'meiko_attack_pack_shortcode');
add_shortcode('meiko_hitmen', 'meiko_hitmen_shortcode');
add_shortcode('meiko_guards', 'meiko_guards_shortcode');
add_shortcode('meiko_defense_pack', 'meiko_defense_pack_shortcode');
add_shortcode('meiko_user_profile', 'meiko_user_profile_shortcode');
add_shortcode('meiko_jobs', 'meiko_jobs_shortcode');
add_shortcode('meiko_chat', 'meiko_chat_box');
add_shortcode('meiko_timer', 'meiko_display_timer_shortcode');
add_shortcode('meiko_fighting_system', 'meiko_fighting_system_shortcode');
add_shortcode('meiko_fighting_cost_gain', 'meiko_fighting_cost_gain_shortcode');
add_shortcode('mk_challenges', 'mk_challenges_shortcode');
add_shortcode('mk_challenge_form', 'mk_challenge_form_shortcode');
add_shortcode('meiko_create_faction', 'meiko_create_faction_shortcode');
add_shortcode('meiko_factions_leaderboard', 'meiko_factions_leaderboard');
add_shortcode('meiko_faction_profile', 'meiko_faction_profile_shortcode');
add_shortcode('meiko_display_current_faction', 'meiko_display_current_faction_shortcode');
add_shortcode('meiko_casino', 'meiko_casino');
add_shortcode('meiko_settings', 'meiko_settings_shortcode');
add_shortcode('meiko_logout', 'meiko_logout_button');
add_shortcode('sell_crypto', 'sell_crypto_shortcode');
add_shortcode('mine_crypto', 'mine_crypto_shortcode');
add_shortcode('user_crypto', 'display_user_crypto_shortcode');
add_shortcode('user_plants', 'display_user_plants_shortcode');
add_shortcode('buy_computer', 'buy_computer_shortcode');
add_shortcode('meiko_radio', 'meiko_radio_player');
add_shortcode('meiko_radio_persistent_with_volume', 'meiko_radio_persistent_with_volume_shortcode');

add_action('init', 'meiko_process_login_form');
add_action('template_redirect', 'meiko_redirect_after_login');
add_action('wp_ajax_meiko_buy_item', 'meiko_buy_item_callback');
add_action('wp_ajax_meiko_sell_stock', 'meiko_sell_stock_callback');
add_action('wp_ajax_nopriv_meiko_buy_item', 'meiko_buy_item_callback'); 
add_action('wp_ajax_update_moves', 'handle_moves_update');
add_action('wp_ajax_nopriv_update_moves', 'handle_moves_update');
add_action('wp_enqueue_scripts', 'meiko_enqueue_styles');
add_action('wp_ajax_meiko_attack_user', 'meiko_attack_user_callback');
add_action('wp_ajax_nopriv_meiko_attack_user', 'meiko_attack_user_callback');
add_action('wp_ajax_meiko_fetch_messages', 'meiko_fetch_chat_messages');
add_action('wp_ajax_nopriv_meiko_fetch_messages', 'meiko_fetch_chat_messages');  
add_action('wp_ajax_meiko_save_message', 'meiko_save_message_callback'); 
add_action('wp_ajax_nopriv_meiko_save_message', 'meiko_save_message_callback'); 
add_action('wp_footer', 'meiko_reload_script');
add_action('wp_ajax_meiko_update_stock_prices', 'meiko_update_stock_prices');
add_action('wp_ajax_nopriv_meiko_update_stock_prices', 'meiko_update_stock_prices');
add_action('wp_ajax_update_scores', 'update_scores');
add_action('admin_menu', 'meiko_fighting_styles_submenu');
add_action('wp_ajax_mk_accept_fight', 'mk_accept_fight');
add_action('init', 'meiko_register_faction_profile_cpt');
add_action('init', 'meiko_handle_join_faction');
add_action('wp_ajax_update_faction_scores', 'update_faction_scores');
add_action('wp_ajax_nopriv_update_faction_scores', 'update_faction_scores');
add_action('wp_ajax_meiko_calculate_player_stats_in_faction', 'meiko_calculate_player_stats_in_faction');
add_action('wp_ajax_nopriv_meiko_calculate_player_stats_in_faction', 'meiko_calculate_player_stats_in_faction');
add_action('wp_ajax_update_player_income', 'update_player_income');
add_action('wp_ajax_nopriv_update_player_income', 'update_player_income');
add_action('wp_ajax_update_faction_max_equipment', 'update_faction_max_equipment');
add_action('wp_ajax_nopriv_update_faction_max_equipment', 'update_faction_max_equipment');
add_action('wp_ajax_process_crash_bet', 'process_crash_bet');
add_action('wp_ajax_place_crash_bet', 'place_crash_bet');
add_action('wp_ajax_cash_out_crash', 'cash_out_crash');
add_action('wp_ajax_play_roulette_animation', 'play_roulette_animation');
add_action('admin_menu', 'meiko_main_admin_menu');
add_action('admin_menu', 'meiko_add_submenus');
add_action('admin_menu', function() {
    add_submenu_page('meiko-plugin', 'Radio Upload', 'Radio Upload', 'manage_options', 'meiko-radio', 'meiko_radio_upload_form');
});

function meiko_deactivate() {
    wp_clear_scheduled_hook('meiko_add_moves_event');
}

/* ---- Style ---- */
function meiko_enqueue_styles() {
    wp_enqueue_script('jquery');

    wp_enqueue_script('meiko-script-handle', plugin_dir_url(__FILE__) . 'assets/js/meiko-attack.js', array('jquery'), '1.0.0', true );
    wp_localize_script('meiko-script-handle', 'MeikoAjax', array('ajax_url' => admin_url('admin-ajax.php')));

    wp_enqueue_script('meiko-ajax-script', plugin_dir_url(__FILE__) . 'assets/js/meiko-ajax.js', array('jquery'), '1.0.0', true);
    wp_localize_script('meiko-ajax-script', 'MeikoAjax', array('ajax_url' => admin_url('admin-ajax.php'),'buy_nonce' => wp_create_nonce('meiko_buy_nonce'), 'sell_nonce' => wp_create_nonce('meiko_sell_nonce')));

    wp_enqueue_script('meiko-chat-js', plugin_dir_url(__FILE__) . 'assets/js/meiko-chat.js', array('jquery'), '1.0.0', true);
    wp_localize_script('meiko-chat-js', 'meiko_chat_params', array('ajax_url' => admin_url('admin-ajax.php')));

    wp_enqueue_script('meiko-casino', plugin_dir_url(__FILE__) . 'assets/js/meiko-casino.js', array('jquery'), '1.0.0', true);
    wp_enqueue_script('meiko-market-filter', plugin_dir_url(__FILE__) . 'assets/js/meiko-market-filter.js', array(), '1.0.0', true);

    $casino_data = array(
        'ajax_url'  => admin_url('admin-ajax.php'),
        'security'  => wp_create_nonce('meiko_casino_nonce'),
    );
    wp_localize_script('meiko-casino', 'meikoCasino', $casino_data);
    
    wp_enqueue_style('meiko-styles', plugin_dir_url(__FILE__) . 'assets/css/meiko-styles.css');

    wp_enqueue_script('frontend.js', plugin_dir_url(__FILE__) . 'assets/js/frontend.js', array('jquery'), null, true );
    $data_array = array(
        'server_time' => time(),
        'ajaxurl' => admin_url('admin-ajax.php')
    );
    wp_localize_script('frontend.js', 'serverData', $data_array);
}

// Function to reload the page based on a URL parameter
function meiko_reload_script() {
    echo "
    <script>
        if (window.location.href.indexOf('meiko_reload=true') !== -1) {
            history.replaceState(null, null, window.location.pathname);
            location.reload(true);
        }
    </script>";
}

// Enqueues the WordPress media uploader.
function mk_enqueue_media_uploader() {
    wp_enqueue_media();
}
add_action('wp_enqueue_scripts', 'mk_enqueue_media_uploader');


function meiko_create_stock_history_table() {
    global $wpdb;
    $table_name = $wpdb->prefix . "meiko_stock_history";
    
    $charset_collate = $wpdb->get_charset_collate();
    
    $sql = "CREATE TABLE IF NOT EXISTS $table_name (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        stock_id BIGINT UNSIGNED NOT NULL,
        price FLOAT NOT NULL,
        recorded_at DATETIME DEFAULT CURRENT_TIMESTAMP
    ) $charset_collate;";
    
    require_once ABSPATH . "wp-admin/includes/upgrade.php";
    dbDelta($sql);
}
register_activation_hook(__FILE__, 'meiko_create_stock_history_table');


// Register a custom WordPress cron event for updates
function meiko_scheduled_updates() {
    error_log("meiko_cron_updates started running.");
    
    update_scores();
    meiko_calculate_player_stats_in_faction();
    update_player_income();
    update_faction_scores();
    update_faction_max_equipment();
    meiko_add_moves_to_players();
    error_log("Moves updated.");
    meiko_update_stock_prices();   // ✅ Add update stock prices
    
    error_log("meiko_cron_updates finished running.");
}
add_action('meiko_cron_updates', 'meiko_scheduled_updates');

function meiko_setup_cron_job() {
    if (!wp_next_scheduled('meiko_cron_updates')) {
        wp_schedule_event(time(), 'five_minutes', 'meiko_cron_updates');
    }
}
add_action('init', 'meiko_setup_cron_job');

// Add a custom interval of 10 minutes
function meiko_custom_cron_schedules($schedules) {
    $schedules['ten_minutes'] = array(
        'interval' => 300,  // 600 seconds = 10 minutes
        'display'  => __('Every 5 Minutes'),
    );
    return $schedules;
}
add_filter('cron_schedules', 'meiko_custom_cron_schedules');

