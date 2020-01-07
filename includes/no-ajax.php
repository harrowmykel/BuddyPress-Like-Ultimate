<?php 

/**
 * Mark activity as like.
 *
 * @since 1.0
 *
 * @return bool False on failure.
 */
function bp_like_ult_action_mark_like() {
	if ( !is_user_logged_in()  || !bp_is_current_action( 'like' ) )
		return false;

	// Check the nonce.
	check_admin_referer( 'mark_like' );

	ob_start();
	bp_like_ult_add_user_like( bp_action_variable( 0 ), bp_action_variable( 1 ) );

	bp_core_redirect( wp_get_referer() . '#activity-' . bp_action_variable( 0 ) );
	//dont output anything
	ob_get_clean();
}
add_action( 'bp_actions', 'bp_like_ult_action_mark_like' );


/**
 * Remove activity from likes.
 *
 * @since 1.0
 *
 * @return bool False on failure.
 */
function bp_like_ult_action_remove_like() {
	if ( ! is_user_logged_in() || ! bp_is_current_action( 'unlike' ) )
		return false;

	// Check the nonce.
	check_admin_referer( 'unmark_like' );
	ob_start();
	bp_like_ult_remove_user_like( bp_action_variable( 0 ), bp_action_variable( 1 ) );

	bp_core_redirect( wp_get_referer() . '#activity-' . bp_action_variable( 0 ) );
	//dont output anything
	ob_get_clean();
}
add_action( 'bp_actions', 'bp_like_ult_action_remove_like' );

/**
* Output the activity like link.
*
* @since 1.0
*
*/
function bp_like_ult_item_like_link($type) {
	echo bp_like_ult_get_item_like_link($type);
}

/**
 * Return the activity like link.
 *
 * @since 1.0
 *
 * @global object $activities_template {@link bp_item_Template}
 *
 * @return string The activity like link.
 */
function bp_like_ult_get_item_like_link($type) {
	global $activities_template;

	/**
	 * Filters the activity like link.
	 *
	 * @since 1.0
	 *
	 * @param string $value Constructed link for liking the activity comment.
	 */
	return apply_filters( 'bp_like_ult_get_like_link', wp_nonce_url( home_url( bp_get_activity_root_slug() . '/like/' . $activities_template->activity->id . '/'.$type .'/'), 'mark_like' ) );
}

/**
* Output the activity unlike link.
*
* @since 1.0
*
*/
function bp_like_ult_item_unlike_link($type) {
	echo bp_like_ult_get_item_unlike_link($type);
}

/**
 * Return the activity unlike link.
 *
 * @since 1.0
 *
 * @global object $activities_template {@link bp_item_Template}
 *
 * @return string The activity unlike link.
 */
function bp_like_ult_get_item_unlike_link($type) {
	global $activities_template;

	/**
	 * Filters the activity unlike link.
	 *
	 * @since 1.0
	 *
	 * @param string $value Constructed link for unliking the activity comment.
	 */
	return apply_filters( 'bp_like_ult_get_unlike_link', wp_nonce_url( home_url( bp_get_activity_root_slug() . '/unlike/' . $activities_template->activity->id . '/' . $type .'/'), 'unmark_like' ) );
}


 ?>