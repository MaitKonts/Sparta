<?php

/* ---- Player Table Creation ---- */
function meiko_create_players_table() {
    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();

    $player_table_name = $wpdb->prefix . "mk_players";
    $players_sql = "CREATE TABLE $player_table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        user_id INT NOT NULL,
        username varchar(255) NOT NULL,
        banned BOOLEAN DEFAULT FALSE;
        score int DEFAULT 0 NOT NULL,
        moves int DEFAULT 2000 NOT NULL,
        health int DEFAULT 100 NOT NULL,
        money float DEFAULT 1000 NOT NULL,
        education int DEFAULT 0 NOT NULL,
        attack int DEFAULT 0 NOT NULL,
        defense int DEFAULT 0 NOT NULL,
        total_attack int DEFAULT 0 NOT NULL,
        total_defense int DEFAULT 0 NOT NULL,
        hitmen_attack int DEFAULT 0 NOT NULL,
        guards_defense int DEFAULT 0 NOT NULL,
        hitmen int DEFAULT 0 NOT NULL,
        guards int DEFAULT 0 NOT NULL,
        current_bank_balance float DEFAULT 0 NOT NULL,
        fighting int DEFAULT 0 NOT NULL,
        karate_fighting int DEFAULT 0 NOT NULL,
        kungfu_fighting int DEFAULT 0 NOT NULL,
        boxing_fighting int DEFAULT 0 NOT NULL,
        kravmaga_fighting int DEFAULT 0 NOT NULL,
        bjj_fighting int DEFAULT 0 NOT NULL,
        ninjutsu_fighting int DEFAULT 0 NOT NULL,
        current_job varchar(255) DEFAULT 'Unemployed' NOT NULL,
        house_name varchar(255) DEFAULT 'Homeless' NOT NULL,
        car_name varchar(255) DEFAULT 'None' NOT NULL,
        faction varchar(255) DEFAULT 'None' NOT NULL,
        food int DEFAULT 0 NOT NULL,
        defense_pack_level varchar(255) DEFAULT 'None' NOT NULL,
        attack_pack_level varchar(255) DEFAULT 'None' NOT NULL,
        rank varchar(255) DEFAULT 'Member' NOT NULL,
        last_study_end_time DATETIME DEFAULT NULL,
        avatar varchar(255) DEFAULT NULL,
        description text DEFAULT NULL,
        training_end_time DATETIME DEFAULT NULL,
        plant_end_time DATETIME DEFAULT NULL,
        watering_time DATETIME DEFAULT NULL,
        tokens int DEFAULT 0 NOT NULL,
        computers int DEFAULT 0 NOT NULL,
        mining_end_time DATETIME DEFAULT NULL,
        friend_ids TEXT DEFAULT '' NOT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($players_sql);
}

/* ---- Jobs Table Creation ---- */
function meiko_create_jobs_table() {
    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();

    $jobs_table_name = $wpdb->prefix . "mk_jobs";
    $jobs_sql = "CREATE TABLE $jobs_table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        name varchar(255) NOT NULL,
        required_education int NOT NULL,
        income float NOT NULL,
        max_bank_balance float DEFAULT 1000 NOT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($jobs_sql);
}

/* ---- Pack Table Creation ---- */
function meiko_create_pack_tables() {
    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();

    // Attack packs
    $attack_table_name = $wpdb->prefix . "mk_attack_packs";
    $attack_sql = "CREATE TABLE $attack_table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        name varchar(255) NOT NULL,
        price float NOT NULL,
        level float NOT NULL,
        attack_score float NOT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";
    dbDelta($attack_sql);

    // Defense packs
    $defense_table_name = $wpdb->prefix . "mk_defense_packs";
    $defense_sql = "CREATE TABLE $defense_table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        name varchar(255) NOT NULL,
        price float NOT NULL,
        level float NOT NULL,
        defense_score float NOT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";
    dbDelta($defense_sql);
}

/* ---- House Table Creation ---- */
function meiko_create_houses_table() {
    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();

    $houses_table_name = $wpdb->prefix . "mk_houses";
    $houses_sql = "CREATE TABLE $houses_table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        name varchar(255) NOT NULL,
        price float NOT NULL,
        house_score float NOT NULL,
        max_plants float NOT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($houses_sql);
}

function meiko_create_cars_table() {
    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();

    $cars_table_name = $wpdb->prefix . "mk_cars";
    $cars_sql = "CREATE TABLE $cars_table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        name varchar(255) NOT NULL,
        price float NOT NULL,
        car_score float NOT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($cars_sql);
}

/* ---- Player Table Creation ---- */
function meiko_create_players_items_table() {
    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();

    $player_items_table_name = $wpdb->prefix . "mk_player_items";
    $items_sql = "CREATE TABLE $player_items_table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        player_id mediumint(9) NOT NULL,
        item_id mediumint(9) NOT NULL,
        quantity mediumint(9) DEFAULT 1 NOT NULL,
        item_score float NOT NULL,
        purchase_date datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($items_sql);
}

function meiko_create_chat_table() {
    global $wpdb;

    $charset_collate = $wpdb->get_charset_collate();
    $table_name = $wpdb->prefix . "mk_chat";

    $sql = "CREATE TABLE $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        user_id mediumint(9) NOT NULL,
        message text NOT NULL,
        timestamp datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}

function meiko_create_market_items_table() {
    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();
    $table_name = $wpdb->prefix . 'meiko_market_items';

    $sql = "CREATE TABLE $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        name varchar(255) NOT NULL,
        price decimal(10,2) NOT NULL,
        current_price decimal(10,2) NOT NULL,
        moves_price decimal(10,2) NOT NULL,
        defense int NOT NULL DEFAULT '0',
        attack int NOT NULL DEFAULT '0',
        item_score float NOT NULL,
        type ENUM('food', 'normal_item', 'stocks') NOT NULL DEFAULT 'normal_item',
        PRIMARY KEY  (id)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}

function meiko_create_fighting_items_table() {
    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();
    $table_name = $wpdb->prefix . "mk_fighting";
    
    $sql = "CREATE TABLE $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        name varchar(255) NOT NULL,
        moves_required int NOT NULL,
        money_required int NOT NULL,
        food_required int NOT NULL,
        fighting_gain int NOT NULL,
        attack_gain int NOT NULL,
        defense_gain int NOT NULL,
        karate_gain int DEFAULT 0 NOT NULL,
        kungfu_gain int DEFAULT 0 NOT NULL,
        boxing_gain int DEFAULT 0 NOT NULL,
        kravmaga_gain int DEFAULT 0 NOT NULL,
        bjj_gain int DEFAULT 0 NOT NULL,
        ninjutsu_gain int DEFAULT 0 NOT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";
    
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
    
}

function meiko_create_factions_table() {
    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();
    $table_name = $wpdb->prefix . 'mk_factions';

    $sql = "CREATE TABLE $table_name (
        id INT NOT NULL AUTO_INCREMENT,
        name VARCHAR(255) NOT NULL,
        score INT DEFAULT 0 NOT NULL,
        attack INT DEFAULT 0 NOT NULL,
        defense INT DEFAULT 0 NOT NULL,
        money INT DEFAULT 0 NOT NULL,
        defense_equipment INT DEFAULT 0 NOT NULL,
        attack_equipment INT DEFAULT 0 NOT NULL,
        max_equipment INT DEFAULT 0 NOT NULL,
        faction_leader INT NOT NULL,
        creation_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP NOT NULL,
        avatar varchar(255) DEFAULT NULL,
        PRIMARY KEY (id)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}

function mk_create_fighting_tables() {
    global $wpdb;

    // Table names with WP table prefix
    $styles_table = $wpdb->prefix . "mk_fighting_styles";
    $challenges_table = $wpdb->prefix . "mk_challenges";

    // Charset
    $charset_collate = $wpdb->get_charset_collate();

    // SQL for creating the tables
    $styles_sql = "CREATE TABLE $styles_table (
        id INT PRIMARY KEY AUTO_INCREMENT,
        style_name VARCHAR(50) NOT NULL,
        logic_field VARCHAR(50) NOT NULL
    ) $charset_collate;";

    $challenges_sql = "CREATE TABLE $challenges_table (
        id INT PRIMARY KEY AUTO_INCREMENT,
        challenger_name VARCHAR(50) NOT NULL,
        fighting_style_id INT,
        amount INT NOT NULL,
        FOREIGN KEY (fighting_style_id) REFERENCES $styles_table(id)
    ) $charset_collate;";
    
    // Include the upgrade library
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    
    // Create the tables
    dbDelta($styles_sql);
    dbDelta($challenges_sql);
}

function meiko_create_faction_join_requests_table() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'mk_faction_join_requests';
    
    $charset_collate = $wpdb->get_charset_collate();
    
    $sql = "CREATE TABLE $table_name (
        id INT NOT NULL AUTO_INCREMENT,
        user_id INT NOT NULL,
        faction_name VARCHAR(255) NOT NULL,
        PRIMARY KEY (id)
    ) $charset_collate;";
    
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}

function meiko_create_crypto_table() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'mk_crypto';
    
    $sql = "CREATE TABLE $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        crypto_name varchar(255) NOT NULL,
        value float NOT NULL,
        mining_speed_per_1_computer float NOT NULL,
        moves_required_to_mine int(11) NOT NULL,
        PRIMARY KEY (id)
    ) $charset_collate;";
    
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}

function create_mk_owned_crypto() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'mk_owned_crypto';

    if($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            player_id mediumint(9) NOT NULL,
            crypto_name varchar(255) NOT NULL,
            amount float NOT NULL DEFAULT 0,
            pending_crypto int DEFAULT 0 NOT NULL,
            PRIMARY KEY (id)
        );";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
}

function meiko_create_ranks_table() {
    global $wpdb;
    $table_name = $wpdb->prefix . "mk_ranks";

    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        rank_name varchar(55) NOT NULL,
        see_admin_bar boolean NOT NULL,
        username_color varchar(7) NOT NULL DEFAULT '#000000',
        view_permissions text,
        published_on_store boolean NOT NULL,
        description text,
        tokens_price int NOT NULL,
        PRIMARY KEY (id)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

    dbDelta($sql);
}

function create_mk_email_table() {
    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();
    $table_name = $wpdb->prefix . 'mk_email';

    $sql = "CREATE TABLE $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        sender_id mediumint(9) NOT NULL,
        receiver_id mediumint(9) NOT NULL,
        message text NOT NULL,
        is_read tinyint(1) DEFAULT 0 NOT NULL,
        sent_date datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
        title varchar(255) NOT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}

function meiko_create_plant_tables() {
    global $wpdb;

    // Table names with prefixes
    $plants_table = $wpdb->prefix . 'mk_plants';
    $owned_table = $wpdb->prefix . 'mk_owned';
    $players_table = $wpdb->prefix . 'mk_players';

    // Charset and collation for database
    $charset_collate = $wpdb->get_charset_collate();

    // SQL for creating mk_plants table
    $plants_sql = "CREATE TABLE $plants_table (
        id INT AUTO_INCREMENT,
        name VARCHAR(255) NOT NULL,
        grow_time INT NOT NULL,
        watering_time INT NOT NULL,
        price DECIMAL(10, 2) NOT NULL,
        seed_price DECIMAL(10, 2) NOT NULL,
        PRIMARY KEY (id)
    ) $charset_collate;";

    // SQL for creating mk_owned table
    $owned_sql = "CREATE TABLE $owned_table (
        id INT AUTO_INCREMENT,
        player_id mediumint(9) NOT NULL,
        plant_name VARCHAR(255) NOT NULL,
        quantity INT NOT NULL DEFAULT 0,
        seeds INT NOT NULL DEFAULT 0,
        seeds_growing INT NOT NULL DEFAULT 0,
        PRIMARY KEY (id),
        FOREIGN KEY (player_id) REFERENCES $players_table(id)
    ) $charset_collate;";

    // Include the WordPress dbDelta function
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

    // Execute the SQL queries
    dbDelta($plants_sql);
    dbDelta($owned_sql);
}

function create_mk_attack_log_table() {
    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();
    $table_name = $wpdb->prefix . 'mk_attack_log';

    $sql = "CREATE TABLE $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        attacker_id mediumint(9) NOT NULL,
        defender_id mediumint(9) NOT NULL,
        amount_stolen decimal(10,2) NOT NULL DEFAULT 0,
        attack_successful tinyint(1) DEFAULT 0 NOT NULL,
        attack_date datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}

function create_mk_friend_requests_table() {
    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();
    $table_name = $wpdb->prefix . 'mk_friend_requests';

    $sql = "CREATE TABLE $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        sender_id mediumint(9) NOT NULL,
        receiver_id mediumint(9) NOT NULL,
        sent_date datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}
?>