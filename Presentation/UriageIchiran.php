<?php
require_once __DIR__ . '/../Business/UriageBusiness.php';

function h($v)
{
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

$biz = new UriageBusiness();

// 検索フォーム入力
$storeName = trim($_GET['store_name'] ?? '');
$dateFrom  = trim($_GET['date_from'] ?? '');
$dateTo    = trim($_GET['date_to'] ?? '');
$payment   = trim($_GET['payment_method'] ?? '');
$page      = max(1, (int)($_GET['page'] ?? 1));
$size      = 20;

$conditions = [];
if ($storeName !== '') $conditions['st.store_name'] = $storeName;
if ($payment !== '')   $conditions['s.payment_method'] = $payment;
if ($dateFrom !== '')  $conditions['s.date >='] = $dateFrom;
if ($dateTo !== '')    $conditions['s.date <='] = $dateTo;

$likeCols = ['st.store_name', 's.payment_method'];
$offset   = ($page - 1) * $size;

// データ取得
$total = $biz->countByConditions($conditions, $likeCols);
$rows  = $biz->searchWithLike($conditions, $likeCols, ['s.date DESC'], $size, $offset);
?>
<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <title>売上一覧</title>
    <link rel="stylesheet" href="../css/UriageIchiran.css">
</head>

<body>
    <div class="container">
        <h1>売上一覧</h1>

        <form action="UriageIchiran.php" method="get">
            <div class="form-section">
                <label for="store_name">店舗名</label>
                <input type="text" id="store_name" name="store_name" value="<?= h($storeName) ?>">

                <label for="payment_method">支払方法</label>
                <input type="text" id="payment_method" name="payment_method" value="<?= h($payment) ?>">

                <label for="date_from">日付（開始）</label>
                <input type="date" id="date_from" name="date_from" value="<?= h($dateFrom) ?>">

                <label for="date_to">日付（終了）</label>
                <input type="date" id="date_to" name="date_to" value="<?= h($dateTo) ?>">

                <button type="submit">検索</button>
            </div>
        </form>

        <div class="result-area">
            <?php if (empty($rows)): ?>
                <p class="no-data">該当する売上データがありません。</p>
            <?php else: ?>
                <table class="result-table">
                    <thead>
                        <tr>
                            <th>日付</th>
                            <th>店舗名</th>
                            <th>レジNo.</th>
                            <th>商品</th>
                            <th>数量</th>
                            <th>売上金額</th>
                            <th>支払方法</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($rows as $r): ?>
                            <tr>
                                <td><?= h($r['date']) ?></td>
                                <td><?= h($r['store_name']) ?></td>
                                <td><?= h($r['register_no']) ?></td>
                                <td><?= h($r['product_name']) ?></td>
                                <td><?= h($r['quantity']) ?></td>
                                <td><?= number_format((float)$r['amount']) ?>円</td>
                                <td><?= h($r['payment_method']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <p>全 <?= h($total) ?> 件</p>
            <?php endif; ?>
        </div>

        <div class="button-group">
            <button type="button" onclick="location.href='Menu.php'">戻る</button>
        </div>
    </div>
</body>

</html>