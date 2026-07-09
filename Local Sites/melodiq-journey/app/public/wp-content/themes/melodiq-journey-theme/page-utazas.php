<?php
/**
 * Template Name: Utazás Page
 *
 * @package Melodiq_Journey
 */

$status_filter = isset($_GET['status']) ? sanitize_key(wp_unslash($_GET['status'])) : 'osszes';
$status_options = array(
    'osszes'        => __('Összes', 'melodiq-journey'),
    'jelentkezheto' => __('Jelentkezhető', 'melodiq-journey'),
    'hamarosan'    => __('Hamarosan', 'melodiq-journey'),
    'betelt'       => __('Betelt', 'melodiq-journey'),
    'lezart'       => __('Lezárt', 'melodiq-journey'),
);

if (!isset($status_options[$status_filter])) {
    $status_filter = 'osszes';
}

$travel_query = melodiq_travel_query();
$travel_stats = melodiq_travel_stats();
$all_travel_query = melodiq_travel_query(array('posts_per_page' => 1));
$has_any_travel_posts = $all_travel_query->have_posts();
wp_reset_postdata();
$show_demo_items = !$has_any_travel_posts && 'osszes' === $status_filter;
$matching_travel_ids = array();

while ($travel_query->have_posts()) {
    $travel_query->the_post();
    $meta = melodiq_travel_meta();

    if ('osszes' === $status_filter || $meta['status'] === $status_filter) {
        $matching_travel_ids[] = get_the_ID();
    }
}

wp_reset_postdata();
$has_travel_posts = !empty($matching_travel_ids);

if ($has_travel_posts) {
    $travel_query = melodiq_travel_query(array(
        'post__in' => $matching_travel_ids,
    ));
}

if ($show_demo_items) {
    $travel_stats = array(
        'active'    => 1,
        'travelers' => 31,
        'countries' => 1,
    );
}

get_header();
?>

<main class="mj-about-page mj-travel-page">
    <div class="container page-shell">
        <div class="mj-travel-content">
            <section class="mj-travel-hero" aria-labelledby="travel-title">
                <div class="mj-travel-hero__copy">
                    <p class="mj-eyebrow"><?php esc_html_e('Melodiq Journey', 'melodiq-journey'); ?></p>
                    <h1 id="travel-title"><?php esc_html_e('Utazások', 'melodiq-journey'); ?></h1>
                    <p><?php esc_html_e('Buszos utazások a legjobb melodic techno eseményekre.', 'melodiq-journey'); ?></p>
                </div>

                <div class="mj-travel-stats" aria-label="<?php esc_attr_e('Utazás statisztikák', 'melodiq-journey'); ?>">
                    <div>
                        <span aria-hidden="true">🚌</span>
                        <strong><?php echo esc_html($travel_stats['active']); ?></strong>
                        <small><?php esc_html_e('aktív utazások', 'melodiq-journey'); ?></small>
                    </div>
                    <div>
                        <span aria-hidden="true">👥</span>
                        <strong><?php echo esc_html($travel_stats['travelers']); ?></strong>
                        <small><?php esc_html_e('utazók száma', 'melodiq-journey'); ?></small>
                    </div>
                    <div>
                        <span aria-hidden="true">🌍</span>
                        <strong><?php echo esc_html($travel_stats['countries']); ?></strong>
                        <small><?php esc_html_e('országok', 'melodiq-journey'); ?></small>
                    </div>
                </div>
            </section>

            <nav class="mj-travel-filters" aria-label="<?php esc_attr_e('Utazás szűrők', 'melodiq-journey'); ?>">
                <?php foreach ($status_options as $status_key => $status_label) : ?>
                    <a class="<?php echo $status_filter === $status_key ? 'is-active' : ''; ?>" href="<?php echo esc_url('osszes' === $status_key ? melodiq_travel_url() : add_query_arg('status', $status_key, melodiq_travel_url())); ?>">
                        <?php echo esc_html($status_label); ?>
                    </a>
                <?php endforeach; ?>
            </nav>

            <section class="mj-travel-list" aria-label="<?php esc_attr_e('Utazás lista', 'melodiq-journey'); ?>">
            <?php
            if ($has_travel_posts) :
                while ($travel_query->have_posts()) :
                    $travel_query->the_post();
                    $meta = melodiq_travel_meta();
                    $percent = melodiq_travel_capacity_percent($meta);
                    ?>
                    <article <?php post_class('mj-travel-card'); ?>>
                        <div class="mj-travel-card__media">
                            <?php if (has_post_thumbnail()) : ?>
                                <?php the_post_thumbnail('large'); ?>
                            <?php else : ?>
                                <div class="mj-travel-card__placeholder"></div>
                            <?php endif; ?>
                            <span class="mj-travel-badge mj-travel-badge--<?php echo esc_attr($meta['status']); ?>"><?php echo esc_html(melodiq_travel_status_label($meta['status'])); ?></span>
                        </div>

                        <div class="mj-travel-card__body">
                            <h2><?php echo nl2br(esc_html(str_replace(' - ', "\n", $meta['event_name'] ? $meta['event_name'] : get_the_title()))); ?></h2>

                            <dl class="mj-travel-meta-grid">
                                <div><dt>📅</dt><dd><?php echo esc_html(melodiq_travel_date_label($meta['date'])); ?></dd></div>
                                <div><dt>📍</dt><dd><?php echo esc_html(melodiq_travel_location_label($meta)); ?></dd></div>
                                <div><dt>🚌</dt><dd><?php esc_html_e('indulás:', 'melodiq-journey'); ?> <?php echo esc_html($meta['departure'] ? $meta['departure'] : __('Hamarosan', 'melodiq-journey')); ?></dd></div>
                                <div><dt>👥</dt><dd><?php echo esc_html($meta['applicants'] . ' / ' . $meta['capacity']); ?> <?php esc_html_e('hely foglalva', 'melodiq-journey'); ?></dd></div>
                                <div><dt>🎟</dt><dd><?php echo esc_html($meta['available']); ?> <?php esc_html_e('szabad hely', 'melodiq-journey'); ?></dd></div>
                                <div><dt>🕒</dt><dd><?php echo esc_html(melodiq_travel_date_label($meta['deadline'])); ?></dd></div>
                            </dl>

                            <p><?php echo esc_html($meta['short_description'] ? $meta['short_description'] : wp_trim_words(get_the_excerpt(), 28)); ?></p>

                            <div class="mj-travel-card__footer">
                                <div class="mj-travel-progress">
                                    <span><i style="width: <?php echo esc_attr($percent); ?>%"></i></span>
                                    <small><?php echo esc_html($meta['applicants'] . ' / ' . $meta['capacity']); ?> <?php esc_html_e('hely foglalva', 'melodiq-journey'); ?></small>
                                </div>
                                <a class="button button-primary" href="<?php echo esc_url(get_permalink()); ?>"><?php esc_html_e('Utazás megnyitása', 'melodiq-journey'); ?></a>
                            </div>
                        </div>
                    </article>
                    <?php
                endwhile;
                wp_reset_postdata();
            elseif ($show_demo_items) :
                foreach (melodiq_travel_demo_items() as $demo_item) :
                    $percent = melodiq_travel_capacity_percent($demo_item);
                    ?>
                    <article class="mj-travel-card">
                        <div class="mj-travel-card__media">
                            <div class="mj-travel-card__placeholder"></div>
                            <span class="mj-travel-badge mj-travel-badge--<?php echo esc_attr($demo_item['status']); ?>"><?php echo esc_html(melodiq_travel_status_label($demo_item['status'])); ?></span>
                        </div>

                        <div class="mj-travel-card__body">
                            <h2><?php echo nl2br(esc_html($demo_item['title'])); ?></h2>
                            <dl class="mj-travel-meta-grid">
                                <div><dt>📅</dt><dd><?php echo esc_html(melodiq_travel_date_label($demo_item['date'])); ?></dd></div>
                                <div><dt>📍</dt><dd><?php echo esc_html(melodiq_travel_location_label($demo_item)); ?></dd></div>
                                <div><dt>🚌</dt><dd><?php esc_html_e('indulás:', 'melodiq-journey'); ?> <?php echo esc_html($demo_item['departure']); ?></dd></div>
                                <div><dt>👥</dt><dd><?php echo esc_html($demo_item['applicants'] . ' / ' . $demo_item['capacity']); ?> <?php esc_html_e('hely foglalva', 'melodiq-journey'); ?></dd></div>
                                <div><dt>🕒</dt><dd><?php echo esc_html(melodiq_travel_date_label($demo_item['deadline'])); ?></dd></div>
                            </dl>
                            <p><?php echo esc_html($demo_item['short_description']); ?></p>
                            <div class="mj-travel-card__footer">
                                <div class="mj-travel-progress">
                                    <span><i style="width: <?php echo esc_attr($percent); ?>%"></i></span>
                                    <small><?php echo esc_html($demo_item['applicants'] . ' / ' . $demo_item['capacity']); ?> <?php esc_html_e('hely foglalva', 'melodiq-journey'); ?></small>
                                </div>
                                <a class="button button-primary" href="<?php echo esc_url($demo_item['permalink']); ?>"><?php esc_html_e('Utazás megnyitása', 'melodiq-journey'); ?></a>
                            </div>
                        </div>
                    </article>
                    <?php
                endforeach;
            else :
                ?>
                <div class="mj-travel-empty">
                    <p class="mj-eyebrow"><?php esc_html_e('Nincs találat', 'melodiq-journey'); ?></p>
                    <h2><?php esc_html_e('Ebben a státuszban most nincs utazás.', 'melodiq-journey'); ?></h2>
                    <a class="button button-secondary" href="<?php echo esc_url(melodiq_travel_url()); ?>"><?php esc_html_e('Összes utazás', 'melodiq-journey'); ?></a>
                </div>
                <?php
            endif;
            ?>
            </section>
        </div>

        <?php get_template_part('template-parts/event-sidebar'); ?>
    </div>
</main>

<?php
get_footer();
