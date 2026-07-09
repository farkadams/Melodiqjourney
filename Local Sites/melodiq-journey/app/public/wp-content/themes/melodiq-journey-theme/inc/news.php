<?php

/**
 * News post type.
 */

function melodiq_register_news_post_type() {
    $labels = array(
        'name'               => __('Hírek', 'melodiq-journey'),
        'singular_name'      => __('Hír', 'melodiq-journey'),
        'add_new_item'       => __('Új hír hozzáadása', 'melodiq-journey'),
        'edit_item'          => __('Hír szerkesztése', 'melodiq-journey'),
        'new_item'           => __('Új hír', 'melodiq-journey'),
        'view_item'          => __('Hír megtekintése', 'melodiq-journey'),
        'search_items'       => __('Hírek keresése', 'melodiq-journey'),
        'not_found'          => __('Nincs hír', 'melodiq-journey'),
        'not_found_in_trash' => __('Nincs hír a lomtárban', 'melodiq-journey'),
        'menu_name'          => __('Hírek', 'melodiq-journey'),
    );

    register_post_type('news', array(
        'labels'       => $labels,
        'public'       => true,
        'has_archive'  => true,
        'menu_icon'    => 'dashicons-megaphone',
        'rewrite'      => array('slug' => 'hirek'),
        'show_in_rest' => true,
        'supports'     => array('title', 'editor', 'excerpt', 'thumbnail', 'comments'),
    ));
}
add_action('init', 'melodiq_register_news_post_type');

function melodiq_news_archive_url() {
    $archive_url = get_post_type_archive_link('news');

    return $archive_url ? $archive_url : home_url('/hirek/');
}

function melodiq_flush_news_rewrite_rules() {
    melodiq_register_news_post_type();
    flush_rewrite_rules();
}
add_action('after_switch_theme', 'melodiq_flush_news_rewrite_rules');

function melodiq_maybe_flush_news_rewrite_rules() {
    if ('1' === get_option('melodiq_news_rewrite_flushed')) {
        return;
    }

    flush_rewrite_rules();
    update_option('melodiq_news_rewrite_flushed', '1');
}
add_action('init', 'melodiq_maybe_flush_news_rewrite_rules', 20);
