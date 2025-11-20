<?php
require_once __DIR__ . '/../Business/LoginBusiness.php';
session_start();

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = trim($_POST['user_id'] ?? '');
    $password = trim($_POST['password'] ?? '');

    $biz = new LoginBusiness();

    try {
        $user = $biz->login($user_id, $password);

        if (!$user) {
            // ユーザーが存在しない、パスワードが間違ってる
            $error = "ユーザーIDまたはパスワードが正しくありません。";
        } elseif ($user['role'] === 'staff') {
            // 権限チェック：staffはログイン不可
            $error = "このアカウントではログインできません。";
        } elseif ($user['account_status'] === '0') {
            // 権限チェック：無効化されたユーザーはログイン不可
            $error = "このアカウントは無効化されています。";
        } elseif ($user['store_state'] === '0') {
            // 無効化された店舗に所属しているユーザーはログイン不可
            $error = "このアカウントが所属する店舗は現在無効化されています。";
        } else {
            // ログイン成功
            $_SESSION['user'] = $user;
            header('Location: Menu.php');
            exit;
        }
    } catch (PDOException $e) {
        error_log("Login error: " . $e->getMessage());
        $error = "ログイン処理中に問題が発生しました。入力内容をご確認ください。";
    } catch (Exception $e) {
        error_log("General login error: " . $e->getMessage());
        $error = "予期しないエラーが発生しました。もう一度お試しください。";
    }
}
?>
<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ログイン</title>
    <link rel="stylesheet" href="../css/Login.css">
</head>

<body>
    <div class="container">
        <h1>ログイン</h1>

        <form method="post" class="login-form">

            <?php if ($error): ?>
                <div class="error-box">
                    <span class="error-icon">⚠️</span>
                    <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
                </div>
            <?php endif; ?>

            <input type="text" name="user_id" placeholder="ユーザーID(半角英数字)" required>
            <input type="password" name="password" placeholder="パスワード(半角英数字、記号)" required>
            <button type="submit" class="login-button">ログイン</button>

        </form>
    </div>
</body>

</html>