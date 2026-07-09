<?php
/**
 * Single partner template.
 *
 * @package Melodiq_Journey
 */

get_header();
?>

<main class="mj-partners-page mj-partner-single-page">
    <div class="container page-shell">
        <div class="mj-partners-content">
            <?php while (have_posts()) : ?>
                <?php
                the_post();
                $partner_id = get_the_ID();
                $meta = melodiq_partner_meta($partner_id);
                $logo_url = melodiq_partner_logo_url($partner_id, 'medium');
                $discount = melodiq_partner_discount_label($partner_id);
                $category_terms = get_the_terms($partner_id, 'partner_category');
                $category_label = (!is_wp_error($category_terms) && $category_terms) ? $category_terms[0]->name : __('Partner', 'melodiq-journey');
                $related_events = melodiq_partner_related_events($partner_id);
                $related_travels = melodiq_partner_related_travels($partner_id);
                ?>

                <article <?php post_class('mj-partner-single'); ?>>
                    <section class="mj-partner-single-hero" aria-labelledby="partner-title">
                        <?php if (has_post_thumbnail()) : ?>
                            <div class="mj-partner-single-hero__media"><?php the_post_thumbnail('full'); ?></div>
                        <?php endif; ?>
                        <div class="mj-partner-single-hero__content">
                            <p class="section-kicker"><?php echo esc_html($category_label); ?></p>
                            <div class="mj-partner-single-logo-wrap">
                                <?php if ($logo_url) : ?>
                                    <img class="mj-partner-single-logo" src="<?php echo esc_url($logo_url); ?>" alt="<?php the_title_attribute(); ?>">
                                <?php else : ?>
                                    <div class="mj-partner-logo" aria-hidden="true"><?php echo esc_html(melodiq_partner_initials(get_the_title())); ?></div>
                                <?php endif; ?>
                            </div>
                            <h1 id="partner-title"><?php the_title(); ?></h1>
                            <?php if (has_excerpt()) : ?>
                                <p><?php echo esc_html(get_the_excerpt()); ?></p>
                            <?php endif; ?>
                            <div class="hero-actions">
                                <?php if ($meta['website']) : ?>
                                    <a class="button button-primary" href="<?php echo esc_url($meta['website']); ?>" target="_blank" rel="noopener"><?php esc_html_e('Partner weboldal', 'melodiq-journey'); ?></a>
                                <?php endif; ?>
                                <a class="button button-secondary" href="<?php echo esc_url(home_url('/partnerek/')); ?>"><?php esc_html_e('Összes partner', 'melodiq-journey'); ?></a>
                            </div>
                        </div>
                    </section>

                    <?php if ($meta['has_discount']) : ?>
                        <section class="mj-partner-benefit" aria-labelledby="partner-discount-title">
                            <p class="section-kicker"><?php esc_html_e('Journey Club kedvezmény', 'melodiq-journey'); ?></p>
                            <h2 id="partner-discount-title"><?php echo esc_html($discount ? $discount : __('Partner kedvezmény', 'melodiq-journey')); ?></h2>
                            <dl>
                                <?php if ($meta['discount_percent']) : ?>
                                    <div><dt><?php esc_html_e('Kedvezmény', 'melodiq-journey'); ?></dt><dd><?php echo esc_html($meta['discount_percent']); ?>%</dd></div>
                                <?php endif; ?>
                                <?php if ($meta['coupon_code']) : ?>
                                    <div><dt><?php esc_html_e('Kupon', 'melodiq-journey'); ?></dt><dd><?php echo esc_html($meta['coupon_code']); ?></dd></div>
                                <?php endif; ?>
                                <?php if ($meta['discount_validity']) : ?>
                                    <div><dt><?php esc_html_e('Érvényesség', 'melodiq-journey'); ?></dt><dd><?php echo esc_html($meta['discount_validity']); ?></dd></div>
                                <?php endif; ?>
                            </dl>
                        </section>
                    <?php endif; ?>

                    <section class="mj-partner-info-grid">
                        <div class="mj-partner-info-card">
                            <p class="section-kicker"><?php esc_html_e('Leírás', 'melodiq-journey'); ?></p>
                            <div class="entry-content">
                                <?php the_content(); ?>
                            </div>
                        </div>

                        <aside class="mj-partner-info-card">
                            <p class="section-kicker"><?php esc_html_e('Kapcsolat', 'melodiq-journey'); ?></p>
                            <h2><?php esc_html_e('Online jelenlét', 'melodiq-journey'); ?></h2>
                            <div class="mj-partner-socials">
                                <?php foreach (array('website' => 'Weboldal', 'facebook' => 'Facebook', 'instagram' => 'Instagram', 'tiktok' => 'TikTok', 'youtube' => 'YouTube') as $field => $label) : ?>
                                    <?php if ($meta[$field]) : ?>
                                        <a href="<?php echo esc_url($meta[$field]); ?>" target="_blank" rel="noopener"><?php echo esc_html($label); ?></a>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </div>
                        </aside>
                    </section>

                    <?php if ($meta['gallery_ids']) : ?>
                        <section class="mj-partners-section" aria-labelledby="partner-gallery-title">
                            <div class="mj-partners-section__heading">
                                <p class="section-kicker"><?php esc_html_e('Galéria', 'melodiq-journey'); ?></p>
                                <h2 id="partner-gallery-title"><?php esc_html_e('Partner galéria', 'melodiq-journey'); ?></h2>
                            </div>
                            <div class="mj-partner-gallery">
                                <?php foreach ($meta['gallery_ids'] as $image_id) : ?>
                                    <?php echo wp_get_attachment_image($image_id, 'large'); ?>
                                <?php endforeach; ?>
                            </div>
                        </section>
                    <?php endif; ?>

                    <?php if ($related_events) : ?>
                        <section class="mj-partners-section" aria-labelledby="partner-events-title">
                            <div class="mj-partners-section__heading">
                                <p class="section-kicker"><?php esc_html_e('Kapcsolódó', 'melodiq-journey'); ?></p>
                                <h2 id="partner-events-title"><?php esc_html_e('Kapcsolódó események', 'melodiq-journey'); ?></h2>
                            </div>
                            <div class="mj-partner-related-grid">
                                <?php foreach ($related_events as $event_id) : ?>
                                    <?php
                                    $event_meta = function_exists('melodiq_event_meta') ? melodiq_event_meta($event_id) : array();
                                    $event_date = function_exists('melodiq_event_date_parts') ? melodiq_event_date_parts($event_meta['date'], $event_meta['date_end']) : array('label' => '');
                                    ?>
                                    <a class="mj-partner-related-card" href="<?php echo esc_url(get_permalink($event_id)); ?>">
                                        <?php echo get_the_post_thumbnail($event_id, 'medium'); ?>
                                        <span><?php echo esc_html(isset($event_date['label']) ? $event_date['label'] : ''); ?></span>
                                        <strong><?php echo esc_html(get_the_title($event_id)); ?></strong>
                                        <small><?php echo esc_html(isset($event_meta['city']) && $event_meta['city'] ? $event_meta['city'] : ''); ?></small>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        </section>
                    <?php endif; ?>

                    <?php if ($related_travels) : ?>
                        <section class="mj-partners-section" aria-labelledby="partner-travels-title">
                            <div class="mj-partners-section__heading">
                                <p class="section-kicker"><?php esc_html_e('Utazások', 'melodiq-journey'); ?></p>
                                <h2 id="partner-travels-title"><?php esc_html_e('Kapcsolódó utazások', 'melodiq-journey'); ?></h2>
                            </div>
                            <div class="mj-partner-related-grid">
                                <?php foreach ($related_travels as $travel_id) : ?>
                                    <?php $travel_meta = function_exists('melodiq_travel_meta') ? melodiq_travel_meta($travel_id) : array(); ?>
                                    <a class="mj-partner-related-card" href="<?php echo esc_url(get_permalink($travel_id)); ?>">
                                        <?php echo get_the_post_thumbnail($travel_id, 'medium'); ?>
                                        <span><?php echo esc_html(isset($travel_meta['date']) ? melodiq_travel_date_label($travel_meta['date']) : ''); ?></span>
                                        <strong><?php echo esc_html(isset($travel_meta['event_name']) && $travel_meta['event_name'] ? $travel_meta['event_name'] : get_the_title($travel_id)); ?></strong>
                                        <small><?php echo esc_html(isset($travel_meta['city']) && $travel_meta['city'] ? $travel_meta['city'] : ''); ?></small>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        </section>
                    <?php endif; ?>
                </article>
            <?php endwhile; ?>
        </div>

        <aside class="mj-partners-sidebar">
            <?php get_template_part('template-parts/event-sidebar'); ?>
            <section class="mj-partners-sidebar-club">
                <p class="section-kicker"><?php esc_html_e('Journey Club', 'melodiq-journey'); ?></p>
                <h2><?php esc_html_e('Partner ajánlatok', 'melodiq-journey'); ?></h2>
                <p><?php esc_html_e('A kedvezmények a Journey Club rendszerhez később automatikusan kapcsolhatók lesznek.', 'melodiq-journey'); ?></p>
                <a class="button button-secondary" href="<?php echo esc_url(home_url('/journey-club/')); ?>"><?php esc_html_e('Megnézem', 'melodiq-journey'); ?></a>
            </section>
        </aside>
    </div>
</main>

<?php
get_footer();
