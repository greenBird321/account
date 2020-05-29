<?php

namespace MyApp\Models;

use Phalcon\Mvc\Model;
use Phalcon\DI;
use Phalcon\Db;
use Exception;

class Logs extends Model
{

    public function initialize()
    {
        $this->setConnectionService('dbLog');
    }


    /**
     * 短信日志
     * @param string $mobile
     * @param string $code
     * @return mixed
     */
    public function smsLog($mobile = '', $code = '')
    {
        $response = DI::getDefault()->get('dbLog')->insertAsDict(
            "sms_logs",
            array(
                "mobile"      => $mobile,
                "code"        => $code,
                "status"      => 0, // 已使用1
                "create_time" => time(),
            )
        );
        return $response;
    }


    /**
     * 账号日志
     * @param array $log
     * @return bool
     */
    public function accountLog($log = [])
    {
        $data = [
            'user_id'     => isset($log['user_id']) ? $log['user_id'] : 0,
            'app_id'      => isset($log['app_id']) ? $log['app_id'] : 0,
            'uuid'        => isset($log['uuid']) ? $log['uuid'] : '',
            'adid'        => isset($log['adid']) ? $log['adid'] : '',
            'device'      => isset($log['device']) ? trim($log['device']) : '',
            'version'     => isset($log['version']) ? trim($log['version']) : '',
            'channel'     => isset($log['channel']) ? trim($log['channel']) : '',
            'ip'          => isset($log['ip']) ? $log['ip'] : '',
            'type'        => isset($log['type']) ? $log['type'] : 0,
            'create_time' => date('Y-m-d H:i:s')
        ];

        $table = "account_login_" . date('Ym');
        try {
            DI::getDefault()->get('dbLog')->insertAsDict($table, $data);
        } catch (Exception $e) {
            if (!$this->createLogTable($table)) {
                return false;
            }
            DI::getDefault()->get('dbLog')->insertAsDict($table, $data);
        }
        return true;
    }


    /**
     * 创建日志表
     * @param string $tableName
     * @return mixed
     */
    private function createLogTable($tableName = '')
    {
        $sql = <<<END
CREATE TABLE `{$tableName}` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `app_id` int(11) DEFAULT '0',
  `user_id` bigint(20) DEFAULT '0',
  `uuid` varchar(36) DEFAULT '',
  `adid` varchar(36) DEFAULT '',
  `device` varchar(32) DEFAULT '',
  `version` varchar(32) DEFAULT '',
  `channel` varchar(32) DEFAULT '',
  `ip` varchar(46) DEFAULT '',
  `type` tinyint(3) DEFAULT '0',
  `create_time` timestamp NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
END;
        try {
            DI::getDefault()->get('dbLog')->execute($sql);
        } catch (Exception $e) {
            return false;
        }
        return true;
    }

}
