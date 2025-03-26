<?php
// Prevent direct access to the file
if (!defined('ABSPATH')) {
    die("No direct script access allowed.");
}

/**
 * Shortcode function for attack packs.
 */
function meiko_attack_pack_shortcode() {
    global $wpdb;
    $mk_attack_packs = $wpdb->get_results("SELECT * FROM " . $wpdb->prefix . "mk_attack_packs ORDER BY level ASC");

    $user_id = get_current_user_id();
    $player = $wpdb->get_row($wpdb->prepare("SELECT * FROM " . $wpdb->prefix . "mk_players WHERE user_id = %d", $user_id));

    // Fetch the player's current attack pack level
    $current_level = intval($player->attack_pack_level);
    $next_pack = null;

    foreach ($mk_attack_packs as $pack) {
        if ($pack->level == ($current_level + 1)) {
            $next_pack = $pack;
            break;
        }
    }

    $output = '<div>Your current attack pack level: ' . $current_level . '</div>';

    if ($next_pack) {
        $output .= '<form class="meiko-attack-pack" method="post" action="">
            <button type="submit" name="upgrade_attack_pack">Upgrade to ' . $next_pack->name . ' (' . $next_pack->price . ' money)</button>
        </form>';
    } else {
        $output .= '<div>You have reached the max attack pack level.</div>';
    }

    if (isset($_POST['upgrade_attack_pack']) && $next_pack) {
        if ($player->money >= $next_pack->price) {
            $money_left = $player->money - $next_pack->price;
            $new_level = $next_pack->level;

            $wpdb->update(
                $wpdb->prefix . "mk_players",
                array('money' => $money_left, 'attack_pack_level' => $new_level),
                array('user_id' => $user_id)
            );

            $output .= '<div>Successfully upgraded to ' . $next_pack->name . '</div>';
            meiko_guards_bonus_to_player($user_id);
        } else {
            $output .= '<div>Not enough money.</div>';
        }

        $output .= "<script> window.location = window.location.href + '?meiko_reload=true'; </script>";
    }

    return $output;
}


function meiko_defense_pack_shortcode() {
    global $wpdb;
    $mk_defense_packs = $wpdb->get_results("SELECT * FROM " . $wpdb->prefix . "mk_defense_packs ORDER BY level ASC");

    $user_id = get_current_user_id();
    $player = $wpdb->get_row($wpdb->prepare("SELECT * FROM " . $wpdb->prefix . "mk_players WHERE user_id = %d", $user_id));

    // Fetch the player's current defense pack level
    $current_level = intval($player->defense_pack_level);
    $next_pack = null;

    foreach ($mk_defense_packs as $pack) {
        if ($pack->level == ($current_level + 1)) {
            $next_pack = $pack;
            break;
        }
    }

    $output = '<div>Your current defense pack level: ' . $current_level . '</div>';

    if ($next_pack) {
        $output .= '<form class="meiko-defense-pack" method="post" action="">
            <button type="submit" name="upgrade_defense_pack">Upgrade to ' . $next_pack->name . ' (' . $next_pack->price . ' money)</button>
        </form>';
    } else {
        $output .= '<div>You have reached the max defense pack level.</div>';
    }

    if (isset($_POST['upgrade_defense_pack']) && $next_pack) {
        if ($player->money >= $next_pack->price) {
            $money_left = $player->money - $next_pack->price;
            $new_level = $next_pack->level;

            $wpdb->update(
                $wpdb->prefix . "mk_players",
                array('money' => $money_left, 'defense_pack_level' => $new_level),
                array('user_id' => $user_id)
            );

            $output .= '<div>Successfully upgraded to ' . $next_pack->name . '</div>';
            meiko_guards_bonus_to_player($user_id);
        } else {
            $output .= '<div>Not enough money.</div>';
        }
    }

    return $output;
}


function meiko_hitmen_shortcode() {
    global $wpdb;

    $user_id = get_current_user_id();
    $player = $wpdb->get_row($wpdb->prepare("SELECT * FROM " . $wpdb->prefix . "mk_players WHERE user_id = %d", $user_id));

    $output = '<div>Hitmen working for you: ' . $player->hitmen . '</div>';

    // Additional form for hiring hitmen
    $output .= '<div>
        <form class="meiko-hire-hitmen" method="post" action="">
            <label>Number of Hitmen to hire:</label>
            <input type="number" name="hitmen_count" value="1" min="1" />
            <button type="submit" name="hire_hitmen">Hire Hitmen</button>
        </form>
    </div>';

    // Logic to hire hitmen
    if (isset($_POST['hire_hitmen'])) {
        $hitmen_count = intval($_POST['hitmen_count']);
        $cost_money = $hitmen_count * 1000;
        $cost_moves = $hitmen_count * 5;

        if ($player->money >= $cost_money && $player->moves >= $cost_moves) {
            $money_left = $player->money - $cost_money;
            $moves_left = $player->moves - $cost_moves;

            // Calculate the total number of hitmen the player will own after hiring
            $total_hitmen_after_hiring = $player->hitmen + $hitmen_count;

            // Calculate hitmen_attack
            $attack_pack_name = $player->attack_pack_level; 
            $attack_pack = $wpdb->get_row($wpdb->prepare("SELECT * FROM " . $wpdb->prefix . "mk_attack_packs WHERE name = %s", $attack_pack_name));
            $attack_pack_level = $attack_pack->level;

            $hitmen_attack = $total_hitmen_after_hiring * $attack_pack_level;

            $wpdb->update(
                $wpdb->prefix . "mk_players", 
                array('money' => $money_left, 'moves' => $moves_left, 'hitmen' => $total_hitmen_after_hiring, 'hitmen_attack' => $hitmen_attack), 
                array('user_id' => $user_id)
            );
            $output .= '<div>Successfully hired ' . $hitmen_count . ' hitmen.</div>';
            meiko_guards_bonus_to_player($user_id);
        } else {
            $output .= '<div>Not enough money or moves.</div>';
        }
        $output .= "<script> window.location = window.location.href + '?meiko_reload=true'; </script>";
    }
    // DEBUG: Hitmen shortcode executed
    error_log('Meiko Plugin: Hitmen shortcode executed');
    return $output;
}

function meiko_guards_shortcode() {
    global $wpdb;

    $user_id = get_current_user_id();
    $player = $wpdb->get_row($wpdb->prepare("SELECT * FROM " . $wpdb->prefix . "mk_players WHERE user_id = %d", $user_id));

    $output = '<div>Guards working for you: ' . $player->guards . '</div>';

    // Existing code for attack packs...

    // Additional form for hiring guards
    $output .= '<div>
        <form class="meiko-hire-guards" method="post" action="">
            <label>Number of Guards to hire:</label>
            <input type="number" name="guards_count" value="1" min="1" />
            <button type="submit" name="hire_guards">Hire guards</button>
        </form>
    </div>';

    // Logic to hire guards
    if (isset($_POST['hire_guards'])) {
        $guards_count = intval($_POST['guards_count']);
        $cost_money = $guards_count * 1000;
        $cost_moves = $guards_count * 5;

        if ($player->money >= $cost_money && $player->moves >= $cost_moves) {
            $money_left = $player->money - $cost_money;
            $moves_left = $player->moves - $cost_moves;
            $new_guards_count = $player->guards + $guards_count;
            $defense_pack_name = $player->defense_pack_level; 
            $defense_pack = $wpdb->get_row($wpdb->prepare("SELECT * FROM " . $wpdb->prefix . "mk_defense_packs WHERE name = %s", $defense_pack_name));
            $defense_pack_level = $defense_pack->level;

            $guards_defense = $new_guards_count * $defense_pack_level;
            $wpdb->update(
                $wpdb->prefix . "mk_players", 
                array('money' => $money_left, 'moves' => $moves_left, 'guards' => $new_guards_count, 'guards_defense' => $guards_defense), 
                array('user_id' => $user_id)
            );
            $output .= '<div>Successfully hired ' . $guards_count . ' guards.</div>';
            meiko_guards_bonus_to_player($user_id);
        } else {
            $output .= '<div>Not enough money or moves.</div>';
        }
        $output .= "<script> window.location = window.location.href + '?meiko_reload=true'; </script>";
    }
    // DEBUG: Guards shortcode executed
    error_log('Meiko Plugin: Guards shortcode executed');
    return $output;
}

function meiko_guards_bonus_to_player() {
    global $wpdb;
    $players_table = $wpdb->prefix . "mk_players";
    $factions_table = $wpdb->prefix . "mk_factions";
    $user_id = get_current_user_id();

    $player_data = $wpdb->get_row($wpdb->prepare("SELECT * FROM $players_table WHERE user_id = %d", $user_id), ARRAY_A);
    $faction_data = $wpdb->get_row($wpdb->prepare("SELECT * FROM $factions_table WHERE name = %s", $player_data['faction']), ARRAY_A);
    
    $new_attack = $player_data['attack'] + $faction_data['attack'] + $player_data['hitmen_attack'];
    $new_defense = $player_data['defense'] + $faction_data['defense'] + $player_data['guards_defense'];
    
    $wpdb->update(
        $players_table,
        array(
            'total_attack' => $new_attack,
            'total_defense' => $new_defense,
        ),
        array('user_id' => $user_id),
        array('%d', '%d'),
        array('%d')
    );
    // DEBUG: Guards bonus applied to player
    error_log('Meiko Plugin: Guards bonus applied to player');
}
?>