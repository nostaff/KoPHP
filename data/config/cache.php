<?php defined('SYS_PATH') or die('No direct script access.');

return array (

	'default'	=> array(
		'driver'   => 'file',
		'path'     => DATA_PATH. 'cache',
		'lifetime'    => 3600 * 24,
	),
/*	
	'xcache'	=> array(
		'driver'   => 'xcache',
        'auth_uset' => 'ko',
		'auth_pass' => 'ko',
	),
	
	'memcache'	=> array(
		'driver'   => 'memcache',
        'servers'	=> array(
			array(
				'host' 		  => '127.0.0.1',
				'port' 		  => 11211,
				'persistent'  => false,
			),
		),
		'compression' => false,
	),
*/
	// 封杀
	'mc_banned'	=> array(
		'driver'   => 'memcache',
        'servers'	=> array(
			array(
				'host' 		  => '127.0.0.1',
				'port' 		  => 11211,
				'persistent'  => false,
			),
		),
		'compression' => false,
		'lifetime'    => 3600 * 24,
	),
	
	//频道cache
	'mc_channel' => array(
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
	
	// 评论
	'mc_comment' => array(
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
    
    // 用户信息
    'mc_member' => array(
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
	
	'starling'	=> array(
		'driver'   => 'memcache',
        'servers'	=> array(
			array(
				'host' 		  => '127.0.0.1',
				'port' 		  => 22122,
				'persistent'  => false,
			),
		),
		'compression' => false,
		'lifetime'    => 3600 * 24,
	),
);
