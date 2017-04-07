<?php 
defined('SYS_PATH') or die('No direct script access.');
/**
 * Abstract configuration reader.
 *
 * @package    Ko
 * @author     Ko Team, Eric 
 * @version    $Id: reader.php 46 2009-09-23 03:43:27Z eric $
 * @copyright  (c) 2008-2009 Ko Team
 * @license    http://kophp.com/license.html
 */
abstract class Config_Driver extends ArrayObject {

	// Configuration group name
	protected $_configuration_group;

	/**
	 * Loads an empty array as the initial configuration and enables array
	 * keys to be used as properties.
	 *
	 * @return  void
	 */
	public function __construct()
	{
		parent::__construct(array(), ArrayObject::ARRAY_AS_PROPS);
	}

	/**
	 * Return the current group in serialized form.
	 *
	 * @return  string
	 */
	public function __toString()
	{
		return serialize($this->getArrayCopy());
	}

	/**
	 * Loads a configuration group.
	 *
	 * @param   string  group name
	 * @param   array   configuration array
	 * @return  $this   clone of the current object
	 */
	public function load($group, array $config = NULL)
	{
        if ($config === NULL) {
            return FALSE;
        }

		// Set the group name
		$this->_configuration_group = $group;

		// Clone the current object
		$object = clone $this;

		// Swap the array with the actual configuration
		$object->exchangeArray($config);

		// Empty the configuration group
		$this->_configuration_group = NULL;

		return $object;
	}

	/**
	 * Save a configuration group.
	 *
	 * @param   string  file name
	 * @param   array   configuration array
	 * @return  boolean
	 */
	public function save($filename, array $config = NULL)
	{
	    return;
	}
	
	/**
	 * Return the raw array that is being used for this object.
	 *
	 * @return  array
	 */
	public function as_array()
	{
		return $this->getArrayCopy();
	}

	/**
	 * Get a variable from the configuration or return the default value.
	 *
	 * @param   string   array key
	 * @param   mixed    default value
	 * @return  mixed
	 */
	public function get($key, $default = NULL)
	{
		return $this->offsetExists($key) ? $this->offsetGet($key) : $default;
	}

	/**
	 * Sets a value in the configuration array.
	 *
	 * @param   string   array key
	 * @param   mixed    array value
	 * @return  $this
	 */
	public function set($key, $value)
	{
		$this->offsetSet($key, $value);

		return $this;
	}

} // End Config_Driver
