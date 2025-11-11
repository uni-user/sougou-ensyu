<?php
// /app/DataAccess/MasterData.php

class MasterData {
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

    // ===== 基本SELECT =====
    public function select(string $table, array $conditions = []): array {
        $sql = "SELECT * FROM {$table}";
        $params = [];
        if (!empty($conditions)) {
            $where = [];
            foreach ($conditions as $col => $val) {
                $where[] = "$col = :$col";
                $params[":$col"] = $val;
            }
            $sql .= " WHERE " . implode(' AND ', $where);
        }
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    // ===== 件数カウント（条件・LIKE対応） =====
    public function countByConditions(string $table, array $conditions = [], array $likeCols = []): int
    {
        $sql = "SELECT COUNT(1) AS cnt FROM {$table} WHERE 1=1";
        $params = [];

        foreach ($conditions as $col => $val) {
            if ($val === '' || $val === null) continue;

            if (in_array($col, $likeCols)) {
                $sql .= " AND {$col} LIKE :{$col}";
                $params[$col] = "%$val%";
            } else {
                $sql .= " AND {$col} = :{$col}";
                $params[$col] = $val;
            }
        }

        $stmt = $this->db->prepare($sql);
        foreach ($params as $key => $val) {
            $stmt->bindValue(":$key", $val, PDO::PARAM_STR);
        }
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int)($row['cnt'] ?? 0);
    }

    // ===== LIKE検索＋ORDER＋LIMIT/OFFSET =====
    public function searchWithLike(string $table, array $conditions = [], array $likeCols = [], array $order = [], int $limit = 0, int $offset = 0): array
    {
        $sql = "SELECT * FROM {$table} WHERE 1=1";
        $params = [];

        foreach ($conditions as $col => $val) {
            if ($val === '' || $val === null) continue;

            if (in_array($col, $likeCols)) {
                $sql .= " AND {$col} LIKE :{$col}";
                $params[$col] = "%$val%";
            } else {
                $sql .= " AND {$col} = :{$col}";
                $params[$col] = $val;
            }
        }

        if (!empty($order)) {
            $sql .= " ORDER BY " . implode(', ', $order);
        }

        if ($limit > 0) {
            $sql .= " OFFSET :offset ROWS FETCH NEXT :limit ROWS ONLY";
            $params['limit']  = $limit;
            $params['offset'] = $offset;
        }

        $stmt = $this->db->prepare($sql);
        foreach ($params as $key => $val) {
            if ($key === 'limit' || $key === 'offset') {
                $stmt->bindValue(":$key", $val, PDO::PARAM_INT);
            } else {
                $stmt->bindValue(":$key", $val, PDO::PARAM_STR);
            }
        }

        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // ===== INSERT/UPDATE =====
    public function save(string $table, string $primaryKey, array $data): int
    {
        if (isset($data[$primaryKey]) && $data[$primaryKey] !== '') {
            // 更新
            $id = $data[$primaryKey];
            unset($data[$primaryKey]);
            $set = [];
            foreach ($data as $col => $val) {
                $set[] = "$col = :$col";
            }
            $sql = "UPDATE {$table} SET " . implode(', ', $set) . " WHERE {$primaryKey} = :id";
            $data['id'] = $id;
            $stmt = $this->db->prepare($sql);
            $stmt->execute($data);
            return $id;
        } else {
            // 新規登録
            $cols = array_keys($data);
            $params = ':' . implode(',:', $cols);
            $sql = "INSERT INTO {$table} (" . implode(',', $cols) . ") VALUES ($params)";
            $stmt = $this->db->prepare($sql);
            $stmt->execute($data);
            $id = $this->db->query("SELECT CAST(SCOPE_IDENTITY() AS INT)")->fetchColumn();
            return (int)$id;
        }
    }

    // ===== DELETE =====
    public function delete(string $table, string $primaryKey, int $id): bool
    {
        $sql = "DELETE FROM {$table} WHERE {$primaryKey} = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);
        return $stmt->rowCount() > 0;
    }

    // ===== 最大ID取得 =====
    public function getMaxId(string $table, string $primaryKey): int
    {
        $sql = "SELECT ISNULL(MAX({$primaryKey}), 0) AS max_id FROM {$table}";
        $stmt = $this->db->query($sql);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int)$row['max_id'];
    }
}
