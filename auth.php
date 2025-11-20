<?php
session_start();

// タイムアウト時間（秒）
$timeout = 1800; // 30分

// ▼ ログインチェック
if (!isset($_SESSION['user'])) {
    header('Location: Login.php');
    exit;
}

// ▼ タイムアウトチェック
if (isset($_SESSION['LAST_ACTIVE']) && time() - $_SESSION['LAST_ACTIVE'] > $timeout) {
    session_unset();
    session_destroy();
    header("Location: Login.php?timeout=1");
    exit;
}

// 最終アクセス時刻更新
$_SESSION['LAST_ACTIVE'] = time();
