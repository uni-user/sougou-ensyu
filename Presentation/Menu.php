<?php
require_once 'auth.php';

// セッションから情報を取得
$user = $_SESSION['user'];
$userId     = (int)($user['user_id'] ?? 0);
$userName   = $user['user_name'] ?? '';
$role       = $user['role'] ?? '';

// 部署表示
$department = $role === 'manager' ? '本部' :
              ($role === 'admin' ? '管理者' : '一般');

// 未ログインまたは不正セッションならログイン画面へ
if ($userId === 0) {
    header('Location: Login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>メニュー</title>
<link rel="stylesheet" href="../css/Menu.css">
</head>
<body>
<header class="topbar">
  <div class="user-info">
    <span>ユーザーID：<?= htmlspecialchars($userId, ENT_QUOTES, 'UTF-8') ?></span>
    <span>（<?= htmlspecialchars($userName, ENT_QUOTES, 'UTF-8') ?> / <?= htmlspecialchars($department, ENT_QUOTES, 'UTF-8') ?>）</span>
  </div>
  <form class="logout" action="Logout.php" method="post">
    <button type="submit">ログアウト</button>
  </form>
</header>

<main class="menu-container">
  <div class="menu-grid">
    <button class="menu-button" onclick="location.href='StoreIchiran.php'">店舗マスタ</button>
    <button class="menu-button" onclick="location.href='ProductIchiran.php'">商品マスタ</button>

    <?php if ($role === 'admin'): ?>
      <button class="menu-button" onclick="location.href='UserIchiran.php'">ユーザーマスタ</button>
    <?php endif; ?>

    <button class="menu-button" onclick="location.href='UriageIchiran.php'">売上一覧</button>
    <button class="menu-button" onclick="location.href='Syukei.php'">集計</button>
    <button class="menu-button" onclick="location.href='Alert.php'">未入力店舗一覧</button>
  </div>
</main>


</body>
</html>
