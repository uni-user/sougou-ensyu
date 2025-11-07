<?php
require_once 'LoginData.php';

class LoginBusiness {
    private $data;

    public function __construct() {
        $this->data = new LoginData();
    }

    public function login($username, $password) {
        // 入力チェック
        if (empty($username) || empty($password)) {
            return false;
        }

        // DB照合
        $user = $this->data->getUserByUsername($username);

        if ($user && password_verify($password, $user['password'])) {
            // ログイン成功
            session_start();
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            return true;
        }

        return false;
    }
}
