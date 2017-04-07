<?php
defined('SYS_PATH') or die('No direct script access.');

/**
 * Request and response wrapper.
 *
 * @package    Ko
 * @author     Ko Team, Eric
 * @version    $Id: request.php 109 2011-06-05 07:00:30Z eric $ request.php 36 2009-09-22 08:27:32Z eric $
 * @copyright  (c) 2008-2009 Ko Team
 * @license    http://kophp.com/license.html
 */
class Request
{
    /**
     * Scheme for http
     *
     */
    const SCHEME_HTTP  = 'http';

    /**
     * Scheme for https
     *
     */
    const SCHEME_HTTPS = 'https';

    /**
     * @var  string  method: GET, POST, PUT, DELETE, etc
     */
    public static $method = 'GET';

    /**
     * @var  string  protocol: http, https, ftp, cli, etc
     */
    public static $protocol = 'http';
    
    /**
     * @var  integer  HTTP response code: 200, 404, 500, etc
     */
    public $status = 200;

    /**
     * @var  string  response body
     */
    public $response = '';

    /**
     * @var  array  headers to send with the response body
     */
    public $headers = array();

    /**
     * @var  string  controller directory
     */
    public $directory = '';

    /**
     * @var  string  controller to be executed
     */
    public $controller;

    /**
     * @var  string  action to be executed in the controller
     */
    public $action;

    /**
     * @var  string  the URI of the request
     */
    public $uri;

    /**
     * @var  Route  route object which matched
     */
    public $route;
    
    /**
     * REQUEST_URI
     * @var string;
     */
    protected $_requestUri;

    /**
     * Base URL of request
     * @var string
     */
    protected $_baseUrl = null;

    /**
     * Base path of request
     * @var string
     */
    protected $_basePath = null;

    /**
     * PATH_INFO
     * @var string
     */
    protected $_pathInfo = '';
    /**
     * @var array  Parameters extracted from the route
     */
    protected $_params;

    /**
     * Creates a new request object for the given URI. Global GET and POST data
     * can be overloaded by setting "get" and "post" in the parameters.
     * Throws an exception when no route can be found for the URI.
     *
     * @throws  Exception
     * @param   string  URI of the request
     * @return  void
     */
    public function __construct ($uri)
    {
        // Are query strings enabled in the config file?
        // If so, we're done since segment based URIs are not used with query strings.
        if (Ko::$enable_query_strings && isset($_GET['c'])) {
            //$this->set_class(trim($this->uri->_filter_uri($_GET[$this->config->item('controller_trigger')])));
            $this->setController(Security::xss_clean($_GET['c']));

            if (isset($_GET['a'])) {
                $this->setAction(Security::xss_clean($_GET['a']));
            }
            
            if (isset($_GET['d'])) {
                $this->setDirectory(Security::xss_clean($_GET['d']));
            }
            $this->_params = array();
            return;
        }
        
        // Remove trailing slashes from the URI
        $uri = trim($uri, '/');
        // Load routes
        $routes = Route::all();
        foreach ($routes as $route) {
            if (($params = $route->matches($uri)) !== false) {
                // Store the URI
                $this->uri = $uri;
                // Store the matching route
                $this->route = $route;
                $this->controller = Security::xss_clean($params['controller']);
                if (isset($params['directory'])) {
                    // Controllers are in a sub-directory
                    $this->directory = Security::xss_clean($params['directory']);
                }
                if (isset($params['action'])) {
                    $this->action = Security::xss_clean($params['action']);
                }
                unset($params['controller'], $params['action'], $params['directory']);
                $this->_params = $params;
                return;
            }
        }
        // No matching route for this URI
        $this->status = 404;
        //throw new KoException('Unable to find a route to match the URI: :uri', array(':uri' => $uri));
        $this->sendHeaders();
        
        // Re-throw the exception
        $this->redirect(Ko::$base_url . Ko::$error_page);
    }

    /**
     * Main request singleton instance. If no URI is provided, the URI will
     * be automatically detected using PATH_INFO, REQUEST_URI, or PHP_SELF.
     *
     * @param   string   URI of the request
     * @return  Request
     */
    public static function instance ($uri = TRUE)
    {
        static $instance;
        if ($instance === NULL) {
            if (Ko::$is_cli) {
                // Default protocol for command line is cli://
                self::$protocol = 'cli';
                // Get the command line options
                $options = CLI::options('uri', 'method', 'get', 'post');
                if (isset($options['uri'])) {
                    // Use the specified URI
                    $uri = $options['uri'];
                }
                if (isset($options['method'])) {
                    // Use the specified method
                    self::$method = strtoupper($options['method']);
                }
                if (isset($options['get'])) {
                    // Overload the global GET data
                    parse_str($options['get'], $_GET);
                }
                if (isset($options['post'])) {
                    // Overload the global POST data
                    parse_str($options['post'], $_POST);
                }
            } else {
                if (isset($_SERVER['REQUEST_METHOD'])) {
                    // Use the server request method
                    self::$method = $_SERVER['REQUEST_METHOD'];
                }
                if (! empty($_SERVER['HTTPS']) and filter_var($_SERVER['HTTPS'], FILTER_VALIDATE_BOOLEAN)) {
                    // This request is secure
                    self::$protocol = 'https';
                }
                if (self::$method !== 'GET' && self::$method !== 'POST') {
                    // Methods besides GET and POST do not properly parse the form-encoded
                    // query string into the $_POST array, so we overload it manually.
                    parse_str(file_get_contents('php://input'), $_POST);
                }
                if ($uri === TRUE) {
                    if (isset($_SERVER['PATH_INFO'])) {
                        $uri = $_SERVER['PATH_INFO'];
                    } else {
                        if (isset($_SERVER['REQUEST_URI'])) {
                            $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
                        } elseif (isset($_SERVER['PHP_SELF'])) {
                            $uri = $_SERVER['PHP_SELF'];
                        } else {
                            throw new KoException('Unable to detect the URI using PATH_INFO, REQUEST_URI, or PHP_SELF');
                        }
                        // Get the path from the base URL, including the index file
                        $base_url = parse_url(Ko::$base_url, PHP_URL_PATH);
                        if (strpos($uri, $base_url) === 0) {
                            $uri = substr($uri, strlen($base_url));
                        }
                        if (Ko::$index_file && strpos($uri, Ko::$index_file) === 0) {
                            $uri = substr($uri, strlen(Ko::$index_file));
                        }
                    }
                }
            }
            // Reduce multiple slashes to a single slash
            $uri = preg_replace('#//+#', '/', $uri);
            // Remove all dot-paths from the URI, they are not valid
            $uri = preg_replace('#\.[\s./]*/#', '', $uri);
            // Create the instance singleton
            $instance = new self($uri);
            // Add the Content-Type header
            $instance->headers['Content-Type'] = 'text/html; charset=' . Ko::$charset;
        }
        return $instance;
    }

    /**
     * Creates a new request object for the given URI.
     *
     * @param   string  URI of the request
     * @return  Request
     */
    public static function factory ($uri)
    {
        return new self($uri);
    }

    /**
     * Access values contained in the superglobals as public members
     * Order of precedence: 1. GET, 2. POST, 3. COOKIE, 4. SERVER, 5. ENV
     *
     * @see http://msdn.microsoft.com/en-us/library/system.web.httprequest.item.aspx
     * @param string $key
     * @return mixed
     */
    public function __get ($key)
    {
        switch (true) {
            case isset($this->_params[$key]):
                return Security::xss_clean($this->_params[$key]);
            case isset($_GET[$key]):
                return Security::xss_clean($_GET[$key]);
            case isset($_POST[$key]):
                return Security::xss_clean($_POST[$key]);
            case isset($_COOKIE[$key]):
                return Security::xss_clean($_COOKIE[$key]);
            case isset($_SERVER[$key]):
                return $_SERVER[$key];
            case isset($_ENV[$key]):
                return $_ENV[$key];
            default:
                return null;
        }
    }

    /**
     * Alias to __get
     *
     * @param string $key
     * @return mixed
     */
    public function get ($key)
    {
        return $this->__get($key);
    }

    /**
     * Set values
     *
     * In order to follow {@link __get()}, which operates on a number of
     * superglobals, setting values through overloading is not allowed and will
     * raise an exception. Use setParam() instead.
     *
     * @param string $key
     * @param mixed $value
     * @return void
     * @throws Exception
     */
    public function __set ($key, $value)
    {
        throw new Exception('Setting values in superglobals not allowed; please use setParam()');
    }

    /**
     * Alias to __set()
     *
     * @param string $key
     * @param mixed $value
     * @return void
     */
    public function set ($key, $value)
    {
        return $this->__set($key, $value);
    }

    /**
     * Check to see if a property is set
     *
     * @param string $key
     * @return boolean
     */
    public function __isset ($key)
    {
        switch (true) {
            case isset($this->_params[$key]):
                return true;
            case isset($_GET[$key]):
                return true;
            case isset($_POST[$key]):
                return true;
            case isset($_COOKIE[$key]):
                return true;
            case isset($_SERVER[$key]):
                return true;
            case isset($_ENV[$key]):
                return true;
            default:
                return false;
        }
    }

    /**
     * Alias to __isset()
     *
     * @param string $key
     * @return boolean
     */
    public function has($key)
    {
        return $this->__isset($key);
    }
    
    /**
     * Set GET values
     *
     * @param  string|array $spec
     * @param  null|mixed $value
     * @return Request
     */
    public function setQuery($spec, $value = null)
    {
        if ((null === $value) && !is_array($spec)) {
            throw new KoException('Invalid value passed to setQuery(); must be either array of values or key/value pair');
        }
        if ((null === $value) && is_array($spec)) {
            foreach ($spec as $key => $value) {
                $this->setQuery($key, $value);
            }
            return $this;
        }
        $_GET[(string) $spec] = $value;
        return $this;
    }

    /**
     * Retrieve a member of the $_GET superglobal
     *
     * If no $key is passed, returns the entire $_GET array.
     *
     * @param string $key
     * @param mixed $default Default value to use if key not found
     * @return mixed Returns null if key does not exist
     */
    public function getQuery($key = null, $default = null)
    {
        if (null === $key) {
            return Security::xss_clean($_GET);
        }

        return (isset($_GET[$key])) ? Security::xss_clean($_GET[$key]) : $default;
    }

    /**
     * Retrieve a member of the $_POST superglobal
     *
     * If no $key is passed, returns the entire $_POST array.
     *
     * @param string $key
     * @param mixed $default Default value to use if key not found
     * @return mixed Returns null if key does not exist
     */
    public function getPost($key = null, $default = null)
    {
        if (null === $key) {
            return Security::xss_clean($_POST);
        }

        return (isset($_POST[$key])) ? Security::xss_clean($_POST[$key]) : $default;
    }

    /**
     * Retrieve a member of the $_COOKIE superglobal
     *
     * If no $key is passed, returns the entire $_COOKIE array.
     *
     * @param string $key
     * @param mixed $default Default value to use if key not found
     * @return mixed Returns null if key does not exist
     */
    public function getCookie($key = null, $default = null)
    {
        if (null === $key) {
            return Security::xss_clean($_COOKIE);
        }

        return (isset($_COOKIE[$key])) ? Security::xss_clean($_COOKIE[$key]) : $default;
    }

    /**
     * Retrieve a member of the $_SERVER superglobal
     *
     * If no $key is passed, returns the entire $_SERVER array.
     *
     * @param string $key
     * @param mixed $default Default value to use if key not found
     * @return mixed Returns null if key does not exist
     */
    public function getServer($key = null, $default = null)
    {
        if (null === $key) {
            return $_SERVER;
        }

        return (isset($_SERVER[$key])) ? $_SERVER[$key] : $default;
    }
    
    /**
     * Set the REQUEST_URI on which the instance operates
     *
     * If no request URI is passed, uses the value in $_SERVER['REQUEST_URI'],
     * $_SERVER['HTTP_X_REWRITE_URL'], or $_SERVER['ORIG_PATH_INFO'] + $_SERVER['QUERY_STRING'].
     *
     * @param string $requestUri
     * @return Request
     */
    public function setRequestUri($requestUri = null)
    {
        if ($requestUri === null) {
            if (isset($_SERVER['HTTP_X_REWRITE_URL'])) { // check this first so IIS will catch
                $requestUri = $_SERVER['HTTP_X_REWRITE_URL'];
            } elseif (isset($_SERVER['REQUEST_URI'])) {
                $requestUri = $_SERVER['REQUEST_URI'];
                // Http proxy reqs setup request uri with scheme and host [and port] + the url path, only use url path
                $schemeAndHttpHost = $this->getScheme() . '://' . $this->getHttpHost();
                if (strpos($requestUri, $schemeAndHttpHost) === 0) {
                    $requestUri = substr($requestUri, strlen($schemeAndHttpHost));
                }
            } elseif (isset($_SERVER['ORIG_PATH_INFO'])) { // IIS 5.0, PHP as CGI
                $requestUri = $_SERVER['ORIG_PATH_INFO'];
                if (!empty($_SERVER['QUERY_STRING'])) {
                    $requestUri .= '?' . $_SERVER['QUERY_STRING'];
                }
            } else {
                return $this;
            }
        } elseif (!is_string($requestUri)) {
            return $this;
        } else {
            // Set GET items, if available
            if (false !== ($pos = strpos($requestUri, '?'))) {
                // Get key => value pairs and set $_GET
                $query = substr($requestUri, $pos + 1);
                parse_str($query, $vars);
                $this->setQuery($vars);
            }
        }

        $this->_requestUri = $requestUri;
        return $this;
    }

    /**
     * Returns the REQUEST_URI taking into account
     * platform differences between Apache and IIS
     *
     * @return string
     */
    public function getRequestUri()
    {
        if (empty($this->_requestUri)) {
            $this->setRequestUri();
        }

        return $this->_requestUri;
    }

    /**
     * Set the base URL of the request; i.e., the segment leading to the script name
     *
     * E.g.:
     * - /admin
     * - /myapp
     * - /subdir/index.php
     *
     * Do not use the full URI when providing the base. The following are
     * examples of what not to use:
     * - http://example.com/admin (should be just /admin)
     * - http://example.com/subdir/index.php (should be just /subdir/index.php)
     *
     * If no $baseUrl is provided, attempts to determine the base URL from the
     * environment, using SCRIPT_FILENAME, SCRIPT_NAME, PHP_SELF, and
     * ORIG_SCRIPT_NAME in its determination.
     *
     * @param mixed $baseUrl
     * @return Request
     */
    public function setBaseUrl($baseUrl = null)
    {
        if ((null !== $baseUrl) && !is_string($baseUrl)) {
            return $this;
        }

        if ($baseUrl === null) {
            $filename = (isset($_SERVER['SCRIPT_FILENAME'])) ? basename($_SERVER['SCRIPT_FILENAME']) : '';

            if (isset($_SERVER['SCRIPT_NAME']) && basename($_SERVER['SCRIPT_NAME']) === $filename) {
                $baseUrl = $_SERVER['SCRIPT_NAME'];
            } elseif (isset($_SERVER['PHP_SELF']) && basename($_SERVER['PHP_SELF']) === $filename) {
                $baseUrl = $_SERVER['PHP_SELF'];
            } elseif (isset($_SERVER['ORIG_SCRIPT_NAME']) && basename($_SERVER['ORIG_SCRIPT_NAME']) === $filename) {
                $baseUrl = $_SERVER['ORIG_SCRIPT_NAME']; // 1and1 shared hosting compatibility
            } else {
                // Backtrack up the script_filename to find the portion matching
                // php_self
                $path    = isset($_SERVER['PHP_SELF']) ? $_SERVER['PHP_SELF'] : '';
                $file    = isset($_SERVER['SCRIPT_FILENAME']) ? $_SERVER['SCRIPT_FILENAME'] : '';
                $segs    = explode('/', trim($file, '/'));
                $segs    = array_reverse($segs);
                $index   = 0;
                $last    = count($segs);
                $baseUrl = '';
                do {
                    $seg     = $segs[$index];
                    $baseUrl = '/' . $seg . $baseUrl;
                    ++$index;
                } while (($last > $index) && (false !== ($pos = strpos($path, $baseUrl))) && (0 != $pos));
            }

            // Does the baseUrl have anything in common with the request_uri?
            $requestUri = $this->getRequestUri();

            if (0 === strpos($requestUri, $baseUrl)) {
                // full $baseUrl matches
                $this->_baseUrl = $baseUrl;
                return $this;
            }

            if (0 === strpos($requestUri, dirname($baseUrl))) {
                // directory portion of $baseUrl matches
                $this->_baseUrl = rtrim(dirname($baseUrl), '/');
                return $this;
            }

            if (!strpos($requestUri, basename($baseUrl))) {
                // no match whatsoever; set it blank
                $this->_baseUrl = '';
                return $this;
            }
            // If using mod_rewrite or ISAPI_Rewrite strip the script filename
            // out of baseUrl. $pos !== 0 makes sure it is not matching a value
            // from PATH_INFO or QUERY_STRING
            if ((strlen($requestUri) >= strlen($baseUrl)) && ((false !== ($pos = strpos($requestUri, $baseUrl))) && ($pos !== 0))) {
                $baseUrl = substr($requestUri, 0, $pos + strlen($baseUrl));
            }
        }

        $this->_baseUrl = rtrim($baseUrl, '/');
        return $this;
    }

    /**
     * Everything in REQUEST_URI before PATH_INFO
     * <form action="<?=$baseUrl?>/news/submit" method="POST"/>
     *
     * @return string
     */
    public function getBaseUrl()
    {
        if (null === $this->_baseUrl) {
            $this->setBaseUrl();
        }

        return $this->_baseUrl;
    }

    /**
     * Set the base path for the URL
     *
     * @param string|null $basePath
     * @return Request
     */
    public function setBasePath($basePath = null)
    {
        if ($basePath === null) {
            $filename = basename($_SERVER['SCRIPT_FILENAME']);

            $baseUrl = $this->getBaseUrl();
            if (empty($baseUrl)) {
                $this->_basePath = '';
                return $this;
            }

            if (basename($baseUrl) === $filename) {
                $basePath = dirname($baseUrl);
            } else {
                $basePath = $baseUrl;
            }
        }

        if (substr(PHP_OS, 0, 3) === 'WIN') {
            $basePath = str_replace('\\', '/', $basePath);
        }

        $this->_basePath = rtrim($basePath, '/');
        return $this;
    }

    /**
     * Everything in REQUEST_URI before PATH_INFO not including the filename
     * <img src="<?=$basePath?>/images/zend.png"/>
     *
     * @return string
     */
    public function getBasePath()
    {
        if (null === $this->_basePath) {
            $this->setBasePath();
        }

        return $this->_basePath;
    }

    /**
     * Set the PATH_INFO string
     *
     * @param string|null $pathInfo
     * @return Request
     */
    public function setPathInfo($pathInfo = null)
    {
        if ($pathInfo === null) {
            $baseUrl = $this->getBaseUrl();

            if (null === ($requestUri = $this->getRequestUri())) {
                return $this;
            }

            // Remove the query string from REQUEST_URI
            if ($pos = strpos($requestUri, '?')) {
                $requestUri = substr($requestUri, 0, $pos);
            }

            if ((null !== $baseUrl)
                && (false === ($pathInfo = substr($requestUri, strlen($baseUrl)))))
            {
                // If substr() returns false then PATH_INFO is set to an empty string
                $pathInfo = '';
            } elseif (null === $baseUrl) {
                $pathInfo = $requestUri;
            }
        }

        $this->_pathInfo = (string) $pathInfo;
        return $this;
    }

    /**
     * Returns everything between the BaseUrl and QueryString.
     * This value is calculated instead of reading PATH_INFO
     * directly from $_SERVER due to cross-platform differences.
     *
     * @return string
     */
    public function getPathInfo()
    {
        if (empty($this->_pathInfo)) {
            $this->setPathInfo();
        }

        return $this->_pathInfo;
    }
    
    /**
     * Return request directory
     *
     * @return string
     */
    public function getDirectory ()
    {
        return $this->directory;
    }
    
    /**
     * Set request directory
     *
     * @param   string
     * @return  void
     */
    public function setDirectory ($directory)
    {
        $this->directory = $directory;
    }
    
    /**
     * Return request controller
     *
     * @return string
     */
    public function getController ()
    {
        return $this->controller;
    }
    
    /**
     * Set request controller
     *
     * @param   string
     * @return  void
     */
    public function setController ($controller)
    {
        $this->controller = $controller;
    }

    /**
     * Return request action
     *
     * @return string
     */
    public function getAction ()
    {
        return $this->action;
    }

    /**
     * Set request action
     *
     * @param   string
     * @return  void
     */
    public function setAction ($action)
    {
        $this->action = $action;
    }

    /**
     * Retrieves a value from the route parameters.
     *
     * @param   string   key of the value
     * @param   mixed    default value if the key is not set
     * @return  mixed
     */
    public function getParam ($key, $default = NULL)
    {
        if (isset($this->_params[$key])) {
            return Security::xss_clean($this->_params[$key]);
        } elseif (isset($_GET[$key])) {
            return Security::xss_clean($_GET[$key]);
        } elseif (isset($_POST[$key])) {
            return Security::xss_clean($_POST[$key]);
        } else {
            return $default;
        }
    }

    /**
     * Retrieves all values from the route parameters.
     *
     * @return  mixed
     */
    public function getParams ()
    {
        $return = $this->_params;
        if (isset($_GET) && is_array($_GET)) {
            $return += $_GET;
        }
        if (isset($_POST) && is_array($_POST)) {
            $return += $_POST;
        }
        return Security::xss_clean($return);
    }

    /**
     * Set an action parameter
     *
     * A $value of null will unset the $key if it exists
     *
     * @param string $key
     * @param mixed $value
     * @return Request
     */
    public function setParam ($key, $value)
    {
        $key = (string) $key;
        if ((null === $value) && isset($this->_params[$key])) {
            unset($this->_params[$key]);
        } elseif (null !== $value) {
            $this->_params[$key] = $value;
        }
        return $this;
    }

    /**
     * Set parameters
     *
     * Set one or more parameters. Parameters are set as userland parameters,
     * using the keys specified in the array.
     *
     * @param array $params
     * @return Request
     */
    public function setParams(array $params)
    {
        foreach ($params as $key => $value) {
            $this->setParam($key, $value);
        }
        return $this;
    }

    /**
     * Was the request made by POST?
     *
     * @return boolean
     */
    public function isPost()
    {
        if ('POST' == $this->getServer('REQUEST_METHOD')) {
            return true;
        }

        return false;
    }

    /**
     * Is the request a Javascript XMLHttpRequest?
     *
     * @return boolean
     */
    public function isAjax()
    {
        return ($this->getServer('HTTP_X_REQUESTED_WITH') == 'XMLHttpRequest');
    }

    /**
     * Is this a Flash request?
     *
     * @return bool
     */
    public function isFlash()
    {
        return strstr(strtolower($this->getServer('USER_AGENT')), ' flash');
    }

    /**
     * Tests if the current request is an AMF request by checking the content type to see if it is
     * of the type 'application/x-amf'
     *
     * @return  boolean
     */
    public function isAmf()
    {
        return (strtolower($this->getServer('CONTENT_TYPE')) === 'application/x-amf');
    }
    
    /**
     * Is https secure request
     *
     * @return boolean
     */
    public function isSecure()
    {
        return ($this->getScheme() === self::SCHEME_HTTPS);
    }

    /**
     * Return the raw body of the request, if present
     *
     * @return string|false Raw body, or false if not present
     */
    public function getRawBody()
    {
        $body = file_get_contents('php://input');

        if (strlen(trim($body)) > 0) {
            return $body;
        }

        return false;
    }

    /**
     * Get the request URI scheme
     *
     * @return string
     */
    public function getScheme()
    {
        return ($this->getServer('HTTPS') == 'on') ? self::SCHEME_HTTPS : self::SCHEME_HTTP;
    }

    /**
     * Get the HTTP host.
     *
     * "Host" ":" host [ ":" port ] ; Section 3.2.2
     * Note the HTTP Host header is not the same as the URI host.
     * It includes the port while the URI host doesn't.
     *
     * @return string
     */
    public function getHttpHost()
    {
        $host = $this->getServer('HTTP_HOST');
        if (! empty($host)) {
            return $host;
        }
        $scheme = $this->getScheme();
        $name = $this->getServer('SERVER_NAME');
        $port = $this->getServer('SERVER_PORT');
        if (($scheme == self::SCHEME_HTTP && $port == 80) || ($scheme == self::SCHEME_HTTPS && $port == 443)) {
            return $name;
        } else {
            return $name . ':' . $port;
        }
    }

    /**
     * Get the Client IP.
     *
     * @return s
     */
    public function getClientIp ()
    {
        $clientIp = '';
        if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $clientIp = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } elseif (isset($_SERVER['HTTP_CLIENT_IP'])) {
            $clientIp = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (isset($_SERVER['REMOTE_ADDR'])) {
            $clientIp = $_SERVER['REMOTE_ADDR'];
        }
        return $clientIp;
    }
    /**
     * Sends the response status and all set headers.
     *
     * @return  Request
     */
    public function sendHeaders ()
    {
        if (! headers_sent()) {
            foreach ($this->headers as $name => $value) {
                if (is_string($name)) {
                    $value = "{$name}: {$value}";
                }
                header($value, TRUE, $this->status);
            }
            if ($this->status) {
                $errRequest = Ko::config('request');
                header($errRequest[$this->status], TRUE, $this->status);
                unset($errRequest);
            }
        }
        return $this;
    }

    /**
     * Redirects as the request response.
     *
     * @param   string   redirect location
     * @param   integer  status code
     * @return  void
     */
    public function redirect ($url, $code = 302)
    {
        if (strpos($url, '://') === FALSE) {
            // Make the URI into a URL
            $url = URL::site($url);
        }
        // Set the response status
        $this->status = $code;
        // Set the location header
        $this->headers['Location'] = $url;
        // Send headers
        $this->sendHeaders();
        // Stop execution
        exit();
    }
    
    /**
     * Processes the request, executing the controller. Before the routed action
     * is run, the before() method will be called, which allows the controller
     * to overload the action based on the request parameters. After the action
     * is run, the after() method will be called, for post-processing.
     *
     * By default, the output from the controller is captured and returned, and
     * no headers are sent.
     *
     * @return Request
     */
    public function execute ()
    {
    	// Create the class prefix
    	$prefix = '';

    	if ($this->directory) {
    		// Add the directory name to the class prefix
            $directory = str_replace (array('\\', '/', '//'), '_', trim($this->directory, '/'));
    		$directories = explode('_', $directory);
    		foreach ($directories as $directory) {
                $prefix .= ucfirst($directory) . '_';
    		}
    	}
        try {
            // Load the controller using reflection
            $class = new ReflectionClass(ucfirst($prefix) . ucfirst($this->controller) . 'Controller');
            if ($class->isAbstract()) {
                throw new KoException('Cannot create instances of abstract controller: :error',
                    array(':error' => $prefix . ucfirst($this->controller) . 'Controller'));
            }
        } catch (Exception $e) {
        	
            if ($e instanceof ReflectionException) {
                // Reflection will throw exceptions for missing classes or actions
                $this->status = 404;
            } else {
                // All other exceptions are PHP/server errors
                $this->status = 500;
            }
            $this->sendHeaders();
            
            // Re-throw the exception
            if (Ko::$errors) {
                throw $e;
            } else {
                $this->redirect(Ko::$base_url . Ko::$error_page);
            }
            exit(0);
        }
        try {
            // Create a new instance of the controller
            $controller = $class->newInstance($this);
            
            // Execute the "before action" method
            $class->getMethod('before')->invoke($controller);
            
            // Determine the action to use
            $action = empty($this->action) ? Route::$default_action : $this->action;
            
            // Execute the main action with the parameters
            $class->getMethod($action)->invokeArgs($controller, $this->_params);
            
            // Execute the "after action" method
            $class->getMethod('after')->invoke($controller);
        } catch (ReflectionException $e) {
            // Use __call instead
            $class->getMethod('__call')->invokeArgs($controller, array($this->getAction() , $this->_params));
        }
        return $this;
    }

    /**
     * Response the response data
     *
     * @return  0
     */
    public function response ()
    {
        echo $this->response;
        exit(0);
    }

    /**
     * Generates a relative URI for the current route.
     *
     * @param   array   additional route parameters
     * @return  string
     */
    public function uri (array $params = NULL)
    {
    	if ( ! isset($params['directory'])) {
    		// Add the current directory
    		$params['directory'] = $this->directory;
        }
        if (! isset($params['controller'])) {
            // Add the current controller
            $params['controller'] = $this->controller;
        }
        if (! isset($params['action'])) {
            // Add the current action
            $params['action'] = $this->action;
        }
        // Add the current parameters
        $params += $this->_params;
        return $this->route->uri($params);
    }

    /**
     * Returns the response as the string representation of a request.
     *
     * @return  string
     */
    public function __toString ()
    {
        return (string) $this->response;
    }
} // End Request
