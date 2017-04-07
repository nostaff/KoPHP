<?php
defined('SYS_PATH') or die('No direct access allowed.');

/**
 * File-based Cache driver.
 *
 * $Id: file.php 84 2009-10-30 09:08:01Z eric $
 *
 * @package    Cache
 * @author     Ko Team
 * @copyright  (c) 2007-2008 Ko Team
 * @license    http://kophp.com/license.html
 */
class Cache_File implements Cache_Driver
{

    protected $_directory = '';

    /**
     * Tests that the storage location is a directory and is writable.
     */
    public function __construct ($config)
    {
        $directory = str_replace('\\', '/', realpath($config['path'])) . '/';
        if (! is_dir($directory) or ! is_writable($directory))
            throw new KoException('cache.unwritable :dir', array(':dir' => $directory));
        $this->_directory = $directory;
    }

    /**
     * Finds an array of files matching the given id or tag.
     *
     * @param  string  cache id
     * @return array   of filenames matching the id or tag
     */
    public function exists ($key)
    {
        return glob($this->_directory . $key . '~*');
    }

    /**
     * Sets a cache item to the given data, tags, and lifetime.
     *
     * @param   string   cache id to set
     * @param   string   data in the cache
     * @param   array    cache tags
     * @param   integer  lifetime
     * @return  bool
     */
    public function set ($key, $data, $lifetime)
    {
        // Remove old cache files
        $this->delete($key);
        // Cache File driver expects unix timestamp
        if ($lifetime !== 0) {
            $lifetime += time();
        }
        // Write out a serialized cache
        return (bool) file_put_contents($this->_directory . $key . '~' . $lifetime, serialize($data));
    }

    /**
     * Fetches a cache item. This will delete the item if it is expired or if
     * the hash does not match the stored hash.
     *
     * @param   string  cache id
     * @return  mixed|NULL
     */
    public function get ($key)
    {
        if ($file = $this->exists($key)) {
            // Use the first file
            $file = current($file);
            // Validate that the cache has not expired
            if ($this->expired($file)) {
                // Remove this cache, it has expired
                $this->delete($key);
            } else {
                // Turn off errors while reading the file
                $ER = error_reporting(0);
                if (($data = file_get_contents($file)) !== FALSE) {
                    // Unserialize the data
                    $data = unserialize($data);
                } else {
                    // Delete the data
                    unset($data);
                }
                // Turn errors back on
                error_reporting($ER);
            }
        }
        // Return NULL if there is no data
        return isset($data) ? $data : NULL;
    }

    /**
     * Deletes a cache item by id or tag
     *
     * @param   string   cache id
     * @return  boolean
     */
    public function delete ($key)
    {
        $files = $this->exists($key);
        if (empty($files))
            return FALSE;
            // Disable all error reporting while deleting
        $ER = error_reporting(0);
        foreach ($files as $file) {
            // Remove the cache file
            if (! unlink($file))
                Ko::log('error', 'Cache: Unable to delete cache file: ' . $file);
        }
        // Turn on error reporting again
        error_reporting($ER);
        return TRUE;
    }

    /**
     * Check if a cache file has expired by filename.
     *
     * @param  string  filename
     * @return bool
     */
    protected function expired ($file)
    {
        // Get the expiration time
        $expires = (int) substr($file, strrpos($file, '~') + 1);
        // Expirations of 0 are "never expire"
        return ($expires !== 0 and $expires <= time());
    }
} // End Cache File Driver