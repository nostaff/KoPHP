<?php
defined('SYS_PATH') or die('No direct access allowed.');

/**
 * APC-based Cache driver.
 *
 * @package    Cache
 * @author     Ko Team, Eric 
 * @version    $Id: apc.php 84 2009-10-30 09:08:01Z eric $
 * @copyright  (c) 2008-2009 Ko Team, Eric 
 * @license    http://kophp.com/license.html
 */
class Cache_Apc implements Cache_Driver
{

    public function __construct ()
    {
        if (! extension_loaded('apc'))
            throw new KoException('Cache: APC extension not loaded.');
    }

    public function get ($id)
    {
        return (($return = apc_fetch($id)) === FALSE) ? NULL : $return;
    }

    public function set ($id, $data, $lifetime)
    {
        return apc_store($id, $data, $lifetime);
    }

    public function delete ($id)
    {
        return apc_delete($id);
    }
} // End Cache APC Driver