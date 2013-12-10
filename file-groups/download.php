<?php

$wppath = preg_replace( '!wp-content.*$!', '', __FILE__ );

require( $wppath . 'wp-load.php' );

if ( !isset($_GET['fgid']) )
	wp_die( __("No file group specified", 'file-groups') );
// file group parent of all attachments/files about to be tarred and zipped
$fgid = $_GET['fgid'];

global $wpdb;
$file_list = $wpdb->get_col($wpdb->prepare("select guid from wp_posts where post_parent = %d", $fgid));

$uploads_path = WP_CONTENT_URL . "/uploads/";
foreach ($file_list as $key => $path) {
	$file_list[$key] = str_replace($uploads_path, '', $file_list[$key]);
}

// var_dump($file_list);
// exit;

$post = get_post($fgid);
$title = $post->post_name;
// Certain characters are not allowed in file names.
// This is actually different by platform, but let's just make life easier...
$title = preg_replace("![:/\\\\]!", '-', $title) . '.zip';

chdir(WP_CONTENT_DIR . '/uploads');
create_zip($file_list, "/tmp/files.zip");

// ship it
header("Content-type: application/zip");
header("Content-Disposition: attachment; filename={$title}");
header("Pragma: no-cache");
header("Expires: 0");
readfile('/tmp/files.zip');
exit;

function create_zip($files = array(), $destination = '', $overwrite = true) {

  // if the zip file already exists and overwrite is false, return false
  if (file_exists($destination) && !$overwrite) { return false; }

  $valid_files = array();
  // if files were passed in...
  if (is_array($files)) {
    // cycle through each file
    foreach ($files as $file) {
      // make sure the file exists
      if (file_exists($file))
        $valid_files[] = $file;
    }
  }

  // if we have good files...
  if (count($valid_files)) {
    // create the archive

    $zip = new ZipArchive();

    if ($zip->open($destination,$overwrite ? ZIPARCHIVE::OVERWRITE : ZIPARCHIVE::CREATE) !== true) {
      return false;
    }
    //add the files
    foreach ($valid_files as $file) {
      $zip->addFile($file,$file);
    }
    
    // close the zip -- done!
    $zip->close();
    
    // check to make sure the file exists
    return file_exists($destination);
  } else {
    return false;
  }
}
