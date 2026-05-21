<?php
ini_set('display_errors', 0);
error_reporting(0);
require_once '../../config/config.php';
require_once '../../config/database.php';
require_once '../../config/session.php';

requireLogin();
$conn = getDB();
$userId = (int) $_SESSION['user_id'];

$sql = "SELECT ci.cart_item_id, ci.quantity,
               p.product_id, p.name, p.price, p.stock, p.allergen_info,
               (ci.quantity * p.price) AS subtotal,
               s.shop_id, s.name AS shop_name,
               pi.image_url
        FROM CART c
        JOIN CART_ITEM ci ON c.cart_id = ci.cart_id
        JOIN PRODUCT p ON ci.product_id = p.product_id
        JOIN SHOP s ON p.shop_id = s.shop_id
        LEFT JOIN PRODUCT_IMAGE pi ON pi.product_id = p.product_id AND pi.display_order = 0
        WHERE c.user_id = :user_id
        ORDER BY s.name, ci.cart_item_id";

$stmt = oci_parse($conn, $sql);
oci_bind_by_name($stmt, ':user_id', $userId);

if (!oci_execute($stmt)) {
    $e = oci_error($stmt);
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e['message']]);
    oci_free_statement($stmt);
    oci_close($conn);
    exit;
}

$items = [];
$total = 0;

while ($row = oci_fetch_assoc($stmt)) {
    $pName = strtolower($row['NAME']);
    $imgMap = [
        'green bell pepper' => 'assets/Item-image/green-bell-pepper-isolated.jpg',
        'eggs'              => 'assets/Item-image/green-broccoli.jpg',
        'broccoli'          => 'assets/Item-image/green-broccoli.jpg',
        'beef'              => 'assets/other-images/fresh-raw-meat-cuts-with-rosemary-spices-dark-slate.jpg',
        'steak'             => 'assets/other-images/fresh-raw-meat-cuts-with-rosemary-spices-dark-slate.jpg',
        'sausage'           => 'assets/other-images/fresh-raw-meat-cuts-with-rosemary-spices-dark-slate.jpg',
        'lamb'              => 'assets/other-images/fresh-raw-meat-cuts-with-rosemary-spices-dark-slate.jpg',
        'chicken'           => 'assets/other-images/fresh-raw-meat-cuts-with-rosemary-spices-dark-slate.jpg',
        'pork'              => 'assets/other-images/fresh-raw-meat-cuts-with-rosemary-spices-dark-slate.jpg',
        'salmon'            => 'assets/other-images/top-view-fresh-fish-slices-dark-table-seafood-ocean-meat-sea-meal-dish-food-salad-water-pepper.jpg',
        'cod'               => 'assets/other-images/top-view-fresh-fish-slices-dark-table-seafood-ocean-meat-sea-meal-dish-food-salad-water-pepper.jpg',
        'prawn'             => 'assets/other-images/top-view-fresh-fish-slices-dark-table-seafood-ocean-meat-sea-meal-dish-food-salad-water-pepper.jpg',
        'seafood'           => 'assets/other-images/top-view-fresh-fish-slices-dark-table-seafood-ocean-meat-sea-meal-dish-food-salad-water-pepper.jpg',
        'fish'              => 'assets/other-images/top-view-fresh-fish-slices-dark-table-seafood-ocean-meat-sea-meal-dish-food-salad-water-pepper.jpg',
        'mackerel'          => 'assets/other-images/top-view-fresh-fish-slices-dark-table-seafood-ocean-meat-sea-meal-dish-food-salad-water-pepper.jpg',
        'brie'              => 'assets/other-images/imgi_47_cheese-wood_573717-86.jpg',
        'cheese'            => 'assets/other-images/imgi_47_cheese-wood_573717-86.jpg',
        'cheddar'           => 'assets/other-images/imgi_47_cheese-wood_573717-86.jpg',
        'stilton'           => 'assets/other-images/imgi_47_cheese-wood_573717-86.jpg',
        'bread'             => 'assets/other-images/imgi_57_sweet-bun-with-chocolate-syrup-peeled-orange_114579-2623.jpg',
        'sourdough'         => 'assets/other-images/imgi_57_sweet-bun-with-chocolate-syrup-peeled-orange_114579-2623.jpg',
        'croissant'         => 'assets/other-images/imgi_57_sweet-bun-with-chocolate-syrup-peeled-orange_114579-2623.jpg',
        'cinnamon'          => 'assets/other-images/imgi_57_sweet-bun-with-chocolate-syrup-peeled-orange_114579-2623.jpg',
        'spinach'           => 'assets/Item-image/imgi_46_chinese-broccoli-vegetables_1203-6831.jpg',
        'carrot'            => 'assets/Item-image/imgi_46_chinese-broccoli-vegetables_1203-6831.jpg',
        'vegetable'         => 'assets/Item-image/imgi_46_chinese-broccoli-vegetables_1203-6831.jpg',
        'tomato'            => 'assets/Item-image/imgi_46_chinese-broccoli-vegetables_1203-6831.jpg',
        'sweet potato'      => 'assets/Item-image/imgi_46_chinese-broccoli-vegetables_1203-6831.jpg',
        'courgette'         => 'assets/Item-image/imgi_46_chinese-broccoli-vegetables_1203-6831.jpg'
    ];
    $imgSrc = $row['IMAGE_URL'] ?? '';
    if (!$imgSrc) {
        $imgSrc = 'assets/Item-image/green-bell-pepper-isolated.jpg';
        foreach ($imgMap as $k => $v) {
            if (str_contains($pName, $k)) { $imgSrc = $v; break; }
        }
    }

    $row['IMAGE_SRC'] = $imgSrc;
    $row['ALLERGEN_INFO'] = $row['ALLERGEN_INFO'] ?? null;
    $items[] = $row;
    $total += (float) ($row['SUBTOTAL'] ?? 0);
}

oci_free_statement($stmt);
oci_close($conn);

echo json_encode(['success' => true, 'data' => $items, 'total' => $total]);