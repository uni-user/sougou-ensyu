<?php
class AlertData
{
    private PDO $pdo;

    public function __construct()
    {
        // SQL Server 接続情報を設定
        $server   = 'VRT-DB-SQL2022';
        $database = 'TRAINING';
        $user     = 'new_employee';
        $pass     = 'HSyQhbmx7U';

        $dsn = "sqlsrv:Server=$server;Database=$database";
        $this->pdo = new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::SQLSRV_ATTR_ENCODING    => PDO::SQLSRV_ENCODING_UTF8,
        ]);
    }

    /**
     * 指定年月の日別未入力店舗を取得
     *
     * @param int $year
     * @param int $month
     * @return array
     */
    public function fetchUnreportedStoresByDay(int $year, int $month): array
    {
        // 対象月の初日と最終日を計算
        $firstDay = new DateTime("$year-$month-01");
        $lastDay  = clone $firstDay;
        $lastDay->modify('last day of this month');

        $period = new DatePeriod($firstDay, new DateInterval('P1D'), $lastDay->modify('+1 day'));

        $result = [];

        // 全店舗取得
        $stmtStores = $this->pdo->query("SELECT store_id, store_name FROM stores");
        $stores = $stmtStores->fetchAll(PDO::FETCH_ASSOC);

        foreach ($period as $date) {
            $dayKey = $date->format('d');
            $result[$dayKey] = [];

            foreach ($stores as $store) {
                // その日、その店舗の売上があるか確認
                $stmt = $this->pdo->prepare("
                SELECT 1
                FROM sales
                WHERE store_id = :store_id AND date = :the_date
            ");
                $stmt->execute([
                    ':store_id' => $store['store_id'],
                    ':the_date' => $date->format('Y-m-d')
                ]);
                if ($stmt->fetch() === false) {
                    $result[$dayKey][] = [
                        'store_id'   => $store['store_id'],
                        'store_name' => $store['store_name']
                    ];
                }
            }
        }

        return $result;
    }
}
