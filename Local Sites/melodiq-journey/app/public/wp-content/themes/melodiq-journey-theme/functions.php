<?php

/**
 * Melodiq Journey Theme
 */

require_once get_template_directory() . '/inc/events.php';
require_once get_template_directory() . '/inc/travels.php';
require_once get_template_directory() . '/inc/travel-registrations.php';
require_once get_template_directory() . '/inc/partners.php';
require_once get_template_directory() . '/inc/artists.php';
require_once get_template_directory() . '/inc/news.php';
require_once get_template_directory() . '/inc/newsletter.php';
require_once get_template_directory() . '/inc/contact.php';
require_once get_template_directory() . '/inc/spotify.php';

function melodiq_request_host_candidates() {
    $headers = array(
        'HTTP_HOST',
        'HTTP_X_FORWARDED_HOST',
        'HTTP_X_ORIGINAL_HOST',
    );
    $hosts = array();

    foreach ($headers as $header) {
        if (empty($_SERVER[$header])) {
            continue;
        }

        $header_hosts = explode(',', sanitize_text_field(wp_unslash($_SERVER[$header])));

        foreach ($header_hosts as $host) {
            $host = strtolower(trim($host));
            $host = preg_replace('/:\d+$/', '', $host);

            if ($host) {
                $hosts[] = $host;
            }
        }
    }

    return array_values(array_unique($hosts));
}

function melodiq_public_tunnel_url() {
    $hosts = melodiq_request_host_candidates();
    $is_cloudflare_request = !empty($_SERVER['HTTP_CF_RAY'])
        || !empty($_SERVER['HTTP_CF_VISITOR'])
        || !empty($_SERVER['HTTP_CF_CONNECTING_IP']);

    if (in_array('beta.melodiqjourney.hu', $hosts, true)
        || ($is_cloudflare_request && in_array('melodiq-journey.local', $hosts, true))
    ) {
        return 'https://beta.melodiqjourney.hu';
    }

    return '';
}

function melodiq_use_public_tunnel_url($url) {
    $public_url = melodiq_public_tunnel_url();

    if ($public_url) {
        return $public_url;
    }

    return $url;
}
add_filter('option_home', 'melodiq_use_public_tunnel_url');
add_filter('option_siteurl', 'melodiq_use_public_tunnel_url');

function melodiq_use_public_tunnel_asset_url($url) {
    $public_url = melodiq_public_tunnel_url();

    if (!$public_url) {
        return $url;
    }

    $path = wp_parse_url($url, PHP_URL_PATH);

    return $public_url . ($path ? $path : '');
}
add_filter('template_directory_uri', 'melodiq_use_public_tunnel_asset_url');
add_filter('stylesheet_directory_uri', 'melodiq_use_public_tunnel_asset_url');
add_filter('content_url', 'melodiq_use_public_tunnel_asset_url');
add_filter('plugins_url', 'melodiq_use_public_tunnel_asset_url');
add_filter('wp_get_attachment_url', 'melodiq_use_public_tunnel_asset_url');
add_filter('style_loader_src', 'melodiq_use_public_tunnel_asset_url');
add_filter('script_loader_src', 'melodiq_use_public_tunnel_asset_url');

function melodiq_replace_beta_tunnel_urls($html) {
    $public_url = melodiq_public_tunnel_url();

    if (!$public_url) {
        return $html;
    }

    return str_replace(
        array(
            'http://melodiq-journey.local',
            'https://melodiq-journey.local',
            '//melodiq-journey.local',
        ),
        array(
            $public_url,
            $public_url,
            '//' . wp_parse_url($public_url, PHP_URL_HOST),
        ),
        $html
    );
}

function melodiq_start_beta_tunnel_buffer() {
    if (melodiq_public_tunnel_url()) {
        ob_start('melodiq_replace_beta_tunnel_urls');
    }
}
add_action('template_redirect', 'melodiq_start_beta_tunnel_buffer', 0);

function melodiq_beta_fallback_styles() {
    $public_url = melodiq_public_tunnel_url();

    if (!$public_url) {
        return;
    }

    $styles = array(
        '/assets/css/base.css',
        '/assets/css/layout.css',
        '/assets/css/header.css',
        '/assets/css/home.css',
        '/assets/css/about.css',
        '/assets/css/travel.css',
        '/assets/css/partners.css',
        '/assets/css/footer.css',
        '/assets/css/account.css',
    );

    foreach ($styles as $style_path) {
        $file_path = get_template_directory() . $style_path;
        $version = file_exists($file_path) ? filemtime($file_path) : wp_get_theme()->get('Version');
        printf(
            '<link rel="stylesheet" href="%s" media="all">' . "\n",
            esc_url($public_url . '/wp-content/themes/melodiq-journey-theme' . $style_path . '?ver=' . $version)
        );
    }
}
add_action('wp_head', 'melodiq_beta_fallback_styles', 99);

function melodiq_about_url() {
    $about_page = get_page_by_path('rolunk');

    if ($about_page) {
        return get_permalink($about_page);
    }

    return home_url('/rolunk/');
}

function melodiq_travel_url() {
    $travel_page = get_page_by_path('utazas');

    if ($travel_page) {
        return get_permalink($travel_page);
    }

    return home_url('/utazas/');
}

function melodiq_theme_setup() {

    add_theme_support('title-tag');
    add_theme_support('post-thumbnails');
    add_theme_support('custom-logo', array(
        'height'      => 80,
        'width'       => 240,
        'flex-height' => true,
        'flex-width'  => true,
    ));

    register_nav_menus(array(
        'primary' => __('Primary Menu', 'melodiq-journey'),
    ));
}
add_action('after_setup_theme', 'melodiq_theme_setup');

function melodiq_seed_primary_menu() {
    $locations = get_nav_menu_locations();

    if (!empty($locations['primary'])) {
        return;
    }

    $menu_name = 'Melodiq Primary Menu';
    $menu = wp_get_nav_menu_object($menu_name);

    if (!$menu) {
        $menu_id = wp_create_nav_menu($menu_name);

        if (is_wp_error($menu_id)) {
            return;
        }

        $menu_items = array(
            array(
                'title' => 'Főoldal',
                'url'   => home_url('/'),
            ),
            array(
                'title' => 'Események',
                'url'   => melodiq_event_archive_url(),
            ),
            array(
                'title' => 'Artist',
                'url'   => melodiq_artist_archive_url(),
            ),
            array(
                'title' => 'Journey Club',
                'url'   => home_url('/journey-club/'),
            ),
            array(
                'title' => 'Utazás',
                'url'   => melodiq_travel_url(),
            ),
            array(
                'title' => 'Hírek',
                'url'   => melodiq_news_archive_url(),
            ),
            array(
                'title' => 'Partnerek',
                'url'   => home_url('/partnerek/'),
            ),
            array(
                'title' => 'Rólunk',
                'url'   => melodiq_about_url(),
            ),
            array(
                'title' => 'Kapcsolat',
                'url'   => home_url('/kapcsolat/'),
            ),
        );

        foreach ($menu_items as $menu_item) {
            wp_update_nav_menu_item($menu_id, 0, array(
                'menu-item-title'  => $menu_item['title'],
                'menu-item-url'    => $menu_item['url'],
                'menu-item-status' => 'publish',
                'menu-item-type'   => 'custom',
            ));
        }
    } else {
        $menu_id = (int) $menu->term_id;
    }

    $locations['primary'] = $menu_id;
    set_theme_mod('nav_menu_locations', $locations);
}
add_action('init', 'melodiq_seed_primary_menu', 30);

function melodiq_ensure_news_menu_item() {
    if ('1' === get_option('melodiq_news_menu_seeded')) {
        return;
    }

    $locations = get_nav_menu_locations();

    if (empty($locations['primary'])) {
        return;
    }

    $menu_id = (int) $locations['primary'];
    $menu_items = wp_get_nav_menu_items($menu_id);

    if (!$menu_items) {
        return;
    }

    foreach ($menu_items as $menu_item) {
        if ('Hírek' === $menu_item->title || untrailingslashit($menu_item->url) === untrailingslashit(melodiq_news_archive_url())) {
            update_option('melodiq_news_menu_seeded', '1');
            return;
        }
    }

    wp_update_nav_menu_item($menu_id, 0, array(
        'menu-item-title'  => 'Hírek',
        'menu-item-url'    => melodiq_news_archive_url(),
        'menu-item-status' => 'publish',
        'menu-item-type'   => 'custom',
        'menu-item-position' => 3,
    ));
    update_option('melodiq_news_menu_seeded', '1');
}
add_action('init', 'melodiq_ensure_news_menu_item', 35);

function melodiq_ensure_artist_menu_item() {
    $locations = get_nav_menu_locations();

    if (empty($locations['primary'])) {
        return;
    }

    $menu_id = (int) $locations['primary'];
    $menu_items = wp_get_nav_menu_items($menu_id);

    $menu_items = $menu_items ? $menu_items : array();

    foreach ($menu_items as $menu_item) {
        if ('Artistok' === $menu_item->title || 'Artist' === $menu_item->title || untrailingslashit($menu_item->url) === untrailingslashit(melodiq_artist_archive_url())) {
            if ('Artist' !== $menu_item->title) {
                wp_update_nav_menu_item($menu_id, $menu_item->ID, array(
                    'menu-item-title'  => 'Artist',
                    'menu-item-url'    => melodiq_artist_archive_url(),
                    'menu-item-status' => 'publish',
                    'menu-item-type'   => 'custom',
                ));
            }
            update_option('melodiq_artist_menu_seeded', '1');
            return;
        }
    }

    wp_update_nav_menu_item($menu_id, 0, array(
        'menu-item-title'    => 'Artist',
        'menu-item-url'      => melodiq_artist_archive_url(),
        'menu-item-status'   => 'publish',
        'menu-item-type'     => 'custom',
        'menu-item-position' => 4,
    ));
    update_option('melodiq_artist_menu_seeded', '1');
}
add_action('init', 'melodiq_ensure_artist_menu_item', 36);

function melodiq_ensure_travel_page() {
    $existing_page = get_page_by_path('utazas');

    if (!$existing_page) {
        wp_insert_post(array(
            'post_title'   => 'Utazás',
            'post_name'    => 'utazas',
            'post_status'  => 'publish',
            'post_type'    => 'page',
            'post_content' => '',
        ));
    }
}
add_action('init', 'melodiq_ensure_travel_page', 39);

function melodiq_partners_page_template($template) {
    if (is_page('partnerek')) {
        $partners_template = get_template_directory() . '/archive-partner.php';

        if (file_exists($partners_template)) {
            return $partners_template;
        }
    }

    return $template;
}
add_filter('template_include', 'melodiq_partners_page_template');

function melodiq_ensure_travel_menu_item() {
    $locations = get_nav_menu_locations();

    if (empty($locations['primary'])) {
        return;
    }

    $menu_id = (int) $locations['primary'];
    $menu_items = wp_get_nav_menu_items($menu_id);

    if (!$menu_items) {
        return;
    }

    $position = 5;

    foreach ($menu_items as $menu_item) {
        if ('Journey Club' === $menu_item->title) {
            $position = ((int) $menu_item->menu_order) + 1;
            break;
        }
    }

    foreach ($menu_items as $menu_item) {
        if ('Utazás' === $menu_item->title || untrailingslashit($menu_item->url) === untrailingslashit(melodiq_travel_url())) {
            wp_update_nav_menu_item($menu_id, $menu_item->ID, array(
                'menu-item-title'    => 'Utazás',
                'menu-item-url'      => melodiq_travel_url(),
                'menu-item-status'   => 'publish',
                'menu-item-type'     => 'custom',
                'menu-item-position' => $position,
            ));
            return;
        }
    }

    wp_update_nav_menu_item($menu_id, 0, array(
        'menu-item-title'    => 'Utazás',
        'menu-item-url'      => melodiq_travel_url(),
        'menu-item-status'   => 'publish',
        'menu-item-type'     => 'custom',
        'menu-item-position' => $position,
    ));
}
add_action('init', 'melodiq_ensure_travel_menu_item', 37);

function melodiq_ensure_account_page() {
    $existing_page = get_page_by_path('fiokom');

    if (!$existing_page) {
        wp_insert_post(array(
            'post_title'   => 'Fiókom',
            'post_name'    => 'fiokom',
            'post_status'  => 'publish',
            'post_type'    => 'page',
            'post_content' => '',
        ));
    }
}
add_action('init', 'melodiq_ensure_account_page', 40);

function melodiq_ensure_about_page() {
    $existing_page = get_page_by_path('rolunk');

    if (!$existing_page) {
        wp_insert_post(array(
            'post_title'   => 'Rólunk',
            'post_name'    => 'rolunk',
            'post_status'  => 'publish',
            'post_type'    => 'page',
            'post_content' => '',
        ));
    }
}
add_action('init', 'melodiq_ensure_about_page', 40);

function melodiq_ensure_about_menu_item() {
    $locations = get_nav_menu_locations();

    if (empty($locations['primary'])) {
        return;
    }

    $menu_id = (int) $locations['primary'];
    $menu_items = wp_get_nav_menu_items($menu_id);

    if (!$menu_items) {
        return;
    }

    foreach ($menu_items as $menu_item) {
        if ('Rólunk' === $menu_item->title || untrailingslashit($menu_item->url) === untrailingslashit(home_url('/about/'))) {
            wp_update_nav_menu_item($menu_id, $menu_item->ID, array(
                'menu-item-title'  => 'Rólunk',
                'menu-item-url'    => melodiq_about_url(),
                'menu-item-status' => 'publish',
                'menu-item-type'   => 'custom',
            ));
            return;
        }
    }
}
add_action('init', 'melodiq_ensure_about_menu_item', 41);

function melodiq_primary_menu_fallback() {
    ?>
    <ul class="primary-menu">
        <li class="<?php echo is_front_page() ? 'current-menu-item' : ''; ?>"><a href="<?php echo esc_url(home_url('/')); ?>"><?php echo melodiq_menu_title_markup('Főoldal'); ?></a></li>
        <li class="<?php echo is_post_type_archive('event') || is_singular('event') ? 'current-menu-item' : ''; ?>"><a href="<?php echo esc_url(melodiq_event_archive_url()); ?>"><?php echo melodiq_menu_title_markup('Események'); ?></a></li>
        <li class="<?php echo is_post_type_archive('artist') || is_singular('artist') ? 'current-menu-item' : ''; ?>"><a href="<?php echo esc_url(melodiq_artist_archive_url()); ?>"><?php echo melodiq_menu_title_markup('Artist'); ?></a></li>
        <li><a href="<?php echo esc_url(home_url('/journey-club/')); ?>"><?php echo melodiq_menu_title_markup('Journey Club'); ?></a></li>
        <li class="<?php echo is_page('utazas') || is_singular('travel') ? 'current-menu-item' : ''; ?>"><a href="<?php echo esc_url(melodiq_travel_url()); ?>"><?php echo melodiq_menu_title_markup('Utazás'); ?></a></li>
        <li class="<?php echo is_post_type_archive('news') || is_singular('news') ? 'current-menu-item' : ''; ?>"><a href="<?php echo esc_url(melodiq_news_archive_url()); ?>"><?php echo melodiq_menu_title_markup('Hírek'); ?></a></li>
        <li class="<?php echo is_page('partnerek') ? 'current-menu-item' : ''; ?>"><a href="<?php echo esc_url(home_url('/partnerek/')); ?>"><?php echo melodiq_menu_title_markup('Partnerek'); ?></a></li>
        <li class="<?php echo is_page(array('about', 'rolunk')) ? 'current-menu-item' : ''; ?>"><a href="<?php echo esc_url(melodiq_about_url()); ?>"><?php echo melodiq_menu_title_markup('Rólunk'); ?></a></li>
        <li class="<?php echo is_page('kapcsolat') ? 'current-menu-item' : ''; ?>"><a href="<?php echo esc_url(home_url('/kapcsolat/')); ?>"><?php echo melodiq_menu_title_markup('Kapcsolat'); ?></a></li>
    </ul>
    <?php
}

function melodiq_menu_title_markup($title) {
    $plain_title = trim(wp_strip_all_tags($title));

    return sprintf(
        '<span class="nav-link-text" data-text="%1$s"><span>%2$s</span></span>',
        esc_attr($plain_title),
        esc_html($plain_title)
    );
}

function melodiq_wrap_primary_menu_title($title, $item, $args, $depth) {
    if (!isset($args->theme_location) || 'primary' !== $args->theme_location) {
        return $title;
    }

    return melodiq_menu_title_markup($title);
}
add_filter('nav_menu_item_title', 'melodiq_wrap_primary_menu_title', 10, 4);

function melodiq_primary_menu_current_classes($classes, $item, $args) {
    if (!isset($args->theme_location) || 'primary' !== $args->theme_location) {
        return $classes;
    }

    $item_url = untrailingslashit($item->url);

    if (is_singular('travel') && $item_url === untrailingslashit(melodiq_travel_url())) {
        $classes[] = 'current-menu-item';
    }

    if ((is_post_type_archive('partner') || is_singular('partner') || is_page('partnerek')) && $item_url === untrailingslashit(home_url('/partnerek/'))) {
        $classes[] = 'current-menu-item';
    }

    return array_values(array_unique($classes));
}
add_filter('nav_menu_css_class', 'melodiq_primary_menu_current_classes', 10, 3);

function melodiq_enqueue_assets() {

    $theme_version = wp_get_theme()->get('Version');
    $asset_version = function ($relative_path) use ($theme_version) {
        $asset_path = get_template_directory() . $relative_path;

        return file_exists($asset_path) ? filemtime($asset_path) : $theme_version;
    };

    $theme_uri = get_template_directory_uri();
    $home_style_dependencies = array('melodiq-header');

    wp_enqueue_style(
        'melodiq-base',
        $theme_uri . '/assets/css/base.css',
        array(),
        $asset_version('/assets/css/base.css')
    );

    wp_enqueue_style(
        'melodiq-layout',
        $theme_uri . '/assets/css/layout.css',
        array('melodiq-base'),
        $asset_version('/assets/css/layout.css')
    );

    wp_enqueue_style(
        'melodiq-header',
        $theme_uri . '/assets/css/header.css',
        array('melodiq-layout'),
        $asset_version('/assets/css/header.css')
    );

    if (is_front_page()) {
        wp_enqueue_style(
            'melodiq-swiper',
            'https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css',
            array(),
            '11'
        );

        $home_style_dependencies[] = 'melodiq-swiper';
    }

    wp_enqueue_style(
        'melodiq-home',
        $theme_uri . '/assets/css/home.css',
        $home_style_dependencies,
        $asset_version('/assets/css/home.css')
    );

    wp_enqueue_style(
        'melodiq-about',
        $theme_uri . '/assets/css/about.css',
        array('melodiq-home'),
        $asset_version('/assets/css/about.css')
    );

    wp_enqueue_style(
        'melodiq-travel',
        $theme_uri . '/assets/css/travel.css',
        array('melodiq-about'),
        $asset_version('/assets/css/travel.css')
    );

    wp_enqueue_style(
        'melodiq-partners',
        $theme_uri . '/assets/css/partners.css',
        array('melodiq-travel'),
        $asset_version('/assets/css/partners.css')
    );

    wp_enqueue_style(
        'melodiq-footer',
        $theme_uri . '/assets/css/footer.css',
        array('melodiq-header'),
        $asset_version('/assets/css/footer.css')
    );

    wp_enqueue_style(
        'melodiq-account',
        $theme_uri . '/assets/css/account.css',
        array('melodiq-home'),
        $asset_version('/assets/css/account.css')
    );

    wp_enqueue_script(
        'melodiq-events',
        $theme_uri . '/assets/js/events.js',
        array(),
        $asset_version('/assets/js/events.js'),
        true
    );

    if (is_front_page()) {
        wp_enqueue_script(
            'melodiq-swiper',
            'https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js',
            array(),
            '11',
            true
        );

        wp_enqueue_script(
            'melodiq-home',
            $theme_uri . '/assets/js/home.js',
            array('melodiq-swiper'),
            $asset_version('/assets/js/home.js'),
            true
        );
    }

    wp_localize_script('melodiq-events', 'melodiqEvents', array(
        'ajaxUrl'     => admin_url('admin-ajax.php'),
        'eventNonce'  => wp_create_nonce('melodiq_event_like'),
        'artistNonce' => wp_create_nonce('melodiq_artist_like'),
    ));
}
add_action('wp_enqueue_scripts', 'melodiq_enqueue_assets');
