<?php
defined('SYS_PATH') or die('No direct access allowed.');

/**
 * Upload class for working with the global $_FILES
 * array and Validation library.
 *
 * $Id: upload.php 109 2011-06-05 07:00:30Z eric $
 *
 * @package    Core
 * @author     Ko Team, Eric
 * @copyright  (c) 2007-2008 Ko Team
 * @license    http://kophp.com/license.html
 */
class Upload
{

    /**
     * Save an uploaded file to a new location.
     *
     * @param   mixed    name of $_FILE input or array of upload data
     * @param   string   new filename
     * @param   string   new directory
     * @param   integer  chmod mask
     * @return  string   full path to new file
     */
    public static function save ($file, $filename = NULL, $directory = NULL, $chmod = 0644)
    {
        // Load file data from FILES if not passed as array
        $file = is_array($file) ? $file : $_FILES[$file];
        if ($filename === NULL) {
            $filename = time() . $file['name'];
        }
        if (Ko::config('upload.remove_spaces') === TRUE) {
            $filename = preg_replace('/\s+/', '_', $filename);
        }
        if ($directory === NULL) {
            $directory = Ko::config('upload.directory', TRUE);
        }
        // Make sure the directory ends with a slash
        $directory = rtrim($directory, '/') . '/';
        if (! is_dir($directory) and Ko::config('upload.create_dir') === TRUE) {
            mkdir($directory, 0777, TRUE);
        }
        if (! is_writable($directory))
            throw new KoException('The upload destination folder, :dir:, does not appear to be writable.', array(':dir:' => $directory));
        if (is_uploaded_file($file['tmp_name']) and move_uploaded_file($file['tmp_name'], $filename = $directory . $filename)) {
            if ($chmod !== FALSE) {
                chmod($filename, $chmod);
            }
            return $filename;
        }
        return FALSE;
    }

    /* Validation Rules */
    /**
     * Tests if input data is valid file type, even if no upload is present.
     *
     * @param   array  $_FILES item
     * @return  bool
     */
    public static function valid ($file)
    {
        return (is_array($file) && isset($file['error']) && isset($file['name']) and isset($file['type']) && isset($file['tmp_name']) && isset($file['size']));
    }

    /**
     * Tests if input data has valid upload data.
     *
     * @param   array    $_FILES item
     * @return  bool
     */
    public static function required (array $file)
    {
        return (isset($file['tmp_name']) && isset($file['error']) && is_uploaded_file($file['tmp_name']) && (int) $file['error'] === UPLOAD_ERR_OK);
    }

    /**
     * Validation rule to test if an uploaded file is allowed by extension.
     *
     * @param   array    $_FILES item
     * @param   array    allowed file extensions
     * @return  bool
     */
    public static function type (array $file, array $allowed_types)
    {
        if ((int) $file['error'] !== UPLOAD_ERR_OK)
            return TRUE;
            // Get the default extension of the file
        $extension = strtolower(substr(strrchr($file['name'], '.'), 1));
        // Make sure there is an extension and that the extension is allowed
        return (! empty($extension) && in_array($extension, $allowed_types));
    }

    /**
     * Validation rule to test if an uploaded file is allowed by file size.
     * File sizes are defined as: SB, where S is the size (1, 15, 300, etc) and
     * B is the byte modifier: (B)ytes, (K)ilobytes, (M)egabytes, (G)igabytes.
     * Eg: to limit the size to 1MB or less, you would use "1M".
     *
     * @param   array    $_FILES item
     * @param   string   maximum file size
     * @return  bool
     */
    public static function size (array $file, $size)
    {
        if ((int) $file['error'] !== UPLOAD_ERR_OK)
            return TRUE;
            // Only one size is allowed
        $size = strtoupper(trim($size));
        if (! preg_match('/[0-9]++[BKMG]/', $size))
            return FALSE;
            // Make the size into a power of 1024
        switch (substr($size, - 1)) {
            case 'G':
                $size = intval($size) * pow(1024, 3);
                break;
            case 'M':
                $size = intval($size) * pow(1024, 2);
                break;
            case 'K':
                $size = intval($size) * pow(1024, 1);
                break;
            default:
                $size = intval($size);
                break;
        }
        // Test that the file is under or equal to the max size
        return ($file['size'] <= $size);
    }
} // End upload