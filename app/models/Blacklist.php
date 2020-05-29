<?php

namespace MyApp\Models;


use Phalcon\Mvc\Model;
use Phalcon\DI;
use Phalcon\Db;

class Blacklist extends Model
{

    /**
     * 获取黑名单用户
     * @param int $user_id
     * @return bool
     */
    public function getUser($user_id = 0)
    {
        $sql = "SELECT `id`,`user_id`,`time` FROM `blacklist` WHERE `user_id`=:user_id";
        $bind = ['user_id' => $user_id];
        $query = DI::getDefault()->get('dbData')->query($sql, $bind);
        $query->setFetchMode(Db::FETCH_ASSOC);
        $result = $query->fetch();
        if (!$result || $result['time'] < time()) {
            $this->removeUser($user_id);
            return false;
        }

        $limitTime = $result['time'] - time();
        if ($limitTime > 86400) {
            $limit = intval($limitTime / 86400) . ' days';
        } elseif ($limitTime > 3600) {
            $limit = intval($limitTime / 3600) . ' hours';
        } else {
            $limit = intval($limitTime / 60) . ' minutes';
        }

        $result['msg'] = 'blacklist limit ' . $limit;
        return $result;
    }


    /**
     * 增加黑名单
     * @param int $user_id
     * @param $time
     */
    public function addUser($user_id = 0, $time)
    {
    }


    /**
     * 删除黑名单
     * @param int $user_id
     * @return bool
     */
    public function removeUser($user_id = 0)
    {
        $bind = ['user_id' => $user_id];
        $sql = "DELETE FROM `blacklist` WHERE `user_id`=:user_id";
        DI::getDefault()->get('dbData')->execute($sql, $bind);
        $sql = "UPDATE `accounts` SET `status`=1 WHERE id=:user_id";
        DI::getDefault()->get('dbData')->execute($sql, $bind);
        return true;
    }

    /**
     * 筛选黑名单列表
     * @param array $parameter
     */
    public function filterUsers($parameter = [])
    {
    }

}