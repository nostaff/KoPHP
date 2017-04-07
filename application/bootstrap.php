<?php 
defined('SYS_PATH') or die('No direct script access.');

/**
 * Bootstrap.
 *
 * @package    Cache
 * @author     Ko Team, Eric 
 * @version    $Id: bootstrap.php 105 2010-10-07 16:25:18Z eric $
 * @copyright  (c) 2007-2009 Ko Team
 * @license    http://kophp.com/license.html
 */

// Load the core Ko class
require_once SYS_PATH . 'core.php';

/**
 * Enable the Ko auto-loader.
 *
 * @see  http://php.net/spl_autoload_register
 */
spl_autoload_register(array('Ko', 'autoload'));

/**
 * Initialize Ko, setting the default options.
 *
 * The following options are available:
 *
 * - string   base_url    path, and optionally domain, of your application   NULL
 * - string   index_file  name of your index file, usually "index.php"       index.php
 * - string   charset     internal character set used for input and output   utf-8
 * - boolean  errors      enable or disable error handling                   TRUE
 * - boolean  caching     enable or disable internal caching                 FALSE
 */
Ko::init( array(
    'base_url'      => '/', 
    'errors'        => isset($show_debug_errors) && $show_debug_errors, 
    'index_file'    => isset($index_file) ? $index_file : 'index.php',
    'caching'       => TRUE
));

/**
 * Attach a file reader to config. Multiple readers are supported.
 */
Ko::$config->attach(new Config_File);

/**
 * Enable modules. Modules are referenced by a relative or absolute path.
 */
Ko::modules(isset($used_modules) ? $used_modules : array());
    
/**
 * Set the routes. Each route must have a minimum of a name, a URI and a set of
 * defaults for the URI.
 */
Route::set('default', '(<controller>(/<action>(/<__KO_VARS__>)))', array('__KO_VARS__' => '.+'))
	->defaults(array(
		'controller' => isset($default_controller) ? $default_controller : 'index',
		'action'     => isset($default_action) ? $default_action : 'index',
	));
	
/**
 * Execute the main request. A source of the URI can be passed, eg: $_SERVER['PATH_INFO'].
 * If no source is specified, the URI will be automatically detected.
 */
Request::instance()->execute()
                   ->sendHeaders()
                   ->response();
