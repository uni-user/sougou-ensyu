<?php
// /app/DataAccess/LoginData.php

require_once __DIR__ . '/MasterData.php';

class LoginData
{
    private PDO $db;

    public function __construct()
    {
        // MasterData と同じ接続設定を利用
        $server   = 'VRT-DB-SQL2022';
        $database = 'TRAINING';
        $dsn      = "sqlsrv:Server=$server;Database=$database";
        $user     = 'new_employee';
        $pass     = 'HSyQhbmx7U';

        $this->db = new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::SQLSRV_ATTR_ENCODING    => PDO::SQLSRV_ENCODING_UTF8,
        ]);
    }

    /**
     * 指定されたユーザーIDに該当するユーザー情報を取得
     */
    public function findUserById(string $userId): ?array
    {
        $sql = "SELECT * FROM users WHERE user_id = :user_id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_STR);
        $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        return $user ?: null;
    }

    /**
     * ログイン認証処理
     * （プレーンなパスワード一致チェック。ハッシュを使うなら password_verify() に変更可）
     */
    public function authenticate(string $userId, string $password): ?array
    {
        $sql = "SELECT * FROM users WHERE user_id = :user_id AND password = :password";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_STR);
        $stmt->bindValue(':password', $password, PDO::PARAM_STR);
        $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        return $user ?: null;
    }

    /**
     * （オプション）パスワードを変更する
     */
    public function updatePassword(string $userId, string $newPassword): bool
    {
        $sql = "UPDATE users SET password = :password WHERE user_id = :user_id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_STR);
        $stmt->bindValue(':password', $newPassword, PDO::PARAM_STR);
        return $stmt->execute();
    }
}
