<?php
defined('SYS_PATH') or die('No direct access allowed.');

/**
 * RSS and Atom feed helper.
 *
 * @package    Ko
 * @author     Ko Team, Eric 
 * @version    $Id: feed.php 88 2010-01-12 11:15:47Z eric $
 * @copyright  (c) 2007-2009 Ko Team
 * @license    http://kophp.com/license.html
 */
class Feed
{

    /**
     * Parses a remote feed into an array.
     *
     * @param   string   remote feed URL
     * @param   integer  item limit to fetch
     * @return  array
     */
    public static function parse ($feed, $limit = 0)
    {
        // Check if SimpleXML is installed
        if (! function_exists('simplexml_load_file'))
            throw new Exception('SimpleXML must be installed!');
            // Make limit an integer
        $limit = (int) $limit;
        // Disable error reporting while opening the feed
        $ER = error_reporting(0);
        // Allow loading by filename or raw XML string
        $load = (is_file($feed) or Validate::url($feed)) ? 'simplexml_load_file' : 'simplexml_load_string';
        // Load the feed
        $feed = $load($feed, 'SimpleXMLElement', LIBXML_NOCDATA);
        // Restore error reporting
        error_reporting($ER);
        // Feed could not be loaded
        if ($feed === FALSE)
            return array();
            // Detect the feed type. RSS 1.0/2.0 and Atom 1.0 are supported.
        $feed = isset($feed->channel) ? $feed->xpath('//item') : $feed->entry;
        $i = 0;
        $items = array();
        foreach ($feed as $item) {
            if ($limit > 0 and $i ++ === $limit)
                break;
            $items[] = (array) $item;
        }
        return $items;
    }

    /**
     * Creates a feed from the given parameters.
     *
     * @param   array   feed information
     * @param   array   items to add to the feed
     * @param   string  define which format to use (only rss2 is supported)
     * @param   string  define which encoding to use
     * @return  string
     */
    public static function create ($info, $items, $encoding = 'UTF-8')
    {
        $info += array('title' => 'Generated Feed' , 'link' => '' , 'generator' => 'kophp');
        $feed = '<?xml version="1.0" encoding="' . $encoding . '"?><rss version="2.0"><channel></channel></rss>';
        $feed = simplexml_load_string($feed);
        foreach ($info as $name => $value) {
            if (($name === 'pubDate' or $name === 'lastBuildDate') and (is_int($value) or ctype_digit($value))) {
                // Convert timestamps to RFC 822 formatted dates
                $value = date(DATE_RFC822, $value);
            } elseif (($name === 'link' or $name === 'docs') and strpos($value, '://') === FALSE) {
                // Convert URIs to URLs
                $value = URL::site($value, 'http');
            }
            // Add the info to the channel
            $feed->channel->addChild($name, $value);
        }
        foreach ($items as $item) {
            // Add the item to the channel
            $row = $feed->channel->addChild('item');
            foreach ($item as $name => $value) {
                if ($name === 'pubDate' and (is_int($value) or ctype_digit($value))) {
                    // Convert timestamps to RFC 822 formatted dates
                    $value = date(DATE_RFC822, $value);
                } elseif (($name === 'link' or $name === 'guid') and strpos($value, '://') === FALSE) {
                    // Convert URIs to URLs
                    $value = URL::site($value, 'http');
                }
                // Add the info to the row
                $row->addChild($name, $value);
            }
        }
        return $feed->asXML();
    }
} // End Feed
