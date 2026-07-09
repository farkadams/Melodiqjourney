<?php
get_header();
?>

<main class="site-main inner-page artist-detail-page">
    <div class="container page-shell">
        <article <?php post_class('page-content-panel artist-detail-panel'); ?>>
            <?php while (have_posts()) : the_post(); ?>
                <section class="artist-detail-hero" aria-labelledby="artist-title">
                    <?php if (has_post_thumbnail()) : ?>
                        <div class="artist-detail-media"><?php the_post_thumbnail('large'); ?></div>
                    <?php endif; ?>
                    <div class="artist-detail-copy">
                        <h1 id="artist-title"><?php the_title(); ?></h1>
                        <?php if (has_excerpt()) : ?>
                            <p><?php echo esc_html(get_the_excerpt()); ?></p>
                        <?php endif; ?>
                    </div>
                </section>

                <section class="artist-detail-body">
                    <div class="entry-content">
                        <?php the_content(); ?>
                    </div>
                </section>
            <?php endwhile; ?>
        </article>

        <?php get_template_part('template-parts/event-sidebar'); ?>
    </div>
</main>

<?php
get_footer();
