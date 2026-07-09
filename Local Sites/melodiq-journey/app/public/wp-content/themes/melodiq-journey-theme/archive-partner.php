<?php
/**
 * Partner archive template.
 *
 * @package Melodiq_Journey
 */

get_header();

$selected_category = isset($_GET['partner_category']) ? sanitize_title(wp_unslash($_GET['partner_category'])) : '';
$terms = get_terms(array(
    'taxonomy'   => 'partner_category',
    'hide_empty' => false,
    'orderby'    => 'name',
    'order'      => 'ASC',
));

$base_meta_query = array(
    array(
        'key'   => '_melodiq_partner_active',
        'value' => '1',
    ),
);

$tax_query = array();
if ($selected_category) {
    $tax_query[] = array(
        'taxonomy' => 'partner_category',
        'field'    => 'slug',
        'terms'    => $selected_category,
    );
}

$featured_query = new WP_Query(array(
    'post_type'      => 'partner',
    'post_status'    => 'publish',
    'posts_per_page' => 4,
    'orderby'        => 'menu_order title',
    'order'          => 'ASC',
    'meta_query'     => array(
        'relation' => 'AND',
        $base_meta_query[0],
        array(
            'key'   => '_melodiq_partner_featured',
            'value' => '1',
        ),
    ),
));

$partner_query_args = array(
    'post_type'      => 'partner',
    'post_status'    => 'publish',
    'posts_per_page' => 12,
    'orderby'        => 'title',
    'order'          => 'ASC',
    'meta_query'     => $base_meta_query,
);

if ($tax_query) {
    $partner_query_args['tax_query'] = $tax_query;
}

$partner_query = new WP_Query($partner_query_args);
?>

<main class="mj-partners-page">
    <div class="container page-shell">
        <div class="mj-partners-content">
            <section class="mj-partners-hero" aria-labelledby="partners-title">
                <div class="mj-partners-hero__inner">
                    <p class="section-kicker">Melodiq Journey</p>
                    <h1 id="partners-title"><?php esc_html_e('Partnerek', 'melodiq-journey'); ?></h1>
                    <p><?php esc_html_e('Akik támogatják a magyar melodic techno közösséget.', 'melodiq-journey'); ?></p>
                    <div class="hero-actions">
                        <a class="button button-primary" href="#partner-jelentkezes"><?php esc_html_e('Partner leszek', 'melodiq-journey'); ?></a>
                        <a class="button button-secondary" href="#journey-club"><?php esc_html_e('Journey Club kedvezmények', 'melodiq-journey'); ?></a>
                    </div>
                </div>
            </section>

            <?php if ($featured_query->have_posts()) : ?>
                <section class="mj-partners-section" aria-labelledby="featured-partners-title">
                    <div class="mj-partners-section__heading">
                        <p class="section-kicker"><?php esc_html_e('Kiemelt', 'melodiq-journey'); ?></p>
                        <h2 id="featured-partners-title"><?php esc_html_e('Kiemelt partnerek', 'melodiq-journey'); ?></h2>
                    </div>

                    <div class="mj-featured-partners-grid">
                        <?php while ($featured_query->have_posts()) : ?>
                            <?php
                            $featured_query->the_post();
                            $meta = melodiq_partner_meta();
                            $logo_url = melodiq_partner_logo_url(get_the_ID(), 'medium');
                            $discount = melodiq_partner_discount_label(get_the_ID());
                            ?>
                            <article <?php post_class('mj-featured-partner-card'); ?>>
                                <?php if ($discount) : ?>
                                    <span class="mj-partner-discount"><?php echo esc_html($discount); ?></span>
                                <?php endif; ?>
                                <?php if ($logo_url) : ?>
                                    <img class="mj-partner-logo-img mj-partner-logo-img--large" src="<?php echo esc_url($logo_url); ?>" alt="<?php the_title_attribute(); ?>">
                                <?php else : ?>
                                    <div class="mj-partner-logo" aria-hidden="true"><?php echo esc_html(melodiq_partner_initials(get_the_title())); ?></div>
                                <?php endif; ?>
                                <h3><?php the_title(); ?></h3>
                                <p><?php echo esc_html(get_the_excerpt() ? get_the_excerpt() : wp_trim_words(wp_strip_all_tags(get_the_content()), 18)); ?></p>
                                <div class="mj-partner-card-actions">
                                    <?php if ($meta['website']) : ?>
                                        <a href="<?php echo esc_url($meta['website']); ?>" target="_blank" rel="noopener"><?php esc_html_e('Weboldal', 'melodiq-journey'); ?></a>
                                    <?php endif; ?>
                                    <a href="<?php the_permalink(); ?>"><?php esc_html_e('Partner oldal', 'melodiq-journey'); ?></a>
                                </div>
                            </article>
                        <?php endwhile; ?>
                        <?php wp_reset_postdata(); ?>
                    </div>
                </section>
            <?php endif; ?>

            <section class="mj-partners-section" aria-labelledby="partner-categories-title">
                <div class="mj-partners-section__heading">
                    <p class="section-kicker"><?php esc_html_e('Kategóriák', 'melodiq-journey'); ?></p>
                    <h2 id="partner-categories-title"><?php esc_html_e('Partner kategóriák', 'melodiq-journey'); ?></h2>
                </div>

                <nav class="mj-partner-categories" aria-label="<?php esc_attr_e('Partner kategória szűrő', 'melodiq-journey'); ?>">
                    <a class="<?php echo $selected_category ? '' : 'is-active'; ?>" href="<?php echo esc_url(home_url('/partnerek/')); ?>"><span>ALL</span><?php esc_html_e('Összes', 'melodiq-journey'); ?></a>
                    <?php if (!is_wp_error($terms)) : ?>
                        <?php foreach ($terms as $term) : ?>
                            <a class="<?php echo $selected_category === $term->slug ? 'is-active' : ''; ?>" href="<?php echo esc_url(add_query_arg('partner_category', $term->slug, home_url('/partnerek/'))); ?>">
                                <span><?php echo esc_html(melodiq_partner_initials($term->name)); ?></span><?php echo esc_html($term->name); ?>
                            </a>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </nav>
            </section>

            <section class="mj-partners-section" aria-labelledby="all-partners-title">
                <div class="mj-partners-section__heading">
                    <p class="section-kicker"><?php esc_html_e('Lista', 'melodiq-journey'); ?></p>
                    <h2 id="all-partners-title"><?php esc_html_e('Összes partner', 'melodiq-journey'); ?></h2>
                </div>

                <?php if ($partner_query->have_posts()) : ?>
                    <div class="mj-partners-grid">
                        <?php while ($partner_query->have_posts()) : ?>
                            <?php
                            $partner_query->the_post();
                            melodiq_render_partner_teaser_card(get_the_ID());
                            ?>
                        <?php endwhile; ?>
                        <?php wp_reset_postdata(); ?>
                    </div>
                    <div class="mj-partners-load-more">
                        <a class="button button-secondary" href="<?php echo esc_url(get_post_type_archive_link('partner')); ?>"><?php esc_html_e('Több partner betöltése', 'melodiq-journey'); ?></a>
                    </div>
                <?php else : ?>
                    <section class="empty-state">
                        <h2><?php esc_html_e('Nincs megjeleníthető partner.', 'melodiq-journey'); ?></h2>
                        <p><?php esc_html_e('A partnerek az admin felületről aktiválhatók.', 'melodiq-journey'); ?></p>
                    </section>
                <?php endif; ?>
            </section>

            <section class="mj-partners-cta-grid" aria-label="<?php esc_attr_e('Partner CTA blokkok', 'melodiq-journey'); ?>">
                <article id="journey-club" class="mj-partners-cta-card">
                    <p class="section-kicker"><?php esc_html_e('Kedvezmények', 'melodiq-journey'); ?></p>
                    <h2><?php esc_html_e('Journey Club', 'melodiq-journey'); ?></h2>
                    <p><?php esc_html_e('Exkluzív kedvezmények partnereinknél és még sok más előny.', 'melodiq-journey'); ?></p>
                    <a class="button button-primary" href="<?php echo esc_url(home_url('/journey-club/')); ?>"><?php esc_html_e('Csatlakozom', 'melodiq-journey'); ?></a>
                </article>

                <article id="partner-jelentkezes" class="mj-partners-cta-card">
                    <p class="section-kicker"><?php esc_html_e('Együttműködés', 'melodiq-journey'); ?></p>
                    <h2><?php esc_html_e('Szeretnél együttműködni?', 'melodiq-journey'); ?></h2>
                    <p><?php esc_html_e('Légy a Melodiq Journey partnere, és érd el velünk a melodic techno közösséget.', 'melodiq-journey'); ?></p>
                    <ul>
                        <li><?php esc_html_e('több ezer aktív tag', 'melodiq-journey'); ?></li>
                        <li><?php esc_html_e('hírlevél megjelenés', 'melodiq-journey'); ?></li>
                        <li><?php esc_html_e('Facebook & Instagram posztok', 'melodiq-journey'); ?></li>
                        <li><?php esc_html_e('weboldali kiemelés', 'melodiq-journey'); ?></li>
                        <li><?php esc_html_e('közös promóciók', 'melodiq-journey'); ?></li>
                    </ul>
                    <a class="button button-primary" href="<?php echo esc_url(home_url('/kapcsolat/')); ?>"><?php esc_html_e('Jelentkezem partnernek', 'melodiq-journey'); ?></a>
                </article>
            </section>
        </div>

        <aside class="mj-partners-sidebar">
            <?php get_template_part('template-parts/event-sidebar'); ?>
            <section class="mj-partners-sidebar-club">
                <p class="section-kicker"><?php esc_html_e('Journey Club', 'melodiq-journey'); ?></p>
                <h2><?php esc_html_e('Partner kedvezmények', 'melodiq-journey'); ?></h2>
                <p><?php esc_html_e('A klubtagok automatikusan láthatják az elérhető partner ajánlatokat és kuponokat.', 'melodiq-journey'); ?></p>
                <a class="button button-secondary" href="<?php echo esc_url(home_url('/journey-club/')); ?>"><?php esc_html_e('Megnézem', 'melodiq-journey'); ?></a>
            </section>
        </aside>
    </div>
</main>

<?php
get_footer();
