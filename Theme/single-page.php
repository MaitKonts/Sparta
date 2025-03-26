<?php
/* Template Name: Single Page */
get_header(); ?>
<main class="container">
    <?php get_sidebar(); ?>
    
    <div class="content">
        <?php 
        if ( have_posts() ) : 
            while ( have_posts() ) : the_post(); ?>
                <?php the_content(); ?>
        <?php 
            endwhile;
        else : ?>
            <p>No content found.</p>
        <?php endif; ?>
    </div>
</main>

<?php get_footer(); ?>
