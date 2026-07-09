<?php
/**
 * Single travel template.
 *
 * @package Melodiq_Journey
 */

get_header();
?>

<main class="mj-about-page mj-travel-page mj-travel-single-page">
    <div class="container page-shell">
        <div class="mj-travel-content">
            <?php
            while (have_posts()) :
                the_post();
                $meta = melodiq_travel_meta();
                $percent = melodiq_travel_capacity_percent($meta);
                $overview_title = $meta['overview_title'] ? $meta['overview_title'] : __('Áttekintés', 'melodiq-journey');
                $overview_content = $meta['overview_content'] ? $meta['overview_content'] : ($meta['short_description'] ? $meta['short_description'] : get_the_excerpt());
                $program_title = $meta['program_title'] ? $meta['program_title'] : ($meta['event_name'] ? $meta['event_name'] : get_the_title());
                $program_content = $meta['program_content'] ? $meta['program_content'] : get_the_content();
                $departure_title = $meta['departure_title'] ? $meta['departure_title'] : ($meta['departure'] ? $meta['departure'] : __('Indulás', 'melodiq-journey'));
                $departure_content = $meta['departure_content'];
                $info_title = $meta['info_title'] ? $meta['info_title'] : __('Fontos tudnivalók', 'melodiq-journey');
                $info_content = $meta['info_content'] ? $meta['info_content'] : __('Az utazás pontos indulási információi, kapcsolattartási pontjai és frissítései az utazás adatlapján, illetve emailben lesznek kezelve.', 'melodiq-journey');
                ?>
                <article <?php post_class('mj-travel-single'); ?>>
                    <section class="mj-travel-single-hero" aria-labelledby="travel-single-title">
                        <?php if (has_post_thumbnail()) : ?>
                            <?php the_post_thumbnail('full'); ?>
                        <?php else : ?>
                            <div class="mj-travel-single-hero__placeholder"></div>
                        <?php endif; ?>

                        <div class="mj-travel-single-hero__content">
                            <h1 id="travel-single-title"><?php echo esc_html($meta['event_name'] ? $meta['event_name'] : get_the_title()); ?></h1>

                            <div class="mj-travel-single-facts">
                                <div><span>📅</span><strong><?php echo esc_html(melodiq_travel_date_label($meta['date'])); ?></strong><small><?php esc_html_e('Dátum', 'melodiq-journey'); ?></small></div>
                                <div><span>📍</span><strong><?php echo esc_html(melodiq_travel_location_label($meta)); ?></strong><small><?php esc_html_e('Helyszín', 'melodiq-journey'); ?></small></div>
                                <div><span>🚌</span><strong><?php echo esc_html($meta['departure'] ? $meta['departure'] : __('Hamarosan', 'melodiq-journey')); ?></strong><small><?php esc_html_e('Indulás', 'melodiq-journey'); ?></small></div>
                                <div><span>👥</span><strong><?php echo esc_html($meta['applicants'] . ' / ' . $meta['capacity']); ?></strong><small><?php esc_html_e('Létszám', 'melodiq-journey'); ?></small></div>
                                <div><span>🎟</span><strong><?php echo esc_html($meta['available']); ?></strong><small><?php esc_html_e('Szabad hely', 'melodiq-journey'); ?></small></div>
                            </div>
                        </div>

                        <nav class="mj-travel-anchor-nav" aria-label="<?php esc_attr_e('Utazás oldal menü', 'melodiq-journey'); ?>">
                            <a href="#attekintes"><?php esc_html_e('Áttekintés', 'melodiq-journey'); ?></a>
                            <a href="#program"><?php esc_html_e('Program', 'melodiq-journey'); ?></a>
                            <a href="#indulas"><?php esc_html_e('Indulás', 'melodiq-journey'); ?></a>
                            <a href="#tudnivalok"><?php esc_html_e('Tudnivalók', 'melodiq-journey'); ?></a>
                            <a href="#jelentkezes"><?php esc_html_e('Jelentkezés', 'melodiq-journey'); ?></a>
                        </nav>
                    </section>

                    <div class="mj-travel-single-content">
                        <section id="attekintes" class="mj-travel-detail-section">
                            <p class="mj-eyebrow"><?php esc_html_e('Áttekintés', 'melodiq-journey'); ?></p>
                            <h2><?php echo esc_html($overview_title); ?></h2>
                            <div class="mj-travel-copy"><?php echo wpautop(wp_kses_post($overview_content)); ?></div>
                            <div class="mj-travel-progress mj-travel-progress--wide">
                                <span><i style="width: <?php echo esc_attr($percent); ?>%"></i></span>
                                <small><?php echo esc_html($meta['applicants'] . ' / ' . $meta['capacity']); ?> <?php esc_html_e('hely foglalva', 'melodiq-journey'); ?></small>
                            </div>
                        </section>

                        <section id="program" class="mj-travel-detail-section">
                            <p class="mj-eyebrow"><?php esc_html_e('Program', 'melodiq-journey'); ?></p>
                            <h2><?php echo esc_html($program_title); ?></h2>
                            <div class="mj-travel-copy">
                                <?php echo apply_filters('the_content', $program_content); ?>
                            </div>
                        </section>

                        <section id="indulas" class="mj-travel-detail-section mj-travel-detail-grid">
                            <div>
                                <p class="mj-eyebrow"><?php esc_html_e('Indulás', 'melodiq-journey'); ?></p>
                                <h2><?php echo esc_html($departure_title); ?></h2>
                                <?php if ($departure_content) : ?>
                                    <div class="mj-travel-copy"><?php echo wpautop(wp_kses_post($departure_content)); ?></div>
                                <?php endif; ?>
                            </div>
                            <dl>
                                <div><dt><?php esc_html_e('Dátum', 'melodiq-journey'); ?></dt><dd><?php echo esc_html(melodiq_travel_date_label($meta['date'])); ?></dd></div>
                                <div><dt><?php esc_html_e('Helyszín', 'melodiq-journey'); ?></dt><dd><?php echo esc_html(melodiq_travel_location_label($meta)); ?></dd></div>
                                <div><dt><?php esc_html_e('Határidő', 'melodiq-journey'); ?></dt><dd><?php echo esc_html(melodiq_travel_date_label($meta['deadline'])); ?></dd></div>
                            </dl>
                        </section>

                        <section id="tudnivalok" class="mj-travel-detail-section">
                            <p class="mj-eyebrow"><?php esc_html_e('Tudnivalók', 'melodiq-journey'); ?></p>
                            <h2><?php echo esc_html($info_title); ?></h2>
                            <div class="mj-travel-copy"><?php echo wpautop(wp_kses_post($info_content)); ?></div>
                        </section>

                        <?php if (function_exists('melodiq_render_related_partners_block')) : ?>
                            <?php melodiq_render_related_partners_block(get_the_ID(), 'travel'); ?>
                        <?php endif; ?>

                        <section id="jelentkezes" class="mj-travel-apply">
                            <p class="mj-eyebrow"><?php esc_html_e('Jelentkezés', 'melodiq-journey'); ?></p>
                            <?php if (function_exists('melodiq_travel_registration_form')) : ?>
                                <?php echo melodiq_travel_registration_form(get_the_ID()); ?>
                            <?php elseif ($meta['shortcode']) : ?>
                                <?php echo do_shortcode($meta['shortcode']); ?>
                            <?php else : ?>
                                <div class="mj-travel-apply__placeholder"><?php esc_html_e('Jelentkezési űrlap helye', 'melodiq-journey'); ?></div>
                            <?php endif; ?>
                        </section>
                    </div>
                </article>
                <?php
            endwhile;
            ?>
        </div>

        <?php get_template_part('template-parts/event-sidebar'); ?>
    </div>
</main>

<?php
get_footer();
