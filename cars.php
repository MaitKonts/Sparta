<?php
function meiko_car_shortcode() {
    global $wpdb;
    
    // Fetch all cars from the database
    $mk_cars = $wpdb->get_results("SELECT * FROM " . $wpdb->prefix . "mk_cars");

    // Fetch current user's details
    $user_id = get_current_user_id();
    $player = $wpdb->get_row($wpdb->prepare("SELECT * FROM " . $wpdb->prefix . "mk_players WHERE user_id = %d", $user_id));

    // Display the user's current car
    $output = '<div>Your current car: ' . $player->car_name . '</div>';

    // Display the dropdown to select and buy a car
    $output .= '<form class="meiko-cars" method="post" action="">
        <select name="selected_car">';
    foreach ($mk_cars as $car) {
        $output .= '<option value="' . $car->id . '">' . $car->name . ' - ' . $car->price . ' money</option>';
    }
    $output .= '</select>
        <button type="submit" name="buy_car">Buy car</button>
    </form>';

    // Logic to buy a car
    if (isset($_POST['buy_car'])) {
        $selected_car = $wpdb->get_row($wpdb->prepare("SELECT * FROM " . $wpdb->prefix . "mk_cars WHERE id = %d", $_POST['selected_car']));

        if ($player->money >= $selected_car->price) {
            $money_left = $player->money - $selected_car->price;

            $wpdb->update($wpdb->prefix . "mk_players", 
                          array('money' => $money_left, 'car_name' => $selected_car->name),
                          array('user_id' => $user_id));
            
            $output .= '<div>Successfully purchased ' . $selected_car->name . '!</div>';
        } else {
            $output .= '<div>Not enough money to buy this car.</div>';
        }
        
        // Reload the page after purchasing a car
        $output .= "<script> window.location = window.location.href + '?meiko_reload=true'; </script>";
    }
    return $output;
}
?>