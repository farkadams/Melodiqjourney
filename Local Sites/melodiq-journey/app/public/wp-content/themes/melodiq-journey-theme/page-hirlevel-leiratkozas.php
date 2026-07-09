<?php
$unsubscribe_message = '';
$unsubscribe_state = 'error';
$token = isset($_GET['token']) ? sanitize_text_field(wp_unslash($_GET['token'])) : '';

if ($token) {
    $signups = get_posts(array(
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

    if ($signups) {
        $signup_id = (int) $signups[0];
        $status = get_post_meta($signup_id, '_melodiq_newsletter_status', true);

        if ('unsubscribed' === $status) {
            $unsubscribe_state = 'success';
            $unsubscribe_message = __('Ezzel az email címmel már korábban leiratkoztál.', 'melodiq-journey');
        } else {
            update_post_meta($signup_id, '_melodiq_newsletter_status', 'unsubscribed');
            update_post_meta($signup_id, '_melodiq_newsletter_unsubscribed_at', current_time('mysql'));
            $unsubscribe_state = 'success';
            $unsubscribe_message = __('Sikeresen leiratkoztál a Melodiq Journey hírlevélről.', 'melodiq-journey');
        }
    } else {
        $unsubscribe_message = __('Ez a leiratkozó link nem érvényes vagy már nem használható.', 'melodiq-journey');
    }
} else {
    $unsubscribe_message = __('Hiányzik a leiratkozáshoz szükséges azonosító.', 'melodiq-journey');
}

get_header();
?>

<main class="site-main inner-page account-page">
    <div class="container account-shell">
        <section class="account-hero" aria-labelledby="unsubscribe-title">
            <h1 id="unsubscribe-title"><?php esc_html_e('Hírlevél leiratkozás', 'melodiq-journey'); ?></h1>
            <p><?php esc_html_e('Itt tudod kezelni, hogy szeretnél-e email értesítéseket kapni az új eseményekről.', 'melodiq-journey'); ?></p>
        </section>

        <section class="account-panel newsletter-unsubscribe-panel">
            <div class="account-message <?php echo 'success' === $unsubscribe_state ? 'account-message-success' : 'account-message-error'; ?>">
                <?php echo esc_html($unsubscribe_message); ?>
            </div>
            <div class="account-actions">
                <a class="button button-primary" href="<?php echo esc_url(home_url('/')); ?>"><?php esc_html_e('Vissza a főoldalra', 'melodiq-journey'); ?></a>
            </div>
        </section>
    </div>
</main>

<?php
get_footer();
