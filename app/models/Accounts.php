<?php

namespace MyApp\Models;


use Phalcon\Mvc\Model;
use Phalcon\DI;
use Phalcon\Db;
use Exception;
use Firebase\JWT\JWT;
use Xxtime\Oauth\OauthAdaptor;
use Phalcon\Config;
use Symfony\Component\Yaml\Yaml;

class Accounts extends Model
{

    private $account_id;


    public function initialize()
    {
        $this->setConnectionService('dbData');
        $this->setSource("accounts");
    }


    /**
     * oauth表名
     * @param int $app_id
     * @param string $platform
     * @return string
     */
    private function getOauthTable($app_id = 0, $platform = '')
    {
        if (isset($this->di['config']['auth']['oauthAlone'])
            && $app_id
            && in_array($app_id, explode('|', $this->di['config']['auth']['oauthAlone']))
        ) {
            $oauth_table = 'oauth_' . $platform . '_' . $app_id;
        } elseif ($app_id >= 1031019) {
            $oauth_table = 'oauth_' . $app_id;
        } else {
            $oauth_table = 'oauth_' . $platform;
        }
        return $oauth_table;
    }


    /**
     * 创建新的access_token(Json Web Token)
     * @link https://github.com/firebase/php-jwt/
     * @link https://tools.ietf.org/html/draft-ietf-oauth-json-web-token-32
     * @param array $account
     * @return string
     */
    public function createAccessToken($account = [])
    {
        $timestamp = time();
        $key = DI::getDefault()->get('config')->setting->secret_key;
        $token = array(
            "sub" => "JWT",                     // subject
            "iss" => $_SERVER['SERVER_NAME'],   // 签发者
            "aud" => "",                        // 接收方
            "iat" => $timestamp,                // 签发时间
            "nbf" => $timestamp,                // Not Before
            "exp" => $timestamp + 86400 * 30,   // 30天过期
            "open_id" => $account['open_id'],
            "name" => $account['name'],
            "gender" => $account['gender'],
            "photo" => $account['photo'],
        );
        $jwt = JWT::encode($token, $key);
        return $jwt;
    }


    /**
     * 销毁AccessToken TODO :: jwt暂不需要
     * @param string $token
     * @return mixed
     */
    public function destroyAccessToken($token = '')
    {
        return DI::getDefault()->get('dbData')->delete("oauth_access_tokens", "access_token = ?", array($token));
    }


    /**
     * 验证access_token
     * @param string $jwt
     * @return array|bool
     */
    public function verifyAccessToken($jwt = '')
    {
        $key = DI::getDefault()->get('config')->setting->secret_key;
        try {
            JWT::$leeway = 300; // 允许误差秒数
            $decoded = JWT::decode($jwt, $key, array('HS256'));
            return [
                'open_id' => $decoded->open_id,
                'name' => $decoded->name,
                'gender' => $decoded->gender,
                'photo' => $decoded->photo,
            ];
        } catch (Exception $e) {
            return false;
        }
    }


    /**
     * 获取账号信息
     * @param int $user_id
     * @return mixed
     */
    public function getAccountByUserId($user_id = 0)
    {
        $sql = "SELECT id,account,password,name,gender,status,mobile,photo,create_time,update_time FROM accounts WHERE id=:user_id";
        $bind = array('user_id' => $user_id);
        $query = DI::getDefault()->get('dbData')->query($sql, $bind);
        $query->setFetchMode(Db::FETCH_ASSOC);
        return $query->fetch();
    }


    /**
     * 获取账号信息
     * @param string $account
     * @return account array
     */
    public function getAccount($account = '')
    {
        $sql = "SELECT id,account,password,name,gender,status,mobile,photo,create_time,update_time FROM accounts WHERE account=:account";
        $bind = array('account' => $account);
        $query = DI::getDefault()->get('dbData')->query($sql, $bind);
        $query->setFetchMode(Db::FETCH_ASSOC);
        return $query->fetch();
    }


    /**
     * TODO :: 获取更多账号信息
     * @param int $user_id
     * @param bool|true $private
     * @return bool
     */
    public function getAccountMore($user_id = 0, $private = true)
    {
        $sql = "SELECT id,account,name,gender,status,mobile,photo,create_time,update_time FROM accounts WHERE id=:user_id";
        $bind = array('user_id' => $user_id);
        $query = DI::getDefault()->get('dbData')->query($sql, $bind);
        $query->setFetchMode(Db::FETCH_ASSOC);
        $account = $query->fetch();
        if (!$account) {
            return false;
        }

        // check email account
        $pattern = "/^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,})$/";
        preg_match($pattern, $account['account'], $matches);

        $user_id = intval($user_id);
        $sql = "SELECT 'mobile' platform, open_id, open_name FROM `oauth_mobile` WHERE user_id=$user_id
UNION SELECT 'facebook' platform, open_id, open_name FROM `oauth_facebook` WHERE user_id=$user_id
UNION SELECT 'google' platform,open_id, open_name FROM `oauth_google` WHERE user_id=$user_id
UNION SELECT 'gamecenter' platform, open_id, open_name FROM `oauth_gamecenter` WHERE user_id=$user_id";

        $query = DI::getDefault()->get('dbData')->query($sql);
        $query->setFetchMode(Db::FETCH_ASSOC);
        $more = $query->fetchAll();
        $more = array_column($more, null, 'platform');
        if ($matches) {
            $more['email'] = [
                'platform' => 'email',
                'open_id' => $account['account'],
                'open_name' => $account['account'],
            ];
        }

        // 屏蔽隐私信息mobile
        if ($private == true) {
            if (!empty($account['mobile'])) {
                $account['mobile'] = substr($account['mobile'], 0, 3) . '****' . substr($account['mobile'], -4);
            }

            $offset = strpos($account['account'], '@');
            if ($offset) {
                if ($offset >= 6) {
                    $account['account'] =
                        substr($account['account'], 0, 3)
                        . '***'
                        . substr($account['account'], $offset - 3, 3)
                        . substr($account['account'], $offset);
                } else {
                    $account['account'] = substr($account['account'], 0,
                            ceil($offset / 2)) . '***' . substr($account['account'], $offset);
                }
            }

            if (array_key_exists('mobile', $more)) {
                $no = $more['mobile']['open_id'];
                $more['mobile']['open_id'] = $more['mobile']['open_name'] = substr($no, 0, 3)
                    . '****'
                    . substr($no, -4);
            }
        }
        $account['more'] = $more;
        return $account;
    }


    /**
     * 修改账号信息
     * @param int $user_id
     * @param array $argv
     * @return bool
     */
    public function modifyAccount($user_id = 0, $argv = [])
    {
        if (!empty($argv['account'])) {
            preg_match("/[^0-9a-zA-Z@_\-\.]/", $argv['account'], $match);
            if ($match || strlen($argv['account']) < 6) {
                return false;
            }
            if ($this->getAccount($argv['account'])) {
                return false;
            }
            $safeArgv['account'] = $argv['account'];
        }
        if (!empty($argv['password'])) {
            if (strlen($argv['password']) < 6) {
                return false;
            }
            $safeArgv['password'] = password_hash($argv['password'], PASSWORD_DEFAULT);
        }
        if (!empty($argv['name'])) {
            $safeArgv['name'] = $argv['name'];
        }
        if (!empty($argv['photo'])) {
            $safeArgv['photo'] = $argv['photo'];
        }

        if (empty($safeArgv)) {
            return false;
        }

        try {
            return DI::getDefault()->get('dbData')->updateAsDict(
                "accounts",
                $safeArgv,
                array(
                    'conditions' => 'id = ?',
                    'bind' => array($user_id),
                    'bindTypes' => array(\PDO::PARAM_INT)
                )
            );
        } catch (Exception $e) {
            return false;
        }
    }


    /**
     * 生成账号ID
     * \Phalcon\Security\Random 方法可能由于phalcon版本低不存在
     * @return string
     */
    public function generateAccountId()
    {
        if (class_exists('\Phalcon\Security\Random')) {
            $random = new \Phalcon\Security\Random();
            $this->account_id = str_replace('-', '', $random->uuid()) . '@id';
        } else {
            mt_srand((double)microtime() * 10000);
            $charID = md5(uniqid(mt_rand(), true));
            $hyphen = chr(45); // "-"
            $uuid = substr($charID, 0, 8) . $hyphen
                . substr($charID, 8, 4) . $hyphen
                . substr($charID, 12, 4) . $hyphen
                . substr($charID, 16, 4) . $hyphen
                . substr($charID, 20, 12);
            $this->account_id = str_replace('-', '', $uuid) . '@id';
        }
        return $this->account_id;
    }


    /**
     * 创建账号
     * @param string $account
     * @param string $password
     * @return user_id
     */
    public function createAccount($account = '', $password = '')
    {
        return $this->createAccountDetail(['account' => $account, 'password' => $password]);
    }


    /**
     * 创建账号
     * @param array $data
     * @return user_id
     */
    public function createAccountDetail($data = [])
    {
        if (empty($data['account'])) {
            $data['account'] = $this->generateAccountId();
        }

        $account['account'] = $data['account'];
        $account['password'] = !empty($data['password']) ? password_hash($data['password'], PASSWORD_DEFAULT) : '';
        $account['name'] = isset($data['name']) ? $data['name'] : substr($data['account'], 0, 8);
        $account['gender'] = isset($data['gender']) ? intval($data['gender']) : 0;
        $account['mobile'] = isset($data['mobile']) ? $data['mobile'] : '';
        $account['photo'] = isset($data['photo']) ? $data['photo'] : '';
        $account['birthday'] = !empty($data['birthday']) ? date('Y-m-d', strtotime($data['birthday'])) : '0000-01-01';
        $account['create_time'] = date('Y-m-d H:i:s');
        $account = array_filter($account);

        try {
            DI::getDefault()->get('dbData')->insertAsDict("accounts", $account);
        } catch (Exception $e) {
            return false;
        }
        return DI::getDefault()->get('dbData')->lastInsertId();
    }


    /**
     * 手机SMS登录 (5分钟有效)
     * @param string $mobile
     * @param string $sms
     * @param null $password
     * @return array|bool|mixed
     */
    public function loginWithSMS($mobile = '', $sms = '', $password = null)
    {
        // 检查SMS
        if (!$this->checkSMS($mobile, $sms)) {
            return false;
        }


        // 检查用户
        $sql = "SELECT id,user_id,update_time FROM oauth_mobile WHERE open_id=:open_id";
        $bind = array('open_id' => $mobile);
        $query = DI::getDefault()->get('dbData')->query($sql, $bind);
        $query->setFetchMode(Db::FETCH_ASSOC);
        $account = $query->fetch();

        if (!$account) {
            try {
                DI::getDefault()->get('dbData')->begin();
                $user_id = $this->createAccountDetail(['mobile' => $mobile, 'password' => $password]);
                DI::getDefault()->get('dbData')->insertAsDict(
                    "oauth_mobile",
                    array(
                        "user_id" => $user_id,
                        "open_id" => $mobile,
                        "open_name" => $mobile,
                        "create_time" => date('Y-m-d H:i:s'),
                    )
                );
                DI::getDefault()->get('dbData')->commit();
            } catch (Exception $e) {
                DI::getDefault()->get('dbData')->rollback();
                return false;
            }
            $accountInfo = [
                'id' => $user_id,
                'account' => $this->account_id,
                'name' => substr($this->account_id, 0, 8),
                'gender' => '0',
                'status' => '1',
                'photo' => '',
                'is_new' => true,
            ];
        } else {
            $accountInfo = $this->getAccountByUserId($account['user_id']);
        }


        // 账号信息
        return $accountInfo;
    }


    /**
     * 手机号密码登录
     * @param string $account
     * @return bool|mixed
     */
    public function loginWithMobile($account = '')
    {
        $sql = "SELECT id,user_id,update_time FROM oauth_mobile WHERE open_id=:open_id";
        $bind = array('open_id' => $account);
        $query = DI::getDefault()->get('dbData')->query($sql, $bind);
        $query->setFetchMode(Db::FETCH_ASSOC);
        $account = $query->fetch();
        if (!$account) {
            return false;
        }

        $accountInfo = $this->getAccountByUserId($account['user_id']);
        return $accountInfo;
    }


    /**
     * 返回账号信息数组(仅user_id字段)
     * @param string $app_id
     * @param string $platform
     * @param string $open_id
     * @param string $open_name
     * @param string $access_token
     * @param string $refresh_token
     * @param array $userData
     * @return array|bool|mixed
     */
    public function getAccountByOauth(
        $app_id = '',
        $platform = '',
        $open_id = '',
        $open_name = '',
        $access_token = '',
        $refresh_token = '',
        $userData = [],
        $channel_id = ''
    )
    {
        if (!$platform || !$open_id) {
            return false;
        }
        $table = $this->getOauthTable($app_id, $platform);
        if ($app_id >= 1031019) {
            if ($channel_id) {
                $platform = $channel_id;
            }
            $sql = "SELECT id,user_id,update_time FROM {$table} WHERE open_id=:open_id AND platform=:platform";
            $bind = array('open_id' => $open_id, 'platform' => $platform);
        } else {
            $sql = "SELECT id,user_id,update_time FROM {$table} WHERE open_id=:open_id";
            $bind = array('open_id' => $open_id);
        }
        $query = DI::getDefault()->get('dbData')->query($sql, $bind);
        $query->setFetchMode(Db::FETCH_ASSOC);
        $result = $query->fetch();
        if (!$result) {
            // 创建
            $user_id = $this->createAccountByOauth(
                $app_id,
                $platform,
                $open_id,
                $open_name,
                $access_token,
                $refresh_token,
                $userData
            );
            if (!$user_id) {
                return false;
            }
            $accountInfo = [
                'id' => $user_id,
                'account' => $this->account_id,
                'name' => isset($open_name) ? $open_name : substr($this->account_id, 0, 8),
                'gender' => isset($userData['gender']) ? intval($userData['gender']) : '0',
                'status' => '1',
                'photo' => isset($userData['photo']) ? $userData['photo'] : '',
                'is_new' => true,
            ];
        } else {
            // 更新
            DI::getDefault()->get('dbData')->updateAsDict(
                $table,
                array(
                    "open_name" => $open_name,
                    "access_token" => $access_token,
                    "refresh_token" => $refresh_token
                ),
                array(
                    'conditions' => 'id = ?',
                    'bind' => array($result['id']),
                    'bindTypes' => array(\PDO::PARAM_INT)
                )
            );
            // 根据社交信息自动更新(昵称,头像)
            if (!empty(DI::getDefault()->get('config')->setting->timelyUpdate)) {
                DI::getDefault()->get('dbData')->updateAsDict(
                    "accounts",
                    array(
                        "name" => $open_name,
                        "photo" => isset($userData['photo']) ? $userData['photo'] : ''
                    ),
                    array(
                        'conditions' => 'id = ?',
                        'bind' => array($result['user_id']),
                        'bindTypes' => array(\PDO::PARAM_INT)
                    )
                );
            }


            $accountInfo = $this->getAccountByUserId($result['user_id']);
        }
        return $accountInfo;
    }


    /**
     * 创建 OauthUser
     * @param string $app_id
     * @param string $platform
     * @param string $open_id
     * @param string $open_name
     * @param string $access_token
     * @param string $refresh_token
     * @param string $data
     * @return bool|user_id
     */
    private function createAccountByOauth(
        $app_id = '',
        $platform = '',
        $open_id = '',
        $open_name = '',
        $access_token = '',
        $refresh_token = '',
        $data = ''
    )
    {
        $account = [
            'account' => $this->generateAccountId(),
            "name" => $open_name,
        ];
        if ($data) {
            $data = array_merge($account, $data);
        } else {
            $data = $account;
        }

        // 事务
        $table = $this->getOauthTable($app_id, $platform);
        try {
            DI::getDefault()->get('dbData')->begin();
            $user_id = $this->createAccountDetail($data);
            DI::getDefault()->get('dbData')->insertAsDict(
                $table,
                array(
                    "user_id" => $user_id,
                    "platform" => $platform,
                    "open_id" => $open_id,
                    "open_name" => $open_name,
                    "access_token" => $access_token,
                    "refresh_token" => $refresh_token,
                    "create_time" => date('Y-m-d H:i:s'),
                )
            );
            DI::getDefault()->get('dbData')->commit();
        } catch (Exception $e) {
            DI::getDefault()->get('dbData')->rollback();
            return false;
        }

        return $user_id;
    }


    /**
     * 检查SMS
     * @param string $mobile
     * @param string $sms
     * @return bool
     */
    public function checkSMS($mobile = '', $sms = '')
    {
        $sql = "SELECT id,code,status,create_time FROM sms_logs WHERE status=0 AND mobile=:mobile AND create_time>:timestamp ORDER BY id DESC LIMIT 1";
        $bind = array('mobile' => $mobile, 'timestamp' => time() - 300);
        $query = DI::getDefault()->get('dbLog')->query($sql, $bind);
        $query->setFetchMode(Db::FETCH_ASSOC);
        $response = $query->fetch();
        if (!$response) {
            return false;
        }
        if ($response['code'] != $sms) {
            return false;
        }

        // 更新SMS使用状态
        $sql = "UPDATE sms_logs SET status=1 WHERE id=:id";
        $bind = array('id' => $response['id']);
        DI::getDefault()->get('dbLog')->execute($sql, $bind);
        return true;
    }


    public function bindEmail($user_id = 0, $email = '', $password = '')
    {
        // get user info
        $sql = "SELECT id,account FROM accounts WHERE id=:id";
        $bind = array('id' => $user_id);
        $query = DI::getDefault()->get('dbData')->query($sql, $bind);
        $query->setFetchMode(Db::FETCH_ASSOC);
        $userInfo = $query->fetch();

        // 已处理
        if ($userInfo['account'] == $email) {
            return true;
        }

        // 已绑定邮箱则返回false
        $p = "/^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,})$/";
        if (preg_match($p, $userInfo['account'])) {
            return false;
        }

        // check account
        $sql = "SELECT id FROM accounts WHERE account=:account";
        $bind = array('account' => $email);
        $query = DI::getDefault()->get('dbData')->query($sql, $bind);
        $query->setFetchMode(Db::FETCH_ASSOC);
        $account = $query->fetch();
        if ($account) {
            return false;
        }


        try {
            $hash_pass = password_hash($password, PASSWORD_DEFAULT);
            $sql = "UPDATE accounts SET account='{$email}',password='{$hash_pass}' WHERE id=$user_id";
            DI::getDefault()->get('dbData')->execute($sql, $bind);
            return true;
        } catch (Exception $e) {
        }
        return false;
    }


    /**
     * 绑定账号
     * @param int $app_id
     * @param int $user_id
     * @param string $platform
     * @param int $open_id
     * @param string $open_name
     * @return array
     */
    public function bindAccount($app_id = 0, $user_id = 0, $platform = 'mobile', $open_id = 0, $open_name = '')
    {
        if (!$user_id) {
            return [false, 'user error'];
        }
        $table = $this->getOauthTable($app_id, $platform);

        // 检查
        $sql = "SELECT id,user_id,open_id FROM $table WHERE open_id=:open_id";
        $bind = array('open_id' => $open_id);
        $query = DI::getDefault()->get('dbData')->query($sql, $bind);
        $query->setFetchMode(Db::FETCH_ASSOC);
        $account = $query->fetch();
        if ($account) {
            return [false, 'the ' . $platform . ' has been used'];
        }

        // 绑定
        try {
            DI::getDefault()->get('dbData')->begin();
            $sql = "DELETE FROM `{$table}` WHERE `user_id`=?";
            DI::getDefault()->get('dbData')->execute($sql, array($user_id));
            DI::getDefault()->get('dbData')->insertAsDict(
                $table,
                array(
                    "user_id" => $user_id,
                    "open_id" => $open_id,
                    "open_name" => $open_name,
                    "create_time" => date('Y-m-d H:i:s'),
                )
            );
            DI::getDefault()->get('dbData')->commit();
        } catch (Exception $e) {
            DI::getDefault()->get('dbData')->rollback();
            return [false, 'bind failed'];
        }
        if ($platform == 'mobile') {
            $update['mobile'] = $open_id;
        }

        // 更新
        $update['update_time'] = date('Y-m-d H:i:s');
        try {
            DI::getDefault()->get('dbData')->updateAsDict(
                "accounts",
                $update,
                array(
                    'conditions' => 'id = ?',
                    'bind' => array($user_id),
                    'bindTypes' => array(\PDO::PARAM_INT)
                )
            );
        } catch (Exception $e) {
        }

        return [true, 'success'];
    }


    public function verifyClientToken($appId = '', $provider = '', $id = '', $token = '')
    {
        $config = new Config(Yaml::parse(file_get_contents(APP_DIR . '/config/auth.yml')));
        if (!isset($config->$provider)) {
            return false;
        }
        if (!isset($config->$provider->$appId)) {
            return false;
        }

        try {
            $oauth = new OauthAdaptor($provider, (array)$config->$provider->$appId);
            $account = $oauth->verify($id, $token);
            return $account;
        } catch (Exception $e) {
            return false;
        }
    }


    public function resetPassword($account = '', $pass = '')
    {
        $argv['password'] = password_hash($pass, PASSWORD_DEFAULT);
        try {
            return DI::getDefault()->get('dbData')->updateAsDict(
                "accounts",
                $argv,
                array(
                    'conditions' => 'account = ?',
                    'bind' => array($account),
                    'bindTypes' => array(\PDO::PARAM_INT)
                )
            );
        } catch (Exception $e) {
            return false;
        }
    }

}