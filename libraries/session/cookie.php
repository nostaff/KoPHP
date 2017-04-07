<?php
defined('SYS_PATH') or die('No direct access allowed.');

/**
 * Session cookie driver.
 *
 * $Id: cookie.php 109 2011-06-05 07:00:30Z eric $
 *
 * @package    Core
 * @author     Ko Team, Eric
 * @copyright  (c) 2007-2008 Ko Team
 * @license    http://kophp.com/license.html
 */
class Session_Cookie implements Session_Driver
{

    protected $cookie_name;

    public function __construct ()
    {
        $this->cookie_name = Ko::config('session.cookie.name');
    }

    public function open ($path, $name)
    {
        return TRUE;
    }

    public function close ()
    {
        return TRUE;
    }

    public function read ($id)
    {
        $data = (string) cookie::get($this->cookie_name);
        if ($data == '')
            return $data;
        return base64_decode($data);
    }

    public function write ($id, $data)
    {
        $data = base64_encode($data);
        if (strlen($data) > 4048) {
            return FALSE;
        }
        return cookie::set($this->cookie_name, $data, Ko::config('session.cookie.lifetime'));
    }

    public function destroy ($id)
    {
        return cookie::delete($this->cookie_name);
    }

    public function regenerate ()
    {
        session_regenerate_id(TRUE);
        // Return new id
        return session_id();
    }

    public function gc ($maxlifetime)
    {
        return TRUE;
    }
} // End Session Cookie Driver Class