<?php

/**
 * WIDGETS
 */

class Related_File_Groups_Widget extends WP_Widget {
	function Related_File_Groups_Widget() {
		$this->WP_Widget(false, __('Related File Groups Widget', 'file-groups'));
	}

	function form($instance) {
	}

	function update($new_instance, $old_instance) {
	}

	function widget($args, $instance) {
	  global $post, $id, $revision;
		$the_tags = get_the_tags( 0 );
		if (!$the_tags)
			return;
		// File groups aren't related to other file groups. That gets confusing.
		if ($post->post_type == 'file_groups')
			return;

	  $groups = get_posts(array('tag__and' => array_keys($the_tags), 'post_type' => 'file_groups', 'numberposts' => -1));
    
    if (count($groups)):
      ?><div id="related-file-groups">
      <h2>Related file groups</h2>
      <p>This widget displays all file groups with the tag(s) <?php the_tags('');?>.</p>
      <?php 
      echo "<ul>";
      foreach ($groups as $filegroup) {
        echo '<li>';
        echo '<a href="'.get_permalink( $filegroup->ID ).'"> 
        '.get_the_title( $filegroup->ID ).'</a> ';
        echo '</li>';
      }
      echo "</ul></div>";
    endif;
	}

}

class Add_File_Group_Widget extends WP_Widget {
	function Add_File_Group_Widget() {
		$this->WP_Widget('add_file_group_widget', __('Upload Related File Group', 'file-groups'));
	}

	function form($instance) {
	}

	function update($new_instance, $old_instance) {
	}

	function widget($args, $instance) {
		global $post;
		extract($args);
		wp_reset_query();
		
		if (!is_singular())
			return;
		if (!is_page() && !is_single())
			return;
		if (is_home())
			return;
		// We can't relate a file group to another file group.
		if ($post->post_type == 'file_groups')
			return;
		
		$tags = wp_get_post_tags($post->ID);
		$tags = join(',', array_map('fg_return_tag_name', $tags));
		$url = admin_url( "post-new.php?post_type=file_groups&tags=" . $tags );

		echo $before_widget;
		echo '<p><a class="button" target="_new" href="' . $url . '">' .
			__("Upload Related File Group", 'file-groups') .
			'</a></p>';
		echo $after_widget;
	}
}

add_action('widgets_init', 'fg_register_widgets');
function fg_register_widgets() {
	register_widget('Related_File_Groups_Widget');
	register_widget('Add_File_Group_Widget');
}
