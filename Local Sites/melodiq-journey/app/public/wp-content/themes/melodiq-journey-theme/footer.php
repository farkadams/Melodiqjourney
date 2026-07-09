
<footer class="site-footer">
    <div class="container">
        <div class="footer-main">
            <div class="footer-brand">
                <a class="footer-logo" href="<?php echo esc_url(home_url('/')); ?>">
                    Melodiq Journey
                </a>
                <p>Melodic techno estek, közösségi élmények és válogatott zenei utazások Magyarországon.</p>
            </div>

            <div class="footer-links">
                <div class="footer-column">
                    <h2>Oldalak</h2>
                    <?php
                    wp_nav_menu(array(
                        'theme_location' => 'primary',
                        'container'      => false,
                        'fallback_cb'    => false,
                        'depth'          => 1,
                    ));
                    ?>
                </div>

                <div class="footer-column">
                    <h2>Közösség</h2>
                    <ul>
                        <li><a href="#">Instagram</a></li>
                        <li><a href="#">Facebook</a></li>
                        <li><a href="#">TikTok</a></li>
                    </ul>
                </div>
            </div>
        </div>

        <div class="footer-bottom">
            <p>&copy; <?php echo esc_html(date('Y')); ?> Melodiq Journey. Minden jog fenntartva.</p>
            <a href="mailto:info@melodiqjourney.hu">info@melodiqjourney.hu</a>
        </div>
    </div>
</footer>

<?php wp_footer(); ?>
</body>
</html>
