<?php
/*
Plugin Name: Really Simple CSV Importer
Plugin URI: http://wordpress.org/plugins/really-simple-csv-importer/
Description: Import posts, categories, tags, custom fields from simple csv file.
Author: Takuro Hishikawa
Author URI: https://en.digitalcube.jp/
Text Domain: really-simple-csv-importer
License: GPL version 2 or later - http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
Version: 1.3
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
require dirname( __FILE__ ) . '/class-rs_csv_helper.php';
require dirname( __FILE__ ) . '/class-rscsv_import_post_helper.php';

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
	public $column_indexes = array();
	public $column_keys = array();

 	// User interface wrapper start
	function header() {
		echo '<div class="wrap">';
		echo '<h2>'.__('Import CSV', 'really-simple-csv-importer').'</h2>';
	}

	// User interface wrapper end
	function footer() {
		echo '</div>';
	}
	
	// Step 1
	function greet() {
		echo '<p>'.__( 'Choose a CSV (.csv) file to upload, then click Upload file and import.', 'really-simple-csv-importer' ).'</p>';
		echo '<p>'.__( 'Excel-style CSV file is unconventional and not recommended. LibreOffice has enough export options and recommended for most users.', 'really-simple-csv-importer' ).'</p>';
		echo '<p>'.__( 'Requirements:', 'really-simple-csv-importer' ).'</p>';
		echo '<ol>';
		echo '<li>'.__( 'Select UTF-8 as charset.', 'really-simple-csv-importer' ).'</li>';
		echo '<li>'.sprintf( __( 'You must use field delimiter as "%s"', 'really-simple-csv-importer'), RS_CSV_Helper::DELIMITER ).'</li>';
		echo '<li>'.__( 'You must quote all text cells.', 'really-simple-csv-importer' ).'</li>';
		echo '</ol>';
		echo '<p>'.__( 'Download example CSV files:', 'really-simple-csv-importer' );
		echo ' <a href="'.plugin_dir_url( __FILE__ ).'sample/sample.csv">'.__( 'csv', 'really-simple-csv-importer' ).'</a>,';
		echo ' <a href="'.plugin_dir_url( __FILE__ ).'sample/sample.ods">'.__( 'ods', 'really-simple-csv-importer' ).'</a>';
		echo ' '.__('(OpenDocument Spreadsheet file format for LibreOffice. Please export as csv before import)', 'really-simple-csv-importer' );
		echo '</p>';
		wp_import_upload_form( add_query_arg('step', 1) );
	}

	// Step 2
	function import() {
		$file = wp_import_handle_upload();

		if ( isset( $file['error'] ) ) {
			echo '<p><strong>' . __( 'Sorry, there has been an error.', 'really-simple-csv-importer' ) . '</strong><br />';
			echo esc_html( $file['error'] ) . '</p>';
			return false;
		} else if ( ! file_exists( $file['file'] ) ) {
			echo '<p><strong>' . __( 'Sorry, there has been an error.', 'really-simple-csv-importer' ) . '</strong><br />';
			printf( __( 'The export file could not be found at <code>%s</code>. It is likely that this was caused by a permissions problem.', 'really-simple-csv-importer' ), esc_html( $file['file'] ) );
			echo '</p>';
			return false;
		}
		
		$this->id = (int) $file['id'];
		$this->file = get_attached_file($this->id);
		$result = $this->process_posts();
		if ( is_wp_error( $result ) )
			return $result;
	}
	
	/**
	* Insert post and postmeta using `RSCSV_Import_Post_Helper` class.
	*
	* @param array $post
	* @param array $meta
	* @param array $terms
	* @param string $thumbnail The uri or path of thumbnail image.
	* @param bool $is_update
	* @return RSCSV_Import_Post_Helper
	*/
	public function save_post($post,$meta,$terms,$thumbnail,$is_update) {
		
		// Separate the post tags from $post array
		if (isset($post['post_tags']) && !empty($post['post_tags'])) {
			$post_tags = $post['post_tags'];
			unset($post['post_tags']);
		}

		// Special handling of attachments
		if (!empty($thumbnail) && $post['post_type'] == 'attachment') {
			$post['media_file'] = $thumbnail;
			$thumbnail = null;
		}

		// Add or update the post
		if ($is_update) {
			$h = RSCSV_Import_Post_Helper::getByID($post['ID']);
			$h->update($post);
		} else {
			$h = RSCSV_Import_Post_Helper::add($post);
		}
		
		// Set post tags
		if (isset($post_tags)) {
			$h->setPostTags($post_tags);
		}
		
		// Set meta data
		$h->setMeta($meta);
		
		// Set terms
		foreach ($terms as $key => $value) {
			$h->setObjectTerms($key, $value);
		}
		
		// Add thumbnail
		if ($thumbnail) {
			$h->addThumbnail($thumbnail);
		}
		
		return $h;
	}

	// process parse csv ind insert posts
	function process_posts() {
		$h = new RS_CSV_Helper;

		$handle = $h->fopen($this->file, 'r');
		if ( $handle == false ) {
			echo '<p><strong>'.__( 'Failed to open file.', 'really-simple-csv-importer' ).'</strong></p>';
			wp_import_cleanup($this->id);
			return false;
		}
		
		$is_first = true;
		$post_statuses = get_post_stati();
		
		echo '<ol>';
		
		while (($data = $h->fgetcsv($handle)) !== FALSE) {
			if ($is_first) {
				$h->parse_columns( $this, $data );
				$is_first = false;
			} else {
				echo '<li>';
				
				$post = array();
				$is_update = false;
				$error = new WP_Error();
				
				// (string) (required) post type
				$post_type = $h->get_data($this,$data,'post_type');
				if ($post_type) {
					if (post_type_exists($post_type)) {
						$post['post_type'] = $post_type;
					} else {
						$error->add( 'post_type_exists', sprintf(__('Invalid post type "%s".', 'really-simple-csv-importer'), $post_type) );
					}
				} else {
					echo __('Note: Please include post_type value if that is possible.', 'really-simple-csv-importer').'<br>';
				}
				
				// (int) post id
				$post_id = $h->get_data($this,$data,'ID');
				$post_id = ($post_id) ? $post_id : $h->get_data($this,$data,'post_id');
				if ($post_id) {
					$post_exist = get_post($post_id);
					if ( is_null( $post_exist ) ) { // if the post id is not exists
						$post['import_id'] = $post_id;
					} else {
						if ( !$post_type || $post_exist->post_type == $post_type ) {
							$post['ID'] = $post_id;
							$is_update = true;
						} else {
							$error->add( 'post_type_check', sprintf(__('The post type value from your csv file does not match the existing data in your database. post_id: %d, post_type(csv): %s, post_type(db): %s', 'really-simple-csv-importer'), $post_id, $post_type, $post_exist->post_type) );
						}
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
				$post_date_gmt = $h->get_data($this,$data,'post_date_gmt');
				if ($post_date_gmt) {
					$post['post_date_gmt'] = date("Y-m-d H:i:s", strtotime($post_date_gmt));
				}
				
				// (string) post status
				$post_status = $h->get_data($this,$data,'post_status');
				if ($post_status) {
    				if (in_array($post_status, $post_statuses)) {
    					$post['post_status'] = $post_status;
    				}
				}
				
				// (string) post password
				$post_password = $h->get_data($this,$data,'post_password');
				if ($post_password) {
    				$post['post_password'] = $post_password;
				}
				
				// (string) post title
				$post_title = $h->get_data($this,$data,'post_title');
				if ($post_title) {
					$post['post_title'] = $post_title;
				}
				
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
				
				// (string) comment status
				$comment_status = $h->get_data($this,$data,'comment_status');
				if ($comment_status) {
					$post['comment_status'] = $comment_status;
				}
				
				// (string, comma separated) slug of post categories
				$post_category = $h->get_data($this,$data,'post_category');
				if ($post_category) {
					$categories = preg_split("/,+/", $post_category);
					if ($categories) {
						$post['post_category'] = wp_create_categories($categories);
					}
				}
				
				// (string, comma separated) name of post tags
				$post_tags = $h->get_data($this,$data,'post_tags');
				if ($post_tags) {
					$post['post_tags'] = $post_tags;
				}
				
				// (string) post thumbnail image uri
				$post_thumbnail = $h->get_data($this,$data,'post_thumbnail');
				
				$meta = array();
				$tax = array();

				// add any other data to post meta
				foreach ($data as $key => $value) {
					if ($value !== false && isset($this->column_keys[$key])) {
						// check if meta is custom taxonomy
						if (substr($this->column_keys[$key], 0, 4) == 'tax_') {
							// (string, comma divided) name of custom taxonomies 
							$customtaxes = preg_split("/,+/", $value);
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
				
				/**
				 * Filter post data.
				 *
				 * @param array $post (required)
				 * @param bool $is_update
				 */
				$post = apply_filters( 'really_simple_csv_importer_save_post', $post, $is_update );
				/**
				 * Filter meta data.
				 *
				 * @param array $meta (required)
				 * @param array $post
				 * @param bool $is_update
				 */
				$meta = apply_filters( 'really_simple_csv_importer_save_meta', $meta, $post, $is_update );
				/**
				 * Filter taxonomy data.
				 *
				 * @param array $tax (required)
				 * @param array $post
				 * @param bool $is_update
				 */
				$tax = apply_filters( 'really_simple_csv_importer_save_tax', $tax, $post, $is_update );
				/**
				 * Filter thumbnail URL or path.
				 *
				 * @since 1.3
				 *
				 * @param string $post_thumbnail (required)
				 * @param array $post
				 * @param bool $is_update
				 */
				$post_thumbnail = apply_filters( 'really_simple_csv_importer_save_thumbnail', $post_thumbnail, $post, $is_update );

				/**
				 * Option for dry run testing
				 *
				 * @since 0.5.7
				 *
				 * @param bool false
				 */
				$dry_run = apply_filters( 'really_simple_csv_importer_dry_run', false );
				
				if (!$error->get_error_codes() && $dry_run == false) {
					
					/**
					 * Get Alternative Importer Class name.
					 *
					 * @since 0.6
					 *
					 * @param string Class name to override Importer class. Default to null (do not override).
					 */
					$class = apply_filters( 'really_simple_csv_importer_class', null );
					
					// save post data
					if ($class && class_exists($class,false)) {
						$importer = new $class;
						$result = $importer->save_post($post,$meta,$tax,$post_thumbnail,$is_update);
					} else {
						$result = $this->save_post($post,$meta,$tax,$post_thumbnail,$is_update);
					}
					
					if ($result->isError()) {
						$error = $result->getError();
					} else {
						$post_object = $result->getPost();
						
						if (is_object($post_object)) {
							/**
							 * Fires adter the post imported.
							 *
							 * @since 1.0
							 *
							 * @param WP_Post $post_object
							 */
							do_action( 'really_simple_csv_importer_post_saved', $post_object );
						}
						
						echo esc_html(sprintf(__('Processing "%s" done.', 'really-simple-csv-importer'), $post_title));
					}
				}
				
				// show error messages
				foreach ($error->get_error_messages() as $message) {
					echo esc_html($message).'<br>';
				}
				
				echo '</li>';
			}
		}
		
		echo '</ol>';

		$h->fclose($handle);
		
		wp_import_cleanup($this->id);
		
		echo '<h3>'.__('All Done.', 'really-simple-csv-importer').'</h3>';
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

// Initialize
function really_simple_csv_importer() {
	load_plugin_textdomain( 'really-simple-csv-importer', false, dirname( plugin_basename(__FILE__) ) . '/languages' );
	
    $rs_csv_importer = new RS_CSV_Importer();
    register_importer('csv', __('CSV', 'really-simple-csv-importer'), __('Import posts, categories, tags, custom fields from simple csv file.', 'really-simple-csv-importer'), array ($rs_csv_importer, 'dispatch'));
}
add_action( 'plugins_loaded', 'really_simple_csv_importer' );

} // class_exists( 'WP_Importer' )
