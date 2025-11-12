<?php
// /app/pages/Syukei.php
declare(strict_types=1);

require_once __DIR__ . '/../DataAccess/SyukeiData.php';
require_once __DIR__ . '/../Business/SyukeiBusiness.php';

$data = new SyukeiData();
$biz  = new SyukeiBusiness($data);

// --------------------------------------------------------
// GETパラメータからランキングタイプを取得。デフォルトは「商品別」
$rankingType = isset($_GET['type']) ? trim($_GET['type']) : 'product';
// --------------------------------------------------------

// 店舗名のマッピング (Graph.phpやSyukei.phpの$stores配列に相当)
// DBの sales.store_id に格納されている値と、表示したい店舗名を対応させてください
$stores = [
    'honbu'     => '本部',
    'shinjuku'  => '新宿店',
    'aomori'    => '青森店',
    'hokkaido'  => '北海道店',
    'shizuoka'  => '静岡店',
    'nagoya'    => '名古屋店',
];

$errors = [];
$rows   = [];
$title  = '売上集計';

// Fキー処理
$f = isset($_GET['f']) ? $_GET['f'] : '';
switch ($f) {
    case '1': header('Location: Register.php'); exit;         // 登録
    case '3': header('Location: Delete.php'); exit;           // 削除
    case '12': header('Location: Menu.php'); exit;            // メニューへ
    default: break;
}

// ランキングデータ取得
$result = $biz->getRanking($rankingType);
$errors = $result['errors'];
$rows   = $result['rows'];
$rankingType = $result['ranking_type']; // Business層でデフォルトに変わった場合を反映

// タイトルを設定
switch ($rankingType) {
    case 'store':
        $title = '店舗別集計';// 店舗別集計
        break;
    case 'product':
        $title = '商品別集計'; // 店商品別集計
        break;
    case 'date':
        $title = '日付別集計'; // 日付別集計
        break;
    default:
        $title = '商品別集計'; // デフォルト
        break;
}

?>
<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="UTF-8">
<title><?= htmlspecialchars($title) ?></title>
<link rel="stylesheet" href="../css/Syukei.css">
</head>
<body>
<div class="container">
  <h2><?= htmlspecialchars($title) ?></h2>

  <!-- ランキングタイプ選択ボタン -->
  <div class="ranking-type-buttons">
    <button type="button" class="<?= ($rankingType === 'store') ? 'active' : '' ?>" onclick="location.href='?type=store'">店舗別</button>
    <button type="button" class="<?= ($rankingType === 'product') ? 'active' : '' ?>" onclick="location.href='?type=product'">商品別</button>
    <button type="button" class="<?= ($rankingType === 'date') ? 'active' : '' ?>" onclick="location.href='?type=date'">日付別</button>
  </div>

  <?php if ($errors): ?>
    <div style="color:#b00020;">
      <?php foreach ($errors as $e): ?>
        <div><?= htmlspecialchars($e) ?></div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

  <div class="box">
    <table>
      <thead>
        <tr>
          <th style="width:60px;">順位</th>
          <?php if ($rankingType === 'store'): ?>
            <th>店舗名</th>
          <?php elseif ($rankingType === 'product'): ?>
            <th>商品</th>
          <?php elseif ($rankingType === 'date'): ?>
            <th>日付</th>
          <?php endif; ?>
          <th style="text-align:right;width:160px;">売上金額(円)</th>
        </tr>
      </thead>
      <tbody>
        <?php
        $rank = 1;
        if (!empty($rows)): // データがある場合のみ表示
            foreach ($rows as $r):
                $amt = (float)$r['total_amount'];
        ?>
          <tr>
            <td><?= $rank ?></td>
            <?php if ($rankingType === 'store'): ?>
                <!-- store_id を $stores 配列で店舗名に変換 -->
                <td><?= htmlspecialchars($r['store_name']) ?></td>
            <?php elseif ($rankingType === 'product'): ?>
                <td><?= htmlspecialchars($r['product_name']) ?></td>
            <?php elseif ($rankingType === 'date'): ?>
                <td><?= htmlspecialchars($r['sales_date']) ?></td>
            <?php endif; ?>
            <td style="text-align:right;"><?= number_format($amt) ?></td>
          </tr>
        <?php
                $rank++;
            endforeach;
        else:
        ?>
          <!-- colspan は表示する列数に合わせて変更 (順位 + 項目名 + 売上金額 = 3列) -->
          <tr><td colspan="3" style="text-align:center;color:#666;">データがありません</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

  <!-- Function Key Button -->
  <div class="footer" id="footerButtons">
    <form method="get" id="f12form">
      <input type="hidden" name="f" value="12">
      <button type="submit" title="F12: メニュー">F12<br>メニュー</button>
    </form>
  </div>
</div>

<!-- Function Key Event -->
<script>
document.addEventListener('keydown', function(e) {
  if (e.isComposing) return;
  const key = e.key;

  switch (key) {
    case 'F12':
      e.preventDefault();
      location.href = '?f=12'; // F12押下でf=12を付与してリロード (PHP側でMenu.phpへリダイレクト)
      break;
    default:
      break;
  }
});
</script>
</body>
</html>