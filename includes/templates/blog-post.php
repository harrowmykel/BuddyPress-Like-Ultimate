<?php
/**
 * BuddyPress Like Ultimate - Blog Post Button
 *
 * This function is used to display the BuddyPress Like Ultimate button on blog posts on the WordPress site.
 *
 * @package BuddyPress Like Ultimate
 *
 */
/*
 * bplike_ult_blog_post_button()
 *
 * Outputs Like/Unlike button for blog posts.
 *
 */
function bplike_ult_blog_post_button( $content ) {
    global $post;

    if ( ! is_user_logged_in() ) {
        return $content;
    }

    if ( !$post || !is_singular($post->post_type) || !bp_like_ult_get_settings('bp_like_ult_post_types') ||
        !in_array($post->post_type, bp_like_ult_get_settings('bp_like_ult_post_types'))) {
        return $content;
    }

    $vars = bp_like_ult_get_template_vars( get_the_ID(), 'blog_post' );
    extract( $vars );
    ob_start();

    if(bp_like_ult_use_ajax()):
    ?>
        <a class="blogpost <?php echo $classes; ?>" id="bp-like-blogpost-<?php echo get_the_ID(); ?>"
         title="<?php echo $title; ?>" data-like-type="blog_post">
            <span class="like-text"><?php echo bp_like_ult_get_text( 'like' ); ?></span>
            <span class="unlike-text"><?php echo bp_like_ult_get_text( 'unlike' ); ?></span>
            <span class="like-count"><?php echo ( $liked_count ? $liked_count : '' ) ?></span>
        </a>
    <!-- not ajax -->
    <?php else: ?>
        <a href="<?php echo $static_like_unlike_link; ?>" class="blogpost <?php echo $classes; ?>" id="bp-like-blogpost-<?php echo get_the_ID(); ?>"
         title="<?php echo $title; ?>" data-like-type="blog_post">
            <span class="like-text"><?php echo bp_like_ult_get_text( 'like' ); ?></span>
            <span class="unlike-text"><?php echo bp_like_ult_get_text( 'unlike' ); ?></span>
            <span class="like-count"><?php echo ( $liked_count ? $liked_count : '' ) ?></span>
        </a>
    <?php
    endif;

    bplikes_ult_view_who_likes( get_the_ID(), 'blog_post');

	$content .= ob_get_clean();

    // do not show like button twice
    remove_filter('the_content', 'bplike_ult_blog_post_button');

	return $content;
}
add_filter('the_content', 'bplike_ult_blog_post_button');
add_filter('bbp_get_topic_content', 'bplike_ult_blog_post_button', 99);
