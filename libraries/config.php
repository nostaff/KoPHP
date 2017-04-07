<?php
defined('SYS_PATH') or die('No direct script access.');

/**
 * Wrapper for configuration arrays.
 *
 * @package    Ko
 * @author     Ko Team, Eric 
 * @version    $Id: config.php 109 2011-06-05 07:00:30Z eric $
 * @copyright  (c) 2009 Ko Team
 * @license    http://kophp.com/license.html
 */
class Config
{

    // Singleton static instance
    protected static $_instance;

    // Configuration readers
    protected $_readers = array();

    /**
     * Get the singleton instance of Config.
     *
     * @return  Config
     */
    public static function instance ($group = 'default')
    {
        if (!isset(self::$_instance[$group]) || self::$_instance[$group] === NULL) {
            // Create a new instance
            self::$_instance[$group] = new self($group);
        }
        return self::$_instance[$group];
    }

    /**
     * Attach a configuration reader.
     *
     * @param   object   Config_Driver instance
     * @param   boolean  add the reader as the first used object
     * @return  $this
     */
    public function attach (Config_Driver $reader, $first = TRUE)
    {
        if ($first === TRUE) {
            // Place the log reader at the top of the stack
            array_unshift($this->_readers, $reader);
        } else {
            // Place the reader at the bottom of the stack
            $this->_readers[] = $reader;
        }
        return $this;
    }

    /**
     * Detaches a configuration reader.
     *
     * @param   object  Config_Driver instance
     * @return  $this
     */
    public function detach (Config_Driver $reader)
    {
        if (($key = array_search($reader, $this->_readers))) {
            // Remove the writer
            unset($this->_readers[$key]);
        }
        return $this;
    }

    /**
     * Load a configuration group. Searches the readers in order until the
     * group is found. If the group does not exist, an empty configuration
     * array will be loaded using the first reader.
     *
     * @param   string  configuration group
     * @return  object  Config_Driver
     */
    public function load ($group)
    {
        foreach ($this->_readers as $reader) {
            if ($config = $reader->load($group)) {
                // Found a reader for this configuration group
                return $config;
            }
        }
        // Reset the iterator
        reset($this->_readers);
        if (! is_object($config = current($this->_readers))) {
            throw new Exception('No configuration readers attached');
        }
        // Load the reader as an empty array
        return $config->load($group, array());
    }

    /**
     * Save a configuration to file.
     * 
     * @param string 	$file  config file name
     * @param mix 		$config config data
     */
    public function save ($file, $config = NULL)
    {
        $return = true;
        foreach ($this->_readers as $reader) {
            if (! $reader->save($file, (array) $config)) {
                $return = false;
            }
            return $return;
        }
    }
    
    /**
     * Copy one configuration group to all of the other readers.
     *
     * @param   string   group name
     * @return  $this
     */
    public function copy ($group)
    {
        // Load the configuration group
        $config = $this->load($group);
        foreach ($this->_readers as $reader) {
            if ($config instanceof $reader) {
                // Do not copy the config to the same group
                continue;
            }
            // Load the configuration object
            $object = $reader->load($group, array());
            foreach ($config as $key => $value) {
                // Copy each value in the config
                $object->offsetSet($key, $value);
            }
        }
        return $this;
    }

    final private function __construct ($group = 'default')
    {    // Enforce singleton behavior
    }

    final private function __clone ()
    {    // Enforce singleton behavior
    }
} // End Config
