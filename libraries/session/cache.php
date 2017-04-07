<?php
defined('SYS_PATH') or die('No direct access allowed.');

/**
 * Session cache driver.
 *
 * Cache library config goes in the session.storage config entry:
 * $config['storage'] = array(
 *     'driver' => 'apc',
 *     'requests' => 10000
 * );
 * Lifetime does not need to be set as it is
 * overridden by the session expiration setting.
 *
 * $Id: cache.php 109 2011-06-05 07:00:30Z eric $
 *
 * @package    Core
 * @author     Ko Team, Eric
 * @copyright  (c) 2007-2008 Ko Team
 * @license    http://kophp.com/license.html
 */
class Session_Cache implements Session_Driver
{

    /**
     * @var Cache Object
     */
    protected $_cache;

    public function __construct ()
    {
    }

    public function open ($path, $name)
    {
        $config = Ko::config('session.storage');
        if (empty($config)) {
            // Load the default group
            $config = Ko::config('cache.default');
        } elseif (is_string($config)) {
            $name = $config;
            // Test the config group name
            if (($config = Ko::config('cache.' . $config)) === NULL)
                throw new KoException('The :group: group is not defined in your configuration.', array(':group:' => $name));
        }
        $this->_cache = Cache::instance($config);
        
        return is_object($this->_cache);
    }

    public function close ()
    {
        unset($this->_cache);
        return TRUE;
    }

    public function read ($id)
    {
        $id = $this->makeKey($id);
        if ($data = $this->_cache->get($id)) {
            return $data;
        }
        return null;
    }

    public function write ($id, $data)
    {
        $id = $this->makeKey($id);
        return $this->_cache->set($id, $data);
    }

    public function destroy ($id)
    {
        $id = $this->makeKey($id);
        return $this->_cache->delete($id);
    }

    public function regenerate ()
    {
        session_regenerate_id(TRUE);
        // Return new session id
        return session_id();
    }

    public function gc ($maxlifetime)
    {
        // Just return, caches are automatically cleaned up
        return TRUE;
    }
    
    
    protected function makeKey($id)
    {
        return 'SESS_' . $id;
    }
} // End Session Cache Driver
