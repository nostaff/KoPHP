<?php
defined('SYS_PATH') or die('No direct script access.');

/**
 * Array and variable validation.
 *
 * @package    Ko
 * @author     Ko Team, Eric 
 * @version    $Id: validate.php 88 2010-01-12 11:15:47Z eric $
 * @copyright  (c) 2008-2009 Ko Team
 * @license    http://kophp.com/license.html
 */
class Validate
{

    /**
     * Creates a new Validation instance.
     *
     * @param   array   array to use for validation
     * @return  object
     */
    public static function factory ()
    {
        return new self();
    }

    /**
     * Checks if a field is not empty.
     *
     * @return  boolean
     */
    public static function not_empty ($value)
    {
        if (is_object($value) and $value instanceof ArrayObject) {
            // Get the array from the ArrayObject
            $value = $value->getArrayCopy();
        }
        return ($value === '0' or ! empty($value));
    }

    /**
     * Checks a field against a regular expression.
     *
     * @param   string  value
     * @param   string  regular expression to match (including delimiters)
     * @return  boolean
     */
    public static function regex ($value, $expression)
    {
        return (bool) preg_match($expression, (string) $value);
    }

    /**
     * Check an email address for correct format.
     *
     * @link  http://www.iamcal.com/publish/articles/php/parsing_email/
     * @link  http://www.w3.org/Protocols/rfc822/
     *
     * @param   string   email address
     * @param   boolean  strict RFC compatibility
     * @return  boolean
     */
    public static function email ($email, $strict = FALSE)
    {
        if ($strict === TRUE) {
            $qtext = '[^\\x0d\\x22\\x5c\\x80-\\xff]';
            $dtext = '[^\\x0d\\x5b-\\x5d\\x80-\\xff]';
            $atom = '[^\\x00-\\x20\\x22\\x28\\x29\\x2c\\x2e\\x3a-\\x3c\\x3e\\x40\\x5b-\\x5d\\x7f-\\xff]+';
            $pair = '\\x5c[\\x00-\\x7f]';
            $domain_literal = "\\x5b($dtext|$pair)*\\x5d";
            $quoted_string = "\\x22($qtext|$pair)*\\x22";
            $sub_domain = "($atom|$domain_literal)";
            $word = "($atom|$quoted_string)";
            $domain = "$sub_domain(\\x2e$sub_domain)*";
            $local_part = "$word(\\x2e$word)*";
            $expression = "/^$local_part\\x40$domain$/D";
        } else {
            $expression = '/^[-_a-z0-9\'+*$^&%=~!?{}]++(?:\.[-_a-z0-9\'+*$^&%=~!?{}]+)*+@(?:(?![-.])[-a-z0-9.]+(?<![-.])\.[a-z]{2,6}|\d{1,3}(?:\.\d{1,3}){3})(?::\d++)?$/iD';
        }
        return (bool) preg_match($expression, (string) $email);
    }

    /**
     * Validate a URL.
     *
     * @param   string   URL
     * @return  boolean
     */
    public static function url ($url)
    {
        return (bool) filter_var($url, FILTER_VALIDATE_URL, FILTER_FLAG_HOST_REQUIRED);
    }

    /**
     * Validate an IP.
     *
     * @param   string   IP address
     * @param   boolean  allow private IP networks
     * @return  boolean
     */
    public static function ip ($ip, $allow_private = TRUE)
    {
        // Do not allow reserved addresses
        $flags = FILTER_FLAG_NO_RES_RANGE;
        if ($allow_private === FALSE) {
            // Do not allow private or reserved addresses
            $flags = $flags | FILTER_FLAG_NO_PRIV_RANGE;
        }
        return (bool) filter_var($ip, FILTER_VALIDATE_IP, $flags);
    }

    /**
     * Checks if a phone number is valid.
     *
     * @param   string   phone number to check
     * @return  boolean
     */
    public static function phone ($number, $lengths = NULL)
    {
        if (! is_array($lengths)) {
            $lengths = array(7 , 10 , 11);
        }
        // Remove all non-digit characters from the number
        $number = preg_replace('/\D+/', '', $number);
        // Check if the number is within range
        return in_array(strlen($number), $lengths);
    }

    /**
     * Tests if a string is a valid date string.
     *
     * @param   string   date to check
     * @return  boolean
     */
    public static function date ($str)
    {
        return (strtotime($str) !== FALSE);
    }

    /**
     * Checks whether a string consists of digits only (no dots or dashes).
     *
     * @param   string   input string
     * @param   boolean  trigger UTF-8 compatibility
     * @return  boolean
     */
    public static function digit ($str, $utf8 = FALSE)
    {
        if ($utf8 === TRUE) {
            return (bool) preg_match('/^\pN++$/uD', $str);
        } else {
            return is_int($str) or ctype_digit($str);
        }
    }

    /**
     * Checks whether a string is a valid number (negative and decimal numbers allowed).
     *
     * Uses {@link http://www.php.net/manual/en/function.localeconv.php locale conversion}
     * to allow decimal point to be locale specific.
     *
     * @param   string   input string
     * @return  boolean
     */
    public static function numeric ($str)
    {
        // Get the decimal point for the current locale
        list ($decimal) = array_values(localeconv());
        return (bool) preg_match('/^-?[0-9' . $decimal . ']++$/D', (string) $str);
    }

    /**
     * Tests if a number is within a range.
     *
     * @param   string   number to check
     * @param   integer  minimum value
     * @param   integer  maximum value
     * @return  boolean
     */
    public static function range ($number, $min, $max)
    {
        return ($number >= $min and $number <= $max);
    }

    /**
     * Checks if a string is a proper decimal format. The format array can be
     * used to specify a decimal length, or a number and decimal length, eg:
     * array(2) would force the number to have 2 decimal places, array(4,2)
     * would force the number to have 4 digits and 2 decimal places.
     *
     * @param   string   number to check
     * @param   integer  number of decimal places
     * @return  boolean
     */
    public static function decimal ($str, $places = 2)
    {
        // Get the decimal point for the current locale
        list ($decimal) = array_values(localeconv());
        return (bool) preg_match('/^[0-9]+' . preg_quote($decimal) . '[0-9]{' . (int) $places . '}$/', $str);
    }

    /**
     * Checks if a string is a proper hexadecimal HTML color value. The validation
     * is quite flexible as it does not require an initial "#" and also allows for
     * the short notation using only three instead of six hexadecimal characters.
     *
     * @param   string   input string
     * @return  boolean
     */
    public static function color ($str)
    {
        return (bool) preg_match('/^#?+[0-9a-f]{3}(?:[0-9a-f]{3})?$/iD', $str);
    }
} // End Validation
