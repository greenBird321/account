<?php

/**
 * 第三方平台登录
 * 仅适用于第三方提供账号登录体系的平台
 */
namespace MyApp\Controllers;


use Phalcon\Config;
use Phalcon\Mvc\Dispatcher;
use MyApp\Models\Accounts;
use MyApp\Models\Blacklist;
use Symfony\Component\Yaml\Yaml;
use MyApp\Models\Logs;
use Xt\Publisher\Publisher;
use Exception;

class PublisherController extends ControllerBase
{

    public function indexAction()
    {
        $platform = $this->dispatcher->getParam("param", 'alphanum');

        // Token
        $token = $this->request->get('token', 'string');

        // UID 目前仅支持整型
        $uid = $this->request->get('uid', 'string');

        // 自定义字段
        $custom = $this->request->get('custom', 'string');
        $app_id = $this->request->get('app_id', ['alphanum', 'trim']);
        // Config
        $cfg = new Config(Yaml::parse(file_get_contents(APP_DIR . '/config/publisher.yml')));

        if (isset($cfg->ip_allow) && !in_array($this->request->getClientAddress(), explode(',', $cfg->ip_allow))) {
            $this->response->setJsonContent(
                ['code' => 1, 'msg' => 'ip is not allowed'], JSON_UNESCAPED_UNICODE)->send();
            exit();
        }

        // 开始验证
        if (isset($cfg->$platform)) {
            if(isset($cfg->$platform->$app_id))
            {
                $cfgPub = $cfg->$platform->$app_id;
            }else
            {
                $cfgPub = $cfg->$platform;
            }
            $publisher = new Publisher($platform, (array)$cfgPub);
//            $result = $publisher->verifyToken($token, ['uid' => $uid, 'custom' => $custom]);
//            dump($result);
//            exit;
            try {
                $result = $publisher->verifyToken($token, ['uid' => $uid, 'custom' => $custom]);
            } catch (Exception $e) {
                $this->response->setJsonContent(['code' => 1, 'msg' => _('exception and failed')],
                    JSON_UNESCAPED_UNICODE)->send();
                exit();
            }
            if (!$result) {
                $this->response->setJsonContent(['code' => 1, 'msg' => _('failed')], JSON_UNESCAPED_UNICODE)->send();
                exit();
            }
        }
        else {
            $this->response->setJsonContent(
                ['code' => 1, 'msg' => 'channel not allowed'], JSON_UNESCAPED_UNICODE)->send();
            exit();

            $result = [
                'uid'      => $uid,
                'username' => $uid,
                'original' => '',
            ];
        }


        // 开始登录


        // 账号信息
        $accountsModel = new Accounts();
        $channel_id = "";
        if(isset($result['channel_id']) && $result['channel_id'])
        {
            $channel_id = $result['channel_id'];
        }
        $account = $accountsModel->getAccountByOauth($app_id, $platform, $result['uid'], $result['username'],'','',[],$channel_id);

        // 检查
        if (!$account) {
            $this->response->setJsonContent(['code' => 1, 'msg' => _('failed')], JSON_UNESCAPED_UNICODE)->send();
            exit();
        }

        // 登录日志
        try {
            $log = [
                'user_id' => $account['id'],
                'app_id'  => $app_id,
                'uuid'    => $this->request->get('uuid', ['string', 'lower']),
                'adid'    => $this->request->get('adid', ['string', 'lower']),
                'device'  => $this->request->get('device', 'string'),
                'version' => $this->request->get('version', 'string'),
                'channel' => $platform,
                'type'    => isset($account['is_new']) ? 1 : 0,
                'ip'      => $this->request->get('ip', 'string')
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
        $accountInfo = [
            'open_id' => $account['id'],
            'name'    => isset($account['name']) ? $account['name'] : $account['id'],
            'gender'  => isset($account['gender']) ? $account['gender'] : 0,
            'photo'   => !empty($account['photo']) ? $account['photo'] : 'https://secure.gravatar.com/avatar/' . md5(strtolower(trim($result['uid']))) . '?s=80&d=identicon',
            'raw'     => base64_encode(json_encode($result['original']))
        ];

        // access_token
        $this->accountModel = new Accounts();
        $accountInfo['access_token'] = $this->accountModel->createAccessToken($accountInfo);

        $this->response->setJsonContent(
            array_merge(['code' => 0, 'msg' => _('success')], $accountInfo), JSON_UNESCAPED_UNICODE
        )->send();
        exit();
    }

}