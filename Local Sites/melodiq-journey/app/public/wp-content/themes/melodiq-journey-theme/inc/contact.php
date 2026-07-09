<?php

/**
 * Contact page helpers.
 */

function melodiq_ensure_contact_page() {
    $existing_page = get_page_by_path('kapcsolat');

    if (!$existing_page) {
        wp_insert_post(array(
            'post_title'   => 'Kapcsolat',
            'post_name'    => 'kapcsolat',
            'post_status'  => 'publish',
            'post_type'    => 'page',
            'post_content' => '',
        ));
    }
}
add_action('init', 'melodiq_ensure_contact_page', 42);

function melodiq_contact_recipient_email() {
    return 'info@melodiqjourney.hu';
}

function melodiq_contact_send_message($name, $email, $topic, $message) {
    $subject = sprintf(
        /* translators: %s: contact topic */
        __('Kapcsolat: %s', 'melodiq-journey'),
        $topic
    );

    $body  = '<div style="font-family:Inter,Arial,Helvetica,sans-serif;color:#111827;font-size:15px;line-height:1.6;">';
    $body .= '<h2 style="margin:0 0 18px;color:#111827;">Új kapcsolat üzenet</h2>';
    $body .= '<p><strong>Név:</strong> ' . esc_html($name) . '</p>';
    $body .= '<p><strong>Email:</strong> ' . esc_html($email) . '</p>';
    $body .= '<p><strong>Téma:</strong> ' . esc_html($topic) . '</p>';
    $body .= '<hr style="border:0;border-top:1px solid #d8dee6;margin:20px 0;">';
    $body .= wpautop(esc_html($message));
    $body .= '</div>';

    $headers = array(
        'Content-Type: text/html; charset=UTF-8',
        'From: Melodiq Journey <info@melodiqjourney.hu>',
        'Reply-To: ' . $name . ' <' . $email . '>',
    );

    return wp_mail(melodiq_contact_recipient_email(), $subject, $body, $headers);
}
