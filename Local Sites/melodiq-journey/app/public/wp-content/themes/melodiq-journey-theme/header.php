

<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<header class="site-header">
    <div class="container">
        <div class="logo">
            <?php
            if (has_custom_logo()) {
                the_custom_logo();
            } else {
                ?>
                <a href="<?php echo esc_url(home_url('/')); ?>">
                    Melodiq Journey
                </a>
                <?php
            }
            ?>
        </div>

        <input class="nav-toggle" type="checkbox" id="nav-toggle" aria-label="Menü megnyitása">
        <label class="nav-toggle-button" for="nav-toggle" aria-hidden="true">
            <span></span>
            <span></span>
            <span></span>
        </label>

        <nav class="main-navigation" aria-label="<?php esc_attr_e('Fő navigáció', 'melodiq-journey'); ?>">
            <?php
            wp_nav_menu(array(
                'theme_location' => 'primary',
                'container'      => false,
                'menu_class'     => 'primary-menu',
                'fallback_cb'    => 'melodiq_primary_menu_fallback',
            ));
            ?>
        </nav>

        <div class="header-right">
            <a class="social-link" href="https://www.instagram.com/melodiq.journey/" target="_blank" rel="noopener noreferrer" aria-label="Instagram">
                <svg viewBox="0 0 24 24" aria-hidden="true">
                    <rect x="3" y="3" width="18" height="18" rx="5"></rect>
                    <circle cx="12" cy="12" r="4"></circle>
                    <circle cx="17.5" cy="6.5" r="1"></circle>
                </svg>
            </a>
            <a class="social-link" href="https://www.facebook.com/share/g/1GUWWZVtQ3/?mibextid=wwXIfr" target="_blank" rel="noopener noreferrer" aria-label="Facebook">
                <svg viewBox="0 0 24 24" aria-hidden="true">
                    <path d="M14 8h3V4h-3c-3 0-5 2-5 5v3H6v4h3v5h4v-5h3l1-4h-4V9c0-.6.4-1 1-1Z"></path>
                </svg>
            </a>
            <a class="social-link" href="https://www.tiktok.com/@melodiq.journey" target="_blank" rel="noopener noreferrer" aria-label="TikTok">
                <svg viewBox="0 0 24 24" aria-hidden="true">
                    <path d="M14 4v10.2a4.2 4.2 0 1 1-4.2-4.2c.4 0 .8.1 1.2.2v3.2a1.5 1.5 0 1 0 1 1.4V4h2Z"></path>
                    <path d="M14 4c.5 3 2.4 4.9 5 5.2v3.1c-2-.1-3.8-.8-5-2.1V4Z"></path>
                </svg>
            </a>
            <a class="login-button" href="<?php echo esc_url(home_url('/fiokom/')); ?>">
                <svg viewBox="0 0 24 24" aria-hidden="true">
                    <path d="M20 21a8 8 0 0 0-16 0"></path>
                    <circle cx="12" cy="7" r="4"></circle>
                </svg>
                <span><?php echo is_user_logged_in() ? esc_html__('Fiókom', 'melodiq-journey') : esc_html__('Belépés', 'melodiq-journey'); ?></span>
            </a>
        </div>
    </div>
</header>
