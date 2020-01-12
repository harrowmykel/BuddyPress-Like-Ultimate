<?php

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * bp_like_ult_is_liked()
 *
 * Checks to see whether the user has liked a given item.
 *
 */
function bp_like_ult_is_liked( $item_id, $type, $user_id) {

    if ( ! $type || ! $item_id ) {
        return false;
    }

    if ( isset( $user_id ) ) {
        if ( ! $user_id ) {
            $user_id = get_current_user_id();
        }
    }

	return BPLIKE_ULT_LIKES::item_is_liked($item_id, $type, $user_id);
}

/**
 * bp_like_ult_add_user_like()
 *
 * Registers that the user likes a given item.
 *
 */
function bp_like_ult_add_user_like( $item_id, $type, $user_id = 0) {

    $liked_count = 0;

    //if user id is 0, null, '' 
    if ( empty($user_id) ) {
        $user_id = get_current_user_id();
    }
    if ( ! $item_id || ! is_user_logged_in() ) {
        return false;
    }

    if ( BPLIKE_ULT_LIKES::get_user_like($item_id, $type, $user_id) )
        return false;

	$like = new BPLIKE_ULT_LIKES();
	$like->liker_id = $user_id;
	$like->item_id = $item_id;
	$like->like_type = $type;
	$like->date_created = current_time( 'mysql' );
	$like->save();

	$liked_count = count(  BPLIKE_ULT_LIKES::get_likers($item_id, $type) );

    if ( $type == 'activity_update' ) {
        $group_id = 0;

        // check if this item is in a group or not, assign group id if so
        if ( bp_is_active( 'groups' ) && bp_is_group() ) {
          $group_id = bp_get_current_group_id();
        }

        bp_like_ult_post_to_stream( $item_id, $user_id, $group_id );

        do_action('bp_like_ult_activity_update_add_like', $user_id, $item_id);

    } elseif ( $type == 'blog_post' ) {

        /* save total like count, so posts can be ordered by likes */
        update_post_meta( $item_id , 'bp_liked_count_total' , $liked_count );

        if ( bp_like_ult_get_settings( 'post_to_activity_stream' ) == 1 ) {
            $post = get_post( $item_id );
            $author_id = $post->post_author;

            $liker = bp_core_get_userlink( $user_id );
            $permalink = get_permalink( $item_id );
            $title = $post->post_title;
            $author = bp_core_get_userlink( $post->post_author );

            if ( $user_id == $author_id ) {
                $action = bp_like_ult_get_text( 'record_activity_likes_own_blogpost' );
            } elseif ( $user_id == 0 ) {
                $action = bp_like_ult_get_text( 'record_activity_likes_a_blogpost' );
            } else {
                $action = bp_like_ult_get_text( 'record_activity_likes_users_blogpost' );
            }

            /* Filter out the placeholders */
            $action = str_replace( '%user%', $liker, $action );
            $action = str_replace( '%permalink%', $permalink, $action );
            $action = str_replace( '%title%', $title, $action );
            $action = str_replace( '%author%', $author, $action );

            /* Grab the content and make it into an excerpt of 140 chars if we're allowed */
            if ( bp_like_ult_get_settings( 'show_excerpt' ) == 1 ) {
                $content = $post->post_content;
                if ( strlen( $content ) > bp_like_ult_get_settings( 'excerpt_length' ) ) {
                    $content = substr( $content, 0, bp_like_ult_get_settings( 'excerpt_length' ) );
                    $content = $content . '...';
                }
            };

            bp_activity_add(
                    array(
                        'action' => $action,
                        'content' => $content,
                        'component' => 'bp-like',
                        'type' => 'blogpost_liked',
                        'user_id' => $user_id,
                        'item_id' => $item_id,
                        'primary_link' => $permalink
                    )
            );
        }

        do_action('bp_like_ult_blog_post_add_like', $user_id, $item_id);
    } else {
		/* Do nothing special for now */
        do_action("bp_like_ult_${type}_add_like", $user_id, $item_id);
    }
    //only echo if doing ajax
    if(wp_doing_ajax()): ?>
      <span class="like-text"><?php echo bp_like_ult_get_text( 'like' ); ?></span>
      <span class="unlike-text"><?php echo bp_like_ult_get_text( 'unlike' ); ?></span>
      <span class="like-count"><?php echo $liked_count; ?></span>
    <?php endif;
}

/**
 * bp_like_ult_remove_user_like()
 *
 * Registers that the user has unliked a given item.
 *
 */
function bp_like_ult_remove_user_like( $item_id = '' , $type = '' ) {

    if ( ! $item_id ) {
        return false;
    }

    if ( ! isset( $user_id ) ) {

        $user_id = get_current_user_id();
    }

    if ( 0 == $user_id ) {
      // todo replace this with an internal wordpress string.
      // maybe use wp_die() here?
        __('Sorry, you must be logged in to like that.', 'buddypress-like-ult');
        return false;
    }

	if ( $like = BPLIKE_ULT_LIKES::get_user_like($item_id, $type, $user_id) )
		$like->delete();

	$liked_count = count(  BPLIKE_ULT_LIKES::get_likers($item_id, $type) );

    if ( $type == 'activity_update' ) {

        if ( bp_is_group() ) {

            $bp = buddypress();
            $update_id = bp_activity_get_activity_id(
                array(
                  'user_id'           => $user_id,
                  'component'         => $bp->groups->id,
                  'type'              => 'activity_liked',
                  'item_id'           => bp_get_current_group_id(),
                  'secondary_item_id' => $item_id,
                )
            );

            if ( $update_id ) {
                bp_activity_delete(
                    array(
                       'id'                => $update_id,
                       'user_id'           => $user_id,
                       'secondary_item_id' => $item_id,
                       'type'              => 'activity_liked',
                       'component'         => $bp->groups->id,
                       'item_id'           => bp_get_current_group_id()
                    )
                );
            }

        } else {
            /* Remove the update on the users profile from when they liked the activity. */
            $update_id = bp_activity_get_activity_id(
                array(
                    'item_id' => $item_id,
                    'component' => 'bp-like',
                    'type' => 'activity_liked',
                    'user_id' => $user_id
                )
            );

            if ( $update_id ) {
                bp_activity_delete(
                        array(
                           'id' => $update_id,
                           'user_id' => $user_id
                        )
                );
            }
        }

    } elseif ( $type == 'activity_comment' ) {

        /* Do nothing special for now */

    } elseif ( $type == 'blog_post' ) {

        /* update total like count, so posts can be ordered by likes */
        update_post_meta( $item_id , 'bp_liked_count_total' , $liked_count );

        /* Remove the update on the users profile from when they liked the activity. */
        $update_id = bp_activity_get_activity_id(
                array(
                    'item_id' => $item_id,
                    'component' => 'bp-like',
                    'type' => 'blogpost_liked',
                    'user_id' => $user_id
                )
        );

        if ( $update_id ) {
            bp_activity_delete(
                array(
                    'id' => $update_id,
                    'item_id' => $item_id,
                    'component' => 'bp-like',
                    'type' => 'blogpost_liked',
                    'user_id' => $user_id
                )
            );
        }
    } elseif ( $type == 'blog_post_comment' ) {

       /* Do nothing special for now */
    }

    do_action("bp_like_ult_remove_like", $user_id, $item_id);
    ?>
    <span class="like-text"><?php echo bp_like_ult_get_text( 'like' ); ?></span>
    <span class="unlike-text"><?php echo bp_like_ult_get_text( 'unlike' ); ?></span>
    <span class="like-count"><?php echo ($liked_count?$liked_count:''); ?></span><?php
}

/*
 * @updated in 1.0
 * includes support for avatar
 * bp_like_ult_get_some_likes()
 *
 * Description: Returns a defined number of likers, beginning with more recent.
 *
 */
function bp_like_ult_get_some_likes( $id, $type, $start, $end) {

    $string_to_display = "";
    $users_to_display_ids = [];
    /*variable holds the number of others to show, -1 means no value*/
    $others_count = -1;

    $users_who_like = BPLIKE_ULT_LIKES::get_likers($id, $type);

    $string = $start . ' class="users-who-like" id="users-who-like-' . $id . '">';

    // if the current users likes the item
    if ( in_array( get_current_user_id(), $users_who_like ) ) {
        if ( count( $users_who_like ) == 0 ) {
          // if noone likes this, do nothing as nothing gets outputted

        } elseif ( count( $users_who_like ) == 1 ) {

            $string_to_display = bp_like_ult_get_text( 'get_likes_only_liker' );
            $users_to_display_ids[] = get_current_user_id();

        } elseif ( count( $users_who_like ) == 2 ) {

            // find where the current_user is in the array $users_who_like
            $key = array_search( get_current_user_id(), $users_who_like );

            // removing current user from $users_who_like
            array_splice( $users_who_like, $key, 1 );

            $string_to_display = bp_like_ult_get_text( 'you_and_username_like_this' );
            $users_to_display_ids[] = $users_who_like[0];

        } elseif ( count( $users_who_like ) == 3 ) {

              $key = array_search( get_current_user_id(), $users_who_like );

              // removing current user from $users_who_like
              array_splice( $users_who_like, $key, 1 );

              $others = count ($users_who_like);

              $string_to_display = bp_like_ult_get_text( 'you_and_two_usernames_like_this' );
              $users_to_display_ids[] = $users_who_like[$others - 2];
              $users_to_display_ids[] = $users_who_like[$others - 1];

        } elseif (  count( $users_who_like ) > 3 ) {

              $key = array_search( get_current_user_id(), $users_who_like );

              // removing current user from $users_who_like
              array_splice( $users_who_like, $key, 1 );

              $others = count ($users_who_like);
              $others = $others - 2;

              $string_to_display = _n( 'You, %s, %s and %s other like this.', 'You, %s, %s and %s others like this.', $others, 'buddypress-like-ult' );
              // output last two people to like (2 at end of array)
              $users_to_display_ids[] = $users_who_like[$others - 2];
              $users_to_display_ids[] = $users_who_like[$others - 1];
              $others_count = $others;
        }
    } else {

        if ( count( $users_who_like ) == 0 ) {
          // if noone likes this, do nothing as nothing gets outputted

        } elseif ( count( $users_who_like ) == 1 ) {

            $string_to_display = bp_like_ult_get_text( 'one_likes_this' );
            $users_to_display_ids[] = $users_who_like[0];

        } elseif ( count( $users_who_like ) == 2 ) {

            $string_to_display = bp_like_ult_get_text( 'two_like_this' );
            $users_to_display_ids[] = $users_who_like[0];
            $users_to_display_ids[] = $users_who_like[1];

        } elseif ( count( $users_who_like ) == 3 ) {

            $string_to_display = bp_like_ult_get_text( 'three_like_this' );
            $users_to_display_ids[] = $users_who_like[0];
            $users_to_display_ids[] = $users_who_like[1];
            $users_to_display_ids[] = $users_who_like[2];

        } elseif (  count( $users_who_like ) > 3 ) {

              $others = count ($users_who_like);

              // output last two people to like (3 at end of array)
              $string_to_display = _n('%s, %s, %s and %s other like this.', '%s, %s, %s and %s others like this.', $others, 'buddypress-like-ult' );
              $users_to_display_ids[] = $users_who_like[ $others - 1];
              $users_to_display_ids[] = $users_who_like[$others - 2];
              $users_to_display_ids[] = $users_who_like[$others - 3];
              $others_count = $others - 3;
        }
    }

    $bp_like_ult_name_or_avatar = bp_like_ult_get_settings( 'bp_like_ult_name_or_avatar' );
    $bp_like_ult_name_or_avatar_position = bp_like_ult_get_settings( 'bp_like_ult_name_or_avatar_position' );

    //separator between names
    $bp_like_ult_names_separator = apply_filters("bp_like_ult_names_separator", ", ");
    //separator between avatars
    $bp_like_ult_avatar_separator = apply_filters("bp_like_ult_avatar_separator", "");
    //separator between names and avatars
    $bp_like_ult_names_and_avatar_separator = apply_filters("bp_like_ult_names_and_avatar_separator", "");

    $users_html_to_replace_with = [];
    $string .= "<small>";

    switch ($bp_like_ult_name_or_avatar) {
      case 'names_only':
        $users_html_to_replace_with = bp_like_ult_get_all_user_links($users_to_display_ids);
        break;
      case 'pictures_only':
        $users_html_to_replace_with = bp_like_ult_get_all_users_avatar($users_to_display_ids);
        break;
      case 'names_and_pictures':
      default:
        $all_users_avatars = bp_like_ult_get_all_users_avatar($users_to_display_ids);
        $all_users_links = bp_like_ult_get_all_user_links($users_to_display_ids);

        if($bp_like_ult_name_or_avatar_position == "all_names_behind_all_pictures"){

            $all_users_avatars_string = implode ( $bp_like_ult_avatar_separator, $all_users_avatars ); 
            $users_html_to_replace_with = $all_users_links;
            //put pictures in first position of array;
            $users_html_to_replace_with[0] = $all_users_avatars_string . $users_html_to_replace_with[0];

        }else if($bp_like_ult_name_or_avatar_position == "all_names_in_front_of_all_pictures"){

            $all_users_links_string = implode ( $bp_like_ult_names_separator, $all_users_links ); 
            $users_html_to_replace_with = $all_users_avatars;
            //put pictures in first position of array;
            $users_html_to_replace_with[0] = $all_users_links_string . $users_html_to_replace_with[0];

        }else if($bp_like_ult_name_or_avatar_position == "each_name_in_front_of_each_picture"){
            //fill up
            foreach ($all_users_links as $key => $current_users_link) {             
              $users_html_to_replace_with[$key] = $all_users_links[$key] . $bp_like_ult_names_and_avatar_separator.$all_users_avatars[$key];
            }

        }else{
          //each_name_behind_each_picture
          //fill up
          foreach ($all_users_links as $key => $current_users_link) {             
            $users_html_to_replace_with[$key] = $all_users_avatars[$key] . $bp_like_ult_names_and_avatar_separator.$all_users_links[$key];
          }
        }
    }
    //catch others string/digit
    if($others_count != -1){
      $users_html_to_replace_with[] = $others_count;
    }
    $string .= bp_like_ult_replace_strings_variables($id, $type, $string_to_display, $users_html_to_replace_with, $others_count);
    $string .= "</small>";
    echo $string;
}


/**
 * Gets all avatar for each user id in array.
 * @since 1.0
 * @param      array    $users_to_display_ids         The users to display identifiers
 * @param      boolean  $return_array                 if function should return array 
 * @param      boolean  $link_images_to_user_profile  if function should hyperlink images to user profile
 * @param      string   $separator                    The separator
 *
 * @return     <type>   All pictures links.
 */
function bp_like_ult_get_all_users_avatar($users_to_display_ids = array() , $return_array = true, $link_images_to_user_profile = true, $separator = ""){  
    $users_html_to_replace_with = [];
   foreach ($users_to_display_ids as $key => $user_to_display_id) {
      //get only link to profile
      $user_link_to_profile = bp_core_get_userlink($user_to_display_id, false, true);
      //if user exists
      if(!empty($user_link_to_profile)){
        $user_avatar_html = bp_core_fetch_avatar(array( 
                  'item_id' => $user_to_display_id,  
                  'object' => 'user',  
                  'type' => 'thumb',    
                  'width' => 20,  
                  'height' => 20,  
                  'class' => 'avatar like-avatar',  
                  'css_id' => 'like-avatar-user-'.$users_to_display_ids)
                  );
        if($link_images_to_user_profile){
          $users_html_to_replace_with[] = "<a href='".$user_link_to_profile."'>".$user_avatar_html."</a>";
        }else{
          $users_html_to_replace_with[] = $user_avatar_html;
        }        
      }else{
        $users_html_to_replace_with[] = '';
      }
    }
  if($return_array){
    return $users_html_to_replace_with;
  }
  //convert array to string using separator
  return implode ( $separator , $users_html_to_replace_with ); 
}


/**
 * Gets all links for each user id in array.
 * @since 1.0
 * @param      array    $users_to_display_ids          The users to display identifiers
 * @param      boolean  $return_array                  if function should return
 *                                                     array
 * @param      string   $separator                     The separator
 *
 * @return     <type>   All Strings links.
 */
function bp_like_ult_get_all_user_links($users_to_display_ids = array() , $return_array = true, $separator = ""){   
    $users_html_to_replace_with = [];
   foreach ($users_to_display_ids as $key => $user_to_display_id) {
      //get only link to profile
      $users_html_to_replace_with[] = bp_core_get_userlink($user_to_display_id);
    }
  if($return_array){
    return $users_html_to_replace_with;
  }
  //convert array to string using separator
  return implode ( $separator , $users_html_to_replace_with ); 
}

/**
 * replaces strings variables with sprintf
 * %s, %d 
 * @since 1.0
 * @param      int, string  $item_id                     The item identifier
 * @param      string  $content_type                The content type
 * @param      string  $string_to_display           The string to display
 * @param      array   $users_html_to_replace_with  The users html to replace %s
 *                                                  with
 *
 * @return     <type>  ( description_of_the_return_value )
 */
function bp_like_ult_replace_strings_variables($item_id, $content_type, $string_to_display = "", $users_html_to_replace_with = array(), $others_count = -1){
  $string = "";
  //we know max showing count of users for like is 3
  //so switch goes till 4, the 4th variable is the others count
  // @see bp_like_ult_get_some_likes()
  $users_html_to_replace_with_count = count($users_html_to_replace_with);
  switch ($users_html_to_replace_with_count) {
    case 1:
        $string = sprintf($string_to_display, $users_html_to_replace_with[0]);
      break;
    case 2:
        $string = sprintf($string_to_display, $users_html_to_replace_with[0], $users_html_to_replace_with[1]);
      break;
    case 3:
    case 4:
        if($others_count != -1){
            $page_for_viewing_likes_id = bp_like_ult_get_settings("bp_likes_view_all_page");
            //if it has been set
            if(!empty($page_for_viewing_likes_id) && $page_for_viewing_likes_id != 0){
              $link_to_see_all_likes = esc_url( add_query_arg( array( 
                                              'bpl_type' => $content_type,
                                              'bpl_id' => $item_id ), 
                                            get_page_link($page_for_viewing_likes_id) ) 
                                      );
              $link_to_see_all_likes = apply_filters( 'bp_link_like_display_page_link', $link_to_see_all_likes, $page_for_viewing_likes_id, $item_id, $content_type );

              if($users_html_to_replace_with_count == 3){
                $link_to_see_all_likes = "<a class='bp_like_ult_view_all_likes_link' href='".$link_to_see_all_likes."'>".$users_html_to_replace_with[2]."</a>";

                $string = sprintf($string_to_display, $users_html_to_replace_with[0], $users_html_to_replace_with[1], $link_to_see_all_likes);
              }else{
                $link_to_see_all_likes = "<a class='bp_like_ult_view_all_likes_link' href='".$link_to_see_all_likes."'>".$users_html_to_replace_with[3]."</a>";
                
                $string = sprintf($string_to_display, $users_html_to_replace_with[0], $users_html_to_replace_with[1], $users_html_to_replace_with[2], $link_to_see_all_likes);
              }
            }else{
              //no page set so nothing to do
              if($users_html_to_replace_with_count == 3){
                $string = sprintf($string_to_display, $users_html_to_replace_with[0], $users_html_to_replace_with[1], $users_html_to_replace_with[2]);
              }else{
                $string = sprintf($string_to_display, $users_html_to_replace_with[0], $users_html_to_replace_with[1], $users_html_to_replace_with[2], $users_html_to_replace_with[3]);
              }
            }
        }else{ 
          $string = sprintf($string_to_display, $users_html_to_replace_with[0], $users_html_to_replace_with[1], $users_html_to_replace_with[2]);
        }
      break;
    default:
        //nothing to replace
        $string = $string_to_display;
      break;
  }
  return $string;
}


/*
 * bp_like_ult_get_some_likes()
 * @updated 1.0
 * Description: Returns a defined number of likers, beginning with more recent.
 *
 */
function bp_like_ult_get_template_vars( $id, $type ) {
  $vars = array();

  $is_liked = bp_like_ult_is_liked( $id, $type, get_current_user_id() );
  $vars['user-has-liked']  = $is_liked;
  if(bp_like_ult_use_ajax()){
    $vars['classes']  = $is_liked?'unlike':'like';
  }else{
    $vars['classes']  = $is_liked?'no-ajax-unlike':'no-ajax-like';    
  }
  $vars['classes'] .= bp_like_ult_get_settings('bp_like_ult_toggle_button')?' toggle':'';
  $vars['liked_count'] = count(  BPLIKE_ULT_LIKES::get_likers( $id, $type) );
  $vars['title'] = bp_like_ult_get_text( ( $is_liked?'unlike_this_item':'like_this_item' ) );
  $vars['static_like_unlike_link'] = $is_liked?bp_like_ult_get_item_unlike_link($type):bp_like_ult_get_item_like_link($type);

  return $vars;
}

/**
 *
 * bplikes_ult_view_who_likes() hook
 *
 */
function bplikes_ult_view_who_likes( $id,  $type, $start = '<p', $end = '</p>') {

    do_action( 'bp_like_ult_before_bplikes_ult_view_who_likes' );

    do_action( 'bplikes_ult_view_who_likes', $id, $type, $start, $end );

    do_action( 'bp_like_ult_after_bplikes_ult_view_who_likes' );

}

// TODO comment why this is here
add_action( 'bplikes_ult_view_who_likes' , 'bp_like_ult_get_some_likes', 10, 4 );
