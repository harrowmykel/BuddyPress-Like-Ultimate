<?php
// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * bp_like_ult_enqueue_scripts()
 *
 * Includes the terms required by plugins Javascript.
 *
 */
function bp_like_ult_enqueue_scripts() {

    wp_register_script( 'bplike-ult', plugins_url( '/assets/js/bp-like-ult.js', dirname( __FILE__ ) ), array( 'jquery' ), BP_LIKE_ULT_VERSION );

    if ( ! is_admin() ) {

        wp_enqueue_script( 'bplike-ult' );

        wp_localize_script( 'bplike-ult', 'bplikeTerms', array(
                'like'           => bp_like_ult_get_text( 'like' ),
                'unlike'         => bp_like_ult_get_text('unlike'),
                'like_message'   => bp_like_ult_get_text( 'like_this_item' ),
                'unlike_message' => bp_like_ult_get_text( 'unlike_this_item' ),
                'you_like_this'  => bp_like_ult_get_text( 'get_likes_only_liker' ),
                'fav_remove'     => bp_like_ult_get_settings( 'remove_fav_button' ) == 1 ? '1' : '0'
            )
        );

        wp_enqueue_style( 'bplike-ult-css', plugins_url( '/assets/css/bplike-ult.css', dirname( __FILE__ ) ));
    }
}
add_action( 'wp_enqueue_scripts' , 'bp_like_ult_enqueue_scripts' );
