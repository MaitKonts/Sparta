<?php
/**
 * The header for your theme
 *
 * This template displays the <head> section and the opening of the <body> tag.
 * It also integrates the custom header functionality using WPBakery or manual HTML Blocks.
 */
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo( 'charset' ); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="profile" href="https://gmpg.org/xfn/11">
    <?php wp_head(); ?>
</head>

<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<header class="header">
<div class="logo-container">
        <a>
            <img src="<?php echo get_template_directory_uri(); ?>/assets/images/logo.png" alt="Logo" class="logo">
        </a>
    </div>
    <nav>
        <?php
        // Display the primary WordPress menu
        wp_nav_menu(array(
            'theme_location' => 'primary', // The location registered in WordPress theme for the primary menu
            'menu_class' => 'navbar',        // Optional class for the <ul> element
            'container' => false           // Remove the default container <div>
        ));
        ?>
    </nav>
    <link href="https://fonts.googleapis.com/css2?family=Lora:wght@400;700&display=swap" rel="stylesheet">

</header>
