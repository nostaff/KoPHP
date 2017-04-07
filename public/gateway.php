<?php
/**
 * AMF Gateway.
 *
 * @author     Ko Team, Eric 
 * @version    $Id: gateway.php 103 2010-08-12 06:52:18Z eric $
 * @copyright  (c) 2007-2009 Ko Team
 * @license    http://kophp.com/license.html
 */

/**
 * Set the default time zone.
 *
 * @see  http://php.net/timezones
 */
date_default_timezone_set('Asia/Shanghai');

/**
 * Set the PHP error reporting level. If you set this in php.ini, you remove this.
 * @see  http://php.net/error_reporting
 *
 * When developing your application, it is highly recommended to enable notices
 * and strict warnings. Enable them by using: E_ALL | E_STRICT
 *
 * In a production environment, it is safe to ignore notices and strict warnings.
 * Disable them by using: E_ALL ^ E_NOTICE
 */
error_reporting( E_ALL | E_STRICT);

/**
 * Developers can modify standard configuration below.
 * 
 * @see  http://docs.kophp.com/configuration
 */
// Define entry file name.
$index_file = basename($_SERVER['PHP_SELF']);

// Define default method.
$default_controller = 'gateway';
$default_action = 'index';

// Define wether show debug message.
$show_debug_errors = TRUE;

// Enable modules. Modules are referenced by a relative or absolute path.
$used_modules = array(
    'amf' => 'amf'      // Load amf module
);

/**
 * End of standard configuration! Changing any of the code below should only be
 * attempted by those with a working knowledge of Ko internals.
 *
 * @see  http://docs.kophp.com/bootstrap
 */

// Set the full path to the docroot
define('DOC_ROOT', dirname(dirname(__FILE__)).DIRECTORY_SEPARATOR);

// Define the application path to the docroot
define('APP_PATH', DOC_ROOT . 'application' . DIRECTORY_SEPARATOR);

// Define the system path to the docroot
define('SYS_PATH', DOC_ROOT . 'libraries' . DIRECTORY_SEPARATOR);

// Define the modules path to the docroot
define('MOD_PATH', DOC_ROOT . 'modules' . DIRECTORY_SEPARATOR);

// Define the system path to the docroot
define('DATA_PATH', DOC_ROOT . 'data' . DIRECTORY_SEPARATOR);

// Bootstrap the application
require_once APP_PATH . 'bootstrap.php';                 
