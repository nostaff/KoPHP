<?php
defined('SYS_PATH') or die('No direct access allowed.');

/**
 * Provides a driver-based interface for cache.
 *
 * @package    Cache
 * @author     Ko Team, Eric 
 * @version    $Id: cache.php 109 2011-06-05 07:00:30Z eric $
 * @copyright  (c) 2007-2009 Ko Team
 * @license    http://kophp.com/license.html
 */
class Cache
{

    protected static $instances = array();

    // Configuration
    protected $_config;

    /**
     * @var Cache_Driver object
     */
    protected $_driver;

    /**
     * Returns a singleton instance of Cache.
     *
     * @param   string  configuration
     * @return  Cache
     */
    public static function &instance ($config = FALSE)
    {
        $name = (string) $config;
        if (! isset(self::$instances[$name])) {
            self::$instances[$name] = new Cache($config);
        }
        return self::$instances[$name];
    }

    /**
     * Loads the configured driver and validates it.
     *
     * @param   array|string  custom configuration or config group name
     * @return  void
     */
    public function __construct ($config = FALSE)
    {
        if (is_string($config)) {
            $name = $config;
            // Test the config group name
            if (($config = Ko::config('cache')->$config) === NULL)
                throw new KoException('Cache: Undefined group :name.', array(':name' => $name));
        }
        if (! is_array($config)) {
            // Load the default group
            $config = Ko::config('cache')->default;
        }
        // Cache the config in the object
        $this->_config = $config;
        // Set driver name
        $driver = 'Cache_' . ucfirst($this->_config['driver']);
        // Load the driver
        if (! Ko::autoload($driver))
            throw new KoException('Class :name not found.', array(':name' => $driver));
            // Initialize the driver
        $this->_driver = new $driver($this->_config);
        // Validate the driver
        if (! ($this->_driver instanceof Cache_Driver))
            throw new KoException('Cache: Not valid driver ' . $this->_config['driver']);
    }

    /**
     * Fetches a cache by id. NULL is returned when a cache item is not found.
     *
     * @param   string  cache id
     * @return  mixed   cached data or NULL
     */
    public function get ($key)
    {
        // Sanitize the ID
        $key = $this->sanitize($key);
        return $this->_driver->get($key);
    }

    /**
     * Set a cache item by id. Tags may also be added and a custom lifetime
     * can be set. Non-string data is automatically serialized.
     *
     * @param   string        unique cache id
     * @param   mixed         data to cache
     * @param   integer       number of seconds until the cache expires
     * @return  boolean
     */
    public function set ($key, $data, $lifetime = NULL)
    {
        if (is_resource($data))
            throw new KoException('Cache: Resources given.');
            // Sanitize the ID
        $key = $this->sanitize($key);
        if ($lifetime === NULL) {
            // Get the default lifetime
            $lifetime = $this->_config['lifetime'];
        }
        return $this->_driver->set($key, $data, $lifetime);
    }

    /**
     * Delete a cache item by id.
     *
     * @param   string   cache id
     * @return  boolean
     */
    public function delete ($key)
    {
        // Sanitize the ID
        $key = $this->sanitize($key);
        return $this->_driver->delete($this->sanitize($key));
    }
    
    /**
     * 原子递减操作
     * 
     * @param int $key		递减字段
     * @param int $value	递减度
     * @return bool
     */
    
    public function decrement ($key, $value = 1)
    {
        return $this->_driver->decrement($this->sanitize($key), $value);
    }
    
	/**
     * 原子递增操作
     * 
     * @param int $key		增长字段
     * @param int $value	增长度
     * @return bool
     */
    
    public function increment ($key, $value = 1)
    {
        return $this->_driver->increment($this->sanitize($key), $value);
    }

    /**
     * Replaces troublesome characters with underscores.
     *
     * @param   string   cache id
     * @return  string
     */
    protected function sanitize ($key)
    {
        // Change slashes and spaces to underscores
        return str_replace(array('/' , '\\' , ' '), '_', $key);
    }
} // End Cache
