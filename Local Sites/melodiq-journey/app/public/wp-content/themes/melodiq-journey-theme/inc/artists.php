<?php

/**
 * Artist post type and helpers.
 */

function melodiq_register_artist_post_type() {
    $labels = array(
        'name'               => __('Artist', 'melodiq-journey'),
        'singular_name'      => __('Artist', 'melodiq-journey'),
        'add_new_item'       => __('Új artist hozzáadása', 'melodiq-journey'),
        'edit_item'          => __('Artist szerkesztése', 'melodiq-journey'),
        'new_item'           => __('Új artist', 'melodiq-journey'),
        'view_item'          => __('Artist megtekintése', 'melodiq-journey'),
        'search_items'       => __('Artist keresése', 'melodiq-journey'),
        'not_found'          => __('Nincs artist', 'melodiq-journey'),
        'not_found_in_trash' => __('Nincs artist a lomtárban', 'melodiq-journey'),
        'menu_name'          => __('Artist', 'melodiq-journey'),
    );

    register_post_type('artist', array(
        'labels'       => $labels,
        'public'       => true,
        'has_archive'  => true,
        'menu_icon'    => 'dashicons-groups',
        'rewrite'      => array('slug' => 'artistok'),
        'show_in_rest' => true,
        'supports'     => array('title', 'editor', 'excerpt', 'thumbnail'),
    ));
}
add_action('init', 'melodiq_register_artist_post_type');

function melodiq_artist_archive_url() {
    $archive_url = get_post_type_archive_link('artist');

    return $archive_url ? $archive_url : home_url('/artistok/');
}

function melodiq_artist_options() {
    return get_posts(array(
        'post_type'      => 'artist',
        'post_status'    => 'publish',
        'posts_per_page' => -1,
        'orderby'        => 'title',
        'order'          => 'ASC',
    ));
}

function melodiq_artist_names_from_ids($artist_ids) {
    $names = array();

    foreach ((array) $artist_ids as $artist_id) {
        $artist_id = absint($artist_id);

        if ($artist_id && 'artist' === get_post_type($artist_id)) {
            $names[] = get_the_title($artist_id);
        }
    }

    return array_values(array_filter(array_unique($names)));
}

function melodiq_sort_artist_archive($query) {
    if (is_admin() || !$query->is_main_query() || !$query->is_post_type_archive('artist')) {
        return;
    }

    $query->set('orderby', 'title');
    $query->set('order', 'ASC');
}
add_action('pre_get_posts', 'melodiq_sort_artist_archive');

function melodiq_artist_like_count($post_id = null) {
    $post_id = $post_id ? $post_id : get_the_ID();
    $likes = (int) get_post_meta($post_id, '_melodiq_artist_likes', true);

    return max(0, $likes);
}

function melodiq_like_artist() {
    check_ajax_referer('melodiq_artist_like', 'nonce');

    $post_id = isset($_POST['post_id']) ? absint($_POST['post_id']) : 0;
    $direction = isset($_POST['direction']) ? sanitize_key(wp_unslash($_POST['direction'])) : 'like';

    if (!$post_id || 'artist' !== get_post_type($post_id)) {
        wp_send_json_error(array(
            'message' => __('Érvénytelen artist.', 'melodiq-journey'),
        ));
    }

    $likes = melodiq_artist_like_count($post_id);
    $likes = 'unlike' === $direction ? max(0, $likes - 1) : $likes + 1;
    update_post_meta($post_id, '_melodiq_artist_likes', $likes);

    wp_send_json_success(array(
        'likes' => $likes,
        'liked' => 'unlike' !== $direction,
    ));
}
add_action('wp_ajax_melodiq_like_artist', 'melodiq_like_artist');
add_action('wp_ajax_nopriv_melodiq_like_artist', 'melodiq_like_artist');

function melodiq_flush_artist_rewrite_rules() {
    melodiq_register_artist_post_type();
    flush_rewrite_rules();
}
add_action('after_switch_theme', 'melodiq_flush_artist_rewrite_rules');

function melodiq_maybe_flush_artist_rewrite_rules() {
    if ('1' === get_option('melodiq_artist_rewrite_flushed')) {
        return;
    }

    flush_rewrite_rules();
    update_option('melodiq_artist_rewrite_flushed', '1');
}
add_action('init', 'melodiq_maybe_flush_artist_rewrite_rules', 20);
