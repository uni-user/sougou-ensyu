<?php
// /app/Business/UriageBusiness.php

require_once __DIR__ . '/../DataAccess/UriageData.php';

class UriageBusiness
{
    private UriageData $data;

    public function __construct()
    {
        $this->data = new UriageData();
    }

    public function countByConditions(array $conditions, array $likeCols = []): int
    {
        return $this->data->countByConditions($conditions, $likeCols);
    }

    public function searchWithLike(
        array $conditions,
        array $likeCols = [],
        array $orderBy = ['s.date DESC'],
        int $limit = 20,
        int $offset = 0
    ): array {
        return $this->data->searchWithLike($conditions, $likeCols, $orderBy, $limit, $offset);
    }
}
