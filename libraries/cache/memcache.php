<?php
defined('SYS_PATH') or die('No direct access allowed.');

/**
 * Memcache-based Cache driver.
 *
 * @package    Cache
 * @author     Ko Team, Eric 
 * @version    $Id: memcache.php 84 2009-10-30 09:08:01Z eric $
 * @copyright  (c) 2007-2009 Ko Team
 * @license    http://kophp.com/license.html
 */
class Cache_Memcache implements Cache_Driver
{

    protected $_config;
    /**
     * Cache _backend object
     *
     * @var Memcache
     */
    protected $_backend;

    protected $_compress;

    // The persistent lifetime value for expirations of 0
    protected $_lifetime;

    public function __construct ($config)
    {
        if (! extension_loaded('memcache'))
            throw new KoException('Cache: Memcache extension not loaded.');
        if (empty($config) || !$config['servers']) {
            throw new KoException('Cache: Memcache Config is needed.');
        }
        $this->_config = $config;
        $this->_backend = new Memcache();
        $this->_compress = $this->_config['compression'] ? MEMCACHE_COMPRESSED : FALSE;
        $servers = $this->_config['servers'];
        foreach ($servers as $server) {
            // Make sure all required keys are set
            $server += array('host' => '127.0.0.1' , 'port' => 11211 , 'persistent' => FALSE);
            // Add the server to the pool
            $this->_backend->addServer($server['host'], $server['port'], (bool) $server['persistent']);
        }
        // Set "persistent lifetime" value to one year
        $this->_lifetime = strtotime('now +1 year');
    }

    public function get ($key)
    {
        return (($return = $this->_backend->get($key)) === FALSE) ? NULL : $return;
    }

    public function set ($key, $data, $lifetime)
    {
        if ($lifetime === 0) {
            // Using an expiration of zero is unreliable, as memcache may delete
            // it without warning. @see http://php.net/memcache_set
            $lifetime = $this->_lifetime;
        } else {
            // Memcache driver expects unix timestamp
            $lifetime += time();
        }
        // Set a new value
        return $this->_backend->set($key, $data, $this->_compress, $lifetime);
    }

    public function delete ($key)
    {
        return $this->_backend->delete($key);
    }
} // End Cache Memcache Driver
