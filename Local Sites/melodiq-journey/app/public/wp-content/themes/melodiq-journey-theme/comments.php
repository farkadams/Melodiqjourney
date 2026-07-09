<?php
if (post_password_required()) {
    return;
}
?>

<section id="comments" class="comments-area">
    <h2>
        <?php
        printf(
            esc_html(_n('%s hozzászólás', '%s hozzászólás', get_comments_number(), 'melodiq-journey')),
            esc_html(number_format_i18n(get_comments_number()))
        );
        ?>
    </h2>

    <?php if (have_comments()) : ?>
        <ol class="comment-list">
            <?php
            wp_list_comments(array(
                'style'       => 'ol',
                'short_ping'  => true,
                'avatar_size' => 42,
            ));
            ?>
        </ol>

        <?php the_comments_navigation(); ?>
    <?php endif; ?>

    <?php
    comment_form(array(
        'title_reply'        => __('Szólj hozzá', 'melodiq-journey'),
        'title_reply_before' => '<h2 id="reply-title" class="comment-reply-title">',
        'title_reply_after'  => '</h2>',
        'label_submit'       => __('Küldés', 'melodiq-journey'),
        'class_submit'       => 'button button-primary',
    ));
    ?>
</section>
