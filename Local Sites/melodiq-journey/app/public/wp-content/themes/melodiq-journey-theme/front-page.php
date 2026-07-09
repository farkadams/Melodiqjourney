<?php
get_header();

$fresh_releases = melodiq_get_fresh_releases();
$fresh_releases_source = melodiq_spotify_is_connected() ? 'spotify' : 'static';
?>

<main class="site-main home-page">
    <div class="container page-shell">
        <div class="home-content">
            <section class="hero-section" aria-labelledby="hero-title">
                <div class="hero-copy">
                    <h1 id="hero-title">
                        <span class="hero-title-line">A melodic techno</span>
                        <span class="hero-title-line hero-title-accent">közösség</span>
                        <span class="hero-title-line">Magyarországon.</span>
                    </h1>
                    <p class="hero-lead">Akiket ugyanaz a zene hoz össze.</p>
                    <p class="hero-text">Közös élmények, felejthetetlen események és új kapcsolatok egy különleges közösségben.</p>

                    <div class="hero-actions">
                        <a class="button button-primary" href="#events">Események</a>
                        <a class="button button-secondary" href="#club">Csatlakozás</a>
                    </div>
                </div>
            </section>

            <section class="fresh-releases content-section" aria-labelledby="fresh-releases-title" data-release-source="<?php echo esc_attr($fresh_releases_source); ?>">
                <div class="section-heading fresh-releases-heading">
                    <div>
                        <h2 id="fresh-releases-title">Friss megjelenések</h2>
                    </div>
                    <div class="fresh-releases-controls" aria-label="Fresh Releases navigáció">
                        <button class="fresh-releases-nav fresh-releases-prev" type="button" aria-label="Előző megjelenés">
                            <span aria-hidden="true">&lt;</span>
                        </button>
                        <button class="fresh-releases-nav fresh-releases-next" type="button" aria-label="Következő megjelenés">
                            <span aria-hidden="true">&gt;</span>
                        </button>
                    </div>
                </div>

                <div class="swiper fresh-releases-swiper">
                    <div class="swiper-wrapper">
                        <?php foreach ($fresh_releases as $release) : ?>
                            <article class="swiper-slide fresh-release-card" data-artist="<?php echo esc_attr($release['artist']); ?>" data-track="<?php echo esc_attr($release['title']); ?>">
                                <div class="fresh-release-art" style="--release-cover: <?php echo esc_attr($release['cover']); ?>;">
                                    <?php if (!empty($release['image'])) : ?>
                                        <img src="<?php echo esc_url($release['image']); ?>" alt="<?php echo esc_attr($release['artist'] . ' - ' . $release['title'] . ' borító'); ?>" loading="lazy">
                                    <?php endif; ?>
                                    <span><?php echo esc_html(strtoupper(substr($release['artist'], 0, 1) . substr($release['title'], 0, 1))); ?></span>
                                </div>
                                <div class="fresh-release-body">
                                    <p class="fresh-release-artist"><?php echo esc_html($release['artist']); ?></p>
                                    <h3><?php echo esc_html($release['title']); ?></h3>
                                    <div class="fresh-release-meta">
                                        <?php if (!empty($release['date'])) : ?>
                                            <time datetime="<?php echo esc_attr($release['date_iso']); ?>"><?php echo esc_html($release['date']); ?></time>
                                        <?php else : ?>
                                            <span aria-hidden="true"></span>
                                        <?php endif; ?>
                                        <a class="fresh-release-spotify" href="<?php echo esc_url($release['spotify_url']); ?>" target="_blank" rel="noopener noreferrer" aria-label="<?php echo esc_attr($release['artist'] . ' - ' . $release['title'] . ' lejátszása Spotify-on'); ?>" title="Lejátszás Spotify-on" data-tooltip="Lejátszás Spotify-on">
                                            <svg viewBox="0 0 24 24" aria-hidden="true">
                                                <path d="M12 2a10 10 0 1 0 0 20 10 10 0 0 0 0-20Z"></path>
                                                <path d="M7.6 9.2c2.9-.9 6.3-.6 8.8.8"></path>
                                                <path d="M8.2 12.1c2.3-.7 5-.5 7 .6"></path>
                                                <path d="M8.8 14.8c1.8-.5 3.9-.4 5.4.5"></path>
                                            </svg>
                                        </a>
                                    </div>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                    <div class="fresh-releases-pagination"></div>
                </div>
            </section>

            <section id="events" class="content-section" aria-labelledby="events-title">
                <div class="section-heading">
                    <h2 id="events-title">Kiemelt események</h2>
                    <a href="<?php echo esc_url(melodiq_event_archive_url()); ?>">Összes esemény</a>
                </div>

                <div class="event-grid">
                    <?php
                    $home_events = melodiq_event_query(array('posts_per_page' => 3));
                    $card_classes = array('event-card-artist', 'event-card-dj', 'event-card-crowd');
                    $event_index = 0;

                    if ($home_events->have_posts()) :
                        while ($home_events->have_posts()) :
                            $home_events->the_post();
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
                        wp_reset_postdata();
                    else :
                        ?>
                        <article class="event-card event-card-artist">
                            <a class="event-card-link" href="#" aria-label="Stephan Jolk megnyitása"></a>
                            <div class="event-date"><strong>14</strong><span>Aug</span></div>
                            <button class="event-like-button" type="button" disabled><span aria-hidden="true">♡</span><b>0</b></button>
                            <div class="event-card-body">
                                <h3>Stephan Jolk</h3>
                                <p class="event-card-location">
                                    <svg viewBox="0 0 24 24" aria-hidden="true">
                                        <path d="M12 21s7-5.4 7-12a7 7 0 0 0-14 0c0 6.6 7 12 7 12Z"></path>
                                        <circle cx="12" cy="9" r="2.4"></circle>
                                    </svg>
                                    Budapest
                                </p>
                                <div class="event-lineup event-lineup-compact">
                                    <div class="event-lineup-row event-lineup-headliner"><span>Headliner</span><strong>Stephan Jolk</strong></div>
                                </div>
                                <span class="event-card-organizer">Melodiq Journey</span>
                                <div class="event-card-footer">
                                    <a class="event-ticket-link" href="#">
                                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 8.5V6a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v2.5a2.5 2.5 0 0 0 0 5V16a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2v-2.5a2.5 2.5 0 0 0 0-5Z"></path><path d="M9 7v10"></path><path d="M13 8h3"></path><path d="M13 12h3"></path></svg>
                                        <span>Jegyvásárlás</span>
                                    </a>
                                </div>
                            </div>
                        </article>

                        <article class="event-card event-card-dj">
                            <a class="event-card-link" href="#" aria-label="Innellea megnyitása"></a>
                            <div class="event-date"><strong>19</strong><span>Sep</span></div>
                            <button class="event-like-button" type="button" disabled><span aria-hidden="true">♡</span><b>0</b></button>
                            <div class="event-card-body">
                                <h3>Innellea</h3>
                                <p class="event-card-location">
                                    <svg viewBox="0 0 24 24" aria-hidden="true">
                                        <path d="M12 21s7-5.4 7-12a7 7 0 0 0-14 0c0 6.6 7 12 7 12Z"></path>
                                        <circle cx="12" cy="9" r="2.4"></circle>
                                    </svg>
                                    Budapest
                                </p>
                                <div class="event-lineup event-lineup-compact">
                                    <div class="event-lineup-row event-lineup-headliner"><span>Headliner</span><strong>Innellea</strong></div>
                                </div>
                                <span class="event-card-organizer">Melodiq Journey</span>
                                <div class="event-card-footer">
                                    <a class="event-ticket-link" href="#">
                                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 8.5V6a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v2.5a2.5 2.5 0 0 0 0 5V16a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2v-2.5a2.5 2.5 0 0 0 0-5Z"></path><path d="M9 7v10"></path><path d="M13 8h3"></path><path d="M13 12h3"></path></svg>
                                        <span>Jegyvásárlás</span>
                                    </a>
                                </div>
                            </div>
                        </article>

                        <article class="event-card event-card-crowd">
                            <a class="event-card-link" href="#" aria-label="Agents of Time megnyitása"></a>
                            <div class="event-date"><strong>24</strong><span>Oct</span></div>
                            <button class="event-like-button" type="button" disabled><span aria-hidden="true">♡</span><b>0</b></button>
                            <div class="event-card-body">
                                <h3>Agents of Time</h3>
                                <p class="event-card-location">
                                    <svg viewBox="0 0 24 24" aria-hidden="true">
                                        <path d="M12 21s7-5.4 7-12a7 7 0 0 0-14 0c0 6.6 7 12 7 12Z"></path>
                                        <circle cx="12" cy="9" r="2.4"></circle>
                                    </svg>
                                    Budapest
                                </p>
                                <div class="event-lineup event-lineup-compact">
                                    <div class="event-lineup-row event-lineup-headliner"><span>Headliner</span><strong>Agents of Time</strong></div>
                                </div>
                                <span class="event-card-organizer">Melodiq Journey</span>
                                <div class="event-card-footer">
                                    <a class="event-ticket-link" href="#">
                                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 8.5V6a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v2.5a2.5 2.5 0 0 0 0 5V16a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2v-2.5a2.5 2.5 0 0 0 0-5Z"></path><path d="M9 7v10"></path><path d="M13 8h3"></path><path d="M13 12h3"></path></svg>
                                        <span>Jegyvásárlás</span>
                                    </a>
                                </div>
                            </div>
                        </article>
                    <?php endif; ?>
                </div>
            </section>

            <section class="about-panel" aria-labelledby="about-title">
                <div>
                    <p class="section-kicker">Rólunk</p>
                    <h2 id="about-title">Több mint zene. Egy közösség.</h2>
                    <p>A Melodiq Journey egy Magyarországon épülő melodic techno közösség. Olyan embereket hozunk össze, akiket ugyanaz a hangzás, ugyanaz az energia és ugyanaz az élménykeresés mozgat.</p>
                    <p>Eseményeket, közösségi találkozókat és Journey Club élményeket szervezünk, ahol a zene nem csak program, hanem kapcsolódási pont.</p>
                    <a class="button button-secondary" href="#">Több rólunk</a>
                </div>
            </section>

            <section id="club" class="club-panel" aria-labelledby="club-title">
                <div>
                    <p class="section-kicker">Journey Club</p>
                    <h2 id="club-title">Légy a közösség meghatározó tagja.</h2>
                    <p>Csatlakozz a Journey Clubhoz, gyűjts pontokat, szerezz exkluzív kedvezményeket és éld át a közösség élményeit.</p>
                    <a class="button button-primary" href="#">Tudj meg többet</a>
                </div>

                <div class="member-card" aria-label="Journey Club Member">
                    <span>Melodiq Journey</span>
                    <strong>Journey Club Member</strong>
                    <small>MJ 2405 1808</small>
                </div>
            </section>
        </div>

        <?php get_template_part('template-parts/event-sidebar'); ?>
    </div>

    <section id="newsletter" class="newsletter-band">
        <div class="container newsletter-inner">
            <div>
                <h2>Ne maradj le a következő közös élményről!</h2>
                <p>Iratkozz fel hírlevelünkre, és értesülj elsőként az új eseményekről.</p>
            </div>
            <form class="newsletter-form" action="<?php echo esc_url(home_url('/')); ?>" method="post">
                <?php wp_nonce_field('melodiq_newsletter_signup', 'melodiq_newsletter_nonce'); ?>
                <input type="hidden" name="melodiq_newsletter_action" value="signup">
                <input type="hidden" name="melodiq_newsletter_source" value="front-page">
                <label class="screen-reader-text" for="newsletter-email">E-mail címed</label>
                <input id="newsletter-email" type="email" name="melodiq_newsletter_email" placeholder="E-mail címed" autocomplete="email" required>
                <button class="button button-primary" type="submit">Feliratkozom</button>
                <?php echo melodiq_newsletter_message(); ?>
            </form>
        </div>
    </section>
</main>

<?php
get_footer();
