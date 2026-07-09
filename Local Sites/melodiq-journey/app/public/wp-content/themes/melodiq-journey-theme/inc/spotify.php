<?php

/**
 * Spotify integration for Fresh Releases.
 */

function melodiq_spotify_option_defaults() {
    return array(
        'client_id'     => '',
        'client_secret' => '',
        'playlist_id'   => '',
    );
}

function melodiq_spotify_get_settings() {
    $settings = get_option('melodiq_spotify_settings', array());

    return wp_parse_args(is_array($settings) ? $settings : array(), melodiq_spotify_option_defaults());
}

function melodiq_spotify_sanitize_settings($input) {
    $current = melodiq_spotify_get_settings();
    $input = is_array($input) ? $input : array();
    $settings = array(
        'client_id'     => isset($input['client_id']) ? sanitize_text_field($input['client_id']) : '',
        'client_secret' => isset($input['client_secret']) ? sanitize_text_field($input['client_secret']) : '',
        'playlist_id'   => isset($input['playlist_id']) ? melodiq_spotify_sanitize_playlist_id($input['playlist_id']) : '',
    );

    if ($settings['client_id'] !== $current['client_id']
        || $settings['client_secret'] !== $current['client_secret']
        || $settings['playlist_id'] !== $current['playlist_id']
    ) {
        melodiq_spotify_clear_cache();
    }

    return $settings;
}

function melodiq_spotify_clear_cache() {
    delete_transient('melodiq_spotify_fresh_releases');
}

function melodiq_spotify_sanitize_playlist_id($playlist_id) {
    $playlist_id = trim(sanitize_text_field($playlist_id));

    if (false !== strpos($playlist_id, 'open.spotify.com/playlist/')) {
        $path = wp_parse_url($playlist_id, PHP_URL_PATH);
        $parts = array_values(array_filter(explode('/', (string) $path)));
        $playlist_index = array_search('playlist', $parts, true);

        if (false !== $playlist_index && !empty($parts[$playlist_index + 1])) {
            return sanitize_text_field($parts[$playlist_index + 1]);
        }
    }

    if (false !== strpos($playlist_id, '?')) {
        $playlist_id = strtok($playlist_id, '?');
    }

    return $playlist_id;
}

function melodiq_spotify_register_settings() {
    register_setting(
        'melodiq_spotify_settings_group',
        'melodiq_spotify_settings',
        array(
            'sanitize_callback' => 'melodiq_spotify_sanitize_settings',
            'default'           => melodiq_spotify_option_defaults(),
        )
    );
}
add_action('admin_init', 'melodiq_spotify_register_settings');

function melodiq_spotify_admin_menu() {
    add_options_page(
        'Spotify Settings',
        'Spotify Settings',
        'manage_options',
        'melodiq-spotify',
        'melodiq_spotify_settings_page'
    );
}
add_action('admin_menu', 'melodiq_spotify_admin_menu');

function melodiq_spotify_redirect_uri() {
    return 'https://melodiq-journey.local/wp-admin/admin-post.php?action=melodiq_spotify_callback';
}

function melodiq_spotify_is_connected() {
    $tokens = get_option('melodiq_spotify_tokens', array());

    return !empty($tokens['refresh_token']);
}

function melodiq_spotify_scope() {
    return 'playlist-read-private playlist-read-collaborative';
}

function melodiq_spotify_settings_page() {
    if (!current_user_can('manage_options')) {
        return;
    }

    $settings = melodiq_spotify_get_settings();
    $is_connected = melodiq_spotify_is_connected();
    $connect_url = wp_nonce_url(
        admin_url('admin-post.php?action=melodiq_spotify_connect'),
        'melodiq_spotify_connect'
    );
    $disconnect_url = wp_nonce_url(
        admin_url('admin-post.php?action=melodiq_spotify_disconnect'),
        'melodiq_spotify_disconnect'
    );
    $refresh_url = wp_nonce_url(
        admin_url('admin-post.php?action=melodiq_spotify_refresh'),
        'melodiq_spotify_refresh'
    );
    $list_playlists_url = wp_nonce_url(
        admin_url('admin-post.php?action=melodiq_spotify_list_playlists'),
        'melodiq_spotify_list_playlists'
    );
    $tokens = get_option('melodiq_spotify_tokens', array());
    $expires_at = !empty($tokens['expires_at']) ? (int) $tokens['expires_at'] : 0;
    $token_scope = !empty($tokens['scope']) ? $tokens['scope'] : '';
    $show_spotify_debug = isset($_GET['spotify_debug']) && '1' === sanitize_text_field(wp_unslash($_GET['spotify_debug']));
    $debug_playlists = get_transient('melodiq_spotify_debug_playlists_' . get_current_user_id());
    $debug_playlist_items = get_transient('melodiq_spotify_debug_playlist_items_' . get_current_user_id());
    ?>
    <div class="wrap">
        <h1>Spotify Settings</h1>

        <?php if (isset($_GET['spotify_connected'])) : ?>
            <div class="notice notice-success is-dismissible"><p>Spotify kapcsolat létrejött, a Fresh Releases cache frissült.</p></div>
        <?php endif; ?>
        <?php if (isset($_GET['spotify_disconnected'])) : ?>
            <div class="notice notice-success is-dismissible"><p>Spotify kapcsolat bontva.</p></div>
        <?php endif; ?>
        <?php if (isset($_GET['spotify_refreshed'])) : ?>
            <div class="notice notice-success is-dismissible"><p>Spotify playlist újra lekérve, a Fresh Releases cache frissült.</p></div>
        <?php endif; ?>
        <?php if (isset($_GET['spotify_playlist_selected'])) : ?>
            <div class="notice notice-success is-dismissible"><p>Playlist ID beállítva a kiválasztott Spotify playlist alapján.</p></div>
        <?php endif; ?>
        <?php if (isset($_GET['spotify_playlists_listed'])) : ?>
            <div class="notice notice-success is-dismissible"><p>Saját Spotify playlistek lekérve.</p></div>
        <?php endif; ?>
        <?php if (isset($_GET['spotify_error'])) : ?>
            <div class="notice notice-error is-dismissible">
                <p><strong>Spotify hiba:</strong></p>
                <pre style="white-space: pre-wrap; margin: 0 0 12px;"><?php echo esc_html(sanitize_textarea_field(wp_unslash($_GET['spotify_error']))); ?></pre>
            </div>
        <?php endif; ?>

        <form method="post" action="options.php">
            <?php settings_fields('melodiq_spotify_settings_group'); ?>
            <table class="form-table" role="presentation">
                <tr>
                    <th scope="row"><label for="melodiq_spotify_client_id">Client ID</label></th>
                    <td><input class="regular-text" id="melodiq_spotify_client_id" name="melodiq_spotify_settings[client_id]" type="text" value="<?php echo esc_attr($settings['client_id']); ?>"></td>
                </tr>
                <tr>
                    <th scope="row"><label for="melodiq_spotify_client_secret">Client Secret</label></th>
                    <td><input class="regular-text" id="melodiq_spotify_client_secret" name="melodiq_spotify_settings[client_secret]" type="password" value="<?php echo esc_attr($settings['client_secret']); ?>"></td>
                </tr>
                <tr>
                    <th scope="row"><label for="melodiq_spotify_playlist_id">Playlist ID</label></th>
                    <td><input class="regular-text" id="melodiq_spotify_playlist_id" name="melodiq_spotify_settings[playlist_id]" type="text" value="<?php echo esc_attr($settings['playlist_id']); ?>"></td>
                </tr>
                <tr>
                    <th scope="row">Redirect URI</th>
                    <td>
                        <input class="large-text code" type="text" readonly value="<?php echo esc_attr(melodiq_spotify_redirect_uri()); ?>" onclick="this.select();">
                        <p class="description">Ezt add meg a Spotify Developer Dashboardban Redirect URI-ként.</p>
                    </td>
                </tr>
            </table>
            <?php submit_button('Beállítások mentése'); ?>
        </form>

        <hr>

        <h2>Kapcsolat</h2>
        <p>
            Állapot:
            <strong><?php echo $is_connected ? 'Csatlakoztatva' : 'Nincs csatlakoztatva'; ?></strong>
            <?php if ($is_connected && $expires_at) : ?>
                <br><span class="description">Access token lejárata: <?php echo esc_html(date_i18n('Y. m. d. H:i', $expires_at)); ?>. Lejárat után automatikusan frissül.</span>
            <?php endif; ?>
            <?php if ($is_connected && $token_scope) : ?>
                <br><span class="description">Token scope: <?php echo esc_html($token_scope); ?></span>
            <?php endif; ?>
        </p>
        <p>
            <a class="button button-primary" href="<?php echo esc_url($connect_url); ?>">Connect Spotify</a>
            <?php if ($is_connected) : ?>
                <a class="button" href="<?php echo esc_url($refresh_url); ?>">Playlist újralekérése</a>
                <a class="button" href="<?php echo esc_url($disconnect_url); ?>">Kapcsolat bontása</a>
            <?php endif; ?>
        </p>

        <?php if ($show_spotify_debug) : ?>
            <h2>Debug</h2>
            <p>
                <a class="button" href="<?php echo esc_url(add_query_arg('spotify_debug', '1', $list_playlists_url)); ?>">Saját Spotify playlistek listázása</a>
            </p>

            <?php if (is_array($debug_playlist_items) && isset($debug_playlist_items['items']) && is_array($debug_playlist_items['items'])) : ?>
                <h3>Playlist item debug</h3>
                <p class="description">
                    Endpoint: <code><?php echo esc_html(!empty($debug_playlist_items['endpoint']) ? $debug_playlist_items['endpoint'] : '-'); ?></code><br>
                    HTTP státuszkód: <code><?php echo esc_html(isset($debug_playlist_items['http_code']) ? (string) $debug_playlist_items['http_code'] : '-'); ?></code><br>
                    Playlist ID: <code><?php echo esc_html(!empty($debug_playlist_items['playlist_id']) ? $debug_playlist_items['playlist_id'] : '-'); ?></code><br>
                    Beérkezett itemek száma: <code><?php echo esc_html(isset($debug_playlist_items['total_items']) ? (string) $debug_playlist_items['total_items'] : '0'); ?></code>
                </p>
                <?php if (empty($debug_playlist_items['items'])) : ?>
                    <p class="description">A Spotify API 0 playlist itemet adott vissza.</p>
                <?php endif; ?>
                <?php foreach ($debug_playlist_items['items'] as $debug_item) : ?>
                    <div style="margin: 14px 0; padding: 12px; border: 1px solid #dcdcde; background: #fff;">
                        <p style="margin-top: 0;"><strong>Item #<?php echo esc_html(isset($debug_item['index']) ? (string) $debug_item['index'] : '-'); ?></strong></p>
                        <ul style="list-style: disc; margin-left: 20px;">
                            <li>Item object létezik-e: <strong><?php echo !empty($debug_item['track_exists']) ? 'igen' : 'nem'; ?></strong></li>
                            <li>Track type: <code><?php echo esc_html(!empty($debug_item['track_type']) ? $debug_item['track_type'] : '-'); ?></code></li>
                            <li>Track name: <code><?php echo esc_html(!empty($debug_item['track_name']) ? $debug_item['track_name'] : '-'); ?></code></li>
                            <li>Artists: <code><?php echo esc_html(!empty($debug_item['artists']) ? implode(', ', $debug_item['artists']) : '-'); ?></code></li>
                            <li>Album images: <code><?php echo esc_html(isset($debug_item['album_images_count']) ? (string) $debug_item['album_images_count'] : '0'); ?></code></li>
                            <li>external_urls.spotify: <code><?php echo esc_html(!empty($debug_item['spotify_url']) ? $debug_item['spotify_url'] : '-'); ?></code></li>
                        </ul>
                        <details>
                            <summary>Nyers item JSON</summary>
                            <pre style="white-space: pre-wrap;"><?php echo esc_html(!empty($debug_item['raw']) ? wp_json_encode($debug_item['raw'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) : '{}'); ?></pre>
                        </details>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>

            <?php if (is_array($debug_playlists) && isset($debug_playlists['items']) && is_array($debug_playlists['items'])) : ?>
                <?php if (empty($debug_playlists['items'])) : ?>
                    <p class="description">A Spotify válasz sikeres volt, de nem adott vissza saját playlistet ehhez a felhasználóhoz.</p>
                <?php endif; ?>
                <table class="widefat striped">
                    <thead>
                        <tr>
                            <th>Playlist név</th>
                            <th>Playlist ID</th>
                            <th>Owner display name</th>
                            <th>Owner ID</th>
                            <th>Státusz</th>
                            <th>Művelet</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($debug_playlists['items'] as $playlist) : ?>
                            <?php
                            $playlist_id = !empty($playlist['id']) ? sanitize_text_field($playlist['id']) : '';
                            $select_url = wp_nonce_url(
                                add_query_arg(
                                    array(
                                        'action'      => 'melodiq_spotify_select_playlist',
                                        'playlist_id' => rawurlencode($playlist_id),
                                    ),
                                    admin_url('admin-post.php')
                                ),
                                'melodiq_spotify_select_playlist_' . $playlist_id
                            );
                            ?>
                            <tr>
                                <td><?php echo esc_html(!empty($playlist['name']) ? $playlist['name'] : '-'); ?></td>
                                <td><code><?php echo esc_html($playlist_id ? $playlist_id : '-'); ?></code></td>
                                <td><?php echo esc_html(!empty($playlist['owner_display_name']) ? $playlist['owner_display_name'] : '-'); ?></td>
                                <td><code><?php echo esc_html(!empty($playlist['owner_id']) ? $playlist['owner_id'] : '-'); ?></code></td>
                                <td><?php echo esc_html(!empty($playlist['status']) ? $playlist['status'] : '-'); ?></td>
                                <td>
                                    <?php if ($playlist_id) : ?>
                                        <a class="button button-small" href="<?php echo esc_url($select_url); ?>">Ezt használom</a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php if (!empty($debug_playlists['endpoint'])) : ?>
                    <p class="description">Debug endpoint: <code><?php echo esc_html($debug_playlists['endpoint']); ?></code></p>
                <?php endif; ?>
            <?php endif; ?>
        <?php endif; ?>
    </div>
    <?php
}

function melodiq_spotify_admin_redirect($args = array()) {
    if (isset($_REQUEST['spotify_debug']) && '1' === sanitize_text_field(wp_unslash($_REQUEST['spotify_debug']))) {
        $args['spotify_debug'] = '1';
    }

    wp_safe_redirect(add_query_arg($args, admin_url('options-general.php?page=melodiq-spotify')));
    exit;
}

function melodiq_spotify_connect() {
    if (!current_user_can('manage_options') || !check_admin_referer('melodiq_spotify_connect')) {
        wp_die('Nincs jogosultság.');
    }

    $settings = melodiq_spotify_get_settings();

    if (empty($settings['client_id']) || empty($settings['client_secret']) || empty($settings['playlist_id'])) {
        melodiq_spotify_admin_redirect(array('spotify_error' => 'Előbb töltsd ki és mentsd a Client ID, Client Secret és Playlist ID mezőket.'));
    }

    $state = wp_generate_password(32, false);
    set_transient('melodiq_spotify_oauth_state_' . get_current_user_id(), $state, 10 * MINUTE_IN_SECONDS);

    $auth_url = add_query_arg(
        array(
            'client_id'     => $settings['client_id'],
            'response_type' => 'code',
            'redirect_uri'  => melodiq_spotify_redirect_uri(),
            'scope'         => melodiq_spotify_scope(),
            'state'         => $state,
            'show_dialog'   => 'true',
        ),
        'https://accounts.spotify.com/authorize'
    );

    wp_redirect($auth_url);
    exit;
}
add_action('admin_post_melodiq_spotify_connect', 'melodiq_spotify_connect');

function melodiq_spotify_callback() {
    if (!current_user_can('manage_options')) {
        wp_die('Nincs jogosultság.');
    }

    $expected_state = get_transient('melodiq_spotify_oauth_state_' . get_current_user_id());
    $state = isset($_GET['state']) ? sanitize_text_field(wp_unslash($_GET['state'])) : '';
    $code = isset($_GET['code']) ? sanitize_text_field(wp_unslash($_GET['code'])) : '';
    $error = isset($_GET['error']) ? sanitize_text_field(wp_unslash($_GET['error'])) : '';

    delete_transient('melodiq_spotify_oauth_state_' . get_current_user_id());

    if ($error) {
        melodiq_spotify_admin_redirect(array('spotify_error' => $error));
    }

    if (!$expected_state || !$state || !hash_equals($expected_state, $state) || !$code) {
        melodiq_spotify_admin_redirect(array('spotify_error' => 'Érvénytelen Spotify válasz.'));
    }

    $token_response = melodiq_spotify_request_token(array(
        'grant_type'   => 'authorization_code',
        'code'         => $code,
        'redirect_uri' => melodiq_spotify_redirect_uri(),
    ));

    if (is_wp_error($token_response)) {
        melodiq_spotify_admin_redirect(array('spotify_error' => $token_response->get_error_message()));
    }

    melodiq_spotify_store_tokens($token_response);
    melodiq_spotify_clear_cache();
    $playlist_response = melodiq_spotify_fetch_playlist_tracks(true);

    if (is_wp_error($playlist_response)) {
        melodiq_spotify_admin_redirect(array('spotify_error' => $playlist_response->get_error_message()));
    }

    melodiq_spotify_admin_redirect(array('spotify_connected' => '1'));
}
add_action('admin_post_melodiq_spotify_callback', 'melodiq_spotify_callback');

function melodiq_spotify_disconnect() {
    if (!current_user_can('manage_options') || !check_admin_referer('melodiq_spotify_disconnect')) {
        wp_die('Nincs jogosultság.');
    }

    delete_option('melodiq_spotify_tokens');
    melodiq_spotify_clear_cache();
    melodiq_spotify_admin_redirect(array('spotify_disconnected' => '1'));
}
add_action('admin_post_melodiq_spotify_disconnect', 'melodiq_spotify_disconnect');

function melodiq_spotify_refresh() {
    if (!current_user_can('manage_options') || !check_admin_referer('melodiq_spotify_refresh')) {
        wp_die('Nincs jogosultság.');
    }

    melodiq_spotify_clear_cache();
    $playlist_response = melodiq_spotify_fetch_playlist_tracks(true);

    if (is_wp_error($playlist_response)) {
        melodiq_spotify_admin_redirect(array('spotify_error' => $playlist_response->get_error_message()));
    }

    melodiq_spotify_admin_redirect(array('spotify_refreshed' => '1'));
}
add_action('admin_post_melodiq_spotify_refresh', 'melodiq_spotify_refresh');

function melodiq_spotify_list_playlists() {
    if (!current_user_can('manage_options') || !check_admin_referer('melodiq_spotify_list_playlists')) {
        wp_die('Nincs jogosultság.');
    }

    $access_token = melodiq_spotify_get_access_token();

    if (is_wp_error($access_token)) {
        melodiq_spotify_admin_redirect(array('spotify_error' => $access_token->get_error_message()));
    }

    $endpoint = 'https://api.spotify.com/v1/me/playlists';
    $response = wp_remote_get($endpoint, array(
        'timeout' => 15,
        'headers' => array(
            'Authorization' => 'Bearer ' . trim($access_token),
            'Accept'        => 'application/json',
        ),
    ));

    if (is_wp_error($response)) {
        melodiq_spotify_admin_redirect(array('spotify_error' => $response->get_error_message()));
    }

    $code = wp_remote_retrieve_response_code($response);
    $raw_body = wp_remote_retrieve_body($response);
    $data = json_decode($raw_body, true);

    if ($code < 200 || $code >= 300 || !is_array($data) || !isset($data['items']) || !is_array($data['items'])) {
        melodiq_spotify_admin_redirect(array(
            'spotify_error' => melodiq_spotify_playlist_error_message($code, $endpoint, 'me/playlists', is_array($data) ? $data : array(), $raw_body),
        ));
    }

    $playlists = array();

    foreach ($data['items'] as $playlist) {
        if (empty($playlist['id'])) {
            continue;
        }

        $public = isset($playlist['public']) ? $playlist['public'] : null;

        if (true === $public) {
            $status = 'Public';
        } elseif (false === $public) {
            $status = 'Private';
        } else {
            $status = 'Ismeretlen';
        }

        $playlists[] = array(
            'name'               => !empty($playlist['name']) ? sanitize_text_field($playlist['name']) : '',
            'id'                 => sanitize_text_field($playlist['id']),
            'owner_display_name' => !empty($playlist['owner']['display_name']) ? sanitize_text_field($playlist['owner']['display_name']) : '',
            'owner_id'           => !empty($playlist['owner']['id']) ? sanitize_text_field($playlist['owner']['id']) : '',
            'status'             => $status,
        );
    }

    set_transient(
        'melodiq_spotify_debug_playlists_' . get_current_user_id(),
        array(
            'endpoint' => $endpoint,
            'items'    => $playlists,
        ),
        15 * MINUTE_IN_SECONDS
    );

    melodiq_spotify_admin_redirect(array('spotify_playlists_listed' => '1'));
}
add_action('admin_post_melodiq_spotify_list_playlists', 'melodiq_spotify_list_playlists');

function melodiq_spotify_select_playlist() {
    if (!current_user_can('manage_options')) {
        wp_die('Nincs jogosultság.');
    }

    $playlist_id = isset($_GET['playlist_id']) ? melodiq_spotify_sanitize_playlist_id(wp_unslash($_GET['playlist_id'])) : '';

    if (!$playlist_id || !check_admin_referer('melodiq_spotify_select_playlist_' . $playlist_id)) {
        wp_die('Nincs jogosultság.');
    }

    $settings = melodiq_spotify_get_settings();
    $settings['playlist_id'] = $playlist_id;
    update_option('melodiq_spotify_settings', $settings, false);
    melodiq_spotify_clear_cache();

    melodiq_spotify_admin_redirect(array('spotify_playlist_selected' => '1'));
}
add_action('admin_post_melodiq_spotify_select_playlist', 'melodiq_spotify_select_playlist');

function melodiq_spotify_request_token($body) {
    $settings = melodiq_spotify_get_settings();

    if (empty($settings['client_id']) || empty($settings['client_secret'])) {
        return new WP_Error('melodiq_spotify_missing_credentials', 'Hiányzó Spotify Client ID vagy Client Secret.');
    }

    $response = wp_remote_post('https://accounts.spotify.com/api/token', array(
        'timeout' => 15,
        'headers' => array(
            'Authorization' => 'Basic ' . base64_encode($settings['client_id'] . ':' . $settings['client_secret']),
            'Content-Type'  => 'application/x-www-form-urlencoded',
        ),
        'body' => $body,
    ));

    if (is_wp_error($response)) {
        return $response;
    }

    $code = wp_remote_retrieve_response_code($response);
    $data = json_decode(wp_remote_retrieve_body($response), true);

    if ($code < 200 || $code >= 300 || !is_array($data)) {
        $message = 'A Spotify token kérés sikertelen.';

        if (!empty($data['error_description'])) {
            $message .= ' ' . sanitize_text_field($data['error_description']);
        } elseif (!empty($data['error'])) {
            $message .= ' ' . sanitize_text_field($data['error']);
        }

        return new WP_Error('melodiq_spotify_token_error', $message);
    }

    return $data;
}

function melodiq_spotify_store_tokens($token_response) {
    $current_tokens = get_option('melodiq_spotify_tokens', array());
    $expires_in = isset($token_response['expires_in']) ? (int) $token_response['expires_in'] : HOUR_IN_SECONDS;
    $tokens = array(
        'access_token'  => isset($token_response['access_token']) ? sanitize_text_field($token_response['access_token']) : '',
        'refresh_token' => isset($token_response['refresh_token']) ? sanitize_text_field($token_response['refresh_token']) : (isset($current_tokens['refresh_token']) ? $current_tokens['refresh_token'] : ''),
        'expires_at'    => time() + max(60, $expires_in - 60),
        'scope'         => isset($token_response['scope']) ? sanitize_text_field($token_response['scope']) : (isset($current_tokens['scope']) ? $current_tokens['scope'] : ''),
    );

    update_option('melodiq_spotify_tokens', $tokens, false);
}

function melodiq_spotify_get_access_token() {
    $tokens = get_option('melodiq_spotify_tokens', array());

    if (empty($tokens['refresh_token'])) {
        return new WP_Error('melodiq_spotify_not_connected', 'A Spotify nincs csatlakoztatva.');
    }

    if (!empty($tokens['access_token']) && !empty($tokens['expires_at']) && (int) $tokens['expires_at'] > time()) {
        return $tokens['access_token'];
    }

    $token_response = melodiq_spotify_request_token(array(
        'grant_type'    => 'refresh_token',
        'refresh_token' => $tokens['refresh_token'],
    ));

    if (is_wp_error($token_response)) {
        return $token_response;
    }

    melodiq_spotify_store_tokens($token_response);
    $tokens = get_option('melodiq_spotify_tokens', array());

    return !empty($tokens['access_token']) ? $tokens['access_token'] : new WP_Error('melodiq_spotify_refresh_error', 'Nem sikerült frissíteni a Spotify tokent.');
}

function melodiq_spotify_playlist_error_message($http_code, $endpoint, $playlist_id, $data, $raw_body) {
    $spotify_message = 'Nincs Spotify error message a válaszban.';

    if (!empty($data['error']['message'])) {
        $spotify_message = sanitize_text_field($data['error']['message']);
    } elseif (!empty($data['error_description'])) {
        $spotify_message = sanitize_text_field($data['error_description']);
    } elseif (!empty($data['error'])) {
        $spotify_message = is_array($data['error']) ? wp_json_encode($data['error']) : sanitize_text_field($data['error']);
    }

    $body_for_display = trim((string) $raw_body);

    if ('' === $body_for_display) {
        $body_for_display = 'Üres válasz.';
    }

    return sprintf(
        "A Spotify playlist lekérése sikertelen.\nHTTP státuszkód: %s\nSpotify error message: %s\nEndpoint: %s\nPlaylist ID: %s\nSpotify válasz: %s",
        $http_code ? (string) $http_code : 'ismeretlen',
        $spotify_message,
        $endpoint,
        $playlist_id,
        $body_for_display
    );
}

function melodiq_spotify_store_playlist_items_debug($endpoint, $playlist_id, $http_code, $items) {
    if (!is_user_logged_in()) {
        return;
    }

    $debug_items = array();
    $items = is_array($items) ? array_values($items) : array();
    $sample_items = array_slice($items, 0, 3);

    foreach ($sample_items as $index => $item) {
        $track = !empty($item['item']) && is_array($item['item']) ? $item['item'] : array();
        $artists = array();

        if (!empty($track['artists']) && is_array($track['artists'])) {
            foreach ($track['artists'] as $artist) {
                if (!empty($artist['name'])) {
                    $artists[] = sanitize_text_field($artist['name']);
                }
            }
        }

        $album_images = !empty($track['album']['images']) && is_array($track['album']['images']) ? $track['album']['images'] : array();

        $debug_items[] = array(
            'index'              => $index + 1,
            'track_exists'       => !empty($track),
            'track_type'         => !empty($track['type']) ? sanitize_text_field($track['type']) : '',
            'track_name'         => !empty($track['name']) ? sanitize_text_field($track['name']) : '',
            'artists'            => $artists,
            'album_images_count' => count($album_images),
            'spotify_url'        => !empty($track['external_urls']['spotify']) ? esc_url_raw($track['external_urls']['spotify']) : '',
            'raw'                => $item,
        );
    }

    set_transient(
        'melodiq_spotify_debug_playlist_items_' . get_current_user_id(),
        array(
            'endpoint'    => $endpoint,
            'playlist_id' => $playlist_id,
            'http_code'   => $http_code,
            'total_items' => count($items),
            'items'       => $debug_items,
        ),
        15 * MINUTE_IN_SECONDS
    );
}

function melodiq_spotify_fetch_playlist_tracks($force_refresh = false, $retry_on_unauthorized = true) {
    if (!$force_refresh) {
        $cached_tracks = get_transient('melodiq_spotify_fresh_releases');

        if (is_array($cached_tracks)) {
            return $cached_tracks;
        }
    }

    $settings = melodiq_spotify_get_settings();

    if (empty($settings['playlist_id'])) {
        return new WP_Error('melodiq_spotify_missing_playlist', 'Hiányzó Spotify Playlist ID.');
    }

    $access_token = melodiq_spotify_get_access_token();

    if (is_wp_error($access_token)) {
        return $access_token;
    }

    $endpoint = 'https://api.spotify.com/v1/playlists/' . rawurlencode($settings['playlist_id']) . '/items';

    $response = wp_remote_get($endpoint, array(
        'timeout' => 15,
        'headers' => array(
            'Authorization' => 'Bearer ' . trim($access_token),
            'Accept'        => 'application/json',
        ),
    ));

    if (is_wp_error($response)) {
        return $response;
    }

    if ($retry_on_unauthorized && 401 === wp_remote_retrieve_response_code($response)) {
        $tokens = get_option('melodiq_spotify_tokens', array());
        if (!empty($tokens['refresh_token'])) {
            $tokens['expires_at'] = 0;
            update_option('melodiq_spotify_tokens', $tokens, false);
            return melodiq_spotify_fetch_playlist_tracks(true, false);
        }
    }

    $code = wp_remote_retrieve_response_code($response);
    $raw_body = wp_remote_retrieve_body($response);
    $data = json_decode($raw_body, true);

    if ($code < 200 || $code >= 300 || !is_array($data)) {
        return new WP_Error(
            'melodiq_spotify_playlist_error',
            melodiq_spotify_playlist_error_message($code, $endpoint, $settings['playlist_id'], is_array($data) ? $data : array(), $raw_body)
        );
    }

    if (empty($data['items']) || !is_array($data['items'])) {
        melodiq_spotify_store_playlist_items_debug($endpoint, $settings['playlist_id'], $code, array());

        return new WP_Error(
            'melodiq_spotify_playlist_error',
            melodiq_spotify_playlist_error_message($code, $endpoint, $settings['playlist_id'], $data, $raw_body)
        );
    }

    melodiq_spotify_store_playlist_items_debug($endpoint, $settings['playlist_id'], $code, $data['items']);

    usort($data['items'], function ($first_item, $second_item) {
        $first_added_at = !empty($first_item['added_at']) ? strtotime($first_item['added_at']) : 0;
        $second_added_at = !empty($second_item['added_at']) ? strtotime($second_item['added_at']) : 0;

        return $second_added_at <=> $first_added_at;
    });

    $tracks = array();

    foreach ($data['items'] as $playlist_item) {
        if (empty($playlist_item['item']) || !is_array($playlist_item['item']) || empty($playlist_item['item']['name'])) {
            continue;
        }

        $track = $playlist_item['item'];
        $artists = array();

        if (!empty($track['artists']) && is_array($track['artists'])) {
            foreach ($track['artists'] as $artist) {
                if (!empty($artist['name'])) {
                    $artists[] = $artist['name'];
                }
            }
        }

        $images = !empty($track['album']['images']) && is_array($track['album']['images']) ? $track['album']['images'] : array();
        $image_url = '';

        if (!empty($images)) {
            $selected_image = $images[0];
            $image_url = !empty($selected_image['url']) ? esc_url_raw($selected_image['url']) : '';
        }

        $tracks[] = array(
            'artist'      => $artists ? implode(', ', $artists) : 'Spotify',
            'title'       => $track['name'],
            'date'        => !empty($track['album']['release_date']) ? melodiq_spotify_format_release_date($track['album']['release_date']) : '',
            'date_iso'    => !empty($track['album']['release_date']) ? sanitize_text_field($track['album']['release_date']) : '',
            'cover'       => 'linear-gradient(135deg, #061116 0%, #123843 48%, #27d9f8 130%)',
            'image'       => $image_url,
            'spotify_url' => !empty($track['external_urls']['spotify']) ? esc_url_raw($track['external_urls']['spotify']) : '',
        );
    }

    $tracks = array_slice($tracks, 0, 12);

    if (empty($tracks)) {
        return new WP_Error('melodiq_spotify_empty_playlist', 'A Spotify playlistben nem található megjeleníthető szám.');
    }

    set_transient('melodiq_spotify_fresh_releases', $tracks, HOUR_IN_SECONDS);

    return $tracks;
}

function melodiq_spotify_format_release_date($release_date) {
    $release_date = sanitize_text_field($release_date);
    $timestamp = strtotime($release_date);

    if (!$timestamp) {
        return $release_date;
    }

    return date_i18n('Y. m. d.', $timestamp);
}

function melodiq_fresh_release_fallback_data() {
    $spotify_url = 'https://open.spotify.com/playlist/0KRLWArpyzKGmN9C0tJXzg?si=VKL29UjERBG74IjD_V_Maw&pi=lOVndrRdTcS1u';
    $image_base = get_template_directory_uri() . '/assets/images/releases/';

    return array(
        array(
            'artist'      => 'Miss Monique',
            'title'       => 'Eclipse Signal',
            'date'        => '2026. 07. 05.',
            'date_iso'    => '2026-07-05',
            'cover'       => 'linear-gradient(135deg, #061116 0%, #123843 48%, #27d9f8 130%)',
            'image'       => $image_base . 'eclipse-signal.svg',
            'spotify_url' => $spotify_url,
        ),
        array(
            'artist'      => 'Agents Of Time',
            'title'       => 'After Meridian',
            'date'        => '2026. 07. 04.',
            'date_iso'    => '2026-07-04',
            'cover'       => 'linear-gradient(135deg, #090a12 0%, #1b1f44 54%, #b7f7ff 135%)',
            'image'       => $image_base . 'after-meridian.svg',
            'spotify_url' => $spotify_url,
        ),
        array(
            'artist'      => 'Kölsch',
            'title'       => 'Northern Pulse',
            'date'        => '2026. 06. 28.',
            'date_iso'    => '2026-06-28',
            'cover'       => 'linear-gradient(135deg, #111713 0%, #315234 52%, #27d9f8 132%)',
            'image'       => $image_base . 'northern-pulse.svg',
            'spotify_url' => $spotify_url,
        ),
        array(
            'artist'      => 'CamelPhat',
            'title'       => 'Gravity Room',
            'date'        => '2026. 06. 21.',
            'date_iso'    => '2026-06-21',
            'cover'       => 'linear-gradient(135deg, #07080a 0%, #292522 48%, #f2f2f2 138%)',
            'image'       => $image_base . 'gravity-room.svg',
            'spotify_url' => $spotify_url,
        ),
        array(
            'artist'      => 'Argy',
            'title'       => 'Nocturnal Codes',
            'date'        => '2026. 06. 14.',
            'date_iso'    => '2026-06-14',
            'cover'       => 'linear-gradient(135deg, #0a0710 0%, #45235a 46%, #27d9f8 128%)',
            'image'       => $image_base . 'nocturnal-codes.svg',
            'spotify_url' => $spotify_url,
        ),
        array(
            'artist'      => 'Innellea',
            'title'       => 'Glass Horizon',
            'date'        => '2026. 06. 07.',
            'date_iso'    => '2026-06-07',
            'cover'       => 'linear-gradient(135deg, #070b10 0%, #183655 50%, #e9eef0 136%)',
            'image'       => $image_base . 'glass-horizon.svg',
            'spotify_url' => $spotify_url,
        ),
        array(
            'artist'      => 'Brina Knauss',
            'title'       => 'Soft Impact',
            'date'        => '2026. 05. 31.',
            'date_iso'    => '2026-05-31',
            'cover'       => 'linear-gradient(135deg, #0b1010 0%, #27433d 50%, #27d9f8 126%)',
            'image'       => $image_base . 'soft-impact.svg',
            'spotify_url' => $spotify_url,
        ),
        array(
            'artist'      => 'Anyma',
            'title'       => 'Neon Memory',
            'date'        => '2026. 05. 24.',
            'date_iso'    => '2026-05-24',
            'cover'       => 'linear-gradient(135deg, #07070a 0%, #1f2830 48%, #27d9f8 122%)',
            'image'       => $image_base . 'neon-memory.svg',
            'spotify_url' => $spotify_url,
        ),
    );
}

function melodiq_get_fresh_releases() {
    $spotify_tracks = melodiq_spotify_fetch_playlist_tracks();

    if (!is_wp_error($spotify_tracks) && !empty($spotify_tracks)) {
        return $spotify_tracks;
    }

    return melodiq_fresh_release_fallback_data();
}
