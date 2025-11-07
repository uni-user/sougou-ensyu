<?php
// /app/Presentation/Login.php
require_once __DIR__ . '/../Business/LoginBusiness.php';
session_start();

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = trim($_POST['user_id'] ?? '');
    $password = trim($_POST['password'] ?? '');

    $biz = new LoginBusiness();
    $user = $biz->login($user_id, $password);

    if ($user) {
        // ログイン成功 → セッションにユーザー情報を保存
        $_SESSION['user'] = $user;
        header('Location: Menu.php');
        exit;
    } else {
        $error = "ユーザー名またはパスワードが違います。";
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

<form method="post">
    <h2>ログイン</h2>
    <input type="text" name="user_id" placeholder="ユーザーID" required>
    <input type="password" name="password" placeholder="パスワード" required>
    <button type="submit">ログイン</button>
    <?php if ($error): ?>
        <p class="error"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p>
    <?php endif; ?>
</form>

</body>
</html>
