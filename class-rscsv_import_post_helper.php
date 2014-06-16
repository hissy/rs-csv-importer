<?php

class RSCSV_Import_Post_Helper
{
    
    /**
     * @var $post WP_Post object
     */
    private $post;
    
    /**
     * @var bool $is_insert
     */
    public $is_insert = true;
    
    // error utilities
    
    private $error;
    
    public function addError($code, $message)
    {
        if (!is_wp_error($this->error)) {
            $e = new WP_Error();
            $this->error = $e;
        }
        $this->error->add($code, $message);
    }
    
    function getError()
    {
        return $this->error;
    }
    
    function isError()
    {
        return is_wp_error($this->error);
    }
    
    /**
     * Set WP_Post object
     *
     * @param (int) $post_id Post ID
     */
    protected function setPost($post_id)
    {
        $post = get_post($post_id);
        if (is_object($post)) {
            $this->post = $post;
        } else {
            $this->addError('post_id_not_found', __('Provided Post ID not found.', 'rs-csv-importer'));
        }
    }
    
    /**
     * Get object by post id. turn $is_insert to false
     *
     * @param (int) $post_id Post ID
     * @return RSCSV_Import_Post_Helper
     */
    public static function getByID($post_id)
    {
        $object = new RSCSV_Import_Post_Helper();
        $object->setPost($post_id);
        if (!$object->isError()) {
            $object->is_insert = false;
        }
        return $object;
    }
    
    /**
     * Get WP_Post object
     *
     * @return WP_Post
     */
    public function getPost()
    {
        return $this->post;
    }
    
    /**
     * Add post
     *
     * @param (array) $data
     * @return RSCSV_Import_Post_Helper
     */
    public function add($data)
    {
    }
    
    /**
     * Update post
     *
     * @param (array) $data
     */
    public function update($data)
    {
    }
    
    /**
     * Set meta fields by array
     *
     * @param (array) $data
     */
    public function setMetaFields($data)
    {
    }
    
    /**
     * A wrapper of update_post_meta
     *
     * @param (string) $key
     * @param (string/array) $value
     * @param (bool) $unique
     * @return (int or boolean) Meta ID if the key didn't exist, true on successful update, false on failure.
     */
    protected function setMeta($key, $value, $unique)
    {
    }
    
    /**
     * A wrapper of update_field
     *
     * @param (string) $key
     * @param (string/array) $value
     */
    protected function acfUpdateField($key, $value)
    {
    }
    
    /**
     * A wrapper of $cfs->save
     *
     * @param (array) $data
     */
    protected function cfsSave($data)
    {
    }
    
    /**
     * A wrapper of wp_set_post_tags
     *
     * @param (array) $tags
     * @return (array/WP_Error) Affected Term IDs
     */
    public function setPostTags($tags)
    {
    }
    
    /**
     * A wrapper of wp_set_object_terms
     *
     * @param (array) $terms
     * @return (array/WP_Error) Affected Term IDs
     */
    public function setObjectTerms($terms)
    {
    }
    
    /**
     * Add attachment file. Automatically get remote file
     *
     * @param (string) $filename
     * @return (boolean) True on success, false on failure.
     */
    public function addMediaFile($filename)
    {
    }
    
    /**
     * Add attachment file and set as thumbnail. Automatically get remote file
     *
     * @param (string) $filename
     * @return (boolean) True on success, false on failure.
     */
    public function addThumbnail($filename)
    {
    }
    
    /**
     * A wrapper of wp_insert_attachment and wp_update_attachment_metadata
     *
     * @param (string) $filename
     * @param (string) $title
     * @param (string) $content
     * @param (string) $excerpt
     * @return (int) attachment_id
     */
    public function setAttachment($filename, $title, $content, $excerpt)
    {
    }
    
    /**
     * A wrapper of wp_safe_remote_get
     *
     * @param (string) $url
     * @param (array) $args
     * @return (string) file path
     */
    public static function remoteGet($url, $args)
    {
    }
    
    /**
     * Unset WP_Post object
     */
    public function __destruct()
    {
    }
    
}