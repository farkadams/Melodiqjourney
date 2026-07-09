<?php

/**
 * Event post type and helpers.
 */

function melodiq_register_event_post_type() {
    $labels = array(
        'name'               => __('Események', 'melodiq-journey'),
        'singular_name'      => __('Esemény', 'melodiq-journey'),
        'add_new_item'       => __('Új esemény hozzáadása', 'melodiq-journey'),
        'edit_item'          => __('Esemény szerkesztése', 'melodiq-journey'),
        'new_item'           => __('Új esemény', 'melodiq-journey'),
        'view_item'          => __('Esemény megtekintése', 'melodiq-journey'),
        'search_items'       => __('Események keresése', 'melodiq-journey'),
        'not_found'          => __('Nincs esemény', 'melodiq-journey'),
        'not_found_in_trash' => __('Nincs esemény a lomtárban', 'melodiq-journey'),
        'menu_name'          => __('Események', 'melodiq-journey'),
    );

    register_post_type('event', array(
        'labels'        => $labels,
        'public'        => true,
        'has_archive'   => true,
        'menu_icon'     => 'dashicons-calendar-alt',
        'rewrite'       => array('slug' => 'esemenyek'),
        'show_in_rest'  => true,
        'supports'      => array('title', 'editor', 'excerpt', 'thumbnail'),
    ));
}
add_action('init', 'melodiq_register_event_post_type');

function melodiq_flush_event_rewrite_rules() {
    melodiq_register_event_post_type();
    flush_rewrite_rules();
}
add_action('after_switch_theme', 'melodiq_flush_event_rewrite_rules');

function melodiq_maybe_flush_event_rewrite_rules() {
    if ('1' === get_option('melodiq_event_rewrite_flushed')) {
        return;
    }

    flush_rewrite_rules();
    update_option('melodiq_event_rewrite_flushed', '1');
}
add_action('init', 'melodiq_maybe_flush_event_rewrite_rules', 20);

function melodiq_add_event_meta_boxes() {
    add_meta_box(
        'melodiq_event_details',
        __('Esemény adatok', 'melodiq-journey'),
        'melodiq_render_event_details_meta_box',
        'event',
        'normal',
        'high'
    );
}
add_action('add_meta_boxes', 'melodiq_add_event_meta_boxes');

function melodiq_enqueue_event_admin_assets($hook) {
    if (!in_array($hook, array('post.php', 'post-new.php'), true)) {
        return;
    }

    $screen = get_current_screen();

    if (!$screen || 'event' !== $screen->post_type) {
        return;
    }

    $asset_path = get_template_directory() . '/assets/css/admin-event.css';

    wp_enqueue_style(
        'melodiq-admin-event',
        get_template_directory_uri() . '/assets/css/admin-event.css',
        array(),
        file_exists($asset_path) ? filemtime($asset_path) : wp_get_theme()->get('Version')
    );

    $script_path = get_template_directory() . '/assets/js/admin-event.js';

    wp_enqueue_script(
        'melodiq-admin-event',
        get_template_directory_uri() . '/assets/js/admin-event.js',
        array(),
        file_exists($script_path) ? filemtime($script_path) : wp_get_theme()->get('Version'),
        true
    );
}
add_action('admin_enqueue_scripts', 'melodiq_enqueue_event_admin_assets');

function melodiq_render_event_details_meta_box($post) {
    wp_nonce_field('melodiq_save_event_details', 'melodiq_event_details_nonce');

    $date       = get_post_meta($post->ID, '_melodiq_event_date', true);
    $date_end   = get_post_meta($post->ID, '_melodiq_event_date_end', true);
    $city       = get_post_meta($post->ID, '_melodiq_event_city', true);
    $organizer  = get_post_meta($post->ID, '_melodiq_event_organizer', true);
    $venue      = get_post_meta($post->ID, '_melodiq_event_venue', true);
    $headliner  = get_post_meta($post->ID, '_melodiq_event_headliner', true);
    $performers = get_post_meta($post->ID, '_melodiq_event_performers', true);
    $headliner_artist_ids = get_post_meta($post->ID, '_melodiq_event_headliner_artists', true);
    $headliner_artist_ids = is_array($headliner_artist_ids) ? array_map('intval', $headliner_artist_ids) : array();
    $legacy_headliner_artist_id = (int) get_post_meta($post->ID, '_melodiq_event_headliner_artist', true);

    if ($legacy_headliner_artist_id && !in_array($legacy_headliner_artist_id, $headliner_artist_ids, true)) {
        $headliner_artist_ids[] = $legacy_headliner_artist_id;
    }

    $performer_artist_ids = get_post_meta($post->ID, '_melodiq_event_performer_artists', true);
    $performer_artist_ids = is_array($performer_artist_ids) ? array_map('intval', $performer_artist_ids) : array();
    $artist_options = function_exists('melodiq_artist_options') ? melodiq_artist_options() : array();
    $ticket_url = get_post_meta($post->ID, '_melodiq_event_ticket_url', true);
    $featured   = get_post_meta($post->ID, '_melodiq_event_featured', true);
    ?>
    <div class="melodiq-admin-event-panel">
        <section class="melodiq-admin-event-section">
            <div class="melodiq-admin-event-section-heading">
                <h3><?php esc_html_e('Időpont', 'melodiq-journey'); ?></h3>
                <p><?php esc_html_e('Egynapos eseménynél csak a kezdő dátumot töltsd ki. Fesztiválnál add meg a záró dátumot is.', 'melodiq-journey'); ?></p>
            </div>

            <div class="melodiq-admin-event-grid melodiq-admin-event-grid-2">
                <label class="melodiq-admin-event-field" for="melodiq_event_date">
                    <span><?php esc_html_e('Kezdő dátum', 'melodiq-journey'); ?></span>
                    <input id="melodiq_event_date" type="date" name="melodiq_event_date" value="<?php echo esc_attr($date); ?>">
                </label>

                <label class="melodiq-admin-event-field" for="melodiq_event_date_end">
                    <span><?php esc_html_e('Záró dátum', 'melodiq-journey'); ?></span>
                    <input id="melodiq_event_date_end" type="date" name="melodiq_event_date_end" value="<?php echo esc_attr($date_end); ?>">
                    <small><?php esc_html_e('Üresen hagyható.', 'melodiq-journey'); ?></small>
                </label>
            </div>
        </section>

        <section class="melodiq-admin-event-section">
            <div class="melodiq-admin-event-section-heading">
                <h3><?php esc_html_e('Helyszín és szervező', 'melodiq-journey'); ?></h3>
            </div>

            <div class="melodiq-admin-event-grid melodiq-admin-event-grid-2">
                <label class="melodiq-admin-event-field" for="melodiq_event_city">
                    <span><?php esc_html_e('Város / helyszín', 'melodiq-journey'); ?></span>
                    <input id="melodiq_event_city" type="text" name="melodiq_event_city" value="<?php echo esc_attr($city); ?>" placeholder="Budapest, Sziget">
                </label>

                <label class="melodiq-admin-event-field" for="melodiq_event_organizer">
                    <span><?php esc_html_e('Szervező', 'melodiq-journey'); ?></span>
                    <input id="melodiq_event_organizer" type="text" name="melodiq_event_organizer" value="<?php echo esc_attr($organizer ? $organizer : $venue); ?>" placeholder="Melodiq Journey">
                </label>
            </div>
        </section>

        <section class="melodiq-admin-event-section">
            <div class="melodiq-admin-event-section-heading">
                <h3><?php esc_html_e('Lineup', 'melodiq-journey'); ?></h3>
                <p><?php esc_html_e('A headliner külön kiemelve jelenik meg, a fellépőket vesszővel vagy új sorral válaszd el.', 'melodiq-journey'); ?></p>
            </div>

            <div class="melodiq-admin-event-grid melodiq-admin-event-grid-2">
                <label class="melodiq-admin-event-field" for="melodiq_event_headliner_artist">
                    <span><?php esc_html_e('Headlinerek kiválasztása', 'melodiq-journey'); ?></span>
                    <select id="melodiq_event_headliner_artist" name="melodiq_event_headliner_artists[]" multiple size="5" data-artist-headliner-select>
                        <?php foreach ($artist_options as $artist_option) : ?>
                            <option value="<?php echo esc_attr($artist_option->ID); ?>" data-artist-name="<?php echo esc_attr(get_the_title($artist_option)); ?>" <?php selected(in_array((int) $artist_option->ID, $headliner_artist_ids, true)); ?>>
                                <?php echo esc_html(get_the_title($artist_option)); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <small><?php esc_html_e('Több headliner kijelölhető. Macen Cmd+kattintás, Windowson Ctrl+kattintás.', 'melodiq-journey'); ?></small>
                </label>

                <label class="melodiq-admin-event-field" for="melodiq_event_headliner">
                    <span><?php esc_html_e('Headlinerek', 'melodiq-journey'); ?></span>
                    <input id="melodiq_event_headliner" type="text" name="melodiq_event_headliner" value="<?php echo esc_attr($headliner); ?>" placeholder="Carl Cox" data-artist-headliner-input>
                </label>

                <label class="melodiq-admin-event-field" for="melodiq_event_performer_artists">
                    <span><?php esc_html_e('Fellépők kiválasztása', 'melodiq-journey'); ?></span>
                    <select id="melodiq_event_performer_artists" name="melodiq_event_performer_artists[]" multiple size="5" data-artist-performers-select>
                        <?php foreach ($artist_options as $artist_option) : ?>
                            <option value="<?php echo esc_attr($artist_option->ID); ?>" data-artist-name="<?php echo esc_attr(get_the_title($artist_option)); ?>" <?php selected(in_array((int) $artist_option->ID, $performer_artist_ids, true)); ?>>
                                <?php echo esc_html(get_the_title($artist_option)); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <small><?php esc_html_e('Több artist kijelölhető. Macen Cmd+kattintás, Windowson Ctrl+kattintás.', 'melodiq-journey'); ?></small>
                </label>

                <label class="melodiq-admin-event-field" for="melodiq_event_performers">
                    <span><?php esc_html_e('Fellépők', 'melodiq-journey'); ?></span>
                    <textarea id="melodiq_event_performers" name="melodiq_event_performers" rows="3" placeholder="Chris Stussy, Karretero" data-artist-performers-textarea><?php echo esc_textarea($performers); ?></textarea>
                </label>
            </div>
        </section>

        <section class="melodiq-admin-event-section">
            <div class="melodiq-admin-event-section-heading">
                <h3><?php esc_html_e('Jegyek és kiemelés', 'melodiq-journey'); ?></h3>
            </div>

            <label class="melodiq-admin-event-field" for="melodiq_event_ticket_url">
                <span><?php esc_html_e('Jegyvásárlás link', 'melodiq-journey'); ?></span>
                <input id="melodiq_event_ticket_url" type="url" name="melodiq_event_ticket_url" value="<?php echo esc_url($ticket_url); ?>" placeholder="https://">
                <small><?php esc_html_e('Ha üres, a kártyák az esemény saját oldalára vezetnek.', 'melodiq-journey'); ?></small>
            </label>

            <label class="melodiq-admin-event-toggle">
                <input type="checkbox" name="melodiq_event_featured" value="1" <?php checked($featured, '1'); ?>>
                <span>
                    <strong><?php esc_html_e('Kiemelt esemény', 'melodiq-journey'); ?></strong>
                    <small><?php esc_html_e('Megjelenhet a jobb oldali kiemelt pakliban és a fő esemény ajánlóban.', 'melodiq-journey'); ?></small>
                </span>
            </label>
        </section>

        <?php if (function_exists('melodiq_partner_select_field')) : ?>
            <section class="melodiq-admin-event-section">
                <div class="melodiq-admin-event-section-heading">
                    <h3><?php esc_html_e('Partnerek', 'melodiq-journey'); ?></h3>
                    <p><?php esc_html_e('A kiválasztott partnerek automatikusan megjelennek az esemény oldalán is.', 'melodiq-journey'); ?></p>
                </div>

                <label class="melodiq-admin-event-field">
                    <span><?php esc_html_e('Kapcsolódó partnerek', 'melodiq-journey'); ?></span>
                    <?php melodiq_partner_select_field('melodiq_event_partners', get_post_meta($post->ID, '_melodiq_event_partners', true)); ?>
                </label>
            </section>
        <?php endif; ?>
    </div>
    <?php
}

function melodiq_save_event_details($post_id) {
    if (!isset($_POST['melodiq_event_details_nonce'])) {
        return;
    }

    if (!wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['melodiq_event_details_nonce'])), 'melodiq_save_event_details')) {
        return;
    }

    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }

    if (!current_user_can('edit_post', $post_id)) {
        return;
    }

    $date = melodiq_validate_event_date(isset($_POST['melodiq_event_date']) ? sanitize_text_field(wp_unslash($_POST['melodiq_event_date'])) : '');
    $date_end = melodiq_validate_event_date(isset($_POST['melodiq_event_date_end']) ? sanitize_text_field(wp_unslash($_POST['melodiq_event_date_end'])) : '');

    if ($date && $date_end && strtotime($date_end . ' 12:00:00') < strtotime($date . ' 12:00:00')) {
        $date_end = $date;
    }

    update_post_meta($post_id, '_melodiq_event_date', $date);
    update_post_meta($post_id, '_melodiq_event_date_end', $date_end);
    update_post_meta($post_id, '_melodiq_event_city', isset($_POST['melodiq_event_city']) ? sanitize_text_field(wp_unslash($_POST['melodiq_event_city'])) : '');
    update_post_meta($post_id, '_melodiq_event_organizer', isset($_POST['melodiq_event_organizer']) ? sanitize_text_field(wp_unslash($_POST['melodiq_event_organizer'])) : '');

    $headliner_artist_ids = isset($_POST['melodiq_event_headliner_artists']) ? array_map('absint', (array) wp_unslash($_POST['melodiq_event_headliner_artists'])) : array();
    $headliner_artist_ids = array_values(array_filter($headliner_artist_ids));
    $performer_artist_ids = isset($_POST['melodiq_event_performer_artists']) ? array_map('absint', (array) wp_unslash($_POST['melodiq_event_performer_artists'])) : array();
    $performer_artist_ids = array_values(array_filter($performer_artist_ids));
    $headliner = isset($_POST['melodiq_event_headliner']) ? sanitize_text_field(wp_unslash($_POST['melodiq_event_headliner'])) : '';
    $performers = isset($_POST['melodiq_event_performers']) ? sanitize_textarea_field(wp_unslash($_POST['melodiq_event_performers'])) : '';

    if ($headliner_artist_ids && function_exists('melodiq_artist_names_from_ids')) {
        $selected_headliners = melodiq_artist_names_from_ids($headliner_artist_ids);
        $manual_headliners = melodiq_event_performer_list($headliner);
        $headliner = implode(', ', array_values(array_unique(array_merge($selected_headliners, $manual_headliners))));
    }

    if ($performer_artist_ids && function_exists('melodiq_artist_names_from_ids')) {
        $selected_performers = melodiq_artist_names_from_ids($performer_artist_ids);
        $manual_performers = melodiq_event_performer_list($performers);
        $performers = implode(', ', array_values(array_unique(array_merge($selected_performers, $manual_performers))));
    }

    update_post_meta($post_id, '_melodiq_event_headliner_artists', $headliner_artist_ids);
    update_post_meta($post_id, '_melodiq_event_headliner_artist', isset($headliner_artist_ids[0]) ? $headliner_artist_ids[0] : 0);
    update_post_meta($post_id, '_melodiq_event_performer_artists', $performer_artist_ids);
    update_post_meta($post_id, '_melodiq_event_headliner', $headliner);
    update_post_meta($post_id, '_melodiq_event_performers', $performers);
    update_post_meta($post_id, '_melodiq_event_ticket_url', isset($_POST['melodiq_event_ticket_url']) ? esc_url_raw(wp_unslash($_POST['melodiq_event_ticket_url'])) : '');
    update_post_meta($post_id, '_melodiq_event_featured', isset($_POST['melodiq_event_featured']) ? '1' : '0');

    $partner_ids = isset($_POST['melodiq_event_partners']) ? array_values(array_filter(array_map('absint', (array) wp_unslash($_POST['melodiq_event_partners'])))) : array();
    update_post_meta($post_id, '_melodiq_event_partners', $partner_ids);
}
add_action('save_post_event', 'melodiq_save_event_details');

function melodiq_validate_event_date($date) {
    return $date && preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) ? $date : '';
}

function melodiq_sort_event_archive($query) {
    if (is_admin() || !$query->is_main_query() || !$query->is_post_type_archive('event')) {
        return;
    }

    $query->set('meta_key', '_melodiq_event_date');
    $query->set('orderby', 'meta_value');
    $query->set('order', 'ASC');
}
add_action('pre_get_posts', 'melodiq_sort_event_archive');

function melodiq_event_meta($post_id = null) {
    $post_id = $post_id ? $post_id : get_the_ID();

    return array(
        'date'       => get_post_meta($post_id, '_melodiq_event_date', true),
        'date_end'   => get_post_meta($post_id, '_melodiq_event_date_end', true),
        'city'       => get_post_meta($post_id, '_melodiq_event_city', true),
        'organizer'  => get_post_meta($post_id, '_melodiq_event_organizer', true) ?: get_post_meta($post_id, '_melodiq_event_venue', true),
        'headliner'  => get_post_meta($post_id, '_melodiq_event_headliner', true),
        'performers' => get_post_meta($post_id, '_melodiq_event_performers', true),
        'headliner_artist_ids' => melodiq_event_artist_ids($post_id, '_melodiq_event_headliner_artists', '_melodiq_event_headliner_artist'),
        'performer_artist_ids' => melodiq_event_artist_ids($post_id, '_melodiq_event_performer_artists'),
        'ticket_url' => get_post_meta($post_id, '_melodiq_event_ticket_url', true),
        'featured'   => get_post_meta($post_id, '_melodiq_event_featured', true),
    );
}

function melodiq_event_artist_ids($post_id, $meta_key, $legacy_meta_key = '') {
    $artist_ids = get_post_meta($post_id, $meta_key, true);
    $artist_ids = is_array($artist_ids) ? array_map('absint', $artist_ids) : array();

    if ($legacy_meta_key) {
        $legacy_artist_id = absint(get_post_meta($post_id, $legacy_meta_key, true));

        if ($legacy_artist_id && !in_array($legacy_artist_id, $artist_ids, true)) {
            $artist_ids[] = $legacy_artist_id;
        }
    }

    return array_values(array_filter(array_unique($artist_ids)));
}

function melodiq_event_month_name($timestamp) {
    $month_names = array(
        1  => 'JAN',
        2  => 'FEBR',
        3  => 'MÁRC',
        4  => 'ÁPR',
        5  => 'MÁJ',
        6  => 'JÚN',
        7  => 'JÚL',
        8  => 'AUG',
        9  => 'SZEPT',
        10 => 'OKT',
        11 => 'NOV',
        12 => 'DEC',
    );
    $month_number = (int) wp_date('n', $timestamp);

    return $month_names[$month_number];
}

function melodiq_event_date_parts($date, $date_end = '') {
    if (!$date) {
        return array(
            'day'   => '--',
            'month' => '',
            'label' => '',
        );
    }

    $timestamp = strtotime($date . ' 12:00:00');
    $end_timestamp = $date_end ? strtotime($date_end . ' 12:00:00') : 0;

    if ($end_timestamp && $end_timestamp > $timestamp) {
        $same_month = wp_date('Y-m', $timestamp) === wp_date('Y-m', $end_timestamp);
        $day = wp_date('d', $timestamp) . '-' . wp_date('d', $end_timestamp);
        $month = $same_month ? melodiq_event_month_name($timestamp) : melodiq_event_month_name($timestamp) . '/' . melodiq_event_month_name($end_timestamp);

        return array(
            'day'   => $day,
            'month' => $month,
            'label' => $same_month ? $day . ' ' . $month : wp_date('d', $timestamp) . ' ' . melodiq_event_month_name($timestamp) . ' - ' . wp_date('d', $end_timestamp) . ' ' . melodiq_event_month_name($end_timestamp),
        );
    }

    return array(
        'day'   => wp_date('d', $timestamp),
        'month' => melodiq_event_month_name($timestamp),
        'label' => wp_date('d', $timestamp) . ' ' . melodiq_event_month_name($timestamp),
    );
}

function melodiq_event_query($args = array()) {
    $today = current_time('Y-m-d');

    $defaults = array(
        'post_type'      => 'event',
        'posts_per_page' => 5,
        'post_status'    => 'publish',
        'meta_key'       => '_melodiq_event_date',
        'orderby'        => 'meta_value',
        'order'          => 'ASC',
        'meta_query'     => array(
            'relation' => 'OR',
            array(
                'key'     => '_melodiq_event_date',
                'value'   => $today,
                'compare' => '>=',
                'type'    => 'DATE',
            ),
            array(
                'key'     => '_melodiq_event_date_end',
                'value'   => $today,
                'compare' => '>=',
                'type'    => 'DATE',
            ),
        ),
    );

    $query_args = wp_parse_args($args, $defaults);
    $query = new WP_Query($query_args);

    if ($query->have_posts() || (isset($args['fallback_to_any']) && false === $args['fallback_to_any'])) {
        return $query;
    }

    $fallback_args = array(
        'post_type'      => 'event',
        'posts_per_page' => isset($query_args['posts_per_page']) ? $query_args['posts_per_page'] : 5,
        'post_status'    => 'publish',
        'orderby'        => 'date',
        'order'          => 'DESC',
    );

    return new WP_Query($fallback_args);
}

function melodiq_featured_event_query() {
    $today = current_time('Y-m-d');
    $query = new WP_Query(array(
        'post_type'      => 'event',
        'posts_per_page' => 1,
        'post_status'    => 'publish',
        'orderby'        => 'rand',
        'meta_query'     => array(
            'relation' => 'AND',
            array(
                'relation' => 'OR',
                array(
                    'key'     => '_melodiq_event_date',
                    'value'   => $today,
                    'compare' => '>=',
                    'type'    => 'DATE',
                ),
                array(
                    'key'     => '_melodiq_event_date_end',
                    'value'   => $today,
                    'compare' => '>=',
                    'type'    => 'DATE',
                ),
            ),
            array(
                'key'     => '_melodiq_event_featured',
                'value'   => '1',
                'compare' => '=',
            ),
        ),
    ));

    if ($query->have_posts()) {
        return $query;
    }

    wp_reset_postdata();

    $query = melodiq_event_query(array(
        'posts_per_page' => 1,
        'meta_query'     => array(
            'relation' => 'AND',
            array(
                'relation' => 'OR',
                array(
                    'key'     => '_melodiq_event_date',
                    'value'   => current_time('Y-m-d'),
                    'compare' => '>=',
                    'type'    => 'DATE',
                ),
                array(
                    'key'     => '_melodiq_event_date_end',
                    'value'   => current_time('Y-m-d'),
                    'compare' => '>=',
                    'type'    => 'DATE',
                ),
            ),
            array(
                'key'     => '_melodiq_event_featured',
                'value'   => '1',
                'compare' => '=',
            ),
        ),
    ));

    if ($query->have_posts()) {
        return $query;
    }

    wp_reset_postdata();

    return melodiq_event_query(array('posts_per_page' => 1));
}

function melodiq_event_action_url($post_id = null) {
    $post_id = $post_id ? $post_id : get_the_ID();
    $ticket_url = get_post_meta($post_id, '_melodiq_event_ticket_url', true);

    return $ticket_url ? $ticket_url : get_permalink($post_id);
}

function melodiq_event_performer_list($performers) {
    if (!$performers) {
        return array();
    }

    $items = preg_split('/[\r\n,]+/', $performers);
    $items = array_map('trim', $items);

    return array_values(array_filter($items));
}

function melodiq_event_artist_link_map($artist_ids) {
    $artist_links = array();

    foreach ((array) $artist_ids as $artist_id) {
        $artist_id = absint($artist_id);

        if (!$artist_id || 'artist' !== get_post_type($artist_id)) {
            continue;
        }

        $artist_name = get_the_title($artist_id);
        $artist_key = function_exists('mb_strtolower') ? mb_strtolower($artist_name) : strtolower($artist_name);
        $artist_links[$artist_key] = get_permalink($artist_id);
    }

    return $artist_links;
}

function melodiq_event_lineup_names_markup($names, $artist_ids = array()) {
    $artist_links = melodiq_event_artist_link_map($artist_ids);
    $parts = array();

    foreach ((array) $names as $name) {
        $name = trim($name);

        if (!$name) {
            continue;
        }

        $artist_key = function_exists('mb_strtolower') ? mb_strtolower($name) : strtolower($name);

        if (isset($artist_links[$artist_key])) {
            $parts[] = sprintf(
                '<a class="event-lineup-artist-link" href="%s">%s</a>',
                esc_url($artist_links[$artist_key]),
                esc_html($name)
            );
            continue;
        }

        $parts[] = esc_html($name);
    }

    return implode('<span class="event-lineup-separator">, </span>', $parts);
}

function melodiq_event_lineup_markup($event_meta, $compact = false) {
    $headliner = isset($event_meta['headliner']) ? melodiq_event_performer_list($event_meta['headliner']) : array();
    $performers = isset($event_meta['performers']) ? melodiq_event_performer_list($event_meta['performers']) : array();
    $headliner_artist_ids = isset($event_meta['headliner_artist_ids']) ? (array) $event_meta['headliner_artist_ids'] : array();
    $performer_artist_ids = isset($event_meta['performer_artist_ids']) ? (array) $event_meta['performer_artist_ids'] : array();

    if (!$headliner && !$performers) {
        return '';
    }

    ob_start();
    ?>
    <div class="event-lineup <?php echo $compact ? 'event-lineup-compact' : ''; ?>">
        <?php if ($headliner) : ?>
            <div class="event-lineup-row event-lineup-headliner">
                <span><?php esc_html_e('Headliner', 'melodiq-journey'); ?></span>
                <strong><?php echo melodiq_event_lineup_names_markup($headliner, $headliner_artist_ids); ?></strong>
            </div>
        <?php endif; ?>

        <?php if ($performers) : ?>
            <div class="event-lineup-row">
                <span><?php esc_html_e('Fellépők', 'melodiq-journey'); ?></span>
                <strong><?php echo melodiq_event_lineup_names_markup($performers, $performer_artist_ids); ?></strong>
            </div>
        <?php endif; ?>
    </div>
    <?php
    return ob_get_clean();
}

function melodiq_event_ticket_link($post_id = null, $class = '') {
    $post_id = $post_id ? $post_id : get_the_ID();
    $ticket_url = get_post_meta($post_id, '_melodiq_event_ticket_url', true);
    $url = $ticket_url ? $ticket_url : get_permalink($post_id);
    $attrs = $ticket_url ? ' target="_blank" rel="noopener noreferrer"' : '';

    ob_start();
    ?>
    <a class="event-ticket-link <?php echo esc_attr($class); ?>" href="<?php echo esc_url($url); ?>"<?php echo $attrs; ?>>
        <svg viewBox="0 0 24 24" aria-hidden="true">
            <path d="M4 8.5V6a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v2.5a2.5 2.5 0 0 0 0 5V16a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2v-2.5a2.5 2.5 0 0 0 0-5Z"></path>
            <path d="M9 7v10"></path>
            <path d="M13 8h3"></path>
            <path d="M13 12h3"></path>
        </svg>
        <span><?php esc_html_e('Jegyvásárlás', 'melodiq-journey'); ?></span>
    </a>
    <?php
    return ob_get_clean();
}

function melodiq_event_like_count($post_id = null) {
    $post_id = $post_id ? $post_id : get_the_ID();
    $likes = (int) get_post_meta($post_id, '_melodiq_event_likes', true);

    return max(0, $likes);
}

function melodiq_like_event() {
    check_ajax_referer('melodiq_event_like', 'nonce');

    $post_id = isset($_POST['post_id']) ? absint($_POST['post_id']) : 0;
    $direction = isset($_POST['direction']) ? sanitize_key(wp_unslash($_POST['direction'])) : 'like';

    if (!$post_id || 'event' !== get_post_type($post_id)) {
        wp_send_json_error(array(
            'message' => __('Érvénytelen esemény.', 'melodiq-journey'),
        ));
    }

    $likes = melodiq_event_like_count($post_id);
    $likes = 'unlike' === $direction ? max(0, $likes - 1) : $likes + 1;
    update_post_meta($post_id, '_melodiq_event_likes', $likes);

    wp_send_json_success(array(
        'likes' => $likes,
        'liked' => 'unlike' !== $direction,
    ));
}
add_action('wp_ajax_melodiq_like_event', 'melodiq_like_event');
add_action('wp_ajax_nopriv_melodiq_like_event', 'melodiq_like_event');

function melodiq_event_archive_url() {
    $archive_url = get_post_type_archive_link('event');

    return $archive_url ? $archive_url : home_url('/esemenyek/');
}

function melodiq_event_admin_columns($columns) {
    $columns['event_date'] = __('Dátum', 'melodiq-journey');
    $columns['event_city'] = __('Város', 'melodiq-journey');
    $columns['organizer']  = __('Szervező', 'melodiq-journey');
    $columns['featured']   = __('Kiemelt', 'melodiq-journey');

    return $columns;
}
add_filter('manage_event_posts_columns', 'melodiq_event_admin_columns');

function melodiq_event_admin_column_content($column, $post_id) {
    if ('event_date' === $column) {
        $event_meta = melodiq_event_meta($post_id);
        $date_parts = melodiq_event_date_parts($event_meta['date'], $event_meta['date_end']);
        echo esc_html($date_parts['label']);
    }

    if ('event_city' === $column) {
        echo esc_html(get_post_meta($post_id, '_melodiq_event_city', true));
    }

    if ('organizer' === $column) {
        $organizer = get_post_meta($post_id, '_melodiq_event_organizer', true) ?: get_post_meta($post_id, '_melodiq_event_venue', true);
        echo esc_html($organizer);
    }

    if ('featured' === $column) {
        echo get_post_meta($post_id, '_melodiq_event_featured', true) ? esc_html__('Igen', 'melodiq-journey') : esc_html__('Nem', 'melodiq-journey');
    }
}
add_action('manage_event_posts_custom_column', 'melodiq_event_admin_column_content', 10, 2);
