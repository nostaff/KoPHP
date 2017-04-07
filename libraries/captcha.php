<?php
defined('SYS_PATH') or die('No direct access allowed.');

/**
 * Captcha library.
 *
 * $Id: captcha.php 83 2009-10-16 14:45:27Z eric $
 *
 * @package    Captcha
 * @author     Ko Team, Eric
 * @copyright  (c) 2007-2008 Ko Team
 * @license    http://kophp.com/license.html
 */
class Captcha
{

    // Captcha singleton
    protected static $_instance;

    /**
     * Style-dependent Captcha driver
     *
     * @var Captcha_Driver
     */
    protected $driver;

    // Config values
    public static $config = array ();
    
    /**
     * Singleton instance of Captcha.
     *
     * @param string $group
     * @param array $config
     * @return Captcha
     */
    public static function instance ($group = 'default', $config = NULL)
    {
        // Create the instance if it does not exist
        if (self::$_instance[$group] === NULL) {
            // Create a new instance
            self::$_instance[$group] = new self($config === NULL ? $group : $config);
        }
        return self::$_instance[$group];
    }
    
    /**
     * Constructs a new Captcha object.
     *
     * @throws  KoException
     * @param   string  config group name
     * @return  void
     */
    public function __construct ($config = NULL)
    {
        if (empty($config)) {
            $config = Ko::config('captcha.default');
        } elseif (is_string($config)) {
            if (($config = Ko::config('captcha.' . $config)) === NULL)
                throw new KoException('captcha.undefined_group :group', array(':group' => $config));
        }
        $config_default = Ko::config('captcha.default');
        // Merge the default config with the passed config
        self::$config = array_merge($config_default, $config);        
        // If using a background image, check if it exists
        if (! empty($config['background'])) {
            self::$config['background'] = str_replace('\\', '/', realpath($config['background']));
            if (! is_file(self::$config['background']))
                throw new KoException('captcha.file_not_found :background', array(':background' => self::$config['background']));
        }
        // If using any fonts, check if they exist
        if (! empty($config['fonts'])) {
            self::$config['fontpath'] = str_replace('\\', '/', realpath($config['fontpath'])) . '/';
            foreach ($config['fonts'] as $font) {
                if (! is_file(self::$config['fontpath'] . $font))
                    throw new KoException('captcha.file_not_found :font', array(':font' => self::$config['fontpath'] . $font));
            }
        }
        // Set driver name
        $driver = 'Captcha_' . ucfirst($config['style']);
        // Load the driver
        if (! Ko::autoload($driver))
            throw new KoException('core.driver_not_found :style', array(':style' =>$driver));
        $this->driver = new $driver();
        // Validate the driver
        if (! ($this->driver instanceof Captcha_Driver))
            throw new KoException('core.driver_implements :driver', array(':driver' => $config['style']));
    }

    /**
     * Validates a Captcha response and updates response counter.
     *
     * @param   string   captcha response
     * @return  boolean
     */
    public static function valid ($response)
    {
        return (bool) self::instance()->driver->valid($response);
    }

    /**
     * Returns or outputs the Captcha challenge.
     *
     * @param   boolean  TRUE to output html, e.g. <img src="#" />
     */
    public function render ()
    {
        return $this->driver->render();
    }

    /**
     * Magically outputs the Captcha challenge.
     *
     * @return  mixed
     */
    public function __toString ()
    {
        return $this->render();
    }
} // End Captcha Class