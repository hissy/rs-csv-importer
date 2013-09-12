=== Really Simple CSV Importer ===
Contributors: hissy, wokamoto
Tags: importer, csv
Requires at least: 3.0
Tested up to: 3.6
Stable tag: 0.1
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Import posts, custom fields, taxonomies from csv file.

== Description ==

Alternative CSV Importer plugin. Simple and powerful.

* Category support
* Tag support
* Custom field support
* Custom post type support

Contains CSV file sample in plugin_dir/rs-csv-importer/sample .

Available values:
* post_name: (string) post slug
* post_author: (login or ID) author
* post_date: (string) publish date
* post_type: (string) post type
* post_status: (string) post status
* post_title: (string) post title
* post_content: (string) post content
* post_category: (string, comma divided) slug of post categories
* post_tags: (string, comma divided) name of post tags
* {custom_field}: any other column labels used as custom field

== Installation ==

1. Upload All files to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress

== Changelog ==

= 0.1 =
* First Release (beta)