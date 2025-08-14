<?php get_header(); ?>

<main>
    <?php
    if (have_posts()) :
        while (have_posts()) : the_post(); ?>
            <article>
                <h2><?php the_title(); ?></h2>
                <p><small>Published on <?php the_date(); ?> by <?php the_author(); ?></small></p>
                <div><?php the_content(); ?></div>
            </article>
        <?php endwhile;
    else :
        echo '<p>No post found</p>';
    endif;
    ?>
</main>

<?php get_footer(); ?>
