<?php
// /app/DataAccess/UriageData.php

class UriageData
{
    private PDO $db;

    public function __construct()
    {
        // SQL Server 接続設定（LoginData と同様）
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
     * 件数を取得
     */
    public function countByConditions(array $conditions, array $likeCols = []): int
    {
        $sql = "SELECT COUNT(*) FROM sales s 
                INNER JOIN stores st ON s.store_id = st.store_id 
                INNER JOIN products p ON s.product_id = p.product_id 
                INNER JOIN users u ON s.created_by = u.user_id 
                WHERE 1=1";
        $params = [];

        foreach ($conditions as $col => $val) {
            if ($val === '' || $val === null) continue;

            if (in_array($col, $likeCols, true)) {
                $sql .= " AND {$col} LIKE :{$col}";
                $params[":{$col}"] = "%{$val}%";
            } elseif (strpos($col, '>=') !== false || strpos($col, '<=') !== false) {
                [$realCol, $op] = explode(' ', $col);
                $sql .= " AND {$realCol} {$op} :{$realCol}_param";
                $params[":{$realCol}_param"] = $val;
            } else {
                $sql .= " AND {$col} = :{$col}";
                $params[":{$col}"] = $val;
            }
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return (int)$stmt->fetchColumn();
    }

    /**
     * 売上データを取得（LIKE検索 + ページング + 並び順）
     */
    public function searchWithLike(
        array $conditions,
        array $likeCols = [],
        array $orderBy = ['s.date DESC'],
        int $limit = 20,
        int $offset = 0
    ): array {
        $sql = "
            SELECT 
                s.sales_id,
                s.store_id,
                st.store_name,
                s.register_no,
                s.date,
                s.product_id,
                p.product_name,
                s.quantity,
                s.amount,
                s.payment_method,
                u.user_name AS created_by_name,
                s.created_at
            FROM sales s
            INNER JOIN stores st ON s.store_id = st.store_id
            INNER JOIN products p ON s.product_id = p.product_id
            INNER JOIN users u ON s.created_by = u.user_id
            WHERE 1=1
        ";

        $params = [];

        foreach ($conditions as $col => $val) {
            if ($val === '' || $val === null) continue;

            if (in_array($col, $likeCols, true)) {
                $sql .= " AND {$col} LIKE :{$col}";
                $params[":{$col}"] = "%{$val}%";
            } elseif (strpos($col, '>=') !== false || strpos($col, '<=') !== false) {
                [$realCol, $op] = explode(' ', $col);
                $sql .= " AND {$realCol} {$op} :{$realCol}_param";
                $params[":{$realCol}_param"] = $val;
            } else {
                $sql .= " AND {$col} = :{$col}";
                $params[":{$col}"] = $val;
            }
        }

        if (!empty($orderBy)) {
            $sql .= " ORDER BY " . implode(', ', $orderBy);
        }

        // SQL Server 用ページング
        $sql .= " OFFSET :offset ROWS FETCH NEXT :limit ROWS ONLY";

        $stmt = $this->db->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v, PDO::PARAM_STR);
        }
        $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
        $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);

        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
