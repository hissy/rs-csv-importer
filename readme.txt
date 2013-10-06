=== Really Simple CSV Importer ===
Contributors: hissy, wokamoto
Tags: importer, csv, acf
Requires at least: 3.0
Tested up to: 3.6.1
Stable tag: 0.5
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Import posts, categories, tags, custom fields from simple csv file.

== Description ==

Alternative CSV Importer plugin. Simple and powerful.

* Category support
* Tag support
* Custom field support
* Adcanved Custom Fields support (beta)
* Custom Taxonomy support
* Custom Post Type support

Contains CSV file sample in `/wp-content/plugins/really-simple-csv-importer/sample` directory.

= Available column names and values: =
* `ID` or `post_id`: (int) post id.  
  This value is not required. The post ID is already exists in your blog, importer will update that post data. If the ID is not exists, importer will trying to create a new post with suggested ID.
* `post_author`: (login or ID) author
* `post_date`: (string) publish date
* `post_content`: (string) post content
* `post_title`: (string) post title
* `post_excerpt`: (string) post excerpt
* `post_status`: (string) post status
* `post_name`: (string) post slug
* `post_parent`: (int) post parent id. Used for page or hierarchical post type.
* `menu_order`: (int)
* `post_type`: (string) post type
* `post_category`: (string, comma divided) slug of post categories
* `post_tags`: (string, comma divided) name of post tags
* `{custom_field}`: (string) any other column labels used as custom field
* `{tax_$taxonomy}`: (string, comma divided) any field prefixed with tax_ in the "custom_field" area will be used as a custom taxonomy. Taxonomy must already exist. Entries are names, not slugs

= Advanced Custom Fields plugin integrate =
If advanced custom field key is exists, importer will trying to use [update_field](http://www.advancedcustomfields.com/resources/functions/update_field/) function instead of built-in add_post_meta function.  
How to find advanced custom field key: [Finding the field key](http://www.advancedcustomfields.com/resources/functions/update_field/#finding-the%20field%20key)

Note: multiple value is not supported yet.

= Official public repository =
Add star and read future issues about rs-csv-importer on [GitHub](https://github.com/hissy/rs-csv-importer)!

== Installation ==

1. Upload All files to the `/wp-content/plugins/` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Go to the Import page under Tools menu.
4. Click CSV link, read the notification, then just upload and import.

== How to customize import rules == 

There are three filters available in the importer.

= really_simple_csv_importer_save_post =

This filter is applied to post data.

Parameters:

* $post - (array)(required) post data
* $is_update (bool) update existing post data, or insert new post data

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

* $meta - (array)(required) post meta data
* $post - (array) post data
* $is_update (bool)

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

* $tax - (array)(required) post taxonomy data
* $post - (array) post data
* $is_update (bool)

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

= 0.5 =
* New feature: Added filter hooks to customize import data
* Bug fix
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
