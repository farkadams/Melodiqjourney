<?php

/**
 * Travel registrations, CSV import, frontend form and emails.
 */

function melodiq_register_travel_registration_post_type() {
    register_post_type('travel_registration', array(
        'labels' => array(
            'name'          => __('Utazási jelentkezések', 'melodiq-journey'),
            'singular_name' => __('Utazási jelentkezés', 'melodiq-journey'),
            'menu_name'     => __('Jelentkezések', 'melodiq-journey'),
            'edit_item'     => __('Jelentkezés szerkesztése', 'melodiq-journey'),
            'search_items'  => __('Jelentkezések keresése', 'melodiq-journey'),
        ),
        'public'              => false,
        'show_ui'             => true,
        'publicly_queryable'  => false,
        'show_in_menu'        => 'edit.php?post_type=travel',
        'capability_type'     => 'post',
        'supports'            => array('title'),
        'exclude_from_search' => true,
        'menu_icon'           => 'dashicons-tickets-alt',
    ));
}
add_action('init', 'melodiq_register_travel_registration_post_type');

/**
 * Always force travel_registration post type to be private, regardless of what is set in the Publish box.
 */
function melodiq_force_private_travel_registration($data, $postarr) {
    if (($data['post_type'] ?? '') === 'travel_registration') {
        $data['post_status'] = 'private';
    }
    return $data;
}
add_filter('wp_insert_post_data', 'melodiq_force_private_travel_registration', 10, 2);

function melodiq_travel_registration_status_options() {
    return array(
        'jelentkezett' => __('Jelentkezett', 'melodiq-journey'),
        'varolista'    => __('Várólista', 'melodiq-journey'),
        'torolve'      => __('Törölve', 'melodiq-journey'),
    );
}

function melodiq_travel_registration_payment_status_options() {
    return array(
        'nem_fizetett' => __('Nem fizetett', 'melodiq-journey'),
        'fuggoben'     => __('Függőben', 'melodiq-journey'),
        'fizetett'     => __('Fizetett', 'melodiq-journey'),
    );
}

function melodiq_travel_registration_source_options() {
    return array(
        'wordpress_form' => __('WordPress form', 'melodiq-journey'),
        'google_sheets'  => __('Google Sheets import', 'melodiq-journey'),
        'manual'         => __('Kézi felvitel', 'melodiq-journey'),
    );
}

function melodiq_travel_registration_status_label($status) {
    $options = melodiq_travel_registration_status_options();

    return isset($options[$status]) ? $options[$status] : $options['jelentkezett'];
}

function melodiq_travel_registration_payment_status_label($status) {
    $options = melodiq_travel_registration_payment_status_options();

    return isset($options[$status]) ? $options[$status] : $options['nem_fizetett'];
}

function melodiq_travel_registration_source_label($source) {
    $options = melodiq_travel_registration_source_options();

    return isset($options[$source]) ? $options[$source] : $options['wordpress_form'];
}

function melodiq_travel_registration_normalize_status_key($value, $default = 'jelentkezett') {
    $value = strtolower(remove_accents((string) $value));
    $value = preg_replace('/[^a-z0-9]+/', '_', $value);
    $value = trim($value, '_');
    $aliases = array(
        'varolistas' => 'varolista',
        'varolista'  => 'varolista',
        'torolt'     => 'torolve',
        'torolve'    => 'torolve',
    );
    $value = isset($aliases[$value]) ? $aliases[$value] : $value;
    $options = melodiq_travel_registration_status_options();

    return isset($options[$value]) ? $value : $default;
}

function melodiq_travel_registration_normalize_payment_status_key($value, $default = 'nem_fizetett') {
    $value = strtolower(remove_accents((string) $value));
    $value = preg_replace('/[^a-z0-9]+/', '_', $value);
    $value = trim($value, '_');
    $aliases = array(
        'nemfizetett'  => 'nem_fizetett',
        'nem_fizetett' => 'nem_fizetett',
        'fuggoben'     => 'fuggoben',
        'fizetve'      => 'fizetett',
    );
    $value = isset($aliases[$value]) ? $aliases[$value] : $value;
    $options = melodiq_travel_registration_payment_status_options();

    return isset($options[$value]) ? $value : $default;
}

function melodiq_travel_registration_meta($post_id) {
    return array(
        'travel_id'      => absint(get_post_meta($post_id, '_melodiq_travel_registration_travel_id', true)),
        'event_id'       => absint(get_post_meta($post_id, '_melodiq_travel_registration_event_id', true)),
        'name'           => get_post_meta($post_id, '_melodiq_travel_registration_name', true),
        'email'          => get_post_meta($post_id, '_melodiq_travel_registration_email', true),
        'phone'          => get_post_meta($post_id, '_melodiq_travel_registration_phone', true),
        'passengers'     => max(1, (int) get_post_meta($post_id, '_melodiq_travel_registration_passengers', true)),
        'message'        => get_post_meta($post_id, '_melodiq_travel_registration_message', true),
        'registered_at'  => get_post_meta($post_id, '_melodiq_travel_registration_registered_at', true),
        'status'         => get_post_meta($post_id, '_melodiq_travel_registration_status', true) ?: 'jelentkezett',
        'payment_status' => get_post_meta($post_id, '_melodiq_travel_registration_payment_status', true) ?: 'nem_fizetett',
        'payment_email_sent_at' => get_post_meta($post_id, '_melodiq_travel_registration_payment_email_sent_at', true),
        'source'         => get_post_meta($post_id, '_melodiq_travel_registration_source', true) ?: 'wordpress_form',
        'admin_note'     => get_post_meta($post_id, '_melodiq_travel_registration_admin_note', true),
    );
}

function melodiq_travel_registration_email_log($registration_id) {
    $log = get_post_meta($registration_id, '_melodiq_travel_registration_email_log', true);

    return is_array($log) ? $log : array();
}

function melodiq_travel_registration_add_email_log($registration_id, $type, $to, $subject, $status = 'sent') {
    $log = melodiq_travel_registration_email_log($registration_id);
    $log[] = array(
        'type'    => sanitize_key($type),
        'to'      => sanitize_email($to),
        'subject' => sanitize_text_field($subject),
        'status'  => sanitize_key($status),
        'date'    => current_time('mysql'),
    );

    update_post_meta($registration_id, '_melodiq_travel_registration_email_log', $log);
}

function melodiq_travel_registration_email_log_type_label($type) {
    $labels = array(
        'confirmation'       => __('Visszaigazolás', 'melodiq-journey'),
        'admin_notification' => __('Admin értesítés', 'melodiq-journey'),
        'payment'            => __('Fizetési email', 'melodiq-journey'),
    );

    return isset($labels[$type]) ? $labels[$type] : $type;
}

function melodiq_travel_registration_email_log_status_label($status) {
    $labels = array(
        'sent'   => __('Elküldve', 'melodiq-journey'),
        'failed' => __('Sikertelen', 'melodiq-journey'),
    );

    return isset($labels[$status]) ? $labels[$status] : $status;
}

function melodiq_travel_registration_find_duplicate($travel_id, $email, $name = '') {
    $email = strtolower(sanitize_email($email));
    $meta_query = array(
        'relation' => 'AND',
        array(
            'key'   => '_melodiq_travel_registration_travel_id',
            'value' => absint($travel_id),
        ),
        array(
            'key'   => '_melodiq_travel_registration_email',
            'value' => $email,
        ),
    );

    if ('' !== $name) {
        $meta_query[] = array(
            'key'   => '_melodiq_travel_registration_name',
            'value' => sanitize_text_field($name),
        );
    }

    $existing = get_posts(array(
        'post_type'      => 'travel_registration',
        'post_status'    => 'private',
        'posts_per_page' => 1,
        'fields'         => 'ids',
        'meta_query'     => $meta_query,
    ));

    return $existing ? (int) $existing[0] : 0;
}

function melodiq_travel_registration_stats($travel_id) {
    $ids = get_posts(array(
        'post_type'      => 'travel_registration',
        'post_status'    => 'private',
        'posts_per_page' => -1,
        'fields'         => 'ids',
        'meta_query'     => array(
            array(
                'key'   => '_melodiq_travel_registration_travel_id',
                'value' => absint($travel_id),
            ),
        ),
    ));
    $confirmed = 0;
    $waitlist = 0;
    $cancelled = 0;

    foreach ($ids as $id) {
        $status = get_post_meta($id, '_melodiq_travel_registration_status', true);
        $passengers = max(1, (int) get_post_meta($id, '_melodiq_travel_registration_passengers', true));

        if ('jelentkezett' === $status) {
            $confirmed += $passengers;
        } elseif ('varolista' === $status) {
            $waitlist += $passengers;
        } elseif ('torolve' === $status) {
            $cancelled += $passengers;
        }
    }

    return array(
        'confirmed' => $confirmed,
        'waitlist'  => $waitlist,
        'cancelled' => $cancelled,
    );
}

function melodiq_travel_registration_available_seats($travel_id) {
    $capacity = (int) get_post_meta($travel_id, '_melodiq_travel_capacity', true);
    $stats = melodiq_travel_registration_stats($travel_id);

    return max(0, $capacity - (int) $stats['confirmed']);
}

function melodiq_travel_registration_create($data) {
    $travel_id = absint($data['travel_id']);
    $event_id = !empty($data['event_id']) ? absint($data['event_id']) : 0;
    $name = sanitize_text_field($data['name']);
    $email = strtolower(sanitize_email($data['email']));
    $phone = sanitize_text_field($data['phone']);
    $passengers = max(1, absint($data['passengers']));
    $message = isset($data['message']) ? sanitize_textarea_field($data['message']) : '';
    $source = isset($data['source']) ? sanitize_key($data['source']) : 'wordpress_form';
    $status = isset($data['status']) ? sanitize_key($data['status']) : 'jelentkezett';
    $payment_status = isset($data['payment_status']) ? sanitize_key($data['payment_status']) : 'nem_fizetett';
    $admin_note = isset($data['admin_note']) ? sanitize_textarea_field($data['admin_note']) : '';
    $registered_at = isset($data['registered_at']) && $data['registered_at'] ? melodiq_travel_registration_normalize_datetime($data['registered_at']) : current_time('mysql');
    $status_options = melodiq_travel_registration_status_options();
    $payment_options = melodiq_travel_registration_payment_status_options();
    $source_options = melodiq_travel_registration_source_options();
    $allow_missing_phone = !empty($data['allow_missing_phone']);

    if (!$travel_id || 'travel' !== get_post_type($travel_id)) {
        return new WP_Error('invalid_travel', __('Érvénytelen utazás.', 'melodiq-journey'));
    }

    if (!$name || !$email || !is_email($email) || (!$phone && !$allow_missing_phone)) {
        return new WP_Error('invalid_fields', __('Hiányzó vagy hibás jelentkezési adatok.', 'melodiq-journey'));
    }

    $allow_shared_email = !empty($data['allow_shared_email']);
    $duplicate_id = $allow_shared_email ? melodiq_travel_registration_find_duplicate($travel_id, $email, $name) : melodiq_travel_registration_find_duplicate($travel_id, $email);

    if ($duplicate_id) {
        return new WP_Error('duplicate', __('Ezzel az email címmel már van jelentkezés erre az utazásra.', 'melodiq-journey'));
    }

    $status = isset($status_options[$status]) ? $status : 'jelentkezett';
    $payment_status = isset($payment_options[$payment_status]) ? $payment_status : 'nem_fizetett';

    if (!isset($source_options[$source])) {
        $source = 'wordpress_form';
    }

    $post_id = wp_insert_post(array(
        'post_type'   => 'travel_registration',
        'post_status' => 'private',
        'post_title'  => $name . ' - ' . get_the_title($travel_id),
        'post_date'   => $registered_at,
    ));

    if (is_wp_error($post_id) || !$post_id) {
        return new WP_Error('insert_failed', __('Nem sikerült menteni a jelentkezést.', 'melodiq-journey'));
    }

    update_post_meta($post_id, '_melodiq_travel_registration_travel_id', $travel_id);
    update_post_meta($post_id, '_melodiq_travel_registration_event_id', $event_id);
    update_post_meta($post_id, '_melodiq_travel_registration_name', $name);
    update_post_meta($post_id, '_melodiq_travel_registration_email', $email);
    update_post_meta($post_id, '_melodiq_travel_registration_phone', $phone);
    update_post_meta($post_id, '_melodiq_travel_registration_passengers', $passengers);
    update_post_meta($post_id, '_melodiq_travel_registration_message', $message);
    update_post_meta($post_id, '_melodiq_travel_registration_registered_at', $registered_at);
    update_post_meta($post_id, '_melodiq_travel_registration_status', $status);
    update_post_meta($post_id, '_melodiq_travel_registration_payment_status', $payment_status);
    update_post_meta($post_id, '_melodiq_travel_registration_source', $source);
    update_post_meta($post_id, '_melodiq_travel_registration_admin_note', $admin_note);

    return $post_id;
}

function melodiq_travel_registration_normalize_datetime($value) {
    $value = melodiq_travel_registration_normalize_csv_value($value);

    if (!$value) {
        return current_time('mysql');
    }

    if (preg_match('/^\d{4}-\d{2}-\d{2}/', $value)) {
        return sanitize_text_field($value);
    }

    if (preg_match('/^(\d{4})\.(\d{2})\.(\d{2})(.*)$/', $value, $matches)) {
        $time = trim($matches[4]);
        return sanitize_text_field($matches[1] . '-' . $matches[2] . '-' . $matches[3] . ($time ? ' ' . $time : ''));
    }

    if (preg_match('/^(\d{2})\.(\d{2})\.(\d{4})(.*)$/', $value, $matches)) {
        $time = trim($matches[4]);
        return sanitize_text_field($matches[3] . '-' . $matches[2] . '-' . $matches[1] . ($time ? ' ' . $time : ''));
    }

    $timestamp = strtotime($value);

    return $timestamp ? gmdate('Y-m-d H:i:s', $timestamp) : current_time('mysql');
}

function melodiq_travel_registration_redirect($travel_id, $status) {
    wp_safe_redirect(add_query_arg('travel_registration', rawurlencode($status), get_permalink($travel_id)) . '#jelentkezes');
    exit;
}

function melodiq_handle_travel_registration_form() {
    if (!isset($_POST['melodiq_travel_registration_action']) || 'submit' !== sanitize_key(wp_unslash($_POST['melodiq_travel_registration_action']))) {
        return;
    }

    $travel_id = isset($_POST['melodiq_travel_id']) ? absint($_POST['melodiq_travel_id']) : 0;

    if (!$travel_id || 'travel' !== get_post_type($travel_id)) {
        wp_safe_redirect(home_url('/utazas/'));
        exit;
    }

    if (!isset($_POST['melodiq_travel_registration_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['melodiq_travel_registration_nonce'])), 'melodiq_travel_registration_' . $travel_id)) {
        melodiq_travel_registration_redirect($travel_id, 'error');
    }

    if (!empty($_POST['melodiq_travel_company'])) {
        melodiq_travel_registration_redirect($travel_id, 'error');
    }

    $travel_meta = melodiq_travel_meta($travel_id);

    if ('betelt' === $travel_meta['status']) {
        melodiq_travel_registration_redirect($travel_id, 'full');
    }

    if ('jelentkezheto' !== $travel_meta['status']) {
        melodiq_travel_registration_redirect($travel_id, 'not_open');
    }

    if (empty($_POST['melodiq_travel_privacy'])) {
        melodiq_travel_registration_redirect($travel_id, 'privacy');
    }

    $email = isset($_POST['melodiq_travel_email']) ? sanitize_email(wp_unslash($_POST['melodiq_travel_email'])) : '';

    if (!$email || !is_email($email)) {
        melodiq_travel_registration_redirect($travel_id, 'invalid_email');
    }

    $passengers = 1;
    if (melodiq_travel_registration_available_seats($travel_id) < $passengers) {
        melodiq_travel_registration_redirect($travel_id, 'full');
    }

    $registration_id = melodiq_travel_registration_create(array(
        'travel_id'      => $travel_id,
        'name'           => isset($_POST['melodiq_travel_name']) ? wp_unslash($_POST['melodiq_travel_name']) : '',
        'email'          => $email,
        'phone'          => isset($_POST['melodiq_travel_phone']) ? wp_unslash($_POST['melodiq_travel_phone']) : '',
        'passengers'     => $passengers,
        'message'        => isset($_POST['melodiq_travel_message']) ? wp_unslash($_POST['melodiq_travel_message']) : '',
        'status'         => 'jelentkezett',
        'payment_status' => 'nem_fizetett',
        'source'         => 'wordpress_form',
    ));

    if (is_wp_error($registration_id)) {
        melodiq_travel_registration_redirect($travel_id, 'duplicate' === $registration_id->get_error_code() ? 'duplicate' : 'error');
    }

    melodiq_travel_registration_send_emails($registration_id);
    melodiq_travel_registration_redirect($travel_id, 'success');
}
add_action('template_redirect', 'melodiq_handle_travel_registration_form');

function melodiq_add_travel_registration_meta_boxes() {
    add_meta_box(
        'melodiq_travel_registration_details',
        __('Jelentkezés adatai', 'melodiq-journey'),
        'melodiq_render_travel_registration_meta_box',
        'travel_registration',
        'normal',
        'high'
    );
}
add_action('add_meta_boxes', 'melodiq_add_travel_registration_meta_boxes');

function melodiq_render_travel_registration_meta_box($post) {
    wp_nonce_field('melodiq_save_travel_registration_details', 'melodiq_travel_registration_details_nonce');
    $meta = melodiq_travel_registration_meta($post->ID);
    $email_log = melodiq_travel_registration_email_log($post->ID);
    ?>
    <div class="melodiq-admin-event-panel">
        <section class="melodiq-admin-event-section">
            <div class="melodiq-admin-event-section-heading">
                <h3><?php esc_html_e('Jelentkező adatai', 'melodiq-journey'); ?></h3>
            </div>
            <div class="melodiq-admin-event-grid melodiq-admin-event-grid-2">
                <label class="melodiq-admin-event-field">
                    <span><?php esc_html_e('Utazás', 'melodiq-journey'); ?></span>
                    <select name="melodiq_registration_travel_id">
                        <?php foreach (melodiq_travel_options('travel') as $travel) : ?>
                            <option value="<?php echo esc_attr($travel->ID); ?>" <?php selected($meta['travel_id'], $travel->ID); ?>><?php echo esc_html(get_the_title($travel)); ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label class="melodiq-admin-event-field">
                    <span><?php esc_html_e('Kapcsolódó event', 'melodiq-journey'); ?></span>
                    <select name="melodiq_registration_event_id">
                        <option value="0"><?php esc_html_e('Nincs kapcsolódó event', 'melodiq-journey'); ?></option>
                        <?php foreach (melodiq_travel_options('event') as $event) : ?>
                            <option value="<?php echo esc_attr($event->ID); ?>" <?php selected($meta['event_id'], $event->ID); ?>><?php echo esc_html(get_the_title($event)); ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label class="melodiq-admin-event-field"><span><?php esc_html_e('Név', 'melodiq-journey'); ?></span><input type="text" name="melodiq_registration_name" value="<?php echo esc_attr($meta['name']); ?>"></label>
                <label class="melodiq-admin-event-field"><span><?php esc_html_e('Email', 'melodiq-journey'); ?></span><input type="email" name="melodiq_registration_email" value="<?php echo esc_attr($meta['email']); ?>"></label>
                <label class="melodiq-admin-event-field"><span><?php esc_html_e('Telefonszám', 'melodiq-journey'); ?></span><input type="text" name="melodiq_registration_phone" value="<?php echo esc_attr($meta['phone']); ?>"></label>
            </div>
            <div class="melodiq-admin-event-section-heading">
                <h3><?php esc_html_e('Fizetés', 'melodiq-journey'); ?></h3>
            </div>
            <div class="melodiq-admin-event-grid melodiq-admin-event-grid-2">
                <label class="melodiq-admin-event-field">
                    <span><?php esc_html_e('Fizetési státusz', 'melodiq-journey'); ?></span>
                    <select name="melodiq_registration_payment_status">
                        <?php foreach (melodiq_travel_registration_payment_status_options() as $payment_key => $payment_label) : ?>
                            <option value="<?php echo esc_attr($payment_key); ?>" <?php selected($meta['payment_status'], $payment_key); ?>><?php echo esc_html($payment_label); ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label class="melodiq-admin-event-field">
                    <span><?php esc_html_e('Fizetési email kiküldve', 'melodiq-journey'); ?></span>
                    <input type="text" value="<?php echo esc_attr($meta['payment_email_sent_at'] ? mysql2date('Y.m.d. H:i', $meta['payment_email_sent_at']) : __('Még nem ment ki', 'melodiq-journey')); ?>" readonly>
                </label>
                <label class="melodiq-admin-event-field">
                    <span><?php esc_html_e('Forrás', 'melodiq-journey'); ?></span>
                    <select name="melodiq_registration_source">
                        <?php foreach (melodiq_travel_registration_source_options() as $source_key => $source_label) : ?>
                            <option value="<?php echo esc_attr($source_key); ?>" <?php selected($meta['source'], $source_key); ?>><?php echo esc_html($source_label); ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
            </div>
        </section>
        <section class="melodiq-admin-event-section">
            <div class="melodiq-admin-event-section-heading">
                <h3><?php esc_html_e('Megjegyzések', 'melodiq-journey'); ?></h3>
            </div>
            <label class="melodiq-admin-event-field"><span><?php esc_html_e('Megjegyzés', 'melodiq-journey'); ?></span><textarea name="melodiq_registration_message" rows="3"><?php echo esc_textarea($meta['message']); ?></textarea></label>
            <label class="melodiq-admin-event-field"><span><?php esc_html_e('Belső admin megjegyzés', 'melodiq-journey'); ?></span><textarea name="melodiq_registration_admin_note" rows="3"><?php echo esc_textarea($meta['admin_note']); ?></textarea></label>
            <section class="melodiq-admin-event-section">
                <div class="melodiq-admin-event-section-heading">
                    <h3><?php esc_html_e('Email napló', 'melodiq-journey'); ?></h3>
                </div>
                <?php if ($email_log) : ?>
                    <table class="widefat striped">
                        <thead>
                            <tr>
                                <th><?php esc_html_e('Dátum', 'melodiq-journey'); ?></th>
                                <th><?php esc_html_e('Típus', 'melodiq-journey'); ?></th>
                                <th><?php esc_html_e('Címzett', 'melodiq-journey'); ?></th>
                                <th><?php esc_html_e('Tárgy', 'melodiq-journey'); ?></th>
                                <th><?php esc_html_e('Státusz', 'melodiq-journey'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach (array_reverse($email_log) as $log_item) : ?>
                                <tr>
                                    <td><?php echo esc_html(!empty($log_item['date']) ? mysql2date('Y.m.d. H:i', $log_item['date']) : '-'); ?></td>
                                    <td><?php echo esc_html(melodiq_travel_registration_email_log_type_label(isset($log_item['type']) ? $log_item['type'] : '')); ?></td>
                                    <td><?php echo esc_html(isset($log_item['to']) ? $log_item['to'] : ''); ?></td>
                                    <td><?php echo esc_html(isset($log_item['subject']) ? $log_item['subject'] : ''); ?></td>
                                    <td><?php echo esc_html(melodiq_travel_registration_email_log_status_label(isset($log_item['status']) ? $log_item['status'] : '')); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else : ?>
                    <p><?php esc_html_e('Ehhez a jelentkezéshez még nincs email napló.', 'melodiq-journey'); ?></p>
                <?php endif; ?>
            </section>
        </section>
    </div>
    <?php
}

function melodiq_save_travel_registration_details($post_id) {
    if (!isset($_POST['melodiq_travel_registration_details_nonce'])) {
        return;
    }

    if (!wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['melodiq_travel_registration_details_nonce'])), 'melodiq_save_travel_registration_details')) {
        return;
    }

    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }

    if (!current_user_can('edit_post', $post_id)) {
        return;
    }

    update_post_meta($post_id, '_melodiq_travel_registration_travel_id', isset($_POST['melodiq_registration_travel_id']) ? absint($_POST['melodiq_registration_travel_id']) : 0);
    update_post_meta($post_id, '_melodiq_travel_registration_event_id', isset($_POST['melodiq_registration_event_id']) ? absint($_POST['melodiq_registration_event_id']) : 0);
    update_post_meta($post_id, '_melodiq_travel_registration_name', isset($_POST['melodiq_registration_name']) ? sanitize_text_field(wp_unslash($_POST['melodiq_registration_name'])) : '');
    update_post_meta($post_id, '_melodiq_travel_registration_email', isset($_POST['melodiq_registration_email']) ? sanitize_email(wp_unslash($_POST['melodiq_registration_email'])) : '');
    update_post_meta($post_id, '_melodiq_travel_registration_phone', isset($_POST['melodiq_registration_phone']) ? sanitize_text_field(wp_unslash($_POST['melodiq_registration_phone'])) : '');
    update_post_meta($post_id, '_melodiq_travel_registration_passengers', 1);
    update_post_meta($post_id, '_melodiq_travel_registration_status', 'jelentkezett');
    update_post_meta($post_id, '_melodiq_travel_registration_payment_status', isset($_POST['melodiq_registration_payment_status']) ? melodiq_travel_registration_normalize_payment_status_key(wp_unslash($_POST['melodiq_registration_payment_status'])) : 'nem_fizetett');
    $source_options = melodiq_travel_registration_source_options();
    $source = isset($_POST['melodiq_registration_source']) ? sanitize_key(wp_unslash($_POST['melodiq_registration_source'])) : 'wordpress_form';
    update_post_meta($post_id, '_melodiq_travel_registration_source', isset($source_options[$source]) ? $source : 'wordpress_form');
    update_post_meta($post_id, '_melodiq_travel_registration_message', isset($_POST['melodiq_registration_message']) ? sanitize_textarea_field(wp_unslash($_POST['melodiq_registration_message'])) : '');
    update_post_meta($post_id, '_melodiq_travel_registration_admin_note', isset($_POST['melodiq_registration_admin_note']) ? sanitize_textarea_field(wp_unslash($_POST['melodiq_registration_admin_note'])) : '');
}
add_action('save_post_travel_registration', 'melodiq_save_travel_registration_details');

function melodiq_travel_registration_message() {
    if (!isset($_GET['travel_registration'])) {
        return '';
    }

    $status = sanitize_key(wp_unslash($_GET['travel_registration']));
    $messages = array(
        'success'       => __('Köszönjük, a jelentkezésedet rögzítettük. Küldtünk egy visszaigazoló emailt.', 'melodiq-journey'),
        'not_open'      => __('Erre az utazásra a jelentkezés még nem nyílt meg.', 'melodiq-journey'),
        'full'          => __('Az utazás jelenleg betelt, ezért most nem tudunk új jelentkezést fogadni.', 'melodiq-journey'),
        'duplicate'     => __('Ezzel az email címmel már van jelentkezés erre az utazásra.', 'melodiq-journey'),
        'invalid_email' => __('Adj meg egy érvényes email címet.', 'melodiq-journey'),
        'privacy'       => __('Az adatkezelési elfogadás kötelező a jelentkezéshez.', 'melodiq-journey'),
        'error'         => __('Nem sikerült menteni a jelentkezést. Próbáld újra később.', 'melodiq-journey'),
    );

    if (!isset($messages[$status])) {
        return '';
    }

    $class = 'success' === $status ? 'mj-travel-form-message--success' : 'mj-travel-form-message--error';

    return '<p class="mj-travel-form-message ' . esc_attr($class) . '">' . esc_html($messages[$status]) . '</p>';
}

function melodiq_travel_registration_form($travel_id) {
    $meta = melodiq_travel_meta($travel_id);
    $privacy_url = melodiq_travel_privacy_url();
    ob_start();
    ?>
    <div class="mj-travel-form-wrap">
        <?php echo melodiq_travel_registration_message(); ?>
        <div class="mj-travel-form-summary">
            <div><strong><?php echo esc_html($meta['available']); ?></strong><span><?php esc_html_e('szabad hely', 'melodiq-journey'); ?></span></div>
            <div><strong><?php echo esc_html($meta['capacity']); ?></strong><span><?php esc_html_e('férőhely', 'melodiq-journey'); ?></span></div>
        </div>
        <?php if ('hamarosan' === $meta['status']) : ?>
            <button class="button button-primary mj-travel-status-button" type="button" disabled><?php esc_html_e('Hamarosan', 'melodiq-journey'); ?></button>
        <?php elseif ('lezart' === $meta['status']) : ?>
            <p class="mj-travel-form-message mj-travel-form-message--error"><?php esc_html_e('A jelentkezés erre az utazásra lezárult.', 'melodiq-journey'); ?></p>
        <?php elseif (0 >= (int) $meta['available']) : ?>
            <p class="mj-travel-form-message mj-travel-form-message--error"><?php esc_html_e('Az utazás jelenleg betelt, ezért most nem tudunk új jelentkezést fogadni.', 'melodiq-journey'); ?></p>
        <?php else : ?>
        <form class="mj-travel-form" method="post" action="<?php echo esc_url(get_permalink($travel_id)); ?>#jelentkezes">
            <?php wp_nonce_field('melodiq_travel_registration_' . $travel_id, 'melodiq_travel_registration_nonce'); ?>
            <input type="hidden" name="melodiq_travel_registration_action" value="submit">
            <input type="hidden" name="melodiq_travel_id" value="<?php echo esc_attr($travel_id); ?>">
            <label class="mj-travel-honeypot" for="melodiq_travel_company"><?php esc_html_e('Cég', 'melodiq-journey'); ?><input id="melodiq_travel_company" type="text" name="melodiq_travel_company" tabindex="-1" autocomplete="off"></label>

            <div class="mj-travel-form-grid">
                <label><span><?php esc_html_e('Név', 'melodiq-journey'); ?></span><input type="text" name="melodiq_travel_name" required autocomplete="name"></label>
                <label><span><?php esc_html_e('Email', 'melodiq-journey'); ?></span><input type="email" name="melodiq_travel_email" required autocomplete="email"></label>
                <label><span><?php esc_html_e('Telefonszám', 'melodiq-journey'); ?></span><input type="tel" name="melodiq_travel_phone" required autocomplete="tel"></label>
            </div>

            <label><span><?php esc_html_e('Megjegyzés', 'melodiq-journey'); ?></span><textarea name="melodiq_travel_message" rows="4"></textarea></label>

            <label class="mj-travel-form-check">
                <input type="checkbox" name="melodiq_travel_privacy" value="1" required>
                <span>
                    <?php esc_html_e('Elfogadom az', 'melodiq-journey'); ?>
                    <a href="<?php echo esc_url($privacy_url); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e('adatkezelési feltételeket', 'melodiq-journey'); ?></a>,
                    <?php esc_html_e('és hozzájárulok, hogy a Melodiq Journey kezelje a jelentkezési adataimat.', 'melodiq-journey'); ?>
                </span>
            </label>

            <button class="button button-primary" type="submit"><?php esc_html_e('Jelentkezés elküldése', 'melodiq-journey'); ?></button>
        </form>
        <?php endif; ?>
    </div>
    <?php
    return ob_get_clean();
}

function melodiq_travel_privacy_url() {
    foreach (array('adatkezeles', 'adatkezelesi-tajekoztato', 'adatvedelem') as $slug) {
        $page = get_page_by_path($slug);

        if ($page) {
            return get_permalink($page);
        }
    }

    return home_url('/adatkezeles/');
}

function melodiq_travel_registration_email_body($message) {
    $content = wpautop(esc_html($message));
    $logo_url = function_exists('melodiq_newsletter_logo_url') ? melodiq_newsletter_logo_url() : '';
    $body = '<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="width:100%;background:#f4f6f8;margin:0;padding:26px 14px;font-family:Inter,Arial,Helvetica,sans-serif;">';
    $body .= '<tr><td align="center">';
    $body .= '<table role="presentation" width="620" cellspacing="0" cellpadding="0" style="width:100%;max-width:620px;background:#05090c;border:1px solid #102129;border-radius:16px;overflow:hidden;">';
    $body .= '<tr><td style="padding:24px 30px 16px;text-align:center;background:#071116;">';

    if ($logo_url) {
        $body .= '<img src="' . esc_url($logo_url) . '" width="132" alt="Melodiq Journey" style="display:block;width:132px;max-width:68%;height:auto;margin:0 auto 14px;">';
    } else {
        $body .= '<div style="margin:0 0 14px;color:#ffffff;font-size:21px;line-height:1;font-weight:800;letter-spacing:1px;text-transform:uppercase;">Melodiq Journey</div>';
    }

    $body .= '<div style="color:#2ed8f3;font-size:11px;line-height:1.4;font-weight:800;text-transform:uppercase;">Utazási jelentkezés</div>';
    $body .= '</td></tr>';
    $body .= '<tr><td style="padding:26px 30px;color:#f7fbff;font-family:Inter,Arial,Helvetica,sans-serif;font-size:16px;line-height:1.62;">';
    $body .= '<div style="color:#f7fbff;font-family:Inter,Arial,Helvetica,sans-serif;font-size:16px;line-height:1.62;">' . $content . '</div>';
    $body .= '</td></tr>';
    $body .= '</table>';
    $body .= '</td></tr>';
    $body .= '</table>';

    return $body;
}

function melodiq_travel_registration_send_mail($to, $subject, $message) {
    return wp_mail($to, $subject, melodiq_travel_registration_email_body($message), array(
        'Content-Type: text/html; charset=UTF-8',
        'From: Melodiq Journey <info@melodiqjourney.hu>',
    ));
}

function melodiq_travel_registration_send_emails($registration_id) {
    $registration = melodiq_travel_registration_meta($registration_id);
    $travel = melodiq_travel_meta($registration['travel_id']);
    $travel_name = $travel['event_name'] ? $travel['event_name'] : get_the_title($registration['travel_id']);
    $location = melodiq_travel_location_label($travel);

    if ('varolista' === $registration['status']) {
        $subject = sprintf(__('Várólistára kerültél – %s', 'melodiq-journey'), $travel_name);
        $message = sprintf(
            "Szia %s!\n\nKöszönjük a jelentkezésedet.\n\nAz utazás jelenleg betelt, ezért várólistára kerültél.\nHa felszabadul hely, értesítünk.\n\nMelodiq Journey",
            $registration['name']
        );
    } else {
        $subject = sprintf(__('Sikeres jelentkezés – %s', 'melodiq-journey'), $travel_name);
        $message = sprintf(
            "Szia %s!\n\nKöszönjük a jelentkezésedet a következő utazásra:\n\n%s\nDátum: %s\nIndulás: %s\nHelyszín: %s\n\nA jelentkezésedet rögzítettük.\n\nHa fizetési információ vagy további tudnivaló lesz, e-mailben értesítünk.\n\nMelodiq Journey",
            $registration['name'],
            $travel_name,
            melodiq_travel_date_label($travel['date']),
            $travel['departure'] ? $travel['departure'] : __('Hamarosan', 'melodiq-journey'),
            $location
        );
    }

    $sent_to_applicant = melodiq_travel_registration_send_mail($registration['email'], $subject, $message);
    melodiq_travel_registration_add_email_log($registration_id, 'confirmation', $registration['email'], $subject, $sent_to_applicant ? 'sent' : 'failed');

    $admin_subject = sprintf(__('Új utazási jelentkezés – %s', 'melodiq-journey'), $travel_name);
    $admin_message = sprintf(
        "Új jelentkezés érkezett.\n\nNév: %s\nEmail: %s\nTelefonszám: %s\nUtasok száma: %d\nStátusz: %s\nUtazás: %s",
        $registration['name'],
        $registration['email'],
        $registration['phone'],
        $registration['passengers'],
        melodiq_travel_registration_status_label($registration['status']),
        $travel_name
    );

    $admin_email = get_option('admin_email');
    $sent_to_admin = melodiq_travel_registration_send_mail($admin_email, $admin_subject, $admin_message);
    melodiq_travel_registration_add_email_log($registration_id, 'admin_notification', $admin_email, $admin_subject, $sent_to_admin ? 'sent' : 'failed');
}

function melodiq_travel_payment_email_default_body() {
    return "Szia {nev}!\n\nElérkezett a fizetés ideje a következő utazásra:\n\n{utazas_neve}\nDátum: {datum}\nIndulás: {indulas}\nHelyszín: {helyszin}\n\nFizetendő összeg: {osszeg}\nFizetési határidő: {fizetesi_hatarido}\nKözlemény: {kozlemeny}\n\n{fizetesi_instrukciok}\n\nKöszönjük!\n\nMelodiq Journey";
}

function melodiq_travel_payment_email_replace_tokens($text, $registration, $travel) {
    $travel_name = $travel['event_name'] ? $travel['event_name'] : get_the_title($registration['travel_id']);
    $location = melodiq_travel_location_label($travel);
    $payment_deadline = $travel['payment_deadline'] ? melodiq_travel_date_label($travel['payment_deadline']) : __('Hamarosan', 'melodiq-journey');
    $memo = trim($registration['name'] . ' - ' . $travel_name);
    $replacements = array(
        '{nev}'                  => $registration['name'],
        '{utazas_neve}'          => $travel_name,
        '{datum}'                => melodiq_travel_date_label($travel['date']),
        '{indulas}'              => $travel['departure'] ? $travel['departure'] : __('Hamarosan', 'melodiq-journey'),
        '{helyszin}'             => $location,
        '{osszeg}'               => $travel['payment_amount'] ? $travel['payment_amount'] : __('Hamarosan', 'melodiq-journey'),
        '{fizetesi_hatarido}'    => $payment_deadline,
        '{fizetesi_instrukciok}' => $travel['payment_instructions'],
        '{kozlemeny}'            => $memo,
    );

    return strtr($text, $replacements);
}

function melodiq_travel_registration_send_payment_email($registration_id) {
    $registration_id = absint($registration_id);

    if (!$registration_id || 'travel_registration' !== get_post_type($registration_id)) {
        return new WP_Error('invalid_registration', __('Érvénytelen jelentkezés.', 'melodiq-journey'));
    }

    $registration = melodiq_travel_registration_meta($registration_id);

    if (!$registration['travel_id'] || 'travel' !== get_post_type($registration['travel_id'])) {
        return new WP_Error('invalid_travel', __('A jelentkezéshez nem tartozik érvényes utazás.', 'melodiq-journey'));
    }

    if ('jelentkezett' !== $registration['status']) {
        return new WP_Error('invalid_status', __('Csak jelentkezett státuszú jelentkezőnek küldhető fizetési email.', 'melodiq-journey'));
    }

    if ('fizetett' === $registration['payment_status']) {
        return new WP_Error('already_paid', __('Ez a jelentkező már fizetett státuszban van.', 'melodiq-journey'));
    }

    if (!$registration['email'] || !is_email($registration['email'])) {
        return new WP_Error('invalid_email', __('A jelentkező email címe hibás vagy hiányzik.', 'melodiq-journey'));
    }

    $travel = melodiq_travel_meta($registration['travel_id']);
    $travel_name = $travel['event_name'] ? $travel['event_name'] : get_the_title($registration['travel_id']);
    $subject_template = $travel['payment_email_subject'] ? $travel['payment_email_subject'] : __('Fizetési információk - {utazas_neve}', 'melodiq-journey');
    $body_template = $travel['payment_email_body'] ? $travel['payment_email_body'] : melodiq_travel_payment_email_default_body();
    $subject = melodiq_travel_payment_email_replace_tokens($subject_template, $registration, $travel);
    $message = melodiq_travel_payment_email_replace_tokens($body_template, $registration, $travel);
    $sent = melodiq_travel_registration_send_mail($registration['email'], $subject, $message);
    melodiq_travel_registration_add_email_log($registration_id, 'payment', $registration['email'], $subject, $sent ? 'sent' : 'failed');

    if (!$sent) {
        return new WP_Error('mail_failed', __('Nem sikerült elküldeni a fizetési emailt.', 'melodiq-journey'));
    }

    update_post_meta($registration_id, '_melodiq_travel_registration_payment_email_sent_at', current_time('mysql'));
    update_post_meta($registration_id, '_melodiq_travel_registration_payment_status', 'fuggoben');

    return true;
}

function melodiq_travel_registration_admin_menu() {
    add_submenu_page(
        'edit.php?post_type=travel',
        __('Utazási CSV import', 'melodiq-journey'),
        __('CSV import', 'melodiq-journey'),
        'manage_options',
        'melodiq-travel-csv-import',
        'melodiq_travel_registration_import_page'
    );

    add_submenu_page(
        'edit.php?post_type=travel',
        __('Utazási jelentkezők', 'melodiq-journey'),
        __('Jelentkezők', 'melodiq-journey'),
        'edit_posts',
        'melodiq-travel-registrants',
        'melodiq_travel_registration_admin_list_page'
    );
}
add_action('admin_menu', 'melodiq_travel_registration_admin_menu');

function melodiq_travel_registration_admin_notice($type, $message) {
    set_transient('melodiq_travel_registration_notice_' . get_current_user_id(), array(
        'type'    => $type,
        'message' => $message,
    ), 60);
}

function melodiq_travel_registration_print_admin_notice() {
    $notice = get_transient('melodiq_travel_registration_notice_' . get_current_user_id());

    if (!$notice) {
        return;
    }

    delete_transient('melodiq_travel_registration_notice_' . get_current_user_id());
    $class = 'success' === $notice['type'] ? 'notice notice-success is-dismissible' : 'notice notice-error is-dismissible';
    echo '<div class="' . esc_attr($class) . '"><p>' . esc_html($notice['message']) . '</p></div>';
}
add_action('admin_notices', 'melodiq_travel_registration_print_admin_notice');

function melodiq_travel_registration_handle_admin_update() {
    if (!is_admin() || !current_user_can('edit_posts') || !isset($_POST['melodiq_travel_registration_update_action'])) {
        return;
    }

    check_admin_referer('melodiq_travel_registration_update', 'melodiq_travel_registration_update_nonce');
    $registration_id = isset($_POST['registration_id']) ? absint($_POST['registration_id']) : 0;

    if (!$registration_id || 'travel_registration' !== get_post_type($registration_id)) {
        return;
    }

    $status = isset($_POST['registration_status']) ? sanitize_key(wp_unslash($_POST['registration_status'])) : '';
    $payment_status = isset($_POST['payment_status']) ? sanitize_key(wp_unslash($_POST['payment_status'])) : '';

    $status_options = melodiq_travel_registration_status_options();
    $payment_options = melodiq_travel_registration_payment_status_options();

    if (isset($status_options[$status])) {
        update_post_meta($registration_id, '_melodiq_travel_registration_status', $status);
    }

    if (isset($payment_options[$payment_status])) {
        update_post_meta($registration_id, '_melodiq_travel_registration_payment_status', $payment_status);
    }

    melodiq_travel_registration_admin_notice('success', __('Jelentkezés frissítve.', 'melodiq-journey'));
    wp_safe_redirect(wp_get_referer() ? wp_get_referer() : admin_url('edit.php?post_type=travel&page=melodiq-travel-registrants'));
    exit;
}
add_action('admin_init', 'melodiq_travel_registration_handle_admin_update');

function melodiq_travel_registration_handle_payment_email_action() {
    if (!is_admin() || !current_user_can('edit_posts') || !isset($_POST['melodiq_travel_payment_email_action'])) {
        return;
    }

    $action = sanitize_key(wp_unslash($_POST['melodiq_travel_payment_email_action']));
    $sent = 0;
    $failed = 0;

    if ('single' === $action) {
        check_admin_referer('melodiq_travel_payment_email_single', 'melodiq_travel_payment_email_nonce');
        $registration_ids = array(isset($_POST['registration_id']) ? absint($_POST['registration_id']) : 0);
    } elseif ('bulk' === $action) {
        check_admin_referer('melodiq_travel_payment_email_bulk', 'melodiq_travel_payment_email_bulk_nonce');
        $registration_ids = isset($_POST['registration_ids']) && is_array($_POST['registration_ids']) ? array_map('absint', wp_unslash($_POST['registration_ids'])) : array();
    } else {
        return;
    }

    foreach (array_filter($registration_ids) as $registration_id) {
        $result = melodiq_travel_registration_send_payment_email($registration_id);

        if (is_wp_error($result)) {
            $failed++;
        } else {
            $sent++;
        }
    }

    if ($sent && !$failed) {
        melodiq_travel_registration_admin_notice('success', sprintf(__('Fizetési email elküldve: %d jelentkező.', 'melodiq-journey'), $sent));
    } elseif ($sent && $failed) {
        melodiq_travel_registration_admin_notice('success', sprintf(__('Fizetési email elküldve: %1$d, sikertelen: %2$d.', 'melodiq-journey'), $sent, $failed));
    } else {
        melodiq_travel_registration_admin_notice('error', __('Nem sikerült fizetési emailt küldeni. Ellenőrizd a státuszt, email címet és SMTP beállításokat.', 'melodiq-journey'));
    }

    wp_safe_redirect(wp_get_referer() ? wp_get_referer() : admin_url('edit.php?post_type=travel&page=melodiq-travel-registrants'));
    exit;
}
add_action('admin_init', 'melodiq_travel_registration_handle_payment_email_action');

function melodiq_travel_registration_handle_csv_export() {
    if (!is_admin() || !current_user_can('edit_posts') || empty($_GET['melodiq_travel_export_csv'])) {
        return;
    }

    check_admin_referer('melodiq_travel_export_csv');
    $filters = melodiq_travel_registration_admin_filters_from_request();
    $registrations = get_posts(melodiq_travel_registration_admin_query_args($filters));
    $filename = 'melodiq-utazasi-jelentkezok-' . wp_date('Y-m-d-H-i') . '.csv';

    nocache_headers();
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    echo "\xEF\xBB\xBF";

    $output = fopen('php://output', 'w');
    fputcsv($output, array(
        'Sorszám',
        'Név',
        'Email',
        'Telefonszám',
        'Jelentkezés státusz',
        'Fizetési státusz',
        'Fizetési email kiküldve',
        'Utolsó email típusa',
        'Utolsó email dátuma',
        'Utolsó email státusza',
        'Jelentkezés dátuma',
        'Forrás',
        'Utazás',
        'Megjegyzés',
        'Belső admin megjegyzés',
    ), ';');

    $index = 1;
    foreach ($registrations as $registration_post) {
        $meta = melodiq_travel_registration_meta($registration_post->ID);
        $email_log = melodiq_travel_registration_email_log($registration_post->ID);
        $latest_email_log = $email_log ? end($email_log) : array();
        fputcsv($output, array(
            $index,
            $meta['name'],
            $meta['email'],
            $meta['phone'],
            melodiq_travel_registration_status_label($meta['status']),
            melodiq_travel_registration_payment_status_label($meta['payment_status']),
            $meta['payment_email_sent_at'] ? mysql2date('Y.m.d. H:i', $meta['payment_email_sent_at']) : '',
            $latest_email_log && !empty($latest_email_log['type']) ? melodiq_travel_registration_email_log_type_label($latest_email_log['type']) : '',
            $latest_email_log && !empty($latest_email_log['date']) ? mysql2date('Y.m.d. H:i', $latest_email_log['date']) : '',
            $latest_email_log && !empty($latest_email_log['status']) ? melodiq_travel_registration_email_log_status_label($latest_email_log['status']) : '',
            $meta['registered_at'] ? mysql2date('Y.m.d. H:i', $meta['registered_at']) : get_the_date('Y.m.d. H:i', $registration_post),
            melodiq_travel_registration_source_label($meta['source']),
            $meta['travel_id'] ? get_the_title($meta['travel_id']) : '',
            $meta['message'],
            $meta['admin_note'],
        ), ';');
        $index++;
    }

    fclose($output);
    exit;
}
add_action('admin_init', 'melodiq_travel_registration_handle_csv_export');

function melodiq_travel_options($post_type = 'travel') {
    return get_posts(array(
        'post_type'      => $post_type,
        'post_status'    => 'publish',
        'posts_per_page' => -1,
        'orderby'        => 'title',
        'order'          => 'ASC',
    ));
}

function melodiq_travel_registration_admin_query_args($filters = array()) {
    $filters = wp_parse_args($filters, array(
        'travel_id'      => 0,
        'search'         => '',
        'status'         => '',
        'payment_status' => '',
        'source'         => '',
    ));
    $meta_query = array('relation' => 'AND');

    if ($filters['travel_id']) {
        $meta_query[] = array(
            'key'   => '_melodiq_travel_registration_travel_id',
            'value' => absint($filters['travel_id']),
        );
    }

    if ($filters['status']) {
        $meta_query[] = array(
            'key'   => '_melodiq_travel_registration_status',
            'value' => sanitize_key($filters['status']),
        );
    }

    if ($filters['payment_status']) {
        $meta_query[] = array(
            'key'   => '_melodiq_travel_registration_payment_status',
            'value' => sanitize_key($filters['payment_status']),
        );
    }
    if ($filters['source']) {
        $meta_query[] = array(
            'key'   => '_melodiq_travel_registration_source',
            'value' => sanitize_key($filters['source']),
        );
    }

    if ($filters['search']) {
        $search = sanitize_text_field($filters['search']);
        $meta_query[] = array(
            'relation' => 'OR',
            array(
                'key'     => '_melodiq_travel_registration_name',
                'value'   => $search,
                'compare' => 'LIKE',
            ),
            array(
                'key'     => '_melodiq_travel_registration_email',
                'value'   => $search,
                'compare' => 'LIKE',
            ),
            array(
                'key'     => '_melodiq_travel_registration_phone',
                'value'   => $search,
                'compare' => 'LIKE',
            ),
        );
    }

    $args = array(
        'post_type'      => 'travel_registration',
        'post_status'    => 'private',
        'posts_per_page' => -1,
        'orderby'        => 'date',
        'order'          => 'DESC',
    );

    if (count($meta_query) > 1) {
        $args['meta_query'] = $meta_query;
    }

    return $args;
}

function melodiq_travel_registration_admin_filters_from_request() {
    return array(
        'travel_id'      => isset($_GET['travel_id']) ? absint($_GET['travel_id']) : 0,
        'search'         => isset($_GET['registration_search']) ? sanitize_text_field(wp_unslash($_GET['registration_search'])) : '',
        'status'         => isset($_GET['registration_status_filter']) ? melodiq_travel_registration_normalize_status_key(wp_unslash($_GET['registration_status_filter']), '') : '',
        'payment_status' => isset($_GET['payment_status_filter']) ? melodiq_travel_registration_normalize_payment_status_key(wp_unslash($_GET['payment_status_filter']), '') : '',
        'source'         => isset($_GET['registration_source_filter']) ? sanitize_key(wp_unslash($_GET['registration_source_filter'])) : '',
    );
}

function melodiq_travel_registration_admin_list_page() {
    $filters = melodiq_travel_registration_admin_filters_from_request();
    $selected_travel = $filters['travel_id'];
    $registrations = get_posts(melodiq_travel_registration_admin_query_args($filters));
    $travel_prices = array();
    $export_url = wp_nonce_url(add_query_arg(array(
        'post_type'                  => 'travel',
        'page'                       => 'melodiq-travel-registrants',
        'melodiq_travel_export_csv'  => '1',
        'travel_id'                  => $selected_travel,
        'registration_search'        => $filters['search'],
        'registration_status_filter' => $filters['status'],
        'payment_status_filter'      => $filters['payment_status'],
        'registration_source_filter' => $filters['source'],
    ), admin_url('edit.php')), 'melodiq_travel_export_csv');

    $all_filtered_registrations = $registrations;
    $total_registrations = count($all_filtered_registrations);
    $paid_registrations = 0;
    $waiting_payment_registrations = 0;
    $confirmed_registrations = 0;
    $total_revenue = 0;
    $capacity = 0;

    if ($selected_travel) {
        $capacity = (int) get_post_meta($selected_travel, '_melodiq_travel_capacity', true);
    }

    foreach ($all_filtered_registrations as $registration_post) {
        $meta = melodiq_travel_registration_meta($registration_post->ID);

        if ('fizetett' === $meta['payment_status']) {
            $paid_registrations += (int) $meta['passengers'];
        } else {
            $waiting_payment_registrations += (int) $meta['passengers'];
        }

        if ('jelentkezett' === $meta['status']) {
            $confirmed_registrations += $meta['passengers'];
        }

        if ('fizetett' === $meta['payment_status'] && !empty($meta['travel_id'])) {
            $travel_meta = melodiq_travel_meta($meta['travel_id']);
            $payment_amount_raw = isset($travel_meta['payment_amount']) ? (string) $travel_meta['payment_amount'] : '';
            $payment_amount = (int) preg_replace('/[^0-9]/', '', $payment_amount_raw);

            if ($payment_amount > 0) {
                $total_revenue += $payment_amount * (int) $meta['passengers'];
            }
        }
    }

    $occupancy_percent = $capacity ? min(100, round(($confirmed_registrations / $capacity) * 100)) : 0;
    ?>
    <div class="wrap melodiq-admin-dashboard">
        <h1><?php esc_html_e('Utazási jelentkezők', 'melodiq-journey'); ?></h1>
        <p class="description"><?php esc_html_e('Kezeld az utazásokra érkező jelentkezéseket, fizetéseket és email küldéseket egy helyen.', 'melodiq-journey'); ?></p>
        <style>
            #wpcontent { padding-left: 0!important; background:#071016!important; }
            #wpbody-content { padding-bottom: 0!important; background:#071016!important; }
            .wrap.melodiq-admin-dashboard { margin: 0!important; padding: 28px 28px 40px; min-height: calc(100vh - 32px); width: 100%; max-width: none; box-sizing: border-box; overflow-x: visible; background: radial-gradient(circle at 20% 0%, rgba(39,217,248,.16), transparent 32%), linear-gradient(135deg, #071016 0%, #0b1118 48%, #06090d 100%); color: #f7fbff; }
            .melodiq-admin-dashboard h1 { color: #f7fbff; font-size: 30px; font-weight: 800; letter-spacing: -.03em; margin: 0 0 6px; }
            .melodiq-admin-dashboard .description { color: #98a6b3; margin: 0 0 18px; }
            .melodiq-registration-stats { display:grid;grid-template-columns:repeat(5,minmax(120px,1fr));gap:14px;margin:20px 0 18px; }
            .melodiq-registration-stat-card { min-width:0;background:linear-gradient(145deg, rgba(18,30,40,.92), rgba(11,18,26,.92));border:1px solid rgba(255,255,255,.08);border-radius:14px;padding:12px 14px;box-shadow:0 18px 45px rgba(0,0,0,.28); }
            .melodiq-registration-stat-card span { display:block;color:#9aa8b5;font-size:12px;text-transform:uppercase;font-weight:800;letter-spacing:.06em;margin-bottom:8px; }
            .melodiq-registration-stat-card strong { display:block;color:#ffffff;font-size:24px;line-height:1.15;font-weight:900;letter-spacing:-.03em; }
            .melodiq-registration-progress { height:9px;border-radius:999px;background:rgba(255,255,255,.08);overflow:hidden;margin-top:12px; }
            .melodiq-registration-progress i { display:block;height:100%;background:linear-gradient(90deg,#21d4ef,#12b8a8);box-shadow:0 0 18px rgba(39,217,248,.55); }
            .melodiq-registration-toolbar { display:flex;flex-wrap:wrap;gap:10px;align-items:center;margin:18px 0;padding:18px;background:rgba(15,25,34,.88);border:1px solid rgba(255,255,255,.08);border-radius:18px;box-shadow:0 16px 40px rgba(0,0,0,.22);overflow-x:auto; }
            .melodiq-registration-toolbar > * { min-width: 0; }
            .melodiq-registration-toolbar select,
            .melodiq-registration-toolbar input[type="search"] { min-height:42px;background:#0b131b!important;border:1px solid rgba(255,255,255,.14)!important;border-radius:10px!important;color:#f7fbff!important;box-shadow:none!important; }
            .melodiq-registration-toolbar input[type="search"] { flex:1 1 300px; min-width: 220px!important; }
            .melodiq-registration-toolbar input::placeholder { color:#768694; }
            .melodiq-registration-toolbar .button,
            .melodiq-registration-actions .button,
            .melodiq-admin-dashboard .button { min-height:38px;border-radius:10px!important;border-color:rgba(255,255,255,.16)!important;background:#111c26!important;color:#f7fbff!important;box-shadow:none!important;font-weight:700; }
            .melodiq-registration-toolbar .button-primary,
            .melodiq-registration-actions .button-primary { background:linear-gradient(135deg,#22d3ee,#14b8a6)!important;border-color:transparent!important;color:#031016!important; }
            .melodiq-registration-actions { display:flex;gap:10px;align-items:center;margin:0 0 16px; }
            .melodiq-registration-table-wrap { background:rgba(12,20,28,.9);border:1px solid rgba(255,255,255,.08);border-radius:18px;overflow-x:auto!important;overflow-y:hidden!important;box-shadow:0 18px 55px rgba(0,0,0,.3); }
            .melodiq-registration-table { display:table!important;width:100%!important;table-layout:auto!important;margin:0!important;border:0!important;background:transparent!important;color:#f7fbff; min-width: 1160px!important; }
            .melodiq-registration-table thead th { background:rgba(4,8,12,.8)!important;color:#aeb8c2!important;border-bottom:1px solid rgba(255,255,255,.08)!important;font-size:10px;text-transform:uppercase;letter-spacing:.035em;font-weight:800;padding:10px 8px!important;line-height:1.15; }
            .melodiq-registration-table tbody tr,
            .melodiq-registration-table tbody tr.alternate,
            .melodiq-registration-table tbody tr:nth-child(odd),
            .melodiq-registration-table tbody tr:nth-child(even) { background:rgba(10,18,26,.96)!important; }
            .melodiq-registration-table tbody td { background:rgba(10,18,26,.96)!important;color:#e9f0f6!important;border-bottom:1px solid rgba(255,255,255,.07)!important;padding:10px 8px!important;vertical-align:middle;font-size:12px; }
            .melodiq-registration-table tbody tr:hover,
            .melodiq-registration-table tbody tr:hover td { background:rgba(39,217,248,.075)!important; }
            .melodiq-registration-table a { color:#63e6ff!important;font-weight:700;text-decoration:none; }
            .melodiq-registration-table select { min-height:32px;background:#0b131b!important;border:1px solid rgba(255,255,255,.14)!important;border-radius:999px!important;color:#f7fbff!important;font-weight:800;width:112px!important;max-width:112px!important;font-size:12px!important;padding-left:10px!important;padding-right:22px!important; }
            .melodiq-registration-table select.melodiq-status-select-jelentkezett { background:rgba(39,217,248,.13)!important;border-color:rgba(39,217,248,.28)!important;color:#74ecff!important; }
            .melodiq-registration-table select.melodiq-status-select-varolista { background:rgba(168,85,247,.15)!important;border-color:rgba(168,85,247,.28)!important;color:#d9b4ff!important; }
            .melodiq-registration-table select.melodiq-status-select-torolve { background:rgba(148,163,184,.14)!important;border-color:rgba(148,163,184,.28)!important;color:#cbd5e1!important; }
            .melodiq-registration-table select.melodiq-payment-select-fizetett { background:rgba(34,197,94,.15)!important;border-color:rgba(34,197,94,.3)!important;color:#76f2a3!important; }
            .melodiq-registration-table select.melodiq-payment-select-fuggoben { background:rgba(245,158,11,.15)!important;border-color:rgba(245,158,11,.3)!important;color:#ffd66b!important; }
            .melodiq-registration-table select.melodiq-payment-select-nem_fizetett { background:rgba(239,68,68,.15)!important;border-color:rgba(239,68,68,.3)!important;color:#ff8a8a!important; }
            .melodiq-registration-table input[type="checkbox"] { background:#0b131b!important;border:1px solid rgba(255,255,255,.28)!important;box-shadow:none!important;width:16px!important;height:16px!important;margin:0!important; }
            .melodiq-registration-table input[type="checkbox"]:checked::before { filter: brightness(2); }
            .melodiq-registration-table td span[style*="color:#787c82"] { color:#9aa8b5!important; }
            .melodiq-registration-name-cell,
            .melodiq-registration-phone-cell,
            .melodiq-registration-email-cell { white-space: nowrap; }
            .melodiq-registration-name-cell { font-weight: 700; }
            .melodiq-registration-select-cell { width: 28px!important; min-width: 28px!important; max-width: 28px!important; padding-left: 8px!important; padding-right: 4px!important; text-align: center; }
            .melodiq-registration-phone-cell { width:auto!important; min-width:0!important; max-width:none!important; }
            .melodiq-registration-name-cell .melodiq-registration-index { vertical-align: middle; }
            .melodiq-email-log-list { margin: 0; }
            .melodiq-email-log-list li { margin: 0 0 4px; color: #9aa8b5; }
            .melodiq-badge { display:inline-flex;align-items:center;gap:6px;border-radius:999px;padding:6px 10px;font-size:12px;font-weight:800;line-height:1;white-space:nowrap;max-width:118px;overflow:hidden;text-overflow:ellipsis; }
            .melodiq-badge-payment-fizetett { background:rgba(34,197,94,.18);color:#76f2a3; }
            .melodiq-badge-payment-fuggoben { background:rgba(245,158,11,.18);color:#ffd66b; }
            .melodiq-badge-payment-nem_fizetett { background:rgba(239,68,68,.18);color:#ff8a8a; }
            .melodiq-badge-status-jelentkezett { background:rgba(39,217,248,.16);color:#74ecff; }
            .melodiq-badge-status-varolista { background:rgba(168,85,247,.18);color:#d9b4ff; }
            .melodiq-badge-status-torolve { background:rgba(148,163,184,.16);color:#cbd5e1; }
            .melodiq-badge-source-wordpress_form { background:rgba(59,130,246,.18);color:#9cc2ff; }
            .melodiq-badge-source-google_sheets { background:rgba(139,92,246,.18);color:#c7b7ff; }
            .melodiq-badge-source-manual { background:rgba(148,163,184,.18);color:#d3dae4; }
            .melodiq-row-actions { display:flex;gap:5px;align-items:center;justify-content:flex-start;flex-wrap:nowrap;white-space:nowrap;line-height:1.2; }
            .melodiq-row-actions a,
            .melodiq-row-actions button{display:inline-flex!important;align-items:center!important;gap:4px!important;background:rgba(255,255,255,.07)!important;border:1px solid rgba(255,255,255,.1)!important;border-radius:999px!important;box-shadow:none!important;padding:6px 7px!important;min-height:27px!important;color:#dffaff!important;font-size:10px!important;font-weight:800!important;cursor:pointer;text-decoration:none!important;}
            .melodiq-row-actions a:hover,.melodiq-row-actions button:hover{background:rgba(39,217,248,.13)!important;border-color:rgba(39,217,248,.28)!important;color:#ffffff!important;text-decoration:none!important;}
            .melodiq-row-actions .melodiq-action-save{background:rgba(39,217,248,.16)!important;border-color:rgba(39,217,248,.3)!important;color:#8ff3ff!important;}
            .melodiq-row-actions .melodiq-action-mail{background:rgba(59,130,246,.16)!important;border-color:rgba(59,130,246,.3)!important;color:#a8cfff!important;}
            .melodiq-row-actions .melodiq-action-payment{background:rgba(34,197,94,.16)!important;border-color:rgba(34,197,94,.3)!important;color:#8ef0ad!important;}
            .melodiq-action-icon{width:12px;height:12px;display:inline-block;flex:0 0 12px;}
            .melodiq-action-col{width:auto!important;min-width:0!important;max-width:none!important;}
            .melodiq-registration-email-cell{width:auto!important;min-width:0!important;max-width:none!important;overflow:hidden;text-overflow:ellipsis;}
            .melodiq-registration-phone-cell { width:auto!important; min-width:0!important; max-width:none!important; }
            .melodiq-registration-table th,
            .melodiq-registration-table td{width:auto!important;min-width:0!important;max-width:none!important;}
            .melodiq-registration-table th:nth-child(1),.melodiq-registration-table td:nth-child(1){width:34px!important;text-align:center;}
            .melodiq-registration-table th:nth-child(2),.melodiq-registration-table td:nth-child(2){white-space:nowrap;}
            .melodiq-registration-table th:nth-child(3),.melodiq-registration-table td:nth-child(3){white-space:nowrap;}
            .melodiq-registration-table th:nth-child(4),.melodiq-registration-table td:nth-child(4){white-space:nowrap;}
            .melodiq-registration-table th:nth-child(5),.melodiq-registration-table td:nth-child(5){white-space:nowrap;}
            .melodiq-registration-table th:nth-child(6),.melodiq-registration-table td:nth-child(6){white-space:nowrap;text-align:left;}
            .melodiq-registration-table th:nth-child(7),.melodiq-registration-table td:nth-child(7){white-space:nowrap;text-align:center;}
            .melodiq-registration-table th:nth-child(8),.melodiq-registration-table td:nth-child(8){white-space:nowrap;text-align:center;}
            .melodiq-registration-table th:nth-child(9),.melodiq-registration-table td:nth-child(9){white-space:nowrap;}
            .melodiq-registration-table th:nth-child(10),.melodiq-registration-table td:nth-child(10){white-space:nowrap;}
            .melodiq-badge{max-width:none;overflow:visible;text-overflow:clip;}
            .melodiq-email-log-pill{padding:5px 8px!important;}
            .melodiq-email-log-toggle{display:inline-flex;align-items:center;gap:6px;border-radius:999px;background:rgba(255,255,255,.07);border:1px solid rgba(255,255,255,.09);padding:5px 8px;color:#cbd5e1;font-weight:800;white-space:nowrap;cursor:pointer;}
            .melodiq-email-log-toggle:hover{background:rgba(39,217,248,.13);border-color:rgba(39,217,248,.28);color:#fff;}
            .melodiq-email-log-details{display:none;position:absolute;top:100%;left:0;margin-top:8px;min-width:220px;padding:10px;border-radius:12px;background:#071016;border:1px solid rgba(255,255,255,.1);box-shadow:0 14px 30px rgba(0,0,0,.28);z-index:100;}
            .melodiq-email-log-details.is-open{display:block;}
            .melodiq-email-log-details p{margin:0 0 8px;font-size:11px;line-height:1.35;color:#d7e2ea;}
            .melodiq-email-log-details p:last-child{margin-bottom:0;}
            .melodiq-email-log-cell{position:relative;overflow:visible!important;}
            .melodiq-row-actions form { margin:0!important; }
            .melodiq-row-actions .button { min-height:28px!important;padding:0 7px!important;border-radius:9px!important;font-size:10px!important; }
            .melodiq-icon-action { width:30px!important;min-width:30px!important;height:30px!important;display:inline-flex!important;align-items:center!important;justify-content:center!important;padding:0!important;font-size:13px!important; }
            .melodiq-payment-email-state { display:inline-flex;align-items:center;gap:4px;font-weight:800;color:#9aa8b5;white-space:nowrap;font-size:11px; }
            .melodiq-payment-email-state--sent { color:#76f2a3; }
            .melodiq-payment-row-fizetett td { background:rgba(34,197,94,.06)!important; }
            .melodiq-payment-row-fuggoben td { background:rgba(245,158,11,.055)!important; }
            .melodiq-payment-row-nem_fizetett td { background:rgba(239,68,68,.045)!important; }
            .melodiq-admin-dashboard input[type="checkbox"] { accent-color:#27d9f8; }
            @media (max-width: 1400px) { .melodiq-registration-stats { grid-template-columns:repeat(3,minmax(150px,1fr)); } }
            @media (max-width: 1100px) { .melodiq-registration-stats { grid-template-columns:repeat(2,minmax(150px,1fr)); } .melodiq-registration-table-wrap { overflow-x:auto; } }
        </style>
        <div class="melodiq-registration-stats">
            <div class="melodiq-registration-stat-card">
                <span><?php esc_html_e('Összes jelentkező', 'melodiq-journey'); ?></span>
                <strong><?php echo esc_html($total_registrations); ?></strong>
            </div>
            <div class="melodiq-registration-stat-card">
                <span><?php esc_html_e('Fizetett', 'melodiq-journey'); ?></span>
                <strong><?php echo esc_html($paid_registrations); ?></strong>
            </div>
            <div class="melodiq-registration-stat-card">
                <span><?php esc_html_e('Fizetésre vár', 'melodiq-journey'); ?></span>
                <strong><?php echo esc_html($waiting_payment_registrations); ?></strong>
            </div>
            <div class="melodiq-registration-stat-card">
                <span><?php esc_html_e('Foglaltság', 'melodiq-journey'); ?></span>
                <strong><?php echo esc_html($selected_travel && $capacity ? $confirmed_registrations . ' / ' . $capacity : $confirmed_registrations); ?></strong>
                <?php if ($selected_travel && $capacity) : ?>
                    <div class="melodiq-registration-progress"><i style="width:<?php echo esc_attr($occupancy_percent); ?>%;"></i></div>
                <?php endif; ?>
            </div>
            <div class="melodiq-registration-stat-card">
                <span><?php esc_html_e('Bevétel', 'melodiq-journey'); ?></span>
                <strong><?php echo esc_html(number_format_i18n($total_revenue)); ?> Ft</strong>
            </div>
        </div>
        <form method="get" class="melodiq-registration-toolbar">
            <input type="hidden" name="post_type" value="travel">
            <input type="hidden" name="page" value="melodiq-travel-registrants">
            <select name="travel_id">
                <option value="0"><?php esc_html_e('Összes utazás', 'melodiq-journey'); ?></option>
                <?php foreach (melodiq_travel_options('travel') as $travel) : ?>
                    <option value="<?php echo esc_attr($travel->ID); ?>" <?php selected($selected_travel, $travel->ID); ?>><?php echo esc_html(get_the_title($travel)); ?></option>
                <?php endforeach; ?>
            </select>
            <input type="search" name="registration_search" value="<?php echo esc_attr($filters['search']); ?>" placeholder="<?php esc_attr_e('Keresés név, email vagy telefonszám alapján', 'melodiq-journey'); ?>" style="min-width:300px;">
            <select name="registration_status_filter">
                <option value=""><?php esc_html_e('Minden státusz', 'melodiq-journey'); ?></option>
                <?php foreach (melodiq_travel_registration_status_options() as $status_key => $status_label) : ?>
                    <option value="<?php echo esc_attr($status_key); ?>" <?php selected($filters['status'], $status_key); ?>><?php echo esc_html($status_label); ?></option>
                <?php endforeach; ?>
            </select>
            <select name="payment_status_filter">
                <option value=""><?php esc_html_e('Minden fizetés', 'melodiq-journey'); ?></option>
                <?php foreach (melodiq_travel_registration_payment_status_options() as $payment_key => $payment_label) : ?>
                    <option value="<?php echo esc_attr($payment_key); ?>" <?php selected($filters['payment_status'], $payment_key); ?>><?php echo esc_html($payment_label); ?></option>
                <?php endforeach; ?>
            </select>
            <select name="registration_source_filter">
                <option value=""><?php esc_html_e('Minden forrás', 'melodiq-journey'); ?></option>
                <?php foreach (melodiq_travel_registration_source_options() as $source_key => $source_label) : ?>
                    <option value="<?php echo esc_attr($source_key); ?>" <?php selected($filters['source'], $source_key); ?>><?php echo esc_html($source_label); ?></option>
                <?php endforeach; ?>
            </select>
            <button class="button" type="submit"><?php esc_html_e('Szűrés', 'melodiq-journey'); ?></button>
            <a class="button" href="<?php echo esc_url(admin_url('edit.php?post_type=travel&page=melodiq-travel-registrants')); ?>"><?php esc_html_e('Szűrők törlése', 'melodiq-journey'); ?></a>
            <a class="button button-secondary" href="<?php echo esc_url($export_url); ?>"><?php esc_html_e('CSV export', 'melodiq-journey'); ?></a>
        </form>

        <form id="melodiq-payment-email-bulk" method="post" class="melodiq-registration-actions">
            <?php wp_nonce_field('melodiq_travel_payment_email_bulk', 'melodiq_travel_payment_email_bulk_nonce'); ?>
            <input type="hidden" name="melodiq_travel_payment_email_action" value="bulk">
            <button class="button button-primary" type="submit" onclick="return confirm('<?php echo esc_js(__('Biztosan kiküldöd a fizetési emailt a kijelölt jelentkezőknek?', 'melodiq-journey')); ?>');"><?php esc_html_e('✉️ Fizetési email a kijelölteknek', 'melodiq-journey'); ?></button>
        </form>

        <div class="melodiq-registration-table-wrap">
        <table class="widefat striped melodiq-registration-table">
            <thead>
                <tr>
                    <th class="melodiq-registration-select-cell"><input type="checkbox" id="melodiq-select-all-registrations" title="<?php esc_attr_e('Összes kijelölése', 'melodiq-journey'); ?>"></th>
                    <th><?php esc_html_e('Név', 'melodiq-journey'); ?></th>
                    <th><?php esc_html_e('Email', 'melodiq-journey'); ?></th>
                    <th><?php esc_html_e('Telefonszám', 'melodiq-journey'); ?></th>
                    <th><?php esc_html_e('Fizetés', 'melodiq-journey'); ?></th>
                    <th><?php esc_html_e('Fizetési email', 'melodiq-journey'); ?></th>
                    <th><?php esc_html_e('Email napló', 'melodiq-journey'); ?></th>
                    <th><?php esc_html_e('Dátum', 'melodiq-journey'); ?></th>
                    <th><?php esc_html_e('Forrás', 'melodiq-journey'); ?></th>
                    <th class="melodiq-action-col"><?php esc_html_e('Műveletek', 'melodiq-journey'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if ($registrations) : ?>
                    <?php $registration_index = 1; ?>
                    <?php foreach ($registrations as $registration_post) : $meta = melodiq_travel_registration_meta($registration_post->ID); $email_log = melodiq_travel_registration_email_log($registration_post->ID); $latest_email_log = $email_log ? end($email_log) : array(); $form_id = 'melodiq-registration-update-' . $registration_post->ID; $can_send_payment_email = 'jelentkezett' === $meta['status'] && 'fizetett' !== $meta['payment_status']; ?>
                        <tr class="melodiq-payment-row-<?php echo esc_attr($meta['payment_status']); ?>">
                            <td class="melodiq-registration-select-cell"><input type="checkbox" class="melodiq-registration-checkbox" name="registration_ids[]" value="<?php echo esc_attr($registration_post->ID); ?>" form="melodiq-payment-email-bulk" <?php disabled(!$can_send_payment_email); ?>></td>
                            <td class="melodiq-registration-name-cell">
                                <span class="melodiq-registration-index" style="display:inline-flex;align-items:center;justify-content:center;min-width:28px;height:24px;margin-right:8px;border-radius:999px;background:#27d9f8;color:#031016;font-weight:800;font-size:12px;">
                                    <?php echo esc_html($registration_index); ?>
                                </span>
                                <?php echo esc_html($meta['name']); ?>
                            </td>
                            <td class="melodiq-registration-email-cell"><a href="mailto:<?php echo esc_attr($meta['email']); ?>"><?php echo esc_html($meta['email']); ?></a></td>
                            <td class="melodiq-registration-phone-cell"><?php echo esc_html($meta['phone']); ?></td>
                            <td>
                                <select class="melodiq-payment-select-<?php echo esc_attr($meta['payment_status']); ?>" name="payment_status" form="<?php echo esc_attr($form_id); ?>">
                                    <?php foreach (melodiq_travel_registration_payment_status_options() as $payment_key => $payment_label) : ?>
                                        <option value="<?php echo esc_attr($payment_key); ?>" <?php selected($meta['payment_status'], $payment_key); ?>><?php echo esc_html($payment_label); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                            <td>
                                <?php if ($meta['payment_email_sent_at']) : ?>
                                    <span class="melodiq-payment-email-state melodiq-payment-email-state--sent">✓ <?php echo esc_html(mysql2date('m.d. H:i', $meta['payment_email_sent_at'])); ?></span>
                                <?php else : ?>
                                    <span class="melodiq-payment-email-state">✉️ 0</span>
                                <?php endif; ?>
                            </td>
                            <td class="melodiq-email-log-cell">
                                <?php if ($email_log) : ?>
                                    <button class="melodiq-email-log-toggle" type="button">📨 <?php echo esc_html(count($email_log)); ?> email ▼</button>
                                    <div class="melodiq-email-log-details">
                                        <?php foreach (array_reverse($email_log) as $log_item) : ?>
                                            <p><strong><?php echo esc_html(!empty($log_item['date']) ? mysql2date('Y.m.d. H:i', $log_item['date']) : '-'); ?></strong><br><?php echo esc_html(melodiq_travel_registration_email_log_type_label(isset($log_item['type']) ? $log_item['type'] : '')); ?><br><?php echo esc_html(melodiq_travel_registration_email_log_status_label(isset($log_item['status']) ? $log_item['status'] : '')); ?></p>
                                        <?php endforeach; ?>
                                    </div>
                                <?php else : ?>
                                    <span class="melodiq-email-log-pill">0 email</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo esc_html($meta['registered_at'] ? mysql2date('Y.m.d. H:i', $meta['registered_at']) : get_the_date('Y.m.d. H:i', $registration_post)); ?></td>
                            <td><span class="melodiq-badge melodiq-badge-source-<?php echo esc_attr($meta['source']); ?>"><?php echo esc_html(melodiq_travel_registration_source_label($meta['source'])); ?></span></td>
                            <td class="melodiq-action-col">
                                <div class="melodiq-row-actions">
                                    <a href="<?php echo esc_url(get_edit_post_link($registration_post->ID)); ?>"><svg class="melodiq-action-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg><?php esc_html_e('Megnyitás', 'melodiq-journey'); ?></a>
                                    <form id="<?php echo esc_attr($form_id); ?>" method="post">
                                        <?php wp_nonce_field('melodiq_travel_registration_update', 'melodiq_travel_registration_update_nonce'); ?>
                                        <input type="hidden" name="melodiq_travel_registration_update_action" value="1">
                                        <input type="hidden" name="registration_id" value="<?php echo esc_attr($registration_post->ID); ?>">
                                        <button class="melodiq-action-save" type="submit"><svg class="melodiq-action-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2Z"/><path d="M17 21v-8H7v8"/><path d="M7 3v5h8"/></svg><?php esc_html_e('Mentés', 'melodiq-journey'); ?></button>
                                    </form>
                                    <form method="post">
                                        <?php wp_nonce_field('melodiq_travel_payment_email_single', 'melodiq_travel_payment_email_nonce'); ?>
                                        <input type="hidden" name="melodiq_travel_payment_email_action" value="single">
                                        <input type="hidden" name="registration_id" value="<?php echo esc_attr($registration_post->ID); ?>">
                                        <button class="melodiq-action-payment" type="submit" onclick="return confirm('<?php echo esc_js(__('Biztosan kiküldöd a fizetési emailt ennek a jelentkezőnek?', 'melodiq-journey')); ?>');" <?php disabled(!$can_send_payment_email); ?>><svg class="melodiq-action-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="5" width="20" height="14" rx="2"/><path d="M2 10h20"/></svg><?php esc_html_e('Fizetési email', 'melodiq-journey'); ?></button>
                                    </form>
                                    <form method="post">
                                        <button class="melodiq-action-mail" type="button"><svg class="melodiq-action-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16v16H4z"/><path d="m22 6-10 7L2 6"/></svg><?php esc_html_e('Email küldése', 'melodiq-journey'); ?></button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        <?php $registration_index++; ?>
                    <?php endforeach; ?>
                <?php else : ?>
                    <tr><td colspan="10"><?php esc_html_e('Nincs jelentkező.', 'melodiq-journey'); ?></td></tr>
                <?php endif; ?>
            </tbody>
        </table>
        </div>
        <script>
            
            document.addEventListener('DOMContentLoaded', function () {
    const selectAll = document.getElementById('melodiq-select-all-registrations');
    const checkboxes = Array.from(document.querySelectorAll('.melodiq-registration-checkbox'));

    if (!selectAll || !checkboxes.length) {
        return;
    }

    selectAll.addEventListener('change', function () {
        checkboxes.forEach(function (checkbox) {
            if (!checkbox.disabled) {
                checkbox.checked = selectAll.checked;
            }
        });
    });

    // Email napló lenyitása
    document.querySelectorAll('.melodiq-email-log-toggle').forEach(function (button) {
        button.addEventListener('click', function () {
            const details = button.nextElementSibling;

            if (details) {
                details.classList.toggle('is-open');
            }
        });
    });

});
        </script>
    </div>
    <?php
}

function melodiq_travel_registration_normalize_csv_value($value) {
    $value = trim((string) $value);
    $value = preg_replace('/^\xEF\xBB\xBF/', '', $value);

    if (function_exists('mb_check_encoding') && !mb_check_encoding($value, 'UTF-8')) {
        $converted = @mb_convert_encoding($value, 'UTF-8', 'Windows-1250,ISO-8859-2,ISO-8859-1');
        if ($converted) {
            $value = $converted;
        }
    }

    return $value;
}

function melodiq_travel_registration_header_key($header) {
    $header = melodiq_travel_registration_normalize_csv_value($header);
    $header = strtolower(remove_accents($header));
    $header = preg_replace('/[^a-z0-9]+/', '_', $header);
    $header = trim($header, '_');
    $map = array(
        'nev'             => 'name',
        'name'            => 'name',
        'email'           => 'email',
        'email_cim'       => 'email',
        'email_address'   => 'email',
        'e_mail'          => 'email',
        'e_mail_cim'      => 'email',
        'e_mail_address'  => 'email',
        'mail'            => 'email',
        'telefonszam'     => 'phone',
        'telefon_szam'    => 'phone',
        'telefon'         => 'phone',
        'phone_number'    => 'phone',
        'phone'           => 'phone',
        'utasok_szama'    => 'passengers',
        'utasok'          => 'passengers',
        'letszam'         => 'passengers',
        'utas'            => 'passengers',
        'passengers'      => 'passengers',
        'megjegyzes'      => 'message',
        'message'         => 'message',
        'admin_megjegyzes'=> 'admin_note',
        'statusz'         => 'status',
        'fizetesi_statusz'=> 'payment_status',
        'datum'           => 'registered_at',
    );

    return isset($map[$header]) ? $map[$header] : $header;
}

function melodiq_travel_registration_handle_csv_import() {
    if (!is_admin() || !current_user_can('manage_options') || !isset($_POST['melodiq_travel_csv_import_action'])) {
        return;
    }

    check_admin_referer('melodiq_travel_csv_import', 'melodiq_travel_csv_import_nonce');
    $travel_id = isset($_POST['travel_id']) ? absint($_POST['travel_id']) : 0;
    $event_id = isset($_POST['event_id']) ? absint($_POST['event_id']) : 0;
    $has_header = !empty($_POST['has_header']);
    $imported = 0;
    $duplicates = 0;
    $errors = 0;
    $seen_registrations = array();
    $skipped_rows = array();

    if (!$travel_id || 'travel' !== get_post_type($travel_id) || empty($_FILES['csv_file']['tmp_name'])) {
        melodiq_travel_registration_admin_notice('error', __('Válassz utazást és CSV fájlt.', 'melodiq-journey'));
        wp_safe_redirect(admin_url('edit.php?post_type=travel&page=melodiq-travel-csv-import'));
        exit;
    }

    $handle = fopen($_FILES['csv_file']['tmp_name'], 'r');

    if (!$handle) {
        melodiq_travel_registration_admin_notice('error', __('Nem sikerült megnyitni a CSV fájlt.', 'melodiq-journey'));
        wp_safe_redirect(admin_url('edit.php?post_type=travel&page=melodiq-travel-csv-import'));
        exit;
    }

    $headers = array('name', 'email', 'phone', 'passengers', 'message');

    if ($has_header) {
        $header_row = fgetcsv($handle, 0, ',');
        if (!$header_row || count($header_row) < 2) {
            rewind($handle);
            $header_row = fgetcsv($handle, 0, ';');
        }

        if ($header_row) {
            $headers = array_map('melodiq_travel_registration_header_key', $header_row);
        }
    }

    $row_number = $has_header ? 1 : 0;

    while (($row = fgetcsv($handle, 0, ',')) !== false) {
        $row_number++;

        if (1 === count($row) && false !== strpos((string) $row[0], ';')) {
            $row = str_getcsv($row[0], ';');
        }

        if (!$row || !array_filter($row)) {
            continue;
        }

        $data = array();
        foreach ($row as $index => $value) {
            $key = isset($headers[$index]) ? $headers[$index] : '';
            if ($key) {
                $data[$key] = melodiq_travel_registration_normalize_csv_value($value);
            }
        }

        $email = isset($data['email']) ? strtolower(sanitize_email($data['email'])) : '';
        $name = isset($data['name']) ? sanitize_text_field($data['name']) : '';
        $duplicate_key = $email . '|' . strtolower(remove_accents($name));

        if (!$email || !is_email($email) || !$name) {
            $errors++;
            $skipped_rows[] = sprintf(__('Sor %1$d: hiányzó név vagy hibás email.', 'melodiq-journey'), $row_number);
            continue;
        }

        if (isset($seen_registrations[$duplicate_key]) || melodiq_travel_registration_find_duplicate($travel_id, $email, $name)) {
            $duplicates++;
            $skipped_rows[] = sprintf(__('Sor %1$d: duplikált jelentkező (%2$s, %3$s).', 'melodiq-journey'), $row_number, $name, $email);
            continue;
        }

        $seen_registrations[$duplicate_key] = true;

        $registration_id = melodiq_travel_registration_create(array(
            'travel_id'       => $travel_id,
            'event_id'        => $event_id,
            'name'            => $name,
            'email'           => $email,
            'phone'           => isset($data['phone']) ? $data['phone'] : '',
            'passengers'      => isset($data['passengers']) ? absint($data['passengers']) : 1,
            'message'         => isset($data['message']) ? $data['message'] : '',
            'admin_note'      => isset($data['admin_note']) ? $data['admin_note'] : '',
            'registered_at'   => isset($data['registered_at']) ? $data['registered_at'] : current_time('mysql'),
            'status'          => isset($data['status']) ? melodiq_travel_registration_normalize_status_key($data['status']) : 'jelentkezett',
            'payment_status'  => isset($data['payment_status']) ? melodiq_travel_registration_normalize_payment_status_key($data['payment_status']) : 'nem_fizetett',
            'source'          => 'google_sheets',
            'allow_shared_email' => true,
            'allow_missing_phone' => true,
        ));

        if (is_wp_error($registration_id)) {
            if ('duplicate' === $registration_id->get_error_code()) {
                $duplicates++;
                $skipped_rows[] = sprintf(__('Sor %1$d: duplikált jelentkező (%2$s, %3$s).', 'melodiq-journey'), $row_number, $name, $email);
            } else {
                $errors++;
                $skipped_rows[] = sprintf(__('Sor %1$d: %2$s', 'melodiq-journey'), $row_number, $registration_id->get_error_message());
            }
        } else {
            $imported++;
        }
    }

    fclose($handle);
    $details = $skipped_rows ? ' ' . sprintf(__('Kihagyott sorok: %s', 'melodiq-journey'), implode(' | ', array_slice($skipped_rows, 0, 8))) : '';
    if (count($skipped_rows) > 8) {
        $details .= ' ' . sprintf(__('További kihagyott sorok: %d.', 'melodiq-journey'), count($skipped_rows) - 8);
    }

    melodiq_travel_registration_admin_notice('success', sprintf(
        __('CSV import kész. Sikeres: %1$d, duplikált kihagyva: %2$d, hibás sor: %3$d.', 'melodiq-journey') . $details,
        $imported,
        $duplicates,
        $errors
    ));
    wp_safe_redirect(admin_url('edit.php?post_type=travel&page=melodiq-travel-csv-import'));
    exit;
}
add_action('admin_init', 'melodiq_travel_registration_handle_csv_import');

function melodiq_travel_registration_import_page() {
    ?>
    <div class="wrap">
        <h1><?php esc_html_e('Utazási CSV import', 'melodiq-journey'); ?></h1>
        <p><?php esc_html_e('Google Sheets exportból tudsz jelentkezőket importálni. A rendszer email + név alapján, utazásonként szűri a duplikációkat.', 'melodiq-journey'); ?></p>
        <form method="post" enctype="multipart/form-data">
            <?php wp_nonce_field('melodiq_travel_csv_import', 'melodiq_travel_csv_import_nonce'); ?>
            <input type="hidden" name="melodiq_travel_csv_import_action" value="1">
            <table class="form-table" role="presentation">
                <tr>
                    <th scope="row"><label for="travel_id"><?php esc_html_e('Utazás', 'melodiq-journey'); ?></label></th>
                    <td>
                        <select id="travel_id" name="travel_id" required>
                            <option value=""><?php esc_html_e('Válassz utazást', 'melodiq-journey'); ?></option>
                            <?php foreach (melodiq_travel_options('travel') as $travel) : ?>
                                <option value="<?php echo esc_attr($travel->ID); ?>"><?php echo esc_html(get_the_title($travel)); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="event_id"><?php esc_html_e('Kapcsolódó event', 'melodiq-journey'); ?></label></th>
                    <td>
                        <select id="event_id" name="event_id">
                            <option value="0"><?php esc_html_e('Nincs kapcsolódó event', 'melodiq-journey'); ?></option>
                            <?php foreach (melodiq_travel_options('event') as $event) : ?>
                                <option value="<?php echo esc_attr($event->ID); ?>"><?php echo esc_html(get_the_title($event)); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="csv_file"><?php esc_html_e('CSV fájl', 'melodiq-journey'); ?></label></th>
                    <td><input id="csv_file" type="file" name="csv_file" accept=".csv,text/csv" required></td>
                </tr>
                <tr>
                    <th scope="row"><?php esc_html_e('Fejléc sor', 'melodiq-journey'); ?></th>
                    <td><label><input type="checkbox" name="has_header" value="1" checked> <?php esc_html_e('Az első sor fejléc', 'melodiq-journey'); ?></label></td>
                </tr>
            </table>
            <?php submit_button(__('Import indítása', 'melodiq-journey')); ?>
        </form>
    </div>
    <?php
}

function melodiq_travel_registration_admin_columns($columns) {
    return array(
        'cb'             => isset($columns['cb']) ? $columns['cb'] : '',
        'title'          => __('Név', 'melodiq-journey'),
        'email'          => __('Email', 'melodiq-journey'),
        'phone'          => __('Telefonszám', 'melodiq-journey'),
        'travel'         => __('Utazás', 'melodiq-journey'),
        'status'         => __('Státusz', 'melodiq-journey'),
        'payment_status' => __('Fizetés', 'melodiq-journey'),
        'source'         => __('Forrás', 'melodiq-journey'),
        'registered_at'  => __('Dátum', 'melodiq-journey'),
    );
}
add_filter('manage_travel_registration_posts_columns', 'melodiq_travel_registration_admin_columns');

function melodiq_travel_registration_admin_column_content($column, $post_id) {
    $meta = melodiq_travel_registration_meta($post_id);

    if ('email' === $column) {
        echo '<a href="mailto:' . esc_attr($meta['email']) . '">' . esc_html($meta['email']) . '</a>';
    } elseif ('phone' === $column) {
        echo esc_html($meta['phone']);
    } elseif ('travel' === $column) {
        echo $meta['travel_id'] ? esc_html(get_the_title($meta['travel_id'])) : '';
    } elseif ('status' === $column) {
        echo esc_html(melodiq_travel_registration_status_label($meta['status']));
    } elseif ('payment_status' === $column) {
        echo esc_html(melodiq_travel_registration_payment_status_label($meta['payment_status']));
    } elseif ('source' === $column) {
        echo esc_html(melodiq_travel_registration_source_label($meta['source']));
    } elseif ('registered_at' === $column) {
        echo esc_html($meta['registered_at'] ? mysql2date('Y.m.d. H:i', $meta['registered_at']) : get_the_date('Y.m.d. H:i', $post_id));
    }
}
add_action('manage_travel_registration_posts_custom_column', 'melodiq_travel_registration_admin_column_content', 10, 2);

/**
 * Hide Status and Visibility controls in the Publish box for travel_registration post type.
 */
function melodiq_customize_travel_registration_submitbox() {
    global $post;

    if (!$post || 'travel_registration' !== $post->post_type) {
        return;
    }

    echo '<style>
    #visibility,#visibility-span,#post-status-display,.misc-pub-post-status,.misc-pub-visibility,#minor-publishing-actions,#preview-action,.edit-post-status,.edit-visibility,.save-post-status,.save-visibility{display:none!important;}
    </style>';
}
add_action('post_submitbox_misc_actions', 'melodiq_customize_travel_registration_submitbox');