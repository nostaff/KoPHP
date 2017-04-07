<?php
defined('SYS_PATH') or die('No direct access allowed.');

/**
 * Session library.
 *
 * $Id: session.php 109 2011-06-05 07:00:30Z eric $
 *
 * @package    Core
 * @author     Ko Team, Eric
 * @copyright  (c) 2007-2008 Ko Team
 * @license    http://kophp.com/license.html
 */
class Session
{

    // Session singleton
    protected static $instance;

    // Protected key names (cannot be set by the user)
    protected static $protect = array('session_id' , 'last_activity');

    // Configuration and driver
    protected static $config;

    /**
     *
     * @var Session_Driver
     */
    protected static $driver;

    /**
     * Singleton instance of Session.
     *
     * @return Session
     */
    public static function instance ($group = 'default')
    {
        if (self::$instance[$group] == NULL) {
            self::$instance[$group] = new self($group);
        }
        return self::$instance[$group];
    }

    /**
     * Be sure to block the use of __clone.
     */
    private function __clone ()
    {}

    /**
     * On first session instance creation, sets up the driver and creates session.
     *
     * @param string Force a specific session_id
     */
    protected function __construct ($group = 'default')
    {
        // This part only needs to be run once
        if (self::$instance[$group] === NULL) {
            // Load config
            self::$config = Ko::config('session.' . $group);
            // Configure garbage collection
            ini_set('session.gc_probability', (int) self::$config['gc_probability']);
            ini_set('session.gc_divisor', 100);
            ini_set('session.gc_maxlifetime', (self::$config['lifetime'] == 0) ? 86400 : self::$config['lifetime']);
            // Create a new session
            $this->create();
            // Write the session at shutdown
            register_shutdown_function(array($this , 'write_close'));
            // Singleton instance
            self::$instance[$group] = $this;
        }
    }

    /**
     * Get the session id.
     *
     * @return  string
     */
    public function sessionid ()
    {
        return session_id();
    }

    /**
     * Create a new session.
     *
     * @return  void
     */
    public function create ()
    {
        // Validate the session name
        if (! preg_match('~^(?=.*[a-z])[a-z0-9_]++$~iD', self::$config['name']))
            throw new KoException('The session_name, :session:, is invalid. It must contain only alphanumeric characters and underscores. Also at least one letter must be present.', array(':session:' => self::$config['name']));
        // Destroy any current sessions
        $this->destroy();
        if (self::$config['driver'] !== 'native') {
            $driver = 'Session_' . ucfirst(self::$config['driver']);
            if (! Ko::autoload($driver))
                throw new KoException('The :driver: driver for the :library: library could not be found', array(':driver:' => self::$config['driver'] , ':library:' => get_class($this)));
            self::$driver = new $driver();
            if (! (self::$driver instanceof Session_Driver))
                throw new KoException('The :driver: driver for the :library: library must implement the :interface: interface', array(':driver:' => self::$config['driver'] , ':library:' => get_class($this) , ':interface:' => 'Session_Driver'));
            session_set_save_handler(array(self::$driver , 'open'), array(self::$driver , 'close'), array(self::$driver , 'read'), array(self::$driver , 'write'), array(self::$driver , 'destroy'), array(self::$driver , 'gc'));
        }
        // Name the session, this will also be the name of the cookie
        session_name(self::$config['name']);
        
        // Set the session cookie parameters
        // session_set_cookie_params(self::$config['lifetime']);
        
        // Start the session!
        session_start();
        
        // Put session_id in the session variable
        $_SESSION['session_id'] = session_id();
        
        // Update last activity
        $_SESSION['last_activity'] = time();
        
    }

    /**
     * Regenerates the global session id.
     *
     * @return  void
     */
    public function regenerate ()
    {
        if (self::$config['driver'] === 'native') {
            session_regenerate_id(TRUE);
            $_SESSION['session_id'] = session_id();
        } else {
            $_SESSION['session_id'] = self::$driver->regenerate();
        }
    }

    public function unsetAll()
    {
        return session_destroy();
    }
    /**
     * Destroys the current session.
     *
     * @return  void
     */
    public function destroy ()
    {
        if (session_id() !== '') {
            session_destroy();
        }
    }

    /**
     * Runs the system.session_write event, then calls session_write_close.
     *
     * @return  void
     */
    public function write_close ()
    {
        session_write_close();
    }

    /**
     * Set a session variable.
     *
     * @param   string|array  key, or array of values
     * @param   mixed         value (if keys is not an array)
     * @return  void
     */
    public function set ($keys, $val = FALSE)
    {
        if (empty($keys))
            return FALSE;
        if (! is_array($keys)) {
            $keys = array($keys => $val);
        }
        foreach ($keys as $key => $val) {
            if (isset(self::$protect[$key]))
                continue;
            $_SESSION[$key] = $val;
        }
    }

    /**
     * Get a variable. Access to sub-arrays is supported with key.subkey.
     *
     * @param   string  variable key
     * @param   mixed   default value returned if variable does not exist
     * @return  mixed   Variable data if key specified, otherwise array containing all session data.
     */
    public function get ($key = FALSE, $default = FALSE)
    {
        if (empty($key))
            return $_SESSION;
        return isset($_SESSION[$key]) ? $_SESSION[$key] : $default;
    }

    /**
     * Delete one or more variables.
     *
     * @param   string  variable key(s)
     * @return  void
     */
    public function delete ($keys)
    {
        $args = func_get_args();
        foreach ($args as $key) {
            if (isset(self::$protect[$key]))
                continue;
            unset($_SESSION[$key]);
        }
    }

    public function toArray()
    {
        return $_SESSION;
    }
    /**
     * Magic: Get value.
     *
     * @param string $key
     * @return Session
     */
    public function __get ($key)
    {
        return $this->get($key);
    }

    /**
     * Magic:  Set values
     *
     *
     * @param string $key
     * @param mixed $value
     * @return Session
     */
    public function __set ($key, $value)
    {
        return $this->set($key, $value);
    }

    /**
     * Magic: Check to see if a property is set
     *
     * @param string $key
     * @return boolean
     */
    public function __isset ($key)
    {
        return isset($_SESSION[$key]);
    }
    
    /**
     * Magic: Unset a data by key.
     *
     * @param string $key
     * @return bool
     */
    public function __unset($key)
    {
        return $this->delete($key);
    }
    
} // End Session Class
