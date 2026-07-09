<?php
get_header();
?>

<main class="site-main inner-page">
    <div class="container page-shell">
        <div class="page-content-panel news-archive-panel">
            <div class="section-heading">
                <div>
                    <h1 class="archive-title">Hírek</h1>
                </div>
            </div>

            <?php if (have_posts()) : ?>
                <div class="news-grid">
                    <?php while (have_posts()) : the_post(); ?>
                        <article <?php post_class('news-card'); ?>>
                            <a href="<?php echo esc_url(get_permalink()); ?>">
                                <?php if (has_post_thumbnail()) : ?>
                                    <div class="news-card-media"><?php the_post_thumbnail('large'); ?></div>
                                <?php endif; ?>
                                <div class="news-card-body">
                                    <p class="section-kicker"><?php echo esc_html(get_the_date()); ?></p>
                                    <h2><?php the_title(); ?></h2>
                                    <?php if (has_excerpt()) : ?>
                                        <p><?php echo esc_html(get_the_excerpt()); ?></p>
                                    <?php endif; ?>
                                    <span>Olvasás</span>
                                </div>
                            </a>
                        </article>
                    <?php endwhile; ?>
                </div>

                <?php the_posts_pagination(); ?>
            <?php else : ?>
                <section class="empty-state">
                    <h1>Nincs megjeleníthető hír.</h1>
                    <p>Hamarosan friss hírekkel érkezünk.</p>
                </section>
            <?php endif; ?>
        </div>

        <?php get_template_part('template-parts/event-sidebar'); ?>
    </div>
</main>

<?php
get_footer();
