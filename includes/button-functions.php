<?php

// utilize BP_Button instead of current setup
// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

function bp_like_ult_setup_button() {
  // Filters to display BuddyPress Like Ultimate button.
  add_action( 'bp_activity_entry_meta', 'bplike_ult_activity_update_button' );
  add_action( 'bp_activity_comment_options', 'bplike_ult_activity_comment_button' );

  // only add this action hook is blog post support is enabled in settings
  if ( bp_like_ult_get_settings( 'enable_blog_post_support' ) == 1 ) {
    add_action( 'bp_before_blog_single_post', 'bplike_ult_blog_post_button' );
  }
}
add_action( 'init', 'bp_like_ult_setup_button');
