<?php
// pages/get_product_name.php
declare(strict_types=1);

header('Content-Type: application/json; charset=UTF-8');

require_once __DIR__ . '/../DataAccess/Syukeidata.php';

$productID = isset($_GET['productID']) ? trim($_GET['productID']) : '';
$result = ['product_name' => ''];

if ($productID !== '') {
    $dao = new Syukeidata();
    $name = $dao->getProductNameById($productID);
    if ($name !== '') {
        $result['product_name'] = $name;
    }
}

echo json_encode($result, JSON_UNESCAPED_UNICODE);