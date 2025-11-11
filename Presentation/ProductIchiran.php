<?php
session_start();

if (!isset($_SESSION['user'])) {
    header('Location: Login.php');
    exit;
}

require_once __DIR__ . '/../Business/MasterBusiness.php';

function h($v)
{
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

$table = 'products';
$pk    = 'product_id';
$biz   = new MasterBusiness($table, $pk);

// 検索フォームの入力
$productName = trim($_GET['product_name'] ?? '');
$category   = trim($_GET['category'] ?? '');
$is_available     = $_GET['is_available'] ?? 'all';
$page      = max(1, (int)($_GET['page'] ?? 1));
$size      = 20;

$is_availables = [
    'all' => '全て',
    '1'   => '取扱中',
    '0'   => '取扱停止',
];

// 検索条件
$conditions = [
    'product_name' => $productName,
    'category'    => $category,
];

// is_available が all 以外なら条件に追加
if ($is_available !== 'all') {
    $conditions['is_available'] = $is_available;
}

$likeCols = ['product_id', 'product_name', 'category'];
$offset   = ($page - 1) * $size;

// Business経由で件数と検索結果を取得
$total = $biz->countByConditions($conditions, $likeCols);
$rows  = $biz->searchWithLike($conditions, $likeCols, ['product_id ASC'], $size, $offset);
?>
<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>商品一覧</title>
    <link rel="stylesheet" href="../css/MasterIchiran.css">
    <style>
        tr.selectable:hover {
            background-color: #eef;
            cursor: pointer;
        }

        tr.selected {
            background-color: #cce;
        }
    </style>
    <script>
        let selectedRow = null;

        function selectRow(row, productId) {
            if (selectedRow) selectedRow.classList.remove('selected');
            row.classList.add('selected');
            selectedRow = row;
            document.getElementById('selectedProductId').value = productId;
        }

        function checkSelection() {
            const productId = document.getElementById('selectedProductId').value;
            if (!productId) {
                alert('編集する行を選択してください。');
                return false;
            }
            return true;
        }
    </script>
</head>

<body>
    <div class="container">
        <h1>商品一覧</h1>

        <form action="ProductIchiran.php" method="get">
            <div class="form-section">

                <label for="product_name">商品名</label>
                <input type="text" id="product_name" name="product_name" value="<?= h($productName) ?>">

                <label for="category">カテゴリ</label>
                <div class="input-with-button">
                    <input type="text" id="category" name="category" value="<?= h($category) ?>">

                    <div class="search-actions">
                        <button type="submit">検索</button>
                    </div>

                </div>

                <label for="is_available">取扱状態</label>
                <select id="is_available" name="is_available">
                    <?php foreach ($is_availables as $key => $name): ?>
                        <option value="<?= h($key) ?>" <?= $is_available === $key ? 'selected' : '' ?>><?= h($name) ?></option>
                    <?php endforeach; ?>
                </select>

            </div>
        </form>

        <form action="ProductMaster.php" method="get">
            <input type="hidden" id="selectedProductId" name="product_id" value="">
            <div class="result-area">
                <?php if (empty($rows)): ?>
                    <p class="no-data">該当する商品がありません。</p>
                <?php else: ?>
                    <table class="result-table">
                        <thead>
                            <tr>
                                <th>商品ID</th>
                                <th>商品名</th>
                                <th>カテゴリ</th>
                                <th>標準価格</th>
                                <th>取扱状態</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($rows as $r): ?>
                                <tr class="selectable" onclick="selectRow(this, '<?= h($r['product_id']) ?>')">
                                    <td><?= h($r['product_id']) ?></td>
                                    <td><?= h($r['product_name']) ?></td>
                                    <td><?= h($r['category']) ?></td>
                                    <td><?= number_format((float)$r['price']) ?>円</td>
                                    <td><?= $r['is_available'] === '1' ? '取扱中' : '取扱停止' ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <p>全 <?= h($total) ?> 件</p>
                <?php endif; ?>
            </div>
        <div class="button-group">
            <button type="button" onclick="location.href='Menu.php'">戻る</button>
            <button type="submit" name="action" value="new" onclick="document.getElementById('selectedProductId').value=''">新規登録</button>
            <button type="submit" name="action" value="edit" onclick="return checkSelection()">編集</button>
        </div>
        </form>
    </div>
</body>

</html>