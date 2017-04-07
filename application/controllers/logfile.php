<?php
defined('SYS_PATH') or die('No direct access allowed.');

/**
 * Default Ko controller. This controller should NOT be used in production.
 * It is for demonstration purposes only!
 *
 * @package    controllers
 * @author     hingguo
 * @version    $Id: index.php 14 2009-10-05 hingguo$
 * @copyright  (c) 2008-2009 CCTV.com
 * @license    http://kophp.com/license.html
 */

class LogfileController extends Controller
{
    /**
     * Log Instance Object.
     *
     * @var Log 
     */
    protected $_postLog;
    
    public function before()
    {
        $this->autoRender(FALSE);
        parent::before();
        
        /**
         * Attach the file write to logging. Multiple writers are supported.
         */
        $postLogFile = 'post-' . date('Ymdh') . '.log';
        $this->_postLog = Log::instance()->attach(new Log_File(DATA_PATH . 'logs/', $postLogFile));
    }
    
    /*
     * Default action
     */
    public function index ()
    {
        for ($i = 0; $i < 100; $i++)
            $this->_postLog->add(array("aaaasdsadsdasda", "adasdasdsadsa", "1213623213", "3213213"));
        $this->_postLog->add(array("aaaasdsadsdasada", "adasdasdasadsa", "12136232a13", "321a3213"));
        $this->_postLog->addMulti(array("aaaasdsadsdasada", "adasdasdasadsa", "12136232a13", "321a3213"));
        $this->_postLog->addMulti(array("aaaasdsadsdasada", "adasdasdasadsa", "12136232a13", "321a3213"));
    }
    
} // End Welcome Controller