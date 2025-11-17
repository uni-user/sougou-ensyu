<?php
require_once __DIR__ . '/../Business/AlertBusiness.php';

$service = new AlertBusiness();

// GET パラメータ取得
$year  = isset($_GET['year']) ? (int)$_GET['year'] : null;
$month = isset($_GET['month']) ? (int)$_GET['month'] : null;

// データ取得
$result = $service->getUnreportedStoresByDay($year, $month);
$year  = $result['year'];
$month = $result['month'];
$data  = $result['data'];

// 前月・翌月計算
$dt   = DateTime::createFromFormat('Y-n-d', "{$year}-{$month}-1");
$prev = (clone $dt)->modify('-1 month');
$next = (clone $dt)->modify('+1 month');
?>
<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <title>未入力店舗一覧</title>

    <!-- 統一デザインCSS -->
    <link rel="stylesheet" href="../css/alert.css">

    <script>
        // ファンクションキー操作
        document.addEventListener('keydown', function(e) {
            if (e.isComposing) return;

            switch (e.key) {
                case 'F5': // 前月
                    e.preventDefault();
                    location.href = "?year=<?= $prev->format('Y') ?>&month=<?= $prev->format('n') ?>";
                    break;

                case 'F6': // 翌月
                    e.preventDefault();
                    location.href = "?year=<?= $next->format('Y') ?>&month=<?= $next->format('n') ?>";
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

        <h1><?= htmlspecialchars($year) ?>年<?= htmlspecialchars($month) ?>月の日別売上未入力店舗</h1>

        <div class="nav-month">
            <a href="?year=<?= $prev->format('Y') ?>&month=<?= $prev->format('n') ?>">F5<br>← 前月</a>
            <a href="?year=<?= $next->format('Y') ?>&month=<?= $next->format('n') ?>">F6<br>翌月 →</a>
        </div>

        <table class="alert-table">
            <tr>
                <th>日付</th>
                <th>未入力店舗</th>
            </tr>

            <?php
            $lastDay = cal_days_in_month(CAL_GREGORIAN, $month, $year);

            for ($d = 1; $d <= $lastDay; $d++):
                $dayKey = str_pad($d, 2, '0', STR_PAD_LEFT);
                $stores = $data[$dayKey] ?? [];
            ?>
                <tr>
                    <td><?= $d ?>日</td>
                    <td>
                        <?php if (empty($stores)): ?>
                            <span class="no-miss">全店舗入力済</span>
                        <?php else: ?>
                            <?php foreach ($stores as $s): ?>
                                <?= htmlspecialchars($s['store_name']) ?><br>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endfor; ?>
        </table>

        <div class="button-group">
            <button type="button" onclick="location.href='Menu.php'">F12<br>戻る</button>
        </div>
    </div>

</body>

</html>