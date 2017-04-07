<?php defined('SYS_PATH') OR die('No direct access allowed.');
/**
 * Cache driver interface.
 *
 * @package    Cache
 * @author     Ko Team, Eric 
 * @version    $Id: driver.php 84 2009-10-30 09:08:01Z eric $
 * @copyright  (c) 2007-2009 Ko Team
 * @license    http://kophp.com/license.html
 */
interface Cache_Driver {

	/**
	 * Set a cache item.
	 */
	public function set($key, $data, $lifetime);

	/**
	 * Get a cache item.
	 * Return NULL if the cache item is not found.
	 */
	public function get($key);

	/**
	 * Delete cache items by id or tag.
	 */
	public function delete($key);

} // End Cache Driver