<?php

namespace MyApp\Models;


use Phalcon\Mvc\Model;
use Phalcon\DI;
use Phalcon\Db;
use Firebase\JWT\JWT;

class Support extends Model
{


    /**
     * 创建新的access_token(Json Web Token)
     * @link https://github.com/firebase/php-jwt/
     * @link https://tools.ietf.org/html/draft-ietf-oauth-json-web-token-32
     *
     * @param string $sub
     * @param string $data
     * @param int $timeout
     * @return string
     */
    public function createJWT($sub = 'JWT', $data = '', $timeout = 3600)
    {
        $timestamp = time();
        $key = DI::getDefault()->get('config')->setting->secret_key;
        $token = array(
            "sub"  => $sub,                      // 主题
            "iss"  => $_SERVER['SERVER_NAME'],   // 签发者
            "aud"  => "",                        // 接收方
            "iat"  => $timestamp,                // 签发时间
            "nbf"  => $timestamp,                // Not Before
            "exp"  => $timestamp + $timeout,     // 过期
            "data" => $data                      // 数据
        );
        $jwt = JWT::encode($token, $key);
        return $jwt;
    }


    public function verifyJWT($jwt = '')
    {
        $key = DI::getDefault()->get('config')->setting->secret_key;
        try {
            JWT::$leeway = 300; // 允许误差秒数
            $decoded = JWT::decode($jwt, $key, array('HS256'));
            return (array)$decoded;
        } catch (Exception $e) {
            return false;
        }
    }


}