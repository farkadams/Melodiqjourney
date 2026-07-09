<?php
get_header();
?>

<main class="site-main inner-page event-detail-page">
    <div class="container page-shell">
        <div class="event-detail-content">
            <?php while (have_posts()) : the_post(); ?>
                <?php
                $event_meta = melodiq_event_meta();
                $date_parts = melodiq_event_date_parts($event_meta['date'], $event_meta['date_end']);
                $ticket_url = $event_meta['ticket_url'];
                $current_event_id = get_the_ID();
                $next_event = melodiq_event_query(array(
                    'posts_per_page' => 1,
                    'post__not_in'   => array($current_event_id),
                ));
                $excluded_event_ids = array($current_event_id);
                ?>

                <?php if ($next_event->have_posts()) : ?>
                    <?php
                    $next_event->the_post();
                    $next_event_id = get_the_ID();
                    $excluded_event_ids[] = $next_event_id;
                    $next_meta = melodiq_event_meta();
                    $next_date = melodiq_event_date_parts($next_meta['date'], $next_meta['date_end']);
                    ?>
                    <section class="next-event-panel" aria-labelledby="next-event-title">
                        <div class="next-event-copy">
                            <p class="section-kicker">Következő esemény</p>
                            <div class="event-detail-date">
                                <strong><?php echo esc_html($next_date['day']); ?></strong>
                                <span><?php echo esc_html($next_date['month']); ?></span>
                            </div>
                            <h1 id="next-event-title"><?php the_title(); ?></h1>
                            <div class="next-event-meta">
                                <span><?php echo esc_html($next_meta['city'] ? $next_meta['city'] : 'Budapest'); ?></span>
                                <span><?php echo esc_html($next_meta['organizer'] ? $next_meta['organizer'] : 'Melodiq Journey'); ?></span>
                            </div>
                            <?php echo melodiq_event_lineup_markup($next_meta); ?>
                            <div class="next-event-actions">
                                <?php if ($next_meta['ticket_url']) : ?>
                                    <?php echo melodiq_event_ticket_link($next_event_id, 'event-ticket-link-primary'); ?>
                                <?php else : ?>
                                    <?php echo melodiq_event_ticket_link($next_event_id, 'event-ticket-link-primary'); ?>
                                <?php endif; ?>
                                <a class="button button-secondary" href="<?php the_permalink(); ?>">Részletek</a>
                            </div>
                        </div>

                        <a class="next-event-media" href="<?php the_permalink(); ?>" aria-label="<?php echo esc_attr(get_the_title() . ' megnyitása'); ?>">
                            <?php if (has_post_thumbnail()) : ?>
                                <?php the_post_thumbnail('large'); ?>
                            <?php endif; ?>
                        </a>
                    </section>
                    <?php wp_reset_postdata(); ?>
                <?php endif; ?>

                <?php
                $event_meta = melodiq_event_meta($current_event_id);
                $date_parts = melodiq_event_date_parts($event_meta['date'], $event_meta['date_end']);
                $ticket_url = $event_meta['ticket_url'];
                ?>

                <article <?php post_class('event-detail'); ?>>
                    <section class="event-detail-hero" aria-labelledby="event-title">
                        <?php if (has_post_thumbnail()) : ?>
                            <div class="event-detail-hero-media"><?php the_post_thumbnail('large'); ?></div>
                        <?php endif; ?>

                        <button class="event-like-button event-detail-like" type="button" data-event-id="<?php echo esc_attr($current_event_id); ?>" aria-label="<?php echo esc_attr(get_the_title($current_event_id) . ' kedvelése'); ?>">
                            <span aria-hidden="true">♡</span>
                            <b><?php echo esc_html(melodiq_event_like_count($current_event_id)); ?></b>
                        </button>

                        <div class="event-detail-hero-copy">
                            <p class="section-kicker">Esemény</p>
                            <div class="event-detail-date">
                                <strong><?php echo esc_html($date_parts['day']); ?></strong>
                                <span><?php echo esc_html($date_parts['month']); ?></span>
                            </div>
                            <h1 id="event-title"><?php the_title(); ?></h1>
                            <?php if (has_excerpt()) : ?>
                                <p class="event-detail-lead"><?php echo esc_html(get_the_excerpt()); ?></p>
                            <?php endif; ?>
                        </div>
                    </section>

                    <section class="event-detail-meta" aria-label="Esemény adatok">
                        <div>
                            <span>Dátum</span>
                            <strong><?php echo esc_html($date_parts['label']); ?></strong>
                        </div>
                        <div>
                            <span>Város</span>
                            <strong><?php echo esc_html($event_meta['city'] ? $event_meta['city'] : 'Budapest'); ?></strong>
                        </div>
                        <div>
                            <span>Szervező</span>
                            <strong><?php echo esc_html($event_meta['organizer'] ? $event_meta['organizer'] : 'Melodiq Journey'); ?></strong>
                        </div>
                        <?php if ($event_meta['headliner'] || $event_meta['performers']) : ?>
                            <div>
                                <span>Lineup</span>
                                <?php echo melodiq_event_lineup_markup($event_meta); ?>
                            </div>
                        <?php endif; ?>
                        <div class="event-detail-meta-cta">
                            <?php if ($ticket_url) : ?>
                                <?php echo melodiq_event_ticket_link($current_event_id, 'event-ticket-link-primary'); ?>
                            <?php else : ?>
                                <a class="button button-secondary" href="<?php echo esc_url(melodiq_event_archive_url()); ?>">Összes esemény</a>
                            <?php endif; ?>
                        </div>
                    </section>

                    <section class="event-detail-body">
                        <div class="entry-content">
                            <?php the_content(); ?>
                        </div>

                        <aside class="event-detail-aside">
                            <h2>Információ</h2>
                            <ul>
                                <li><span>Város</span><strong><?php echo esc_html($event_meta['city'] ? $event_meta['city'] : 'Budapest'); ?></strong></li>
                                <?php if ($event_meta['headliner']) : ?>
                                    <li><span>Headliner</span><strong><?php echo esc_html($event_meta['headliner']); ?></strong></li>
                                <?php endif; ?>
                                <?php if ($event_meta['performers']) : ?>
                                    <li><span>Fellépők</span><strong><?php echo esc_html(implode(', ', melodiq_event_performer_list($event_meta['performers']))); ?></strong></li>
                                <?php endif; ?>
                                <li><span>Szervező</span><strong><?php echo esc_html($event_meta['organizer'] ? $event_meta['organizer'] : 'Melodiq Journey'); ?></strong></li>
                                <li><span>Jegyek</span><strong><?php echo $ticket_url ? esc_html__('Elérhetők', 'melodiq-journey') : esc_html__('Hamarosan', 'melodiq-journey'); ?></strong></li>
                            </ul>
                        </aside>
                    </section>
                </article>

                <?php if (function_exists('melodiq_render_related_partners_block')) : ?>
                    <?php melodiq_render_related_partners_block($current_event_id, 'event'); ?>
                <?php endif; ?>

                <?php
                $related_events = melodiq_event_query(array(
                    'posts_per_page' => 3,
                    'post__not_in'   => $excluded_event_ids,
                ));
                ?>

                <?php if ($related_events->have_posts()) : ?>
                    <section class="related-events" aria-labelledby="related-events-title">
                        <div class="section-heading">
                            <h2 id="related-events-title">További események</h2>
                            <a href="<?php echo esc_url(melodiq_event_archive_url()); ?>">Összes esemény</a>
                        </div>

                        <div class="event-grid">
                            <?php
                            $card_classes = array('event-card-artist', 'event-card-dj', 'event-card-crowd');
                            $event_index = 0;

                            while ($related_events->have_posts()) :
                                $related_events->the_post();
                                $related_meta = melodiq_event_meta();
                                $related_date = melodiq_event_date_parts($related_meta['date'], $related_meta['date_end']);
                                $card_class = $card_classes[$event_index % count($card_classes)];
                                ?>
                                <article <?php post_class('event-card ' . $card_class); ?>>
                                    <?php if (has_post_thumbnail()) : ?>
                                        <div class="event-card-media"><?php the_post_thumbnail('large'); ?></div>
                                    <?php endif; ?>
                                    <a class="event-card-link" href="<?php echo esc_url(melodiq_event_action_url()); ?>" aria-label="<?php echo esc_attr(get_the_title() . ' megnyitása'); ?>"></a>
                                    <div class="event-date"><strong><?php echo esc_html($related_date['day']); ?></strong><span><?php echo esc_html($related_date['month']); ?></span></div>
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
                                            <?php echo esc_html($related_meta['city'] ? $related_meta['city'] : 'Budapest'); ?>
                                        </p>
                                        <?php echo melodiq_event_lineup_markup($related_meta, true); ?>
                                        <span class="event-card-organizer"><?php echo esc_html($related_meta['organizer'] ? $related_meta['organizer'] : 'Melodiq Journey'); ?></span>
                                        <div class="event-card-footer">
                                            <?php echo melodiq_event_ticket_link(); ?>
                                        </div>
                                    </div>
                                </article>
                                <?php
                                $event_index++;
                            endwhile;
                            wp_reset_postdata();
                            ?>
                        </div>
                    </section>
                <?php endif; ?>
            <?php endwhile; ?>
        </div>

        <?php get_template_part('template-parts/event-sidebar'); ?>
    </div>
</main>

<?php
get_footer();
