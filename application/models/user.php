<?php
defined('SYS_PATH') OR die('No direct access allowed.');

/**
 * UserModel class.
 *
 * @package    Model
 * @author     Ko Team, Eric 
 * @version    $Id: user.php 71 2009-10-06 13:37:05Z eric $
 * @copyright  (c) 2008-2009 Ko Team
 * @license    http://kophp.com/license.html
 */
class UserModel extends Model 
{
    function init()
    {
        echo 'inited';
        
        
        //$this->db->connect();
        echo '<pre>';
        print_r($this->db->listTables());
        
        $query = $this->db->select('u.*')
                          ->from('user as u')
                          ->limit(5, 1)
                          ->execute()
                          ->fetchObject();
        print_r($query);
        echo 'Last Query : ' . $this->db->getLastQuery() . '<hr>';
        
        $ret = $this->db->query('select * from acl_user where name = ?', 'admin')
                        ->fetchArray();
        print_r($ret);
        echo 'Last Query : ' . $this->db->getLastQuery() . '<hr>';
                
        $rand_name = 'test' . rand();
        $data = array(
            'name' => $rand_name,
            'loginname' => $rand_name,
            'password' => md5('thisispass'),
            'enabled' => 1,
            'email' => 'test@test.com',
            'addtime' => date('Y-m-d H:i:s')
        );
        $insertid = $this->db->insert('user', $data);
        echo 'Last Query : ' . $this->db->getLastQuery() . '<hr>';
        
        echo 'Last Insert Id : ' . $this->db->lastInsertId() . '<br>';
        
        $ret = $this->db->query('select * from acl_user where userid = ?', $insertid)
                        ->fetchArray();
        print_r($ret);        
        echo 'Last Query : ' . $this->db->getLastQuery() . '<hr>';
        
        echo 'Update data use update method\n';
        $ret = $this->db->update('user', array('password'=> md5('password'), 'uptime' => date('Y-m-d H:i:s')), 'userid=' . $insertid);
        var_dump($ret);
        
        $ret = $this->db->query('select * from acl_user where userid='.$insertid)
                        ->fetchArray();
        print_r($ret);
              
        echo '</pre>';
    }
    
}
?>