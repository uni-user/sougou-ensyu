<?php
// Menu.php
session_start();

// ログイン済みか確認
if (!isset($_SESSION['user'])) {
    header('Location: Login.php');
    exit;
}

// セッションから情報を取得
$user = $_SESSION['user'];
$userId     = (int)($user['user_id'] ?? 0);
$userName   = $user['user_name'] ?? '';
$department = $user['role'] === 'manager' ? '本部' :
              ($user['role'] === 'admin' ? '管理者' : '一般');

// 未ログインまたは不正なセッションならログイン画面へ
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

<main>
  <h1 class="dept"><?= htmlspecialchars($department, ENT_QUOTES, 'UTF-8') ?> メニュー</h1>

  <section class="menu-grid">
    <a class="card" href="SalesIchiran.php">売上一覧</a>
    <a class="card" href="Aggregate.php">集計</a>
    <a class="card" href="RankingIchiran.php">ランキング一覧</a>

    <a class="card" href="StoreIchiran.php">店舗マスタ</a>
    <a class="card" href="ProductIchiran.php">商品マスタ</a>
    <a class="card" href="UserIchiran.php">ユーザーマスタ</a>
  </section>
</main>
</body>
</html>
