<?php
require_once 'auth.php';
require_once __DIR__ . '/../Business/MasterBusiness.php';

function h($v)
{
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

$table = 'products';
$pk = 'product_id';
$biz = new MasterBusiness($table, $pk);

// GET/POST どちらからも product_id を取得
$productId = trim($_GET[$pk] ?? '');
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $productId = trim($_POST[$pk] ?? $productId);
}

// フォーム用データ初期化
$product = [
    'product_name' => '',
    'category'    => '',
    'price'    => '',
    'is_available'      => '1',
];

$errorMsg = '';

// 検索条件保持用（一覧に戻るとき用）
$searchParams = [
    // 'product_id'   => trim($_GET['product_id'] ?? ''),
    'product_name' => trim($_GET['product_name'] ?? ''),
    'category'    => trim($_GET['category'] ?? ''),
    'price'    => trim($_GET['price'] ?? ''),
    'is_available'      => $_GET['is_available'] ?? 'all',
];

$is_availables = ['1' => '取扱中', '0' => '取扱停止'];

// 編集モードなら既存データを取得
if ($productId !== '') {
    $rows = $biz->search([$pk => $productId]);
    if (!empty($rows)) {
        $product = $rows[0];
    } else {
        $errorMsg = '指定された商品が存在しません。';
    }
}

// 保存処理（削除ではない）
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['delete'])) {
    $productData = [
        'product_name' => trim($_POST['product_name'] ?? ''),
        'category'    => trim($_POST['category'] ?? ''),
        'price'    => trim($_POST['price'] ?? ''),
        'is_available'      => $_POST['is_available'] ?? '1',
    ];

    // 編集モードなら product_id を追加
    if ($productId !== '') {
        $productData[$pk] = (int)$productId;
    }

    try {
        $id = $biz->insertUpdate($productData);
        header("Location: ProductIchiran.php?success=1");
        exit;
    } catch (Exception $e) {
        $errorMsg = '保存に失敗しました: ' . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>商品マスタ登録・編集</title>
    <link rel="stylesheet" href="../css/Master.css">

    <script>
        // 入力フォームをクリア
        function clearSearchForm() {
            const form = document.querySelector('form[action="ProductMaster.php"]');
            if (!form) return;
            form.querySelectorAll('input[type="text"]').forEach(el => el.value = '');
            form.submit(); // クリア後に全件表示
        }

        // ファンクションキー操作
        document.addEventListener('keydown', function(e) {
            if (e.isComposing) return;

            switch (e.key) {
                case 'F1': // 保存
                    e.preventDefault();
                    document.querySelector('form[action="ProductMaster.php"]').submit();
                    break;

                case 'F9': // クリア
                    e.preventDefault();
                    clearSearchForm();
                    break;

                case 'F12': // 一覧画面へ戻る
                    e.preventDefault();
                    location.href = 'ProductIchiran.php';
                    break;
            }
        });
    </script>
</head>

<body>
    <div class="container">
        <h1>商品マスタ登録・編集</h1>

        <?php if ($errorMsg): ?>
            <p class="error-msg"><?= h($errorMsg) ?></p>
        <?php endif; ?>

        <?php
        $searchParamsNoId = $searchParams;
        unset($searchParamsNoId['product_id']);
        ?>

        <form method="post" action="ProductMaster.php">
            <div class="form-section">
                <!-- 商品IDは非表示 -->
                <?php if ($productId !== ''): ?>
                    <input type="hidden" name="product_id" value="<?= h($productId) ?>">
                <?php endif; ?>

                <label for="product_name">商品名</label>
                <div class="input-group">
                    <input type="text" id="product_name" name="product_name" value="<?= h($product['product_name']) ?>" required>
                </div>

                <label for="category">カテゴリ</label>
                <div class="input-group">
                    <input type="text" id="category" name="category" value="<?= h($product['category']) ?>" required>
                </div>

                <label for="price">単価
                </label>
                <div class="input-group">
                    <input type="text" id="price" name="price" value="<?= h($product['price']) ?>" required>
                </div>

                <label>取扱状況</label>
                <div class="input-group">
                    <?php foreach ($is_availables as $key => $label): ?>
                        <label>
                            <input type="radio" name="is_available" value="<?= h($key) ?>"
                                <?= ((string)$product['is_available'] === (string)$key) ? 'checked' : '' ?>>
                            <?= h($label) ?>
                        </label>
                    <?php endforeach; ?>
                </div>

                <div class="button-group">
                    <button type="button" class="cancel-button" onclick="location.href='ProductIchiran.php?<?= http_build_query($searchParamsNoId) ?>'">F12<br>キャンセル</button>

                    <button type="submit" name="input" class="submit-button">F1<br>保存</button>
                </div>
            </div>
        </form>
    </div>
</body>

</html>