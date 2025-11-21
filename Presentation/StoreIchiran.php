<?php
require_once __DIR__ . '/../auth.php';
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

$conditions = [
    'store_id'   => $storeId,
    'store_name' => $storeName,
    'address'    => $address,
];
if ($state !== 'all') $conditions['state'] = $state;

$likeCols = ['store_id', 'store_name', 'address'];
$offset   = ($page - 1) * $size;

$total = $biz->countByConditions($conditions, $likeCols);
$rows  = $biz->searchWithLike($conditions, $likeCols, ['store_id ASC'], $size, $offset);
?>
<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
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

        // 検索フォームをクリア
        function clearSearchForm() {
            const form = document.querySelector('form[action="StoreIchiran.php"]');
            if (!form) return;
            form.querySelectorAll('input[type="text"], input[type="date"], select').forEach(el => el.value = '');
        }

        // ファンクションキー処理
        document.addEventListener('keydown', function(e) {
            if (e.isComposing) return;

            switch (e.key) {
                case 'F1': // 新規登録
                    e.preventDefault();
                    document.querySelector('button[name="action"][value="new"]').click();
                    break;

                case 'F2': // 検索
                    e.preventDefault();
                    document.querySelector('form[action="StoreIchiran.php"]').submit();
                    break;

                case 'F3': // 編集
                    e.preventDefault();
                    const editBtn = document.querySelector('button[name="action"][value="edit"]');
                    if (editBtn) editBtn.click();
                    break;

                case 'F9': // クリア
                    e.preventDefault();
                    clearSearchForm();
                    break;

                case 'F12': // メニューに戻る
                    e.preventDefault();
                    location.href = 'Menu.php';
                    break;
            }
        });
    </script>
</head>

<body>
    <div class="container">
        <h1>店舗一覧</h1>

        <!-- 検索フォーム -->
        <form action="StoreIchiran.php" method="get">
            <div class="form-section">
                <label for="store_name">店舗名</label>
                <input type="text" id="store_name" name="store_name" value="<?= h($storeName) ?>">

                <label for="address">所在地</label>
                <div class="input-with-button">
                    <input type="text" id="address" name="address" value="<?= h($address) ?>">
                    <div class="search-actions">
                        <button type="submit">F2<br>検索</button>
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

        <!-- 一覧＆操作ボタン -->
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
                                    <td class="<?= $r['state'] === '1' ? 'status-available' : 'status-stopped' ?>">
                                        <?= $r['state'] === '1' ? '有効' : '無効' ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <p>全 <?= h($total) ?> 件</p>
                <?php endif; ?>
            </div>

            <div class="button-group">
                <button type="button" onclick="location.href='Menu.php'">F12<br>戻る</button>
                <button type="submit" name="action" value="new" onclick="document.getElementById('selectedStoreId').value=''">F1<br>新規登録</button>
                <button type="submit" name="action" value="edit" onclick="return checkSelection()">F3<br>編集</button>
            </div>
        </form>
    </div>
</body>

</html>