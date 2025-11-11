<?php
// pages/Syukei.php

// DataAccessフォルダ内のファイルをインクルード
require_once __DIR__ . '/../DataAccess/Syukeidata.php';

// 現在の日付を取得し、デフォルト値を設定
$today     = date('Y-m-d');
$yesterday = date('Y-m-d', strtotime('-1 day'));

// GETリクエストから検索条件を取得
$start_date         = $_GET['start_date']      ?? $yesterday;         // デフォルトは昨日
$end_date           = $_GET['end_date']        ?? $today;             // デフォルトは今日
$store              = $_GET['store']           ?? 'all';              // デフォルトは「全ての店舗」
$product_id_input   = trim($_GET['productID']  ?? '');                // 商品IDの取得
$product_name_input = trim($_GET['product']    ?? '');                // デフォルトは空、前後空白除去
$payment_method     = $_GET['payment_method']  ?? 'all';              // デフォルトは「全ての支払方法」

// 選択肢の配列（HTMLフォーム用）。DataAccess/Syukeidata.phpのgetStoreIdByKeyとgetPaymentMethodNameByKeyのマッピングとキーを合わせる
// dbo.storesテーブルのデータに基づいて更新
$stores = [
    'all'       => '全ての店舗',
    'honbu'     => '本部',
    'shinjuku'  => '新宿本店',
    'aomori'    => '青森店',
    'hokkaido'  => '北海道店',
    'shizuoka'  => '静岡店',
    'nagoya'    => '名古屋店',
];

$payment_methods = [
    'all'    => '全ての支払方法',
    'cash'   => '現金',
    'credit' => 'クレジットカード',
    'qr'     => 'QRコード決済',
];

// Syukeidataクラスのインスタンスを作成
$syukei_data_accessor = new Syukeidata();

$errors        = [];
$filtered_data = [];
$message       = '';

// 簡易バリデーション（日付形式・期間整合性）
$reDate = '/^\d{4}-\d{2}-\d{2}$/';
if (!preg_match($reDate, $start_date)) $errors[] = '開始日が不正です（YYYY-MM-DD形式）';
if (!preg_match($reDate, $end_date))   $errors[] = '終了日が不正です（YYYY-MM-DD形式）';
if (!$errors && strtotime($start_date) > strtotime($end_date)) $errors[] = '開始日は終了日以前にしてください';

// 商品IDと商品名の両方が未入力の場合にエラーとする
if ($product_id_input === '' && $product_name_input === '') {
    $errors[] = '商品名または商品IDが未入力です。検索するにはいずれかを入力してください。';
}

if ($errors) {
    // エラーがある場合はメッセージを表示
    $message = implode(' / ', $errors);
} else {
    // フィルター条件を配列にまとめる
    // SyukeidataクラスのgetSalesDataメソッドに渡すフィルター
    $filters = [
        'start_date'         => $start_date,
        'end_date'           => $end_date,
        'store_key'          => $store,              // 'honbu'のようなキーを渡す
        'product_id'         => $product_id_input,
        'product_name'       => $product_name_input,
        'payment_method_key' => $payment_method,      // 'cash'のようなキーを渡す
    ];

    // Syukeidataクラスからデータを取得（DB側でフィルタリング済み）
    $all_data = $syukei_data_accessor->getSalesData($filters);
    $filtered_data = $all_data; // DBクエリがフィルタリング済みデータを返すため、そのまま利用

    // フィルタリング後にデータが空だった場合のメッセージ
    if (empty($filtered_data)) {
        // 商品IDまたは商品名が入力されており、かつデータが見つからない場合
        if (($product_id_input !== '' || $product_name_input !== '')) {
             $message = '指定された商品IDまたは商品名に一致するデータが見つかりません、または指定されたその他の条件に一致するデータがありません。';
        } else {
            // 商品IDも商品名も入力されていないが、他の条件でデータがない場合
            $message = '指定された条件に一致するデータがありません。';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>集計作成</title>
    <link rel="stylesheet" href="../css/Syukei.css">
</head>
<body>
    <div class="container">
        <h1>集計 作成</h1>

        <form action="Syukei.php" method="get">
            <div class="form-section">
                <label for="start_date">期間</label>
                <div class="input-group">
                    <input type="date" id="start_date" name="start_date" value="<?php echo htmlspecialchars($start_date); ?>">
                    <span> ~ </span>
                    <input type="date" id="end_date" name="end_date" value="<?php echo htmlspecialchars($end_date); ?>">
                </div>

                <label for="store">店舗</label>
                <div class="input-group">
                    <select id="store" name="store">
                        <?php foreach ($stores as $key => $name): ?>
                            <option value="<?php echo htmlspecialchars($key); ?>" <?php if ($store === $key) echo 'selected'; ?>>
                                <?php echo htmlspecialchars($name); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <label for="productID">商品ID</label>
                <div class="input-group">
                    <input type="text" id="productIDInput" name="productID" value="<?php echo htmlspecialchars($product_id_input); ?>" placeholder="商品IDを入力">
                </div>

                <label for="product">商品</label>
                <div class="input-group">
                    <input type="text" id="productNameInput" name="product" value="<?php echo htmlspecialchars($product_name_input); ?>" placeholder="商品名を入力">
                </div>

                <label for="payment_method">支払方法</label>
                <div class="input-group">
                    <select id="payment_method" name="payment_method">
                        <?php foreach ($payment_methods as $key => $name): ?>
                            <option value="<?php echo htmlspecialchars($key); ?>" <?php if ($payment_method === $key) echo 'selected'; ?>>
                                <?php echo htmlspecialchars($name); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="button-group">
                    <button type="submit" class="submit-button">集計</button>
                </div>
            </div>
        </form>

        <div class="result-area">
            <?php if (!empty($message)): ?>
                <p class="message"><?php echo htmlspecialchars($message); ?></p>
            <?php elseif (empty($filtered_data)): ?>
                <!-- メッセージが空でフィルタリングデータも空の場合のみ表示される -->
                <p class="no-data">指定された条件に一致するデータがありません。</p>
            <?php else: ?>
                <table class="result-table">
                    <thead>
                        <tr>
                            <th>日付</th>
                            <th>店舗</th>
                            <th>商品ID</th>
                            <th>商品</th>
                            <th>数量</th>
                            <th>売上金額(円)</th>
                            <th>支払方法</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($filtered_data as $item): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($item['date']); ?></td>
                                <td><?php echo htmlspecialchars($item['store_name']); ?></td>
                                <td><?php echo htmlspecialchars($item['productID']); ?></td>
                                <td><?php echo htmlspecialchars($item['product']); ?></td>
                                <td><?php echo htmlspecialchars((string)$item['quantity']); ?></td>
                                <td><?php echo number_format((int)$item['sales']); ?></td>
                                <td><?php echo htmlspecialchars($item['payment_name']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>

        <div class="footer-buttons">
            <button type="button" class="cancel-button" onclick="history.back()">キャンセル</button>
            <div class="graph-button-wrapper">
                <button type="button" class="graph-button">グラフ化</button>
            </div>
        </div>
    </div>
    
<script>
// 商品名自動補完
document.addEventListener('DOMContentLoaded', function() {
    const productIDInput = document.getElementById('productIDInput');
    const productNameInput = document.getElementById('productNameInput');

    if (productIDInput && productNameInput) {
        productIDInput.addEventListener('input', function() {
            const productID = this.value.trim();

            if (productID.length > 0) {
                // pages/get_product_name.php につなぐ
                fetch(`get_product_name.php?productID=${encodeURIComponent(productID)}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.product_name) {
                            productNameInput.value = data.product_name;
                        } else {
                            productNameInput.value = '';
                        }
                    })
                    .catch(error => {
                        console.error('商品名の取得中にエラーが発生しました:', error);
                        productNameInput.value = '';
                    });
            } else {
                productNameInput.value = '';
            }
        });
    }
});

//グラフ化画面
document.addEventListener('DOMContentLoaded', function() {
  const graphBtn = document.querySelector('.graph-button');
  // 検索フォーム
  const searchForm = document.querySelector('form[action="Syukei.php"][method="get"]');
  if (graphBtn && searchForm) {
    graphBtn.addEventListener('click', function() {
      const params = new URLSearchParams(new FormData(searchForm));
      // Graph.php に同条件で遷移
      window.location.href = 'Graph.php?' + params.toString();
    });
  }
});
</script>
</body>
</html>