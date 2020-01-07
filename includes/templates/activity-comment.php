<?php
/**
 * BuddyPress Like Ultimate - Activty Comment Button
 *
 * This function is used to display the BuddyPress Like Ultimate button on comments in the activity stream
 *
 * @package BuddyPressLike
 *
 */

/*
 * bplike_ult_activity_comment_button()
 *
 * Outputs Like/Unlike button for activity comments.
 *
 */
function bplike_ult_activity_comment_button() {

    if ( ! is_user_logged_in() ) {
        return;
    }

    $type = 'activity_comment';
    $id = bp_get_activity_comment_id();

    $comments_query = new WP_Comment_Query;
    $comments = $comments_query->query( array(
        'meta_key'   => 'bp_activity_comment_id',
        'meta_value' => $id
    ) );

    // this activity comment is synchronized with a post comment
    // so also synchronize the likes
    if ( $comments ) {
        $comment = $comments[0];
        $post_type = get_post_type( $comment->comment_post_ID );

        if ( ! ( $settings = bp_like_ult_get_settings('bp_like_ult_post_types') ) ||
             ! in_array( $post_type, $settings ) ) {
            return;
        }

        $id = $comment->comment_ID;
        $type = 'blog_post_comment';
    }

    $vars = bp_like_ult_get_template_vars( $id, $type );
    extract( $vars );


    if(bp_like_ult_use_ajax()):
    ?>
        <a href="#" class="activity_comment bp-primary-action <?php echo $classes ?>"
            id="bp-like-activity-comment-<?php echo $id; ?>"
            title="<?php echo $title ?>" data-like-type="<?php echo $type ?>">
            <span class="like-text"><?php echo bp_like_ult_get_text( 'like' ); ?></span>
            <span class="unlike-text"><?php echo bp_like_ult_get_text( 'unlike' ); ?></span>
            <span class="like-count"><?php echo ( $liked_count ? $liked_count : '' ) ?></span>
        </a>
    <!-- not ajax -->
    <?php else: ?>
        <a href="<?php echo $static_like_unlike_link; ?>" class="activity_comment bp-primary-action no-ajax <?php echo $classes ?>"
            id="bp-like-activity-comment-<?php echo $id; ?>"
            title="<?php echo $title ?>" data-like-type="<?php echo $type ?>">
            <span class="like-text"><?php echo bp_like_ult_get_text( 'like' ); ?></span>
            <span class="unlike-text"><?php echo bp_like_ult_get_text( 'unlike' ); ?></span>
            <span class="like-count"><?php echo ( $liked_count ? $liked_count : '' ) ?></span>
        </a>
    <?php
    endif;
}
