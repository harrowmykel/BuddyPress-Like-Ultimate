<?php
// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * bp_like_ult_get_settings()
 *
 * Returns settings from the database
 *
 */
function bp_like_ult_get_settings( $option = false ) {

    $settings = get_site_option( 'bp_like_ult_settings' );

    if ( ! $option ) {
        return $settings;
    } else {
        return isset($settings[$option])?$settings[$option]:false;
    }
}


add_action( 'init', 'bp_like_ult_remove_favourites' );

function bp_like_ult_remove_favourites() {
    if( bp_like_ult_get_settings('remove_fav_button') == 1 ) {

        add_filter( 'bp_activity_can_favorite', '__return_false', 1 );
        add_filter( 'bp_get_total_favorite_count_for_user', '__return_false', 1 );
        bp_core_remove_nav_item('favorites');
        bp_core_remove_subnav_item( 'activity', 'favorites');

        function bp_like_ult_admin_bar_render_remove_favorites() {
            global $wp_admin_bar;
            $wp_admin_bar->remove_menu('my-account-activity-favorites');
        }
        add_action( 'wp_before_admin_bar_render' , 'bp_like_ult_admin_bar_render_remove_favorites' );
    }
}


function bp_like_ult_use_ajax(){
    return apply_filters( 'bp_like_ult_use_ajax_for_likes', !empty(bp_like_ult_get_settings('bp_like_ult_use_ajax_for_likes')));
}
