<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<?php get_header(); ?>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="<?php bloginfo('stylesheet_url'); ?>">
    <?php wp_head(); ?>
</head>

    <body <?php body_class(); ?>>
        <main class="content-area">        
            <aside class="sidebar">
                <?php get_sidebar(); ?>
            </aside>
            <div class="primary-content">
            <?php
                    if (have_posts()) :
                        while (have_posts()) : the_post();
                            the_content();
                        endwhile;
                    else :
                        echo '<p>No content found</p>';
                    endif;
                    ?>
            </div>
        </main>

        <?php get_footer(); ?>
    </body>
</html>

