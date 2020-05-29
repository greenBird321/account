<?php
/**
 * Created by PhpStorm.
 * User: lihe
 * Date: 2019/5/30
 * Time: 3:42 PM
 */
include __DIR__ . '/../vendor/autoload.php';

class DBHelper
{
    static private $_instance; // 保存此类的是实例

    private function __construct($dsn, $username, $pass)
    {
        $this->_instance = new PDO($dsn, $username, $pass);
    }

    static public function DB($dsn, $username, $pass)
    {
        if (self::$_instance instanceof self) {
            return self::$_instance;
        }

        return self::$_instance = new PDO($dsn, $username, $pass);
    }

    public function mySort(array $source)
    {
        if (empty($source)) {
            return null;
        }

        foreach ($source as $k => $v) {

        }
    }
}
