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

$table = 'stores';
$pk    = 'store_id';
$biz   = new MasterBusiness($table, $pk);

// 検索フォームの入力
$storeId   = trim($_GET['store_id'] ?? '');
$storeName = trim($_GET['store_name'] ?? '');
$address   = trim($_GET['address'] ?? '');
$state     = $_GET['state'] ?? 'all';
$page      = max(1, (int)($_GET['page'] ?? 1));
$size      = 20;

$states = [
    'all' => '全て',
    '1'   => '有効',
    '0'   => '無効',
];

// 検索条件
$conditions = [
    'store_id'   => $storeId,
    'store_name' => $storeName,
    'address'    => $address,
];

// state が all 以外なら条件に追加
if ($state !== 'all') {
    $conditions['state'] = $state;
}

$likeCols = ['store_id', 'store_name', 'address'];
$offset   = ($page - 1) * $size;

// Business経由で件数と検索結果を取得
$total = $biz->countByConditions($conditions, $likeCols);
$rows  = $biz->searchWithLike($conditions, $likeCols, ['store_id ASC'], $size, $offset);
?>
<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>店舗一覧</title>
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

        function selectRow(row, storeId) {
            if (selectedRow) selectedRow.classList.remove('selected');
            row.classList.add('selected');
            selectedRow = row;
            document.getElementById('selectedStoreId').value = storeId;
        }

        function checkSelection() {
            const storeId = document.getElementById('selectedStoreId').value;
            if (!storeId) {
                alert('編集する行を選択してください。');
                return false;
            }
            return true;
        }
    </script>
</head>

<body>
    <div class="container">
        <h1>店舗一覧</h1>

        <form action="StoreIchiran.php" method="get">
            <div class="form-section">
                <!-- <label for="store_id">店舗ID</label>
                <input type="text" id="store_id" name="store_id" value="<?= h($storeId) ?>"> -->

                <label for="store_name">店舗名</label>
                <input type="text" id="store_name" name="store_name" value="<?= h($storeName) ?>">

                <label for="address">所在地</label>
                <div class="input-with-button">
                    <input type="text" id="address" name="address" value="<?= h($address) ?>">

                    <div class="search-actions">
                        <button type="submit">検索</button>
                    </div>

                </div>

                <label for="state">状態</label>
                <select id="state" name="state">
                    <?php foreach ($states as $key => $name): ?>
                        <option value="<?= h($key) ?>" <?= $state === $key ? 'selected' : '' ?>><?= h($name) ?></option>
                    <?php endforeach; ?>
                </select>

            </div>
        </form>

        <form action="StoreMaster.php" method="get">
            <input type="hidden" id="selectedStoreId" name="store_id" value="">
            <div class="result-area">
                <?php if (empty($rows)): ?>
                    <p class="no-data">該当する店舗がありません。</p>
                <?php else: ?>
                    <table class="result-table">
                        <thead>
                            <tr>
                                <th>店舗ID</th>
                                <th>店舗名</th>
                                <th>所在地</th>
                                <th>状態</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($rows as $r): ?>
                                <tr class="selectable" onclick="selectRow(this, '<?= h($r['store_id']) ?>')">
                                    <td><?= h($r['store_id']) ?></td>
                                    <td><?= h($r['store_name']) ?></td>
                                    <td><?= h($r['address']) ?></td>
                                    <td><?= $r['state'] === '1' ? '有効' : '無効' ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <p>全 <?= h($total) ?> 件</p>
                <?php endif; ?>
            </div>
            <div class="button-group">
                <button type="button" onclick="location.href='Menu.php'">戻る</button>
                <button type="submit" name="action" value="new" onclick="document.getElementById('selectedStoreId').value=''">新規登録</button>
                <button type="submit" name="action" value="edit" onclick="return checkSelection()">編集</button>
            </div>
        </form>
    </div>
</body>

</html>