<?php

get_header(); ?>
<main class="container">
    <?php get_sidebar(); ?>
    
    <section id="primary" class="content-area">
    <div id="content" class="content" role="main">

    <?php    if ( have_posts() ) : 
        while ( have_posts() ) : the_post(); 
            // You can add your custom post layout here
            ?>
            <div class="faction-profile-container">
                <div class="faction-profile-content">
                    <?php the_content(); // Display the content of the post ?>
                </div>
                <?php
                // Display faction profile information using shortcode
                echo do_shortcode('[meiko_faction_profile]');
                ?>
            </div>
            <?php
        endwhile; 
    else : 
        echo '<p>No Faction Profile found.</p>';
    endif;
            ?>

    </div><!-- #content -->
    </section><!-- #primary -->
</main>

<?php get_footer(); ?>
