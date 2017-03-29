<?php
/* Plugin Name: Post Views Count
 * Plugin URI: http://idomedia.ca
 * Description: Count views for posts of any post types and register a most views post widget
 * Author: Fred Hong
 * Author URI: http://www.fredhong.ca
**/
add_action( 'init', 'idopvc_init', 1 );
function idopvc_init() {
	load_plugin_textdomain( 'idopvc', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
}

register_uninstall_hook( __FILE__, 'idopvc_uninstaller' );
function idopvc_uninstaller() {
	global $wpdb;
	$wpdb->query( 'DELETE FROM ' . $wpdb->postmeta . ' WHERE meta_key LIKE "_count-views%"' );
}

function idopvc_add_post_columns( $columns ) {
	$columns['idopvc'] = 'Post Views Count';
	return $columns;
}

add_action( 'ido_count_views_render_post_columns', 'idopvc_render_post_columns_action' );
function idopvc_render_post_columns_action( $post_id ) {
	echo (int) get_post_meta( $post_id, '_count-views_all', true );
}

function idopvc_render_post_columns( $column_name, $post_id ) {
	if( $column_name == 'idopvc' && current_user_can( 'edit_posts' ) ) {
		do_action( 'ido_count_views_render_post_columns', $post_id );
	}
}

add_action( 'load-edit.php', 'idopvc_admin_init' );
function idopvc_admin_init() {
	if ( current_user_can( 'edit_posts' ) ){
		$args          = array('public'   => true, '_builtin' => false);
		$post_types    = get_post_types($args);
		$post_types[]  = 'post'; 
		foreach ( $post_types as $cpt ) {
			add_action( 'manage_' . $cpt . '_posts_columns', 'idopvc_add_post_columns', 10, 2 );
			add_action( 'manage_' . $cpt . '_posts_custom_column', 'idopvc_render_post_columns', 10, 2 );
		}
	}
}

add_filter( 'the_content', 'update_post_views', 100 );
add_filter( 'bbp_get_the_content', 'update_post_views', 100);
function update_post_views( $content ) {
	
	global $post;

	// $args          = array('public'   => true, '_builtin' => false);
	// $post_types    = get_post_types($args);
	$post_types  = array('post', 'topic', 'place', 'advert'); 

	if(is_single($post) && in_array($post->post_type, $post_types) && is_main_query()){
	
		$timings = array( 'all'=>'', 'day'=>'Ymd', 'week'=>'YW', 'month'=>'Ym', 'year'=>'Y' );
		
		foreach( $timings as $time=>$date ) {
			if( $time != 'all' ) {
				$date = '-' . date( $date );
			}

			$meta_key = '_count-views_' . $time . $date;
			$count_type = 'count_' . $time . $date;
			$$count_type = (int) get_post_meta( $post->ID, $meta_key, true );

			if(empty($$count_type)){
				$$count_type = 0;
			}
			++$$count_type;
			update_post_meta( $post->ID, $meta_key, $$count_type );
		}
	}

	return $content;
}

