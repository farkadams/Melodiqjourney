<?php
$featured_event = melodiq_featured_event_query();
$upcoming_events = melodiq_event_query(array('posts_per_page' => 5));

$calendar_dates = array();
$calendar_event_links = array();
$requested_calendar_month = isset($_GET['event_month']) ? sanitize_text_field(wp_unslash($_GET['event_month'])) : '';
$default_calendar_date = wp_date('Y-m-d', current_time('timestamp'));
$first_event_query = melodiq_event_query(array('posts_per_page' => 1));

if ($first_event_query->have_posts()) {
    $first_event_query->the_post();
    $first_event_meta = melodiq_event_meta();

    if ($first_event_meta['date']) {
        $default_calendar_date = $first_event_meta['date'];
    }

    wp_reset_postdata();
}

if (!preg_match('/^\d{4}-\d{2}$/', $requested_calendar_month)) {
    $requested_calendar_month = wp_date('Y-m', strtotime($default_calendar_date . ' 12:00:00'));
}

$calendar_timestamp = strtotime($requested_calendar_month . '-01 12:00:00');
$calendar_month_key = wp_date('Y-m', $calendar_timestamp);
$calendar_month_names = array(
    1  => 'JANUÁR',
    2  => 'FEBRUÁR',
    3  => 'MÁRCIUS',
    4  => 'ÁPRILIS',
    5  => 'MÁJUS',
    6  => 'JÚNIUS',
    7  => 'JÚLIUS',
    8  => 'AUGUSZTUS',
    9  => 'SZEPTEMBER',
    10 => 'OKTÓBER',
    11 => 'NOVEMBER',
    12 => 'DECEMBER',
);
$calendar_month_label = wp_date('Y', $calendar_timestamp) . ' ' . $calendar_month_names[(int) wp_date('n', $calendar_timestamp)];
$calendar_month_start = wp_date('Y-m-01', $calendar_timestamp);
$calendar_month_end = wp_date('Y-m-t', $calendar_timestamp);
$calendar_prev_month = wp_date('Y-m', strtotime('-1 month', $calendar_timestamp));
$calendar_next_month = wp_date('Y-m', strtotime('+1 month', $calendar_timestamp));
$calendar_prev_url = add_query_arg('event_month', $calendar_prev_month);
$calendar_next_url = add_query_arg('event_month', $calendar_next_month);
$calendar_query = new WP_Query(array(
    'post_type'      => 'event',
    'posts_per_page' => -1,
    'post_status'    => 'publish',
    'meta_key'       => '_melodiq_event_date',
    'orderby'        => 'meta_value',
    'order'          => 'ASC',
    'meta_query'     => array(
        'relation' => 'OR',
        array(
            'key'     => '_melodiq_event_date',
            'value'   => array($calendar_month_start, $calendar_month_end),
            'compare' => 'BETWEEN',
            'type'    => 'DATE',
        ),
        array(
            'key'     => '_melodiq_event_date_end',
            'value'   => array($calendar_month_start, $calendar_month_end),
            'compare' => 'BETWEEN',
            'type'    => 'DATE',
        ),
        array(
            'relation' => 'AND',
            array(
                'key'     => '_melodiq_event_date',
                'value'   => $calendar_month_start,
                'compare' => '<=',
                'type'    => 'DATE',
            ),
            array(
                'key'     => '_melodiq_event_date_end',
                'value'   => $calendar_month_end,
                'compare' => '>=',
                'type'    => 'DATE',
            ),
        ),
    ),
));

if ($calendar_query->have_posts()) {
    while ($calendar_query->have_posts()) {
        $calendar_query->the_post();
        $calendar_meta = melodiq_event_meta();

        if ($calendar_meta['date']) {
            $calendar_dates[] = array(
                'start' => $calendar_meta['date'],
                'end'   => $calendar_meta['date_end'] ? $calendar_meta['date_end'] : $calendar_meta['date'],
                'url'   => melodiq_event_action_url(),
                'image' => get_the_post_thumbnail_url(get_the_ID(), 'thumbnail'),
            );
        }
    }

    wp_reset_postdata();
}

$calendar_days_in_month = (int) wp_date('t', $calendar_timestamp);
$calendar_first_weekday = (int) wp_date('N', strtotime(wp_date('Y-m-01', $calendar_timestamp) . ' 12:00:00'));
$calendar_event_days = array();

foreach ($calendar_dates as $calendar_event_date) {
    $event_start_timestamp = strtotime($calendar_event_date['start'] . ' 12:00:00');
    $event_end_timestamp = strtotime($calendar_event_date['end'] . ' 12:00:00');

    if ($event_end_timestamp < $event_start_timestamp) {
        $event_end_timestamp = $event_start_timestamp;
    }

    for ($event_timestamp = $event_start_timestamp; $event_timestamp <= $event_end_timestamp; $event_timestamp = strtotime('+1 day', $event_timestamp)) {
        if (wp_date('Y-m', $event_timestamp) === $calendar_month_key) {
            $calendar_event_days[(int) wp_date('j', $event_timestamp)] = array(
                'url'   => $calendar_event_date['url'],
                'image' => $calendar_event_date['image'],
            );
        }
    }
}
?>

<aside class="event-sidebar" aria-label="Esemény sidebar">
    <article class="featured-event">
        <p class="section-kicker">Kiemelt esemény</p>
        <?php if ($featured_event->have_posts()) : ?>
            <?php
            $featured_event->the_post();
            $featured_meta = melodiq_event_meta();
            $featured_date = melodiq_event_date_parts($featured_meta['date'], $featured_meta['date_end']);
            ?>
            <div class="featured-art">
                <?php if (has_post_thumbnail()) : ?>
                    <?php the_post_thumbnail('large'); ?>
                <?php endif; ?>
                <a class="featured-art-link" href="<?php echo esc_url(melodiq_event_action_url()); ?>" aria-label="<?php echo esc_attr(get_the_title() . ' megnyitása'); ?>"></a>
                <div class="event-date"><strong><?php echo esc_html($featured_date['day']); ?></strong><span><?php echo esc_html($featured_date['month']); ?></span></div>
                <button class="event-like-button featured-event-like" type="button" data-event-id="<?php echo esc_attr(get_the_ID()); ?>" aria-label="<?php echo esc_attr(get_the_title() . ' kedvelése'); ?>">
                    <span aria-hidden="true">♡</span>
                    <b><?php echo esc_html(melodiq_event_like_count()); ?></b>
                </button>
                <div class="featured-art-content">
                    <h2><?php the_title(); ?></h2>
                    <p><?php echo esc_html($featured_meta['city'] ? $featured_meta['city'] : 'Budapest'); ?></p>
                    <?php echo melodiq_event_lineup_markup($featured_meta, true); ?>
                    <span class="event-card-organizer"><?php echo esc_html($featured_meta['organizer'] ? $featured_meta['organizer'] : 'Melodiq Journey'); ?></span>
                    <?php echo melodiq_event_ticket_link(); ?>
                </div>
            </div>
            <?php wp_reset_postdata(); ?>
        <?php else : ?>
            <div class="featured-art">
                <a class="featured-art-link" href="#" aria-label="Stephan Jolk megnyitása"></a>
                <div class="event-date"><strong>14</strong><span>Aug</span></div>
                <button class="event-like-button featured-event-like" type="button" disabled><span aria-hidden="true">♡</span><b>0</b></button>
                <div class="featured-art-content">
                    <h2>Stephan Jolk</h2>
                    <p>Budapest</p>
                    <div class="event-lineup event-lineup-compact">
                        <div class="event-lineup-row event-lineup-headliner"><span>Headliner</span><strong>Stephan Jolk</strong></div>
                    </div>
                    <span class="event-card-organizer">Melodiq Journey</span>
                    <a class="event-ticket-link" href="#">
                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 8.5V6a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v2.5a2.5 2.5 0 0 0 0 5V16a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2v-2.5a2.5 2.5 0 0 0 0-5Z"></path><path d="M9 7v10"></path><path d="M13 8h3"></path><path d="M13 12h3"></path></svg>
                        <span>Jegyvásárlás</span>
                    </a>
                </div>
            </div>
        <?php endif; ?>
    </article>

    <section class="calendar-card" aria-labelledby="calendar-title">
        <div class="sidebar-title">
            <h2 id="calendar-title">Eseménynaptár</h2>
        </div>

        <div class="calendar-nav" aria-label="Hónapok közötti navigáció">
            <a href="<?php echo esc_url($calendar_prev_url); ?>" aria-label="Előző hónap">&lt;</a>
            <span><?php echo esc_html($calendar_month_label); ?></span>
            <a href="<?php echo esc_url($calendar_next_url); ?>" aria-label="Következő hónap">&gt;</a>
        </div>

        <div class="calendar-grid">
            <span>H</span><span>K</span><span>Sze</span><span>Cs</span><span>P</span><span>Szo</span><span>V</span>
            <?php for ($blank_day = 1; $blank_day < $calendar_first_weekday; $blank_day++) : ?>
                <i></i>
            <?php endfor; ?>
            <?php for ($calendar_day = 1; $calendar_day <= $calendar_days_in_month; $calendar_day++) : ?>
                <?php if (isset($calendar_event_days[$calendar_day])) : ?>
                    <?php
                    $calendar_event_day = $calendar_event_days[$calendar_day];
                    $calendar_day_style = $calendar_event_day['image'] ? '--calendar-thumb: url(' . esc_url($calendar_event_day['image']) . ');' : '';
                    ?>
                    <a class="is-active" href="<?php echo esc_url($calendar_event_day['url']); ?>" aria-label="<?php echo esc_attr($calendar_day . '. napi esemény megnyitása'); ?>"<?php echo $calendar_day_style ? ' style="' . esc_attr($calendar_day_style) . '"' : ''; ?>><?php echo esc_html($calendar_day); ?></a>
                <?php else : ?>
                    <b><?php echo esc_html($calendar_day); ?></b>
                <?php endif; ?>
            <?php endfor; ?>
        </div>
    </section>

    <section class="upcoming-card" aria-labelledby="upcoming-title">
        <h2 id="upcoming-title">Közelgő események</h2>
        <ul>
            <?php if ($upcoming_events->have_posts()) : ?>
                <?php
                while ($upcoming_events->have_posts()) :
                    $upcoming_events->the_post();
                    $upcoming_meta = melodiq_event_meta();
                    $upcoming_date = melodiq_event_date_parts($upcoming_meta['date'], $upcoming_meta['date_end']);
                    $upcoming_image = get_the_post_thumbnail_url(get_the_ID(), 'medium');
                    $upcoming_style = $upcoming_image ? '--upcoming-bg: url(' . esc_url($upcoming_image) . ');' : '';
                    ?>
                    <li>
                        <a class="upcoming-event-link" href="<?php echo esc_url(melodiq_event_action_url()); ?>"<?php echo $upcoming_style ? ' style="' . esc_attr($upcoming_style) . '"' : ''; ?>>
                            <strong><?php echo esc_html(trim($upcoming_date['day'] . ' ' . $upcoming_date['month'])); ?></strong>
                            <span>
                                <?php the_title(); ?>
                                <small><?php echo esc_html($upcoming_meta['city'] ? $upcoming_meta['city'] : 'Budapest'); ?></small>
                                <small><?php echo esc_html($upcoming_meta['organizer'] ? $upcoming_meta['organizer'] : 'Melodiq Journey'); ?></small>
                            </span>
                        </a>
                    </li>
                <?php endwhile; ?>
                <?php wp_reset_postdata(); ?>
            <?php else : ?>
                <li><a class="upcoming-event-link" href="#"><strong>14 Aug</strong><span>Stephan Jolk<small>Budapest</small><small>Melodiq Journey</small></span></a></li>
                <li><a class="upcoming-event-link" href="#"><strong>19 Sep</strong><span>Innellea<small>Budapest</small><small>Melodiq Journey</small></span></a></li>
                <li><a class="upcoming-event-link" href="#"><strong>24 Oct</strong><span>Agents of Time<small>Budapest</small><small>Melodiq Journey</small></span></a></li>
                <li><a class="upcoming-event-link" href="#"><strong>15 Nov</strong><span>Melodiq Open Air<small>Balaton</small><small>Melodiq Journey</small></span></a></li>
                <li><a class="upcoming-event-link" href="#"><strong>31 Dec</strong><span>New Year Journey<small>Budapest</small><small>Melodiq Journey</small></span></a></li>
            <?php endif; ?>
        </ul>
        <a class="button button-secondary" href="<?php echo esc_url(melodiq_event_archive_url()); ?>">Összes esemény</a>
    </section>
</aside>
