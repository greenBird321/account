<?php


namespace MyApp\Controllers;


use MyApp\Models\Accounts;
use MyApp\Models\Blacklist;
use MyApp\Models\Logs;
use MyApp\Models\Support;
use MyApp\Plugins\Mail;
use Overtrue\Socialite\SocialiteManager;
use Phalcon\Mvc\Controller;
use Phalcon\Filter;
use Phalcon\Config;
use Symfony\Component\Yaml\Yaml;
use Exception;
use Phalcon\Logger\Adapter\File as FileLogger;
use Phalcon\Logger;

class PublicController extends ControllerBase
{


    private $accountModel;


    public function initialize()
    {
        $this->accountModel = new Accounts();


        // record request
        if ($this->config->setting->request_log) {
            if (isset($_REQUEST['_url'])) {
                $_url = $_REQUEST['_url'];
                unset($_REQUEST['_url']);
            }
            else {
                $_url = '/';
            }
            $log = empty($_REQUEST) ? $_url : ($_url . '?' . urldecode(http_build_query($_REQUEST)));
            $logger = new FileLogger(APP_DIR . '/logs/' . date("Ym") . '.log');
            $logger->log($log, Logger::INFO);
        }
    }


    /**
     * https://docs.phalconphp.com/zh/latest/api/Phalcon_Http_Response.html
     */
    public function indexAction()
    {
        $this->response->setStatusCode(403, "not allowed")->setContent("not allowed")->send();
    }


    /**
     * 账号登录
     *
     * 账号密码(可以手机号)
     * 客户端模式
     * 授权码模式
     * 手机SMS登录
     */
    public function access_tokenAction()
    {
        $app_id = $this->request->get('app_id', ['alphanum', 'trim']);
        $platform = strtolower($this->request->get('platform', 'alphanum'));
        $username = $this->request->get('username', 'string');
        $mobile = $this->request->get('mobile', 'int');


        // oauth 登陆参数
        $code = $this->request->get('code');
        $access_token = $this->request->get('access_token');


        /**
         *
         *
         * 账号密码模式
         */
        if (!$platform && $username) {

            // 检查密码
            $password = $this->request->get('password', 'trim');
            if (!$password) {
                $this->response->setJsonContent([
                    'code' => 1,
                    'msg'  => _('invalid password')
                ], JSON_UNESCAPED_UNICODE)->send();
                exit();
            }


            // 判断账号类型
            if (preg_match("/^1[34578]{1}\d{9}$/", $username)) {
                $account = $this->accountModel->loginWithMobile($username);
            }
            else {
                $account = $this->accountModel->getAccount($username);
            }


            if (!$account) {
                $this->response->setJsonContent([
                    'code' => 1,
                    'msg'  => _('account is not exist')
                ], JSON_UNESCAPED_UNICODE)->send();
                exit();
            }
            if (!password_verify($password, $account['password'])) {
                $this->response->setJsonContent([
                    'code' => 1,
                    'msg'  => _('invalid password')
                ], JSON_UNESCAPED_UNICODE)->send();
                exit();
            }
        }
        /**
         *
         *
         * 客户端模式 与 授权码模式
         */
        elseif ($code || $access_token || ($platform == 'gamecenter')) {
            $code = empty($code) ? $access_token : $code;

            // 微信需要code换取access_token和 open_id
            if ($platform == 'weixin') {
                // 配置文件
                $this->config->auth = new Config(Yaml::parse(file_get_contents(APP_DIR . '/config/auth.yml')));

                $config = $this->config->auth;
                if (isset($config->$platform->$app_id)) {
                    $config->$platform = $config->$platform->$app_id;
                }


                // @link https://github.com/overtrue/socialite
                try {
                    if ($platform == 'weixin') {
                        $config->wechat = &$config->weixin;
                        $handle = 'wechat';
                    }
                    else {
                        $handle = &$platform;
                    }
                    $auth2 = new SocialiteManager($config->toArray());
                    $response = $auth2->driver($handle)->user();

                    $original = $response->getOriginal(); // 原始信息
                    $user['open_id'] = isset($original['unionid']) ? $original['unionid'] : $response->getId();
                    $user['open_name'] = $response->getNickname();
                    $user['gender'] = $original['sex'];
                    $user['photo'] = $response->getAvatar();
                    $user['birthday'] = null;
                    $user['mobile'] = null;
                    $user['email'] = null;
                    $wxAccessToken = $response->getToken()->access_token;
                    $wxRefreshToken = $response->getToken()->refresh_token;
                } catch (Exception $e) {
                    $this->response->setJsonContent([
                        'code' => 1,
                        'msg'  => $e->getMessage()
                    ], JSON_UNESCAPED_UNICODE)->send();
                    exit();
                }

                $account = $this->accountModel->getAccountByOauth($app_id, $platform, $user['open_id'],
                    $user['open_name'],
                    $wxAccessToken, $wxRefreshToken, $user);
            }


            // 其他客户端模式 facebook google accountKit gameCenter
            else {
                $open_id = $this->request->get('open_id', 'string');
                $open_name = $this->request->get('open_name', 'string', strtolower(substr($open_id, 0, 8)));

                // 可选字段
                $data['gender'] = $this->request->get('gender', 'int');
                $data['mobile'] = $this->request->get('mobile', 'alphanum');
                $data['email'] = $this->request->get('email');
                $data['mobile'] = $this->request->get('mobile');
                $data['birthday'] = $this->request->get('birthday');
                $data['access_token'] = $code;
                $data['refresh_token'] = $this->request->get('refresh_token');


                // GameCenter 不做验证
                if ($platform == 'gamecenter') {
                    $payload['id'] = $open_id;
                }
                else {
                    // 验证第三方登陆
                    if (!($payload = $this->accountModel->verifyClientToken($app_id, $platform, $open_id, $code))) {
                        $this->response->setJsonContent([
                            'code' => 1,
                            'msg'  => _('account verify fail')
                        ], JSON_UNESCAPED_UNICODE)->send();
                        exit();
                    }
                }


                // 绑定第三方
                $nickName = empty($payload['name']) ? $open_name : $payload['name'];

                $account = $this->accountModel->getAccountByOauth($app_id, $platform, $payload['id'], $nickName,
                    $data['access_token'], $data['refresh_token'], $data);

                // 绑定accountKit手机号
                if (!empty($payload['mobile'])) {
                    $this->accountModel->bindAccount(
                        $app_id, $account['id'], 'mobile', $payload['mobile'], $nickName
                    );
                }
            }

        }
        /**
         *
         *
         * 手机SMS登录
         */
        elseif ($mobile) {

            if (!preg_match("/^1[34578]{1}\d{9}$/", $mobile)) {
                $this->response->setJsonContent([
                    'code' => 1,
                    'msg'  => _('invalid mobile number')
                ], JSON_UNESCAPED_UNICODE)->send();
                exit();
            }

            $sms = $this->request->get('sms', 'alphanum');
            if (!$sms) {
                $this->response->setJsonContent([
                    'code' => 1,
                    'msg'  => _('sms error')
                ], JSON_UNESCAPED_UNICODE)->send();
                exit();
            }

            $password = $this->request->get('password', 'trim');
            if (!empty($password) && strlen($password) < 6) {
                $this->response->setJsonContent([
                    'code' => 1,
                    'msg'  => _('invalid password length')
                ], JSON_UNESCAPED_UNICODE)->send();
                exit();
            }

            $account = $this->accountModel->loginWithSMS($mobile, $sms, $password);

            if (!$account) {
                $this->response->setJsonContent([
                    'code' => 1,
                    'msg'  => _('mobile login failed')
                ], JSON_UNESCAPED_UNICODE)->send();
                exit();
            }
        }
        /**
         *
         *
         * 设备登录UUID
         */
        else {
            $uuid = $this->request->get('uuid', ['string', 'lower']);
            if (strlen($uuid) < 32) {
                $this->response->setJsonContent([
                    'code' => 1,
                    'msg'  => _('uuid error')
                ], JSON_UNESCAPED_UNICODE)->send();
                exit();
            }
            $account = $this->accountModel->getAccountByOauth($app_id, 'device', $uuid, null, null, null, null);
        }

        if (empty($account['id'])) {
            $this->response->setJsonContent([
                'code' => 1,
                'msg'  => _('account error')
            ], JSON_UNESCAPED_UNICODE)->send();
            exit();
        }
        // 登录日志
        try {
            $log = [
                'user_id' => $account['id'],
                'app_id'  => $this->request->get('app_id', ['alphanum', 'trim']),
                'uuid'    => $this->request->get('uuid', ['string', 'lower']),
                'adid'    => $this->request->get('adid', ['string', 'lower']),
                'device'  => $this->request->get('device', 'string'),
                'version' => $this->request->get('version', 'string'),
                'channel' => $this->request->get('channel', 'string'),
                'type'    => isset($account['is_new']) ? 1 : 0,
                'ip'      => $this->request->get('ip', 'string') ?
                    $this->request->get('ip', 'string') : $this->request->getClientAddress()
            ];
            $logs = new Logs();
            $logs->accountLog($log);
        } catch (Exception $e) {
            // TODO :: error logs
        }


        // 检查黑名单
        if (isset($account['status']) && $account['status'] != 1) {
            $blacklistModel = new Blacklist();
            $blackUser = $blacklistModel->getUser($account['id']);
            if ($blackUser) {
                $this->response->setJsonContent(
                    array_merge(['code' => 1, 'msg' => $blackUser['msg']]), JSON_UNESCAPED_UNICODE
                )->send();
                exit();
            }
        }


        // 输出信息
        $accountName = isset($account['account']) ? $account['account'] : $account['id'];
        $accountInfo = [
            'open_id' => $account['id'],
            'name'    => isset($account['name']) ? $account['name'] : $account['id'],
            'gender'  => isset($account['gender']) ? $account['gender'] : 0,
            'photo'   => !empty($account['photo']) ? $account['photo'] : 'https://secure.gravatar.com/avatar/' . md5(strtolower(trim($accountName))) . '?s=80&d=identicon'
        ];
        $accountInfo['access_token'] = $this->accountModel->createAccessToken($accountInfo);
        $this->response->setJsonContent(
            array_merge(['code' => 0, 'msg' => _('success')], $accountInfo), JSON_UNESCAPED_UNICODE
        )->send();
    }


    /**
     * 注册账号
     * TODO :: 注册的账号如果是手机号需要验证
     */
    public function registerAction()
    {
        $username = $this->request->get('username', 'string');
        $password = $this->request->get('password', 'trim');

        // filter
        preg_match("/[^0-9a-zA-Z@_\-\.]/", $username, $match);
        if ($match || strlen($username) < 6) {
            $this->response->setJsonContent([
                'code' => 1,
                'msg'  => _('invalid username')
            ], JSON_UNESCAPED_UNICODE)->send();
            exit();
        }

        if (strlen($password) < 6) {
            $this->response->setJsonContent([
                'code' => 1,
                'msg'  => _('invalid password length')
            ], JSON_UNESCAPED_UNICODE)->send();
            exit();
        }


        // 创建账号
        $user_id = $this->accountModel->createAccount($username, $password);
        if (!$user_id) {
            $this->response->setJsonContent([
                'code' => 1,
                'msg'  => _('username exist')
            ], JSON_UNESCAPED_UNICODE)->send();
            exit();
        }


        // 创建access_token
        $accountInfo = [
            'open_id' => $user_id,
            'name'    => $username,
            'gender'  => 0,
            'photo'   => '',
        ];
        $access_token = $this->accountModel->createAccessToken($accountInfo);


        // 登录日志
        try {
            $log = [
                'user_id' => $user_id,
                'app_id'  => $this->request->get('app_id', ['alphanum', 'trim']),
                'uuid'    => $this->request->get('uuid', ['string', 'lower']),
                'adid'    => $this->request->get('adid', ['string', 'lower']),
                'device'  => $this->request->get('device', 'string'),
                'version' => $this->request->get('version', 'string'),
                'channel' => $this->request->get('channel', 'string'),
                'type'    => 1,
                'ip'      => $this->request->get('ip', 'string') ?
                    $this->request->get('ip', 'string') : $this->request->getClientAddress()
            ];
            $logs = new Logs();
            $logs->accountLog($log);
        } catch (Exception $e) {
            // TODO :: error logs
        }


        // 响应
        $this->response->setJsonContent([
            'code'         => 0,
            'msg'          => _('success'),
            'open_id'      => $user_id,
            'name'         => $username,
            'gender'       => '0',
            'status'       => '1',
            'photo'        => 'https://secure.gravatar.com/avatar/' . md5(strtolower(trim($username))) . '?s=80&d=identicon',
            'access_token' => $access_token,
        ], JSON_UNESCAPED_UNICODE)->send();
    }


    /**
     * 从官网中预注册
     * TODO :: 账号只能是邮箱注册
     */
    public function registerFromWebAction()
    {
        header("Access-Control-Allow-Origin:*");
        $username = $this->request->get('username', 'string');
        $password = $this->request->get('password', 'trim');

        // 验证邮箱
        preg_match("/^([A-Za-z0-9_\-\.])+\@([A-Za-z0-9_\-\.])+\.([A-Za-z]{2,4})$/", $username, $match);
        if (!$match) {
            $this->response->setJsonContent([
                'code' => 1,
                'msg' => _('invalid username')
            ], JSON_UNESCAPED_UNICODE)->send();
            exit();
        }

        if (strlen($password) < 6) {
            $this->response->setJsonContent([
                'code' => 1,
                'msg' => _('invalid password length')
            ], JSON_UNESCAPED_UNICODE)->send();
            exit();
        }

        // 账号创建
        $user_id = $this->accountModel->createAccount($username, $password);
        if (!$user_id) {
            $this->response->setJsonContent([
                'code' => 1,
                'msg' => _('user exist')
            ], JSON_UNESCAPED_UNICODE)->send();
            exit();
        }

        // 回复成功预约
        $this->response->setJsonContent([
            'code' => 0,
            'msg' => _('success')
        ])->send();
    }


    /**
     * 注销
     */
    public function logoutAction()
    {
        $this->persistent->destroy();
        $this->session->destroy();
        $access_token = $this->request->get('access_token', 'string');
        $this->accountModel->destroyAccessToken($access_token);

        $this->response->setJsonContent([
            'code' => 0,
            'msg'  => _('success'),
        ], JSON_UNESCAPED_UNICODE)->send();
    }


    /**
     * 验证access_token
     */
    public function verify_access_tokenAction()
    {
        $access_token = $this->dispatcher->getParam('0', 'string');
        if (!$access_token) {
            $access_token = $this->request->get('access_token', 'string');
        }

        $response = $this->accountModel->verifyAccessToken($access_token);
        if (!$response) {
            $this->response->setJsonContent([
                'code' => 1,
                'msg'  => _('failed')
            ], JSON_UNESCAPED_UNICODE)->send();
            exit();
        }
        $this->response->setJsonContent(
            array_merge(['code' => 0, 'msg' => _('success')], $response), JSON_UNESCAPED_UNICODE
        )->send();
    }


    /**
     *
     */
    public function forgetAction()
    {
        $account = $this->request->get('account', 'string');

        $patternMobile = "/^1[34578]{1}\d{9}$/";
        $patternEmail = "/^[a-z0-9]+([-_.][a-z0-9]+)*@([a-z0-9]+[-.])*[a-z0-9]+[\.][a-z]{2,3}$/i";

        if (preg_match($patternMobile, $account)) {
            // 手机
            // TODO :: 手机验证，兼容国际号段
            $this->response->setJsonContent(['code' => 0, 'msg' => _('success')])->send();
            exit;
        }
        elseif (preg_match($patternEmail, $account)) {
            // 检查邮箱账号是否存在
            if (!$this->accountModel->getAccount($account)) {
                return $this->response->setJsonContent([
                    'code' => 1,
                    'msg'  => _('account is not exist'),
                ])->send();
            }

            // 生成token
            $support = new Support();
            $token = $support->createJWT('RESET-PASS', $account, 3600 * 1);

            // 内容
            $url = 'http://' . $_SERVER['HTTP_HOST'] . "/do/token/$token";
            $content = <<<EOF
Dear Player:<br/>
If you wanna reset your password, then visit <a target="_blank" href='$url'>$url</a>
<br/>
The URL link will be timeout in an hours.
<br/><br/>
Yours truly,<br/>
GameHetu Network Team.
EOF;
            // 加入邮件队列
            $mail = new Mail();
            $mail->sendMail($account, 'Reset Your Password', $content);

            // 响应
            $this->response->setJsonContent(['code' => 0, 'msg' => _('success')])->send();
            exit;
        }

        $this->response->setJsonContent([
            'code' => 1,
            'msg'  => _('account error'),
        ])->send();
    }


    /**
     * 账号登录统计
     */
    public function statAction()
    {
        $data = [
            'user_id' => $this->request->get('open_id', ['alphanum', 'trim']),
            'app_id'  => $this->request->get('app_id', ['alphanum', 'trim']),
            'uuid'    => $this->request->get('uuid', ['string', 'lower']),
            'adid'    => $this->request->get('adid', ['string', 'lower']),
            'device'  => $this->request->get('device', 'string'),
            'version' => $this->request->get('version', 'string'),
            'channel' => $this->request->get('channel', 'string'),
            'type'    => 1,
            'ip'      => $this->request->get('ip', 'string')
        ];
        if (empty($data['user_id'])) {
            $this->response->setJsonContent(['code' => 1, 'msg' => _('no open_id')], JSON_UNESCAPED_UNICODE)->send();
            exit();
        }
        if (empty($data['ip'])) {
            $data['ip'] = $this->request->getClientAddress();
        }
        $logs = new Logs();
        if (!$logs->accountLog($data)) {
            $this->response->setJsonContent(
                ['code' => 1, 'msg' => _('failed to write logs')],
                JSON_UNESCAPED_UNICODE)->send();
            exit();
        }
        $this->response->setJsonContent(['code' => 0, 'msg' => _('success')], JSON_UNESCAPED_UNICODE)->send();
    }


    /**
     * 短信接口
     */
    public function smsAction()
    {
        $logs = new Logs();
        $mobile = $this->request->get('mobile', 'int');
        if (!preg_match("/^1[34578]{1}\d{9}$/", $mobile)) {
            $this->response->setJsonContent([
                'code' => 1,
                'msg'  => _('mobile number error')
            ], JSON_UNESCAPED_UNICODE)->send();
            exit();
        }
        $code = mt_rand(100000, 999999);
        $argv = json_encode(['code' => strval($code)], JSON_UNESCAPED_UNICODE); // 坑, 必须转字符串

        /**
         * AliDaYu SMS
         * @link http://www.alidayu.com/center/application/sdk
         */
        if (!empty($this->config->sms->app_id)) {
            include APP_DIR . '/plugins/aliyun/alidayu/TopSdk.php';
            $c = new \TopClient;
            $c->appkey = strval($this->config->sms->app_id); // 坑, 必须转字符串
            $c->secretKey = $this->config->sms->app_key;
            $req = new \AlibabaAliqinFcSmsNumSendRequest;
            $req->setSmsType("normal");
            $req->setSmsFreeSignName($this->config->sms->sign);
            $req->setSmsParam($argv);
            $req->setRecNum($mobile);
            $req->setSmsTemplateCode($this->config->sms->tmp_default);
            // $req->setExtend("123456"); 透传参数
            $response = $c->execute($req);
            if (!$response->result->success) {
                $res = (array)$response;
                $this->response->setJsonContent([
                    'code' => 1,
                    'msg'  => $res['msg']
                ], JSON_UNESCAPED_UNICODE)->send();
                exit();
            }
            $logs->smsLog($mobile, $code);
            $this->response->setJsonContent([
                'code' => 0,
                'msg'  => _('success')
            ], JSON_UNESCAPED_UNICODE)->send();
            exit();
        }
        /**
         *
         *
         * AliYun SMS
         */
        elseif (isset($this->config->aliyun->access_id)) {
            include_once APP_DIR . '/plugins/aliyun/aliyun-php-sdk-core/Config.php';
            $iClientProfile = \DefaultProfile::getProfile("cn-hangzhou", $this->config->aliyun->access_id,
                $this->config->aliyun->access_key);
            $client = new \DefaultAcsClient($iClientProfile);
            $request = new \Sms\Request\V20160927\SingleSendSmsRequest();
            $request->setSignName($this->config->sms->sign);
            $request->setTemplateCode($this->config->sms->tmp_default);
            $request->setRecNum($mobile);
            $request->setParamString($argv);
            try {
                $client->getAcsResponse($request);
                $logs->smsLog($mobile, $code);
                $this->response->setJsonContent([
                    'code' => 0,
                    'msg'  => _('success')
                ], JSON_UNESCAPED_UNICODE)->send();
            } catch (Exception  $e) {
                $this->response->setJsonContent([
                    'code' => 1,
                    'msg'  => $e->getErrorMessage()
                ], JSON_UNESCAPED_UNICODE)->send();
            }
            exit();
        }

        $this->response->setJsonContent([
            'code' => 1,
            'msg'  => _('send sms failed')
        ], JSON_UNESCAPED_UNICODE)->send();
        exit();
    }

}