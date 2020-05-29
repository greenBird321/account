<?php


namespace MyApp\Controllers;


use MyApp\Models\Accounts;
use Phalcon\Mvc\Dispatcher;

class AccountController extends ControllerBase
{

    private $accountModel;


    private $_account;


    public function initialize()
    {
        parent::initialize();

        $this->accountModel = new Accounts();

        $access_token = $this->request->get('access_token');
        $this->_account = $this->accountModel->verifyAccessToken($access_token);
        if (!$this->_account) {
            $this->response->setJsonContent([
                'code' => 1,
                'msg'  => _('access_token error')
            ], JSON_UNESCAPED_UNICODE)->send();
            exit();
        }
    }


    public function indexAction()
    {
        $this->response->setStatusCode(403, "not allowed")->setContent("not allowed")->send();
    }


    public function profileAction()
    {
        $account = $this->accountModel->getAccountMore($this->_account['open_id']);
        if (!$account) {
            $this->response->setJsonContent([
                'code' => 1,
                'msg'  => _('account error')
            ])->send();
            exit();
        }
        $this->response->setJsonContent(array_merge([
            'code' => 0,
            'msg'  => _('success')
        ], $account), JSON_UNESCAPED_UNICODE)->send();
        exit();
    }


    /**
     * 修改账号信息
     */
    public function modifyAction()
    {
        $argv['account'] = $this->request->get('account', 'string');
        $argv['name'] = $this->request->get('name', 'string');
        $argv['photo'] = $this->request->get('photo', 'string');
        $response = $this->accountModel->modifyAccount($this->_account['open_id'], $argv);
        if (!$response) {
            $this->response->setJsonContent([
                'code' => 1,
                'msg'  => _('failed')
            ], JSON_UNESCAPED_UNICODE)->send();
            exit();
        }
        $this->response->setJsonContent([
            'code' => 0,
            'msg'  => _('success')
        ], JSON_UNESCAPED_UNICODE)->send();
        exit();
    }


    /**
     * 修改密码
     */
    public function passwordAction()
    {
        $old_pwd = $this->request->get('old_pwd', 'trim');
        $password = $this->request->get('password', 'trim');
        if (!$old_pwd || !$password) {
            $this->response->setJsonContent([
                'code' => 1,
                'msg'  => _('parameter error')
            ], JSON_UNESCAPED_UNICODE)->send();
            exit();
        }

        // 检查原密码
        $account = $this->accountModel->getAccountByUserId($this->_account['open_id']);
        if (!($account['password'] && password_verify($old_pwd, $account['password']))) {
            $this->response->setJsonContent([
                'code' => 1,
                'msg'  => _('password verify failed')
            ], JSON_UNESCAPED_UNICODE)->send();
            exit();
        }

        // 更新密码
        $response = $this->accountModel->modifyAccount($this->_account['open_id'], ['password' => $password]);
        if (!$response) {
            $this->response->setJsonContent([
                'code' => 1,
                'msg'  => _('failed')
            ], JSON_UNESCAPED_UNICODE)->send();
            exit();
        }
        $this->response->setJsonContent([
            'code' => 0,
            'msg'  => _('success')
        ], JSON_UNESCAPED_UNICODE)->send();
        exit();
    }


    public function bindAction()
    {
        $app_id = $this->request->get('app_id', 'alphanum');
        $username = $this->request->get('username', 'string');
        $mobile = $this->request->get('mobile', 'alphanum');

        /**
         *
         * 绑定手机号
         */
        if ($mobile) {
            $sms = $this->request->get('sms', 'alphanum');
            if (!preg_match("/^1[34578]{1}\d{9}$/", $mobile)) {
                $this->response->setJsonContent([
                    'code' => 1,
                    'msg'  => _('invalid mobile number')
                ], JSON_UNESCAPED_UNICODE)->send();
                exit();
            }
            if (!$sms || !$this->accountModel->checkSMS($mobile, $sms)) {
                $this->response->setJsonContent([
                    'code' => 1,
                    'msg'  => _('sms error')
                ], JSON_UNESCAPED_UNICODE)->send();
                exit();
            }

            list($response, $msg) = $this->accountModel->bindAccount(
                $app_id, $this->_account['open_id'], 'mobile', $mobile, $mobile
            );

            if (!$response) {
                $this->response->setJsonContent([
                    'code' => 1,
                    'msg'  => $msg
                ], JSON_UNESCAPED_UNICODE)->send();
                exit();
            }
        }
        /**
         *
         *
         * 绑定账号
         */
        elseif ($username) {
            $username = $this->request->get('username', 'trim');
            $password = $this->request->get('password', 'trim');

            // check
            if (!preg_match("/^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,})$/", $username)) {
                $this->response->setJsonContent(['code' => 1, 'msg' => _('email error')])->send();
                exit;
            }
            if (strlen($password) < 6) {
                $this->response->setJsonContent(['code' => 1, 'msg' => _('password too sample')])->send();
                exit;
            }

            // bind
            $response = $this->accountModel->bindEmail($this->_account['open_id'], $username, $password);
            if (!$response) {
                $this->response->setJsonContent(['code' => 1, 'msg' => _('fail')])->send();
                exit;
            }

            $this->response->setJsonContent(['code' => 0, 'msg' => _('success')])->send();
            exit;
        }
        /**
         *
         *
         * 绑定社交账号
         */
        else {
            // 需要$app_id
            $platform = $this->request->get('platform', 'alphanum');
            $open_id = $this->request->get('open_id', 'alphanum');
            $open_name = $this->request->get('open_name', 'alphanum');
            $code = $this->request->get('code', 'alphanum'); // access_token

            // 验证第三方登陆
            if (!($payload = $this->accountModel->verifyClientToken(
                $app_id,
                $platform,
                $open_id,
                $code))
            ) {
                $this->response->setJsonContent([
                    'code' => 1,
                    'msg'  => _('account verify fail')
                ], JSON_UNESCAPED_UNICODE)->send();
                exit();
            }

            // 绑定
            $nickName = empty($payload['name']) ? $open_name : $payload['name'];
            $this->accountModel->bindAccount(
                $app_id, $this->_account['open_id'], $platform, $payload['id'], $nickName
            );

            // 绑定手机
            if (!empty($payload['mobile'])) {
                $this->accountModel->bindAccount(
                    $app_id, $this->_account['open_id'], 'mobile', $payload['mobile'], $nickName
                );
            }
        }


        // 绑定成功
        $this->response->setJsonContent([
            'code' => 0,
            'msg'  => _('success')
        ], JSON_UNESCAPED_UNICODE)->send();
        exit();
    }

}