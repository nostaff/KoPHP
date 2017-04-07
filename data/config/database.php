<?php
defined('SYS_PATH') OR die('No direct access allowed.');

/**
 * @package  Core
 *
 * Database configuration is defined in groups which allows you to easily switch
 *
 * Group Options:
 *  type          	- Database driver name, e.g. mysql, mysqlrw, mysqli, pdo.
 *  host         	- Database host.
 *  port            - Database port.
 *  socket      	- Database socket string.
 *  username        - Database auth username.
 *  password  		- Database auth password.
 *  persistent  	- Whether use database persistent connection.
 *  database  		- Database name.
 */
return array (
	'mysql' => array (
		'type'       => 'mysql',
		'host'       => 'localhost',
		'port'       => '3306',
		'username'   => 'demo',
		'password'   => 'demo',
		'persistent' => FALSE,
		'database'   => 'db_comment',
		'table_prefix' => '',
		'charset'      => 'utf8',
	),
	'default' => array (
	   	'type'       => 'mysqlrw',
        'read'      => array(
            array(
                'host'       => 'localhost',
                'port'       => '3306',
                'username'   => 'demo',
                'password'   => 'demo',
                'persistent' => FALSE,
            ),
            array(
                'host'       => 'localhost',
                'port'       => '3306',
                'username'   => 'demo',
                'password'   => 'demo',
                'persistent' => FALSE,
            ),
	    ),
	    'write'      => array (
            'host'       => 'localhost',
            'port'       => '3306',
            'username'   => 'demo',
            'password'   => 'demo',
            'persistent' => FALSE,
	    ),
        'database'   => 'db_comment',
        'table_prefix' => '',
        'charset'      => 'utf8',
    ),
	'alternate' => array (
		'type'       => 'pdo',
		/**
		 * The following options are available for PDO:
		 *
		 * string   dsn
		 * string   username
		 * string   password
		 * boolean  persistent
		 * string   identifier
		 */
		'dsn'        => 'mysql:host=localhost;dbname=ko',
		'username'   => 'root',
		'password'   => 'r00tdb',
		'persistent' => FALSE,
		'table_prefix' => '',
		'charset'      => 'utf8',
	),
);