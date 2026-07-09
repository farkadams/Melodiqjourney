<?php
get_header();
?>

<main class="site-main inner-page">
    <div class="container page-shell">
        <div class="page-content-panel artist-archive-panel">
            <div class="section-heading">
                <div>
                    <h1 class="archive-title">Artist</h1>
                </div>
            </div>

            <?php if (have_posts()) : ?>
                <div class="artist-grid">
                    <?php while (have_posts()) : the_post(); ?>
                        <article <?php post_class('artist-card'); ?>>
                            <a href="<?php echo esc_url(get_permalink()); ?>">
                                <?php if (has_post_thumbnail()) : ?>
                                    <div class="artist-card-media"><?php the_post_thumbnail('large'); ?></div>
                                <?php endif; ?>
                                <div class="artist-card-body">
                                    <h2><?php the_title(); ?></h2>
                                </div>
                            </a>
                            <button class="event-like-button artist-like-button" type="button" data-artist-id="<?php echo esc_attr(get_the_ID()); ?>" aria-label="<?php echo esc_attr(get_the_title() . ' kedvelése'); ?>">
                                <span aria-hidden="true">♡</span>
                                <b><?php echo esc_html(melodiq_artist_like_count()); ?></b>
                            </button>
                        </article>
                    <?php endwhile; ?>
                </div>

                <?php the_posts_pagination(); ?>
            <?php else : ?>
                <section class="empty-state">
                    <h1>Nincs feltöltött artist.</h1>
                    <p>Hamarosan érkeznek az artist bio-k.</p>
                </section>
            <?php endif; ?>
        </div>

        <?php get_template_part('template-parts/event-sidebar'); ?>
    </div>
</main>

<?php
get_footer();
