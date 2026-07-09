<?php
$account_errors = array();
$account_notice = '';
$account_action = isset($_POST['melodiq_account_action']) ? sanitize_key(wp_unslash($_POST['melodiq_account_action'])) : '';

if ('login' === $account_action && !is_user_logged_in()) {
    if (!isset($_POST['melodiq_login_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['melodiq_login_nonce'])), 'melodiq_login')) {
        $account_errors[] = __('A belépési kérés lejárt. Próbáld újra.', 'melodiq-journey');
    } else {
        $credentials = array(
            'user_login'    => isset($_POST['melodiq_login_email']) ? sanitize_text_field(wp_unslash($_POST['melodiq_login_email'])) : '',
            'user_password' => isset($_POST['melodiq_login_password']) ? (string) wp_unslash($_POST['melodiq_login_password']) : '',
            'remember'      => isset($_POST['melodiq_login_remember']),
        );
        $user = wp_signon($credentials, is_ssl());

        if (is_wp_error($user)) {
            $account_errors[] = __('Nem sikerült belépni. Ellenőrizd az email címet és a jelszót.', 'melodiq-journey');
        } else {
            wp_safe_redirect(home_url('/fiokom/'));
            exit;
        }
    }
}

if ('register' === $account_action && !is_user_logged_in()) {
    if (!isset($_POST['melodiq_register_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['melodiq_register_nonce'])), 'melodiq_register')) {
        $account_errors[] = __('A regisztrációs kérés lejárt. Próbáld újra.', 'melodiq-journey');
    } else {
        $email = isset($_POST['melodiq_register_email']) ? sanitize_email(wp_unslash($_POST['melodiq_register_email'])) : '';
        $name = isset($_POST['melodiq_register_name']) ? sanitize_text_field(wp_unslash($_POST['melodiq_register_name'])) : '';
        $password = isset($_POST['melodiq_register_password']) ? (string) wp_unslash($_POST['melodiq_register_password']) : '';

        if (!$name) {
            $account_errors[] = __('Add meg a neved.', 'melodiq-journey');
        }

        if (!$email || !is_email($email)) {
            $account_errors[] = __('Adj meg egy érvényes email címet.', 'melodiq-journey');
        } elseif (email_exists($email)) {
            $account_errors[] = __('Ezzel az email címmel már létezik fiók.', 'melodiq-journey');
        }

        if (strlen($password) < 8) {
            $account_errors[] = __('A jelszó legyen legalább 8 karakter.', 'melodiq-journey');
        }

        if (!$account_errors) {
            $email_parts = explode('@', $email);
            $username_base = sanitize_user($email_parts[0], true);
            $username = $username_base ? $username_base : 'melodiq_user';
            $suffix = 1;

            while (username_exists($username)) {
                $username = $username_base . $suffix;
                $suffix++;
            }

            $user_id = wp_create_user($username, $password, $email);

            if (is_wp_error($user_id)) {
                $account_errors[] = __('Nem sikerült létrehozni a fiókot. Próbáld újra később.', 'melodiq-journey');
            } else {
                wp_update_user(array(
                    'ID'           => $user_id,
                    'display_name' => $name,
                    'first_name'   => $name,
                ));
                $registered_user = new WP_User($user_id);
                $registered_user->set_role('subscriber');

                wp_signon(array(
                    'user_login'    => $username,
                    'user_password' => $password,
                    'remember'      => true,
                ), is_ssl());

                wp_safe_redirect(home_url('/fiokom/?registered=1'));
                exit;
            }
        }
    }
}

if (isset($_GET['registered'])) {
    $account_notice = __('Sikeres regisztráció. Üdv a Melodiq Journey közösségben!', 'melodiq-journey');
}

get_header();
?>

<main class="site-main inner-page account-page">
    <div class="container account-shell">
        <section class="account-hero" aria-labelledby="account-title">
            <h1 id="account-title"><?php echo is_user_logged_in() ? esc_html__('Fiókom', 'melodiq-journey') : esc_html__('Belépés és regisztráció', 'melodiq-journey'); ?></h1>
            <p>
                <?php
                echo is_user_logged_in()
                    ? esc_html__('Itt találod a Melodiq Journey fiókod alapadatait és a későbbi Journey Club státuszodat.', 'melodiq-journey')
                    : esc_html__('Hozz létre fiókot most, hogy később innen indulhasson a Journey Club tagságod.', 'melodiq-journey');
                ?>
            </p>
        </section>

        <?php if ($account_notice) : ?>
            <div class="account-message account-message-success"><?php echo esc_html($account_notice); ?></div>
        <?php endif; ?>

        <?php if ($account_errors) : ?>
            <div class="account-message account-message-error">
                <?php foreach ($account_errors as $account_error) : ?>
                    <p><?php echo esc_html($account_error); ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if (is_user_logged_in()) : ?>
            <?php $current_user = wp_get_current_user(); ?>
            <section class="account-panel account-profile">
                <div>
                    <p class="section-kicker">Státusz</p>
                    <h2><?php echo esc_html($current_user->display_name ? $current_user->display_name : $current_user->user_email); ?></h2>
                    <p><?php echo esc_html($current_user->user_email); ?></p>
                </div>
                <div class="account-status-card">
                    <span>Journey Club</span>
                    <strong>Hamarosan</strong>
                    <small><?php esc_html_e('A tagsági funkció később erre a fiókra épül majd.', 'melodiq-journey'); ?></small>
                </div>
                <div class="account-actions">
                    <?php if (current_user_can('edit_posts')) : ?>
                        <a class="button button-primary" href="<?php echo esc_url(admin_url()); ?>"><?php esc_html_e('Admin', 'melodiq-journey'); ?></a>
                    <?php endif; ?>
                    <a class="button button-secondary" href="<?php echo esc_url(wp_logout_url(home_url('/'))); ?>"><?php esc_html_e('Kijelentkezés', 'melodiq-journey'); ?></a>
                </div>
            </section>
        <?php else : ?>
            <div class="account-grid">
                <section class="account-panel" aria-labelledby="login-title">
                    <h2 id="login-title"><?php esc_html_e('Belépés', 'melodiq-journey'); ?></h2>
                    <form class="account-form" action="<?php echo esc_url(home_url('/fiokom/')); ?>" method="post">
                        <?php wp_nonce_field('melodiq_login', 'melodiq_login_nonce'); ?>
                        <input type="hidden" name="melodiq_account_action" value="login">
                        <label for="melodiq_login_email"><?php esc_html_e('Email vagy felhasználónév', 'melodiq-journey'); ?></label>
                        <input id="melodiq_login_email" type="text" name="melodiq_login_email" autocomplete="username" required>
                        <label for="melodiq_login_password"><?php esc_html_e('Jelszó', 'melodiq-journey'); ?></label>
                        <input id="melodiq_login_password" type="password" name="melodiq_login_password" autocomplete="current-password" required>
                        <label class="account-check">
                            <input type="checkbox" name="melodiq_login_remember" value="1">
                            <span><?php esc_html_e('Maradjak belépve', 'melodiq-journey'); ?></span>
                        </label>
                        <button class="button button-primary" type="submit"><?php esc_html_e('Belépés', 'melodiq-journey'); ?></button>
                    </form>
                </section>

                <section class="account-panel" aria-labelledby="register-title">
                    <h2 id="register-title"><?php esc_html_e('Regisztráció', 'melodiq-journey'); ?></h2>
                    <form class="account-form" action="<?php echo esc_url(home_url('/fiokom/')); ?>" method="post">
                        <?php wp_nonce_field('melodiq_register', 'melodiq_register_nonce'); ?>
                        <input type="hidden" name="melodiq_account_action" value="register">
                        <label for="melodiq_register_name"><?php esc_html_e('Név', 'melodiq-journey'); ?></label>
                        <input id="melodiq_register_name" type="text" name="melodiq_register_name" autocomplete="name" required>
                        <label for="melodiq_register_email"><?php esc_html_e('Email', 'melodiq-journey'); ?></label>
                        <input id="melodiq_register_email" type="email" name="melodiq_register_email" autocomplete="email" required>
                        <label for="melodiq_register_password"><?php esc_html_e('Jelszó', 'melodiq-journey'); ?></label>
                        <input id="melodiq_register_password" type="password" name="melodiq_register_password" autocomplete="new-password" minlength="8" required>
                        <p class="account-help"><?php esc_html_e('A fiók még nem Journey Club tagság, de később ehhez kapcsoljuk a tagsági funkciókat.', 'melodiq-journey'); ?></p>
                        <button class="button button-primary" type="submit"><?php esc_html_e('Regisztráció', 'melodiq-journey'); ?></button>
                    </form>
                </section>
            </div>
        <?php endif; ?>
    </div>
</main>

<?php
get_footer();
