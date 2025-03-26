<?php

function meiko_all_players_leaderboard() {
    global $wpdb;
    $table_name_players = $wpdb->prefix . "mk_players";
    $table_name_ranks = $wpdb->prefix . "mk_ranks";

    // Determine sorting method (default: Score)
    $sort_by = isset($_GET['sort_by']) ? sanitize_text_field($_GET['sort_by']) : 'score';
    
    $valid_sorts = ['score', 'money', 'rank', 'faction'];
    if (!in_array($sort_by, $valid_sorts)) {
        $sort_by = 'score';
    }

    // Fetch sorted leaderboard data
    $mk_players = $wpdb->get_results("SELECT * FROM $table_name_players ORDER BY $sort_by DESC");

    // Dropdown Sorting UI
    $output = '<div class="leaderboard-sorting">
        <label for="sortLeaderboard">Sort by: </label>
        <select id="sortLeaderboard" onchange="sortLeaderboard()">
            <option value="score" ' . selected($sort_by, 'score', false) . '>Score</option>
            <option value="money" ' . selected($sort_by, 'money', false) . '>Money</option>
            <option value="rank" ' . selected($sort_by, 'rank', false) . '>Rank</option>
            <option value="faction" ' . selected($sort_by, 'faction', false) . '>Faction</option>
        </select>
    </div>';

    // JavaScript for Dropdown Sorting
    $output .= "<script>
        function sortLeaderboard() {
            let selectedValue = document.getElementById('sortLeaderboard').value;
            window.location.href = '?sort_by=' + selectedValue;
        }
    </script>";

    // Leaderboard Table
    $output .= '<table class="leaderboard-table">
        <thead>
            <tr>
                <th>#</th>
                <th>Avatar</th>
                <th>Username</th>
                <th>Score</th>
                <th>Money</th>
                <th>Rank</th>
                <th>Faction</th>
            </tr>
        </thead>
        <tbody>';

    $position = 1;
    foreach ($mk_players as $player) {
        $profile_url = get_author_posts_url($player->user_id);
        $rank_color = $wpdb->get_var("SELECT username_color FROM $table_name_ranks WHERE rank_name = '$player->rank'");

        // Fetch avatar from the `avatar` column
        $avatar_url = $player->avatar ? esc_url($player->avatar) : get_template_directory_uri() . "/default-avatar.png";

        $output .= "<tr>
            <td>{$position}</td>
            <td><img src='{$avatar_url}' alt='Avatar' class='leaderboard-avatar'></td>
            <td><a style='color: {$rank_color};' href='{$profile_url}'>{$player->username}</a></td>
            <td>{$player->score}</td>
            <td>{$player->money}</td>
            <td style='color: {$rank_color};'>{$player->rank}</td>
            <td>{$player->faction}</td>
        </tr>";

        $position++;
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