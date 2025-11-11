<?php
// DataAccess/Syukeidata.php
declare(strict_types=1);

class Syukeidata {
    private PDO $db;

    // データベース接続情報の定数（本来は外部設定ファイルから読み込むべき）
    private const DB_SERVER   = 'VRT-DB-SQL2022';
    private const DB_DATABASE = 'TRAINING';
    private const DB_USER     = 'new_employee';
    private const DB_PASS     = 'HSyQhbmx7U';

    /**
     * コンストラクタ
     * データベースへの接続を確立します。
     */
    public function __construct() {
        $dsn = "sqlsrv:Server=" . self::DB_SERVER . ";Database=" . self::DB_DATABASE;
        try {
            $this->db = new PDO($dsn, self::DB_USER, self::DB_PASS, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::SQLSRV_ATTR_ENCODING    => PDO::SQLSRV_ENCODING_UTF8,
            ]);
        } catch (PDOException $e) {
            // 本番環境ではエラーメッセージをそのまま表示せず、ログに記録するなど適切な処理をしてください。
            die("データベース接続エラー: " . $e->getMessage());
        }
    }

    /**
     * 集計データを取得するメソッド
     *
     * @param array $filters 検索フィルターの連想配列:
     *     'start_date'         => 開始日 (YYYY-MM-DD)
     *     'end_date'           => 終了日 (YYYY-MM-DD)
     *     'store_key'          => 店舗キー ('honbu', 'shinjuku' など)
     *     'product_id'         => 商品ID (厳密一致)
     *     'product_name'       => 商品名 (部分一致)
     *     'payment_method_key' => 支払方法キー ('cash', 'credit' など)
     * @return array 取得したデータ（連想配列の配列）
     */
    public function getSalesData(array $filters = []): array {
        // dbo.sales, dbo.products, dbo.stores をJOINしてデータを取得
        // Syukei.phpの表示ロジックと合うようにカラム名をエイリアス
        $sql = "
            SELECT
                CONVERT(VARCHAR(10), s.date, 120) AS date,      -- YYYY-MM-DD形式に変換
                s.store_id,
                ISNULL(st.store_name, N'不明な店舗') AS store_name, -- 店舗マスタから店舗名を取得、無ければ「不明な店舗」
                s.product_id AS productID,                          -- salesテーブルのproduct_idをproductIDとして返す
                ISNULL(p.product_name, N'不明な商品') AS product,   -- productマスタから商品名を取得、無ければ「不明な商品」
                s.quantity,
                s.amount AS sales,                                  -- amountカラムをsalesとして返す
                s.payment_method AS payment_name                    -- payment_methodカラムをpayment_nameとして返す
            FROM
                dbo.sales AS s
            LEFT JOIN
                dbo.products AS p ON s.product_id = p.product_id -- 商品マスタとのJOIN
            LEFT JOIN
                dbo.stores AS st ON s.store_id = st.store_id     -- 店舗マスタとのJOIN
            WHERE 1 = 1
        ";

        $params = [];

        // 期間フィルター
        if (!empty($filters['start_date'])) {
            $sql .= " AND s.date >= :start_date";
            $params[':start_date'] = $filters['start_date'];
        }
        if (!empty($filters['end_date'])) {
            // 終了日当日を含めるために翌日未満にする（DATETIME対応）
            $sql .= " AND s.date < DATEADD(DAY, 1, :end_date)";
            $params[':end_date'] = $filters['end_date'];
        }

        // 店舗フィルター (Syukei.phpのキーからDBのstore_idに変換して適用)
        if (!empty($filters['store_key']) && $filters['store_key'] !== 'all') {
            $db_store_id = $this->getStoreIdByKey($filters['store_key']);
            if ($db_store_id !== null) {
                $sql .= " AND s.store_id = :store_id";
                $params[':store_id'] = $db_store_id;
            } else {
                // マッピングに存在しないストアキーが指定された場合、データを返さないようにする
                return [];
            }
        }

        // 商品IDフィルター (厳密一致)
        if (!empty($filters['product_id'])) {
            // product_idは数値型であり、厳密一致が通常好ましいため '=' を使用
            $sql .= " AND s.product_id = :product_id";
            $params[':product_id'] = $filters['product_id'];
        }

        // 商品名フィルター (部分一致)
        if (!empty($filters['product_name'])) {
            $sql .= " AND p.product_name LIKE :product_name"; // 商品マスタのproduct_nameを使用
            $params[':product_name'] = '%' . $filters['product_name'] . '%';
        }

        // 支払方法フィルター (Syukei.phpのキーからDBの日本語名に変換して適用)
        if (!empty($filters['payment_method_key']) && $filters['payment_method_key'] !== 'all') {
            $db_payment_name = $this->getPaymentMethodNameByKey($filters['payment_method_key']);
            if ($db_payment_name !== null) {
                $sql .= " AND s.payment_method = :payment_method_name";
                $params[':payment_method_name'] = $db_payment_name;
            } else {
                // マッピングに存在しない支払方法キーが指定された場合、データを返さないようにする
                return [];
            }
        }

        // 任意の並び順
        $sql .= " ORDER BY date ASC, store_id ASC, productID ASC";

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("集計データ取得エラー: " . $e->getMessage()); // エラーログに記録
            return []; // エラー時は空の配列を返す
        }
    }

    /**
     * 店舗キーからDBのstore_idを取得する補助メソッド
     * @param string $key 'honbu', 'shinjuku' など
     * @return int|null DBのstore_id、または見つからない場合はnull
     */
    public function getStoreIdByKey(string $key): ?int {
        // dbo.storesテーブルのデータに基づいてマッピングを更新
        $store_map = [
            'honbu'     => 101, // 本部
            'shinjuku'  => 102, // 新宿本店
            'aomori'    => 103, // 青森店
            'hokkaido'  => 104, // 北海道店
            'shizuoka'  => 105, // 静岡店
            'nagoya'    => 106, // 名古屋店
        ];
        return $store_map[$key] ?? null;
    }

    /**
     * 支払方法キー ('cash'など) からDBの日本語名を取得する補助メソッド
     * @param string $key 'cash', 'credit' など
     * @return string|null DBの日本語名、または見つからない場合はnull
     */
    public function getPaymentMethodNameByKey(string $key): ?string {
        $payment_map = [
            'all'    => '全ての支払方法', // 'all' は実際にはフィルタリングしないが、一覧には含める
            'cash'   => '現金',
            'credit' => 'クレジットカード',
            'qr'     => 'QRコード決済',
        ];
        return $payment_map[$key] ?? null;
    }

    /**
     * 商品IDから商品名を取得するメソッド (get_product_name.php用)
     * dbo.products テーブルから product_name を取得します。
     *
     * @param string $product_id 商品ID
     * @return string 商品名、見つからない場合は空文字列
     */
    public function getProductNameById(string $product_id): string {
        $sql = "SELECT product_name FROM dbo.products WHERE product_id = :product_id";
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':product_id' => $product_id]);
            $result = $stmt->fetchColumn();
            return $result ?: '';
        } catch (PDOException $e) {
            error_log("商品名取得エラー: " . $e->getMessage());
            return '';
        }
    }
}