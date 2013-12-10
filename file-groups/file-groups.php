<?php
/*
Plugin Name: File Groups
Plugin URI: http://ecs.mit.edu/
Description: Add "file group" multiple file handling capability
Version: 1.1.5
Author: mitcho (Michael Yoshitaka Erlewine), Brett Mellor
Author URI: http://ecs.mit.edu/
*/

include( dirname(__FILE__) . '/admin-includes.php' );
include( dirname(__FILE__) . '/widgets.php' );

// By default File Groups will hide the native Media Library in your admin.
// Hiding of native media can be overridden by defining
// FILE_GROUPS_HIDE_NATIVE_MEDIA to be false in your wp-config file.
if ( !defined('FILE_GROUPS_HIDE_NATIVE_MEDIA') )
	define( 'FILE_GROUPS_HIDE_NATIVE_MEDIA', true );

// Infrastructure: admin + custom post type
add_action('init', 'fg_register');
add_action('admin_enqueue_scripts', 'fg_enqueue_scripts');
add_action('admin_menu', 'fg_hide_media_menu');

// Setup file-group edit screen:
add_action('add_meta_boxes', 'fg_edit_box');
add_action('save_post', 'fg_save_data');
add_action('post_edit_form_tag', 'fg_add_multipart_to_form');

// Add "related file group" button to media buttons
add_action('in_admin_header', 'fg_related_url');
add_action('admin_init', 'fg_remove_media_buttons');

// Insert initial tags when coming from "create related file group" link
add_action('dbx_post_advanced','fg_insert_initial_tags');

// If a file group is deleted, delete all it's files
add_action('delete_post', 'fg_delete_all_files');

// Display the file list on the file group post archive page
add_filter('the_content','fg_archive_page');

// Add custom columns to the listing of file groups
add_filter('manage_posts_columns', 'fg_columns');
add_action('manage_posts_custom_column', 'fg_column_list', 10, 2);

// Enable filtering in Media Library by orphan status
// @since 1.1
add_action('restrict_manage_posts', 'fg_media_filter_menu');
add_filter('request', 'fg_filter_media_request');

// Enable "create new file group" bulk action in Media Library
// @since 1.1
// TODO: do it the right way, once this is enabled in the future:
// add_filter('bulk_actions-upload', 'fg_media_bulk_actions');
add_action('restrict_manage_posts', 'fg_media_js_bulk_actions');
add_filter('media_row_actions', 'fg_media_row_actions', 10, 3);
add_filter('wp_redirect', 'fg_intercept_redirect_and_do_actions', 10, 2);
add_action('all_admin_notices', 'fg_admin_notices');

// added to display file groups within pages or posts using short codes
// [filegroup id="group-id"]
function filegroup_get( $atts ) {
	fg_list_files($atts['groupid'], false);
}
add_shortcode( 'filegroup', 'filegroup_get' );

// Creates new post type and add it to the admin menu
function fg_register() {
	register_post_type('file_groups', array(
		'label' => __('File Groups', 'file-groups'),
		'labels' => array(
			'singular_name' => __('File Group', 'file-groups'),
			'add_new_item' => __('Add New File Group', 'file-groups'),
			'edit_item' => __('Edit File Group', 'file-groups'),
			'new_item' => __('New File Group', 'file-groups'),
			'view_item' => __('View File Group', 'file-groups'),
			'search_items' => __('Search File Groups', 'file-groups'),
			'not_found' => __('No file groups found', 'file-groups'),
			'not_found_in_trash' => __('No file groups found in Trash', 'file-groups')
		),
		'public' => true,
		'hierarchical' => false,
		'supports' => array('title', 'author', 'comments'),
		'taxonomies' => array('post_tag','category')
	));
}

// used to list files on the front-end
function fg_list_files($post_id){
	global $wpdb;
	
	echo '<div class="file_group" id="file_group_'.trim(strtolower(get_the_title($post_id))).'">';
	
	echo '<h4>'.get_the_title($post_id).'</h4>';
	
	$attachments = get_posts(array(
		'post_type' => 'attachment',
		'numberposts' => -1,
		'post_status' => null,
		'post_parent' => $post_id,
		'orderby' => 'title',
		'order' => 'ASC'
	));

	// If there are no attachments, end here
	if (!$attachments) {
		echo "<p><em>" .
			__("There are no files in this group.", 'file-groups') .
			"</em></p>";
		return;
	}
	
	// print list
	$state = 0;
	echo '<ol class="fg_list">';
	foreach ( $attachments as $attachment ) {		

		if($state == 2) { $state = 0; }
		
		$url = wp_get_attachment_url($attachment->ID);
		$textlink = get_the_title($attachment->ID);
		
		echo "<li class='fg_list_item".($state == 0 ? ' odd' : ' even')."'>";
		
		echo "<a title='$textlink' href='$url'>$textlink</a>";
		
		echo "</li>";
	
		$state++;
	
	}
	echo '</ol>';
	
	echo '</div>';
	
}

// used to display files within admin interface
function fg_show_files($post_id, $edit) {
	// list the files attached to this group
	// if $edit = true, show option to delete files 
	global $wpdb;

	// print these inline as fg_show_files can also be called from the outside, not just admin
	echo "<link href='" . plugins_url('file-groups.css', __FILE__) .
		"' type='text/css' rel='stylesheet'>";
	echo "<style type='text/css'>.fg_xit { background-image: url('" .
		admin_url('images/xit.gif') .
		"') }</style>";

	$attachments = get_posts(array(
		'post_type' => 'attachment',
		'numberposts' => -1,
		'post_status' => null,
		'post_parent' => $post_id,
		'orderby' => 'title',
		'order' => 'ASC'
	));

	// If there are no attachments, end here
	if (!$attachments) {
		echo "<p><em>" .
			__("There are no files in this group.", 'file-groups') .
			"</em></p>";
		return;
	}

	foreach ( $attachments as $attachment ) {
				
		echo "<div class='fg_list_item'>";

		echo "<div class='iconWrap'>" .
			wp_get_attachment_link($id = $attachment->ID, $size = '46x60', $permalink = false, $icon = true) .
			"</div>";

		if ($edit)
			echo "<a title='" . __("delete file", 'file-groups') .
			"' href='?post={$post_id}&action=edit&fg_del_file={$attachment->ID}' class='fg_xit'></a>";

		$url = wp_get_attachment_url($attachment->ID);
		$textlink = get_the_title($attachment->ID);

		echo "<div class='fg_list_item_link'><a target=_blank' title='" . $textlink .
			"' href='?post={$attachment->ID}&action=edit'>$textlink</a><br/>";

		// get the file extension back
		// TODO: this seems fragile?
		if ( preg_match( '/^.*?\.(\w+)$/', wp_get_attachment_url( $attachment->ID ), $matches ) )
			echo esc_html( strtoupper( $matches[1] ) );
		else
			echo strtoupper( str_replace( 'image/', '', get_post_mime_type( $attachment->ID ) ) );

		echo "</div></div>";
	}

	if ( count( $attachments ) > 1 ) {
		$zip_icon = wp_mime_type_icon('archive');
		$dl_url = plugins_url( "download.php?fgid=" . $post_id, __FILE__ );
		echo "<div class='fg_list_item'><div class='iconWrap'><img width='46' height='60' class='attachment-46x60' src='{$zip_icon}'></div><a title='" .
			__("Download all files in group", 'file-groups') .
			"' href='$dl_url'>" .
			__("Download all files in group", 'file-groups') .
			"</a><br>ZIP</div>";
	}
}

function fg_del_file($attachID) {
	global $wpdb;

	// First, we are going to recreate the name of the file and then delete it's presence from the post_content field of the parent file group of this attachment
	// The post_content field of the parent file group contains the original filenames of all it's attachments/children

	// attachment record as an object
	$attach_record = get_post($attachID);

	// this is just the base part of the filename
	$filename = $attach_record->post_title;

	// let's get the original filename extension from the attachment url, which is the only place it exists in the attachment record.  thank you wordpress.
	// TODO: there must be a better way... - mitcho
	preg_match( '/^.*?\.(\w+)$/', $attach_record->guid, $matches );
	// add the . and the extension, and a trailing comma and space, 'cause that's how it went in to begin with
	$filename .= "." . $matches[1] . ", ";

	// parent (file group) record as an object
	$parent_record = get_post($attach_record->post_parent);

	// list of all the file names
	$file_list = $parent_record->post_content;

	// delete the original attachment filename from the file list
	$file_list = str_replace($filename, '', $file_list);
	$file_list = str_replace(', , ', ', ', $file_list);
	$file_list = preg_replace('/(^, |, $)/', '', $file_list);

	// put the edited file list back into the parent's post_content
	$wpdb->update( $wpdb->posts,
		array( 'post_content' => $file_list ),
		array( 'ID' => $parent_record->ID ),
		array( '%s' ),
		array( '%d' ));

	echo "<div class='updated below-h2'><p>" .
		sprintf( __("%s deleted", 'file-groups'), trim(str_replace(',','',$filename)) ) . 
		"</p></div>";

	wp_delete_attachment($attachID, $force_delete = true);

	// return the updated file list, sans the name of the file just deleted
	return $file_list;
}

function fg_archive_page($content) {
	global $post;
	if (get_post_type($post) == 'file_groups') {
		$output = fg_show_files($post->ID, '');
		$output .= "<div style='clear:both;'><strong>File Group Description: </strong><br>" . get_post_meta($post->ID, 'fg_description', true) ."</div>";
		return $output;
		}
	else
		return $content;
}

// Utility function:

function fg_return_tag_name($tag) {
	return $tag->name;
}
