<?php
require_once __DIR__ . '/../Business/MasterBusiness.php';

function h($v)
{
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

$table = 'users';
$pk    = 'user_id';
$biz   = new MasterBusiness($table, $pk);

// 店舗マスタ取得（一覧でも店舗名を表示するため）
$storeBiz = new MasterBusiness('stores', 'store_id');
$stores = $storeBiz->search([]);

// 検索フォームの入力
$userId   = trim($_GET['user_id'] ?? '');
$userName = trim($_GET['user_name'] ?? '');
$storeId  = trim($_GET['store_id'] ?? '');
$role     = $_GET['role'] ?? 'all';
$page     = max(1, (int)($_GET['page'] ?? 1));
$size     = 20;

$roles = [
    'all'     => '全て',
    'staff'   => '一般',
    'manager' => '本部',
    'admin'   => '管理者',
];

// 検索条件
$conditions = [
    'user_id'   => $userId,
    'user_name' => $userName,
    'store_id'  => $storeId,
];

// role が all 以外なら条件に追加
if ($role !== 'all') {
    $conditions['role'] = $role;
}

$likeCols = ['user_id', 'user_name'];
$offset   = ($page - 1) * $size;

// 件数と検索結果を取得
$total = $biz->countByConditions($conditions, $likeCols);
$rows  = $biz->searchWithLike($conditions, $likeCols, ['user_id ASC'], $size, $offset);
?>
<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ユーザー一覧</title>
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

        function selectRow(row, userId) {
            if (selectedRow) selectedRow.classList.remove('selected');
            row.classList.add('selected');
            selectedRow = row;
            document.getElementById('selectedUserId').value = userId;
        }

        function checkSelection() {
            const userId = document.getElementById('selectedUserId').value;
            if (!userId) {
                alert('編集する行を選択してください。');
                return false;
            }
            return true;
        }
    </script>
</head>

<body>
    <div class="container">
        <h1>ユーザー一覧</h1>

        <!-- 検索フォーム -->
        <form action="UserIchiran.php" method="get">
            <div class="form-section">

                <label for="user_id">ユーザーID</label>
                <input type="text" id="user_id" name="user_id" value="<?= h($userId) ?>">

                <label for="user_name">ユーザー名</label>
                <div class="input-with-button">
                    <input type="text" id="user_name" name="user_name" value="<?= h($userName) ?>">

                    <div class="search-actions">
                        <button type="submit">検索</button>
                    </div>
                </div>

                <label for="store_id">所属店舗</label>
                <div class="horizontal-group">
                    <select id="store_id" name="store_id">
                        <option value="">選択してください</option>
                        <?php foreach ($stores as $s): ?>
                            <option value="<?= h($s['store_id']) ?>"
                                <?= ($storeId == $s['store_id']) ? 'selected' : '' ?>>
                                <?= h($s['store_id']) ?>：<?= h($s['store_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <label for="role">権限種別</label>
                    <select id="role" name="role">
                        <?php foreach ($roles as $key => $name): ?>
                            <option value="<?= h($key) ?>" <?= $role === $key ? 'selected' : '' ?>>
                                <?= h($name) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

            </div>
        </form>

        <!-- 検索結果 -->
        <form action="UserMaster.php" method="get">
            <input type="hidden" id="selectedUserId" name="user_id" value="">
            <div class="result-area">
                <?php if (empty($rows)): ?>
                    <p class="no-data">該当するユーザーがありません。</p>
                <?php else: ?>
                    <table class="result-table">
                        <thead>
                            <tr>
                                <th>ユーザーID</th>
                                <th>ユーザー名</th>
                                <th>パスワード</th>
                                <th>所属店舗</th>
                                <th>権限種別</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($rows as $r): ?>
                                <tr class="selectable" onclick="selectRow(this, '<?= h($r['user_id']) ?>')">
                                    <td><?= h($r['user_id']) ?></td>
                                    <td><?= h($r['user_name']) ?></td>
                                    <td><?= h($r['password']) ?></td>
                                    <td>
                                        <?php
                                        $storeName = '';
                                        foreach ($stores as $s) {
                                            if ($s['store_id'] == $r['store_id']) {
                                                $storeName = $s['store_name'];
                                                break;
                                            }
                                        }
                                        echo h($storeName);
                                        ?>
                                    </td>
                                    <td>
                                        <?php
                                        switch ($r['role']) {
                                            case 'staff': echo '一般'; break;
                                            case 'manager': echo '本部'; break;
                                            case 'admin': echo '管理者'; break;
                                            default: echo h($r['role']);
                                        }
                                        ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <p>全 <?= h($total) ?> 件</p>
                <?php endif; ?>
            </div>

            <div class="button-group">
                <button type="button" onclick="location.href='Menu.php'">戻る</button>
                <button type="submit" name="action" value="new"
                    onclick="document.getElementById('selectedUserId').value=''">新規登録</button>
                <button type="submit" name="action" value="edit" onclick="return checkSelection()">編集</button>
            </div>
        </form>
    </div>
</body>
</html>
