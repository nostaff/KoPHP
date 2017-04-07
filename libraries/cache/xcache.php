<?php
defined('SYS_PATH') or die('No direct access allowed.');

/**
 * Xcache Cache driver.
 *
 * @package    Cache
 * @author     Ko Team, Eric 
 * @version    $Id: xcache.php 84 2009-10-30 09:08:01Z eric $
 * @copyright  (c) 2008-2009 Ko Team, Eric 
 * @license    http://kophp.com/license.html
 */
class Cache_Xcache implements Cache_Driver
{
    protected $_config;

    public function __construct ($config)
    {
        if (! extension_loaded('xcache'))
            throw new KoException('Cache: XCache extension not loaded.');
        if (empty($config) || ! $config['auth_uset'] || ! $config['auth_pass']) {
            throw new KoException('Cache: XCache Config is needed.');
        }            
    }

    public function get ($id)
    {
        if (xcache_isset($id))
            return xcache_get($id);
        return NULL;
    }

    public function set ($id, $data, $lifetime)
    {
        return xcache_set($id, $data, $lifetime);
    }
    
    public function delete ($id)
    {
        // Do the login
        $this->_auth();
        $result = TRUE;
        for ($i = 0, $max = xcache_count(XC_TYPE_VAR); $i < $max; $i ++) {
            if (xcache_clear_cache(XC_TYPE_VAR, $i) !== NULL) {
                $result = FALSE;
                break;
            }
        }
        // Undo the login
        $this->_auth(TRUE);
        return $result;
    }

    private function _auth ($reverse = FALSE)
    {
        static $backup = array();
        $keys = array('auth_user' , 'auth_pass');
        foreach ($keys as $key) {
            if ($reverse) {
                if (isset($backup[$key])) {
                    $_SERVER[$key] = $backup[$key];
                    unset($backup[$key]);
                } else {
                    unset($_SERVER[$key]);
                }
            } else {
                $value = getenv($key);
                if (! empty($value)) {
                    $backup[$key] = $value;
                }
                $_SERVER[$key] = Ko::config('cache')->xcache[$key];
            }
        }
    }
} // End Cache Xcache Driver
