<?php
/**
 * AuthController
 *
 * @package IBugu
 * @version $Id: captcha.php 82 2009-10-16 03:54:25Z eric $
 */
class CaptchaController extends Controller
{
    
    public function before()
    {
        $this->autoRender(false);
        parent::before();
    }
    
    /**
     * The default action - show the home page
     */
    public function index ()
    {
        if ($this->request->isPost()) {
            var_dump(Captcha::valid($this->request->getParam('vcode')));
        } else {
            //echo '<p><img src="' . $this->getBaseUrl(). 'captcha/basic"></p>';
            echo '<p><img src="' . $this->getBaseUrl(). 'captcha/alpha"></p>';
            //echo '<p><img src="' . $this->getBaseUrl(). 'captcha/black"></p>';
            
            echo '<form method="post"><input type="text" name="vcode"><input type="submit" value="Valid"></form>';
        }
        exit;
    }
    
    public function test()
    {
        $session = Session::instance();
        print_r($session->toArray());
    }
    
    public function basic()
    {
        $captcha = Captcha::instance('default');
        $captcha->render();
    }
    public function alpha()
    {
        $config = array (
            'style'      => 'alpha',
            'width'      => 200,
            'height'     => 60,
            'complexity' => 4,
        );        
        $captcha = Captcha::instance('alpha', $config);
        $captcha->render();
    }
    
    public function black()
    {
        $config = array (
            'style'      => 'black',
            'width'      => 100,
            'height'     => 50,
            'complexity' => 4,
        );         
        $captcha = Captcha::instance('black');
        $captcha->render();
    }
    
    public function __call($method, $args)
    {
        die($this->request->getAction());
    }    
}
