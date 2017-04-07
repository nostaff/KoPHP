<?php
defined('SYS_PATH') or die('No direct access allowed.');

/**
 * Text helper class.
 *
 * $Id: text.php 83 2009-10-16 14:45:27Z eric $
 * 
 * @package    Ko
 * @author     Ko Team
 * @copyright  (c) 2007-2008 Ko Team
 * @license    http://kophp.com/license.html
 */
class Text
{

    /**
     * Alternates between two or more strings.
     *
     * @param   string  strings to alternate between
     * @return  string
     */
    public static function alternate ()
    {
        static $i;
        if (func_num_args() === 0) {
            $i = 0;
            return '';
        }
        $args = func_get_args();
        return $args[($i ++ % count($args))];
    }

    /**
     * Generates a random string of a given type and length.
     *
     * @param   string   a type of pool, or a string of characters to use as the pool
     * @param   integer  length of string to return
     * @return  string
     *
     * @tutorial  alnum     alpha-numeric characters
     * @tutorial  alpha     alphabetical characters
     * @tutorial  hexdec    hexadecimal characters, 0-9 plus a-f
     * @tutorial  numeric   digit characters, 0-9
     * @tutorial  nozero    digit characters, 1-9
     * @tutorial  distinct  clearly distinct alpha-numeric characters
     */
    public static function random ($type = 'alnum', $length = 8)
    {
        $utf8 = FALSE;
        switch ($type) {
            case 'alnum':
                $pool = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
                break;
            case 'alpha':
                $pool = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
                break;
            case 'hexdec':
                $pool = '0123456789abcdef';
                break;
            case 'numeric':
                $pool = '0123456789';
                break;
            case 'nozero':
                $pool = '123456789';
                break;
            case 'distinct':
                $pool = '2345679ACDEFHJKLMNPRSTUVWXYZ';
                break;
            default:
                $pool = (string) $type;
                break;
        }
        // Split the pool into an array of characters
        $pool = str_split($pool, 1);
        // Largest pool key
        $max = count($pool) - 1;
        $str = '';
        for ($i = 0; $i < $length; $i ++) {
            $str .= $pool[mt_rand(0, $max)];
        }
        // Make sure alnum strings contain at least one letter and one digit
        if ($type === 'alnum' and $length > 1) {
            if (ctype_alpha($str)) {
                $str[mt_rand(0, $length - 1)] = chr(mt_rand(48, 57));
            } elseif (ctype_digit($str)) {
                $str[mt_rand(0, $length - 1)] = chr(mt_rand(65, 90));
            }
        }
        return $str;
    }

    /**
     * Reduces multiple slashes in a string to single slashes.
     *
     * @param   string  string to reduce slashes of
     * @return  string
     */
    public static function reduceslashes ($str)
    {
        return preg_replace('#(?<!:)//+#', '/', $str);
    }

    /**
     * Replaces the given words with a string.
     *
     * @param   string   phrase to replace words in
     * @param   array    words to replace
     * @param   string   replacement string
     * @param   boolean  replace words across word boundries (space, period, etc)
     * @return  string
     */
    public static function censor ($str, $badwords, $replacement = '#', $replace_partial_words = TRUE)
    {
        foreach ((array) $badwords as $key => $badword) {
            $badwords[$key] = str_replace('\*', '\S*?', preg_quote((string) $badword));
        }
        $regex = '(' . implode('|', $badwords) . ')';
        if ($replace_partial_words === FALSE) {
            // Just using \b isn't sufficient when we need to replace a badword that already contains word boundaries itself
            $regex = '(?<=\b|\s|^)' . $regex . '(?=\b|\s|$)';
        }
        $regex = '!' . $regex . '!ui';
        if (mb_strlen($replacement) == 1) {
            $regex .= 'e';
            return preg_replace($regex, 'str_repeat($replacement, mb_strlen(\'$1\'))', $str);
        }
        return preg_replace($regex, $replacement, $str);
    }

    /**
     * Finds the text that is similar between a set of words.
     *
     * @param   array   words to find similar text of
     * @return  string
     */
    public static function similar (array $words)
    {
        // First word is the word to match against
        $word = current($words);
        for ($i = 0, $max = strlen($word); $i < $max; ++ $i) {
            foreach ($words as $w) {
                // Once a difference is found, break out of the loops
                if (! isset($w[$i]) or $w[$i] !== $word[$i])
                    break 2;
            }
        }
        // Return the similar text
        return substr($word, 0, $i);
    }


    /**
     * Returns human readable sizes.
     * @see  Based on original functions written by:
     * @see  Aidan Lister: http://aidanlister.com/repos/v/function.size_readable.php
     * @see  Quentin Zervaas: http://www.phpriot.com/d/code/strings/filesize-format/
     *
     * @param   integer  size in bytes
     * @param   string   a definitive unit
     * @param   string   the return string format
     * @param   boolean  whether to use SI prefixes or IEC
     * @return  string
     */
    public static function bytes ($bytes, $force_unit = NULL, $format = NULL, $si = TRUE)
    {
        // Format string
        $format = ($format === NULL) ? '%01.2f %s' : (string) $format;
        // IEC prefixes (binary)
        if ($si == FALSE or strpos($force_unit, 'i') !== FALSE) {
            $units = array('B' , 'KiB' , 'MiB' , 'GiB' , 'TiB' , 'PiB');
            $mod = 1024;
        } else {
            $units = array('B' , 'kB' , 'MB' , 'GB' , 'TB' , 'PB');
            $mod = 1000;
        }
        // Determine unit to use
        if (($power = array_search((string) $force_unit, $units)) === FALSE) {
            $power = ($bytes > 0) ? floor(log($bytes, $mod)) : 0;
        }
        return sprintf($format, $bytes / pow($mod, $power), $units[$power]);
    }

    /**
     * Prevents widow words by inserting a non-breaking space between the last two words.
     * @see  http://www.shauninman.com/archive/2006/08/22/widont_wordpress_plugin
     *
     * @param   string  string to remove widows from
     * @return  string
     */
    public static function widont ($str)
    {
        $str = rtrim($str);
        $space = strrpos($str, ' ');
        if ($space !== FALSE) {
            $str = substr($str, 0, $space) . '&nbsp;' . substr($str, $space + 1);
        }
        return $str;
    }
} // End text
