<?php
defined('SYS_PATH') or die('No direct script access.');

/**
 * Cookie helper.
 *
 * @package    Ko
 * @author     Ko Team, Eric
 * @version    $Id: cookie.php 91 2010-07-04 03:34:02Z eric $
 * @copyright  (c) 2008-2009 Ko Team
 * @license    http://kophp.com/license.html
 */
class Cookie
{

    /**
     * @var  integer  Number of seconds before the cookie expires
     */
    public static $expiration = 0;

    /**
     * @var  string  Restrict the path that the cookie is available to
     */
    public static $path = '/';

    /**
     * @var  string  Restrict the domain that the cookie is available to
     */
    public static $domain = NULL;

    /**
     * @var  boolean  Only transmit cookies over secure connections
     */
    public static $secure = FALSE;

    /**
     * @var  boolean  Only transmit cookies over HTTP, disabling Javascript access
     */
    public static $httponly = FALSE;

    /**
     * Gets the value of a signed cookie. Cookies without signatures will not
     * be returned. If the cookie signature is present, but invalid, the cookie
     * will be deleted.
     *
     * @param   string  cookie name
     * @param   mixed   default value to return
     * @return  string
     */
    public static function get ($key, $default = NULL)
    {
        if (! isset($_COOKIE[$key])) {
            // The cookie does not exist
            return $default;
        }
        return $_COOKIE[$key];
    }

    /**
     * Sets a signed cookie. Note that all cookie values must be strings and no
     * automatic serialization will be performed!
     *
     * @param   string   name of cookie
     * @param   string   value of cookie
     * @param   integer  lifetime in seconds
     * @return  boolean
     */
    public static function set ($name, $value, $expiration = NULL)
    {
        if ($expiration === NULL) {
            // Use the default expiration
            $expiration = self::$expiration;
        }
        if ($expiration !== 0) {
            // The expiration is expected to be a UNIX timestamp
            $expiration += time();
        }
        return setcookie($name, $value, $expiration, self::$path, self::$domain, self::$secure, self::$httponly);
    }

    /**
     * Deletes a cookie by making the value NULL and expiring it.
     *
     * @param   string   cookie name
     * @return  boolean
     */
    public static function delete ($name)
    {
        // Remove the cookie
        unset($_COOKIE[$name]);
        // Nullify the cookie and make it expire
        return self::set($name, NULL, - 86400);
    }

    final private function __construct ()
    {    // This is a static class
    }
} // End cookie
