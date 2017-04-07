<?php 
defined('SYS_PATH') or die('No direct script access.');

/**
 * @package  Core
 *
 * Session configuration is defined in groups which allows you to easily switch
 * between different Session settings for different forms on your website.
 *
 * Group Options:
 *  driver          - Session driver name, e.g. native, cookie, cache, database.
 *  storage         - Session storage parameter, used by drivers.
 *  name            - Session name.
 *  encryption      - Enable or disable session encryption.
 *  lifetime        - Session lifetime. Number of seconds that each session will last.
 *  gc_probability  - Percentage probability that the gc (garbage collection) routine is started.
 */
return array(
    'default'  => array(
        'driver'            => 'cache',
        'name'              => 'KOSSID',
        'lifetime'          => 3600,
        'gc_probability'    => 10,
    ),
    // session
    'storage' => array(
        'driver'   => 'memcache',
        'servers'   => array(
            array(
                'host'        => '127.0.0.1',
                'port'        => 11211,
                'persistent'  => false,
            ),
        ),
        'compression' => false,
        'lifetime'    => 3600 * 24,
    ),      
);
