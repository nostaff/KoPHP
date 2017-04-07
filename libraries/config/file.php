<?php
defined('SYS_PATH') or die('No direct script access.');
/**
 * File-based configuration loader.
 *
 * @package    Ko
 * @author     Ko Team, Eric
 * @version    $Id: file.php 109 2011-06-05 07:00:30Z eric $
 * @copyright  (c) 2009 Ko Team
 * @license    http://kophp.com/license.html
 */
class Config_File extends Config_Driver {

	// Configuration group name
	protected $_configuration_group;

	// Has the config group changed?
	protected $_configuration_modified = FALSE;

	public function __construct($directory = 'config')
	{
		// Set the configuration directory name
		$this->_directory = trim($directory, '/');

		// Load the empty array
		parent::__construct();
	}

	/**
	 * Merge all of the configuration files in this group.
	 *
	 * @param   string  group name
	 * @param   array   configuration array
	 * @return  $this   clone of the current object
	 */
	public function load($group, array $config = NULL)
	{
        if (($files = Ko::findFile($this->_directory, $group)) !== FALSE) {
			$config = array();
            foreach ($files as $file) {
                $config = Arr::merge($config, require $file);
            }
		}

		return parent::load($group, $config);
	}

	/**
	 * Save a configuration group.
	 *
	 * @param   string  file name
	 * @param   array   configuration array
	 * @return  boolean
	 */
	public function save($file, array $config = NULL)
	{
	    $filename = $file . '.php';
	    $path = DATA_PATH . $this->_directory;
	    if(!is_dir($path)) {
	        mkdir($path, 0755, true);
	    }
	    $data = "<?php\n/* $" . "Id: {$filename} " . date('Y-m-d H:i:s') . " $ */\n";
	    $data .= "defined('SYS_PATH') OR die('No direct access allowed.');\n\n";
	    $data .= "return " . str_replace("=> \n  array", '=> array', var_export($config, true)) . ";\n";
	    return file_put_contents($path . DIRECTORY_SEPARATOR . $filename, $data, LOCK_EX);
	}
	
} // End Config
