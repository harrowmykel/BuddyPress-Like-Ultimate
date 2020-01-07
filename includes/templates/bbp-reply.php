<?php
/**
 * BuddyPress Like Ultimate - BBP Reply Button
 *
 * This function is used to display the BuddyPress Like Ultimate button on bbp replies on the WordPress site.
 *
 * @package BuddyPress Like Ultimate
 *
 */
/*
 * bplike_ult_bbp_reply_button()
 *
 * Outputs Like/Unlike button for bbp replies.
 *
 */
function bplike_ult_bbp_reply_button() {

    if ( ! is_user_logged_in() ) {
        return;
    }

    $post = get_post( bbp_get_reply_id() );

    if (!bp_like_ult_get_settings('bp_like_ult_post_types') ||
        !in_array($post->post_type, bp_like_ult_get_settings('bp_like_ult_post_types')))
        return;

    $vars = bp_like_ult_get_template_vars( bbp_get_reply_id(), 'blog_post' );
    extract( $vars );

    if(bp_like_ult_use_ajax()):
    ?>
        <a class="bbp-reply <?php echo $classes ?>" id="bp-like-bbp-reply-<?php echo bbp_get_reply_id(); ?>"
           title="<?php echo $title; ?>" data-like-type="blog_post">
            <span class="like-text"><?php echo bp_like_ult_get_text( 'like' ); ?></span>
            <span class="unlike-text"><?php echo bp_like_ult_get_text( 'unlike' ); ?></span>
            <span class="like-count"><?php echo ( $liked_count ? $liked_count : '' ) ?></span>
        </a>
    <!-- not ajax -->
    <?php else: ?>
        <a href="<?php echo $static_like_unlike_link; ?>" class="bbp-reply <?php echo $classes ?>" id="bp-like-bbp-reply-<?php echo bbp_get_reply_id(); ?>"
           title="<?php echo $title; ?>" data-like-type="blog_post">
            <span class="like-text"><?php echo bp_like_ult_get_text( 'like' ); ?></span>
            <span class="unlike-text"><?php echo bp_like_ult_get_text( 'unlike' ); ?></span>
            <span class="like-count"><?php echo ( $liked_count ? $liked_count : '' ) ?></span>
        </a>
    <?php
    endif;

    bplikes_ult_view_who_likes( bbp_get_reply_id(), 'blog_post');

}
add_action('bbp_theme_after_reply_content', 'bplike_ult_bbp_reply_button');
