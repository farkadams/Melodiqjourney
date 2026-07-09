<?php
get_header();
?>

<main class="site-main inner-page">
    <div class="container page-shell">
        <div class="page-content-panel">
            <div class="section-heading">
                <div>
                    <h1 class="archive-title">Események</h1>
                </div>
            </div>

            <?php if (have_posts()) : ?>
                <div class="event-grid archive-event-grid">
                    <?php
                    $card_classes = array('event-card-artist', 'event-card-dj', 'event-card-crowd');
                    $event_index = 0;

                    while (have_posts()) :
                        the_post();
                        $event_meta = melodiq_event_meta();
                        $date_parts = melodiq_event_date_parts($event_meta['date'], $event_meta['date_end']);
                        $card_class = $card_classes[$event_index % count($card_classes)];
                        ?>
                        <article <?php post_class('event-card ' . $card_class); ?>>
                            <?php if (has_post_thumbnail()) : ?>
                                <div class="event-card-media"><?php the_post_thumbnail('large'); ?></div>
                            <?php endif; ?>
                            <a class="event-card-link" href="<?php echo esc_url(melodiq_event_action_url()); ?>" aria-label="<?php echo esc_attr(get_the_title() . ' megnyitása'); ?>"></a>
                            <div class="event-date"><strong><?php echo esc_html($date_parts['day']); ?></strong><span><?php echo esc_html($date_parts['month']); ?></span></div>
                            <button class="event-like-button" type="button" data-event-id="<?php echo esc_attr(get_the_ID()); ?>" aria-label="<?php echo esc_attr(get_the_title() . ' kedvelése'); ?>">
                                <span aria-hidden="true">♡</span>
                                <b><?php echo esc_html(melodiq_event_like_count()); ?></b>
                            </button>
                            <div class="event-card-body">
                                <h3><?php the_title(); ?></h3>
                                <p class="event-card-location">
                                    <svg viewBox="0 0 24 24" aria-hidden="true">
                                        <path d="M12 21s7-5.4 7-12a7 7 0 0 0-14 0c0 6.6 7 12 7 12Z"></path>
                                        <circle cx="12" cy="9" r="2.4"></circle>
                                    </svg>
                                    <?php echo esc_html($event_meta['city'] ? $event_meta['city'] : 'Budapest'); ?>
                                </p>
                                <?php echo melodiq_event_lineup_markup($event_meta, true); ?>
                                <span class="event-card-organizer"><?php echo esc_html($event_meta['organizer'] ? $event_meta['organizer'] : 'Melodiq Journey'); ?></span>
                                <div class="event-card-footer">
                                    <?php echo melodiq_event_ticket_link(); ?>
                                </div>
                            </div>
                        </article>
                        <?php
                        $event_index++;
                    endwhile;
                    ?>
                </div>

                <?php the_posts_pagination(); ?>
            <?php else : ?>
                <section class="empty-state">
                    <h1>Nincs meghirdetett esemény.</h1>
                    <p>Hamarosan új dátumokkal érkezünk.</p>
                </section>
            <?php endif; ?>
        </div>

        <?php get_template_part('template-parts/event-sidebar'); ?>
    </div>
</main>

<?php
get_footer();
