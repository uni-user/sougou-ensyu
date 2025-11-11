<?php
// /app/DataAccess/RankingData.php
declare(strict_types=1);

class RankingData {
    private PDO $db;

    public function __construct() {
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
     * 商品別売上金額ランキングを取得します。
     * @return array ランキングデータ
     */
    public function fetchProductRanking(): array {
        $sql = "
            SELECT
                p.product_name,
                SUM(s.amount) AS total_amount
                FROM sales s
                INNER JOIN products p ON p.product_id = s.product_id
                GROUP BY s.product_id, p.product_name
                ORDER BY total_amount DESC, p.product_name ASC
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * 店舗別売上金額ランキングを取得します。
     * @return array ランキングデータ (store_id と total_amount を含む)
     */
public function fetchStoreRanking(): array {
    $sql = "
        SELECT
            st.store_name,
            SUM(s.amount) AS total_amount
            FROM sales s
            INNER JOIN stores st ON s.store_id = st.store_id
            GROUP BY st.store_id, st.store_name
            ORDER BY total_amount DESC, st.store_name ASC
    ";
    $stmt = $this->db->prepare($sql);
    $stmt->execute();
    return $stmt->fetchAll();
}

    /**
     * 日付別売上金額ランキングを取得します。
     * @return array ランキングデータ (sales_date と total_amount を含む)
     */
    public function fetchDateRanking(): array {
        $sql = "
            SELECT
                CONVERT(DATE, s.date) AS sales_date, -- sales.date がDATETIME型の場合
                SUM(s.amount) AS total_amount
            FROM sales s
            GROUP BY CONVERT(DATE, s.date)
            ORDER BY total_amount DESC, sales_date ASC
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }
}