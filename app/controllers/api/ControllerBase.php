<?php

namespace MyApp\Controllers\Api;


use Phalcon\Mvc\Controller;
use Xxtime\Util;

class ControllerBase extends Controller
{

    public function beforeExecuteRoute()
    {
        if (!$this->verify()) {
            $this->response->setJsonContent([
                'code' => 1,
                'msg'  => 'verify failed'
            ])->send();
            exit();
        }
    }


    private function verify()
    {
        $signKey = $this->config->setting->secret_key;
        $sign = $this->request->get('sign');
        $time = $this->request->get('time');
        if (abs($time - time()) > 300) {
            return false;
        }
        $request = $_REQUEST;
        unset($request['_url'], $request['sign']);
        if (Util::createSign($request, $signKey) != $sign) {
            return false;
        }
        return true;
    }

}