<?php
session_start();

if (!isset($_SESSION['user'])) {
    header('Location: Login.php');
    exit;
}

require_once __DIR__ . '/../Business/UriageBusiness.php';

function h($v)
{
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

$biz = new UriageBusiness();

// 検索フォーム入力
$dateFrom    = trim($_GET['date_from'] ?? '');
$dateTo      = trim($_GET['date_to'] ?? '');
$storeId     = trim($_GET['store_id'] ?? '');
$productName = trim($_GET['product_name'] ?? '');
$payment     = trim($_GET['payment_method'] ?? '');

// ページング
$page = max(1, (int)($_GET['page'] ?? 1));
$size = 20;
$offset = ($page - 1) * $size;

// 条件配列
$conditions = [];
if ($storeId !== '')    $conditions['st.store_id']    = $storeId;
if ($payment !== '')    $conditions['s.payment_method'] = $payment;
if ($dateFrom !== '')   $conditions['s.date >=']      = $dateFrom;
if ($dateTo !== '')     $conditions['s.date <=']      = $dateTo;

$likeCols = ['p.product_name'];
$total = $biz->countByConditions($conditions, $likeCols);
$rows  = $biz->searchWithLike($conditions, $likeCols, ['s.date DESC'], $size, $offset);

// 店舗と支払方法の配列（例）
$stores = $biz->getAllStores(); // store_id, store_name
$paymentMethods = ['現金', 'クレジット', 'QRコード'];
?>
<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <title>売上一覧</title>
    <link rel="stylesheet" href="../css/UriageIchiran.css">

    <script>
        // 検索フォームをクリア
        function clearSearchForm() {
            const form = document.querySelector('form[action="UriageIchiran.php"]');
            if (!form) return;
            form.querySelectorAll('input[type="text"], input[type="date"], select').forEach(el => el.value = '');
            form.submit(); // クリア後に全件表示
        }

        // ファンクションキー操作
        document.addEventListener('keydown', function(e) {
            if (e.isComposing) return;

            switch (e.key) {
                case 'F2': // 検索
                    e.preventDefault();
                    document.querySelector('form[action="UriageIchiran.php"]').submit();
                    break;

                case 'F9': // クリア
                    e.preventDefault();
                    clearSearchForm();
                    break;

                case 'F12': // メニューへ戻る
                    e.preventDefault();
                    location.href = 'Menu.php';
                    break;
            }
        });
    </script>
</head>

<body>
    <div class="container">
        <h1>売上一覧</h1>

        <form action="UriageIchiran.php" method="get">
            <div class="form-section">

                <!-- 期間 -->
                <label for="date_from">期間</label>
                <div class="horizontal-group">
                    <input type="date" id="date_from" name="date_from" value="<?= h($dateFrom) ?>">
                    <span>～</span>
                    <input type="date" id="date_to" name="date_to" value="<?= h($dateTo) ?>">
                </div>

                <!-- 店舗名プルダウン -->
                <label for="store_id">店舗名</label>
                <select id="store_id" name="store_id">
                    <option value="">選択してください</option>
                    <?php foreach ($stores as $s): ?>
                        <option value="<?= h($s['store_id']) ?>" <?= ($storeId == $s['store_id']) ? 'selected' : '' ?>>
                            <?= h($s['store_id']) ?>：<?= h($s['store_name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <!-- 商品名テキスト -->
                <label for="product_name">商品名</label>
                <input type="text" id="product_name" name="product_name" value="<?= h($productName) ?>">

                <!-- 支払方法プルダウン -->
                <label for="payment_method">支払方法</label>
                <select id="payment_method" name="payment_method">
                    <option value="">選択してください</option>
                    <?php foreach ($paymentMethods as $pm): ?>
                        <option value="<?= h($pm) ?>" <?= ($payment == $pm) ? 'selected' : '' ?>>
                            <?= h($pm) ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <!-- 検索ボタン -->
                <div class="search-actions">
                    <button type="submit">F2<br>検索</button>
                </div>
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
                                <td style="text-align:right;"><?= number_format((float)$r['amount']) ?>円</td>
                                <td style="text-align:center;"><?= h($r['payment_method']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <p>全 <?= h($total) ?> 件</p>
            <?php endif; ?>
        </div>

        <div class="button-group">
            <button type="button" onclick="location.href='Menu.php'">F12<br>戻る</button>
        </div>
    </div>
</body>
</html>
