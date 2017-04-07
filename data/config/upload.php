<?php 
defined('SYS_PATH') or die('No direct script access.');

/**
 * @package  Core
 *
 * Upload configuration is defined in groups which allows you to easily switch
 * between different Session settings for different forms on your website.
 *
 * Group Options:
 *  directory       - This path is relative to your index file. Absolute paths are also supported.
 *  create_dir      - Enable or disable directory creation.
 *  remove_spaces   - Remove spaces from uploaded filenames.
 */
return array(
    'directory'            => DOC_ROOT . 'uploads',
    'create_dir'           => TRUE,
    'remove_spaces'        => TRUE,
);
