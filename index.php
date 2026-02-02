<?php
/**
 * The main template file
 *
 * @package Flexible_Remote_Services
 */

get_header(); ?>

<div class="container" style="padding-top: 100px; min-height: 60vh;">
    <?php if ( have_posts() ) : ?>
        <?php while ( have_posts() ) : the_post(); ?>
            <article id="post-<?php the_ID(); ?>" <?php post_class( 'fade-in' ); ?>>
                <h2><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
                <div class="entry-content">
                    <?php the_excerpt(); ?>
                </div>
            </article>
        <?php endwhile; ?>

        <?php the_posts_navigation(); ?>

    <?php else : ?>
        <p><?php esc_html_e( 'No posts found.', 'flexible-remote-services' ); ?></p>
    <?php endif; ?>
</div>

<?php get_footer(); ?>
