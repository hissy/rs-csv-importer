<?php
/*
Plugin Name: Really Simple CSV Importer
Plugin URI: http://wordpress.org/plugins/really-simple-csv-importer/
Description: Import posts, categories, tags, custom fields from simple csv file.
Author: Takuro Hishikawa, wokamoto
Author URI: http://notnil-creative.com/
Text Domain: rs-csv-importer
License: GPL version 2 or later - http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
Version: 0.3
*/

if ( !defined('WP_LOAD_IMPORTERS') )
	return;

// Load Importer API
require_once ABSPATH . 'wp-admin/includes/import.php';

if ( !class_exists( 'WP_Importer' ) ) {
	$class_wp_importer = ABSPATH . 'wp-admin/includes/class-wp-importer.php';
	if ( file_exists( $class_wp_importer ) )
		require_once $class_wp_importer;
}

// Load Helpers
require dirname( __FILE__ ) . '/rs-csv-helper.php';
require dirname( __FILE__ ) . '/wp_post_helper/class-wp_post_helper.php';

/**
 * CSV Importer
 *
 * @package WordPress
 * @subpackage Importer
 */
if ( class_exists( 'WP_Importer' ) ) {
class RS_CSV_Importer extends WP_Importer {
	
	/** Sheet columns
	* @value array
	*/
	var $column_indexes = array();
	var $column_keys = array();

 	// User interface wrapper start
	function header() {
		echo '<div class="wrap">';
		screen_icon();
		echo '<h2>'.__('Import CSV', 'rs-csv-importer').'</h2>';
	}

	// User interface wrapper end
	function footer() {
		echo '</div>';
	}
	
	// Step 1
	function greet() {
		echo '<p>'.__( 'Choose a CSV (.csv) file to upload, then click Upload file and import.', 'rs-csv-importer' ).'</p>';
		echo '<p>'.__( 'Maybe Excel-style CSV file is not best for import data. Follow export options below. LibreOffice might be good for you.', 'rs-csv-importer' ).'</p>';
		echo '<ol>';
		echo '<li>'.__( 'Select UTF-8 as charset.', 'rs-csv-importer' ).'</li>';
		echo '<li>'.sprintf( __( 'You must use field delimiter as "%s"', 'rs-csv-importer'), RS_CSV_Helper::DELIMITER ).'</li>';
		echo '<li>'.__( 'You must quote all text cells.', 'rs-csv-importer' ).'</li>';
		echo '</ol>';
		echo '<p>'.__( 'Sample CSV file download:', 'rs-csv-importer' );
		echo ' <a href="'.plugin_dir_url( __FILE__ ).'sample/sample.csv">'.__( 'csv', 'rs-csv-importer' ).'</a>,';
		echo ' <a href="'.plugin_dir_url( __FILE__ ).'sample/sample.ods">'.__( 'ods (OpenDocument Spreadsheet file format)', 'rs-csv-importer' ).'</a>';
		echo '</p>';
		wp_import_upload_form( add_query_arg('step', 1) );
	}

	// Step 2
	function import() {
		$file = wp_import_handle_upload();

		if ( isset( $file['error'] ) ) {
			echo '<p><strong>' . __( 'Sorry, there has been an error.', 'rs-csv-importer' ) . '</strong><br />';
			echo esc_html( $file['error'] ) . '</p>';
			return false;
		} else if ( ! file_exists( $file['file'] ) ) {
			echo '<p><strong>' . __( 'Sorry, there has been an error.', 'rs-csv-importer' ) . '</strong><br />';
			printf( __( 'The export file could not be found at <code>%s</code>. It is likely that this was caused by a permissions problem.', 'rs-csv-importer' ), esc_html( $file['file'] ) );
			echo '</p>';
			return false;
		}
		
		$this->id = (int) $file['id'];
		$this->file = get_attached_file($this->id);
		$result = $this->process_posts();
		if ( is_wp_error( $result ) )
			return $result;
	}
	
	/** Insert post and postmeta using wp_post_helper
	* @param array $post
	* @param array $meta
	* @param bool $is_update
	* More information: https://gist.github.com/4084471
	*/
	function save_post($post,$meta,$terms,$is_update) {
		$ph = new wp_post_helper($post);
		
		foreach ($meta as $key => $value) {
			$is_acf = 0;
			if (function_exists('get_field_object')) {
				if (strpos($key, 'field_') === 0) {
					$fobj = get_field_object($key);
					if (is_array($fobj) && isset($fobj['key']) && $fobj['key'] == $key) {
						$ph->add_field($key,$value);
						$is_acf = 1;
					}
				}
			}
			if (!$is_acf)
				$ph->add_meta($key,$value,true);
		}

		foreach ($terms as $key => $value) {
			$ph->add_terms($key, $value);
		}
		
		if ($is_update)
			$result = $ph->update();
		else
			$result = $ph->insert();
		
		unset($ph);
		
		return $result;
	}

	// process parse csv ind insert posts
	function process_posts() {
		$h = new RS_CSV_Helper;

		$handle = $h->fopen($this->file, 'r');
		if ( $handle == false ) {
			echo '<p><strong>'.__( 'Failed to open file.', 'rs-csv-importer' ).'</strong></p>';
			wp_import_cleanup($this->id);
			return false;
		}
		
		$is_first = true;
		
		echo '<ol>';
		
		while (($data = $h->fgetcsv($handle)) !== FALSE) {
			if ($is_first) {
				$h->parse_columns( $this, $data );
				$is_first = false;
			} else {
				$post = array();
				$is_update = false;
				
				// (int) post id
				$post_id = $h->get_data($this,$data,'ID');
				$post_id = ($post_id) ? $post_id : $h->get_data($this,$data,'post_id');
				if ($post_id) {
					$post_exist = get_post($post_id);
					if ( is_null( $post_exist ) ) {
						$post['import_id'] = $post_id;
					} else {
						$post['ID'] = $post_id;
						$is_update = true;
					}
				}
				
				// (string) post slug
				$post_name = $h->get_data($this,$data,'post_name');
				if ($post_name) {
					$post['post_name'] = $post_name;
				}
				
				// (login or ID) post_author
				$post_author = $h->get_data($this,$data,'post_author');
				if ($post_author) {
					if (is_numeric($post_author)) {
						$user = get_user_by('id',$post_author);
					} else {
						$user = get_user_by('login',$post_author);
					}
					if (isset($user) && is_object($user)) {
						$post['post_author'] = $user->ID;
						unset($user);
					}
				}
				
				// (string) publish date
				$post_date = $h->get_data($this,$data,'post_date');
				if ($post_date) {
					$post['post_date'] = date("Y-m-d H:i:s", strtotime($post_date));
				}
				
				// (string) post type
				$post_type = $h->get_data($this,$data,'post_type');
				if ($post_type) {
					$post['post_type'] = $post_type;
				}
				
				// (string) post status
				$post_status = $h->get_data($this,$data,'post_status');
				if ($post_status) {
					$post['post_status'] = $post_status;
				}
				
				// (string) post title
				$post['post_title'] = $h->get_data($this,$data,'post_title');
				
				// (string) post content
				$post_content = $h->get_data($this,$data,'post_content');
				if ($post_content) {
					$post['post_content'] = $post_content;
				}
				
				// (string) post excerpt
				$post_excerpt = $h->get_data($this,$data,'post_excerpt');
				if ($post_excerpt) {
					$post['post_excerpt'] = $post_excerpt;
				}
				
				// (int) post parent
				$post_parent = $h->get_data($this,$data,'post_parent');
				if ($post_parent) {
					$post['post_parent'] = $post_parent;
				}
				
				// (int) menu order
				$menu_order = $h->get_data($this,$data,'menu_order');
				if ($menu_order) {
					$post['menu_order'] = $menu_order;
				}
				
				// (string, comma divided) slug of post categories
				$post_category = $h->get_data($this,$data,'post_category');
				if ($post_category) {
					$categories = preg_split("/[\s,]+/", $post_category);
					if ($categories) {
						$post['post_category'] = wp_create_categories($categories);
					}
				}
				
				// (string, comma divided) name of post tags
				$post_tags = $h->get_data($this,$data,'post_tags');
				if ($post_tags) {
					$tags = preg_split("/[\s,]+/", $post_tags);
					if ($tags) {
						$post['post_tags'] = $tags;
					}
				}
				
				$meta = array();
				$tax = array();

				foreach ($data as $key => $value) {
					if (!empty($value) && isset($this->column_keys[$key])) {
						// check if meta is custom taxonomy
						if (substr($this->column_keys[$key], 0, 4) == 'tax_') {
							// (string, comma divided) name of custom taxonomies 
							
							// modified preg_split to only split on commas
							$customtaxes = preg_split("/,\s+/", $value);
							$taxname = substr($this->column_keys[$key], 4);
							$tax[$taxname] = array();
							foreach($customtaxes as $key => $value ) {
								$tax[$taxname][] = $value;
							}
						}
						else {
							$meta[$this->column_keys[$key]] = $value;
						}
					}
				}
				
				$result = $this->save_post($post,$meta,$tax,$is_update);
				if (!$result) {
					echo '<li>'.sprintf(__('An error occurred during processing %s', 'rs-csv-importer'), esc_html($post['post_title'])).'</li>';
				} else {
					echo '<li>'.esc_html($post['post_title']).'</li>';
				}
			}
		}
		
		echo '</ol>';

		$h->fclose($handle);
		
		wp_import_cleanup($this->id);
		
		echo '<h3>'.__('All Done.', 'rs-csv-importer').'</h3>';
	}

	// dispatcher
	function dispatch() {
		$this->header();
		
		if (empty ($_GET['step']))
			$step = 0;
		else
			$step = (int) $_GET['step'];

		switch ($step) {
			case 0 :
				$this->greet();
				break;
			case 1 :
				check_admin_referer('import-upload');
				set_time_limit(0);
				$result = $this->import();
				if ( is_wp_error( $result ) )
					echo $result->get_error_message();
				break;
		}
		
		$this->footer();
	}
	
}

// setup importer
$rs_csv_importer = new RS_CSV_Importer();

register_importer('csv', __('CSV', 'rs-csv-importer'), __('Import posts, categories, tags, custom fields from simple csv file.', 'rs-csv-importer'), array ($rs_csv_importer, 'dispatch'));

} // class_exists( 'WP_Importer' )
