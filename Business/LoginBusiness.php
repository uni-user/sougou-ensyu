<?php
require_once __DIR__ . '/../DataAccess/LoginData.php';

class LoginBusiness
{
    private LoginData $loginData;

    public function __construct()
    {
        $this->loginData = new LoginData();
    }

    /**
     * ログイン処理
     */
    public function login(string $userId, string $password): ?array
    {
        // 入力チェック
        if ($userId === '' || $password === '') {
            return null;
        }

        // ユーザー情報取得
        $user = $this->loginData->findUserById($userId);
        if (!$user) {
            return null;
        }

        // パスワード認証
        if ($user['password'] !== $password) {
            return null;
        }

        // ログイン成功
        return $user;
    }



    /**
     * ハッシュ化されたパスワード対応バージョン（必要に応じて差し替え）
     */
    public function loginWithHash(string $userId, string $password): ?array
    {
        if ($userId === '' || $password === '') {
            throw new Exception('ユーザーIDとパスワードを入力してください。');
        }

        $user = $this->loginData->findUserById($userId);
        if (!$user) {
            throw new Exception('ユーザーが存在しません。');
        }

        if (!password_verify($password, $user['password'])) {
            throw new Exception('パスワードが正しくありません。');
        }

        return $user;
    }
}
