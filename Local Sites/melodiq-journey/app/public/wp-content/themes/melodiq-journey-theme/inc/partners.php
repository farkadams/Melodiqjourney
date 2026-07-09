<?php

/**
 * Partner post type, taxonomy and helpers.
 */

function melodiq_register_partner_post_type() {
    $labels = array(
        'name'               => __('Partnerek', 'melodiq-journey'),
        'singular_name'      => __('Partner', 'melodiq-journey'),
        'add_new_item'       => __('Új partner hozzáadása', 'melodiq-journey'),
        'edit_item'          => __('Partner szerkesztése', 'melodiq-journey'),
        'new_item'           => __('Új partner', 'melodiq-journey'),
        'view_item'          => __('Partner megtekintése', 'melodiq-journey'),
        'search_items'       => __('Partnerek keresése', 'melodiq-journey'),
        'not_found'          => __('Nincs partner', 'melodiq-journey'),
        'not_found_in_trash' => __('Nincs partner a lomtárban', 'melodiq-journey'),
        'menu_name'          => __('Partnerek', 'melodiq-journey'),
    );

    register_post_type('partner', array(
        'labels'        => $labels,
        'public'        => true,
        'has_archive'   => 'partnerek',
        'menu_icon'     => 'dashicons-groups',
        'rewrite'       => array('slug' => 'partner'),
        'show_in_rest'  => true,
        'supports'      => array('title', 'editor', 'excerpt', 'thumbnail'),
    ));
}
add_action('init', 'melodiq_register_partner_post_type');

function melodiq_register_partner_taxonomy() {
    register_taxonomy('partner_category', 'partner', array(
        'labels' => array(
            'name'          => __('Partner kategóriák', 'melodiq-journey'),
            'singular_name' => __('Partner kategória', 'melodiq-journey'),
            'menu_name'     => __('Kategóriák', 'melodiq-journey'),
        ),
        'hierarchical'      => true,
        'show_admin_column' => true,
        'show_in_rest'     => true,
        'rewrite'          => array('slug' => 'partner-kategoria'),
    ));
}
add_action('init', 'melodiq_register_partner_taxonomy');

function melodiq_maybe_flush_partner_rewrite_rules() {
    if ('1' === get_option('melodiq_partner_rewrite_flushed')) {
        return;
    }

    melodiq_register_partner_post_type();
    melodiq_register_partner_taxonomy();
    flush_rewrite_rules();
    update_option('melodiq_partner_rewrite_flushed', '1');
}
add_action('init', 'melodiq_maybe_flush_partner_rewrite_rules', 22);

function melodiq_seed_partner_categories() {
    if ('1' === get_option('melodiq_partner_categories_seeded')) {
        return;
    }

    $categories = array(
        'Rendezvényszervező',
        'Utazási partner',
        'Bár & Étterem',
        'Ruházat',
        'DJ / Producer',
        'Zenei szolgáltató',
        'Szállás',
        'Média',
        'Egyéb',
    );

    foreach ($categories as $category) {
        if (!term_exists($category, 'partner_category')) {
            wp_insert_term($category, 'partner_category');
        }
    }

    update_option('melodiq_partner_categories_seeded', '1');
}
add_action('init', 'melodiq_seed_partner_categories', 45);

function melodiq_enqueue_partner_admin_assets($hook) {
    if (!in_array($hook, array('post.php', 'post-new.php'), true)) {
        return;
    }

    $screen = get_current_screen();

    if (!$screen || 'partner' !== $screen->post_type) {
        return;
    }

    wp_enqueue_media();

    $admin_style = get_template_directory() . '/assets/css/admin-event.css';
    wp_enqueue_style(
        'melodiq-admin-event',
        get_template_directory_uri() . '/assets/css/admin-event.css',
        array(),
        file_exists($admin_style) ? filemtime($admin_style) : wp_get_theme()->get('Version')
    );

    $script_path = get_template_directory() . '/assets/js/admin-partner.js';
    wp_enqueue_script(
        'melodiq-admin-partner',
        get_template_directory_uri() . '/assets/js/admin-partner.js',
        array('jquery'),
        file_exists($script_path) ? filemtime($script_path) : wp_get_theme()->get('Version'),
        true
    );
}
add_action('admin_enqueue_scripts', 'melodiq_enqueue_partner_admin_assets');

function melodiq_partner_category_options() {
    return get_terms(array(
        'taxonomy'   => 'partner_category',
        'hide_empty' => false,
        'orderby'    => 'name',
        'order'      => 'ASC',
    ));
}

function melodiq_partner_options() {
    return get_posts(array(
        'post_type'      => 'partner',
        'post_status'    => 'publish',
        'posts_per_page' => -1,
        'orderby'        => 'title',
        'order'          => 'ASC',
    ));
}

function melodiq_partner_meta($post_id = null) {
    $post_id = $post_id ? $post_id : get_the_ID();
    $term_ids = wp_get_post_terms($post_id, 'partner_category', array('fields' => 'ids'));

    return array(
        'logo_id'            => absint(get_post_meta($post_id, '_melodiq_partner_logo_id', true)),
        'website'            => esc_url_raw(get_post_meta($post_id, '_melodiq_partner_website', true)),
        'facebook'           => esc_url_raw(get_post_meta($post_id, '_melodiq_partner_facebook', true)),
        'instagram'          => esc_url_raw(get_post_meta($post_id, '_melodiq_partner_instagram', true)),
        'tiktok'             => esc_url_raw(get_post_meta($post_id, '_melodiq_partner_tiktok', true)),
        'youtube'            => esc_url_raw(get_post_meta($post_id, '_melodiq_partner_youtube', true)),
        'category_id'        => $term_ids ? (int) $term_ids[0] : 0,
        'active'             => '1' === get_post_meta($post_id, '_melodiq_partner_active', true),
        'featured'           => '1' === get_post_meta($post_id, '_melodiq_partner_featured', true),
        'has_discount'       => '1' === get_post_meta($post_id, '_melodiq_partner_has_discount', true),
        'discount_type'      => get_post_meta($post_id, '_melodiq_partner_discount_type', true),
        'discount_percent'   => get_post_meta($post_id, '_melodiq_partner_discount_percent', true),
        'coupon_code'        => get_post_meta($post_id, '_melodiq_partner_coupon_code', true),
        'discount_validity'  => get_post_meta($post_id, '_melodiq_partner_discount_validity', true),
        'related_events'     => array_values(array_filter(array_map('absint', (array) get_post_meta($post_id, '_melodiq_partner_related_events', true)))),
        'related_travels'    => array_values(array_filter(array_map('absint', (array) get_post_meta($post_id, '_melodiq_partner_related_travels', true)))),
        'gallery_ids'        => array_values(array_filter(array_map('absint', (array) get_post_meta($post_id, '_melodiq_partner_gallery_ids', true)))),
    );
}

function melodiq_partner_logo_url($partner_id, $size = 'medium') {
    $meta = melodiq_partner_meta($partner_id);

    if ($meta['logo_id']) {
        $logo = wp_get_attachment_image_url($meta['logo_id'], $size);

        if ($logo) {
            return $logo;
        }
    }

    return get_the_post_thumbnail_url($partner_id, $size);
}

function melodiq_partner_discount_label($partner_id) {
    $meta = melodiq_partner_meta($partner_id);

    if (!$meta['has_discount']) {
        return '';
    }

    if ($meta['discount_percent']) {
        return sprintf(__('%s%% kedvezmény', 'melodiq-journey'), $meta['discount_percent']);
    }

    return $meta['discount_type'] ? $meta['discount_type'] : __('Journey Club kedvezmény', 'melodiq-journey');
}

function melodiq_partner_initials($title) {
    $words = preg_split('/\s+/', trim(wp_strip_all_tags($title)));
    $initials = '';

    foreach ((array) $words as $word) {
        if ('' === $word) {
            continue;
        }

        $initials .= function_exists('mb_substr') ? mb_substr($word, 0, 1) : substr($word, 0, 1);

        $initial_count = function_exists('mb_strlen') ? mb_strlen($initials) : strlen($initials);

        if ($initial_count >= 2) {
            break;
        }
    }

    return function_exists('mb_strtoupper') ? mb_strtoupper($initials ? $initials : 'MJ') : strtoupper($initials ? $initials : 'MJ');
}

function melodiq_partner_select_field($name, $selected_ids = array()) {
    $selected_ids = array_map('intval', (array) $selected_ids);
    $partners = melodiq_partner_options();
    ?>
    <select name="<?php echo esc_attr($name); ?>[]" multiple size="6">
        <?php foreach ($partners as $partner) : ?>
            <option value="<?php echo esc_attr($partner->ID); ?>" <?php selected(in_array((int) $partner->ID, $selected_ids, true)); ?>>
                <?php echo esc_html(get_the_title($partner)); ?>
            </option>
        <?php endforeach; ?>
    </select>
    <small><?php esc_html_e('Több elem kijelölhető. Macen Cmd+kattintás, Windowson Ctrl+kattintás.', 'melodiq-journey'); ?></small>
    <?php
}

function melodiq_post_ids_with_partner($post_type, $partner_id, $meta_key) {
    return get_posts(array(
        'post_type'      => $post_type,
        'post_status'    => 'publish',
        'posts_per_page' => -1,
        'fields'         => 'ids',
        'meta_query'     => array(
            array(
                'key'     => $meta_key,
                'value'   => '"' . absint($partner_id) . '"',
                'compare' => 'LIKE',
            ),
        ),
    ));
}

function melodiq_partner_related_events($partner_id) {
    $meta = melodiq_partner_meta($partner_id);
    $reverse_ids = melodiq_post_ids_with_partner('event', $partner_id, '_melodiq_event_partners');

    return array_values(array_unique(array_merge($meta['related_events'], $reverse_ids)));
}

function melodiq_partner_related_travels($partner_id) {
    $meta = melodiq_partner_meta($partner_id);
    $reverse_ids = melodiq_post_ids_with_partner('travel', $partner_id, '_melodiq_travel_partners');

    return array_values(array_unique(array_merge($meta['related_travels'], $reverse_ids)));
}

function melodiq_partners_for_object($object_id, $object_type) {
    $meta_key = 'event' === $object_type ? '_melodiq_event_partners' : '_melodiq_travel_partners';
    $reverse_meta_key = 'event' === $object_type ? '_melodiq_partner_related_events' : '_melodiq_partner_related_travels';
    $selected_ids = array_values(array_filter(array_map('absint', (array) get_post_meta($object_id, $meta_key, true))));
    $reverse_partners = get_posts(array(
        'post_type'      => 'partner',
        'post_status'    => 'publish',
        'posts_per_page' => -1,
        'fields'         => 'ids',
        'meta_query'     => array(
            array(
                'key'     => $reverse_meta_key,
                'value'   => '"' . absint($object_id) . '"',
                'compare' => 'LIKE',
            ),
        ),
    ));

    return array_values(array_unique(array_merge($selected_ids, $reverse_partners)));
}

function melodiq_render_partner_teaser_card($partner_id) {
    $meta = melodiq_partner_meta($partner_id);
    $logo_url = melodiq_partner_logo_url($partner_id, 'medium');
    $discount = melodiq_partner_discount_label($partner_id);
    ?>
    <article class="mj-partner-card">
        <div class="mj-partner-card__top">
            <?php if ($logo_url) : ?>
                <img class="mj-partner-logo-img" src="<?php echo esc_url($logo_url); ?>" alt="<?php echo esc_attr(get_the_title($partner_id)); ?>">
            <?php else : ?>
                <div class="mj-partner-logo mj-partner-logo--small" aria-hidden="true"><?php echo esc_html(melodiq_partner_initials(get_the_title($partner_id))); ?></div>
            <?php endif; ?>
            <?php if ($meta['has_discount']) : ?>
                <span class="mj-partner-mini-badge">💎</span>
            <?php endif; ?>
        </div>
        <h3><?php echo esc_html(get_the_title($partner_id)); ?></h3>
        <?php if ($discount) : ?>
            <p><?php echo esc_html($discount); ?></p>
            <small><?php esc_html_e('Journey Club tagoknak', 'melodiq-journey'); ?></small>
        <?php else : ?>
            <p><?php echo esc_html(get_the_excerpt($partner_id)); ?></p>
        <?php endif; ?>
        <a href="<?php echo esc_url(get_permalink($partner_id)); ?>"><?php esc_html_e('Partner oldal', 'melodiq-journey'); ?></a>
    </article>
    <?php
}

function melodiq_render_related_partners_block($object_id, $object_type = 'event') {
    $partner_ids = melodiq_partners_for_object($object_id, $object_type);

    if (!$partner_ids) {
        return;
    }
    ?>
    <section class="mj-related-partners" aria-labelledby="related-partners-title">
        <div class="section-heading">
            <h2 id="related-partners-title"><?php esc_html_e('Partnerek', 'melodiq-journey'); ?></h2>
        </div>
        <div class="mj-partners-grid mj-partners-grid--related">
            <?php foreach ($partner_ids as $partner_id) : ?>
                <?php melodiq_render_partner_teaser_card($partner_id); ?>
            <?php endforeach; ?>
        </div>
    </section>
    <?php
}

function melodiq_render_partner_details_meta_box($post) {
    wp_nonce_field('melodiq_save_partner_details', 'melodiq_partner_details_nonce');
    $meta = melodiq_partner_meta($post->ID);
    $logo_url = $meta['logo_id'] ? wp_get_attachment_image_url($meta['logo_id'], 'thumbnail') : '';
    $gallery_urls = array();

    foreach ($meta['gallery_ids'] as $gallery_id) {
        $image_url = wp_get_attachment_image_url($gallery_id, 'thumbnail');
        if ($image_url) {
            $gallery_urls[] = $image_url;
        }
    }
    ?>
    <div class="melodiq-admin-event-panel">
        <section class="melodiq-admin-event-section">
            <div class="melodiq-admin-event-section-heading">
                <h3><?php esc_html_e('Alapadatok', 'melodiq-journey'); ?></h3>
                <p><?php esc_html_e('A partner neve a WordPress cím mezője, a rövid leírás a kivonat, a hosszú leírás a tartalom.', 'melodiq-journey'); ?></p>
            </div>
            <label class="melodiq-admin-event-field">
                <span><?php esc_html_e('Partner logó', 'melodiq-journey'); ?></span>
                <input type="hidden" name="melodiq_partner_logo_id" value="<?php echo esc_attr($meta['logo_id']); ?>" data-partner-logo-id>
                <button class="button" type="button" data-partner-logo-button><?php esc_html_e('Logó kiválasztása', 'melodiq-journey'); ?></button>
                <button class="button" type="button" data-partner-logo-clear><?php esc_html_e('Törlés', 'melodiq-journey'); ?></button>
                <div class="melodiq-admin-partner-preview" data-partner-logo-preview>
                    <?php if ($logo_url) : ?>
                        <img src="<?php echo esc_url($logo_url); ?>" alt="">
                    <?php endif; ?>
                </div>
            </label>
        </section>

        <section class="melodiq-admin-event-section">
            <div class="melodiq-admin-event-section-heading">
                <h3><?php esc_html_e('Kapcsolatok', 'melodiq-journey'); ?></h3>
            </div>
            <div class="melodiq-admin-event-grid melodiq-admin-event-grid-2">
                <?php foreach (array('website' => 'Weboldal', 'facebook' => 'Facebook', 'instagram' => 'Instagram', 'tiktok' => 'TikTok', 'youtube' => 'YouTube') as $field => $label) : ?>
                    <label class="melodiq-admin-event-field" for="melodiq_partner_<?php echo esc_attr($field); ?>">
                        <span><?php echo esc_html($label); ?></span>
                        <input id="melodiq_partner_<?php echo esc_attr($field); ?>" type="url" name="melodiq_partner_<?php echo esc_attr($field); ?>" value="<?php echo esc_url($meta[$field]); ?>" placeholder="https://">
                    </label>
                <?php endforeach; ?>
            </div>
        </section>

        <section class="melodiq-admin-event-section">
            <div class="melodiq-admin-event-section-heading">
                <h3><?php esc_html_e('Partner adatok', 'melodiq-journey'); ?></h3>
            </div>
            <div class="melodiq-admin-event-grid melodiq-admin-event-grid-2">
                <label class="melodiq-admin-event-field" for="melodiq_partner_category">
                    <span><?php esc_html_e('Kategória', 'melodiq-journey'); ?></span>
                    <select id="melodiq_partner_category" name="melodiq_partner_category">
                        <option value="0"><?php esc_html_e('Válassz kategóriát', 'melodiq-journey'); ?></option>
                        <?php foreach (melodiq_partner_category_options() as $term) : ?>
                            <option value="<?php echo esc_attr($term->term_id); ?>" <?php selected($meta['category_id'], $term->term_id); ?>><?php echo esc_html($term->name); ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label class="melodiq-admin-event-toggle">
                    <input type="checkbox" name="melodiq_partner_active" value="1" <?php checked($meta['active']); ?>>
                    <span><strong><?php esc_html_e('Aktív partner', 'melodiq-journey'); ?></strong><small><?php esc_html_e('Csak aktív partnerek jelennek meg kiemelten.', 'melodiq-journey'); ?></small></span>
                </label>
                <label class="melodiq-admin-event-toggle">
                    <input type="checkbox" name="melodiq_partner_featured" value="1" <?php checked($meta['featured']); ?>>
                    <span><strong><?php esc_html_e('Kiemelt partner', 'melodiq-journey'); ?></strong><small><?php esc_html_e('Megjelenik az archív oldal elején.', 'melodiq-journey'); ?></small></span>
                </label>
            </div>
        </section>

        <section class="melodiq-admin-event-section">
            <div class="melodiq-admin-event-section-heading">
                <h3><?php esc_html_e('Journey Club', 'melodiq-journey'); ?></h3>
            </div>
            <label class="melodiq-admin-event-toggle">
                <input type="checkbox" name="melodiq_partner_has_discount" value="1" <?php checked($meta['has_discount']); ?>>
                <span><strong><?php esc_html_e('Van kedvezmény', 'melodiq-journey'); ?></strong><small><?php esc_html_e('Automatikusan megjelenik Journey Club kedvezményként.', 'melodiq-journey'); ?></small></span>
            </label>
            <div class="melodiq-admin-event-grid melodiq-admin-event-grid-2">
                <label class="melodiq-admin-event-field">
                    <span><?php esc_html_e('Kedvezmény típusa', 'melodiq-journey'); ?></span>
                    <input type="text" name="melodiq_partner_discount_type" value="<?php echo esc_attr($meta['discount_type']); ?>" placeholder="Online kupon / helyszíni kedvezmény">
                </label>
                <label class="melodiq-admin-event-field">
                    <span><?php esc_html_e('Kedvezmény %', 'melodiq-journey'); ?></span>
                    <input type="number" min="0" max="100" name="melodiq_partner_discount_percent" value="<?php echo esc_attr($meta['discount_percent']); ?>" placeholder="20">
                </label>
                <label class="melodiq-admin-event-field">
                    <span><?php esc_html_e('Kuponkód', 'melodiq-journey'); ?></span>
                    <input type="text" name="melodiq_partner_coupon_code" value="<?php echo esc_attr($meta['coupon_code']); ?>" placeholder="MELODIQ20">
                </label>
                <label class="melodiq-admin-event-field">
                    <span><?php esc_html_e('Érvényesség', 'melodiq-journey'); ?></span>
                    <input type="text" name="melodiq_partner_discount_validity" value="<?php echo esc_attr($meta['discount_validity']); ?>" placeholder="2026. december 31.">
                </label>
            </div>
        </section>

        <section class="melodiq-admin-event-section">
            <div class="melodiq-admin-event-section-heading">
                <h3><?php esc_html_e('Kapcsolódó tartalmak', 'melodiq-journey'); ?></h3>
            </div>
            <div class="melodiq-admin-event-grid melodiq-admin-event-grid-2">
                <label class="melodiq-admin-event-field">
                    <span><?php esc_html_e('Kapcsolódó események', 'melodiq-journey'); ?></span>
                    <?php melodiq_post_select_field('melodiq_partner_related_events', 'event', $meta['related_events']); ?>
                </label>
                <label class="melodiq-admin-event-field">
                    <span><?php esc_html_e('Kapcsolódó utazások', 'melodiq-journey'); ?></span>
                    <?php melodiq_post_select_field('melodiq_partner_related_travels', 'travel', $meta['related_travels']); ?>
                </label>
            </div>
        </section>

        <section class="melodiq-admin-event-section">
            <div class="melodiq-admin-event-section-heading">
                <h3><?php esc_html_e('Galéria', 'melodiq-journey'); ?></h3>
            </div>
            <input type="hidden" name="melodiq_partner_gallery_ids" value="<?php echo esc_attr(implode(',', $meta['gallery_ids'])); ?>" data-partner-gallery-ids>
            <button class="button" type="button" data-partner-gallery-button><?php esc_html_e('Galéria kiválasztása', 'melodiq-journey'); ?></button>
            <button class="button" type="button" data-partner-gallery-clear><?php esc_html_e('Törlés', 'melodiq-journey'); ?></button>
            <div class="melodiq-admin-partner-gallery" data-partner-gallery-preview>
                <?php foreach ($gallery_urls as $gallery_url) : ?>
                    <img src="<?php echo esc_url($gallery_url); ?>" alt="">
                <?php endforeach; ?>
            </div>
        </section>
    </div>
    <?php
}

function melodiq_post_select_field($name, $post_type, $selected_ids = array()) {
    $selected_ids = array_map('intval', (array) $selected_ids);
    $posts = get_posts(array(
        'post_type'      => $post_type,
        'post_status'    => 'publish',
        'posts_per_page' => -1,
        'orderby'        => 'title',
        'order'          => 'ASC',
    ));
    ?>
    <select name="<?php echo esc_attr($name); ?>[]" multiple size="6">
        <?php foreach ($posts as $post) : ?>
            <option value="<?php echo esc_attr($post->ID); ?>" <?php selected(in_array((int) $post->ID, $selected_ids, true)); ?>><?php echo esc_html(get_the_title($post)); ?></option>
        <?php endforeach; ?>
    </select>
    <small><?php esc_html_e('Több elem kijelölhető. Macen Cmd+kattintás, Windowson Ctrl+kattintás.', 'melodiq-journey'); ?></small>
    <?php
}

function melodiq_add_partner_meta_boxes() {
    add_meta_box(
        'melodiq_partner_details',
        __('Partner adatok', 'melodiq-journey'),
        'melodiq_render_partner_details_meta_box',
        'partner',
        'normal',
        'high'
    );
}
add_action('add_meta_boxes', 'melodiq_add_partner_meta_boxes');

function melodiq_save_partner_details($post_id) {
    if (!isset($_POST['melodiq_partner_details_nonce'])) {
        return;
    }

    if (!wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['melodiq_partner_details_nonce'])), 'melodiq_save_partner_details')) {
        return;
    }

    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }

    if (!current_user_can('edit_post', $post_id)) {
        return;
    }

    foreach (array('website', 'facebook', 'instagram', 'tiktok', 'youtube') as $url_field) {
        update_post_meta($post_id, '_melodiq_partner_' . $url_field, isset($_POST['melodiq_partner_' . $url_field]) ? esc_url_raw(wp_unslash($_POST['melodiq_partner_' . $url_field])) : '');
    }

    update_post_meta($post_id, '_melodiq_partner_logo_id', isset($_POST['melodiq_partner_logo_id']) ? absint($_POST['melodiq_partner_logo_id']) : 0);
    update_post_meta($post_id, '_melodiq_partner_active', !empty($_POST['melodiq_partner_active']) ? '1' : '0');
    update_post_meta($post_id, '_melodiq_partner_featured', !empty($_POST['melodiq_partner_featured']) ? '1' : '0');
    update_post_meta($post_id, '_melodiq_partner_has_discount', !empty($_POST['melodiq_partner_has_discount']) ? '1' : '0');
    update_post_meta($post_id, '_melodiq_partner_discount_type', isset($_POST['melodiq_partner_discount_type']) ? sanitize_text_field(wp_unslash($_POST['melodiq_partner_discount_type'])) : '');
    update_post_meta($post_id, '_melodiq_partner_discount_percent', isset($_POST['melodiq_partner_discount_percent']) ? max(0, min(100, absint($_POST['melodiq_partner_discount_percent']))) : '');
    update_post_meta($post_id, '_melodiq_partner_coupon_code', isset($_POST['melodiq_partner_coupon_code']) ? sanitize_text_field(wp_unslash($_POST['melodiq_partner_coupon_code'])) : '');
    update_post_meta($post_id, '_melodiq_partner_discount_validity', isset($_POST['melodiq_partner_discount_validity']) ? sanitize_text_field(wp_unslash($_POST['melodiq_partner_discount_validity'])) : '');

    $category_id = isset($_POST['melodiq_partner_category']) ? absint($_POST['melodiq_partner_category']) : 0;
    wp_set_object_terms($post_id, $category_id ? array($category_id) : array(), 'partner_category');

    $related_events = isset($_POST['melodiq_partner_related_events']) ? array_values(array_filter(array_map('absint', (array) wp_unslash($_POST['melodiq_partner_related_events'])))) : array();
    $related_travels = isset($_POST['melodiq_partner_related_travels']) ? array_values(array_filter(array_map('absint', (array) wp_unslash($_POST['melodiq_partner_related_travels'])))) : array();
    $gallery_ids = isset($_POST['melodiq_partner_gallery_ids']) ? array_values(array_filter(array_map('absint', explode(',', sanitize_text_field(wp_unslash($_POST['melodiq_partner_gallery_ids'])))))) : array();

    update_post_meta($post_id, '_melodiq_partner_related_events', $related_events);
    update_post_meta($post_id, '_melodiq_partner_related_travels', $related_travels);
    update_post_meta($post_id, '_melodiq_partner_gallery_ids', $gallery_ids);
}
add_action('save_post_partner', 'melodiq_save_partner_details');

function melodiq_partner_admin_columns($columns) {
    $new_columns = array();

    foreach ($columns as $key => $label) {
        $new_columns[$key] = $label;

        if ('title' === $key) {
            $new_columns['partner_active'] = __('Aktív', 'melodiq-journey');
            $new_columns['partner_featured'] = __('Kiemelt', 'melodiq-journey');
            $new_columns['partner_discount'] = __('Kedvezmény', 'melodiq-journey');
        }
    }

    return $new_columns;
}
add_filter('manage_partner_posts_columns', 'melodiq_partner_admin_columns');

function melodiq_partner_admin_column_content($column, $post_id) {
    $meta = melodiq_partner_meta($post_id);

    if ('partner_active' === $column) {
        echo $meta['active'] ? esc_html__('Igen', 'melodiq-journey') : esc_html__('Nem', 'melodiq-journey');
    }

    if ('partner_featured' === $column) {
        echo $meta['featured'] ? esc_html__('Igen', 'melodiq-journey') : esc_html__('Nem', 'melodiq-journey');
    }

    if ('partner_discount' === $column) {
        echo $meta['has_discount'] ? esc_html(melodiq_partner_discount_label($post_id)) : esc_html__('Nincs', 'melodiq-journey');
    }
}
add_action('manage_partner_posts_custom_column', 'melodiq_partner_admin_column_content', 10, 2);
