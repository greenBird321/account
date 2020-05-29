<?php


namespace MyApp\Controllers;


use MyApp\Models\Accounts;
use MyApp\Models\Support;
use Phalcon\Mvc\Dispatcher;

class DoController extends ControllerBase
{


    public function indexAction()
    {
        $token = $this->dispatcher->getParam('token');
        if (!$token) {
            exit;
        }
        $token = ltrim($token, '/');

        $support = new Support();
        try {
            $data = $support->verifyJWT($token);
        } catch (\Exception $e) {
            exit('Token Error');
        }


        switch ($data['sub']) {
            case 'RESET-PASS':
                $this->resetPass($data['data']);
                break;

            default:
                break;
        }
    }


    private function resetPass($data = '')
    {
        if ($_POST) {
            $account = $this->request->getPost('account', 'trim');
            $password = $this->request->getPost('password', 'trim');

            if (strlen($password) < 6) {
                $this->view->tips = [
                    'redirect' => '',
                    'seconds'  => '5',
                    'type'     => 'fail',
                    'message'  => 'password too simple'
                ];
                return $this->view->pick("public/tips");
            }

            $accountModel = new Accounts();
            if (!$accountModel->resetPassword($account, $password)) {
                $this->view->tips = [
                    'redirect' => '',
                    'seconds'  => '5',
                    'type'     => 'fail',
                    'message'  => 'Fail'
                ];
                return $this->view->pick("public/tips");
            }

            $this->view->tips = [
                'redirect' => '#',
                'seconds'  => '9999',
                'type'     => 'success',
                'message'  => 'Success'
            ];
            return $this->view->pick("public/tips");
        }


        $this->view->data = [
            'account' => $data
        ];
        $this->view->pick("public/reset");
    }

}