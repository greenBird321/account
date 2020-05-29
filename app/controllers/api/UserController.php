<?php

namespace MyApp\Controllers\Api;


use MyApp\Models\Accounts;

class UserController extends ControllerBase
{

    private $accountModel;


    public function initialize()
    {
        $this->accountModel = new Accounts();
    }


    public function indexAction()
    {
    }


    /**
     * /api/user/info
     * user_id,time,sign
     */
    public function infoAction()
    {
        $user_id = $this->request->get('user_id', 'int');
        $account = $this->accountModel->getAccountByUserId($user_id);
        if (!$account) {
            $this->response->setJsonContent([
                'code' => 1,
                'msg'  => 'no account'
            ])->send();
            exit();
        }
        unset($account['password']);
        $this->response->setJsonContent(array_merge([
            'code' => 0,
            'msg'  => 'success'
        ], $account))->send();
        exit();
    }

}