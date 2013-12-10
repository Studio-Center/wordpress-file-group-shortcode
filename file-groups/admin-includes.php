<?php

function fg_hide_media_menu() {
	if (defined('FILE_GROUPS_HIDE_NATIVE_MEDIA') && FILE_GROUPS_HIDE_NATIVE_MEDIA)
		if ( function_exists(remove_menu_page) )
			remove_menu_page('upload.php');
		else
			echo "<style type='text/css'> #menu-media {display:none;} </style>";
	
}

// FILE GROUPS LIST TABLE

// Add the "files in group" column to the file groups edit screen
function fg_columns($columns) {
	global $current_screen;
	if ($current_screen->post_type == "file_groups")
		$columns['file_list'] = __('Files in group', 'file-groups');
		$columns['shortcode'] = __('Shortcode for posts and pages', 'file-groups');
		$columns['fg_description'] = __('Description', 'file-groups');
	return $columns;
}

// Displays the "files in group" column in the file groups edit screen
function fg_column_list($column_name) {
	global $post;
	switch( $column_name){
		case 'file_list':
		// strip the trailing comma and space
			echo rtrim($post->post_content, ", ");
		break;
		case 'fg_description':
			echo get_post_meta($post->ID, 'fg_description', true);
		break;
		case 'shortcode':
			echo '[filegroup groupid=\''.$post->ID.'\']';
		break;
	}
		return; 
}

// FILE GROUP EDIT SCREEN

// Make upload form multipart-encoded
function fg_add_multipart_to_form() {
	global $current_screen;
	if ($current_screen->base == 'post' && $current_screen->post_type == 'file_groups')
		echo " enctype='multipart/form-data'";
}

// Add file group editing metabox
function fg_edit_box() {
	add_meta_box( 'files', 'Files in this group', 'fg_files_metabox', 'file_groups' );
}
// Display the file group metabox
function fg_files_metabox() {
	global $post;

	// echo "<pre>Post ID: " . $post->ID . "</pre></br>";

	// is a file being deleted?
	if ( isset( $_GET['fg_del_file'] ) ) {
		$file_names = fg_del_file( $_GET['fg_del_file'] );
	} else {
		$post_record = get_post($post->ID);
		$file_names = $post_record->post_content;
	}

	// wordpress will process form field with name='content' as post_content.  normally it is a <textarea>
	echo "<input type='hidden' name='content' id='fg_filenames' value='$file_names'>";
	// echo "<pre>post_content: $file_names</pre>";


	// show files currently in group
	fg_show_files($post->ID, 'true');

	// get the group comment
	$fg_description = get_post_meta($post->ID, 'fg_description', true);

	echo "<div id='queueContainer'>
		<b>Files to add:</b><div id='queue'></div>
		<div id='inputs'><input name='file[0]' id='file0' type='file' size='42' onchange='fileGroups.enQueue(this);'></div>
		</div><!-- queueContainer -->";
		echo "<b>File Group Description:</b> <i>(optional)</i><br><input type='text' name='fg_description' style='width:100%;' value='$fg_description'>";
}

function fg_save_data($post_id) {
	// Save data from meta box	
	// TODO: maybe we want to use wp_verify_nonce?

	// check autosave
	if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE)
		return;

	// check permissions
	if (!current_user_can('edit_page', $post_id))
		return;
	
	if ( !isset($_FILES['file']) )
		return;

	// save the comment
	update_post_meta($post_id, 'fg_description', $_POST['fg_description']);
	
	// first let's straighten out the $_FILES array, because it comes in kinda sideways relative to how it would be useful
	foreach ($_FILES['file'] as $key => $subarr) {
		foreach ($subarr as $subkey => $subvalue) {
			$upfiles[$subkey][$key] = $subvalue;
		}
	}

	// $num = count($upfiles);
	// echo "Number of files uploaded: $num<p>";

	foreach ($upfiles as $row) {
		if ($row['error'] > 0)
			continue;

		// filename as passed in by browser
		$pathinfo = pathinfo($row['name']);
		// strip off the extension
		$fileslug = $pathinfo['filename'];

		// writes the uploaded file to disk, creates a global unique id, and mime_types the file
		$fileinfo = wp_handle_upload($row, array( 'test_form' => false ), current_time('mysql'));
		$guid = $fileinfo['url'];
		$filetype = $fileinfo['type'];

		$fields = array(
			'post_author' => $user_ID,
			'post_mime_type' => $filetype,
			'post_title' => $fileslug,
			'post_name' => $fileslug,
			'guid' => $guid,
			'post_content' => '',
			'post_status' => 'inherit'
		);

		wp_insert_attachment($fields, $file, $post_id);
	}	
}

// MODS TO OTHER EDIT SCREENS

function fg_remove_media_buttons() {
	// Remove default media buttons
	remove_action( 'media_buttons', 'media_buttons' );
}

function fg_enqueue_scripts() {
	$fg_script_src = plugins_url( 'file-groups.js', __FILE__ );
	wp_enqueue_script( 'file-groups', $fg_script_src, array( 'jquery' ) , '1.0', true );
}

function fg_related_url() {
	global $current_screen;
	
	$related_url = admin_url( "post-new.php?post_type=file_groups&tags=" ); 
	echo "<script type='text/javascript'>var fg_related_url = '$related_url';</script>";
}

function fg_insert_initial_tags() {
	global $post;
	if ( $post->post_status == 'auto-draft' && isset( $_GET['tags'] ) )
		wp_set_post_tags( $post->ID, $_GET['tags'] );
}

// MODS TO MEDIA LIBRARY
// @since 1.1

function fg_media_filter_menu() {
	global $current_screen;

	if ($current_screen->id != "upload")
		return;
	
	if (isset($_REQUEST['fg-orphanage']))
		$orphanage = $_REQUEST['fg-orphanage'];
	else
		$orphanage = '';
		
	?>
	<select id="fg-orphanage" name="fg-orphanage">
		<option<?php selected( '', $orphanage ); ?> value=""><?php _e("Show all", 'file-groups'); ?></option>
		<option<?php selected( 'children', $orphanage ); ?> value="children"><?php _e("Show only items in file groups", 'file-groups'); ?></option>
		<option<?php selected( 'orphans', $orphanage ); ?> value="orphans"><?php _e("Show only orphans", 'file-groups'); ?></option>
	</select>
	<?php	
}

function fg_filter_media_request($query_vars) {
	global $current_screen;

	if ($current_screen->id != "upload" || !isset($_REQUEST['fg-orphanage']))
		return $query_vars;
	
	$orphans = fg_get_orphans();
	if ($_REQUEST['fg-orphanage'] == 'orphans')
		$query_vars['post__in'] = $orphans;
	else if ($_REQUEST['fg-orphanage'] == 'children')
		$query_vars['post__not_in'] = $orphans;
		
	return $query_vars;
}

// @returns an array of orphaned attachment IDs
function fg_get_orphans() {
	global $wpdb;
	return $wpdb->get_col("select attachments.ID from {$wpdb->posts} as attachments left join {$wpdb->posts} as filegroups on (attachments.post_parent = filegroups.ID and filegroups.post_type = 'file_groups') where filegroups.ID is null and attachments.post_type = 'attachment'");
}

function fg_media_bulk_actions($actions) {
	$actions['create_file_group'] = __("Create New File Group", 'file-groups');
	return $actions;
}

function fg_media_js_bulk_actions() {
	global $current_screen;			

	if ($current_screen->id != "upload")
		return;

	echo "<script type='text/javascript'>jQuery(window).load(function() {jQuery('select[name=\"action\"]').append('<option value=\"create_file_group\">" .
		__("Create New File Group", 'file-groups') . "</option>')})</script>";
}

function fg_media_row_actions($actions, $post, $detached) {
	global $fg_cached_orphans, $wp_query;
	
	if (!isset($fg_cached_orphans))
		$fg_cached_orphans = fg_get_orphans();

	// if orphan...
	if (array_search($post->ID, $fg_cached_orphans) !== false) {
		$baseurl = remove_query_arg(array('action', 'action2', 'media'), $_SERVER['REQUEST_URI']);
		$url = wp_nonce_url(add_query_arg(array(
			'media' => array($post->ID),
			'action' => 'create_file_group'
		), $baseurl), 'bulk-media');
		$actions['create_singleton_file_group'] = "<a href='$url'>" . __("Create Singleton File Group", 'file-group') . "</a>";
	}
		
	return $actions;
}

// HACK! This is the only way (I think) we can add another custom action to the
// list table right now in 3.1. :(
function fg_intercept_redirect_and_do_actions($location, $status) {
	global $doaction, $wpdb;
	if (!isset($doaction) || !isset($_REQUEST['media']) || $doaction != 'create_file_group')
		return $location;
	
	$media = $_REQUEST['media'];
	if (!count($media))
		return $location;

	// verify that the media chosen are not already in a file group.
	foreach ($media as $id) {
		$post = get_post($id);
		if (!$post->post_parent)
			continue;
		if (get_post_type($post->post_parent) == 'file_groups')
			wp_die(__("One of these files is already in a file group.",'file-groups'));
	}
	
	// Collect the tags that we will apply to the file group.
	// If tags were used for media, we use that, but we also look for the existence of
	// "media-tags" which could have come from the Media Tags plugin.
	// Also collect the file names.
	$tags = array();
	$filenames = array();
	foreach ($media as $id) {
		$mytags = get_the_terms((int) $id, 'post_tag');
		if ($mytags && count(mytags)) {
			$tags = array_merge($tags, $mytags);
		}
		
		if (defined('MEDIA_TAGS_TAXONOMY')) {
			$mytags = get_the_terms((int) $id, MEDIA_TAGS_TAXONOMY);
			if ($mytags)
				$tags = array_merge($tags, $mytags);
		}
		
		$post = get_post($id);
		$filenames[] = basename($post->guid);
	}
	// Convert tags into an array of text strings.
	$tags = array_unique(array_map('fg_return_tag_name', $tags));

	$first_ID = (int) $media[0];
	$first = get_post($first_ID);
	$post_ID = wp_insert_post(array(
		'post_title' => get_the_title((int) $media[0]),
		'post_content' => join(', ', $filenames),
		'post_status' => 'publish',
		'post_type' => 'file_groups',
		'post_author' => $first->post_author,
		'tags_input' => join(',', $tags)
	));

	if ( !$post_ID )
		wp_die(__("There was a problem creating the new file group.", 'file-groups'));

	// Add the attachments to the file group
	foreach ($media as $id) {
		$wpdb->update( $wpdb->posts,
			array( 'post_parent' => $post_ID ),
			array( 'ID' => (int) $id ),
			array( '%s' ),
			array( '%d' ));
	}

	// If we clicked on a row action instead of using the bulk action,
	// the GET parameters we built based on the current list table query is ignored.
	// Fix that here.
	if (!strstr($location, '?'))
		$location = remove_query_arg(array('action', 'action2', 'media'), $_SERVER['REQUEST_URI']);
	return add_query_arg( array( 'fg_new_id' => $post_ID ), $location );
}

function fg_admin_notices() {
	global $current_screen;			

	if ($current_screen->id != "upload" || !isset($_REQUEST['fg_new_id']))
		return;

	$id = (int) $_REQUEST['fg_new_id'];
	$url = admin_url("post.php?action=edit&post=$id");
	echo '<div class="updated"><p>' . __("New file group created.", 'file-groups') .
		" <a href='$url'>" . __("View File Group", 'file-groups') . '</a></p></div>';
}

// MISC

function fg_delete_all_files($post_id) {
	global $wpdb;
	// TODO: replace this with a get_children
	$ids = $wpdb->get_col($wpdb->prepare("SELECT ID FROM {$wpdb->posts} WHERE post_parent = %d AND post_type = 'attachment'", $post_id ));
	foreach ( $ids as $id ) {
		wp_delete_attachment($id);
	}
}