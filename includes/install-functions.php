<?php
// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

function bp_like_ult_get_default_text_strings( $text = false ) {
    $default_text_strings = array(
        'like' => __( 'Like' , 'buddypress-like-ult' ),
        'unlike' => __( 'Unlike' , 'buddypress-like-ult' ),
        'like_this_item' => __( 'Like this item' , 'buddypress-like-ult' ),
        'unlike_this_item' => __( 'Unlike this item' , 'buddypress-like-ult' ),
        'update_likes' => __( 'Update Likes' , 'buddypress-like-ult' ),
        'show_blogpost_likes' => __( 'Blog Post Likes' , 'buddypress-like-ult' ),
        'must_be_logged_in' => __( 'Sorry, you must be logged in to like that.' , 'buddypress-like-ult' ),
        'record_activity_likes_own' => __( '%user% liked their own <a href="%permalink%">update</a>' , 'buddypress-like-ult' ),
        'record_activity_likes_an' => __( '%user% liked an <a href="%permalink%">update</a>' , 'buddypress-like-ult' ),
        'record_activity_likes_users' => __( '%user% liked %author%\'s <a href="%permalink%">update</a>' , 'buddypress-like-ult' ),
        'record_activity_likes_own_blogpost' => __( '%user% liked their own blog post <a href="%permalink%">%title%</a>' , 'buddypress-like-ult' ),
        'record_activity_likes_a_blogpost' => __( '%user% liked a blog post <a href="%permalink%">%title%</a>' , 'buddypress-like-ult' ),
        'record_activity_likes_users_blogpost' => __( '%user% liked %author%\'s blog post <a href="%permalink%">%title%</a>' , 'buddypress-like-ult' ),
        'get_likes_only_liker' => __( 'You like this.' , 'buddypress-like-ult' ) ,
        'get_likes_you_and_singular' => __( 'You and %count% other person like this.' , 'buddypress-like-ult' ) ,
        'you_and_username_like_this' => __( 'You and %s like this.' , 'buddypress-like-ult' ) ,
        'you_and_two_usernames_like_this' => __( 'You, %s and %s like this.' , 'buddypress-like-ult' ) ,
        'get_likes_you_and_plural' => __( 'You and %count% other people like this' , 'buddypress-like-ult' ) ,
        'get_likes_count_people_singular' => __( '%count% person likes this.' , 'buddypress-like-ult' ) ,
        'get_likes_count_people_plural' => __( '%count% people like this.' , 'buddypress-like-ult' ) ,
        'get_likes_and_people_singular' => __( 'and %count% other person like this.' , 'buddypress-like-ult' ) ,
        'get_likes_and_people_plural' => __( 'and %count% other people like this.' , 'buddypress-like-ult' ) ,
        'three_like_this' => __( '%s, %s and %s like this.' , 'buddypress-like-ult' ) ,
        'two_like_this' => __( '%s and %s like this.' , 'buddypress-like-ult' ) ,
        'one_likes_this' => __( '%s likes this.' , 'buddypress-like-ult' ) ,
        'get_likes_no_friends_you_and_singular' => __( 'None of your friends like this yet, but you and %count% other person does.' , 'buddypress-like-ult' ) ,
        'get_likes_no_friends_you_and_plural' => __( 'None of your friends like this yet, but you and %count% other people do.' , 'buddypress-like-ult' ) ,
        'get_likes_no_friends_singular' => __( 'None of your friends like this yet, but %count% other person does.' , 'buddypress-like-ult' ) ,
        'get_likes_no_friends_plural' => __( 'None of your friends like this yet, but %count% other people do.' , 'buddypress-like-ult' )
    );

    return $text ? $default_text_strings[$text] : $default_text_strings;
}
/**
 * bp_like_ult_install()
 *
 * Installs or upgrades the database content
 */
function bp_like_ult_install() {

    $current_settings = get_site_option( 'bp_like_ult_settings' );

    if ( $current_settings['enable_notifications'] ) {
      $enable_notifications = $current_settings['enable_notifications'];
    } else {
      $enable_notifications = 1;
    }

    if ( $current_settings['enable_blog_post_support'] ) {
      $enable_blog_post_support = $current_settings['enable_blog_post_support'];
    } else {
      $enable_blog_post_support = 0;
    }

    if ( $current_settings['post_to_activity_stream'] ) {
        $post_to_activity_stream = $current_settings['post_to_activity_stream'];
    } else {
        $post_to_activity_stream = 0;
    }

    if ( $current_settings['show_excerpt'] ) {
        $show_excerpt = $current_settings['show_excerpt'];
    } else {
        $show_excerpt = 0;
    }

    if ( $current_settings['excerpt_length'] ) {
        $excerpt_length = $current_settings['excerpt_length'];
    } else {
        $excerpt_length = 140;
    }

    if ( $current_settings['likers_visibility'] ) {
        $likers_visibility = $current_settings['likers_visibility'];
    } else {
        $likers_visibility = 'show_all';
    }

    if ( $current_settings['name_or_avatar'] ) {
        $name_or_avatar = $current_settings['name_or_avatar'];
    } else {
        $name_or_avatar = 'name';
    }
    if ( $current_settings['remove_fav_button']) {
        $remove_fav_button = $current_settings['remove_fav_button'];
    } else {
        $remove_fav_button = '0';
    }

    if ( $current_settings['bp_like_ult_toggle_button']) {
        $toggle_button = $current_settings['bp_like_ult_toggle_button'];
    } else {
        $toggle_button = '0';
    }

    if ( $current_settings['bp_like_ult_post_types']) {
        $bp_like_ult_post_types = $current_settings['bp_like_ult_post_types'];
    } else {
        $bp_like_ult_post_types = array('post', 'page');
    }

    $default_text_strings = bp_like_ult_get_default_text_strings();
    $text_strings         = array();

    if ( $current_settings['text_strings'] ) {

        /* Go through each string and update the default to the current default, keep the custom settings */
        foreach ( $default_text_strings as $string_name => $string_contents ) {

            $custom = $current_settings['text_strings'][$string_name]['custom'];

            if ( !empty($custom) )
                $text_strings[$string_name] = $custom;
        }
    }

    $settings = array(
        'likers_visibility'        => $likers_visibility,
        'post_to_activity_stream'  => $post_to_activity_stream,
        'show_excerpt'             => $show_excerpt,
        'excerpt_length'           => $excerpt_length,
        'text_strings'             => $text_strings,
        'name_or_avatar'           => $name_or_avatar,
        'remove_fav_button'        => $remove_fav_button,
        'bp_like_ult_toggle_button'    => $toggle_button,
        'bp_like_ult_post_types'       => $bp_like_ult_post_types,
        'enable_notifications'     => $enable_notifications
    );

    bp_like_ult_update_db_version_52();

    update_site_option( 'bp_like_ult_db_version', BP_LIKE_ULT_DB_VERSION );
    update_site_option( 'bp_like_ult_settings', $settings );

    add_action( 'admin_notices', 'bp_like_ult_updated_notice' );
}

/**
 * change deprecated activity action 'bbp_reply_like' to 'blog_post_like'
 * and like type 'bbp_reply' to 'blog_post'
 *
 **/
function bp_like_ult_update_db_version_52() {
    global $wpdb, $bp;

    // update notifications action
    $wpdb->query( "UPDATE {$bp->notifications->table_name} SET
        component_action = CONCAT('blog_post_like_', SUBSTRING_INDEX(component_action, '_', -1 ) )
        WHERE  component_action LIKE 'bbp_reply_like%'" );

    // update like type
    $wpdb->update( $bp->likes->table_name, array( 'like_type' => 'blog_post'), array( 'like_type' => 'bbp_reply') );
}

/**
 * bp_like_ult_check_installed()
 *
 * Checks to see if the DB tables exist or if you are running an old version
 * of the component. If it matches, it will run the installation function.
 * This means we don't have to deactivate and then reactivate.
 *
 */
function bp_like_ult_check_installed() {
    global $wpdb;

    if ( ! is_super_admin() ) {
        return false;
    }

    if ( ! get_site_option( 'bp_like_ult_settings' ) || get_site_option( 'bp-like-db-version' ) ) {
        bp_like_ult_install();
    }

    if ( get_site_option( 'bp_like_ult_db_version' ) < BP_LIKE_ULT_DB_VERSION ) {
        bp_like_ult_install();
    }
}

add_action( 'admin_menu', 'bp_like_ult_check_installed' );

/*
 * The notice we show if the plugin is updated.
 */
function bp_like_ult_updated_notice() {

    if ( ! is_super_admin() ) {
        return false;
    } else {
        echo '<div id="message" class="updated fade"><p style="line-height: 150%">';
        printf( __( '<strong>BuddyPress Like Ultimate</strong> has been successfully updated to version %s.' , 'buddypress-like-ult' ) , BP_LIKE_ULT_VERSION );
        echo '</p></div>';
    }
}


/*
 * The notice we show when the plugin is installed.
 */

function bp_like_ult_install_buddypress_notice() {
    echo '<div id="message" class="error fade"><p style="line-height: 150%">';
    _e( '<strong>BuddyPress Like Ultimate</strong></a> requires the BuddyPress plugin to work. Please <a href="http://buddypress.org">install BuddyPress</a> first, or <a href="plugins.php">deactivate BuddyPress Like Ultimate</a>.' , 'buddypress-like-ult' );
    echo '</p></div>';
}

function bp_like_ult_init_like_count_total($post_id, $post, $update) {
    if (!$update && in_array($post->post_type, bp_like_ult_get_settings('bp_like_ult_post_types'))) {
        /* save total like count, so posts can be ordered by likes */
        add_post_meta( $post_id , 'bp_liked_count_total' , count(  BPLIKE_ULT_LIKES::get_likers($post_id, 'blog_post') ) );
    }
}

function bp_like_ult_setup_post_insert_hooks() {
	if ( bp_like_ult_get_settings( 'enable_blog_post_support' ) == 1 &&
	     bp_like_ult_get_settings('bp_like_ult_post_types') ) {
		add_action('wp_insert_post', 'bp_like_ult_init_like_count_total', 10, 3);
	}
}
add_action( 'init', 'bp_like_ult_setup_post_insert_hooks');

