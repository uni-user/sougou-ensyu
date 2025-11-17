<?php
require_once __DIR__ . '/../DataAccess/AlertData.php';

class AlertBusiness
{
    private AlertData $data;

    public function __construct()
    {
        $this->data = new AlertData();
    }

    /**
     * 指定年月の日別未入力店舗を取得
     *
     * @param int|null $year
     * @param int|null $month
     * @return array ['year'=>int,'month'=>int,'data'=>array]
     */
    public function getUnreportedStoresByDay(?int $year, ?int $month): array
    {
        // 年月が未指定なら今月
        $now = new DateTime();
        if (!$year) {
            $year = (int)$now->format('Y');
        }
        if (!$month) {
            $month = (int)$now->format('n');
        }

        $data = $this->data->fetchUnreportedStoresByDay($year, $month);

        return [
            'year'  => $year,
            'month' => $month,
            'data'  => $data,
        ];
    }
}
