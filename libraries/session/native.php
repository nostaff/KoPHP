<?php
defined('SYS_PATH') or die('No direct script access.');

/**
 * Native PHP session class.
 *
 * @package    Session
 * @author     Ko Team, Eric 
 * @version    $Id: native.php 80 2009-10-11 17:48:57Z eric $
 * @copyright  (c) 2008-2009 Ko Team
 * @license    http://kophp.com/license.html
 */
class Session_Native extends Session
{

    protected function _read ($id = NULL)
    {
        // Set the cookie lifetime
        session_set_cookie_params($this->_lifetime);
        // Set the session cookie name
        session_name($this->_name);
        if ($id) {
            // Set the session id
            session_id($id);
        }
        // Start the session
        session_start();
        // Use the $_SESSION global for storing data
        $this->_data = & $_SESSION;
        
        return NULL;
    }

    protected function _regenerate ()
    {
        // Regenerate the session id
        session_regenerate_id();
        return session_id();
    }

    protected function _write ()
    {
        // Write and close the session
        session_write_close();
        return TRUE;
    }

    protected function _destroy ()
    {
        // Destroy the current session
        session_destroy();
        return ! session_id();
    }
} // End Session_Native
