=== Really Simple CSV Importer ===
Contributors: hissy, wokamoto
Tags: importer, csv
Requires at least: 3.0
Tested up to: 3.6.1
Stable tag: 0.2
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Import posts, categories, tags, custom fields from simple csv file.

== Description ==

Alternative CSV Importer plugin. Simple and powerful.

* Category support
* Tag support
* Custom field support
* Custom post type support

Contains CSV file sample in `/wp-content/plugins/really-simple-csv-importer/sample` directory.

= Available column names and values: =
* ID or post_id: (int) post id. update post data if this value is defined. default is insert
* post_author: (login or ID) author
* post_date: (string) publish date
* post_content: (string) post content
* post_title: (string) post title
* post_status: (string) post status
* post_name: (string) post slug
* post_type: (string) post type
* post_category: (string, comma divided) slug of post categories
* post_tags: (string, comma divided) name of post tags
* {custom_field}: any other column labels used as custom field

Add star and read future issues about rs-csv-importer on [GitHub](https://github.com/hissy/rs-csv-importer)!

== Installation ==

1. Upload All files to the `/wp-content/plugins/` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Go to the Import page under Tools menu.
4. Click CSV link, read the notification, then just upload and import.

== Changelog ==

= 0.2 =
* New feature: Add post_id column. It enables to update post data.
* Some bug fixes
= 0.1.1 =
* Bug fix
= 0.1 =
* First Release (beta)
