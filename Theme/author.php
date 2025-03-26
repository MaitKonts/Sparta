<?php

get_header(); ?>
<main class="container">
    <?php get_sidebar(); ?>
    
    <section id="primary" class="content-area">
    <div id="content" class="content" role="main">

    <div class="author-profile">
    <?php
    $author_id = get_queried_object_id();
    echo do_shortcode('[meiko_user_profile user_id="' . $author_id . '"]');
    ?>
</div>

    </div><!-- #content -->
    </section><!-- #primary -->
</main>

<?php get_footer(); ?>
