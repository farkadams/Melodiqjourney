<?php
get_header();
?>

<main class="site-main inner-page">
    <div class="container page-shell">
        <div class="page-content-panel">
            <?php if (have_posts()) : ?>
                <div class="archive-list">
                    <?php while (have_posts()) : the_post(); ?>
                        <article <?php post_class('archive-card'); ?>>
                            <a href="<?php the_permalink(); ?>">
                                <p class="section-kicker"><?php echo esc_html(get_the_date()); ?></p>
                                <h1><?php the_title(); ?></h1>
                                <?php the_excerpt(); ?>
                            </a>
                        </article>
                    <?php endwhile; ?>
                </div>

                <?php the_posts_pagination(); ?>
            <?php else : ?>
                <section class="empty-state">
                    <h1>Nincs megjeleníthető tartalom.</h1>
                    <p>Hamarosan új eseményekkel és hírekkel érkezünk.</p>
                </section>
            <?php endif; ?>
        </div>

        <?php get_template_part('template-parts/event-sidebar'); ?>
    </div>
</main>

<?php
get_footer();
