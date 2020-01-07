<?php
// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * bp_like_ult_process_ajax()
 *
 * Runs the relevant function depending on what AJAX call has been made.
 *
 */
function bp_like_ult_process_ajax() {

    // ensuring $id only contains an integer
    $id = preg_replace( "/\D/" , "" , $_POST['id'] );


    if ($_POST['method'] == 'like')
        bp_like_ult_add_user_like( $id , $_POST['type'] );
    else if ($_POST['method'] == 'unlike')
        bp_like_ult_remove_user_like( $id , $_POST['type'] );

    die();

}
add_action( 'wp_ajax_activity_like' , 'bp_like_ult_process_ajax' );

/**
 * bp_like_ult_ajax_get_likes()
 *
 */
function bp_like_ult_ajax_get_likes() {

  // ensuring $id only contains an integer
  $id = preg_replace( "/\D/" , "" , $_POST['id'] );

  if ( $_POST['type'] == 'blog_post_comment' ) {

    bplikes_ult_view_who_likes( $id , 'blog_post_comment',  '<span', '</span>' );

  } else {

      bplikes_ult_view_who_likes( $id , $_POST['type'] );

  }

  die();
}
add_action( 'wp_ajax_bplike_ult_get_likes', 'bp_like_ult_ajax_get_likes', 10, 1);
