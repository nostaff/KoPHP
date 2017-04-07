<?php
require_once 'MethodTable.php';

/**
 * A built-in amfphp service that allows introspection into services and their methods.
 * Remove from production servers
 */
class AMFTestCase
{
	private $servicePath;

	function __construct() {
	   $this->servicePath = dirname(__FILE__) . DIRECTORY_SEPARATOR;
	}
	
	/**
	 * Get the list of services
	 * @returns An array of array ready to be bound to a Tree
	 */
	public function getServices()
	{
		$services = $this->_listServices($this->servicePath);
		ksort($services);
		$out = array();
		foreach($services as $val) {
			$out[] = array('label' => $val, 'data' => '');
		}
		return $out;
	}

	/**
	 * Describe a service and all its methods
	 * @param $data An object containing 'label' and 'data' keys
	 */
	public function describeService($data)
	{
		$className = $data['label'];
		//Sanitize path
		//$path = str_replace('..', '', $data['data']);
		$methodTable = MethodTable::create($this->servicePath . ($data['data'] ? DIRECTORY_SEPARATOR . $data['data'] : '') . $className . '.php', NULL, $classComment);
		return array($methodTable, $classComment);
	}

	/**
	 * Get the details of the server on which AMFPHP is running
	 */
	public function getServerDetails()
	{
		$details = array();
		$details["software"] = $_SERVER["SERVER_SOFTWARE"];
		$details["address"] = gethostbyname($_SERVER["SERVER_ADDR"]);
		$details["userAgent"] = $_SERVER["HTTP_USER_AGENT"];
		$details["name"] = $_SERVER["SERVER_NAME"];
		return $details;
	}

    protected function _listServices($dir, $suffix = "")
    {
        $services = array();
        foreach (glob($dir . '*.php') as $filename) {
        	$filename = basename($filename);
            if(strpos($filename, 'MethodTable') !== FALSE) {
                continue;
            }
            $services[] = substr($filename, 0, strrpos($filename, '.'));
        }
        return $services;
    }
	
}

