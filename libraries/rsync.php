<?php
defined('SYS_PATH') or die('No direct script access.');

/**
 * Helper functions for working in a command-line environment.
 *
 * @package    Ko
 * @author     Ko Team, Eric 
 * @version    $Id: rsync.php 644 2009-11-11 09:06:01Z wangzh $
 * @copyright  (c) 2009 Ko Team
 * @license    http://kophp.com/license.html
 */
class Rsync
{
    
    /**
     * Rsync 文件同步函数
     *
     * @param string $rsync_file    带绝对路径的文件名
     * @param string $rsync_user    rsyncd.conf中配置的目录名
     * @param array $rsync_hosts    接收数据主机
     * @param string $rsync_model   rsync模块名
     * @param string $password_file 密码文件路径
     * @param bool $update_only     是否只同步更新的文件
     * @return boolean
     */
    public static function rsyncFile ($rsync_file, $rsync_hosts, $rsync_model, $rsync_user = NULL, $password_file = NULL, $update_only = TRUE)
    {
        if (empty($rsync_hosts)) {
            return false;
        }
        // rsync命令
        // rsync -autp --password-file=/etc/rsyncd-client.secrets /data/www/main/data/keyword/ keyword@127.0.0.1::keyword_data/
        $rsync_comm = ($update_only == TRUE) ? "/usr/bin/rsync -autp" : "/usr/bin/rsync -atp";
        $rsync_comm .= $password_file ? ' --password-file=' . $password_file : '';
        $rsync_hosts = (array) $rsync_hosts;
        foreach ($rsync_hosts as $rsync_host) {
            $rsync_host = $rsync_user ? $rsync_user . '@' . $rsync_host : $rsync_host;
            $cmd_line = "{$rsync_comm} {$rsync_file} {$rsync_host}::{$rsync_model}/";
            $result = popen($cmd_line, 'r');
            if ($result === false) {
                return false;
            } else {
                pclose($result);
            }
        }
        return true;
    }    
} // End CLI
