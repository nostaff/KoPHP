<?php
defined('SYS_PATH') or die('No direct script access.');

/**
 * Acts as an object wrapper for HTML pages with embedded PHP, called "views".
 * Variables can be assigned with the view object and referenced locally within
 * the view.
 *
 * @package    Ko
 * @author     Ko Team, Eric 
 * @version    $Id: view.php 78 2009-10-09 04:01:57Z eric $
 * @copyright  (c) 2008-2009 Ko Team
 * @license    http://kophp.com/license.html
 */
require_once 'smarty/Smarty.class.php';

class View
{
    /**
     * View filename
     *
     * @var str
     */
    protected $_file;
    
    /**
     * Smarty object
     *
     * @var Smarty
     */
    protected $_smarty;

    /**
     * Sets the initial view filename and local data.
     *
     * @param   string  view filename
     * @param   array   array of values
     * @return  void
     */
    public function __construct ($file = NULL, array $data = NULL)
    {
        if (! empty($file)) {
            $this->setView($file);
        }
        
        $this->_smarty = new Smarty();
        if ($data !== NULL) {
            $this->_smarty->assign($data);
        }        
        $this->_smarty->template_dir   = Ko::config('smarty.template_path');
        $this->_smarty->cache_dir      = Ko::config('smarty.cache_path');
        $this->_smarty->compile_dir    = Ko::config('smarty.compile_path');
        $this->_smarty->config_dir     = Ko::config('smarty.configs_path');
            
        // setup left && right delimiter
        if (Ko::config('smarty.left_delimiter'))
            $this->_smarty->left_delimiter = Ko::config('smarty.left_delimiter');
        if (Ko::config('smarty.right_delimiter'))
            $this->_smarty->right_delimiter = Ko::config('smarty.right_delimiter');
    }
    
    /**
     * Returns a new View object.
     *
     * @param   string  view filename
     * @param   array   array of values
     * @return  View
     */
    public static function factory ($view = NULL, array $data = NULL)
    {
        return new self($view, $data);
    }

    /**
     * Assign variables to the template
     *
     * Allows setting a specific key to the specified value, OR passing an array
     * of key => value pairs to set en masse.
     *
     * @see __set()
     * @param string|array $spec The assignment strategy to use (key or array of key
     * => value pairs)
     * @param mixed $value (Optional) If assigning a named variable, use this
     * as the value.
     * @return void
     */
    public function assign ($spec, $value = null)
    {
        if (is_array($spec)) {
            $this->_smarty->assign($spec);
            return;
        }
        $this->_smarty->assign($spec, $value);
    }

    /**
     * Clear all assigned variables
     *
     * Clears all variables assigned to Zend_View either via {@link assign()} or
     * property overloading ({@link __get()}/{@link __set()}).
     *
     * @return void
     */
    public function clearVars ()
    {
        $this->_smarty->clear_all_assign();
    }

    /**
     * Sets the view filename.
     *
     * @throws  Exception
     * @param   string  filename
     * @return  View
     */
    public function setView ($filename)
    {
        // Store the file path locally
        $this->_file = $filename;
        return $this;
    }

    /**
     * Return this view filename.
     *
     * @return string
     */
    public function getView ()
    {
        return $this->_file;
    }

    /**
     * Assigns a variable by name. Assigned values will be available as a
     * variable within the view file:
     *
     *     // This value can be accessed as $foo within the view
     *     $view->set('foo', 'my value');
     *
     * You can also use an array to set several values at once:
     *
     *     // Create the values $food and $beverage in the view
     *     $view->set(array('food' => 'bread', 'beverage' => 'water'));
     *
     * @param   string   variable name or an array of variables
     * @param   mixed    value
     * @return  View
     */
    public function set ($key, $value = NULL)
    {
        $this->assign($key, $value);
        return $this;
    }

    /**
     * Captures the output that is generated when a view is included.
     * The view data will be extracted to make local variables. This method
     * is static to prevent object scope resolution.
     *
     * @param   string  filename
     * @return  string
     */
    public function capture ($filename = NULL, $display = FALSE)
    {
        if ($filename !== NULL) {
            $this->setView($filename);
        }
        if (empty($this->_file)) {
            throw new Exception('You must set the file to use within your view before rendering');
        }
        // Check &&　get the file's realpath
        $type = Ko::config('smarty.templates_ext') or $type = 'tpl';
        if (($filepath = Ko::findFile('views', $this->_file, $type)) === FALSE) {
            throw new KoException('The requested view :file could not be found', array(':file' => $this->_file . '.' . $type));
        }
        return $this->_smarty->fetch($filepath, NULL, NULL, $display);
    }

    /**
     * Renders the view object to a string. Global and local data are merged
     * and extracted to create local variables within the view file.
     *
     * Note: Global variables with the same key name as local variables will be
     * overwritten by the local variable.
     *
     * @throws   Exception
     * @param    view filename
     * @return   boolean
     */
    public function render ($filename = NULL)
    {
        return $this->capture($filename, TRUE);
    }

    /**
     * Magic method, Assign a variable to the template
     *
     * @param string $key The variable name.
     * @param mixed $val The variable value.
     * @return void
     */
    public function __set ($key, $val)
    {
        $this->_smarty->assign($key, $val);
    }

    /**
     * Magic method, Retrieve an assigned variable
     *
     * @param string $key The variable name.
     * @return mixed The variable value.
     */
    public function __get ($key)
    {
        return $this->_smarty->get_template_vars($key);
    }

    /**
     * Magic method, Allows testing with empty() and isset() to work
     *
     * @param string $key
     * @return boolean
     */
    public function __isset ($key)
    {
        return (null !== $this->_smarty->get_template_vars($key));
    }

    /**
     * Magic method, Allows unset() on object properties to work
     *
     * @param string $key
     * @return void
     */
    public function __unset ($key)
    {
        $this->_smarty->clear_assign($key);
    }

    /**
     * Magic method, returns the output of render(). If any exceptions are
     * thrown, the exception output will be returned instead.
     *
     * @return  string
     */
    public function __toString ()
    {
        try {
            return $this->capture();
        } catch (Exception $e) {
            Ko::exception_handler($e);
            return '';
        }
    }
    
} // End View
