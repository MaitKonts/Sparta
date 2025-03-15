<?php

function meiko_all_players_leaderboard() {
    global $wpdb;
    $table_name_players = $wpdb->prefix . "mk_players";
    $table_name_ranks = $wpdb->prefix . "mk_ranks";

    $mk_players = $wpdb->get_results("SELECT * FROM $table_name_players ORDER BY score DESC");

    $output = '<table>
        <thead>
            <tr>
                <th class="lb_header_position">#</th>
                <th>Username</th>
                <th>Score</th>
                <th>Money</th>
                <th>Rank</th>
                <th>Faction</th>
            </tr>
        </thead>
        <tbody>';

    $position = 1;  // Start position counter

    foreach($mk_players as $player) {
        $profile_url = get_author_posts_url($player->user_id);
        $faction = ($player->faction != 'None') ? esc_html($player->faction) : '';

        // Retrieve rank color from mk_ranks table
        $rank_color = $wpdb->get_var("SELECT username_color FROM $table_name_ranks WHERE rank_name = '$player->rank'");
        
        $faction_slug = sanitize_title($faction);
        $faction_profile_url = ($faction != 'None') ? get_site_url() . '/faction_profile/' . $faction_slug : '';

        $output .= '<tr>
            <td class="lb_position">' . $position . '</td>  <!-- Added position data cell -->
            <td class="lb_username">
                <a style="color: ' . esc_attr($rank_color) . ';" href="' . esc_url($profile_url) . '">' . esc_html($player->username) . '</a>
            </td>
            <td class="lb_score">' . esc_html($player->score) . '</td>
            <td class="lb_money">' . esc_html($player->money) . '</td>
            <td class="lb_rank" style="color: ' . esc_attr($rank_color) . ';">' . esc_html($player->rank) . '</td>
            <td class="lb_faction">
                <a href="' . esc_url($faction_profile_url) . '">' . $faction . '</a>
            </td>
        </tr>';

        $position++;  // Increment position counter
    }

    $output .= '</tbody></table>';
    return $output;
}

function meiko_top_10_players_leaderboard() {
    global $wpdb;
    $table_name = $wpdb->prefix . "mk_players";

    $mk_players = $wpdb->get_results("SELECT * FROM $table_name ORDER BY score DESC LIMIT 10");

    $output = '<table>
        <thead>
            <tr>
                <th>Username</th>
                <th>Score</th>
            </tr>
        </thead>
        <tbody>';

    foreach($mk_players as $player) {
        $output .= '<tr>
            <td>' . esc_html($player->username) . '</td>
            <td>' . esc_html($player->score) . '</td>
        </tr>';
    }

    $output .= '</tbody></table>';
    return $output;
}
?>