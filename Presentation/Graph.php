<?php
// /app/pages/Graph.php
declare(strict_types=1);

require_once __DIR__ . '/../DataAccess/Syukeidata.php';

// 既定日付
$today     = date('Y-m-d');
$yesterday = date('Y-m-d', strtotime('-1 day'));

// 集計画面と同じクエリキーで受け取る
$start_date         = isset($_GET['start_date']) ? trim($_GET['start_date']) : $yesterday;
$end_date           = isset($_GET['end_date'])   ? trim($_GET['end_date'])   : $today;
$store              = isset($_GET['store'])      ? trim($_GET['store'])      : 'all';
$product_id_input   = isset($_GET['productID'])  ? trim($_GET['productID'])  : '';
$product_name_input = isset($_GET['product'])    ? trim($_GET['product'])    : '';
$payment_method     = isset($_GET['payment_method']) ? trim($_GET['payment_method']) : 'all';

// 表示用ラベル（Syukei.php と同一）
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

// バリデーション
$errors = [];
$reDate = '/^\d{4}-\d{2}-\d{2}$/';
if (!preg_match($reDate, $start_date)) $errors[] = '開始日が不正です（YYYY-MM-DD形式）';
if (!preg_match($reDate, $end_date))   $errors[] = '終了日が不正です（YYYY-MM-DD形式）';
if (!$errors && strtotime($start_date) > strtotime($end_date)) $errors[] = '開始日は終了日以前にしてください';
if ($product_id_input === '' && $product_name_input === '') {
    $errors[] = '商品名または商品IDが未入力です。グラフ化するにはいずれかを入力してください。';
}

$filtered_data = [];
$message = '';
if ($errors) {
    $message = implode(' / ', $errors);
} else {
    $filters = [
        'start_date'         => $start_date,
        'end_date'           => $end_date,
        'store_key'          => $store,
        'product_id'         => $product_id_input,
        'product_name'       => $product_name_input,
        'payment_method_key' => $payment_method,
    ];

    $syukei = new Syukeidata();
    $filtered_data = $syukei->getSalesData($filters);
    
    if (empty($filtered_data)) {
        $message = '指定条件に一致するデータが見つかりません。';
    }

// グルーピング（合計：売上金額／数量）
function groupByDate(array $rows): array {
    $result = [];
    foreach ($rows as $r) {
        $key = (string)$r['date'];
        if (!isset($result[$key])) $result[$key] = ['sales'=>0,'qty'=>0];
        $result[$key]['sales'] += (int)$r['sales'];
        $result[$key]['qty']   += (int)$r['quantity'];
    }
    ksort($result);
    return $result;
}
function groupByStore(array $rows): array {
    $result = [];
    foreach ($rows as $r) {
        $key = (string)$r['store_name'];
        if (!isset($result[$key])) $result[$key] = ['sales'=>0,'qty'=>0];
        $result[$key]['sales'] += (int)$r['sales'];
        $result[$key]['qty']   += (int)$r['quantity'];
    }
    ksort($result);
    return $result;
}
function groupByPayment(array $rows): array {
    $result = [];
    foreach ($rows as $r) {
        $key = (string)$r['payment_name'];
        if (!isset($result[$key])) $result[$key] = ['sales'=>0,'qty'=>0];
        $result[$key]['sales'] += (int)$r['sales'];
        $result[$key]['qty']   += (int)$r['quantity'];
    }
    ksort($result);
    return $result;
}

// 初期集計
$dateAgg  = groupByDate($filtered_data);
$storeAgg = groupByStore($filtered_data);
$payAgg   = groupByPayment($filtered_data);

// Chart.jsへ渡すペイロード
$payload = [
    'date'  => $dateAgg,
    'store' => $storeAgg,
    'pay'   => $payAgg,
    'meta'  => [
        'start_date' => $start_date,
        'end_date'   => $end_date,
        'store'      => $store,
        'productID'  => $product_id_input,
        'product'    => $product_name_input,
        'payment_method' => $payment_method,
    ]
];
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="UTF-8">
<title>グラフ表示</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="stylesheet" href="../css/Graph.css">
<link rel="stylesheet" href="../css/Syukei.css">
</head>
<body>
<div class="container">
  <h1>グラフ表示</h1>

  <?php if (!empty($message)): ?>
    <div style="color:#b00020; margin-bottom:10px;"><?= htmlspecialchars($message) ?></div>
  <?php else: ?>
    <div class="condition-summary">
      期間: <?= htmlspecialchars($start_date) ?> 〜 <?= htmlspecialchars($end_date) ?>
      ／ 店舗: <?= htmlspecialchars($stores[$store] ?? $store) ?>
      ／ 商品ID: <?= htmlspecialchars($product_id_input ?: '未指定') ?>
      ／ 商品: <?= htmlspecialchars($product_name_input ?: '未指定') ?>
      ／ 支払方法: <?= htmlspecialchars($payment_methods[$payment_method] ?? $payment_method) ?>
    </div>

    <div class="toolbar">
  <label>集計軸
    <select id="axisSelect">
      <option value="date">期間別（日付）</option>
      <option value="store">店舗別</option>
      <option value="pay">支払方法別</option>
    </select>
  </label>
  <!-- ★ここを修正しました: [last_name]タイプ と [name] を適切なテキストに置き換え -->
  <label>グラフタイプ
    <select id="typeSelect">
      <option value="bar">棒グラフ</option>
      <option value="line">折れ線グラフ</option>
      <option value="pie">円グラフ</option>
    </select>
  </label>
  <label>指標
    <select id="metricSelect">
      <option value="sales">売上金額</option>
      <option value="qty">数量</option>
    </select>
  </label>
  <button type="button" class="fn-btn" id="backBtn">集計へ戻る</button>
  <button type="button" class="fn-btn" id="downloadBtn">画像ダウンロード</button>
</div>

<div class="chart-wrap">
  <canvas id="chartCanvas"></canvas> <!-- height="120" は削除済みであることを確認 -->
  <div class="legend-note">棒・折れ線は「売上金額／数量」を選択できます。円グラフは選択した指標で比率表示します。</div>
</div>
  <?php endif; ?>

  <!-- Function Key（ランキング画面） -->
  <div class="footer" id="footerButtons">
    <!-- F2: 集計画面へ戻って検索 -->
    <form method="get" id="f2Form" action="Syukei.php">
      <input type="hidden" name="start_date" value="<?= htmlspecialchars($start_date) ?>">
      <input type="hidden" name="end_date"   value="<?= htmlspecialchars($end_date) ?>">
      <input type="hidden" name="store"      value="<?= htmlspecialchars($store) ?>">
      <input type="hidden" name="productID"  value="<?= htmlspecialchars($product_id_input) ?>">
      <input type="hidden" name="product"    value="<?= htmlspecialchars($product_name_input) ?>">
      <input type="hidden" name="payment_method" value="<?= htmlspecialchars($payment_method) ?>">
    </form>


<!-- Chart.js CDN -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
// PHPから受け取ったデータをJSへ
const payload = <?= json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;

const axisSelect   = document.getElementById('axisSelect');
const typeSelect   = document.getElementById('typeSelect');
const metricSelect = document.getElementById('metricSelect');
const backBtn      = document.getElementById('backBtn');
const downloadBtn  = document.getElementById('downloadBtn');

const canvas = document.getElementById('chartCanvas');
const ctx = canvas ? canvas.getContext('2d') : null;
let chart = null;

// 円グラフ用の色のパレットを定義
const chartColorPalette = [
    '#f7a87aff', // オレンジ (元々の色)
    '#a8d0b2',   // 緑系
    '#c2e0ef',   // 青系
    '#efc2c2',   // 赤系
    '#e2a0f0',   // 紫系
    '#ffcc99',   // 薄めのオレンジ
    '#ccff99',   // 黄緑系
    '#99ccff',   // 水色系
    '#f0e68c',   // カーキ色
    '#deb887',   // タン色
    '#b0c4de',   // ライトスチールブルー
    '#ffe4e1',   // ミスティローズ
    '#d8bfd8',   // スルタンのラベンダー
    '#add8e6',   // ライトブルー
    '#ffe0b2'    // モカシン
];

// ホバー時の色のパレット (少し濃いめに調整)
const chartHoverColorPalette = [
    '#f0db66',   // オレンジ (ホバー時)
    '#95c0a0',   // 緑系 (ホバー時)
    '#add8e6',   // 青系 (ホバー時)
    '#e5b0b0',   // 赤系 (ホバー時)
    '#d6a0e0',   // 紫系 (ホバー時)
    '#eebb88',   // 薄めのオレンジ (ホバー時)
    '#b0ee88',   // 黄緑系 (ホバー時)
    '#88b0ee',   // 水色系 (ホバー時)
    '#e0d77c',   // カーキ色 (ホバー時)
    '#d2a877',   // タン色 (ホバー時)
    '#9fb0d0',   // ライトスチールブルー (ホバー時)
    '#fbcbca',   // ミスティローズ (ホバー時)
    '#c7a8c7',   // スルタンのラベンダー (ホバー時)
    '#97c8d9',   // ライトブルー (ホバー時)
    '#eecfa2'    // モカシン (ホバー時)
];


function extract(axis, metric) {
  const obj = payload[axis] || {};
  const labels = Object.keys(obj);
  const values = labels.map(k => obj[k]?.[metric] ?? 0);
  return { labels, values };
}
function fmtYen(n) {
  return new Intl.NumberFormat('ja-JP').format(n);
}
function render() {
  if (!ctx) return;
  const axis   = axisSelect.value;
  const type   = typeSelect.value;
  const metric = metricSelect.value;

  const { labels, values } = extract(axis, metric);

  const options = {
    responsive: true,
    plugins: {
      legend: { display: type === 'pie' },
      tooltip: {
        callbacks: {
          label: (ctx) => {
            const v = ctx.parsed;
            if (metric === 'sales') return `${ctx.label}: ¥${fmtYen(v)}`;
            return `${ctx.label}: ${v} 個`;
          }
        }
      }
    },
    scales: (type === 'pie') ? {} : {
      y: {
        beginAtZero: true,
        ticks: {
          callback: (v) => metric === 'sales' ? '¥' + fmtYen(v) : v + '個'
        }
      }
    }
  };
  const datasetLabel = (metric === 'sales') ? '売上金額（円）' : '数量';
  const data = {
    labels,
    datasets: [{
      label: datasetLabel,
      data: values,
      // `borderColor`
      borderColor: '#acabaaff',
      // `backgroundColor`
      backgroundColor: type === 'pie'
        ? labels.map((_, index) => chartColorPalette[index % chartColorPalette.length])
        : '#f7e57a', // 棒・折れ線は単色
      // `hoverBackgroundColor`も同様に円グラフの場合に色の配列を適用し、棒・折れ線は単色
      hoverBackgroundColor: type === 'pie'
        ? labels.map((_, index) => chartHoverColorPalette[index % chartHoverColorPalette.length])
        : '#f0db66', // 棒・折れ線は単色
      tension: 0.2
    }]
  };
  if (chart) chart.destroy();
  chart = new Chart(ctx, { type, data, options });
}
// 初期描画（エラー時はキャンバスが無いので何もしない）
if (ctx) render();

// 切替イベント
axisSelect?.addEventListener('change', render);
typeSelect?.addEventListener('change', render);
metricSelect?.addEventListener('change', render);

// 戻る（同条件で Syukei.php へ）
backBtn?.addEventListener('click', () => {
  const q = new URLSearchParams(payload.meta);
  window.location.href = 'Syukei.php?' + q.toString();
});

// 画像ダウンロード
downloadBtn?.addEventListener('click', () => {
  if (!canvas) return;
  const a = document.createElement('a');
  a.href = canvas.toDataURL('image/png');
  const axis = axisSelect.value;
  const type = typeSelect.value;
  const metric = metricSelect.value;
  a.download = `graph_${axis}_${type}_${metric}.png`;
  a.click();
});
</script>
</body>
</html>