<?php
defined('SYS_PATH') OR die('No direct access allowed.');

/**
 * Default AMF Gateway controller. This controller should be called in FLEX or Flash.
 *
 * @package    Core
 * @author     Ko Team, Eric
 * @version    $Id: gateway.php 103 2010-08-12 06:52:18Z eric $
 * @copyright  (c) 2008-2009 Ko Team, Eric
 * @license    http://kophp.com/license.html
 */
require_once 'Zend/Amf/Server.php';

class GatewayController extends Controller
{
	public $amf;

	public function before()
	{
		//  Auto render , use user defined template.
		$this->autoRender(FALSE);

		parent::before();
	}

	public function index()
	{

		$this->amf = new Zend_Amf_Server();
		$this->amf->setProduction(false);
		$this->amf->addDirectory(APP_PATH . 'services');
		$handle = $this->amf->handle();

		// fixes bug with content-type headers
		if ($this->request->isAmf())
		  $this->request->response = $handle;
		else
		  echo $handle;
	}

	public function after()
	{
		parent::after();
	}

} // End AMF Controller
