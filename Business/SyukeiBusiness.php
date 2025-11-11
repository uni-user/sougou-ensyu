<?php
// /app/Business/RankingBusiness.php
declare(strict_types=1);

class RankingBusiness {
    private RankingData $data;

    public function __construct(RankingData $data) {
        $this->data = $data;
    }

    // 入力日付の検証とランキング取得
    public function getRanking(string $startDate, string $endDate): array {
        $errors = [];
        if (!$this->isValidDate($startDate)) {
            $errors[] = '開始日は YYYY-MM-DD 形式で入力してください。';
        }
        if (!$this->isValidDate($endDate)) {
            $errors[] = '終了日は YYYY-MM-DD 形式で入力してください。';
        }
        if ($errors) {
            return ['errors' => $errors, 'rows' => []];
        }
        $start = $startDate . ' 00:00:00';
        $end   = $endDate   . ' 23:59:59';
        $rows  = $this->data->fetchRanking($start, $end);
        return ['errors' => [], 'rows' => $rows];
    }

    private function isValidDate(string $d): bool {
        $dt = DateTime::createFromFormat('Y-m-d', $d);
        return $dt && $dt->format('Y-m-d') === $d;
    }
}