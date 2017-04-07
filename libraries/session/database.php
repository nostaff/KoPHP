<?php
defined('SYS_PATH') or die('No direct access allowed.');

/**
 * Session database driver.
 *
 * $Id: database.php 109 2011-06-05 07:00:30Z eric $
 *
 * @package    Core
 * @author     Ko Team, Eric
 * @copyright  (c) 2007-2008 Ko Team
 * @license    http://kophp.com/license.html
 */
class Session_Database implements Session_Driver
{
    // TODO
    /*
	CREATE TABLE sessions
	(
		session_id VARCHAR(127) NOT NULL,
		last_activity INT(10) UNSIGNED NOT NULL,
		data TEXT NOT NULL,
		PRIMARY KEY (session_id)
	);
	*/
    // Database settings
    protected $db = 'default';

    protected $table = 'sessions';

    // Session settings
    protected $session_id;

    protected $written = FALSE;

    public function __construct ()
    {
        // Load configuration
        $config = Ko::config('session');
        if (is_array($config['database'])) {
            if (! empty($config['database']['dbname'])) {
                // Set the group name
                $this->db = $config['database']['dbname'];
            }
            if (! empty($config['database']['table'])) {
                // Set the table name
                $this->table = $config['database']['table'];
            }
        }
    }

    public function open ($path, $name)
    {
        return TRUE;
    }

    public function close ()
    {
        return TRUE;
    }

    public function read ($id)
    {
        // Load the session
        $query = db::select('data')->from($this->table)->where('session_id', '=', $id)->limit(1)->execute($this->db);
        if ($query->count() === 0) {
            // No current session
            $this->session_id = NULL;
            return '';
        }
        // Set the current session id
        $this->session_id = $id;
        // Load the data
        $data = $query->current()->data;
        return base64_decode($data);
    }

    public function write ($id, $data)
    {
        $data = array('session_id' => $id , 'last_activity' => time() , 'data' => base64_encode($data));
        if ($this->session_id === NULL) {
            // Insert a new session
            $query = db::insert($this->table, $data)->execute($this->db);
        } elseif ($id === $this->session_id) {
            // Do not update the session_id
            unset($data['session_id']);
            // Update the existing session
            $query = db::update($this->table)->set($data)->where('session_id', '=', $id)->execute($this->db);
        } else {
            // Update the session and id
            $query = db::update($this->table)->set($data)->where('session_id', '=', $this->session_id)->execute($this->db);
            // Set the new session id
            $this->session_id = $id;
        }
        return (bool) $query->count();
    }

    public function destroy ($id)
    {
        // Delete the requested session
        db::delete($this->table)->where('session_id', '=', $id)->execute($this->db);
        // Session id is no longer valid
        $this->session_id = NULL;
        return TRUE;
    }

    public function regenerate ()
    {
        // Generate a new session id
        session_regenerate_id();
        // Return new session id
        return session_id();
    }

    public function gc ($maxlifetime)
    {
        // Delete all expired sessions
        $query = db::delete($this->table)->where('last_activity', '<', time() - $maxlifetime)->execute($this->db);
        return TRUE;
    }
} // End Session Database Driver
