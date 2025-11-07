<?php
require_once __DIR__ . '/../Business/MasterBusiness.php';

function h($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }

$table = 'users';
$pk = 'user_id';
$biz = new MasterBusiness($table, $pk);

// 店舗マスタ取得
$storeBiz = new MasterBusiness('stores', 'store_id');
$stores = $storeBiz->search([]); // 全件取得

// GET/POST どちらからも user_id を取得
$userId = trim($_GET[$pk] ?? '');
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userId = trim($_POST[$pk] ?? $userId);
}

// フォーム用データ初期化
$user = [
    'user_name' => '',
    'password'  => '',
    'store_id'  => '',
    'role'      => 'staff',
];

$errorMsg = '';

// 検索条件保持用（一覧に戻るとき用）
$searchParams = [
    'user_name' => trim($_GET['user_name'] ?? ''),
    'store_id'  => trim($_GET['store_id'] ?? ''),
    'role'      => $_GET['role'] ?? 'all',
];

$roles = ['staff' => '一般', 'manager' => '本部', 'admin' => '管理者'];

// 編集モードなら既存データを取得
if ($userId !== '') {
    $rows = $biz->search([$pk => $userId]);
    if (!empty($rows)) {
        $user = $rows[0];
    } else {
        $errorMsg = '指定されたユーザーが存在しません。';
    }
}

// 保存処理（削除ではない）
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['delete'])) {
    $userData = [
        'user_name' => trim($_POST['user_name'] ?? ''),
        'password'  => trim($_POST['password'] ?? ''),
        'store_id'  => trim($_POST['store_id'] ?? ''),
        'role'      => $_POST['role'] ?? 'staff',
    ];

    if ($userId !== '') {
        $userData[$pk] = (int)$userId;
    }

    try {
        $id = $biz->insertUpdate($userData);
        header("Location: UserIchiran.php?success=1");
        exit;
    } catch (Exception $e) {
        $errorMsg = '保存に失敗しました: ' . $e->getMessage();
    }
}

// 削除処理
if (isset($_POST['delete']) && $userId !== '') {
    try {
        if ($biz->delete((int)$userId)) {
            $queryParams = $searchParams;
            unset($queryParams['user_id']);
            $query = http_build_query($queryParams);
            header("Location: UserIchiran.php?$query");
            exit;
        } else {
            $errorMsg = '削除に失敗しました。';
        }
    } catch (Exception $e) {
        $errorMsg = '削除に失敗しました: ' . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>ユーザーマスタ登録・編集</title>
<link rel="stylesheet" href="../css/Master.css">
</head>
<body>
<div class="container">
    <h1>ユーザーマスタ登録・編集</h1>

    <?php if ($errorMsg): ?>
        <p class="error-msg"><?= h($errorMsg) ?></p>
    <?php endif; ?>

    <?php
    $searchParamsNoId = $searchParams;
    unset($searchParamsNoId['user_id']);
    ?>

    <form method="post" action="">
        <div class="form-section">
            <?php if ($userId !== ''): ?>
                <input type="hidden" name="user_id" value="<?= h($userId) ?>">
            <?php endif; ?>

            <label for="user_name">ユーザー名</label>
            <div class="input-group">
                <input type="text" id="user_name" name="user_name" value="<?= h($user['user_name']) ?>" required>
            </div>

            <label for="password">パスワード</label>
            <div class="input-group">
                <input type="text" id="password" name="password" value="<?= h($user['password']) ?>" required>
            </div>

            <label for="store_id">所属店舗</label>
            <select id="store_id" name="store_id" required>
                <option value="">選択してください</option>
                <?php foreach ($stores as $s): ?>
                    <option value="<?= h($s['store_id']) ?>" <?= ($user['store_id'] == $s['store_id']) ? 'selected' : '' ?>>
                        <?= h($s['store_id']) ?>：<?= h($s['store_name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>


            <label for="role">権限種別</label>
            <select id="role" name="role">
                <?php foreach ($roles as $key => $label): ?>
                    <option value="<?= h($key) ?>" <?= ((string)$user['role'] === (string)$key) ? 'selected' : '' ?>>
                        <?= h($label) ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <div class="button-group">
                <button type="button" class="cancel-button" onclick="location.href='UserIchiran.php?<?= http_build_query($searchParamsNoId) ?>'">キャンセル</button>
                <?php if ($userId !== ''): ?>
                    <button type="submit" name="delete" value="1" class="delete-button" onclick="return confirm('本当に削除しますか？')">削除</button>
                <?php endif; ?>
                <button type="submit" class="submit-button">保存</button>
            </div>
        </div>
    </form>
</div>
</body>
</html>
