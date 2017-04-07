<?php
defined('SYS_PATH') or die('No direct script access.');

/**
 * File log writer.
 *
 * @package    Ko
 * @author     Ko Team, Eric
 * @version    $Id: file.php 109 2011-06-05 07:00:30Z eric $
 * @copyright  (c) 2008-2009 Ko Team
 * @license    http://kophp.com/license.html
 */
class Log_File extends Log_Writer
{

    // Directory to place log files in
    protected $_directory;

    /**
     * Creates a new file logger.
     *
     * @param   string  $directory log directory
     * @param   string  $filename log filename
     * @return  void
     */
    public function __construct ($directory, $filename = NULL)
    {
        if (! is_dir($directory)) {
            if (! mkdir($directory, 0777, TRUE) || ! is_writable($directory)) {
                throw new KoException('Directory :dir must be writable', array(':dir' => Ko::debug_path($directory)));
            }
			chmod($directory, 0777);
        }
        // Determine the directory path && filename.
        $this->_directory = realpath($directory) . '/';
        if (! is_null($filename) && ! empty($filename)) {
            $this->_filename = $filename;
        }
    }
    
    /**
     * Writes each of the messages into the log file.
     *
     * @param   array   messages
     * @return  void
     */
    public function write (array $messages)
    {
        // Set the monthly directory name
        if (is_null($this->_filename)) {
            $directory = $this->_directory . date('Y/m') . '/';
            if (! is_dir($directory)) {
                mkdir($directory, 0777, TRUE);
				chmod($directory, 0777);
            }
            $filename = $directory . date('d') . '.php';
        } else {
            $filename = $this->_directory . $this->_filename;
        }
        if (! file_exists($filename)) {
            // Create the log file
            file_put_contents($filename, Ko::FILE_SECURITY . ' ?>' . PHP_EOL);
            // Allow anyone to write to log files
            chmod($filename, 0777);
        }
        // Set the log line format
        $format = 'time --- type: body';
        foreach ($messages as $message) {
            // Write each message into the log file
            // NFS can't support FILE_LOCK
            file_put_contents($filename, PHP_EOL . strtr($format, $message), FILE_APPEND);
        }
    }
} // End Log_File