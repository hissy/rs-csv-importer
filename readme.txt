=== Really Simple CSV Importer ===
Contributors: hissy, wokamoto
Tags: importer, csv, acf
Requires at least: 3.0
Tested up to: 3.7.1
Stable tag: 0.6.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Import posts, categories, tags, custom fields from simple csv file.

== Description ==

Alternative CSV Importer plugin. Simple and powerful.

* Category support
* Tag support
* Custom field support
* Advanced Custom Fields support (beta)
* Custom Taxonomy support
* Custom Post Type support

Contains CSV file examples in `/wp-content/plugins/really-simple-csv-importer/sample` directory.

= Available column names and values: =
* `ID` or `post_id`: (int) post id.  
  This value is not required. The post ID is already exists in your blog, importer will update that post data. If the ID is not exists, importer will trying to create a new post with suggested ID.
* `post_author`: (login or ID) The user name or user ID number of the author.
* `post_date`: (string) The time of publish date.
* `post_content`: (string) The full text of the post.
* `post_title`: (string) The title of the post.
* `post_excerpt`: (string) For all your post excerpt needs.
* `post_status`: ('draft' or 'publish' or 'pending' or 'future' or 'private' or custom registered status) The status of the post. 'draft' is default.
* `post_name`: (string) The slug of the post.
* `post_parent`: (int) The post parent id. Used for page or hierarchical post type.
* `menu_order`: (int)
* `post_type`: ('post' or 'page' or any other post type name) *(required)* The post type slug, not labels.
* `post_thumbnail`: (string) The uri or path of the post thumbnail.  
  E.g. http://example.com/example.jpg or /path/to/example.jpg
* `post_category`: (string, comma divided) slug of post categories
* `post_tags`: (string, comma divided) name of post tags
* `{custom_field}`: (string) Any other column labels used as custom field
* `tax_{taxonomy}`: (string, comma divided) Any field prefixed with tax_ in the "custom_field" area will be used as a custom taxonomy. Taxonomy must already exist. Entries are names or slugs of terms.

Note: Empty cells in the csv file means "keep it", not "delete it".  
Note: To set the page template of a page, use custom field key of `_wp_page_template`.  
Note: If providing a post_status of 'future' you must specify the post_date in order for WordPress to know when to publish your post.

= Advanced Custom Fields plugin integrate =
If advanced custom field key is exists, importer will trying to use [update_field](http://www.advancedcustomfields.com/resources/functions/update_field/) function instead of built-in add_post_meta function.  
How to find advanced custom field key: [Finding the field key](http://www.advancedcustomfields.com/resources/functions/update_field/#finding-the%20field%20key)

= Official public repository =
Add star and read future issues about rs-csv-importer on [GitHub](https://github.com/hissy/rs-csv-importer)!

= Thanks =
Cover banner designed by @[luchino__](http://uwasora.com/)

== Installation ==

1. Upload All files to the `/wp-content/plugins/` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Go to the Import page under Tools menu.
4. Click CSV link, read the notification, then just upload and import.

== Frequently Asked Questions ==

= Should I fill all columns of post data? =

No. Only columns you need.

= Can I update existing post data? =

Yes. Please use ID field.

= Can I insert post with specific post id? =

Yes. Please use ID field.

= Can I import custom field/custom taxonomy of the post? =

Yes. You can use column names same as wp_post table, but if the column name does not match, it creates a custom field (post meta) data. Importing custom taxonomy is a bit more complicated, "tax_{taxonomy}" means, "tax_" is prefix, and {taxonomy} is name of custom taxonomy (not labels).

Here is a example.

**csv file**  
"post_title","released","tax_actors"  
"Captain Phillips","2013","Tom Hanks, Barkhad Abdi, Barkhad Abdirahman"  

**imported post data**  
Post Title: Captain Phillips  
Custom field "released": 2013  
Custom taxonomy "Actors": Tom Hanks, Barkhad Abdi, Barkhad Abdirahman

= Why should I quote text cells when I save csv file? =

Because PHP cannot read multibyte text cells in some cases.

> Locale setting is taken into account by this function. If LANG is e.g. en_US.UTF-8, files in one-byte encoding are read wrong by this function.

= Can I insert multiple value to ACF field like Select or Checkbox? =

Yes. Please use `really_simple_csv_importer_save_meta` filter to make array data.

== How to debug import data == 

*Really Simple CSV Importer Debugger add-on* enables you to dry-run-testing and show more detailed post, meta, taxonomy data of each csv row.  
Download from [gist](https://gist.github.com/hissy/7175656).

== How to customize import post data == 

There are three filters available in the importer.

= really_simple_csv_importer_save_post =

This filter is applied to post data.

Parameters:

* `$post` - (array)(required) post data
* `$is_update` - (bool) update existing post data, or insert new post data

Example:

`
function really_simple_csv_importer_save_post_filter( $post, $is_update ) {
	
	// remove specific tag from import data
	if (isset($post['post_tags'])) {
		$_tags = array();
		foreach ($post['post_tags'] as $tag) {
			if ($tag != 'Apple') {
				$_tags[] = $tag;
			}
		}
		$post['post_tags'] = $_tags;
	}
	
	return $post;
}
add_filter( 'really_simple_csv_importer_save_post', 'really_simple_csv_importer_save_post_filter', 10, 2 );
`

= really_simple_csv_importer_save_meta =

This filter is applied to post meta data.

Parameters:

* `$meta` - (array)(required) post meta data
* `$post` - (array) post data
* `$is_update` - (bool)

Example:

`
function really_simple_csv_importer_save_meta_filter( $meta, $post, $is_update ) {
	
	// serialize metadata
	$meta_array = array();
	if (isset($meta['meta_key_1'])) $meta_array[] = $meta['meta_key_1'];
	if (isset($meta['meta_key_2'])) $meta_array[] = $meta['meta_key_2'];
	$meta = array( 'meta_key' => $meta_array );
	
	return $meta;
}
add_filter( 'really_simple_csv_importer_save_meta', 'really_simple_csv_importer_save_meta_filter', 10, 3 );
`

= really_simple_csv_importer_save_tax =

This filter is applied to post taxonomy data (categories and tags are not included, these are post data).

Parameters:

* `$tax` - (array)(required) post taxonomy data
* `$post` - (array) post data
* `$is_update` - (bool)

Example:

`
function really_simple_csv_importer_save_tax_filter( $tax, $post, $is_update ) {
	
	// Fix misspelled taxonomy
	if (isset($tax['actors'])) {
		$_actors = array();
		foreach ($tax['actors'] as $actor) {
			if ($actor == 'Johnny Dep') {
				$actor = 'Johnny Depp';
			}
			$_actors[] = $actor;
		}
		$tax['actors'] = $_actors;
	}
	
	return $tax;
}
add_filter( 'really_simple_csv_importer_save_tax', 'really_simple_csv_importer_save_tax_filter', 10, 3 );
`

== Changelog ==

= 0.5.7 =
* Enhancement: Add dry run filter
= 0.5.6 =
* Bug fix: Fails to update empty custom field value.
= 0.5.5 =
* Bug fix: Fix to enable to update post meta values.
= 0.5.4 =
* Enhancement: Check the post type is already exists.
* Update readme
= 0.5.2 =
* New feature: Add Post Thumbnail support
* Bug fixes
= 0.5.1 =
* Enhancement: Check whether both posts has same post type when updating.
= 0.5 =
* New feature: Added filter hooks to customize import data
= 0.4.2 =
* Post title bug fix
= 0.4.1 =
* Version fix
= 0.4 =
* New feature: Added custom taxonomy support. Thanks chuckhendo!
= 0.3 =
* New feature: Advanced Custom Fields integrate.
* Enhancement: Use post_id if not already present when inserting post.
= 0.2 =
* New feature: Add post_id column. It enables to update post data.
* Some bug fixes
= 0.1.1 =
* Bug fix
= 0.1 =
* First Release (beta)
