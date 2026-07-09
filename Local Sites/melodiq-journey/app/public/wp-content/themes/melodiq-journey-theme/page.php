<?php
get_header();
?>

<main class="site-main inner-page">
    <div class="container page-shell">
        <div class="page-content-panel">
            <?php while (have_posts()) : the_post(); ?>
                <article <?php post_class('entry-content'); ?>>
                    <h1><?php the_title(); ?></h1>
                    <?php the_content(); ?>
                </article>
            <?php endwhile; ?>
        </div>

        <?php get_template_part('template-parts/event-sidebar'); ?>
    </div>
</main>

<?php
get_footer();
