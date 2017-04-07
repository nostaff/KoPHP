<?php
defined('SYS_PATH') or die('No direct script access.');

/**
 * Contains the most low-level helpers methods in Ko:
 *
 * - Environment initialization
 * - Locating files within the cascading filesystem
 * - Auto-loading and transparent extension of classes
 * - Variable and path debugging
 *
 * @package    Ko
 * @author     Ko Team, Eric
 * @version    $Id: core.php 109 2011-06-05 07:00:30Z eric $
 * @copyright  (c) 2008-2009 Ko Team
 * @license    http://kophp.com/license.html
 */
class Ko
{
    // Log message types
    const ERROR = 'ERROR';

    const DEBUG = 'DEBUG';

    const INFO = 'INFO';

    // Security check that is added to all generated PHP files
    const FILE_SECURITY = "<?php defined('SYS_PATH') or die('No direct script access.'); ";

    /**
     * @var  array  PHP error code => human readable name
     */
    public static $php_errors = array(
        E_ERROR => 'Fatal Error' ,
        E_USER_ERROR => 'User Error' ,
        E_PARSE => 'Parse Error' ,
        E_WARNING => 'Warning' ,
        E_USER_WARNING => 'User Warning' ,
        E_STRICT => 'Strict' ,
        E_NOTICE => 'Notice' ,
        E_RECOVERABLE_ERROR => 'Recoverable Error'
    );

    /**
     * @var  boolean  command line environment?
     */
    public static $is_cli = FALSE;

    /**
     * @var  boolean  Windows environment?
     */
    public static $is_windows = FALSE;

    /**
     * @var  boolean  magic quotes enabled?
     */
    public static $magic_quotes = FALSE;

    /**
     * @var  string  character set of input and output
     */
    public static $charset = 'utf-8';

    /**
     * @var  string  base URL to the application
     */
    public static $base_url = '/';

    /**
     * @var  string  application index file
     */
    public static $index_file = 'index.php';

    /**
     * @var  string  cache directory
     */
    public static $cache_dir;

    /**
     * @var  boolean  enabling internal caching?
     */
    public static $caching = FALSE;

    /**
     * @var  boolean  enable query strings?
     */
    public static $enable_query_strings = TRUE;

    /**
     * @var  boolean  enable error handling?
     */
    public static $errors = FALSE;

    /**
     * @var  strin
     */
    public static $error_page = NULL;

    /**
     * @var  Config  config object
     */
    public static $config;

    // Currently active modules
    private static $_modules = array();

    // Include paths that are used to find files
    private static $_paths = array(APP_PATH , SYS_PATH);

    // File path cache
    private static $_files = array();

    // Has the file cache changed?
    private static $_files_changed = FALSE;

    /**
     * Initializes the environment:
     *
     * - Disables register_globals and magic_quotes_gpc
     * - Determines the current environment
     * - Set global settings
     * - Sanitizes GET, POST, and COOKIE variables
     * - Converts GET, POST, and COOKIE variables to the global character set
     *
     * Any of the global settings can be set here:
     *
     * - **boolean "errors"**     use internal error and exception handling?
     * - **boolean "caching"**    cache the location of files between requests?
     * - **string  "charset"**    character set used for all input and output
     * - **string  "base_url"**   set the base URL for the application
     * - **string  "index_file"** set the index.php file name
     *
     * @throws  Exception
     * @param   array   global settings
     * @return  void
     */
    public static function init (array $settings = NULL)
    {
        static $inited;
        // This function can only be run once
        if ($inited === TRUE)
            return;
            // The system is now ready
        $inited = TRUE;

        // Start an output buffer
        ob_start();

        isset($settings['enable_query_strings']) && Ko::$enable_query_strings = (bool) $settings['enable_query_strings'];

        isset($settings['errors']) && Ko::$errors = (bool) $settings['errors'];
        isset($settings['error_page']) && Ko::$error_page = (string) $settings['error_page'];
        if (self::$errors === TRUE) {
            // Enable the Ko shutdown handler, which catches E_FATAL errors.
            register_shutdown_function(array('Ko' , 'shutdown_handler'));

            // Enable Ko exception handling, adds stack traces and error source.
            set_exception_handler(array('Ko' , 'exception_handler'));

            // Enable Ko error handling, converts all PHP errors to exceptions.
            set_error_handler(array('Ko' , 'error_handler'));

        }
        if (ini_get('register_globals')) {
            // Reverse the effects of register_globals
			self::globals();
        }

        // Determine if we are running in a command line environment
        self::$is_cli = (PHP_SAPI === 'cli');

        // Determine if we are running in a Windows environment
        self::$is_windows = (DIRECTORY_SEPARATOR === '\\');

        isset($settings['caching']) && self::$caching = (bool) $settings['caching'];

        if (self::$caching === TRUE) {
            // Use the default cache directory
            self::$cache_dir = DATA_PATH . 'cache';
            self::$_files = self::cache('Ko::findFile()');
        }
        // Setup page charset
        isset($settings['charset']) && self::$charset = strtolower($settings['charset']);

        // Setup page base_url
        isset($settings['base_url']) && self::$base_url = rtrim($settings['base_url'], '/') . '/';

        // Setup page index_file
        isset($settings['index_file']) && self::$index_file = trim($settings['index_file'], '/');

        // Determine if the extremely evil magic quotes are enabled
        self::$magic_quotes = (bool) get_magic_quotes_gpc();

        // Sanitize all request variables
        $_GET = self::sanitize($_GET);
        $_POST = self::sanitize($_POST);
        $_COOKIE = self::sanitize($_COOKIE);

        // Load the config
        self::$config = Config::instance();
    }

	/**
	 * Reverts the effects of the `register_globals` PHP setting by unsetting
	 * all global varibles except for the default super globals (GPCS, etc).
	 *
	 *     if (ini_get('register_globals'))
	 *     {
	 *         Kohana::globals();
	 *     }
	 *
	 * @return  void
	 */
	public static function globals()
	{
		if (isset($_REQUEST['GLOBALS']) OR isset($_FILES['GLOBALS'])) {
			// Prevent malicious GLOBALS overload attack
			echo "Global variable overload attack detected! Request aborted.\n";

			// Exit with an error status
			exit(1);
		}

		// Get the variable names of all globals
		$global_variables = array_keys($GLOBALS);

		// Remove the standard global variables from the list
		$global_variables = array_diff($global_variables, array(
			'_COOKIE',
			'_ENV',
			'_GET',
			'_FILES',
			'_POST',
			'_REQUEST',
			'_SERVER',
			'_SESSION',
			'GLOBALS',
		));

		foreach ($global_variables as $name) {
			// Unset the global variable, effectively disabling register_globals
			unset($GLOBALS[$name]);
		}
	}

    /**
     * Recursively sanitizes an input variable:
     *
     * - Strips slashes if magic quotes are enabled
     * - Normalizes all newlines to LF
     *
     * @param   mixed  any variable
     * @return  mixed  sanitized variable
     */
    public static function sanitize ($value)
    {
        if (is_array($value) || is_object($value)) {
            foreach ($value as $key => $val) {
                // Recursively clean each value
                $value[$key] = self::sanitize($val);
            }
        } elseif (is_string($value)) {
            if (self::$magic_quotes === TRUE) {
                $value = stripslashes($value);
            }
            if (strpos($value, "\r") !== FALSE) {
                $value = str_replace(array("\r\n" , "\r"), "\n", $value);
            }
        }
        return $value;
    }

    /**
     * Provides auto-loading support of Ko classes, as well as transparent
     * extension of classes that have a Controller / Model suffix.
     *
     * Class names are converted to file names by making the class name
     * lowercase and converting underscores to slashes:
     *
     *     // Loads classes/my/class/name.php
     *     Ko::autoload('My_Class_Name');
     *
     * @param   string   class name
     * @return  boolean
     */
    public static function autoload ($class)
    {
        if (class_exists($class, FALSE))
            return TRUE;
        if ($class !== 'Controller' && substr($class, -10) === 'Controller') {
            $type = 'controllers';
            $file = strtolower(substr($class, 0, -10));
        } elseif ($class !== 'Model' && substr($class, -5) === 'Model') {
            $type = 'models';
            $file = strtolower(substr($class, 0, -5));
        } elseif ($class !== 'Service' && substr($class, -7) === 'Service') {
            $type = 'services';
            $file = strtolower(substr($class, 0, -7));
        } else {
            $type = '.';
            $file = strtolower($class);
        }
        if (($path = self::findFile($type, $file)) !== false) {
            require_once $path;
            return TRUE;
        }
        return FALSE;
    }

    /**
     * Changes the currently enabled modules. Module paths may be relative
     * or absolute, but must point to a directory:
     *
     *     self::modules(array('modules/foo', MODPATH.'bar'));
     *
     * @param   array  list of module paths
     * @return  array  enabled modules
     */
    public static function modules(array $modules = NULL)
    {
        if ($modules === NULL)
            return self::$_modules;

        // Start a new list of include paths, APPPATH first
        $paths = array(APP_PATH);
        foreach ($modules as $name => &$path) {
        	$path = MOD_PATH . $path;
            if (is_dir($path)) {
                // Add the module to include paths
                $paths[] = $modules[$name] = $path;
            } else {
                // This module is invalid, remove it
                unset($modules[$name]);
            }
        }

        // Finish the include paths by adding SYS_PATH
        $paths[] = SYS_PATH;

        // Set the new include paths
        self::$_paths = $paths;

        // Set the current module list
        self::$_modules = $modules;
        foreach (self::$_modules as $path) {
            $init = $path . DIRECTORY_SEPARATOR . 'init.php';
            if (is_file($init)) {
                require_once $init;
            }
        }

        return self::$_modules;
    }

    /**
     * Finds the path of a file by directory, filename, and extension.
     * If no extension is given, the default extension will be used.
     *
     * When searching the "config" directory, an array of files
     * will be returned. These files will return arrays which must be
     * merged together.
     *
     *     // Returns an absolute path to views/template.php
     *     Ko::findFile('views', 'template');
     *
     *     // Returns an absolute path to media/css/style.css
     *     Ko::findFile('media', 'css/style', 'css');
     *
     *     // Returns an array of all the "mimes" configuration file
     *     Ko::findFile('config', 'mimes');
     *
     * @param   string   directory name (views, extensions, etc.)
     * @param   string   filename with subdirectory
     * @param   string   extension to search for
     * @return  array    file list from the "config" or "messages" directories
     * @return  string   single file path
     */
    public static function findFile ($dir, $file, $ext = 'php')
    {
        $path = $dir . DIRECTORY_SEPARATOR . str_replace('_', DIRECTORY_SEPARATOR, $file) . '.' .$ext;
        if (self::$caching === TRUE and isset(self::$_files[$path])) {
            return self::$_files[$path];
        }
        if ($dir === 'config' OR $dir === 'i18n' OR $dir === 'messages') {
        	// Array of files that have been found
        	$found = array();
        	if (is_file(DATA_PATH . $path)) {
        		// This path has a file, add it to the list
        		$found[] = DATA_PATH . $path;
        	}
        } else {
        	// The file has not been found yet
        	$found = FALSE;
        	foreach (self::$_paths as $dir) {
        		if (is_file($dir . $path)) {
        			// A path has been found
        			$found = $dir . $path;
        			break;
        		}
        	}
        }
        if (self::$caching === TRUE) {
            // Add the path to the cache
            self::$_files[$path] = $found;
            // Files have been changed
            self::$_files_changed = TRUE;
        }
        return $found;
    }

    /**
     * Loads a file within a totally empty scope and returns the output:
     *
     *     $foo = Ko::load('foo.php');
     *
     * @param   string
     * @return  mixed
     */
    public static function load ($file)
    {
        return include $file;
    }

    /**
     * Creates a new configuration object for the requested group.
     *
     * @param   string   group name
     * @return  Config
     */
    public static function config ($group)
    {
        static $config;
        if (strpos($group, '.') !== FALSE) {
            // Split the config group and path
            list ($group, $path) = explode('.', $group, 2);
        }
        if (! isset($config[$group])) {
            // Load the config group into the cache
            $config[$group] = self::$config->load($group);
        }
        if (isset($path)) {
            return Arr::path($config[$group], $path);
        } else {
            return $config[$group];
        }
    }

    /**
     * Provides simple file-based caching for strings and arrays:
     *
     *     // Set the "foo" cache
     *     Ko::cache('foo', 'hello, world');
     *
     *     // Get the "foo" cache
     *     $foo = Ko::cache('foo');
     *
     * All caches are stored as PHP code, generated with [var_export][ref-var].
     * Caching objects may not work as expected. Storing references or an
     * object or array that has recursion will cause an E_FATAL.
     *
     * [ref-var]: http://php.net/var_export
     *
     * @throws  Exception
     * @param   string   name of the cache
     * @param   mixed    data to cache
     * @param   integer  number of seconds the cache is valid for
     * @return  mixed    for getting
     * @return  boolean  for setting
     */
    public static function cache ($name, $data = NULL, $lifetime = 3600)
    {
        // Cache file is a hash of the name
        $file = sha1($name) . '.txt';
        // Cache directories are split by keys to prevent filesystem overload
        $dir = self::$cache_dir . DIRECTORY_SEPARATOR . "{$file[0]}{$file[1]}" . DIRECTORY_SEPARATOR;
        try {
            if ($data === NULL) {
                if (is_file($dir . $file)) {
                    if ((time() - filemtime($dir . $file)) < $lifetime) {
                        // Return the cache
                        return unserialize(file_get_contents($dir . $file));
                    } else {
                        // Cache has expired
                        @unlink($dir . $file);
                    }
                }
                // Cache not found
                return NULL;
            }
            if (! is_dir($dir)) {
                // Create the cache directory
                mkdir($dir, 0777, TRUE);
                // Set permissions (must be manually set to fix umask issues)
                chmod($dir, 0777);
            }
            // Write the cache
            if (! is_writable($dir)) {
            	throw new KoException('Directory :dir must be writable', array(':dir' => $dir . $file));
            }
            file_put_contents($dir . $file, serialize($data));
            chmod($dir . $file, 0777);
            return true;
        } catch (Exception $e) {
            throw $e;
        }
    }

    /**
     * PHP error handler, converts all errors into ErrorExceptions. This handler
     * respects error_reporting settings.
     *
     * @throws  ErrorException
     * @return  TRUE
     */
    public static function error_handler ($code, $error, $file = NULL, $line = NULL)
    {
        if ((error_reporting() & $code) !== 0) {
            // This error is not suppressed by current error reporting settings
            // Convert the error into an ErrorException
            throw new ErrorException($error, $code, 0, $file, $line);
        }
        // Do not execute the PHP error handler
        return TRUE;
    }

    /**
     * Inline exception handler, displays the error message, source of the
     * exception, and the stack trace of the error.
     *
     * @uses    Ko::textException
     * @param   object   exception object
     * @return  boolean
     */
    public static function exception_handler (Exception $e)
    {
        try {
            // Get the exception information
            $type = get_class($e);
            $code = $e->getCode();
            $message = $e->getMessage();
            $file = $e->getFile();
            $line = $e->getLine();
            // Create a text version of the exception
            $error = self::textException($e);
            if (self::$is_cli) {
                echo "\n{$error}\n";
                return TRUE;
            }
            // Get the exception backtrace
            $trace = $e->getTrace();
            if ($e instanceof ErrorException) {
                if (isset(self::$php_errors[$code])) {
                    // Use the human-readable error name
                    $code = self::$php_errors[$code];
                }
                if (version_compare(PHP_VERSION, '5.3', '<')) {
                    // Workaround for a bug in ErrorException::getTrace() that exists in
                    // all PHP 5.2 versions. @see http://bugs.php.net/bug.php?id=45895
                    for ($i = count($trace) - 1; $i > 0; -- $i) {
                        if (isset($trace[$i - 1]['args'])) {
                            // Re-position the args
                            $trace[$i]['args'] = $trace[$i - 1]['args'];
                            // Remove the args
                            unset($trace[$i - 1]['args']);
                        }
                    }
                }
            }
            if (! headers_sent()) {
                // Make sure the proper content type is sent with a 500 status
                header('Content-Type: text/html; charset=' . self::$charset, TRUE, 500);
            }
            // Start an output buffer
            ob_start();
            // Include the exception HTML
            include self::findFile('views', 'error');
            // Display the contents of the output buffer
            echo ob_get_clean();

            return TRUE;
        } catch (Exception $e) {
            // Clean the output buffer if one exists
            ob_get_level() and ob_clean();

            // Display the exception text
            echo self::textException($e), "\n";
            // Exit with an error status
            exit(1);
        }
    }

    /**
     * Catches errors that are not caught by the error handler, such as E_PARSE.
     *
     * @uses    Ko::exception_handler
     * @return  void
     */
    public static function shutdown_handler ()
    {
        try {
            if (self::$caching === TRUE and self::$_files_changed === TRUE) {
                // Write the file path cache
                self::cache('Ko::findFile()', self::$_files);
            }
        } catch (Exception $e) {
            // Pass the exception to the handler
            self::exception_handler($e);
        }
        if ($error = error_get_last()) {
            // If an output buffer exists, clear it
            ob_get_level() and ob_clean();

            // Fake an exception for nice debugging
            self::exception_handler(new ErrorException($error['message'], $error['type'], 0, $error['file'], $error['line']));
            // Shutdown now to avoid a "death loop"
            exit(1);
        }
    }

    /**
     * Get a single line of text representing the exception:
     *
     * Error [ Code ]: Message ~ File [ Line ]
     *
     * @param   object  Exception
     * @return  string
     */
    public static function textException (Exception $e)
    {
        return sprintf('%s [ %s ]: %s ~ %s [ %d ]', get_class($e), $e->getCode(), strip_tags($e->getMessage()), self::debug_path($e->getFile()), $e->getLine());
    }

    /**
     * Returns an HTML string of debugging information about any number of
     * variables, each wrapped in a "pre" tag:
     *
     *     // Displays the type and value of each variable
     *     echo Ko::debug($foo, $bar, $baz);
     *
     * @param   mixed   variable to debug
     * @param   ...
     * @return  string
     */
    public static function debug ()
    {
        if (func_num_args() === 0)
            return;
            // Get all passed variables
        $variables = func_get_args();
        $output = array();
        foreach ($variables as $var) {
            $output[] = self::_dump($var, 1024);
        }
        return '<pre class="debug">' . implode("\n", $output) . '</pre>';
    }

    /**
     * Returns an HTML string of information about a single variable.
     *
     * Borrows heavily on concepts from the Debug class of [Nette](http://nettephp.com/).
     *
     * @param   mixed    variable to dump
     * @param   integer  maximum length of strings
     * @return  string
     */
    public static function dump ($value, $length = 128)
    {
        return self::_dump($value, $length);
    }

    /**
     * Helper for Ko::dump(), handles recursion in arrays and objects.
     *
     * @param   mixed    variable to dump
     * @param   integer  maximum length of strings
     * @param   integer  recursion level (internal)
     * @return  string
     */
    private static function _dump (& $var, $length = 128, $level = 0)
    {
        if ($var === NULL) {
            return '<small>NULL</small>';
        } elseif (is_bool($var)) {
            return '<small>bool</small> ' . ($var ? 'TRUE' : 'FALSE');
        } elseif (is_float($var)) {
            return '<small>float</small> ' . $var;
        } elseif (is_resource($var)) {
            if (($type = get_resource_type($var)) === 'stream' and $meta = stream_get_meta_data($var)) {
                $meta = stream_get_meta_data($var);
                if (isset($meta['uri'])) {
                    $file = $meta['uri'];
                    if (function_exists('stream_is_local')) {
                        // Only exists on PHP >= 5.2.4
                        if (stream_is_local($file)) {
                            $file = self::debug_path($file);
                        }
                    }
                    return '<small>resource</small><span>(' . $type . ')</span> ' . htmlspecialchars($file, ENT_NOQUOTES, self::$charset);
                }
            } else {
                return '<small>resource</small><span>(' . $type . ')</span>';
            }
        } elseif (is_string($var)) {
            if (strlen($var) > $length) {
                // Encode the truncated string
                $str = htmlspecialchars(mb_substr($var, 0, $length, self::$charset), ENT_NOQUOTES, self::$charset) . '&nbsp;&hellip;';
            } else {
                // Encode the string
                $str = htmlspecialchars($var, ENT_NOQUOTES, self::$charset);
            }
            return '<small>string</small><span>(' . strlen($var) . ')</span> "' . $str . '"';
        } elseif (is_array($var)) {
            $output = array();
            $space = str_repeat($s = '    ', $level);
            static $marker;
            if ($marker === NULL) {
                $marker = uniqid("\x00");
            }
            if (empty($var)) {
                // Do nothing
            } elseif (isset($var[$marker])) {
                $output[] = "(\n$space$s*RECURSION*\n$space)";
            } elseif ($level < 5) {
                $output[] = "<span>(";
                $var[$marker] = TRUE;
                foreach ($var as $key => & $val) {
                    if ($key === $marker)
                        continue;
                    if (! is_int($key)) {
                        $key = '"' . $key . '"';
                    }
                    $output[] = "$space$s$key => " . self::_dump($val, $length, $level + 1);
                }
                unset($var[$marker]);
                $output[] = "$space)</span>";
            } else {
                $output[] = "(\n$space$s...\n$space)";
            }
            return '<small>array</small><span>(' . count($var) . ')</span> ' . implode("\n", $output);
        } elseif (is_object($var)) {
            // Copy the object as an array
            $array = (array) $var;
            $output = array();
            // Indentation for this variable
            $space = str_repeat($s = '    ', $level);
            $hash = spl_object_hash($var);
            // Objects that are being dumped
            static $objects = array();
            if (empty($var)) {
                // Do nothing
            } elseif (isset($objects[$hash])) {
                $output[] = "{\n$space$s*RECURSION*\n$space}";
            } elseif ($level < 5) {
                $output[] = "<code>{";
                $objects[$hash] = TRUE;
                foreach ($array as $key => & $val) {
                    if ($key[0] === "\x00") {
                        // Determine if the access is private or protected
                        $access = '<small>' . ($key[1] === '*' ? 'protected' : 'private') . '</small>';
                        // Remove the access level from the variable name
                        $key = substr($key, strrpos($key, "\x00") + 1);
                    } else {
                        $access = '<small>public</small>';
                    }
                    $output[] = "$space$s$access $key => " . self::_dump($val, $length, $level + 1);
                }
                unset($objects[$hash]);
                $output[] = "$space}</code>";
            } else {
                // Depth too great
                $output[] = "{\n$space$s...\n$space}";
            }
            return '<small>object</small> <span>' . get_class($var) . '(' . count($array) . ')</span> ' . implode("\n", $output);
        } else {
            return '<small>' . gettype($var) . '</small> ' . htmlspecialchars(print_r($var, TRUE), ENT_NOQUOTES, self::$charset);
        }
    }

    /**
     * Removes application, system, or docroot from a filename,
     * replacing them with the plain text equivalents. Useful for debugging
     * when you want to display a shorter path.
     *
     *     // Displays SYS_PATH/core.php
     *     echo Ko::debug_path(Ko::findFile('classes', 'ko'));
     *
     * @param   string  path to debug
     * @return  string
     */
    public static function debug_path ($file)
    {
        if (strpos($file, MOD_PATH) === 0) {
            $file = 'MOD_PATH/' . substr($file, strlen(MOD_PATH));
        } elseif (strpos($file, APP_PATH) === 0) {
            $file = 'APP_PATH/' . substr($file, strlen(APP_PATH));
        } elseif (strpos($file, SYS_PATH) === 0) {
            $file = 'SYS_PATH/' . substr($file, strlen(SYS_PATH));
        } elseif (strpos($file, DOC_ROOT) === 0) {
            $file = 'DOC_ROOT/' . substr($file, strlen(DOC_ROOT));
        }
        return $file;
    }

    /**
     * Returns an HTML string, highlighting a specific line of a file, with some
     * number of lines padded above and below.
     *
     *     // Highlights the current line of the current file
     *     echo Ko::debug_source(__FILE__, __LINE__);
     *
     * @param   string   file to open
     * @param   integer  line number to highlight
     * @param   integer  number of padding lines
     * @return  string
     */
    public static function debug_source ($file, $line_number, $padding = 5)
    {
        // Open the file and set the line position
        $file = fopen($file, 'r');
        $line = 0;
        // Set the reading range
        $range = array('start' => $line_number - $padding , 'end' => $line_number + $padding);
        // Set the zero-padding amount for line numbers
        $format = '% ' . strlen($range['end']) . 'd';
        $source = '';
        while (($row = fgets($file)) !== FALSE) {
            // Increment the line number
            if (++ $line > $range['end'])
                break;
            if ($line >= $range['start']) {
                // Make the row safe for output
                $row = htmlspecialchars($row, ENT_NOQUOTES, self::$charset);
                // Trim whitespace and sanitize the row
                $row = '<span class="number">' . sprintf($format, $line) . '</span> ' . $row;
                if ($line === $line_number) {
                    // Apply highlighting to this row
                    $row = '<span class="line highlight">' . $row . '</span>';
                } else {
                    $row = '<span class="line">' . $row . '</span>';
                }
                // Add to the captured source
                $source .= $row;
            }
        }
        // Close the file
        fclose($file);
        return '<pre class="source"><code>' . $source . '</code></pre>';
    }

    /**
     * Returns an array of HTML strings that represent each step in the backtrace.
     *
     *     // Displays the entire current backtrace
     *     echo implode('<br/>', Ko::trace());
     *
     * @param   string  path to debug
     * @return  string
     */
    public static function trace (array $trace = NULL)
    {
        if ($trace === NULL) {
            // Start a new trace
            $trace = debug_backtrace();
        }
        // Non-standard function calls
        $statements = array('include' , 'include_once' , 'require' , 'require_once');
        $output = array();
        foreach ($trace as $step) {
            if (! isset($step['function'])) {
                // Invalid trace step
                continue;
            }
            if (isset($step['file']) and isset($step['line'])) {
                // Include the source of this step
                $source = self::debug_source($step['file'], $step['line']);
            }
            if (isset($step['file'])) {
                $file = $step['file'];
                if (isset($step['line'])) {
                    $line = $step['line'];
                }
            }
            // function()
            $function = $step['function'];
            if (in_array($step['function'], $statements)) {
                if (empty($step['args'])) {
                    // No arguments
                    $args = array();
                } else {
                    // Sanitize the file path
                    $args = array($step['args'][0]);
                }
            } elseif (isset($step['args'])) {
                if (isset($step['class'])) {
                    if (method_exists($step['class'], $step['function'])) {
                        $reflection = new ReflectionMethod($step['class'], $step['function']);
                    } else {
                        $reflection = new ReflectionMethod($step['class'], '__call');
                    }
                } else {
                    $reflection = new ReflectionFunction($step['function']);
                }
                // Get the function parameters
                $params = $reflection->getParameters();
                $args = array();
                foreach ($step['args'] as $i => $arg) {
                    if (isset($params[$i])) {
                        // Assign the argument by the parameter name
                        $args[$params[$i]->name] = $arg;
                    } else {
                        // Assign the argument by number
                        $args[$i] = $arg;
                    }
                }
            }
            if (isset($step['class'])) {
                // Class->method() or Class::method()
                $function = $step['class'] . $step['type'] . $step['function'];
            }
            $output[] = array('function' => $function , 'args' => isset($args) ? $args : NULL , 'file' => isset($file) ? $file : NULL , 'line' => isset($line) ? $line : NULL , 'source' => isset($source) ? $source : NULL);
            unset($function, $args, $file, $line, $source);
        }
        return $output;
    }
} // End Ko

/**
 * Ko exception class. Converts exceptions into HTML messages.
 *
 * @package    Exception
 * @author     Ko Team, Eric
 * @copyright  (c) 2008-2009 Ko Team
 * @license    http://kophp.com/license.html
 */
class KoException extends Exception
{
    /**
     * Creates a new translated exception.
     *
     * @param   string   error message
     * @param   array    translation variables
     * @return  void
     */
    public function __construct ($message, array $variables = NULL, $code = 0)
    {
        // Set the message
        $message = empty($variables) ? $message : strtr($message, $variables);
        // Pass the message to the parent
        parent::__construct($message, $code);
    }

    /**
     * Magic object-to-string method.
     *
     * @uses    Ko::textException
     * @return  string
     */
    public function __toString ()
    {
        return Ko::textException($this);
    }
} // End KoException
