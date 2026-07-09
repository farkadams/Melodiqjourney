<?php
$contact_errors = array();
$contact_notice = '';
$contact_values = array(
    'name'    => '',
    'email'   => '',
    'topic'   => '',
    'message' => '',
);
$contact_topics = array(
    'Esemény információ',
    'Partnerség',
    'Sajtó',
    'Journey Club',
    'Egyéb',
);

if (isset($_POST['melodiq_contact_action']) && 'send' === sanitize_key(wp_unslash($_POST['melodiq_contact_action']))) {
    if (!isset($_POST['melodiq_contact_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['melodiq_contact_nonce'])), 'melodiq_contact_send')) {
        $contact_errors[] = __('Az üzenetküldés lejárt. Próbáld újra.', 'melodiq-journey');
    } elseif (!empty($_POST['melodiq_contact_website'])) {
        $contact_errors[] = __('Nem sikerült elküldeni az üzenetet. Próbáld újra.', 'melodiq-journey');
    } else {
        $contact_values['name'] = isset($_POST['melodiq_contact_name']) ? sanitize_text_field(wp_unslash($_POST['melodiq_contact_name'])) : '';
        $contact_values['email'] = isset($_POST['melodiq_contact_email']) ? sanitize_email(wp_unslash($_POST['melodiq_contact_email'])) : '';
        $contact_values['topic'] = isset($_POST['melodiq_contact_topic']) ? sanitize_text_field(wp_unslash($_POST['melodiq_contact_topic'])) : '';
        $contact_values['message'] = isset($_POST['melodiq_contact_message']) ? sanitize_textarea_field(wp_unslash($_POST['melodiq_contact_message'])) : '';

        if (!$contact_values['name']) {
            $contact_errors[] = __('Add meg a neved.', 'melodiq-journey');
        }

        if (!$contact_values['email'] || !is_email($contact_values['email'])) {
            $contact_errors[] = __('Adj meg egy érvényes email címet.', 'melodiq-journey');
        }

        if (!$contact_values['topic'] || !in_array($contact_values['topic'], $contact_topics, true)) {
            $contact_errors[] = __('Válassz témát.', 'melodiq-journey');
        }

        if (strlen($contact_values['message']) < 8) {
            $contact_errors[] = __('Írj egy rövid üzenetet.', 'melodiq-journey');
        }

        if (!$contact_errors) {
            $sent = melodiq_contact_send_message(
                $contact_values['name'],
                $contact_values['email'],
                $contact_values['topic'],
                $contact_values['message']
            );

            if ($sent) {
                $contact_notice = __('Köszönjük, megkaptuk az üzeneted. Hamarosan válaszolunk.', 'melodiq-journey');
                $contact_values = array(
                    'name'    => '',
                    'email'   => '',
                    'topic'   => '',
                    'message' => '',
                );
            } else {
                $contact_errors[] = __('Nem sikerült elküldeni az üzenetet. Próbáld újra később.', 'melodiq-journey');
            }
        }
    }
}

get_header();
?>

<main class="site-main inner-page account-page contact-page">
    <div class="container account-shell contact-shell">
        <section class="account-hero contact-hero" aria-labelledby="contact-title">
            <h1 id="contact-title"><?php esc_html_e('Kapcsolat', 'melodiq-journey'); ?></h1>
            <p><?php esc_html_e('Írj nekünk esemény, partnerség, sajtó vagy közösségi megkeresés miatt. A leveled a Melodiq Journey csapatához érkezik.', 'melodiq-journey'); ?></p>
        </section>

        <?php if ($contact_notice) : ?>
            <div class="account-message account-message-success"><?php echo esc_html($contact_notice); ?></div>
        <?php endif; ?>

        <?php if ($contact_errors) : ?>
            <div class="account-message account-message-error">
                <?php foreach ($contact_errors as $contact_error) : ?>
                    <p><?php echo esc_html($contact_error); ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <div class="contact-grid">
            <section class="account-panel contact-panel" aria-labelledby="contact-form-title">
                <h2 id="contact-form-title"><?php esc_html_e('Üzenet küldése', 'melodiq-journey'); ?></h2>
                <form class="account-form contact-form" action="<?php echo esc_url(home_url('/kapcsolat/')); ?>" method="post">
                    <?php wp_nonce_field('melodiq_contact_send', 'melodiq_contact_nonce'); ?>
                    <input type="hidden" name="melodiq_contact_action" value="send">
                    <label class="contact-honeypot" for="melodiq_contact_website"><?php esc_html_e('Weboldal', 'melodiq-journey'); ?></label>
                    <input class="contact-honeypot" id="melodiq_contact_website" type="text" name="melodiq_contact_website" tabindex="-1" autocomplete="off">

                    <label for="melodiq_contact_name"><?php esc_html_e('Név', 'melodiq-journey'); ?></label>
                    <input id="melodiq_contact_name" type="text" name="melodiq_contact_name" value="<?php echo esc_attr($contact_values['name']); ?>" autocomplete="name" required>

                    <label for="melodiq_contact_email"><?php esc_html_e('Email', 'melodiq-journey'); ?></label>
                    <input id="melodiq_contact_email" type="email" name="melodiq_contact_email" value="<?php echo esc_attr($contact_values['email']); ?>" autocomplete="email" required>

                    <label for="melodiq_contact_topic"><?php esc_html_e('Téma', 'melodiq-journey'); ?></label>
                    <select id="melodiq_contact_topic" name="melodiq_contact_topic" required>
                        <option value=""><?php esc_html_e('Válassz témát', 'melodiq-journey'); ?></option>
                        <?php
                        foreach ($contact_topics as $topic) :
                            ?>
                            <option value="<?php echo esc_attr($topic); ?>" <?php selected($contact_values['topic'], $topic); ?>><?php echo esc_html($topic); ?></option>
                        <?php endforeach; ?>
                    </select>

                    <label for="melodiq_contact_message"><?php esc_html_e('Üzenet', 'melodiq-journey'); ?></label>
                    <textarea id="melodiq_contact_message" name="melodiq_contact_message" rows="7" required><?php echo esc_textarea($contact_values['message']); ?></textarea>

                    <button class="button button-primary" type="submit"><?php esc_html_e('Üzenet küldése', 'melodiq-journey'); ?></button>
                </form>
            </section>

            <aside class="account-panel contact-info" aria-label="<?php esc_attr_e('Kapcsolati adatok', 'melodiq-journey'); ?>">
                <p class="section-kicker"><?php esc_html_e('Email', 'melodiq-journey'); ?></p>
                <h2><?php esc_html_e('info@melodiqjourney.hu', 'melodiq-journey'); ?></h2>
                <p><?php esc_html_e('Általában eseményekkel, együttműködésekkel és közösségi kérdésekkel kapcsolatban válaszolunk.', 'melodiq-journey'); ?></p>

                <div class="contact-info-list">
                    <div>
                        <span><?php esc_html_e('Események', 'melodiq-journey'); ?></span>
                        <strong><?php esc_html_e('Program, jegy, helyszín', 'melodiq-journey'); ?></strong>
                    </div>
                    <div>
                        <span><?php esc_html_e('Partnerek', 'melodiq-journey'); ?></span>
                        <strong><?php esc_html_e('Brand, klub, szervező', 'melodiq-journey'); ?></strong>
                    </div>
                    <div>
                        <span><?php esc_html_e('Közösség', 'melodiq-journey'); ?></span>
                        <strong><?php esc_html_e('Journey Club és fiók', 'melodiq-journey'); ?></strong>
                    </div>
                </div>
            </aside>
        </div>
    </div>
</main>

<?php
get_footer();
