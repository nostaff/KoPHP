<?php
/**
 * AMF Helloworld Service. 
 * @author Eric
 * @version $Id: HelloWorld.php 95 2010-07-04 16:15:22Z eric $
 */

class HelloWorld
{
    public function say($sMessage){
        return 'You said: ' . $sMessage;
    }

    public function ask ($ask) {
        return 'You ask: ' . $ask;
    }
    
    public function answer ($answer) {
        return 'You anser: ' . $answer;
    }
}
