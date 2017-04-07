<?php
defined('SYS_PATH') or die('No direct access allowed.');

/**
 * SQLite-based Cache driver.
 *
 * @package    Cache
 * @author     Ko Team, Eric 
 * @version    $Id: sqlite.php 109 2011-06-05 07:00:30Z eric $
 * @copyright  (c) 2007-2009 Ko Team
 * @license    http://kophp.com/license.html
 */
class Cache_Sqlite implements Cache_Driver
{

    // SQLite database instance
    protected $db;

    // Database error messages
    protected $error;

    /**
     * Tests that the storage location is a directory and is writable.
     */
    public function __construct ($filename)
    {
        // Get the directory name
        $directory = str_replace('\\', '/', realpath(pathinfo($filename, PATHINFO_DIRNAME))) . '/';
        // Set the filename from the real directory path
        $filename = $directory . basename($filename);
        // Make sure the cache directory is writable
        if (! is_dir($directory) or ! is_writable($directory))
            throw new KoException('Cache: Directory :name is unwritable.', array(':name' => $directory));
            // Make sure the cache database is writable
        if (is_file($filename) and ! is_writable($filename))
            throw new KoException('Cache: File :name is unwritable.', array(':name' => $filename));
            // Open up an instance of the database
        $this->db = new SQLiteDatabase($filename, '0777', $error);
        // Throw an exception if there's an error
        if (! empty($error))
            throw new KoException('Cache: Driver error - ' . sqlite_error_string($error));
        $query = "SELECT name FROM sqlite_master WHERE type = 'table' AND name = 'caches'";
        $tables = $this->db->query($query, SQLITE_BOTH, $error);
        // Throw an exception if there's an error
        if (! empty($error))
            throw new KoException('Cache: Driver error - ' . sqlite_error_string($error));
        if ($tables->numRows() == 0) {
            // Issue a CREATE TABLE command
            $this->db->unbufferedQuery('CREATE TABLE caches(id VARCHAR(127) PRIMARY KEY, expiration INTEGER, cache TEXT);');
        }
    }

    /**
     * Checks if a cache id is already set.
     *
     * @param  string   cache id
     * @return boolean
     */
    public function exists ($key)
    {
        // Find the id that matches
        $query = "SELECT id FROM caches WHERE id = '$key'";
        return ($this->db->query($query)->numRows() > 0);
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
        // Serialize and escape the data
        $data = sqlite_escape_string(serialize($data));
        // Cache Sqlite driver expects unix timestamp
        if ($lifetime !== 0) {
            $lifetime += time();
        }
        $query = $this->exists($key) ? "UPDATE caches SET expiration = '$lifetime', cache = '$data' WHERE id = '$key'" : "INSERT INTO caches VALUES('$key', '$lifetime', '$data')";
        // Run the query
        $this->db->unbufferedQuery($query, SQLITE_BOTH, $error);
        if (! empty($error)) {
            return FALSE;
        } else {
            return TRUE;
        }
    }

    /**
     * Fetches a cache item. This will delete the item if it is expired or if
     * the hash does not match the stored hash.
     *
     * @param  string  cache id
     * @return mixed|NULL
     */
    public function get ($key)
    {
        $query = "SELECT id, expiration, cache FROM caches WHERE id = '$key' LIMIT 0, 1";
        $query = $this->db->query($query, SQLITE_BOTH, $error);
        if ($cache = $query->fetchObject()) {
            // Make sure the expiration is valid and that the hash matches
            if ($cache->expiration != 0 and $cache->expiration <= time()) {
                // Cache is not valid, delete it now
                $this->delete($cache->id);
            } else {
                // Bug Report #1775
                $data = unserialize($cache->cache);
                return $data;
            }
        }
        // No valid cache found
        return NULL;
    }

    /**
     * Deletes a cache item by id or tag
     *
     * @param  string  cache id or tag, or TRUE for "all items"
     * @param  bool    delete a tag
     * @return bool
     */
    public function delete ($key)
    {
        $where = "id = '$key'";
        $this->db->unbufferedQuery('DELETE FROM caches WHERE ' . $where, SQLITE_BOTH, $error);
        if (! empty($error)) {
            return FALSE;
        } else {
            return (boolean) $this->db->changes();
        }
    }

} // End Cache SQLite Driver