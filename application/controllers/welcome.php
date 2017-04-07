<?php 
defined('SYS_PATH') OR die('No direct access allowed.');

/**
 * Default Ko controller. This controller should NOT be used in production.
 * It is for demonstration purposes only!
 *
 * @package    Core
 * @author     Ko Team, Eric 
 * @version    $Id: welcome.php 82 2009-10-16 03:54:25Z eric $
 * @copyright  (c) 2008-2009 Ko Team, Eric 
 * @license    http://kophp.com/license.html
 */

class WelcomeController extends Controller
{
    /**
     * Creates a new controller instance. Each controller must be constructed
     * with the request object that created it.
     *
     * @param   Request  $request
     * @return  void
     */    
    public function __construct (Request $request)
    {
         parent::__construct($request);
         
         //  Auto render , use user defined template.
        $this->autoRender(FALSE);
    }
    
    /*
     * Default action
     */
    public function index ()
    {
        // In Ko, all views are loaded and treated as objects.
        $this->view = View::factory('template');
        // You can assign anything variable to a view by using standard OOP
        // methods. In my welcome view, the $title variable will be assigned
        // the value I give it here.
        $this->view->title = 'Welcome to Ko!';

		// An array of links to display. Assiging variables to views is completely
		// asyncronous. Variables can be set in any order, and can be any type
		// of data, including objects.
		$this->view->links = array (
			'Home Page'     => 'http://kophp.com/',
			'Documentation' => 'http://docs.kophp.com/',
			'Forum'         => 'http://forum.kophp.com/',
			'License'       => 'Ko License.html',
			'Donate'        => 'http://kophp.com/donate',
		);
		$this->view->controller = $this->getRequest()->getController();
        $this->view->action = $this->getRequest()->getAction();
        $this->view->template = str_replace(DOC_ROOT, '', $this->view->getView());
        $this->view->render();
	}
	
	public function __call($method, $arguments)
	{
		// Disable auto-rendering
		$this->auto_render = FALSE;
        echo $method;
        print_r($arguments);
		// By defining a __call method, all pages routed to this controller
		// that result in 404 errors will be handled by this method, instead of
		// being displayed as "Page Not Found" errors.
		echo 'This text is generated by __call. If you expected the index page, you need to use: welcome/index';
		die();
	}

} // End Welcome Controller