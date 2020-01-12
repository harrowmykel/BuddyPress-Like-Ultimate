<?php
/**
 * BuddyPress Like Ultimate Notifications.
 *
 * @package BuddyPressLike
 * @subpackage Notifications
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/* Emails *********************************************************************/

/**
 * Send email and BP notifications when a users update is liked.
 *
 * @since 0.4
 *
 * @uses bp_notifications_add_notification()
 * @uses bp_get_user_meta()
 * @uses bp_core_get_user_displayname()
 * @uses bp_activity_get_permalink()
 * @uses bp_core_get_user_domain()
 * @uses bp_get_settings_slug()
 * @uses bp_activity_filter_kses()
 * @uses bp_core_get_core_userdata()
 * @uses wp_specialchars_decode()
 * @uses get_blog_option()
 * @uses bp_is_active()
 * @uses bp_is_group()
 * @uses bp_get_current_group_name()
 * @uses apply_filters() To call the 'bp_like_ult_update_liked_notification_to' hook.
 * @uses apply_filters() To call the 'bp_like_ult_update_liked_notification_subject' hook.
 * @uses apply_filters() To call the 'bp_like_ult_update_liked_notification_message' hook.
 * @uses wp_mail()
 * @uses do_action() To call the 'bp_activity_sent_mention_email' hook.
 *
 * @param int $activity_id      The ID of the activity update.
 * @param int $receiver_user_id The ID of the user who is receiving the notification.
 */
function bp_like_ult_activity_update_notification( $activity_id, $receiver_user_id ) {
  // Don't leave multiple notifications for the same activity item.
	$notifications = BP_Core_Notification::get_all_for_user( $receiver_user_id, 'all' );

	foreach( $notifications as $notification ) {
		if ( $activity_id == $notification->item_id ) {
			return;
		}
	}

	$activity = new BP_Activity_Activity( $activity_id );

	$subject = '';
	$message = '';
	$content = '';

	// Now email the user with the contents of the message (if they have enabled email notifications).
  // TODO change 'notification_activity_new_mention' to bp like notification settings options
	if ( 'no' != bp_get_user_meta( $receiver_user_id, 'notification_activity_new_mention', true ) ) {
    // TODO change this to user who liked activty update

    $users_who_like = array_keys((array)(bp_activity_get_meta( $bp_like_ult_id , 'liked_count' , true )));

    if ( count( $users_who_like ) == 1 ) {
      // If only one person likes the current item.

      if ( $receiver_user_id == $users_who_like[0] ) {
        // if the user liked their own update we should do nothing.
      } else {
        $liker_name = bp_core_get_user_displayname( $users_who_like[0] );
        $subject  = bp_get_email_subject( array( 'text' => sprintf( __( '%s liked your update', 'buddypress-like-ult' ), $liker_name ) ) );

      }
    } elseif ( count( $users_who_like ) == 2 ) {

        $liker_one = bp_core_get_user_displayname( $users_who_like[0] );
        $liker_two = bp_core_get_user_displayname( $users_who_like[1] );
        $subject  = bp_get_email_subject( array( 'text' => sprintf( __( '%s and %s liked your update', 'buddypress-like-ult' ), $liker_one, $liker_two ) ) );

    } elseif ( count ($users_who_like) > 2 ) {

        $others = count ($users_who_like);

        $liker_one = bp_core_get_user_displayname( $users_who_like[$others - 1] );
        $liker_two = bp_core_get_user_displayname( $users_who_like[$others - 2] );

        // $users_who_like will always be greater than 2 in here
        if ( $users_who_like == 3 ) {
          // if 3 users like an update we remove 1 as we output 2 user names
          // to match the format of - "User1, User2 and 1 other like this"
            $others = $others - 1;
        } else {
        // remove the two named users from the count
            $others = $others - 2;
        }
      //  $string .= '%s, %s and %d ' . _n( 'other', 'others', $others );
        $subject  = bp_get_email_subject( array( 'text' => sprintf( __('%s, %s and %d ' . _n( 'other', 'others', $others ), 'buddypress-like-ult' ), $liker_one, $liker_two ) ) );
      //  printf( $string , $one , $two , $others );
    }

    $likers_text =  __( '%s and %s like this.' , 'buddypress-like-ult' );

  //  $poster_name = bp_core_get_user_displayname( $activity->user_id );

		$message_link  = bp_activity_get_permalink( $activity_id );
		$settings_slug = function_exists( 'bp_get_settings_slug' ) ? bp_get_settings_slug() : 'settings';
		$settings_link = bp_core_get_user_domain( $receiver_user_id ) . $settings_slug . '/notifications/';

	//	$poster_name = stripslashes( $poster_name );
		$content = bp_activity_filter_kses( strip_tags( stripslashes( $activity->content ) ) );

		// Set up and send the message.
		$ud       = bp_core_get_core_userdata( $receiver_user_id );
		$to       = $ud->user_email;
    //$subject has been declared and assigned its value previously
		//$subject  = bp_get_email_subject( array( 'text' => sprintf( __( '%s liked your update', 'buddypress-like-ult' ), $liker_names ) ) );

		if ( bp_is_active( 'groups' ) && bp_is_group() ) {
			$message = sprintf( __(
'%1$s liked your update in the group "%2$s":

"%3$s"

To view your liked update, log in and visit: %4$s

---------------------
', 'buddypress-like-ult' ), $poster_name, bp_get_current_group_name(), $content, $message_link );
		} else {
			$message = sprintf( __(
'%1$s liked your update:

"%2$s"

To view your liked update, log in and visit: %3$s

---------------------
', 'buddypress-like-ult' ), $poster_name, $content, $message_link );
		}

		// Only show the disable notifications line if the settings component is enabled.
		if ( bp_is_active( 'settings' ) ) {
			$message .= sprintf( __( 'To disable these notifications please log in and go to: %s', 'buddypress' ), $settings_link );
		}

		/**
		 * Filters the user email that the @mention notification will be sent to.
		 *
		 * @since 0.4
		 *
		 * @param string $to User email the notification is being sent to.
		 */
  		$to 	 = apply_filters( 'bp_like_ult_update_liked_notification_to', $to );

		/**
		 * Filters the @mention notification subject that will be sent to user.
		 *
		 * @since 0.4
		 *
		 * @param string $subject     Email notification subject text.
		 * @param string $poster_name Name of the person who made the @mention.
		 */
		$subject = apply_filters( 'bp_like_ult_update_liked_notification_subject', $subject, $poster_name );

		/**
		 * Filters the @mention notification message that will be sent to user.
		 *
		 * @since 0.4
		 *
		 * @param string $message       Email notification message text.
		 * @param string $poster_name   Name of the person who made the @mention.
		 * @param string $content       Content of the liked update.
		 * @param string $message_link  URL permalink for the liked activity update.
		 * @param string $settings_link URL permalink for the user's notification settings area.
		 */
		$message = apply_filters( 'bp_like_ult_update_liked_notification_message', $message, $poster_name, $content, $message_link, $settings_link );

		wp_mail( $to, $subject, $message );
	}

	/**
	 * Fires after the sending of an @mention email notification.
	 *
	 * @since 1.5.0
	 *
	 * @param BP_Activity_Activity $activity         Activity Item object.
	 * @param string               $subject          Email notification subject text.
	 * @param string               $message          Email notification message text.
	 * @param string               $content          Content of the @mention.
	 * @param int                  $receiver_user_id The ID of the user who is receiving the update.
	 */
	do_action( 'bp_like_ult_sent_update_email', $activity, $subject, $message, $content, $receiver_user_id );
// need to add do_action here with hook to call function to add notification
}

/**
 * Send email and BP notifications when an activity item receives a comment.
 *
 * @since 1.2.0
 *
 * @uses bp_get_user_meta()
 * @uses bp_core_get_user_displayname()
 * @uses bp_activity_get_permalink()
 * @uses bp_core_get_user_domain()
 * @uses bp_get_settings_slug()
 * @uses bp_activity_filter_kses()
 * @uses bp_core_get_core_userdata()
 * @uses wp_specialchars_decode()
 * @uses get_blog_option()
 * @uses bp_get_root_blog_id()
 * @uses apply_filters() To call the 'bp_activity_new_comment_notification_to' hook.
 * @uses apply_filters() To call the 'bp_activity_new_comment_notification_subject' hook.
 * @uses apply_filters() To call the 'bp_activity_new_comment_notification_message' hook.
 * @uses wp_mail()
 * @uses do_action() To call the 'bp_activity_sent_reply_to_update_email' hook.
 * @uses apply_filters() To call the 'bp_activity_new_comment_notification_comment_author_to' hook.
 * @uses apply_filters() To call the 'bp_activity_new_comment_notification_comment_author_subject' hook.
 * @uses apply_filters() To call the 'bp_activity_new_comment_notification_comment_author_message' hook.
 * @uses do_action() To call the 'bp_activity_sent_reply_to_reply_email' hook.
 *
 * @param int   $comment_id   The comment id.
 * @param int   $commenter_id The ID of the user who posted the comment.
 * @param array $params       {@link bp_activity_new_comment()}.
 * @return bool
 */
 function bp_like_ult_new_comment_like_notification( $comment_id = 0, $commenter_id = 0, $params = array() ) {

	// Set some default parameters.
	$activity_id = 0;
	$parent_id   = 0;

	extract( $params );

	$original_activity = new BP_Activity_Activity( $activity_id );
	$activity = $original_activity;
	$receiver_user_id = $original_activity->user_id;

	if ( $original_activity->user_id != $commenter_id && 'no' != bp_get_user_meta( $original_activity->user_id, 'bp_liked_activities', true ) ) {
		$liker_name   = bp_core_get_user_displayname( $commenter_id );
		$thread_link   = bp_activity_get_permalink( $activity_id ); // todo add comment link to end of update link, will do for now
		$settings_slug = function_exists( 'bp_get_settings_slug' ) ? bp_get_settings_slug() : 'settings';
		$settings_link = bp_core_get_user_domain( $original_activity->user_id ) . $settings_slug . '/notifications/';

		$liker_name = stripslashes( $liker_name );
		$content = bp_activity_filter_kses( stripslashes($content) );

		// Set up and send the message.
		$ud      = bp_core_get_core_userdata( $original_activity->user_id );
		$to      = $ud->user_email;
		$subject = bp_get_email_subject( array( 'text' => sprintf( __( '%s liked one of your comments', 'buddypress-like-ult' ), $liker_name ) ) );
		$message = sprintf( __(
'%1$s liked one of your comments:

"%2$s"

To view your comment, log in and visit: %3$s

---------------------
', 'buddypress-like-ult' ), $liker_name, $content, $thread_link );

		// Only show the disable notifications line if the settings component is enabled.
		if ( bp_is_active( 'settings' ) ) {
			$message .= sprintf( __( 'To disable these notifications please log in and go to: %s', 'buddypress' ), $settings_link );
		}

		/**
		 * Filters the user email that the new comment notification will be sent to.
		 *
		 * @since 1.2.0
		 *
		 * @param string $to User email the notification is being sent to.
		 */
		$to = apply_filters( 'bp_like_ult_comment_like_notification_to', $to );

		/**
		 * Filters the new comment notification subject that will be sent to user.
		 *
		 * @since 1.2.0
		 *
		 * @param string $subject     Email notification subject text.
		 * @param string $poster_name Name of the person who made the comment.
		 */
		$subject = apply_filters( 'bp_like_ult_comment_like_notification_subject', $subject, $liker_name );

		/**
		 * Filters the new comment notification message that will be sent to user.
		 *
		 * @since 1.2.0
		 *
		 * @param string $message       Email notification message text.
		 * @param string $poster_name   Name of the person who made the comment.
		 * @param string $content       Content of the comment.
		 * @param string $thread_link   URL permalink for the activity thread.
		 * @param string $settings_link URL permalink for the user's notification settings area.
		 */
		$message = apply_filters( 'bp_like_ult_comment_like_notification_to_message', $message, $liker_name, $content, $thread_link, $settings_link );

		wp_mail( $to, $subject, $message );

		/**
		 * Fires after the sending of a reply to an update email notification.
		 *
		 * @since 1.5.0
		 *
		 * @param int    $user_id      ID of the original activity item author.
		 * @param string $subject      Email notification subject text.
		 * @param string $message      Email notification message text.
		 * @param int    $comment_id   ID for the newly received comment.
		 * @param int    $commenter_id ID of the user who made the comment.
		 * @param array  $params       Arguments used with the original activity comment.
		 */
	//	do_action( 'bp_activity_sent_reply_to_update_email', $original_activity->user_id, $subject, $message, $comment_id, $commenter_id, $params );
			do_action( 'bp_like_ult_sent_comment_like_email', $activity, $subject, $message, $content, $receiver_user_id  );
	}

	/*
	 * If this is a reply to another comment, send an email notification to the
	 * author of the immediate parent comment.
	 */
	if ( empty( $parent_id ) || ( $activity_id == $parent_id ) ) {
		return false;
	}

	$parent_comment = new BP_Activity_Activity( $parent_id );

	if ( $parent_comment->user_id != $commenter_id && $original_activity->user_id != $parent_comment->user_id && 'no' != bp_get_user_meta( $parent_comment->user_id, 'notification_activity_new_reply', true ) ) {
		$poster_name   = bp_core_get_user_displayname( $commenter_id );
		$thread_link   = bp_activity_get_permalink( $activity_id );
		$settings_slug = function_exists( 'bp_get_settings_slug' ) ? bp_get_settings_slug() : 'settings';
		$settings_link = bp_core_get_user_domain( $parent_comment->user_id ) . $settings_slug . '/notifications/';

		// Set up and send the message.
		$ud       = bp_core_get_core_userdata( $parent_comment->user_id );
		$to       = $ud->user_email;
		$subject = bp_get_email_subject( array( 'text' => sprintf( __( '%s replied to one of your comments', 'buddypress' ), $poster_name ) ) );

		$poster_name = stripslashes( $poster_name );
		$content = bp_activity_filter_kses( stripslashes( $content ) );

$message = sprintf( __(
'%1$s liked one of your comments:

"%2$s"

To view the original activity, your comment and all replies, log in and visit: %3$s

---------------------
', 'buddypress-like-ult' ), $poster_name, $content, $thread_link );

		// Only show the disable notifications line if the settings component is enabled.
		if ( bp_is_active( 'settings' ) ) {
			$message .= sprintf( __( 'To disable these notifications please log in and go to: %s', 'buddypress' ), $settings_link );
		}

		/**
		 * Filters the user email that the new comment reply notification will be sent to.
		 *
		 * @since 1.2.0
		 *
		 * @param string $to User email the notification is being sent to.
		 */
		$to = apply_filters( 'bp_activity_new_comment_notification_comment_author_to', $to );

		/**
		 * Filters the new comment reply notification subject that will be sent to user.
		 *
		 * @since 1.2.0
		 *
		 * @param string $subject     Email notification subject text.
		 * @param string $poster_name Name of the person who made the comment reply.
		 */
		$subject = apply_filters( 'bp_activity_new_comment_notification_comment_author_subject', $subject, $poster_name );

		/**
		 * Filters the new comment reply notification message that will be sent to user.
		 *
		 * @since 1.2.0
		 *
		 * @param string $message       Email notification message text.
		 * @param string $poster_name   Name of the person who made the comment reply.
		 * @param string $content       Content of the comment reply.
		 * @param string $settings_link URL permalink for the user's notification settings area.
		 * @param string $thread_link   URL permalink for the activity thread.
		 */
		$message = apply_filters( 'bp_activity_new_comment_notification_comment_author_message', $message, $poster_name, $content, $settings_link, $thread_link );

		wp_mail( $to, $subject, $message );

		/**
		 * Fires after the sending of a reply to a reply email notification.
		 *
		 * @since 1.5.0
		 *
		 * @param int    $user_id      ID of the parent activity item author.
		 * @param string $subject      Email notification subject text.
		 * @param string $message      Email notification message text.
		 * @param int    $comment_id   ID for the newly received comment.
		 * @param int    $commenter_id ID of the user who made the comment.
		 * @param array  $params       Arguments used with the original activity comment.
		 */
		do_action( 'bp_activity_sent_reply_to_reply_email', $parent_comment->user_id, $subject, $message, $comment_id, $commenter_id, $params );
	}
}

/**
 * Helper method to map action arguments to function parameters.
 *
 * @since 0.4
 *
 * @param int   $comment_id ID of the comment being notified about.
 * @param array $params     Parameters to use with notification.
 */
function bp_like_ult_comment_like_notification_helper( $comment_id, $params ) {
	bp_like_ult_new_comment_like_notification( $comment_id, $params['user_id'], $params );
}
add_action( 'bp_like_ult_new_comment_like', 'bp_like_ult_comment_like_notification_helper', 10, 2 );

/** Notifications *************************************************************/

function bp_like_ult_string_starts_with( $haystick, $search ) {
	return substr($haystick, 0, strlen($search)) === $search;
}

/**
 * Format notifications related to activity.
 *
 * @since 0.4
 *
 * @uses bp_core_get_user_displayname()
 * @uses apply_filters() To call the 'bp_like_ult_new_like_notification' hook.
 * @uses apply_filters() To call the 'bp_like_ult_new_like_return_notification' hook.
 * @uses do_action() To call 'activity_format_notifications' hook.
 *
 * @param string $action            The type of activity item. Identifies the type of content being liked.
 * @param int    $item_id           The ID of the item being liked.
 * @param int    $secondary_item_id In the case of bp like, this is the liker's ID.
 * @param int    $total_items       The total number of notifications to format.
 * @param string $format            'string' to get a BuddyBar-compatible notification, 'array' otherwise.
 * @return string $return Formatted bp like notification.
 */
function bp_like_ult_format_notifications( $action, $item_id, $secondary_item_id, $total_items, $format = 'string' ) {

	/**
	 * Fires right before returning the formatted activity notifications.
	 *
	 * @since 1.2.0
	 *
	 * @param string $action            The type of activity item.
	 * @param int    $item_id           The activity ID.
	 * @param int    $secondary_item_id @mention mentioner ID.
	 * @param int    $total_items       Total amount of items to format.
	 */
	do_action( 'activity_format_notifications', $action, $item_id, $secondary_item_id, $total_items );

	$link = $text = false;
	$user_display_name = bp_core_get_user_displayname( $secondary_item_id );

	if ( bp_like_ult_string_starts_with( $action, 'activity_update_like' ) ) {

		$link  = bp_activity_get_permalink( $item_id );
		$activity = new BP_Activity_Activity( $item_id );
		$content = strip_tags( $activity->content );

		if ( empty( $content ) ) {
			$content = strip_tags( $activity->action );
		}

		$content = wp_trim_words( $content, 12,  ' ...' );


		if ( 1 == $total_items ) {
			$text = sprintf( __( '%s likes your update: <em>%s</em>', 'buddypress-like-ult'), $user_display_name, $content );
		} else {
			$text = sprintf( __( '%1$d people liked your update: <em>%2$s</em>', 'buddypress-like-ult' ), (int) $total_items, $content );
		}

	} elseif ( bp_like_ult_string_starts_with( $action, 'activity_comment_like' ) ) {

		$link  = bp_activity_get_permalink( $item_id );
		$activity = new BP_Activity_Activity( $item_id );
		$content = wp_trim_words( $activity->content, 12,  ' ...' );

		if ( 1 == $total_items ) {
			$text = sprintf( __( '%s likes your comment: <em>%s</em>', 'buddypress-like-ult'), $user_display_name, $content );
		} else {
			$text = sprintf( __( '%1$d people liked your comment: <em>%2$s</em>', 'buddypress-like-ult' ), (int) $total_items, $content );
		}

	} elseif ( bp_like_ult_string_starts_with( $action, 'blog_post_like' ) ) {

		if ( function_exists('bbp_is_reply') && bbp_is_reply( $item_id ) ) {
			// a reply was liked
			$topic_id = bbp_get_reply_topic_id( $item_id );
			$topic = bbp_get_topic( $topic_id );
			$reply = bbp_get_reply( $item_id );
			$link = bbp_get_topic_permalink( $topic_id );
			$post = get_post( $item_id );
			$topic_post_type = get_post_type_object( bbp_get_topic_post_type() );
			$reply_post_type = get_post_type_object( bbp_get_reply_post_type() );
			$content = wp_trim_words( $reply->post_content, 12,  ' ...' );

			if ( 1 == $total_items ) {
				$text = sprintf( __( '%s likes your <strong>%s</strong> to the %s <em>%s</em>: <em>%s</em>', 'buddypress-like-ult'),
					$user_display_name,
					$reply_post_type->labels->singular_name, $topic_post_type->labels->singular_name, $topic->post_title, $content );
			} else {
				$text = sprintf( __( '%1$d people liked your <strong>%2$s</strong> to the %3$s <em>%4$s</em>: <em%5$s</em>', 'buddypress-like-ult'),
					(int) $total_items,
					$reply_post_type->labels->singular_name, $topic_post_type->labels->singular_name, $topic->post_title, $content );
			}
		} else {
			// any othe post type was liked
			$post = get_post( $item_id );
			$link = get_permalink( $item_id );

			$post_type = get_post_type_object( $post->post_type );

			if ( 1 == $total_items ) {
				$text = sprintf( __( '%s likes your <strong>%s</strong>: <em>%s</em>', 'buddypress-like-ult'),
					$user_display_name,
					$post_type->labels->singular_name, $post->post_title );
			} else {
				$text = sprintf( __( '%1$d people liked your <strong>%2$s</strong>: <em>%3$s</em>', 'buddypress-like-ult'),
					(int) $total_items,
					$post_type->labels->singular_name, $post->post_title );
			}
		}

	} elseif ( bp_like_ult_string_starts_with( $action, 'blog_post_comment_like' ) ) {

		$comment = get_comment( $item_id );
		$link = get_comment_link( $comment );
		$post = get_post( $comment->comment_post_ID );
		$comment_content = wp_trim_words( $comment->comment_content, 12,  ' ...' );
		$post_type = get_post_type_object( $post->post_type );

		if ( 1 == $total_items ) {
			$text = sprintf( __( '%s likes your comment on the <strong>%s</strong> %s: <em>%s</em>', 'buddypress-like-ult'),
				$user_display_name,
				$post_type->labels->singular_name, $post->post_title, $comment_content );
		} else {
			$text = sprintf( __( '%1$d people liked your comment on the <strong>%2$s</strong> %3$s: <em>%4$s</em>', 'buddypress-like-ult'),
				(int) $total_items,
				$post_type->labels->singular_name, $post->post_title, $comment_content );
		}

	}

	if ( ! $link || ! $text ) {
		return false;
	}

	if ( 'string' == $format ) {

		return apply_filters( 'bp_like_ult_new_like_notification', '<a href="' . $link . '">' . $text . '</a>', $action, $total_items, $link, $text, $item_id, $secondary_item_id );
	} else {
		$array = array(
			'text' => $text,
			'link' => $link
		);

		return apply_filters( 'bp_like_ult_new_like_return_notification', $array, $action, $item_id, $secondary_item_id, $total_items );
	}
}

/**
 * Notify a member when their content has been liked.
 *
 *
 * @since 1.9.0
 *
 * @param int    $receiver_user_id  ID of user receiving notification.
 * @param int    $item_id 			ID of the item being liked
 * @param int    $liker_id 			ID of user who liked the item
 * @param string $action 			String which identifies the type of content being liked
 */
function bp_like_ult_add_notification( $receiver_user_id, $item_id, $liker_id, $action ) {
	if ( bp_is_active( 'notifications' ) && $receiver_user_id != $liker_id ) {
		bp_notifications_add_notification( array(
			'user_id'           => $receiver_user_id,
			'item_id'           => $item_id,
			'secondary_item_id' => $liker_id,
			'component_name'    => buddypress()->likes->id,
			'component_action'  => "${action}_${item_id}",
			'date_notified'     => bp_core_current_time(),
			'is_new'            => 1,
		) );
	}
}

function bp_like_ult_activity_update_like_notification_helper( $user_id, $item_id ) {

	$action = 'activity_update_like';

	$activity = new BP_Activity_Activity( $item_id );

	$receiver_user_id = $activity->user_id;

	bp_like_ult_add_notification( $receiver_user_id, $item_id, $user_id, $action );
}
add_action( 'bp_like_ult_activity_update_add_like', 'bp_like_ult_activity_update_like_notification_helper', 10, 2 );

function bp_like_ult_activity_comment_like_notification_helper( $user_id, $item_id ) {

	$action = 'activity_comment_like';

	$activity = new BP_Activity_Activity( $item_id );

	$receiver_user_id = $activity->user_id;

	bp_like_ult_add_notification( $receiver_user_id, $item_id, $user_id, $action );
}
add_action( 'bp_like_ult_activity_comment_add_like', 'bp_like_ult_activity_comment_like_notification_helper', 10, 2 );

function bp_like_ult_blog_post_like_notification_helper( $user_id, $item_id ) {

	$action = 'blog_post_like';

	$post = get_post( $item_id );

	$receiver_user_id = $post->post_author;

	bp_like_ult_add_notification( $receiver_user_id, $item_id, $user_id, $action );
}
add_action( 'bp_like_ult_blog_post_add_like', 'bp_like_ult_blog_post_like_notification_helper', 10, 2 );

function bp_like_ult_blog_post_comment_like_notification_helper( $user_id, $item_id ) {

	$action = 'blog_post_comment_like';

	$comment = get_comment( $item_id );

	$receiver_user_id = $comment->user_id;

	bp_like_ult_add_notification( $receiver_user_id, $item_id, $user_id, $action );
}
add_action( 'bp_like_ult_blog_post_comment_add_like', 'bp_like_ult_blog_post_comment_like_notification_helper', 10, 2 );

function bp_like_ult_blog_notifications_mark_read() {

	if ( ! bp_is_active( 'notifications' ) ) {
		return;
	}

	if ( ! is_user_logged_in() ) {
		return;
	}

	if ( is_singular() && ( $post_id = get_queried_object_id() ) ) {
		BP_Notifications_Notification::update(
			array( 'is_new' => 0 ),
			array( 'item_id' => $post_id,
			       'component_name'    => buddypress()->likes->id,
			       'user_id'           => get_current_user_id(),
				)
		);

		// also clear all comment like notifications
		$comments = get_comments( array( 'post_id' => $post_id ) );

		if ( $comments ) {
			foreach ( $comments as $comment ) {
				BP_Notifications_Notification::update(
					array( 'is_new' => 0 ),
					array( 'item_id' => $comment->comment_ID,
					       'component_name'    => buddypress()->likes->id,
					       'user_id'           => get_current_user_id(),
						)
				);
			}
		}

	}

}
add_action( 'template_redirect', 'bp_like_ult_blog_notifications_mark_read' );

function bp_like_ult_bbp_reply_notifications_mark_read() {
	BP_Notifications_Notification::update(
		array( 'is_new' => 0 ),
		array( 'item_id' => bbp_get_reply_id(),
		       'component_name'    => buddypress()->likes->id,
		       'user_id'           => get_current_user_id(),
			)
	);
}
add_action( 'bbp_theme_before_reply_content', 'bp_like_ult_bbp_reply_notifications_mark_read' );


function bp_like_ult_activity_notifications_mark_read( $activity ) {

	if ( ! bp_is_active( 'notifications' ) ) {
		return;
	}

	if ( ! is_user_logged_in() ) {
		return;
	}

	// Mark as read any notifications for the current user related to this activity item.
	bp_notifications_mark_notifications_by_item_id( bp_loggedin_user_id(), $activity->id, buddypress()->likes->id, null );

	// also mark any notifications as read for the activity comments

	$comments = BP_Activity_Activity::get_activity_comments( $activity->id, $activity->mptt_left, $activity->mptt_right, $spam );

	if($comments != false){
		foreach ( $comments as $comment ) {
			bp_notifications_mark_notifications_by_item_id( bp_loggedin_user_id(), $comment->id, buddypress()->likes->id, null );
		}		
	}


}
add_action( 'bp_activity_screen_single_activity_permalink', 'bp_like_ult_activity_notifications_mark_read' );


function bp_like_ult_notifications_like_removed( $user_id, $item_id ) {
	if ( ! bp_is_active( 'notifications' ) ) {
		return;
	}

	BP_Notifications_Notification::delete(
		array( 'item_id' => $item_id,
		       'component_name'    => buddypress()->likes->id,
		       'secondary_item_id' => $user_id,
			)
	);

}
add_action( 'bp_like_ult_remove_like', 'bp_like_ult_notifications_like_removed', 10, 2 );

/**
 * Remove notifications if activity update has been deleted
**/
function bp_like_ult_activities_deleted( $activity_ids_deleted ) {

	foreach ( $activity_ids_deleted as $activity_id ) {
		BP_Notifications_Notification::delete(
			array( 'item_id' => $activity_id,
			       'component_name'    => buddypress()->likes->id,
				)
		);
	}

}
add_action( 'bp_activity_deleted_activities', 'bp_like_ult_activities_deleted' );