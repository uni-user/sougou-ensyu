<?php
// /app/Business/MasterBusiness.php
require_once __DIR__ . '/../DataAccess/MasterData.php';

class MasterBusiness {
    private MasterData $dao;
    private string $table;
    private string $primaryKey;

    public function __construct(string $table, string $primaryKey) {
        $this->dao        = new MasterData();
        $this->table      = $table;
        $this->primaryKey = $primaryKey;
    }

    // ===== 条件検索（完全一致） =====
    public function search(array $conditions = []): array {
        return $this->dao->select($this->table, $conditions);
    }

    // ===== LIKE検索＋並び順＋ページング =====
    public function searchWithLike(array $conditions = [], array $likeCols = [], array $order = [], int $limit = 0, int $offset = 0): array {
        return $this->dao->searchWithLike($this->table, $conditions, $likeCols, $order, $limit, $offset);
    }

    // ===== 件数取得（LIKE対応） =====
    public function countByConditions(array $conditions = [], array $likeCols = []): int {
        return $this->dao->countByConditions($this->table, $conditions, $likeCols);
    }

    // ===== 新規/更新 =====
 public function insertUpdate(array $data): int {
    // カラム情報を取得
    $columns = $this->dao->getTableSchema($this->table);

    foreach ($data as $col => $val) {
        if (!isset($columns[$col])) continue;

        $type = $columns[$col]['type'];

        if (!$this->validateType($type, $val)) {
            throw new Exception("「{$col}」の値「{$val}」は正しい形式ではありません。");
        }
    }

    return $this->dao->save($this->table, $this->primaryKey, $data);
}

/**
 * SQL Server 用の型チェック関数
 */
private function validateType(string $type, $value): bool {
    if ($value === '' || $value === null) return true;

    switch (true) {
        case preg_match('/int|smallint|tinyint|bigint/', $type):
            return is_numeric($value);

        case preg_match('/decimal|numeric|money|float|real/', $type):
            return is_numeric($value);

        case preg_match('/char|text|nchar|nvarchar|varchar/', $type):
            return is_string($value);

        case preg_match('/date|time|datetime|smalldatetime/', $type):
            return (bool)strtotime($value);

        case preg_match('/bit/', $type):
            return in_array($value, [0, 1, '0', '1', true, false], true);

        default:
            return true;
    }
}


    // ===== 削除 =====
    public function delete(int $id): bool {
        return $this->dao->delete($this->table, $this->primaryKey, $id);
    }

    // ===== 最大ID取得 =====
    public function getMaxId(): int {
        return $this->dao->getMaxId($this->table, $this->primaryKey);
    }
}
