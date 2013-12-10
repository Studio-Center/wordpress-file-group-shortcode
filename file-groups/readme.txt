=== File Groups ===
Contributors: bmellor, mitchoyoshitaka
Author: mitcho (Michael Yoshitaka Erlewine), Brett Mellor
Author URI: http://ecs.mit.edu/
Tags: files, file groups, file, file group, attachment, tagging, tags, ecs
Requires at least: 3.1
Tested up to: 3.3.1
Stable tag: 1.1.5

Add "file group" multiple file handling capability.

== Description ==

WordPress's built-in file-management is built primarily for handling "media". But sometimes there's a need for curating collections of files within WordPress, not necessarily as media which will be displayed, but simply as files.

This plugin adds an abstraction called "file groups" to WordPress. Each file group can contain multiple files, which are presented elegantly to the user. All files in a file group can be downloaded together as a zip archive as well.

In addition, file groups can be tagged and these tags are used to associate particular file groups to your posts. Two widgets, "related file groups" and "upload related file group", are supplied to make it easier to integrate the display of related file groups with your posts.

The plugin currently uses the PHP [ZipArchive extension](http://php.net/manual/en/class.ziparchive.php) to support the batch download functionality.

This plugin is a component of the [MIT Educational Collaboration Space](http://ecs.mit.edu) project.

== Installation ==

1. Upload the `file-groups` folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Optionally activate the "related file groups" and/or "upload related file group" widgets.

You should now see a new item in your WordPress admin menu called "File Groups". By default, File Groups will hide the native Media Library. You can keep the native Media Library around by adding the following to your `wp-config.php` file:

`	define( 'FILE_GROUPS_HIDE_NATIVE_MEDIA', false );`

If your WordPress installation has some files uploaded from before File Groups is installed, you will most likely want to migrate these files into their own file groups. See the "Migration" section under "Other Notes" for more information.

== Frequently Asked Questions ==

= Your question here! =

Our answer here!

== Screenshots ==

1. A nice interface for creating file groups.
2. A file group, as viewed by visitors.
3. File groups have tags and these are used to "relate" file groups to posts. A "related file groups" widget and "upload related file group here" widget are included.
4. Files in your WordPress installation not attached to file groups ("orphans") can be easily turned into their own file groups. See the "Migration" section of the readme for details.

== Changelog ==

= 1.1.5 = 
* related file groups widget (widgets.php) defaulted to showing only first five groups because numberposts was not set.  numberposts set to -1 to show all related groups

= 1.1.4 =
* At some point in time between WP 3.1 and WP 3.3.1, WP changed the id of the #media-buttons div on the editing interface to #wp-content-media-buttons.  file-groups.js has been updated to reflect this change.

= 1.1.3 =
* Immediate security fix for [documented SQL injection vulnerability](http://www.exploit-db.com/exploits/17677/).

= 1.1.2 =
* add capability to store a "file group description" as post meta along with the file group
* add description column to edit.php?post_type=file_groups
* enable category taxonomy for file groups post type
* remove_menu_page made conditional so as not to break wp ver < 3.1.0

= 1.1.1 =
* Fixed [a bug](http://wordpress.org/support/topic/plugin-file-groups-uploadinsert-icons-missing-in-action) with the "create related file group" button.

= 1.1 =
* Added tools for migration of files into file groups. See "Migration" section of the readme.
* Fixed a bug where the "files in group" column was showing up in other list tables.

= 1.0 =
* Initial public release.

== Migration ==

File Groups has some tools which make it easy for you to migrate files which are not in file groups (known as "orphans") into file groups.

First things first, you will need to (temporarily) unhide the native Media Library from your admin by adding the following to your `wp-config.php` file:

`	define( 'FILE_GROUPS_HIDE_NATIVE_MEDIA', false );`

In the Media Library, you will now see a drop down which will let you "show only orphans". Select any collection of files there which you would like to migrate into a file group, and then select the "create new file group" action from the "Bulk actions" menu and click "apply". A new file group will be created for the files you selected.

Alternatively, individual orphan files have an action called "create singleton group" available to them if you hover over its row in the table.