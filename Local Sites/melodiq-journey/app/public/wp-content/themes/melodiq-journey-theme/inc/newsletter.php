<?php

/**
 * Newsletter signups.
 */

function melodiq_register_newsletter_post_type() {
    register_post_type('newsletter_signup', array(
        'labels' => array(
            'name'          => __('Hírlevél feliratkozók', 'melodiq-journey'),
            'singular_name' => __('Hírlevél feliratkozó', 'melodiq-journey'),
            'menu_name'     => __('Hírlevél', 'melodiq-journey'),
        ),
        'public'              => false,
        'show_ui'             => true,
        'show_in_menu'        => true,
        'menu_icon'           => 'dashicons-email-alt2',
        'capability_type'     => 'post',
        'supports'            => array('title'),
        'exclude_from_search' => true,
    ));
}
add_action('init', 'melodiq_register_newsletter_post_type');

function melodiq_ensure_newsletter_unsubscribe_page() {
    $existing_page = get_page_by_path('hirlevel-leiratkozas');

    if (!$existing_page) {
        wp_insert_post(array(
            'post_title'   => 'Hírlevél leiratkozás',
            'post_name'    => 'hirlevel-leiratkozas',
            'post_status'  => 'publish',
            'post_type'    => 'page',
            'post_content' => '',
        ));
    }
}
add_action('init', 'melodiq_ensure_newsletter_unsubscribe_page', 41);

function melodiq_newsletter_get_signup_by_email($email) {
    $existing = get_posts(array(
        'post_type'      => 'newsletter_signup',
        'post_status'    => 'private',
        'posts_per_page' => 1,
        'fields'         => 'ids',
        'meta_query'     => array(
            array(
                'key'   => '_melodiq_newsletter_email',
                'value' => $email,
            ),
        ),
    ));

    return !empty($existing) ? (int) $existing[0] : 0;
}

function melodiq_newsletter_generate_token() {
    do {
        $token = wp_generate_password(32, false, false);
        $existing = get_posts(array(
            'post_type'      => 'newsletter_signup',
            'post_status'    => 'private',
            'posts_per_page' => 1,
            'fields'         => 'ids',
            'meta_query'     => array(
                array(
                    'key'   => '_melodiq_newsletter_token',
                    'value' => $token,
                ),
            ),
        ));
    } while (!empty($existing));

    return $token;
}

function melodiq_newsletter_unsubscribe_url($signup_id) {
    $token = get_post_meta($signup_id, '_melodiq_newsletter_token', true);

    if (!$token) {
        return '';
    }

    return add_query_arg('token', rawurlencode($token), home_url('/hirlevel-leiratkozas/'));
}

function melodiq_newsletter_logo_url() {
    $custom_logo_id = get_theme_mod('custom_logo');

    if (!$custom_logo_id) {
        return '';
    }

    $logo = wp_get_attachment_image_src($custom_logo_id, 'full');

    return $logo ? $logo[0] : '';
}

function melodiq_newsletter_redirect($status) {
    $redirect_url = wp_get_referer() ? wp_get_referer() : home_url('/');
    $redirect_url = remove_query_arg('newsletter', $redirect_url);
    $redirect_url = add_query_arg('newsletter', $status, $redirect_url);

    wp_safe_redirect($redirect_url . '#newsletter');
    exit;
}

function melodiq_handle_newsletter_signup() {
    if (!isset($_POST['melodiq_newsletter_action']) || 'signup' !== sanitize_key(wp_unslash($_POST['melodiq_newsletter_action']))) {
        return;
    }

    if (!isset($_POST['melodiq_newsletter_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['melodiq_newsletter_nonce'])), 'melodiq_newsletter_signup')) {
        melodiq_newsletter_redirect('error');
    }

    $email = isset($_POST['melodiq_newsletter_email']) ? sanitize_email(wp_unslash($_POST['melodiq_newsletter_email'])) : '';

    if (!$email || !is_email($email)) {
        melodiq_newsletter_redirect('invalid');
    }

    $existing_signup_id = melodiq_newsletter_get_signup_by_email($email);

    if ($existing_signup_id) {
        $status = get_post_meta($existing_signup_id, '_melodiq_newsletter_status', true);

        if (!$status) {
            update_post_meta($existing_signup_id, '_melodiq_newsletter_status', 'active');
        }

        if (!get_post_meta($existing_signup_id, '_melodiq_newsletter_token', true)) {
            update_post_meta($existing_signup_id, '_melodiq_newsletter_token', melodiq_newsletter_generate_token());
        }

        if ('unsubscribed' === $status) {
            update_post_meta($existing_signup_id, '_melodiq_newsletter_status', 'active');
            update_post_meta($existing_signup_id, '_melodiq_newsletter_resubscribed_at', current_time('mysql'));
            update_post_meta($existing_signup_id, '_melodiq_newsletter_source', isset($_POST['melodiq_newsletter_source']) ? sanitize_text_field(wp_unslash($_POST['melodiq_newsletter_source'])) : 'site');

            melodiq_newsletter_redirect('success');
        }

        melodiq_newsletter_redirect('exists');
    }

    $signup_id = wp_insert_post(array(
        'post_title'  => $email,
        'post_type'   => 'newsletter_signup',
        'post_status' => 'private',
    ));

    if (is_wp_error($signup_id) || !$signup_id) {
        melodiq_newsletter_redirect('error');
    }

    update_post_meta($signup_id, '_melodiq_newsletter_email', $email);
    update_post_meta($signup_id, '_melodiq_newsletter_source', isset($_POST['melodiq_newsletter_source']) ? sanitize_text_field(wp_unslash($_POST['melodiq_newsletter_source'])) : 'site');
    update_post_meta($signup_id, '_melodiq_newsletter_ip', isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR'])) : '');
    update_post_meta($signup_id, '_melodiq_newsletter_status', 'active');
    update_post_meta($signup_id, '_melodiq_newsletter_token', melodiq_newsletter_generate_token());

    melodiq_newsletter_redirect('success');
}
add_action('template_redirect', 'melodiq_handle_newsletter_signup');

function melodiq_newsletter_active_signup_ids() {
    return get_posts(array(
        'post_type'      => 'newsletter_signup',
        'post_status'    => 'private',
        'posts_per_page' => -1,
        'fields'         => 'ids',
        'orderby'        => 'date',
        'order'          => 'DESC',
        'meta_query'     => array(
            'relation' => 'OR',
            array(
                'key'     => '_melodiq_newsletter_status',
                'value'   => 'unsubscribed',
                'compare' => '!=',
            ),
            array(
                'key'     => '_melodiq_newsletter_status',
                'compare' => 'NOT EXISTS',
            ),
        ),
    ));
}

function melodiq_newsletter_style_content_links($html) {
    return preg_replace_callback('/<a\b([^>]*)>/i', function($matches) {
        $attributes = $matches[1];
        $link_style = 'color:#2ed8f3;font-weight:700;';

        if (preg_match('/style=(["\'])(.*?)\1/i', $attributes, $style_match)) {
            $existing_style = trim($style_match[2]);

            if ($existing_style && ';' !== substr($existing_style, -1)) {
                $existing_style .= ';';
            }

            $updated_style = 'style=' . $style_match[1] . $existing_style . $link_style . $style_match[1];
            $attributes = str_replace($style_match[0], $updated_style, $attributes);
        } else {
            $attributes .= ' style="' . $link_style . '"';
        }

        return '<a' . $attributes . '>';
    }, $html);
}

function melodiq_newsletter_normalize_content_colors($html) {
    $html = preg_replace('/\scolor=(["\']).*?\1/i', '', $html);

    return preg_replace_callback('/\sstyle=(["\'])(.*?)\1/i', function($matches) {
        $quote = $matches[1];
        $styles = explode(';', $matches[2]);
        $kept_styles = array();

        foreach ($styles as $style) {
            $style = trim($style);

            if (!$style) {
                continue;
            }

            $property = strtolower(trim(strtok($style, ':')));

            if (in_array($property, array('color', 'background', 'background-color'), true)) {
                continue;
            }

            $kept_styles[] = $style;
        }

        if (!$kept_styles) {
            return '';
        }

        return ' style=' . $quote . implode(';', $kept_styles) . ';' . $quote;
    }, $html);
}

function melodiq_newsletter_email_body($message, $signup_id = 0) {
    $content = melodiq_newsletter_style_content_links(melodiq_newsletter_normalize_content_colors(wpautop($message)));
    $logo_url = melodiq_newsletter_logo_url();

    $body = '<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="width:100%;background:#f4f6f8;margin:0;padding:26px 14px;font-family:Inter,Arial,Helvetica,sans-serif;">';
    $body .= '<tr><td align="center">';
    $body .= '<table role="presentation" width="620" cellspacing="0" cellpadding="0" style="width:100%;max-width:620px;background:#05090c;border:1px solid #102129;border-radius:16px;overflow:hidden;">';
    $body .= '<tr><td style="padding:24px 30px 16px;text-align:center;background:#071116;">';

    if ($logo_url) {
        $body .= '<img src="' . esc_url($logo_url) . '" width="132" alt="Melodiq Journey" style="display:block;width:132px;max-width:68%;height:auto;margin:0 auto 14px;">';
    } else {
        $body .= '<div style="margin:0 0 14px;color:#ffffff;font-size:21px;line-height:1;font-weight:800;letter-spacing:1px;text-transform:uppercase;">Melodiq Journey</div>';
    }

    $body .= '<div style="color:#2ed8f3;font-size:11px;line-height:1.4;font-weight:800;text-transform:uppercase;">Melodic techno közösség</div>';
    $body .= '</td></tr>';
    $body .= '<tr><td style="padding:26px 30px 22px;color:#f7fbff;font-family:Inter,Arial,Helvetica,sans-serif;font-size:16px;line-height:1.62;">';
    $body .= '<div style="color:#f7fbff;font-family:Inter,Arial,Helvetica,sans-serif;font-size:16px;line-height:1.62;">' . $content . '</div>';
    $body .= '</td></tr>';
    $body .= '<tr><td style="padding:0 30px 26px;">';

    if ($signup_id) {
        $unsubscribe_url = melodiq_newsletter_unsubscribe_url($signup_id);

        if ($unsubscribe_url) {
            $body .= '<div style="border-top:1px solid #16313a;padding-top:18px;color:#9aa7b5;font-family:Inter,Arial,Helvetica,sans-serif;font-size:13px;line-height:1.5;">';
            $body .= esc_html__('Azért kaptad ezt az emailt, mert feliratkoztál a Melodiq Journey hírlevélre.', 'melodiq-journey');
            $body .= '<br>';
            $body .= '<a style="color:#2ed8f3;" href="' . esc_url($unsubscribe_url) . '">' . esc_html__('Leiratkozás a hírlevélről', 'melodiq-journey') . '</a>';
            $body .= '</div>';
        }
    } else {
        $body .= '<div style="border-top:1px solid #16313a;padding-top:18px;color:#9aa7b5;font-family:Inter,Arial,Helvetica,sans-serif;font-size:13px;line-height:1.5;">';
        $body .= esc_html__('Ez egy teszt email. Éles küldésnél ide automatikusan bekerül a személyes leiratkozó link.', 'melodiq-journey');
        $body .= '</div>';
    }

    $body .= '</td></tr>';
    $body .= '</table>';
    $body .= '</td></tr>';
    $body .= '</table>';

    return $body;
}

function melodiq_newsletter_send_email($to, $subject, $message, $signup_id = 0) {
    $headers = array(
        'Content-Type: text/html; charset=UTF-8',
        'From: Melodiq Journey <info@melodiqjourney.hu>',
    );

    return wp_mail($to, $subject, melodiq_newsletter_email_body($message, $signup_id), $headers);
}

function melodiq_newsletter_send_admin_notice($type, $message) {
    set_transient('melodiq_newsletter_send_notice_' . get_current_user_id(), array(
        'type'    => $type,
        'message' => $message,
    ), 60);
}

function melodiq_newsletter_admin_menu() {
    add_submenu_page(
        'edit.php?post_type=newsletter_signup',
        __('Hírlevél küldés', 'melodiq-journey'),
        __('Hírlevél küldés', 'melodiq-journey'),
        'manage_options',
        'melodiq-newsletter-send',
        'melodiq_newsletter_send_page'
    );
}
add_action('admin_menu', 'melodiq_newsletter_admin_menu');

function melodiq_newsletter_admin_assets($hook) {
    if ('newsletter_signup_page_melodiq-newsletter-send' !== $hook) {
        return;
    }

    wp_enqueue_style(
        'melodiq-newsletter-admin',
        get_template_directory_uri() . '/assets/css/admin-newsletter.css',
        array(),
        filemtime(get_template_directory() . '/assets/css/admin-newsletter.css')
    );
}
add_action('admin_enqueue_scripts', 'melodiq_newsletter_admin_assets');

function melodiq_newsletter_handle_admin_send() {
    if (!is_admin() || !current_user_can('manage_options')) {
        return;
    }

    if (!isset($_POST['melodiq_newsletter_send_action'])) {
        return;
    }

    check_admin_referer('melodiq_newsletter_send', 'melodiq_newsletter_send_nonce');

    $send_action = sanitize_key(wp_unslash($_POST['melodiq_newsletter_send_action']));
    $subject = isset($_POST['melodiq_newsletter_subject']) ? sanitize_text_field(wp_unslash($_POST['melodiq_newsletter_subject'])) : '';
    $message = isset($_POST['melodiq_newsletter_message']) ? wp_kses_post(wp_unslash($_POST['melodiq_newsletter_message'])) : '';

    if (!$subject || !$message) {
        melodiq_newsletter_send_admin_notice('error', __('Add meg a tárgyat és az üzenetet is.', 'melodiq-journey'));
        wp_safe_redirect(admin_url('edit.php?post_type=newsletter_signup&page=melodiq-newsletter-send'));
        exit;
    }

    if ('test' === $send_action) {
        $test_email = isset($_POST['melodiq_newsletter_test_email']) ? sanitize_email(wp_unslash($_POST['melodiq_newsletter_test_email'])) : '';

        if (!$test_email || !is_email($test_email)) {
            melodiq_newsletter_send_admin_notice('error', __('Adj meg egy érvényes teszt email címet.', 'melodiq-journey'));
            wp_safe_redirect(admin_url('edit.php?post_type=newsletter_signup&page=melodiq-newsletter-send'));
            exit;
        }

        $sent = melodiq_newsletter_send_email($test_email, '[TESZT] ' . $subject, $message);
        melodiq_newsletter_send_admin_notice($sent ? 'success' : 'error', $sent ? __('A teszt email elküldve.', 'melodiq-journey') : __('A teszt email küldése nem sikerült.', 'melodiq-journey'));
        wp_safe_redirect(admin_url('edit.php?post_type=newsletter_signup&page=melodiq-newsletter-send'));
        exit;
    }

    if ('send' !== $send_action) {
        melodiq_newsletter_send_admin_notice('error', __('Ismeretlen küldési művelet.', 'melodiq-journey'));
        wp_safe_redirect(admin_url('edit.php?post_type=newsletter_signup&page=melodiq-newsletter-send'));
        exit;
    }

    $signup_ids = melodiq_newsletter_active_signup_ids();
    $sent_count = 0;
    $failed_count = 0;

    foreach ($signup_ids as $signup_id) {
        if (!get_post_meta($signup_id, '_melodiq_newsletter_token', true)) {
            update_post_meta($signup_id, '_melodiq_newsletter_token', melodiq_newsletter_generate_token());
        }

        $email = get_post_meta($signup_id, '_melodiq_newsletter_email', true);

        if (!$email || !is_email($email)) {
            $failed_count++;
            continue;
        }

        if (melodiq_newsletter_send_email($email, $subject, $message, $signup_id)) {
            $sent_count++;
        } else {
            $failed_count++;
        }
    }

    update_option('melodiq_newsletter_last_send', array(
        'subject' => $subject,
        'sent'    => $sent_count,
        'failed'  => $failed_count,
        'date'    => current_time('mysql'),
    ), false);

    melodiq_newsletter_send_admin_notice('success', sprintf(
        /* translators: 1: sent count, 2: failed count */
        __('Hírlevél elküldve. Sikeres: %1$d, sikertelen: %2$d.', 'melodiq-journey'),
        $sent_count,
        $failed_count
    ));

    wp_safe_redirect(admin_url('edit.php?post_type=newsletter_signup&page=melodiq-newsletter-send'));
    exit;
}
add_action('admin_init', 'melodiq_newsletter_handle_admin_send');

function melodiq_newsletter_admin_notice() {
    $notice = get_transient('melodiq_newsletter_send_notice_' . get_current_user_id());

    if (!$notice) {
        return;
    }

    delete_transient('melodiq_newsletter_send_notice_' . get_current_user_id());

    $class = 'success' === $notice['type'] ? 'notice notice-success is-dismissible' : 'notice notice-error is-dismissible';
    echo '<div class="' . esc_attr($class) . '"><p>' . esc_html($notice['message']) . '</p></div>';
}
add_action('admin_notices', 'melodiq_newsletter_admin_notice');

function melodiq_newsletter_send_page() {
    $active_count = count(melodiq_newsletter_active_signup_ids());
    $last_send = get_option('melodiq_newsletter_last_send');
    ?>
    <div class="wrap melodiq-newsletter-send">
        <h1><?php esc_html_e('Hírlevél küldés', 'melodiq-journey'); ?></h1>
        <p><?php esc_html_e('Innen tudsz emailt küldeni az aktív hírlevél-feliratkozóknak. A leiratkozó link minden éles email végére automatikusan bekerül.', 'melodiq-journey'); ?></p>

        <div class="melodiq-newsletter-summary">
            <div>
                <strong><?php echo esc_html(number_format_i18n($active_count)); ?></strong>
                <span><?php esc_html_e('aktív címzett', 'melodiq-journey'); ?></span>
            </div>
            <?php if (is_array($last_send) && !empty($last_send['date'])) : ?>
                <div>
                    <strong><?php echo esc_html(mysql2date('Y.m.d. H:i', $last_send['date'])); ?></strong>
                    <span><?php esc_html_e('utolsó küldés', 'melodiq-journey'); ?></span>
                </div>
            <?php endif; ?>
        </div>

        <form method="post" action="<?php echo esc_url(admin_url('edit.php?post_type=newsletter_signup&page=melodiq-newsletter-send')); ?>" class="melodiq-newsletter-form">
            <?php wp_nonce_field('melodiq_newsletter_send', 'melodiq_newsletter_send_nonce'); ?>

            <table class="form-table" role="presentation">
                <tr>
                    <th scope="row"><label for="melodiq-newsletter-subject"><?php esc_html_e('Tárgy', 'melodiq-journey'); ?></label></th>
                    <td><input id="melodiq-newsletter-subject" class="regular-text" type="text" name="melodiq_newsletter_subject" required></td>
                </tr>
                <tr>
                    <th scope="row"><label for="melodiq_newsletter_message_editor"><?php esc_html_e('Üzenet', 'melodiq-journey'); ?></label></th>
                    <td>
                        <?php
                        wp_editor('', 'melodiq_newsletter_message_editor', array(
                            'textarea_name' => 'melodiq_newsletter_message',
                            'textarea_rows' => 12,
                            'media_buttons' => false,
                            'teeny'         => true,
                        ));
                        ?>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="melodiq-newsletter-test-email"><?php esc_html_e('Teszt email', 'melodiq-journey'); ?></label></th>
                    <td>
                        <input id="melodiq-newsletter-test-email" class="regular-text" type="email" name="melodiq_newsletter_test_email" value="<?php echo esc_attr(wp_get_current_user()->user_email); ?>">
                        <p class="description"><?php esc_html_e('Tesztküldésnél nem kerül bele valódi leiratkozó link.', 'melodiq-journey'); ?></p>
                    </td>
                </tr>
            </table>

            <p class="submit">
                <button class="button button-secondary" type="submit" name="melodiq_newsletter_send_action" value="test"><?php esc_html_e('Teszt küldése', 'melodiq-journey'); ?></button>
                <button class="button button-primary" type="submit" name="melodiq_newsletter_send_action" value="send" onclick="return confirm('<?php echo esc_attr__('Biztosan elküldöd a hírlevelet az összes aktív feliratkozónak?', 'melodiq-journey'); ?>');" <?php disabled(0, $active_count); ?>><?php esc_html_e('Küldés aktív feliratkozóknak', 'melodiq-journey'); ?></button>
            </p>
        </form>
    </div>
    <?php
}

function melodiq_newsletter_message() {
    if (!isset($_GET['newsletter'])) {
        return '';
    }

    $status = sanitize_key(wp_unslash($_GET['newsletter']));
    $messages = array(
        'success' => __('Sikeres feliratkozás. Hamarosan jelentkezünk az új eseményekkel.', 'melodiq-journey'),
        'exists'  => __('Ezzel az email címmel már feliratkoztál.', 'melodiq-journey'),
        'invalid' => __('Adj meg egy érvényes email címet.', 'melodiq-journey'),
        'error'   => __('Nem sikerült menteni a feliratkozást. Próbáld újra később.', 'melodiq-journey'),
    );

    if (!isset($messages[$status])) {
        return '';
    }

    $class = 'success' === $status || 'exists' === $status ? 'newsletter-message-success' : 'newsletter-message-error';

    return '<p class="newsletter-message ' . esc_attr($class) . '">' . esc_html($messages[$status]) . '</p>';
}

function melodiq_newsletter_admin_columns($columns) {
    return array(
        'cb'          => isset($columns['cb']) ? $columns['cb'] : '',
        'title'       => __('Email', 'melodiq-journey'),
        'status'      => __('Státusz', 'melodiq-journey'),
        'source'      => __('Forrás', 'melodiq-journey'),
        'signup_date' => __('Dátum', 'melodiq-journey'),
    );
}
add_filter('manage_newsletter_signup_posts_columns', 'melodiq_newsletter_admin_columns');

function melodiq_newsletter_admin_column_content($column, $post_id) {
    if ('status' === $column) {
        $status = get_post_meta($post_id, '_melodiq_newsletter_status', true);
        echo 'unsubscribed' === $status ? esc_html__('Leiratkozott', 'melodiq-journey') : esc_html__('Aktív', 'melodiq-journey');
    }

    if ('source' === $column) {
        echo esc_html(get_post_meta($post_id, '_melodiq_newsletter_source', true));
    }

    if ('signup_date' === $column) {
        echo esc_html(get_the_date('Y.m.d. H:i', $post_id));
    }
}
add_action('manage_newsletter_signup_posts_custom_column', 'melodiq_newsletter_admin_column_content', 10, 2);
