<?php

declare(strict_types=1);

require_once __DIR__ . '/../DataAccess/SyukeiData.php';

class SyukeiBusiness
{
    private SyukeiData $data;

    public function __construct(SyukeiData $data)
    {
        $this->data = $data;
    }

    /**
     * 指定されたランキングタイプに基づいてデータを取得します（期間対応）。
     * @param string      $rankingType 'store' | 'product' | 'date'
     * @param ?string     $start       YYYY-MM-DD または null
     * @param ?string     $end         YYYY-MM-DD または null
     * @return array      ['errors'=>array,'rows'=>array,'ranking_type'=>string]
     */
    public function getRanking(string $rankingType, ?string $start = null, ?string $end = null): array
    {
        $rows = [];
        $actualRankingType = in_array($rankingType, ['store', 'product', 'date', 'payment'], true) ? $rankingType : 'product';
        $errors = [];

        $dateRe = '/^\d{4}-\d{2}-\d{2}$/';
        if (($start !== null && !preg_match($dateRe, $start)) ||
            ($end   !== null && !preg_match($dateRe, $end))
        ) {
            $errors[] = '期間指定が不正です。';
            $start = $end = null;
        }

        switch ($actualRankingType) {
            case 'store':
                $rows = $this->data->fetchStoreRanking($start, $end);
                break;
            case 'product':
                $rows = $this->data->fetchProductRanking($start, $end);
                break;
            case 'date':
                $rows = $this->data->fetchDateRanking($start, $end);
                break;
            case 'payment':
                $rows = $this->data->fetchPaymentRanking($start, $end);
                break;
        }

        return ['errors' => $errors, 'rows' => $rows, 'ranking_type' => $actualRankingType];
    }
}
