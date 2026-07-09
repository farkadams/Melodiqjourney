<?php

/**
 * Travel post type and helpers.
 */

function melodiq_register_travel_post_type() {
    $labels = array(
        'name'               => __('Utazások', 'melodiq-journey'),
        'singular_name'      => __('Utazás', 'melodiq-journey'),
        'add_new_item'       => __('Új utazás hozzáadása', 'melodiq-journey'),
        'edit_item'          => __('Utazás szerkesztése', 'melodiq-journey'),
        'new_item'           => __('Új utazás', 'melodiq-journey'),
        'view_item'          => __('Utazás megtekintése', 'melodiq-journey'),
        'search_items'       => __('Utazások keresése', 'melodiq-journey'),
        'not_found'          => __('Nincs utazás', 'melodiq-journey'),
        'not_found_in_trash' => __('Nincs utazás a lomtárban', 'melodiq-journey'),
        'menu_name'          => __('Utazások', 'melodiq-journey'),
    );

    register_post_type('travel', array(
        'labels'        => $labels,
        'public'        => true,
        'has_archive'   => false,
        'menu_icon'     => 'dashicons-location-alt',
        'rewrite'       => array('slug' => 'utazas'),
        'show_in_rest'  => true,
        'supports'      => array('title', 'editor', 'excerpt', 'thumbnail'),
    ));
}
add_action('init', 'melodiq_register_travel_post_type');

function melodiq_maybe_flush_travel_rewrite_rules() {
    if ('1' === get_option('melodiq_travel_rewrite_flushed')) {
        return;
    }

    melodiq_register_travel_post_type();
    flush_rewrite_rules();
    update_option('melodiq_travel_rewrite_flushed', '1');
}
add_action('init', 'melodiq_maybe_flush_travel_rewrite_rules', 21);

function melodiq_ensure_default_travel() {
    if ('1' === get_option('melodiq_default_travel_seeded')) {
        return;
    }

    $existing_travels = get_posts(array(
        'post_type'      => 'travel',
        'post_status'    => 'any',
        'posts_per_page' => 1,
        'fields'         => 'ids',
    ));

    if ($existing_travels) {
        update_option('melodiq_default_travel_seeded', '1');
        return;
    }

    $travel_id = wp_insert_post(array(
        'post_title'   => 'UNBROS - Whispers Of Ice - Pozsony',
        'post_name'    => 'unbros-whispers-of-ice',
        'post_status'  => 'publish',
        'post_type'    => 'travel',
        'post_excerpt' => 'Közös buszos indulás Budapestről a Whispers Of Ice eseményre.',
        'post_content' => '<p>Közös buszos utazás Budapestről Pozsonyba, a Whispers Of Ice melodic techno eseményre. Az utazás célja, hogy a közösség együtt, szervezetten és kényelmesen érkezzen meg az estére.</p><p>A részletes program, indulási pont és friss információk a jelentkezési felület beágyazása után lesznek véglegesítve.</p>',
    ));

    if (is_wp_error($travel_id) || !$travel_id) {
        return;
    }

    update_post_meta($travel_id, '_melodiq_travel_event_name', 'UNBROS - Whispers Of Ice');
    update_post_meta($travel_id, '_melodiq_travel_country', 'Szlovákia');
    update_post_meta($travel_id, '_melodiq_travel_city', 'Pozsony');
    update_post_meta($travel_id, '_melodiq_travel_date', '2026-12-12');
    update_post_meta($travel_id, '_melodiq_travel_departure', 'Budapest');
    update_post_meta($travel_id, '_melodiq_travel_deadline', '2026-11-30');
    update_post_meta($travel_id, '_melodiq_travel_capacity', 51);
    update_post_meta($travel_id, '_melodiq_travel_applicants', 31);
    update_post_meta($travel_id, '_melodiq_travel_status', 'jelentkezheto');
    update_post_meta($travel_id, '_melodiq_travel_short_description', 'Közös buszos indulás Budapestről a Whispers Of Ice eseményre. Kényelmes oda-vissza utazás, szervezett érkezés és a Melodiq Journey közösség hangulata egy úton.');
    update_post_meta($travel_id, '_melodiq_travel_gallery', '');
    update_post_meta($travel_id, '_melodiq_travel_shortcode', '');
    update_option('melodiq_default_travel_seeded', '1');
}
add_action('init', 'melodiq_ensure_default_travel', 42);

function melodiq_add_travel_meta_boxes() {
    add_meta_box(
        'melodiq_travel_details',
        __('Utazás adatok', 'melodiq-journey'),
        'melodiq_render_travel_details_meta_box',
        'travel',
        'normal',
        'high'
    );
}
add_action('add_meta_boxes', 'melodiq_add_travel_meta_boxes');

function melodiq_enqueue_travel_admin_assets($hook) {
    if (!in_array($hook, array('post.php', 'post-new.php'), true)) {
        return;
    }

    $screen = get_current_screen();

    if (!$screen || !in_array($screen->post_type, array('travel', 'travel_registration'), true)) {
        return;
    }

    $asset_path = get_template_directory() . '/assets/css/admin-event.css';

    wp_enqueue_style(
        'melodiq-admin-event',
        get_template_directory_uri() . '/assets/css/admin-event.css',
        array(),
        file_exists($asset_path) ? filemtime($asset_path) : wp_get_theme()->get('Version')
    );
}
add_action('admin_enqueue_scripts', 'melodiq_enqueue_travel_admin_assets');

function melodiq_travel_status_options() {
    return array(
        'jelentkezheto' => __('Jelentkezhető', 'melodiq-journey'),
        'hamarosan'    => __('Hamarosan', 'melodiq-journey'),
        'betelt'       => __('Betelt', 'melodiq-journey'),
        'lezart'       => __('Lezárt', 'melodiq-journey'),
    );
}

function melodiq_render_travel_details_meta_box($post) {
    wp_nonce_field('melodiq_save_travel_details', 'melodiq_travel_details_nonce');

    $meta = melodiq_travel_meta($post->ID);
    ?>
    <div class="melodiq-admin-event-panel">
        <section class="melodiq-admin-event-section">
            <div class="melodiq-admin-event-section-heading">
                <h3><?php esc_html_e('Esemény és útvonal', 'melodiq-journey'); ?></h3>
            </div>

            <div class="melodiq-admin-event-grid melodiq-admin-event-grid-2">
                <label class="melodiq-admin-event-field" for="melodiq_travel_event_name">
                    <span><?php esc_html_e('Esemény neve', 'melodiq-journey'); ?></span>
                    <input id="melodiq_travel_event_name" type="text" name="melodiq_travel_event_name" value="<?php echo esc_attr($meta['event_name']); ?>" placeholder="UNBROS - Whispers Of Ice">
                </label>

                <label class="melodiq-admin-event-field" for="melodiq_travel_country">
                    <span><?php esc_html_e('Ország', 'melodiq-journey'); ?></span>
                    <input id="melodiq_travel_country" type="text" name="melodiq_travel_country" value="<?php echo esc_attr($meta['country']); ?>" placeholder="Szlovákia">
                </label>

                <label class="melodiq-admin-event-field" for="melodiq_travel_city">
                    <span><?php esc_html_e('Város', 'melodiq-journey'); ?></span>
                    <input id="melodiq_travel_city" type="text" name="melodiq_travel_city" value="<?php echo esc_attr($meta['city']); ?>" placeholder="Pozsony">
                </label>

                <label class="melodiq-admin-event-field" for="melodiq_travel_departure">
                    <span><?php esc_html_e('Indulási hely', 'melodiq-journey'); ?></span>
                    <input id="melodiq_travel_departure" type="text" name="melodiq_travel_departure" value="<?php echo esc_attr($meta['departure']); ?>" placeholder="Budapest">
                </label>
            </div>
        </section>

        <section class="melodiq-admin-event-section">
            <div class="melodiq-admin-event-section-heading">
                <h3><?php esc_html_e('Időpont és jelentkezés', 'melodiq-journey'); ?></h3>
            </div>

            <div class="melodiq-admin-event-grid melodiq-admin-event-grid-2">
                <label class="melodiq-admin-event-field" for="melodiq_travel_date">
                    <span><?php esc_html_e('Dátum', 'melodiq-journey'); ?></span>
                    <input id="melodiq_travel_date" type="date" name="melodiq_travel_date" value="<?php echo esc_attr($meta['date']); ?>">
                </label>

                <label class="melodiq-admin-event-field" for="melodiq_travel_deadline">
                    <span><?php esc_html_e('Jelentkezési határidő', 'melodiq-journey'); ?></span>
                    <input id="melodiq_travel_deadline" type="date" name="melodiq_travel_deadline" value="<?php echo esc_attr($meta['deadline']); ?>">
                </label>

                <label class="melodiq-admin-event-field" for="melodiq_travel_capacity">
                    <span><?php esc_html_e('Férőhely', 'melodiq-journey'); ?></span>
                    <input id="melodiq_travel_capacity" type="number" min="0" name="melodiq_travel_capacity" value="<?php echo esc_attr($meta['capacity']); ?>" placeholder="51">
                </label>

                <label class="melodiq-admin-event-field" for="melodiq_travel_applicants">
                    <span><?php esc_html_e('Régi kézi jelentkezőszám', 'melodiq-journey'); ?></span>
                    <input id="melodiq_travel_applicants" type="number" min="0" name="melodiq_travel_applicants" value="<?php echo esc_attr($meta['applicants']); ?>" placeholder="31">
                    <small><?php esc_html_e('A publikus oldalon a létszám már automatikusan az utazási jelentkezésekből számolódik.', 'melodiq-journey'); ?></small>
                </label>

                <label class="melodiq-admin-event-field" for="melodiq_travel_status">
                    <span><?php esc_html_e('Státusz', 'melodiq-journey'); ?></span>
                    <select id="melodiq_travel_status" name="melodiq_travel_status">
                        <?php foreach (melodiq_travel_status_options() as $status_key => $status_label) : ?>
                            <option value="<?php echo esc_attr($status_key); ?>" <?php selected($meta['status'], $status_key); ?>><?php echo esc_html($status_label); ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
            </div>
        </section>

        <section class="melodiq-admin-event-section">
            <div class="melodiq-admin-event-section-heading">
                <h3><?php esc_html_e('Tartalom', 'melodiq-journey'); ?></h3>
                <p><?php esc_html_e('A borítókép a kiemelt kép. Az adatlap szekcióit az alábbi mezők vezérlik.', 'melodiq-journey'); ?></p>
            </div>

            <label class="melodiq-admin-event-field" for="melodiq_travel_short_description">
                <span><?php esc_html_e('Rövid leírás', 'melodiq-journey'); ?></span>
                <textarea id="melodiq_travel_short_description" name="melodiq_travel_short_description" rows="3"><?php echo esc_textarea($meta['short_description']); ?></textarea>
            </label>

            <div class="melodiq-admin-event-grid melodiq-admin-event-grid-2">
                <label class="melodiq-admin-event-field" for="melodiq_travel_overview_title">
                    <span><?php esc_html_e('Áttekintés cím', 'melodiq-journey'); ?></span>
                    <input id="melodiq_travel_overview_title" type="text" name="melodiq_travel_overview_title" value="<?php echo esc_attr($meta['overview_title']); ?>" placeholder="Közös buszos utazás">
                </label>
                <label class="melodiq-admin-event-field" for="melodiq_travel_program_title">
                    <span><?php esc_html_e('Program cím', 'melodiq-journey'); ?></span>
                    <input id="melodiq_travel_program_title" type="text" name="melodiq_travel_program_title" value="<?php echo esc_attr($meta['program_title']); ?>" placeholder="Program">
                </label>
                <label class="melodiq-admin-event-field" for="melodiq_travel_departure_title">
                    <span><?php esc_html_e('Indulás cím', 'melodiq-journey'); ?></span>
                    <input id="melodiq_travel_departure_title" type="text" name="melodiq_travel_departure_title" value="<?php echo esc_attr($meta['departure_title']); ?>" placeholder="Indulás Budapestről">
                </label>
                <label class="melodiq-admin-event-field" for="melodiq_travel_info_title">
                    <span><?php esc_html_e('Tudnivalók cím', 'melodiq-journey'); ?></span>
                    <input id="melodiq_travel_info_title" type="text" name="melodiq_travel_info_title" value="<?php echo esc_attr($meta['info_title']); ?>" placeholder="Fontos tudnivalók">
                </label>
            </div>

            <label class="melodiq-admin-event-field" for="melodiq_travel_overview_content">
                <span><?php esc_html_e('Áttekintés szöveg', 'melodiq-journey'); ?></span>
                <textarea id="melodiq_travel_overview_content" name="melodiq_travel_overview_content" rows="4"><?php echo esc_textarea($meta['overview_content']); ?></textarea>
            </label>

            <label class="melodiq-admin-event-field" for="melodiq_travel_program_content">
                <span><?php esc_html_e('Program szöveg', 'melodiq-journey'); ?></span>
                <textarea id="melodiq_travel_program_content" name="melodiq_travel_program_content" rows="5"><?php echo esc_textarea($meta['program_content']); ?></textarea>
            </label>

            <label class="melodiq-admin-event-field" for="melodiq_travel_departure_content">
                <span><?php esc_html_e('Indulás szöveg', 'melodiq-journey'); ?></span>
                <textarea id="melodiq_travel_departure_content" name="melodiq_travel_departure_content" rows="4"><?php echo esc_textarea($meta['departure_content']); ?></textarea>
            </label>

            <label class="melodiq-admin-event-field" for="melodiq_travel_info_content">
                <span><?php esc_html_e('Tudnivalók szöveg', 'melodiq-journey'); ?></span>
                <textarea id="melodiq_travel_info_content" name="melodiq_travel_info_content" rows="4"><?php echo esc_textarea($meta['info_content']); ?></textarea>
            </label>

            <label class="melodiq-admin-event-field" for="melodiq_travel_gallery">
                <span><?php esc_html_e('Galéria', 'melodiq-journey'); ?></span>
                <input id="melodiq_travel_gallery" type="text" name="melodiq_travel_gallery" value="<?php echo esc_attr($meta['gallery']); ?>" placeholder="Képek ID-i vesszővel elválasztva">
                <small><?php esc_html_e('Később médiaválasztóval bővíthető.', 'melodiq-journey'); ?></small>
            </label>

            <label class="melodiq-admin-event-field" for="melodiq_travel_shortcode">
                <span><?php esc_html_e('Jelentkezési shortcode', 'melodiq-journey'); ?></span>
                <input id="melodiq_travel_shortcode" type="text" name="melodiq_travel_shortcode" value="<?php echo esc_attr($meta['shortcode']); ?>" placeholder="[unbros_jelentkezes]">
            </label>
        </section>

        <section class="melodiq-admin-event-section">
            <div class="melodiq-admin-event-section-heading">
                <h3><?php esc_html_e('Fizetési email', 'melodiq-journey'); ?></h3>
                <p><?php esc_html_e('Ezekből a mezőkből készül az adminból egy gombnyomással kiküldhető fizetési értesítő.', 'melodiq-journey'); ?></p>
            </div>

            <div class="melodiq-admin-event-grid melodiq-admin-event-grid-2">
                <label class="melodiq-admin-event-field" for="melodiq_travel_payment_amount">
                    <span><?php esc_html_e('Fizetendő összeg', 'melodiq-journey'); ?></span>
                    <input id="melodiq_travel_payment_amount" type="text" name="melodiq_travel_payment_amount" value="<?php echo esc_attr($meta['payment_amount']); ?>" placeholder="19 990 Ft">
                </label>

                <label class="melodiq-admin-event-field" for="melodiq_travel_payment_deadline">
                    <span><?php esc_html_e('Fizetési határidő', 'melodiq-journey'); ?></span>
                    <input id="melodiq_travel_payment_deadline" type="date" name="melodiq_travel_payment_deadline" value="<?php echo esc_attr($meta['payment_deadline']); ?>">
                </label>
            </div>

            <label class="melodiq-admin-event-field" for="melodiq_travel_payment_instructions">
                <span><?php esc_html_e('Fizetési instrukciók', 'melodiq-journey'); ?></span>
                <textarea id="melodiq_travel_payment_instructions" name="melodiq_travel_payment_instructions" rows="4" placeholder="Bankszámlaszám, Revolut link, közlemény, további tudnivalók..."><?php echo esc_textarea($meta['payment_instructions']); ?></textarea>
            </label>

            <label class="melodiq-admin-event-field" for="melodiq_travel_payment_email_subject">
                <span><?php esc_html_e('Email tárgy', 'melodiq-journey'); ?></span>
                <input id="melodiq_travel_payment_email_subject" type="text" name="melodiq_travel_payment_email_subject" value="<?php echo esc_attr($meta['payment_email_subject']); ?>" placeholder="Fizetési információk - {utazas_neve}">
            </label>

            <label class="melodiq-admin-event-field" for="melodiq_travel_payment_email_body">
                <span><?php esc_html_e('Email szöveg', 'melodiq-journey'); ?></span>
                <textarea id="melodiq_travel_payment_email_body" name="melodiq_travel_payment_email_body" rows="8" placeholder="Szia {nev}!"><?php echo esc_textarea($meta['payment_email_body']); ?></textarea>
                <small><?php esc_html_e('Használható változók: {nev}, {utazas_neve}, {datum}, {indulas}, {helyszin}, {osszeg}, {fizetesi_hatarido}, {fizetesi_instrukciok}, {kozlemeny}', 'melodiq-journey'); ?></small>
            </label>
        </section>

        <?php if (function_exists('melodiq_partner_select_field')) : ?>
            <section class="melodiq-admin-event-section">
                <div class="melodiq-admin-event-section-heading">
                    <h3><?php esc_html_e('Partnerek', 'melodiq-journey'); ?></h3>
                    <p><?php esc_html_e('A kiválasztott partnerek automatikusan megjelennek az utazás oldalán is.', 'melodiq-journey'); ?></p>
                </div>

                <label class="melodiq-admin-event-field">
                    <span><?php esc_html_e('Kapcsolódó partnerek', 'melodiq-journey'); ?></span>
                    <?php melodiq_partner_select_field('melodiq_travel_partners', get_post_meta($post->ID, '_melodiq_travel_partners', true)); ?>
                </label>
            </section>
        <?php endif; ?>
    </div>
    <?php
}

function melodiq_save_travel_details($post_id) {
    if (!isset($_POST['melodiq_travel_details_nonce'])) {
        return;
    }

    if (!wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['melodiq_travel_details_nonce'])), 'melodiq_save_travel_details')) {
        return;
    }

    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }

    if (!current_user_can('edit_post', $post_id)) {
        return;
    }

    $fields = array(
        'event_name'        => 'sanitize_text_field',
        'country'           => 'sanitize_text_field',
        'city'              => 'sanitize_text_field',
        'departure'         => 'sanitize_text_field',
        'overview_title'    => 'sanitize_text_field',
        'program_title'     => 'sanitize_text_field',
        'departure_title'   => 'sanitize_text_field',
        'info_title'        => 'sanitize_text_field',
        'short_description' => 'sanitize_textarea_field',
        'overview_content'  => 'wp_kses_post',
        'program_content'   => 'wp_kses_post',
        'departure_content' => 'wp_kses_post',
        'info_content'      => 'wp_kses_post',
        'gallery'           => 'sanitize_text_field',
        'shortcode'         => 'sanitize_text_field',
        'payment_amount'    => 'sanitize_text_field',
        'payment_instructions' => 'sanitize_textarea_field',
        'payment_email_subject' => 'sanitize_text_field',
        'payment_email_body' => 'sanitize_textarea_field',
    );

    foreach ($fields as $field => $callback) {
        $value = isset($_POST['melodiq_travel_' . $field]) ? wp_unslash($_POST['melodiq_travel_' . $field]) : '';
        update_post_meta($post_id, '_melodiq_travel_' . $field, call_user_func($callback, $value));
    }

    update_post_meta($post_id, '_melodiq_travel_date', melodiq_validate_travel_date(isset($_POST['melodiq_travel_date']) ? sanitize_text_field(wp_unslash($_POST['melodiq_travel_date'])) : ''));
    update_post_meta($post_id, '_melodiq_travel_deadline', melodiq_validate_travel_date(isset($_POST['melodiq_travel_deadline']) ? sanitize_text_field(wp_unslash($_POST['melodiq_travel_deadline'])) : ''));
    update_post_meta($post_id, '_melodiq_travel_payment_deadline', melodiq_validate_travel_date(isset($_POST['melodiq_travel_payment_deadline']) ? sanitize_text_field(wp_unslash($_POST['melodiq_travel_payment_deadline'])) : ''));
    update_post_meta($post_id, '_melodiq_travel_capacity', isset($_POST['melodiq_travel_capacity']) ? max(0, absint($_POST['melodiq_travel_capacity'])) : 0);
    update_post_meta($post_id, '_melodiq_travel_applicants', isset($_POST['melodiq_travel_applicants']) ? max(0, absint($_POST['melodiq_travel_applicants'])) : 0);

    $status = isset($_POST['melodiq_travel_status']) ? sanitize_key(wp_unslash($_POST['melodiq_travel_status'])) : 'hamarosan';
    $status_options = melodiq_travel_status_options();
    update_post_meta($post_id, '_melodiq_travel_status', isset($status_options[$status]) ? $status : 'hamarosan');

    $partner_ids = isset($_POST['melodiq_travel_partners']) ? array_values(array_filter(array_map('absint', (array) wp_unslash($_POST['melodiq_travel_partners'])))) : array();
    update_post_meta($post_id, '_melodiq_travel_partners', $partner_ids);
}
add_action('save_post_travel', 'melodiq_save_travel_details');

function melodiq_validate_travel_date($date) {
    return $date && preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) ? $date : '';
}

function melodiq_travel_meta($post_id = null) {
    $post_id = $post_id ? $post_id : get_the_ID();
    $status = get_post_meta($post_id, '_melodiq_travel_status', true);
    if ('folyamatban' === $status) {
        $status = 'hamarosan';
        update_post_meta($post_id, '_melodiq_travel_status', $status);
    }

    $capacity = (int) get_post_meta($post_id, '_melodiq_travel_capacity', true);
    $registration_stats = function_exists('melodiq_travel_registration_stats') ? melodiq_travel_registration_stats($post_id) : array(
        'confirmed' => (int) get_post_meta($post_id, '_melodiq_travel_applicants', true),
        'waitlist'  => 0,
    );

    if ($capacity > 0 && $registration_stats['confirmed'] >= $capacity && in_array($status, array('', 'jelentkezheto', 'betelt'), true)) {
        $status = 'betelt';
        if (get_post_meta($post_id, '_melodiq_travel_status', true) !== $status) {
            update_post_meta($post_id, '_melodiq_travel_status', $status);
        }
    } elseif ('betelt' === $status && $capacity > 0 && $registration_stats['confirmed'] < $capacity) {
        $status = 'jelentkezheto';
        update_post_meta($post_id, '_melodiq_travel_status', $status);
    }

    return array(
        'event_name'        => get_post_meta($post_id, '_melodiq_travel_event_name', true),
        'country'           => get_post_meta($post_id, '_melodiq_travel_country', true),
        'city'              => get_post_meta($post_id, '_melodiq_travel_city', true),
        'date'              => get_post_meta($post_id, '_melodiq_travel_date', true),
        'departure'         => get_post_meta($post_id, '_melodiq_travel_departure', true),
        'deadline'          => get_post_meta($post_id, '_melodiq_travel_deadline', true),
        'capacity'          => $capacity,
        'applicants'        => (int) $registration_stats['confirmed'],
        'waitlist'          => (int) $registration_stats['waitlist'],
        'available'         => max(0, $capacity - (int) $registration_stats['confirmed']),
        'status'            => $status ? $status : 'hamarosan',
        'short_description' => get_post_meta($post_id, '_melodiq_travel_short_description', true),
        'overview_title'    => get_post_meta($post_id, '_melodiq_travel_overview_title', true),
        'overview_content'  => get_post_meta($post_id, '_melodiq_travel_overview_content', true),
        'program_title'     => get_post_meta($post_id, '_melodiq_travel_program_title', true),
        'program_content'   => get_post_meta($post_id, '_melodiq_travel_program_content', true),
        'departure_title'   => get_post_meta($post_id, '_melodiq_travel_departure_title', true),
        'departure_content' => get_post_meta($post_id, '_melodiq_travel_departure_content', true),
        'info_title'        => get_post_meta($post_id, '_melodiq_travel_info_title', true),
        'info_content'      => get_post_meta($post_id, '_melodiq_travel_info_content', true),
        'gallery'           => get_post_meta($post_id, '_melodiq_travel_gallery', true),
        'shortcode'         => get_post_meta($post_id, '_melodiq_travel_shortcode', true),
        'payment_amount'    => get_post_meta($post_id, '_melodiq_travel_payment_amount', true),
        'payment_deadline'  => get_post_meta($post_id, '_melodiq_travel_payment_deadline', true),
        'payment_instructions' => get_post_meta($post_id, '_melodiq_travel_payment_instructions', true),
        'payment_email_subject' => get_post_meta($post_id, '_melodiq_travel_payment_email_subject', true),
        'payment_email_body' => get_post_meta($post_id, '_melodiq_travel_payment_email_body', true),
    );
}

function melodiq_travel_status_label($status) {
    $options = melodiq_travel_status_options();

    return isset($options[$status]) ? $options[$status] : $options['hamarosan'];
}

function melodiq_travel_date_label($date) {
    if (!$date) {
        return __('Dátum hamarosan', 'melodiq-journey');
    }

    $timestamp = strtotime($date . ' 12:00:00');

    return wp_date('Y. F j.', $timestamp);
}

function melodiq_travel_location_label($meta) {
    $parts = array_filter(array($meta['city'], $meta['country']));

    return $parts ? implode(', ', $parts) : __('Helyszín hamarosan', 'melodiq-journey');
}

function melodiq_travel_capacity_percent($meta) {
    if (empty($meta['capacity'])) {
        return 0;
    }

    return min(100, round(($meta['applicants'] / $meta['capacity']) * 100));
}

function melodiq_travel_query($args = array()) {
    $defaults = array(
        'post_type'      => 'travel',
        'posts_per_page' => -1,
        'post_status'    => 'publish',
        'meta_key'       => '_melodiq_travel_date',
        'orderby'        => 'meta_value',
        'order'          => 'ASC',
    );

    return new WP_Query(wp_parse_args($args, $defaults));
}

function melodiq_travel_stats() {
    $query = melodiq_travel_query();
    $active = 0;
    $travelers = 0;
    $countries = array();

    while ($query->have_posts()) {
        $query->the_post();
        $meta = melodiq_travel_meta();

        if (in_array($meta['status'], array('jelentkezheto', 'hamarosan'), true)) {
            $active++;
        }

        $travelers += (int) $meta['applicants'];

        if ($meta['country']) {
            $countries[] = $meta['country'];
        }
    }

    wp_reset_postdata();

    return array(
        'active'    => $active,
        'travelers' => $travelers,
        'countries' => count(array_unique($countries)),
    );
}

function melodiq_travel_demo_items() {
    return array(
        array(
            'title'             => "UNBROS\nWhispers Of Ice\nPozsony",
            'event_name'        => 'UNBROS - Whispers Of Ice',
            'country'           => 'Szlovákia',
            'city'              => 'Pozsony',
            'date'              => '2026-12-12',
            'departure'         => 'Budapest',
            'deadline'          => '2026-11-30',
            'capacity'          => 51,
            'applicants'        => 31,
            'status'            => 'jelentkezheto',
            'short_description' => 'Közös buszos indulás Budapestről a Whispers Of Ice eseményre. Kényelmes oda-vissza utazás, szervezett érkezés és a Melodiq Journey közösség hangulata egy úton.',
            'permalink'         => home_url('/utazas/unbros-whispers-of-ice/'),
        ),
    );
}

function melodiq_travel_admin_columns($columns) {
    $columns['travel_date'] = __('Dátum', 'melodiq-journey');
    $columns['travel_city'] = __('Város', 'melodiq-journey');
    $columns['travel_status'] = __('Státusz', 'melodiq-journey');
    $columns['travel_capacity'] = __('Létszám', 'melodiq-journey');

    return $columns;
}
add_filter('manage_travel_posts_columns', 'melodiq_travel_admin_columns');

function melodiq_travel_admin_column_content($column, $post_id) {
    $meta = melodiq_travel_meta($post_id);

    if ('travel_date' === $column) {
        echo esc_html(melodiq_travel_date_label($meta['date']));
    }

    if ('travel_city' === $column) {
        echo esc_html(melodiq_travel_location_label($meta));
    }

    if ('travel_status' === $column) {
        echo esc_html(melodiq_travel_status_label($meta['status']));
    }

    if ('travel_capacity' === $column) {
        echo esc_html($meta['applicants'] . ' / ' . $meta['capacity']);
    }
}
add_action('manage_travel_posts_custom_column', 'melodiq_travel_admin_column_content', 10, 2);
