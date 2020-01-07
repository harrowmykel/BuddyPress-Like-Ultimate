<?php 
// Exit if accessed directly
defined( 'ABSPATH' ) || exit;


/**
 * Shows all post likes.
 * @since 1.0
 * @param      <type>  $atts   The Shortcode attributes
 *
 *[bp-user max=20 per_page=12 item_id='use_get' item_type='use_get' page='use_get']
 *'max' => 20, max to show
 *'per_page'=>10, amount to show per page
 *'item_id' => 'use_get',//id of item, post, comment, page
 *'item_type' => 'use_get',//type of item, post, comment, page
 *'page' => 'use_get'//0-infinity
	
	if(use_get), takes values from http get url
	//bpl_type, bpl_id, page;
 * @return     <type>  ( description_of_the_return_value )
 */
	function bp_like_ult_show_all_post_likes($atts){
		global $bp_like_ult_members_display_query;
		$atts = shortcode_atts( array(
			'max' => false,
			'per_page'=>11,
			'item_id' => 'use_get',
			'item_type' => 'use_get',
			'page' => 'use_get'
		), $atts, 'bp-like' );

		$item_type = ($atts["item_type"]=="use_get")?(isset($_GET["bpl_type"])?$_GET["bpl_type"]:0):$atts["item_type"];
		$item_id = ($atts["item_id"]=="use_get")?(isset($_GET["bpl_id"])?$_GET["bpl_id"]:0):$atts["item_id"];
		$page = ($atts["page"]=="use_get")?(isset($_GET["page"])?$_GET["page"]:0):$atts["page"];
		$max = $atts["max"];
		$per_page = $atts["per_page"];

	//dont trust users
		$item_type = sanitize_text_field($item_type);
		$item_id = sanitize_text_field($item_id);
		$page = sanitize_text_field($page);
		$max = ($max)?sanitize_text_field($max):$max;
		$per_page = sanitize_text_field($per_page);
		$all_likers_id = BPLIKE_ULT_LIKES::get_likers($item_id, $item_type);
		if(count($all_likers_id) > 0){
			$all_likers_id = implode(",", $all_likers_id);
		}else{
		$all_likers_id = 0;//show none
	}

	$bp_like_ult_members_display_query = "include=".$all_likers_id."&page=".$page."&max=".$max."&per_page=".$per_page;

	add_filter('bp_ajax_querystring', "bp_like_ult_pass_members_query_to_core", 999);

	$template_location = apply_filters("bp_like_ult_members_template_location", "members/members-loop", $item_id, $item_type);
	ob_start();
	bp_get_template_part($template_location);
	$content = ob_get_clean();
	$content ="<div class='bp-like-contents'>".$content."</div>";
	return $content;  
}

 ?>