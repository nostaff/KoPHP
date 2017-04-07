<?php
defined('SYS_PATH') or die('No direct access allowed.');

/**
 * Session driver interface
 *
 * $Id: driver.php 83 2009-10-16 14:45:27Z eric $
 *
 * @package    Core
 * @author     Ko Team, Eric
 * @copyright  (c) 2007-2008 Ko Team
 * @license    http://kophp.com/license.html
 */
interface Session_Driver
{

    /**
     * Opens a session.
     *
     * @param   string   save path
     * @param   string   session name
     * @return  boolean
     */
    public function open ($path, $name);

    /**
     * Closes a session.
     *
     * @return  boolean
     */
    public function close ();

    /**
     * Reads a session.
     *
     * @param   string  session id
     * @return  string
     */
    public function read ($id);

    /**
     * Writes a session.
     *
     * @param   string   session id
     * @param   string   session data
     * @return  boolean
     */
    public function write ($id, $data);

    /**
     * Destroys a session.
     *
     * @param   string   session id
     * @return  boolean
     */
    public function destroy ($id);

    /**
     * Regenerates the session id.
     *
     * @return  string
     */
    public function regenerate ();

    /**
     * Garbage collection.
     *
     * @param   integer  session expiration period
     * @return  boolean
     */
    public function gc ($maxlifetime);
} // End Session Driver Interface