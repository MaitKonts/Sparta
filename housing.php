<?php
function meiko_housing_shortcode() {
    global $wpdb;
    $mk_houses = $wpdb->get_results("SELECT * FROM " . $wpdb->prefix . "mk_houses");

    $user_id = get_current_user_id();
    $player = $wpdb->get_row($wpdb->prepare("SELECT * FROM " . $wpdb->prefix . "mk_players WHERE user_id = %d", $user_id));

    $output = '<div>Your current house: ' . $player->house_name . '</div>';

    $output .= '<form class="meiko-housing" method="post" action="">
        <select name="selected_house">';
    foreach ($mk_houses as $house) {
        $output .= '<option value="' . $house->id . '">' . $house->name . ' - ' . $house->price . ' money</option>';
    }
    $output .= '</select>
        <button type="submit" name="buy_house">Buy House</button>
    </form>';

    // Logic to buy a house
    if (isset($_POST['buy_house'])) {
        $selected_house = $wpdb->get_row($wpdb->prepare("SELECT * FROM " . $wpdb->prefix . "mk_houses WHERE id = %d", $_POST['selected_house']));

        if ($player->money >= $selected_house->price) {
            $money_left = $player->money - $selected_house->price;

            $wpdb->update($wpdb->prefix . "mk_players", 
                          array('money' => $money_left, 'house_name' => $selected_house->name),
                          array('user_id' => $user_id));
        } else {
            $output .= '<div>Not enough money to buy this house.</div>';
        }
        $output .= "<script> window.location = window.location.href + '?meiko_reload=true'; </script>";
    }
    return $output;
}
?>