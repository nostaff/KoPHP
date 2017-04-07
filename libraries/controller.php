<?php
defined('SYS_PATH') or die('No direct script access.');

/**
 * Abstract controller class.
 *
 * @package    Controller
 * @author     Ko Team, Eric
 * @version    $Id: controller.php 109 2011-06-05 07:00:30Z eric $
 * @copyright  (c) 2008-2009 Ko Team
 * @license    http://kophp.com/license.html
 */
abstract class Controller
{
    /**
     * @var Request Request that created the controller
     */
    public $request;

    /**
     * @var  string  page template
     */
    public $template = '';

    /**
     * @var  View object view
     */
    public $view = NULL;

    /**
     * @var  boolean  auto render template
     **/
    public $auto_render = TRUE;

    /**
     * Creates a new controller instance. Each controller must be constructed
     * with the request object that created it.
     *
     * @param   Request  $request
     * @return  void
     */
    public function __construct (Request $request)
    {
        // Assign the request to the controller
        $this->request = $request;
    }
    
    /**
     * Get the base_url, useful for js/css files in templates
     *
     * @return string the base URL
     */
    public function getBaseUrl()
    {
        return Ko::$base_url;
    }

    /**
     * Automatically executed before the controller action.
     * Loads the template View object.
     *
     * @return  void
     */
    public function before ()
    {
        if ($this->auto_render === TRUE) {
            // Load the template
            $this->template = $this->request->getController() . '/' . $this->request->getAction();
            $this->view = View::factory($this->template);
        }
    }

    /**
     * Assigns the template as the request response.
     *
     * @param   string   request method
     * @return  void
     */
    public function after ()
    {
        if ($this->auto_render === TRUE) {
            // Assign the template as the request response and render it
            $this->view->render();
        }
    }
    
    /**
     * Setup whether use auto render.
     *
     * @param bool $auto_render
     */
    public function autoRender ($auto_render = FALSE)
    {
        $this->auto_render = (bool) $auto_render;
    }
    
    /**
     * Return the request that created this controller object.
     *
     * @return Request
     */
    public function getRequest ()
    {
        return $this->request;
    }

    /**
     * Redirect to $url
     *
     * @param string $url
     * @param int $code
     */
    public function redirect ($url, $code = 302)
    {
        $this->request->redirect($url, $code);
    }
    
    public function __call ($method, $arguments)
    {
        // Disable auto-rendering
        $this->autoRender(FALSE);
        
        if (Ko::$errors) {
            throw new KoException('Method :error does not exist, args: :arguments', array(':error' => $method, ':arguments' => var_export($arguments, true)));
        } else {
            $this->request->status = 404;
            $this->request->sendHeaders();
            exit(0);
        }
        exit(0);
    }
} // End Controller
