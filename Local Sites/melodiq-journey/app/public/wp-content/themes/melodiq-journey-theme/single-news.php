<?php
get_header();
?>

<main class="site-main inner-page news-detail-page">
    <div class="container page-shell">
        <div class="page-content-panel news-detail-panel">
            <?php while (have_posts()) : the_post(); ?>
                <article <?php post_class('entry-content news-detail-content'); ?>>
                    <?php if (has_post_thumbnail()) : ?>
                        <div class="news-detail-image"><?php the_post_thumbnail('large'); ?></div>
                    <?php endif; ?>

                    <p class="section-kicker"><?php echo esc_html(get_the_date()); ?></p>
                    <h1><?php the_title(); ?></h1>
                    <?php the_content(); ?>
                </article>

                <?php
                if (comments_open() || get_comments_number()) {
                    comments_template();
                }
                ?>
            <?php endwhile; ?>
        </div>

        <?php get_template_part('template-parts/event-sidebar'); ?>
    </div>
</main>

<?php
get_footer();
