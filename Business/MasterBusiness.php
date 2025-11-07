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
        return $this->dao->save($this->table, $this->primaryKey, $data);
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
