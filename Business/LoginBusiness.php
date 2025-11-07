<?php
// /app/Business/LoginBusiness.php

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
     * 
     * @param string $userId
     * @param string $password
     * @return array|null ログイン成功時はユーザー情報、失敗時はnull
     */
    public function login(string $userId, string $password): ?array
    {
        // 入力チェック
        if ($userId === '' || $password === '') {
            throw new Exception('ユーザーIDとパスワードを入力してください。');
        }

        // ユーザー情報取得
        $user = $this->loginData->findUserById($userId);
        if (!$user) {
            throw new Exception('ユーザーが存在しません。');
        }

        // パスワード認証（ハッシュ未使用バージョン）
        if ($user['password'] !== $password) {
            throw new Exception('パスワードが正しくありません。');
        }

        // ログイン成功時
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
