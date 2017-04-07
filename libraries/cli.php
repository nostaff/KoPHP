<?php
defined('SYS_PATH') or die('No direct script access.');

/**
 * Helper functions for working in a command-line environment.
 *
 * @package    Ko
 * @author     Ko Team, Eric 
 * @version    $Id: cli.php 78 2009-10-09 04:01:57Z eric $
 * @copyright  (c) 2009 Ko Team
 * @license    http://kophp.com/license.html
 */
class CLI
{

    /**
     * Returns one or more command-line options. Options are specified using
     * standard CLI syntax:
     *
     *     php index.php --option "value"
     *
     * @param   string  option name
     * @param   ...
     * @return  array
     */
    public static function options ()
    {
        // Get all of the requested options
        $options = func_get_args();
        // Found option values
        $values = array();
        // Skip the first option, it is always the file executed
        for ($i = 1; $i < $_SERVER['argc']; $i ++) {
            if (! isset($_SERVER['argv'][$i])) {
                // No more args left
                break;
            }
            // Get the option
            $opt = $_SERVER['argv'][$i];
            if (substr($opt, 0, 2) !== '--') {
                // This is not an option argument
                continue;
            }
            // Remove the "--" prefix
            $opt = substr($opt, 2);
            if (strpos($opt, '=')) {
                // Separate the name and value
                list ($opt, $value) = explode('=', $opt);
            } else {
                $value = NULL;
            }
            if (in_array($opt, $options)) {
                // Set the given value
                $values[$opt] = $value;
            }
        }
        return $values;
    }
} // End CLI
