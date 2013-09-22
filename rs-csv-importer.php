<?php
/*
Plugin Name: Really Simple CSV Importer
Description: Import posts, custom fields, taxonomies from csv file.
Author: Takuro Hishikawa, wokamoto
Author URI: http://notnil-creative.com/
Text Domain: rs-csv-importer
License: GPL version 2 or later - http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
Version: 0.1
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

// Load WP Post Helper
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
	var $columns = array();
	var $column_raw = array();
	
	/** Delimiter
	* @var string
	*/
	const DELIMITER = ",";
	
	// Utility functions
	function fopen($filename, $mode='r') {
		return fopen($filename, $mode);
	}

	function fgetcsv($handle, $length = 0) {
		return fgetcsv($handle, $length, self::DELIMITER);
	}

	function fclose($fp) {
		return fclose($fp);
 	}

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
		echo '<ol>';
		echo '<li>'.__( 'Select UTF-8 as charset.', 'rs-csv-importer' ).'</li>';
		echo '<li>'.sprintf( __( 'You must use field delimiter as "%s"', 'rs-csv-importer'), self::DELIMITER ).'</li>';
		echo '<li>'.__( 'You must quote all text cells.', 'rs-csv-importer' ).'</li>';
		echo '</ol>';
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
	
	/** Parsing header row, setup the columns definition.
	* @param array $data name of columns
	*/
	function parse_columns($data=array()) {
		$columns = array();
		foreach ($data as $key => $value) {
			$columns[$value] = $key;
		}
		$this->columns = $columns;
		$this->column_raw = $data;
	}
	
	/** Insert post and postmeta using wp_post_helper
	* @param array $post
	* @param array $meta
	* More information: https://gist.github.com/4084471
	*/
	function save_post($post,$meta) {
		$ph = new wp_post_helper($post);
		
		foreach ($meta as $key => $value) {
			$ph->add_meta($key,$value,true);
		}
		
		$ph->insert();
		
		unset($ph);
	}

	// process parse csv ind insert posts
	function process_posts() {
		global $wpdb;

		$handle = $this->fopen($this->file, 'r');
		if ( $handle == false ) {
			echo '<p><strong>'.__( 'Failed to open file.', 'rs-csv-importer' ).'</strong></p>';
			wp_import_cleanup($this->id);
			return false;
		}
		
		$is_first = true;
		
		echo '<ol>';
		
		while (($data = $this->fgetcsv($handle)) !== FALSE) {
			if ($is_first) {
				$this->parse_columns( $data );
				$is_first = false;
			} else {
				$post = array();
				
				// (string) post slug
				if (isset($this->columns['post_name']) &&
					isset($data[$this->columns['post_name']]) &&
					! empty($data[$this->columns['post_name']])) {
					$post['post_name'] = $data[$this->columns['post_name']];
					unset($data[$this->columns['post_name']]);
				}
				
				// (login or ID) post_author
				if (isset($this->columns['post_author']) &&
					isset($data[$this->columns['post_author']]) &&
					! empty($data[$this->columns['post_author']])) {
					$post_author = $data[$this->columns['post_author']];
					if (is_int($post_author)) {
						$post['post_author'] = $post_author;
					} else {
						$post_author = get_user_by('login',$post_author);
						if (is_object($post_author)) {
							$post['post_author'] = $data[$post_author->ID];
						}
					}
					unset($data[$this->columns['post_author']]);
					unset($post_author);
				}
				
				// (string) publish date
				if (isset($this->columns['post_date']) &&
					isset($data[$this->columns['post_date']]) &&
					! empty($data[$this->columns['post_date']])) {
					$post_date = $data[$this->columns['post_date']];
					$post_date = date("Y-m-d H:i:s", strtotime($post_date));
					$post['post_date'] = $post_date;
					unset($data[$this->columns['post_date']]);
					unset($post_date);
				}
				
				// (string) post type
				if (isset($this->columns['post_type']) &&
					isset($data[$this->columns['post_type']]) &&
					! empty($data[$this->columns['post_type']])) {
					$post['post_type'] = $data[$this->columns['post_type']];
					unset($data[$this->columns['post_type']]);
				}
				
				// (string) post status
				if (isset($this->columns['post_status']) &&
					isset($data[$this->columns['post_status']]) &&
					! empty($data[$this->columns['post_status']])) {
					$post['post_status'] = $data[$this->columns['post_status']];
					unset($data[$this->columns['post_status']]);
				}
				
				// (string) post title
				$post['post_title'] = '';
				if (isset($this->columns['post_title']) &&
					isset($data[$this->columns['post_title']]) &&
					! empty($data[$this->columns['post_title']])) {
					$post['post_title'] = $data[$this->columns['post_title']];
					unset($data[$this->columns['post_title']]);
				}
				
				// (string) post content
				if (isset($this->columns['post_content']) &&
					isset($data[$this->columns['post_content']]) &&
					! empty($data[$this->columns['post_content']])) {
					$post['post_content'] = $data[$this->columns['post_content']];
					unset($data[$this->columns['post_content']]);
				}
				
				// (string, comma divided) slug of post categories
				if (isset($this->columns['post_category']) &&
					isset($data[$this->columns['post_category']]) &&
					! empty($data[$this->columns['post_category']])) {
					$categories = preg_split("/[\s,]+/", $data[$this->columns['post_category']]);
					if ($categories) {
						$post['post_category'] = wp_create_categories($categories);
					}
					unset($data[$this->columns['post_category']]);
					unset($categories);
				}
				
				// (string, comma divided) name of post tags
				if (isset($this->columns['post_tags']) &&
					isset($data[$this->columns['post_tags']]) &&
					! empty($data[$this->columns['post_tags']])) {
					$tags = preg_split("/[\s,]+/", $data[$this->columns['post_tags']]);
					if ($tags) {
						$post['post_tags'] = $tags;
					}
					unset($data[$this->columns['post_tags']]);
				}
				
				$meta = array();
				foreach ($data as $key => $value) {
					if (!empty($value) && isset($this->column_raw[$key])) {
						$meta[$this->column_raw[$key]] = $value;
					}
				}
				
				$this->save_post($post,$meta);
				
				echo '<li>'.esc_html($post['post_title']).'</li>';
			}
		}
		
		echo '</ol>';

		$this->fclose($handle);
		
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

register_importer('csv', __('CSV', 'rs-csv-importer'), __('Import posts, custom fields, taxonomies from csv file.', 'rs-csv-importer'), array ($rs_csv_importer, 'dispatch'));

} // class_exists( 'WP_Importer' )