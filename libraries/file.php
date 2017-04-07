<?php
defined('SYS_PATH') or die('No direct access allowed.');

/**
 * File helper class.
 *
 * @package    Ko
 * @author     Ko Team, Eric
 * @version    $Id: file.php 109 2011-06-05 07:00:30Z eric $
 * @copyright  (c) 2007-2009 Ko Team
 * @license    http://kophp.com/license.html
 */
class File
{

    /**
     * Attempt to get the mime type from a file. This method is horribly
     * unreliable, due to PHP being horribly unreliable when it comes to
     * determining the mime type of a file.
     *
     * @param   string  file path
     * @return  string  mime type on success
     * @return  FALSE   on failure
     */
    public static function mime ($filename)
    {
        // Get the complete path to the file
        $filename = realpath($filename);
        // Get the extension from the filename
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        if (preg_match('/^(?:jpe?g|png|[gt]if|bmp|swf)$/', $extension)) {
            // Use getimagesize() to find the mime type on images
            $file = getimagesize($filename);
            if (isset($image['mime']))
                return $file['mime'];
        }
        if (function_exists('finfo_open')) {
            if ($file = finfo_open(FILEINFO_MIME)) {
                // Get the mime type
                $mime = finfo_file($file, $filename);
                // Close the finfo
                finfo_close($file);
            }
            return $mime;
        }
        if (ini_get('mime_magic.magicfile') and function_exists('mime_content_type')) {
            // The mime_content_type function is only useful with a magic file
            return mime_content_type($filename);
        }
        if (! empty($extension)) {
            return File::mime_by_ext($extension);
        }
        // Unable to find the mime-type
        return FALSE;
    }

    /**
     * Return the mime type of an extension.
     *
     * @param   string  extension: php, pdf, txt, etc
     * @return  string  mime type on success
     * @return  FALSE   on failure
     */
    public static function mime_by_ext ($extension)
    {
        // Load all of the mime types
        $mimes = Ko::config('mimes');
        return isset($mimes[$extension]) ? $mimes[$extension][0] : FALSE;
    }

    /**
     * Split a file into pieces matching a specific size.
     *
     * @param   string   file to be split
     * @param   string   directory to output to, defaults to the same directory as the file
     * @param   integer  size, in MB, for each piece to be
     * @return  integer  The number of pieces that were created.
     */
    public static function split ($filename, $piece_size = 10)
    {
        // Open the input file
        $file = fopen($filename, 'rb');
        // Change the piece size to bytes
        $piece_size = floor($piece_size * 1024 * 1024);
        // Write files in 8k blocks
        $block_size = 1024 * 8;
        // Total number of peices
        $peices = 0;
        while (! feof($file)) {
            // Create another piece
            $peices += 1;
            // Create a new file piece
            $piece = str_pad($peices, 3, '0', STR_PAD_LEFT);
            $piece = fopen($filename . '.' . $piece, 'wb+');
            // Number of bytes read
            $read = 0;
            do {
                // Transfer the data in blocks
                fwrite($piece, fread($file, $block_size));
                // Another block has been read
                $read += $block_size;
            } while ($read < $piece_size);
            // Close the piece
            fclose($piece);
        }
        // Close the file
        fclose($file);
        return $peices;
    }

    /**
     * Join a split file into a whole file.
     *
     * @param   string   split filename, without .000 extension
     * @param   string   output filename, if different then an the filename
     * @return  integer  The number of pieces that were joined.
     */
    public static function join ($filename)
    {
        // Open the file
        $file = fopen($filename, 'wb+');
        // Read files in 8k blocks
        $block_size = 1024 * 8;
        // Total number of peices
        $pieces = 0;
        while (is_file($piece = $filename . '.' . str_pad($pieces + 1, 3, '0', STR_PAD_LEFT))) {
            // Read another piece
            $pieces += 1;
            // Open the piece for reading
            $piece = fopen($piece, 'rb');
            while (! feof($piece)) {
                // Transfer the data in blocks
                fwrite($file, fread($piece, $block_size));
            }
            // Close the peice
            fclose($piece);
        }
        return $pieces;
    }
    
	/**
	 * Force a download of a file to the user's browser. This function is
	 * binary-safe and will work with any MIME type that Kohana is aware of.
	 *
	 * @param   string  a file path or file name
	 * @param   mixed   data to be sent if the filename does not exist
	 * @param   string  suggested filename to display in the download
	 * @return  void
	 */
	public static function download($filename = NULL, $data = NULL, $nicename = NULL)
	{
		if (empty($filename))
			return FALSE;

		if (is_file($filename)) {
			// Get the real path
			$filepath = str_replace('\\', '/', realpath($filename));

			// Set filesize
			$filesize = filesize($filepath);

			// Get filename
			$filename = substr(strrchr('/'.$filepath, '/'), 1);

			// Get extension
			$extension = strtolower(substr(strrchr($filepath, '.'), 1));
		} else {
			// Get filesize
			$filesize = strlen($data);

			// Make sure the filename does not have directory info
			$filename = substr(strrchr('/'.$filename, '/'), 1);

			// Get extension
			$extension = strtolower(substr(strrchr($filename, '.'), 1));
		}

		// Get the mime type of the file
		$mime = Ko::config('mimes.' . $extension);

		if (empty($mime)) {
			// Set a default mime if none was found
			$mime = array('application/octet-stream');
		}

		// Generate the server headers
		header('Content-Type: '.$mime[0]);
		header('Content-Disposition: attachment; filename="'.(empty($nicename) ? $filename : $nicename).'"');
		header('Content-Transfer-Encoding: binary');
		header('Content-Length: '.sprintf('%d', $filesize));

		if (isset($filepath)) {
			// Open the file
			$handle = fopen($filepath, 'rb');
			// Send the file data
			fpassthru($handle);
			// Close the file
			fclose($handle);
		} else {
			// Send the file data
			echo $data;
		}
	}
	
    final private function __construct ()
    {    // This is a static class
    }
} // End file
