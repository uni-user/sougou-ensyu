<?php
// /app/DataAccess/SyukeiData.php
declare(strict_types=1);

class SyukeiData
{
    private PDO $db;

    public function __construct()
    {
        $server   = 'VRT-DB-SQL2022';
        $database = 'TRAINING';
        $dsn      = "sqlsrv:Server=$server;Database=$database";
        $user     = 'new_employee';
        $pass     = 'HSyQhbmx7U';
        $this->db = new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::SQLSRV_ATTR_ENCODING    => PDO::SQLSRV_ENCODING_UTF8,
        ]);
    }

    /**
     * 商品別売上金額ランキング（期間対応）
     */
    public function fetchProductRanking(?string $start = null, ?string $end = null): array
    {
        // sales.date は DATETIME 前提。日付比較は CONVERT(date, s.date) を使用
        $where = '';
        $params = [];
        if ($start !== null && $end !== null) {
            $where = "WHERE CONVERT(date, s.date) BETWEEN :start AND :end";
            $params[':start'] = $start;
            $params[':end']   = $end;
        }

        $sql = "
            SELECT
                p.product_name,
                SUM(s.amount) AS total_amount
            FROM sales s
            INNER JOIN products p ON p.product_id = s.product_id
            $where
            GROUP BY s.product_id, p.product_name
            ORDER BY total_amount DESC, p.product_name ASC
        ";
        $stmt = $this->db->prepare($sql);
        foreach ($params as $k => $v) $stmt->bindValue($k, $v);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * 店舗別売上金額ランキング（期間対応）
     */
    public function fetchStoreRanking(?string $start = null, ?string $end = null): array
    {
        $where = '';
        $params = [];
        if ($start !== null && $end !== null) {
            $where = "WHERE CONVERT(date, s.date) BETWEEN :start AND :end";
            $params[':start'] = $start;
            $params[':end']   = $end;
        }

        $sql = "
            SELECT
                st.store_name,
                SUM(s.amount) AS total_amount
            FROM sales s
            INNER JOIN stores st ON s.store_id = st.store_id
            $where
            GROUP BY st.store_id, st.store_name
            ORDER BY total_amount DESC, st.store_name ASC
        ";
        $stmt = $this->db->prepare($sql);
        foreach ($params as $k => $v) $stmt->bindValue($k, $v);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * 日付別売上金額ランキング（期間対応）
     */
    public function fetchDateRanking(?string $start = null, ?string $end = null): array
    {
        $where = '';
        $params = [];
        if ($start !== null && $end !== null) {
            $where = "WHERE CONVERT(date, s.date) BETWEEN :start AND :end";
            $params[':start'] = $start;
            $params[':end']   = $end;
        }

        $sql = "
            SELECT
                CONVERT(date, s.date) AS sales_date,
                SUM(s.amount) AS total_amount
            FROM sales s
            $where
            GROUP BY CONVERT(date, s.date)
            ORDER BY total_amount DESC, sales_date ASC
        ";
        $stmt = $this->db->prepare($sql);
        foreach ($params as $k => $v) $stmt->bindValue($k, $v);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * 支払方法別売上金額ランキング（期間対応）
     */
    public function fetchPaymentRanking(?string $start = null, ?string $end = null): array
    {
        // 期間条件
        $where = '';
        $params = [];
        if ($start !== null && $end !== null) {
            $where = "WHERE CONVERT(date, s.date) BETWEEN :start AND :end";
            $params[':start'] = $start;
            $params[':end']   = $end;
        }

        $sql = "
        SELECT
            s.payment_method AS payment_name,
            SUM(s.amount) AS total_amount
        FROM sales s
        $where
        GROUP BY s.payment_method
        ORDER BY total_amount DESC, payment_name ASC
    ";

        $stmt = $this->db->prepare($sql);
        foreach ($params as $k => $v) $stmt->bindValue($k, $v);
        $stmt->execute();
        return $stmt->fetchAll();
    }
}
