<?php
declare(strict_types=1);

require_once __DIR__ . '/../DataAccess/SyukeiData.php';

class SyukeiBusiness {
    private SyukeiData $data;

    public function __construct(SyukeiData $data) {
        $this->data = $data;
    }

    /**
     * 指定されたランキングタイプに基づいてデータを取得します。
     * @param string $rankingType ランキングの種類 ('store', 'product', 'date')
     * @return array 取得したデータ配列とランキングタイプ
     */
    public function getRanking(string $rankingType): array {
        $rows = [];
        $actualRankingType = $rankingType; // 実際に処理したランキングタイプを保持

        switch ($rankingType) {
            case 'store':
                $rows = $this->data->fetchStoreRanking();
                break;
            case 'product':
                $rows = $this->data->fetchProductRanking();
                break;
            case 'date':
                $rows = $this->data->fetchDateRanking();
                break;
            default:
                // 未知のタイプが指定された場合、デフォルトで商品別ランキングを表示
                $rows = $this->data->fetchProductRanking();
                $actualRankingType = 'product';
                break;
        }
        // errorsは今回のケースでは発生しないが、Ranking.phpとの整合性のため空配列を返却
        return ['errors' => [], 'rows' => $rows, 'ranking_type' => $actualRankingType];
    }
}