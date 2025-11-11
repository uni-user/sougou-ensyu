<?php
session_start();

if (!isset($_SESSION['user'])) {
    header('Location: Login.php');
    exit;
}

require_once __DIR__ . '/../Business/MasterBusiness.php';

function h($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }

$table = 'stores';
$pk = 'store_id';
$biz = new MasterBusiness($table, $pk);

// GET/POSTどちらからも store_id を取得
$storeId = trim($_GET[$pk] ?? '');
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $storeId = trim($_POST[$pk] ?? $storeId);
}

// フォーム用データ初期化
$store = [
    'store_name' => '',
    'address'    => '',
    'state'      => '1',
];

$errorMsg = '';

// 検索条件保持用（一覧に戻るとき用）
$searchParams = [
    'store_id'   => trim($_GET['store_id'] ?? ''),
    'store_name' => trim($_GET['store_name'] ?? ''),
    'address'    => trim($_GET['address'] ?? ''),
    'state'      => $_GET['state'] ?? 'all',
];

$states = ['1' => '有効', '0' => '無効'];

// 編集モードなら既存データを取得
if ($storeId !== '') {
    $rows = $biz->search([$pk => $storeId]);
    if (!empty($rows)) {
        $store = $rows[0];
    } else {
        $errorMsg = '指定された店舗が存在しません。';
    }
}

// 保存処理（削除ではない）
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['delete'])) {
    $storeData = [
        'store_name' => trim($_POST['store_name'] ?? ''),
        'address'    => trim($_POST['address'] ?? ''),
        'state'      => $_POST['state'] ?? '1',
    ];

    // 編集モードなら store_id を追加
    if ($storeId !== '') {
        $storeData[$pk] = (int)$storeId;
    }

    try {
        $id = $biz->insertUpdate($storeData);
        header("Location: StoreIchiran.php?success=1");
        exit;
    } catch (Exception $e) {
        $errorMsg = '保存に失敗しました: ' . $e->getMessage();
    }
}

// 削除処理
if (isset($_POST['delete']) && $storeId !== '') {
    try {
        if ($biz->delete((int)$storeId)) {
            $queryParams = $searchParams;
            unset($queryParams['store_id']);
            $query = http_build_query($queryParams);
            header("Location: StoreIchiran.php?$query");
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
<title>店舗マスタ登録・編集</title>
<link rel="stylesheet" href="../css/Master.css">
</head>
<body>
<div class="container">
    <h1>店舗マスタ登録・編集</h1>

    <?php if ($errorMsg): ?>
        <p class="error-msg"><?= h($errorMsg) ?></p>
    <?php endif; ?>

    <?php
    $searchParamsNoId = $searchParams;
    unset($searchParamsNoId['store_id']);
    ?>

    <form method="post" action="">
        <div class="form-section">
            <!-- 店舗IDは非表示 -->
            <?php if ($storeId !== ''): ?>
                <input type="hidden" name="store_id" value="<?= h($storeId) ?>">
            <?php endif; ?>

            <label for="store_name">店舗名</label>
            <div class="input-group">
                <input type="text" id="store_name" name="store_name" value="<?= h($store['store_name']) ?>" required>
            </div>

            <label for="address">所在地</label>
            <div class="input-group">
                <input type="text" id="address" name="address" value="<?= h($store['address']) ?>" required>
            </div>

            <label>状態</label>
            <div class="input-group">
                <?php foreach ($states as $key => $label): ?>
                    <label>
                        <input type="radio" name="state" value="<?= h($key) ?>"
                            <?= ((string)$store['state'] === (string)$key) ? 'checked' : '' ?>>
                        <?= h($label) ?>
                    </label>
                <?php endforeach; ?>
            </div>

            <div class="button-group">
                <button type="button" class="cancel-button" onclick="location.href='StoreIchiran.php?<?= http_build_query($searchParamsNoId) ?>'">キャンセル</button>
                <?php if ($storeId !== ''): ?>
                    <button type="submit" name="delete" value="1" class="delete-button" onclick="return confirm('本当に削除しますか？')">削除</button>
                <?php endif; ?>
                <button type="submit" class="submit-button">保存</button>
            </div>
        </div>
    </form>
</div>
</body>
</html>
