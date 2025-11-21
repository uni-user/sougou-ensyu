<?php
// /app/pages/Syukei.php
declare(strict_types=1);
require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../DataAccess/SyukeiData.php';
require_once __DIR__ . '/../Business/SyukeiBusiness.php';

$data = new SyukeiData();
$biz  = new SyukeiBusiness($data);

// GET: ランキングタイプ（デフォルト: product）
$rankingType = isset($_GET['type']) ? trim($_GET['type']) : 'product';

// 追加: 期間パラメータ（YYYY-MM-DD）
$startDate = isset($_GET['start']) ? trim($_GET['start']) : '';
$endDate   = isset($_GET['end'])   ? trim($_GET['end'])   : '';

// 簡易バリデーション
$validDate = function ($v) {
  if ($v === '') return false;
  $d = DateTime::createFromFormat('Y-m-d', $v);
  return $d && $d->format('Y-m-d') === $v;
};
if ($startDate !== '' && !$validDate($startDate)) $startDate = '';
if ($endDate   !== '' && !$validDate($endDate))   $endDate   = '';
if ($startDate !== '' && $endDate !== '') {
  $sd = new DateTime($startDate);
  $ed = new DateTime($endDate);
  if ($sd > $ed) {
    [$startDate, $endDate] = [$endDate, $startDate];
  }
}

$errors = [];
$rows   = [];
$title  = '売上集計';

// Fキー処理
$f = isset($_GET['f']) ? $_GET['f'] : '';
switch ($f) {
  case '1':
    header('Location: Register.php');
    exit;
  case '3':
    header('Location: Delete.php');
    exit;
  case '12':
    header('Location: Menu.php');
    exit;
  default:
    break;
}

// ランキングデータ取得（期間を引数に追加）
$result = $biz->getRanking($rankingType, $startDate !== '' ? $startDate : null, $endDate !== '' ? $endDate : null);
$errors = $result['errors'];
$rows   = $result['rows'];
$rankingType = $result['ranking_type'] ?? $rankingType; // Business側のデフォルト反映

// タイトル
switch ($rankingType) {
  case 'store':
    $title = '店舗別集計';
    break;
  case 'product':
    $title = '商品別集計';
    break;
  case 'date':
    $title = '日付別集計';
    break;
  case 'payment':
    $title = '支払方法別集計';
    break;
  default:
    $title = '商品別集計';
    break;
}

// 期間クエリの引き回し用
$queryPeriod = '';
if ($startDate !== '') $queryPeriod .= '&start=' . rawurlencode($startDate);
if ($endDate   !== '') $queryPeriod .= '&end='   . rawurlencode($endDate);

// 店舗名マッピング（必要なら Business で名前解決して返しているのでここでは未使用でも可）
$stores = [
  'honbu'     => '本部',
  'shinjuku'  => '[address]',
  'aomori'    => '[address]',
  'hokkaido'  => '[address]',
  'shizuoka'  => '[address]',
  'nagoya'    => '[address]',
];
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
    <h2>
      <?= htmlspecialchars($title) ?>
      <?php if ($startDate !== '' && $endDate !== ''): ?>
        <small style="font-weight:normal;color:#555;">(<?= htmlspecialchars($startDate) ?> ～ <?= htmlspecialchars($endDate) ?>)</small>
      <?php endif; ?>
    </h2>

    <!-- 期間指定フォーム -->
    <div class="period-form">
      <form method="get" action="">
        <input type="hidden" name="type" value="<?= htmlspecialchars($rankingType) ?>">
        <label>集計期間：</label>
        <input type="date" name="start" value="<?= htmlspecialchars($startDate) ?>">
        <label>～</label>
        <input type="date" name="end" value="<?= htmlspecialchars($endDate) ?>">
        <button type="submit">適用</button>
        <?php if ($startDate !== '' || $endDate !== ''): ?>
          <a href="?type=<?= htmlspecialchars($rankingType) ?>" style="margin-left:8px;">期間クリア</a>
        <?php endif; ?>
      </form>
    </div>

    <!-- ランキングタイプ選択ボタン（期間を維持して遷移） -->
    <div class="ranking-type-buttons">
      <button type="button" class="<?= ($rankingType === 'store') ? 'active' : '' ?>" onclick="location.href='?type=store<?= $queryPeriod ?>'">店舗別</button>
      <button type="button" class="<?= ($rankingType === 'product') ? 'active' : '' ?>" onclick="location.href='?type=product<?= $queryPeriod ?>'">商品別</button>
      <button type="button" class="<?= ($rankingType === 'date') ? 'active' : '' ?>" onclick="location.href='?type=date<?= $queryPeriod ?>'">日付別</button>
      <button type="button" class="<?= ($rankingType === 'payment') ? 'active' : '' ?>" onclick="location.href='?type=payment<?= $queryPeriod ?>'">支払方法別</button>
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
            <?php elseif ($rankingType === 'payment'): ?>
              <th>支払方法</th>
            <?php endif; ?>
            <th style="text-align:right;width:160px;">売上金額(円)</th>
          </tr>
        </thead>
        <tbody>
          <?php
          $rank = 1;
          if (!empty($rows)):
            foreach ($rows as $r):
              $amt = (float)($r['total_amount'] ?? 0);
          ?>
              <tr>
                <td><?= $rank ?></td>
                <?php if ($rankingType === 'store'): ?>
                  <td><?= htmlspecialchars($r['store_name'] ?? ($r['store_id'] ?? '')) ?></td>
                <?php elseif ($rankingType === 'product'): ?>
                  <td><?= htmlspecialchars($r['product_name'] ?? ($r['product_id'] ?? '')) ?></td>
                <?php elseif ($rankingType === 'date'): ?>
                  <td><?= htmlspecialchars($r['sales_date'] ?? '') ?></td>
                <?php endif; ?>
                <?php if ($rankingType === 'payment'): ?>
                  <td><?= htmlspecialchars($r['payment_name'] ?? '') ?></td>
                <?php endif; ?>
                <td style="text-align:right;"><?= number_format($amt) ?></td>
              </tr>
            <?php
              $rank++;
            endforeach;
          else:
            ?>
            <tr>
              <td colspan="3" style="text-align:center;color:#666;">データがありません</td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>

    <div class="footer" id="footerButtons">
      <form method="get" id="f12form">
        <input type="hidden" name="f" value="12">
        <button type="submit" title="F12: メニュー">F12<br>メニュー</button>
      </form>
    </div>
  </div>

  <script>
    document.addEventListener('keydown', function(e) {
      if (e.isComposing) return;
      if (e.key === 'F12') {
        e.preventDefault();
        location.href = '?f=12<?= $queryPeriod ?>&type=<?= htmlspecialchars($rankingType) ?>';
      }
    });
  </script>
</body>

</html>