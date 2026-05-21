<?php
require_once '../config/database.php';
require_once '../config/session.php';

$userId = getUserId();
$isLoggedIn = isLoggedIn();
$cartCount = 0;

$shop = isset($_GET['shop']) ? strtolower(trim($_GET['shop'])) : '';
$type = isset($_GET['type']) ? strtolower(trim($_GET['type'])) : '';
$sub  = isset($_GET['sub'])  ? strtolower(trim($_GET['sub']))  : '';
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'name';
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;

$valid_shops = ['butcher', 'greengrocer', 'fishmonger', 'bakery', 'delicatessen'];
$valid_types = ['veg', 'non-veg', 'vegan', 'gluten-free', 'fresh-today'];
$valid_sorts = ['name', 'price-low', 'price-high', 'rating', 'newest'];

if ($shop && !in_array($shop, $valid_shops)) $shop = '';
if ($type && !in_array($type, $valid_types)) $type = '';
if (!in_array($sort, $valid_sorts)) $sort = 'name';

$page_title = 'All Products';
$page_desc  = 'Browse fresh items from all our local traders.';
$bannerImg  = 'assets/Banner/banner-1.png';

$type_labels = ['veg' => 'Vegetarian', 'non-veg' => 'Non-Vegetarian', 'vegan' => 'Vegan', 'gluten-free' => 'Gluten Free', 'fresh-today' => 'Fresh Today'];

if ($shop) {
    $page_title = ucfirst($shop) . ' Shop';
    if ($type && isset($type_labels[$type])) {
        $page_title = $type_labels[$type] . ' ' . $page_title;
    }
    $shop_color = match($shop) {
        'butcher' => '#8B1A1A', 'greengrocer' => '#2D6A4F', 'fishmonger' => '#1A5276',
        'bakery' => '#D4A017', 'delicatessen' => '#6C3483', default => '#2D6A4F'
    };
    $page_desc = match($shop) {
        'butcher' => 'Premium cuts of locally sourced meat.',
        'greengrocer' => 'Fresh fruits and vegetables daily.',
        'fishmonger' => 'Sustainably caught fish and seafood.',
        'bakery' => 'Artisan breads, cakes, and pastries.',
        'delicatessen' => 'Fine cheeses, cured meats, and gourmet dips.',
        default => 'Browse our selection.'
    };
    $bannerImg = match($shop) {
        'butcher' => 'assets/Banner/banner-2.png',
        'greengrocer' => 'assets/Banner/banner-1.png',
        'fishmonger' => 'assets/Banner/banner-3.png',
        'bakery' => 'assets/Banner/banner-4.png',
        'delicatessen' => 'assets/Banner/banner-1.png',
        default => 'assets/Banner/banner-1.png'
    };
} elseif ($type) {
    $page_title = $type_labels[$type] ?? 'Products';
    $page_desc  = 'Products matching your dietary preferences.';
}

$products    = [];
$totalCount  = 0;
$perPage     = 15;
$offset      = ($page - 1) * $perPage;

$subcategories = [];
if ($shop) {
    $sc_sql  = "SELECT DISTINCT pc.category_name FROM PRODUCT p
                JOIN SHOP s ON p.shop_id = s.shop_id
                JOIN PRODUCT_CATEGORY pc ON p.category_id = pc.category_id
                WHERE UPPER(s.shop_type) = :sh_type ORDER BY pc.category_name";
    $sc_stmt = oci_parse($conn, $sc_sql);
    $shop_upper = strtoupper($shop);
    oci_bind_by_name($sc_stmt, ':sh_type', $shop_upper);
    oci_execute($sc_stmt);
    while ($row = oci_fetch_assoc($sc_stmt)) {
        $subcategories[] = $row['CATEGORY_NAME'];
    }
    oci_free_statement($sc_stmt);
}

function getShopProducts($conn, $shopType, $limit = 10) {
    $sql = "SELECT p.product_id, p.name, p.description, p.price, p.stock,
                   p.min_order, p.max_order, p.allergen_info, p.dietary_tags,
                   p.unit, p.status,
                   s.shop_id, s.name AS shop_name, s.shop_type,
                   pc.category_name,
                   NVL(ROUND(AVG(r.rating), 1), 0) AS avg_rating,
                   COUNT(r.review_id) AS review_count,
                   NVL(d.discount_percent, 0) AS discount_percent,
                   pi.image_url
            FROM PRODUCT p
            JOIN SHOP s ON p.shop_id = s.shop_id
            LEFT JOIN PRODUCT_CATEGORY pc ON p.category_id = pc.category_id
            LEFT JOIN REVIEW r ON r.product_id = p.product_id
            LEFT JOIN DISCOUNT d ON d.product_id = p.product_id AND SYSDATE <= d.valid_until
            LEFT JOIN PRODUCT_IMAGE pi ON pi.product_id = p.product_id AND pi.display_order = 0
            WHERE p.status = 'Active' AND UPPER(s.shop_type) = :shop_type
            GROUP BY p.product_id, p.name, p.description, p.price, p.stock,
                     p.min_order, p.max_order, p.allergen_info, p.dietary_tags,
                     p.unit, p.status, s.shop_id, s.name, s.shop_type, pc.category_name,
                     d.discount_percent, pi.image_url
            ORDER BY DBMS_RANDOM.VALUE
            FETCH FIRST :lim ROWS ONLY";
    $stmt = oci_parse($conn, $sql);
    $shopTypeUpper = strtoupper($shopType);
    oci_bind_by_name($stmt, ':shop_type', $shopTypeUpper);
    oci_bind_by_name($stmt, ':lim', $limit, -1, SQLT_INT);
    oci_execute($stmt);
    $results = [];
    while ($row = oci_fetch_assoc($stmt)) {
        $results[] = $row;
    }
    oci_free_statement($stmt);
    return $results;
}

$butcherProducts = getShopProducts($conn, 'butcher', 10);
$greengrocerProducts = getShopProducts($conn, 'greengrocer', 10);
$fishmongerProducts = getShopProducts($conn, 'fishmonger', 10);
$bakeryProducts = getShopProducts($conn, 'bakery', 10);
$delicatessenProducts = getShopProducts($conn, 'delicatessen', 10);

function getShopTotal($conn, $shopType) {
    $sql = "SELECT COUNT(DISTINCT p.product_id) AS total FROM PRODUCT p
            JOIN SHOP s ON p.shop_id = s.shop_id
            WHERE p.status = 'Active' AND UPPER(s.shop_type) = :shop_type";
    $stmt = oci_parse($conn, $sql);
    $shopTypeUpper = strtoupper($shopType);
    oci_bind_by_name($stmt, ':shop_type', $shopTypeUpper);
    oci_execute($stmt);
    $row = oci_fetch_assoc($stmt);
    oci_free_statement($stmt);
    return $row ? intval($row['TOTAL']) : 0;
}

$butcherTotal = getShopTotal($conn, 'butcher');
$greengrocerTotal = getShopTotal($conn, 'greengrocer');
$fishmongerTotal = getShopTotal($conn, 'fishmonger');
$bakeryTotal = getShopTotal($conn, 'bakery');
$delicatessenTotal = getShopTotal($conn, 'delicatessen');

function getShopId($conn, $shopType) {
    $sql = "SELECT shop_id FROM SHOP WHERE UPPER(shop_type) = :shop_type FETCH FIRST 1 ROWS ONLY";
    $stmt = oci_parse($conn, $sql);
    $shopTypeUpper = strtoupper($shopType);
    oci_bind_by_name($stmt, ':shop_type', $shopTypeUpper);
    oci_execute($stmt);
    $row = oci_fetch_assoc($stmt);
    oci_free_statement($stmt);
    return $row ? (int)$row['SHOP_ID'] : 0;
}

$butcherId = getShopId($conn, 'butcher');
$greengrocerId = getShopId($conn, 'greengrocer');
$fishmongerId = getShopId($conn, 'fishmonger');
$bakeryId = getShopId($conn, 'bakery');
$delicatessenId = getShopId($conn, 'delicatessen');

// Fetch products when a shop or dietary type is selected
if ($shop || $type) {
    $shopId = 0;
    if ($shop) {
        $shopId = match($shop) {
            'butcher' => $butcherId,
            'greengrocer' => $greengrocerId,
            'fishmonger' => $fishmongerId,
            'bakery' => $bakeryId,
            'delicatessen' => $delicatessenId,
            default => 0
        };
    }

    $base = "SELECT p.product_id, p.name, p.description, p.price, p.stock,
                    p.min_order, p.max_order, p.allergen_info, p.dietary_tags,
                    p.unit, p.status,
                    s.shop_id, s.name AS shop_name, s.shop_type,
                    pc.category_name,
                    NVL(ROUND(AVG(r.rating), 1), 0) AS avg_rating,
                    COUNT(r.review_id) AS review_count,
                    NVL(d.discount_percent, 0) AS discount_percent,
                    pi.image_url
             FROM PRODUCT p
             JOIN SHOP s ON p.shop_id = s.shop_id
             LEFT JOIN PRODUCT_CATEGORY pc ON p.category_id = pc.category_id
             LEFT JOIN REVIEW r ON r.product_id = p.product_id
             LEFT JOIN DISCOUNT d ON d.product_id = p.product_id AND SYSDATE <= d.valid_until
             LEFT JOIN PRODUCT_IMAGE pi ON pi.product_id = p.product_id AND pi.display_order = 0
             WHERE p.status = 'Active'";

    $w    = "";
    $wCt  = "";
    $params = [];

    if ($shop) {
        $w   .= " AND UPPER(s.shop_type) = :shop_type";
        $wCt .= " AND UPPER(s.shop_type) = :shop_type";
        $params[':shop_type'] = strtoupper($shop);
    }
    
    if ($type === 'veg') {
        $w   .= " AND (INSTR(',' || UPPER(p.dietary_tags) || ',', ',VEGETARIAN,') > 0 OR INSTR(',' || UPPER(p.dietary_tags) || ',', 'VEGETARIAN,') > 0 OR INSTR(',' || UPPER(p.dietary_tags) || ',', ',VEGETARIAN') > 0)";
        $wCt .= " AND (INSTR(',' || UPPER(p.dietary_tags) || ',', ',VEGETARIAN,') > 0 OR INSTR(',' || UPPER(p.dietary_tags) || ',', 'VEGETARIAN,') > 0 OR INSTR(',' || UPPER(p.dietary_tags) || ',', ',VEGETARIAN') > 0)";
    } elseif ($type === 'vegan') {
        $w   .= " AND INSTR(UPPER(p.dietary_tags), 'VEGAN') > 0";
        $wCt .= " AND INSTR(UPPER(p.dietary_tags), 'VEGAN') > 0";
    } elseif ($type === 'gluten-free') {
        $w   .= " AND INSTR(UPPER(p.dietary_tags), 'GLUTEN FREE') > 0";
        $wCt .= " AND INSTR(UPPER(p.dietary_tags), 'GLUTEN FREE') > 0";
    } elseif ($type === 'non-veg') {
        // Non-veg is anything that is NOT explicitly Vegetarian (but could be Non-Vegetarian) and NOT Vegan
        $w   .= " AND (p.dietary_tags IS NULL 
                    OR (
                        (INSTR(UPPER(p.dietary_tags), 'VEGETARIAN') = 0 OR INSTR(UPPER(p.dietary_tags), 'NON-VEGETARIAN') > 0)
                        AND INSTR(UPPER(p.dietary_tags), 'VEGAN') = 0
                    )
                )";
        $wCt .= " AND (p.dietary_tags IS NULL 
                    OR (
                        (INSTR(UPPER(p.dietary_tags), 'VEGETARIAN') = 0 OR INSTR(UPPER(p.dietary_tags), 'NON-VEGETARIAN') > 0)
                        AND INSTR(UPPER(p.dietary_tags), 'VEGAN') = 0
                    )
                )";
    }
    
    if ($sub) {
        $w   .= " AND (UPPER(pc.category_name) = :subcategory OR UPPER(p.name) LIKE :sub_like OR UPPER(p.description) LIKE :sub_like)";
        $wCt .= " AND (UPPER(pc.category_name) = :subcategory OR UPPER(p.name) LIKE :sub_like OR UPPER(p.description) LIKE :sub_like)";
        $params[':subcategory'] = strtoupper($sub);
        $params[':sub_like'] = '%' . strtoupper($sub) . '%';
    }
    if (isset($_GET['in_stock']) && $_GET['in_stock'] === '1') {
        $w   .= " AND p.stock > 0";
        $wCt .= " AND p.stock > 0";
    }

    $groupBy = " GROUP BY p.product_id, p.name, p.description, p.price, p.stock,
                  p.min_order, p.max_order, p.allergen_info, p.dietary_tags,
                  p.unit, p.status, s.shop_id, s.name, s.shop_type, pc.category_name,
                  d.discount_percent, pi.image_url";

    $countSql = "SELECT COUNT(DISTINCT p.product_id) AS total FROM PRODUCT p"
               . " JOIN SHOP s ON p.shop_id = s.shop_id"
               . " LEFT JOIN PRODUCT_CATEGORY pc ON p.category_id = pc.category_id"
               . " LEFT JOIN REVIEW r ON r.product_id = p.product_id"
               . " WHERE p.status = 'Active'" . $wCt;

    $countStmt = oci_parse($conn, $countSql);
    foreach ($params as $key => $value) {
        oci_bind_by_name($countStmt, $key, $params[$key]);
    }
    oci_execute($countStmt);
    $countRow = oci_fetch_assoc($countStmt);
    $totalCount = $countRow ? intval($countRow['TOTAL']) : 0;
    oci_free_statement($countStmt);

    $sql = $base . $w . $groupBy;

    switch ($sort) {
        case 'price-low':
            $sql .= " ORDER BY p.price ASC, p.name ASC";
            break;
        case 'price-high':
            $sql .= " ORDER BY p.price DESC, p.name ASC";
            break;
        case 'rating':
            $sql .= " ORDER BY avg_rating DESC, p.name ASC";
            break;
        case 'newest':
            $sql .= " ORDER BY p.product_id DESC";
            break;
        default:
            $sql .= " ORDER BY p.name ASC";
    }

    $sql .= " OFFSET :offset ROWS FETCH NEXT :limit ROWS ONLY";

    $stmt = oci_parse($conn, $sql);
    foreach ($params as $key => $value) {
        oci_bind_by_name($stmt, $key, $params[$key]);
    }
    oci_bind_by_name($stmt, ':offset', $offset, -1, SQLT_INT);
    oci_bind_by_name($stmt, ':limit', $perPage, -1, SQLT_INT);
    oci_execute($stmt);

    $products = [];
    while ($row = oci_fetch_assoc($stmt)) {
        $products[] = $row;
    }
    oci_free_statement($stmt);

    $totalPages   = max(1, ceil($totalCount / $perPage));
}

if ($userId) {
    $cartStmt = oci_parse($conn, "SELECT SUM(ci.quantity) AS total_qty FROM CART c JOIN CART_ITEM ci ON c.cart_id = ci.cart_id WHERE c.user_id = :user_id");
    oci_bind_by_name($cartStmt, ':user_id', $userId);
    oci_execute($cartStmt);
    $cartRow = oci_fetch_assoc($cartStmt);
    $cartCount = (int)($cartRow['TOTAL_QTY'] ?? 0);
    oci_free_statement($cartStmt);
}

oci_close($conn);

function buildProductCard($p) {
    $name = $p['NAME'] ?? '';
    $price = floatval($p['PRICE'] ?? 0);
    $stock = intval($p['STOCK'] ?? 0);
    $shop = $p['SHOP_NAME'] ?? '';
    $discPct = floatval($p['DISCOUNT_PERCENT'] ?? 0);
    $avgRating = floatval($p['AVG_RATING'] ?? 0);
    $revCount = intval($p['REVIEW_COUNT'] ?? 0);
    $unit = $p['UNIT'] ?? 'unit';
    $imgUrl = $p['IMAGE_URL'] ?? '';

    $pName = strtolower($name);
    $imgMap = [
        'beef' => 'assets/other-images/fresh-raw-meat-cuts-with-rosemary-spices-dark-slate.jpg',
        'steak' => 'assets/other-images/fresh-raw-meat-cuts-with-rosemary-spices-dark-slate.jpg',
        'lamb' => 'assets/other-images/fresh-raw-meat-cuts-with-rosemary-spices-dark-slate.jpg',
        'chicken' => 'assets/other-images/fresh-raw-meat-cuts-with-rosemary-spices-dark-slate.jpg',
        'pork' => 'assets/other-images/fresh-raw-meat-cuts-with-rosemary-spices-dark-slate.jpg',
        'sausage' => 'assets/other-images/fresh-raw-meat-cuts-with-rosemary-spices-dark-slate.jpg',
        'rib' => 'assets/other-images/fresh-raw-meat-cuts-with-rosemary-spices-dark-slate.jpg',
        'bacon' => 'assets/other-images/fresh-raw-meat-cuts-with-rosemary-spices-dark-slate.jpg',
        'burger' => 'assets/other-images/fresh-raw-meat-cuts-with-rosemary-spices-dark-slate.jpg',
        'duck' => 'assets/other-images/fresh-raw-meat-cuts-with-rosemary-spices-dark-slate.jpg',
        'prosciutto' => 'assets/other-images/fresh-raw-meat-cuts-with-rosemary-spices-dark-slate.jpg',
        'salami' => 'assets/other-images/fresh-raw-meat-cuts-with-rosemary-spices-dark-slate.jpg',
        'pate' => 'assets/other-images/fresh-raw-meat-cuts-with-rosemary-spices-dark-slate.jpg',
        'salmon' => 'assets/other-images/top-view-fresh-fish-slices-dark-table-seafood-ocean-meat-sea-meal-dish-food-salad-water-pepper.jpg',
        'cod' => 'assets/other-images/top-view-fresh-fish-slices-dark-table-seafood-ocean-meat-sea-meal-dish-food-salad-water-pepper.jpg',
        'prawn' => 'assets/other-images/top-view-fresh-fish-slices-dark-table-seafood-ocean-meat-sea-meal-dish-food-salad-water-pepper.jpg',
        'shrimp' => 'assets/other-images/top-view-fresh-fish-slices-dark-table-seafood-ocean-meat-sea-meal-dish-food-salad-water-pepper.jpg',
        'fish' => 'assets/other-images/top-view-fresh-fish-slices-dark-table-seafood-ocean-meat-sea-meal-dish-food-salad-water-pepper.jpg',
        'mackerel' => 'assets/other-images/top-view-fresh-fish-slices-dark-table-seafood-ocean-meat-sea-meal-dish-food-salad-water-pepper.jpg',
        'tuna' => 'assets/other-images/top-view-fresh-fish-slices-dark-table-seafood-ocean-meat-sea-meal-dish-food-salad-water-pepper.jpg',
        'trout' => 'assets/other-images/top-view-fresh-fish-slices-dark-table-seafood-ocean-meat-sea-meal-dish-food-salad-water-pepper.jpg',
        'bass' => 'assets/other-images/top-view-fresh-fish-slices-dark-table-seafood-ocean-meat-sea-meal-dish-food-salad-water-pepper.jpg',
        'haddock' => 'assets/other-images/top-view-fresh-fish-slices-dark-table-seafood-ocean-meat-sea-meal-dish-food-salad-water-pepper.jpg',
        'sardine' => 'assets/other-images/top-view-fresh-fish-slices-dark-table-seafood-ocean-meat-sea-meal-dish-food-salad-water-pepper.jpg',
        'sole' => 'assets/other-images/top-view-fresh-fish-slices-dark-table-seafood-ocean-meat-sea-meal-dish-food-salad-water-pepper.jpg',
        'mussel' => 'assets/other-images/top-view-fresh-fish-slices-dark-table-seafood-ocean-meat-sea-meal-dish-food-salad-water-pepper.jpg',
        'scallop' => 'assets/other-images/top-view-fresh-fish-slices-dark-table-seafood-ocean-meat-sea-meal-dish-food-salad-water-pepper.jpg',
        'seafood' => 'assets/other-images/top-view-fresh-fish-slices-dark-table-seafood-ocean-meat-sea-meal-dish-food-salad-water-pepper.jpg',
        'marinara' => 'assets/other-images/top-view-fresh-fish-slices-dark-table-seafood-ocean-meat-sea-meal-dish-food-salad-water-pepper.jpg',
        'cheese' => 'assets/other-images/imgi_47_cheese-wood_573717-86.jpg',
        'brie' => 'assets/other-images/imgi_47_cheese-wood_573717-86.jpg',
        'cheddar' => 'assets/other-images/imgi_47_cheese-wood_573717-86.jpg',
        'stilton' => 'assets/other-images/imgi_47_cheese-wood_573717-86.jpg',
        'manchego' => 'assets/other-images/imgi_47_cheese-wood_573717-86.jpg',
        'feta' => 'assets/other-images/imgi_47_cheese-wood_573717-86.jpg',
        'ricotta' => 'assets/other-images/imgi_47_cheese-wood_573717-86.jpg',
        'bread' => 'assets/other-images/imgi_57_sweet-bun-with-chocolate-syrup-peeled-orange_114579-2623.jpg',
        'sourdough' => 'assets/other-images/imgi_57_sweet-bun-with-chocolate-syrup-peeled-orange_114579-2623.jpg',
        'croissant' => 'assets/other-images/imgi_57_sweet-bun-with-chocolate-syrup-peeled-orange_114579-2623.jpg',
        'cinnamon' => 'assets/other-images/imgi_57_sweet-bun-with-chocolate-syrup-peeled-orange_114579-2623.jpg',
        'baguette' => 'assets/other-images/imgi_57_sweet-bun-with-chocolate-syrup-peeled-orange_114579-2623.jpg',
        'focaccia' => 'assets/other-images/imgi_57_sweet-bun-with-chocolate-syrup-peeled-orange_114579-2623.jpg',
        'loaf' => 'assets/other-images/imgi_57_sweet-bun-with-chocolate-syrup-peeled-orange_114579-2623.jpg',
        'brownie' => 'assets/other-images/imgi_57_sweet-bun-with-chocolate-syrup-peeled-orange_114579-2623.jpg',
        'cookie' => 'assets/other-images/imgi_57_sweet-bun-with-chocolate-syrup-peeled-orange_114579-2623.jpg',
        'cake' => 'assets/other-images/imgi_57_sweet-bun-with-chocolate-syrup-peeled-orange_114579-2623.jpg',
        'spinach' => 'assets/Item-image/imgi_46_chinese-broccoli-vegetables_1203-6831.jpg',
        'carrot' => 'assets/Item-image/imgi_46_chinese-broccoli-vegetables_1203-6831.jpg',
        'broccoli' => 'assets/Item-image/green-broccoli.jpg',
        'egg' => 'assets/Item-image/green-broccoli.jpg',
        'pepper' => 'assets/Item-image/green-bell-pepper-isolated.jpg',
        'tomato' => 'assets/Item-image/green-bell-pepper-isolated.jpg',
        'olive' => 'assets/Item-image/green-bell-pepper-isolated.jpg',
        'tapenade' => 'assets/Item-image/green-bell-pepper-isolated.jpg',
        'coleslaw' => 'assets/Item-image/imgi_46_chinese-broccoli-vegetables_1203-6831.jpg',
        'salad' => 'assets/Item-image/imgi_46_chinese-broccoli-vegetables_1203-6831.jpg',
        'quiche' => 'assets/other-images/imgi_57_sweet-bun-with-chocolate-syrup-peeled-orange_114579-2623.jpg',
        'dolma' => 'assets/Item-image/green-bell-pepper-isolated.jpg',
        'vine' => 'assets/Item-image/green-bell-pepper-isolated.jpg',
        'apple' => 'assets/Item-image/imgi_46_chinese-broccoli-vegetables_1203-6831.jpg',
        'banana' => 'assets/Item-image/imgi_46_chinese-broccoli-vegetables_1203-6831.jpg',
        'orange' => 'assets/Item-image/imgi_46_chinese-broccoli-vegetables_1203-6831.jpg',
        'grape' => 'assets/Item-image/imgi_46_chinese-broccoli-vegetables_1203-6831.jpg',
        'strawberry' => 'assets/Item-image/imgi_46_chinese-broccoli-vegetables_1203-6831.jpg',
        'blueberry' => 'assets/Item-image/imgi_46_chinese-broccoli-vegetables_1203-6831.jpg',
        'raspberry' => 'assets/Item-image/imgi_46_chinese-broccoli-vegetables_1203-6831.jpg',
        'lettuce' => 'assets/Item-image/imgi_46_chinese-broccoli-vegetables_1203-6831.jpg',
        'cucumber' => 'assets/Item-image/imgi_46_chinese-broccoli-vegetables_1203-6831.jpg',
        'onion' => 'assets/Item-image/imgi_46_chinese-broccoli-vegetables_1203-6831.jpg',
        'potato' => 'assets/Item-image/imgi_46_chinese-broccoli-vegetables_1203-6831.jpg',
        'mushroom' => 'assets/Item-image/imgi_46_chinese-broccoli-vegetables_1203-6831.jpg',
        'courgette' => 'assets/Item-image/imgi_46_chinese-broccoli-vegetables_1203-6831.jpg',
        'aubergine' => 'assets/Item-image/imgi_46_chinese-broccoli-vegetables_1203-6831.jpg',
        'cabbage' => 'assets/Item-image/imgi_46_chinese-broccoli-vegetables_1203-6831.jpg',
        'kale' => 'assets/Item-image/imgi_46_chinese-broccoli-vegetables_1203-6831.jpg',
        'avocado' => 'assets/Item-image/imgi_46_chinese-broccoli-vegetables_1203-6831.jpg',
        'mango' => 'assets/Item-image/imgi_46_chinese-broccoli-vegetables_1203-6831.jpg',
        'pear' => 'assets/Item-image/imgi_46_chinese-broccoli-vegetables_1203-6831.jpg',
        'lemon' => 'assets/Item-image/imgi_46_chinese-broccoli-vegetables_1203-6831.jpg',
        'lime' => 'assets/Item-image/imgi_46_chinese-broccoli-vegetables_1203-6831.jpg',
        'melon' => 'assets/Item-image/imgi_46_chinese-broccoli-vegetables_1203-6831.jpg',
        'watermelon' => 'assets/Item-image/imgi_46_chinese-broccoli-vegetables_1203-6831.jpg',
        'pineapple' => 'assets/Item-image/imgi_46_chinese-broccoli-vegetables_1203-6831.jpg',
        'kiwi' => 'assets/Item-image/imgi_46_chinese-broccoli-vegetables_1203-6831.jpg',
        'peach' => 'assets/Item-image/imgi_46_chinese-broccoli-vegetables_1203-6831.jpg',
        'plum' => 'assets/Item-image/imgi_46_chinese-broccoli-vegetables_1203-6831.jpg',
        'cherry' => 'assets/Item-image/imgi_46_chinese-broccoli-vegetables_1203-6831.jpg',
        'fig' => 'assets/Item-image/imgi_46_chinese-broccoli-vegetables_1203-6831.jpg',
        'date' => 'assets/Item-image/imgi_46_chinese-broccoli-vegetables_1203-6831.jpg',
        'apricot' => 'assets/Item-image/imgi_46_chinese-broccoli-vegetables_1203-6831.jpg',
        'nectarine' => 'assets/Item-image/imgi_46_chinese-broccoli-vegetables_1203-6831.jpg',
        'blackberry' => 'assets/Item-image/imgi_46_chinese-broccoli-vegetables_1203-6831.jpg',
        'cranberry' => 'assets/Item-image/imgi_46_chinese-broccoli-vegetables_1203-6831.jpg',
        'pomegranate' => 'assets/Item-image/imgi_46_chinese-broccoli-vegetables_1203-6831.jpg',
        'passion' => 'assets/Item-image/imgi_46_chinese-broccoli-vegetables_1203-6831.jpg',
        'papaya' => 'assets/Item-image/imgi_46_chinese-broccoli-vegetables_1203-6831.jpg',
        'guava' => 'assets/Item-image/imgi_46_chinese-broccoli-vegetables_1203-6831.jpg',
        'lychee' => 'assets/Item-image/imgi_46_chinese-broccoli-vegetables_1203-6831.jpg',
        'dragon' => 'assets/Item-image/imgi_46_chinese-broccoli-vegetables_1203-6831.jpg',
        'starfruit' => 'assets/Item-image/imgi_46_chinese-broccoli-vegetables_1203-6831.jpg',
        'persimmon' => 'assets/Item-image/imgi_46_chinese-broccoli-vegetables_1203-6831.jpg',
        'quince' => 'assets/Item-image/imgi_46_chinese-broccoli-vegetables_1203-6831.jpg',
    ];
    $imgSrc = 'assets/Item-image/green-bell-pepper-isolated.jpg';
    if ($imgUrl) {
        $imgSrc = $imgUrl;
    } else {
        foreach ($imgMap as $k => $v) {
            if (str_contains($pName, $k)) { $imgSrc = $v; break; }
        }
    }

    $discountedPrice = $discPct > 0 ? $price * (1 - $discPct / 100) : 0;
    $badge = '';
    if ($stock === 0) {
        $badge = '<span class="stock-badge out">Out of stock</span>';
    } elseif ($discPct > 0) {
        $badge = '<span class="stock-badge discount">' . round($discPct) . '% OFF</span>';
    }

    $priceHtml = '<div class="price-group">';
    if ($discPct > 0) {
        $priceHtml .= '<span class="current-price">&#163;' . number_format($discountedPrice, 2) . '</span>';
        $priceHtml .= '<span class="original-price">&#163;' . number_format($price, 2) . '</span>';
    } else {
        $priceHtml .= '<span class="current-price">&#163;' . number_format($price, 2) . '</span>';
    }
    $priceHtml .= ' <span class="unit">/ ' . htmlspecialchars($unit) . '</span></div>';
    if ($discPct > 0) {
        $priceHtml .= '<span class="discount-text">' . round($discPct) . '% off</span>';
    }

    $starCount = (int)round($avgRating);
    $starsHtml = '';
    for ($i = 1; $i <= 5; $i++) {
        $starsHtml .= '<span class="material-icons-outlined">' . ($i <= $starCount ? 'star' : 'star_border') . '</span>';
    }
    $ratingHtml = '<div class="rating-row"><div class="rating-stars">' . $starsHtml . '</div><div class="rating-count">(' . $revCount . ')</div></div>';

    $outOfStock = $stock === 0;
    $cardClass = 'product-card' . ($outOfStock ? ' is-out-of-stock' : '');

    $html = '<div class="' . $cardClass . '">';
    $html .= '<div class="product-card-inner" data-link="product.html?id=' . intval($p['PRODUCT_ID']) . '" data-product-id="' . intval($p['PRODUCT_ID']) . '">';
    $html .= '<div class="product-image-wrapper">';
    $html .= $badge;
    $html .= '<button class="favorite-btn" type="button"><span class="material-icons-outlined">favorite_border</span></button>';
    $html .= '<div class="product-image"><img src="' . htmlspecialchars($imgSrc) . '" alt="' . htmlspecialchars($name) . '" onerror="this.src=\'assets/Item-image/green-bell-pepper-isolated.jpg\'" loading="lazy"></div>';
    $html .= '</div>';
    $html .= '<div class="product-info">';
    $html .= '<div class="product-name">' . htmlspecialchars($name) . '</div>';
    if ($shop) {
        $html .= '<div class="product-shop"><a href="category.php?shop=' . strtolower($p['SHOP_TYPE'] ?? '') . '" class="shop-link">' . htmlspecialchars($shop) . '</a></div>';
    }
    $html .= $ratingHtml;
    $html .= '<div class="price-row">' . $priceHtml . '</div>';
    $html .= '<button class="add-to-cart-btn"' . ($outOfStock ? ' disabled aria-disabled="true"' : '') . '>';
    $html .= '<span class="material-icons-outlined">shopping_cart</span>';
    $html .= $outOfStock ? 'Out of stock' : 'Add to cart';
    $html .= '</button>';
    $html .= '</div></div></div>';
    return $html;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?> | HuddersHub</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Google+Sans+Flex:ital,wght@0,400;0,500;0,600;0,700;1,400;1,500;1,600;1,700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:ital,wght@0,200..800;1,200..800&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons+Outlined" rel="stylesheet">
    <style>
        :root {
            --primary-orange: #ff5e3a;
            --primary-orange-light: #ff8c70;
            --primary-orange-dark: #e3472c;
            --primary-green: #0f260b;
            --primary-green-light: rgba(15, 38, 11, 0.14);
            --primary-green-dark: #0b1c08;
            --brand-dark: #0f260b;
            --brand-mid: #1c3c17;
            --accent-green: #caed95;
            --bg-white: #ffffff;
            --bg-light: #f7f6f3;
            --bg-gray: #f2f4f1;
            --surface: #ffffff;
            --text-primary: #0b140a;
            --text-muted: #5e6a63;
            --text-dark-gray: #1e2a1c;
            --text-medium-gray: #5e6a63;
            --border-light: #dce3da;
            --border: #dce3da;
            --bg-gradient: linear-gradient(135deg, #f8faf7 0%, #ffffff 100%);
            --badge-bg: #0f260b;
            --badge-text: #ffffff;
            --shadow-sm: 0 2px 6px rgba(15, 38, 11, 0.08);
            --shadow-md: 0 10px 24px rgba(15, 38, 11, 0.12);
            --shadow-lg: 0 18px 36px rgba(15, 38, 11, 0.16);
            --transition-smooth: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
            --radius-sm: 0;
        }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        html { scroll-behavior: smooth; }
        body {
            font-family: "Plus Jakarta Sans", sans-serif;
            background: linear-gradient(180deg, #f7f6f3 0%, #ffffff 45%, #f7f6f3 100%);
            color: var(--text-primary);
            padding-top: 140px;
            min-height: 100vh;
        }
        a { text-decoration: none; color: inherit; transition: var(--transition-smooth); }
        button { font-family: inherit; transition: var(--transition-smooth); cursor: pointer; }
        img { max-width: 100%; height: auto; display: block; }
        .material-icons-outlined { font-size: 18px; vertical-align: middle; }
        .page-wrap { max-width: 1200px; margin: 0 auto; padding: 0 24px; }

        header { position: fixed; top: 0; left: 0; right: 0; z-index: 1000; background: var(--bg-gradient); backdrop-filter: blur(12px); transition: var(--transition-smooth); }
        header.scrolled { box-shadow: var(--shadow-md); background: var(--bg-white); }
        .top-bar { background: rgba(255, 255, 255, 0.98); border-bottom: 1px solid var(--border-light); padding: 14px 0; transition: var(--transition-smooth); }
        header.scrolled .top-bar { padding: 10px 0; }
        header.scrolled .brand img { width: 42px; height: 42px; }
        header.scrolled .brand-text { font-size: 30px; }
        .top-bar-inner { display: grid; grid-template-columns: auto 1fr auto; gap: 18px; align-items: center; }
        .brand { display: flex; align-items: center; gap: 14px; }
        .brand img { width: 56px; height: 56px; object-fit: contain; transition: var(--transition-smooth); filter: drop-shadow(0 6px 12px rgba(15, 38, 11, 0.12)); }
        .brand-text { font-family: "Google Sans Flex", sans-serif; font-weight: 700; font-style: italic; font-size: 36px; color: var(--brand-dark); }
        .search-wrap { display: flex; align-items: center; gap: 12px; }
        .search-bar { position: relative; flex: 1; min-width: 280px; }
        .search-bar input { width: 100%; padding: 6px 44px 6px 14px; height: 36px; border: 1px solid #c8d1c6; background: #fff; font-size: 14px; outline: none; border-radius: 0; }
        .search-bar input:focus { border-color: var(--primary-orange); box-shadow: 0 0 0 3px rgba(255, 94, 58, 0.22); }
        .search-icon { position: absolute; right: 12px; top: 50%; transform: translateY(-50%); font-size: 18px; color: #1b2419; opacity: 0.55; border: none; background: transparent; padding: 0; cursor: pointer; }
        .actions { display: flex; align-items: center; gap: 16px; }
        .user-menu, .action-btn { display: inline-flex; align-items: center; gap: 6px; font-size: 14px; font-weight: 600; cursor: pointer; padding: 8px 12px; border-radius: var(--radius-sm); }
        .user-menu { color: var(--text-primary); }
        .user-menu:hover, .action-btn:hover { background: var(--primary-green-light); color: var(--brand-dark); }
        .action-btn { color: var(--text-medium-gray); }
        .icon-with-badge { position: relative; display: inline-flex; align-items: center; justify-content: center; cursor: pointer; padding: 6px; color: var(--text-primary); border-radius: 0; }
        .icon-with-badge:hover { background: var(--primary-green-light); color: var(--brand-dark); }
        .icon-with-badge .material-icons-outlined { font-size: 24px; }
        .badge { position: absolute; top: 0; right: 0; background: var(--badge-bg); color: #fff; padding: 2px 5px; font-size: 10px; font-weight: 600; min-width: 16px; text-align: center; }

        .user-dropdown-wrap { position: relative; }
        .user-dropdown { position: absolute; top: calc(100% + 8px); right: 0; background: #fff; border: 1px solid var(--border-light); box-shadow: var(--shadow-lg); min-width: 190px; z-index: 2000; display: none; flex-direction: column; padding: 6px; }
        .user-dropdown.open { display: flex; }
        .dropdown-item { display: flex; align-items: center; gap: 10px; padding: 10px 14px; font-size: 14px; font-weight: 500; color: var(--text-primary); text-decoration: none; background: none; border: none; width: 100%; border-radius: 0; transition: var(--transition-smooth); }
        .dropdown-item:hover { background: var(--primary-green-light); color: var(--brand-dark); }
        .dropdown-item.logout-item { color: #dc2626; }
        .dropdown-item.logout-item:hover { background: #fef2f2; }
        .dropdown-divider { height: 1px; background: var(--border-light); margin: 4px 0; }

        .nav-bar { background: #f1f3f0; border-bottom: 1px solid var(--border-light); padding: 10px 0; }
        header.scrolled .nav-bar { padding: 8px 0; background: #f3f4f6; }
        .nav-list { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }
        .nav-item { display: inline-flex; align-items: center; gap: 6px; padding: 8px 14px; border-radius: 0; text-decoration: none; color: var(--text-black); font-size: 15px; }
        .nav-item.primary { background: transparent; color: #0f260b; font-weight: 600; }
        .nav-item.is-active { position: relative; }
        .nav-item.is-active::after { content: ''; position: absolute; left: 0; right: 0; bottom: -6px; height: 2px; background: #0f260b; }
        .nav-separator { width: 1px; height: 24px; background: var(--border-light); margin: 0 6px; display: inline-block; }
        .categories-wrapper { position: relative; }
        .categories-dropdown { position: absolute; top: 100%; left: 0; background: #fff; border: 1px solid var(--border-light); box-shadow: var(--shadow-lg); padding: 0; min-width: 240px; display: none; z-index: 1000; transform: translateY(-8px); opacity: 0; transition: opacity 0.2s ease, transform 0.2s ease; }
        .categories-wrapper:hover .categories-dropdown, .categories-dropdown:hover { display: block; transform: translateY(0); opacity: 1; }
        .categories-dropdown::before { content: ''; position: absolute; top: -15px; left: 0; right: 0; height: 15px; }
        .dropdown-section { padding: 12px 0; }
        .dropdown-section-title { padding: 8px 16px; font-size: 11px; font-weight: 700; color: #6b7280; text-transform: uppercase; letter-spacing: 0.8px; }
        .dropdown-divider { height: 1px; background: var(--border-light); margin: 8px 16px; }
        .dropdown-item-link { display: block; padding: 10px 16px; color: var(--text-black); text-decoration: none; font-size: 14px; border-left: 3px solid transparent; transition: var(--transition-smooth); }
        .dropdown-item-link:hover { background: rgba(15,38,11,.08); border-left-color: var(--primary-green); }
        .dropdown-item-link.all-categories { font-weight: 600; background: linear-gradient(135deg, #f9fafb 0%, #ffffff 100%); padding: 12px 16px; }
        .dropdown-item-link.all-categories:hover { background: var(--primary-orange); color: #fff; border-left-color: var(--primary-orange); }

        /* CATEGORY SECTIONS */
        .cart-link { font-size: 13px; color: var(--text-medium-gray); text-decoration: none; border-bottom: 1px solid var(--border-light); }
        .cart-link:hover { color: var(--brand-dark); border-bottom-color: var(--brand-dark); }
        .cart-checkout { display: inline-flex; align-items: center; gap: 8px; padding: 10px 16px; border: none; background: linear-gradient(135deg, var(--primary-orange) 0%, var(--primary-orange-light) 100%); color: #fff; font-size: 13px; font-weight: 700; cursor: pointer; box-shadow: 0 10px 22px rgba(255, 94, 58, 0.28); }

        .category-page { padding: 24px 0 50px; }
        .browse-section { margin-bottom: 28px; }
        .section-title-lg { font-size: 32px; font-weight: 800; color: var(--text-black); text-align: center; margin-bottom: 32px; }
        .shop-selector { display: flex; flex-direction: column; align-items: center; gap: 16px; margin-bottom: 26px; }
        .shop-row { display: flex; justify-content: center; gap: 16px; width: 100%; max-width: 800px; }
        .shop-row:first-child .shop-card { flex: 1; }
        .shop-row:last-child .shop-card { flex: 1.5; }
        .shop-card {
            position: relative;
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            border: 2px solid rgba(255,255,255,0.2);
            border-radius: 16px;
            padding: 48px 24px;
            text-align: center;
            cursor: pointer;
            transition: var(--transition-smooth);
            text-decoration: none;
            color: #fff;
            font-weight: 800;
            text-transform: uppercase;
            font-size: 22px;
            letter-spacing: 1.5px;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 160px;
            overflow: hidden;
            text-shadow: 0 2px 10px rgba(0,0,0,0.7);
        }
        .shop-card::before {
            content: '';
            position: absolute;
            inset: 0;
            background: rgba(0, 0, 0, 0.45);
            transition: var(--transition-smooth);
            z-index: 1;
        }
        .shop-card:hover::before { background: rgba(0, 0, 0, 0.3); }
        .shop-card:hover { transform: translateY(-4px); box-shadow: var(--shadow-lg); border-color: rgba(255,255,255,0.4); }
        .shop-card.is-active::before { background: rgba(0, 0, 0, 0.35); }
        .shop-card.is-active { border-color: var(--primary-orange); box-shadow: 0 0 0 3px rgba(255,94,58,0.3); }
        .shop-card.is-active span { color: var(--primary-orange); }
        .shop-card span { position: relative; z-index: 2; }

        .shop-card[data-shop="fishmonger"] { background-image: url('assets/other-images/fih.jpg'); }
        .shop-card[data-shop="greengrocer"] { background-image: url('assets/other-images/green.jpg'); }
        .shop-card[data-shop="bakery"] { background-image: url('assets/other-images/cokies.jpg'); }
        .shop-card[data-shop="butcher"] { background-image: url('assets/other-images/meat.jpg'); }
        .shop-card[data-shop="delicatessen"] { background-image: url('assets/other-images/deli.jpg'); }

        @media (max-width: 720px) { .shop-card { padding: 32px 16px; font-size: 16px; min-height: 120px; } .shop-row { gap: 12px; } }
        @media (max-width: 480px) { .shop-row { flex-direction: column; } .shop-row:first-child .shop-card, .shop-row:last-child .shop-card { flex: 1; } .shop-card { min-height: 100px; font-size: 18px; } }

        .type-strip { background: #fff; border: 1px solid var(--border-light); padding: 18px; margin-bottom: 22px; box-shadow: var(--shadow-sm); }
        .type-header { display: flex; align-items: center; justify-content: space-between; gap: 12px; margin-bottom: 12px; }
        .type-header h2 { font-size: 20px; color: var(--text-black); }
        .type-header span { color: var(--text-medium-gray); font-size: 13px; }
        .type-tabs { display: flex; flex-wrap: wrap; gap: 10px; }
        .type-tab { border: 1px solid var(--border-light); background: #f7f8f6; padding: 10px 14px; border-radius: 0; font-size: 13px; font-weight: 700; color: var(--text-dark-gray); text-decoration: none; }
        .type-tab:hover { border-color: var(--primary-green); color: var(--primary-green); }
        .type-tab.is-active { background: var(--primary-green); color: #fff; border-color: var(--primary-green); }

        .category-section { margin-bottom: 48px; }
        .category-title { font-size: 36px; font-weight: 800; color: var(--text-black); margin-bottom: 20px; }

        .subcategory-grid { display: grid; grid-template-columns: repeat(5, 1fr); gap: 12px; margin-bottom: 24px; }
        .sub-tile {
            position: relative;
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            border: 2px solid rgba(255,255,255,0.15);
            border-radius: 12px;
            padding: 0;
            aspect-ratio: 1.2;
            display: flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
            color: #fff;
            font-size: 16px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            transition: var(--transition-smooth);
            cursor: pointer;
            overflow: hidden;
            text-shadow: 0 2px 6px rgba(0,0,0,0.7);
        }
        .sub-tile::before {
            content: '';
            position: absolute;
            inset: 0;
            background: rgba(0, 0, 0, 0.5);
            transition: var(--transition-smooth);
            z-index: 1;
        }
        .sub-tile:hover::before { background: rgba(0, 0, 0, 0.35); }
        .sub-tile:hover { transform: translateY(-3px); box-shadow: var(--shadow-md); border-color: rgba(255,255,255,0.3); }
        .sub-tile.is-active::before { background: rgba(0, 0, 0, 0.3); }
        .sub-tile.is-active { border-color: var(--primary-orange); box-shadow: 0 0 0 3px rgba(255,94,58,0.3); }
        .sub-tile.is-active span { color: var(--primary-orange); }
        .sub-tile span { position: relative; z-index: 2; }

        /* Butcher subcategory images */
        .sub-tile[data-sub="beef"] { background-image: url('assets/other-images/meat.jpg'); }
        .sub-tile[data-sub="chicken"] { background-image: url('assets/other-images/fresh-raw-meat-cuts-with-rosemary-spices-dark-slate.jpg'); }
        .sub-tile[data-sub="lamb"] { background-image: url('assets/other-images/meat.jpg'); }
        .sub-tile[data-sub="pork"] { background-image: url('assets/other-images/fresh-raw-meat-cuts-with-rosemary-spices-dark-slate.jpg'); }
        .sub-tile[data-sub="sausages"] { background-image: url('assets/other-images/meat.jpg'); }
        /* Greengrocer subcategory images */
        .sub-tile[data-sub="fruit"] { background-image: url('assets/other-images/green.jpg'); }
        .sub-tile[data-sub="vegetables"] { background-image: url('assets/other-images/variety-fresh-organic-herbs-lettuce-arugula-dill-mint-red-lettuce-onion-rustic-style-top-view.jpg'); }
        .sub-tile[data-sub="herbs"] { background-image: url('assets/other-images/green.jpg'); }
        .sub-tile[data-sub="organic"] { background-image: url('assets/other-images/variety-fresh-organic-herbs-lettuce-arugula-dill-mint-red-lettuce-onion-rustic-style-top-view.jpg'); }
        .sub-tile[data-sub="seasonal"] { background-image: url('assets/other-images/green.jpg'); }
        /* Fishmonger subcategory images */
        .sub-tile[data-sub="fresh-fish"] { background-image: url('assets/other-images/fih.jpg'); }
        .sub-tile[data-sub="shellfish"] { background-image: url('assets/other-images/top-view-fresh-fish-slices-dark-table-seafood-ocean-meat-sea-meal-dish-food-salad-water-pepper.jpg'); }
        .sub-tile[data-sub="smoked"] { background-image: url('assets/other-images/fih.jpg'); }
        .sub-tile[data-sub="prepared"] { background-image: url('assets/other-images/top-view-fresh-fish-slices-dark-table-seafood-ocean-meat-sea-meal-dish-food-salad-water-pepper.jpg'); }
        .sub-tile[data-sub="seasonal-fish"] { background-image: url('assets/other-images/fih.jpg'); }
        /* Bakery subcategory images */
        .sub-tile[data-sub="bread"] { background-image: url('assets/other-images/imgi_57_sweet-bun-with-chocolate-syrup-peeled-orange_114579-2623.jpg'); }
        .sub-tile[data-sub="pastries"] { background-image: url('assets/other-images/imgi_57_sweet-bun-with-chocolate-syrup-peeled-orange_114579-2623.jpg'); }
        .sub-tile[data-sub="cakes"] { background-image: url('assets/other-images/cokies.jpg'); }
        .sub-tile[data-sub="cookies"] { background-image: url('assets/other-images/cokies.jpg'); }
        .sub-tile[data-sub="sourdough"] { background-image: url('assets/other-images/imgi_57_sweet-bun-with-chocolate-syrup-peeled-orange_114579-2623.jpg'); }
        /* Delicatessen subcategory images */
        .sub-tile[data-sub="cheese"] { background-image: url('assets/other-images/imgi_47_cheese-wood_573717-86.jpg'); }
        .sub-tile[data-sub="cured-meats"] { background-image: url('assets/other-images/deli.jpg'); }
        .sub-tile[data-sub="olives"] { background-image: url('assets/other-images/imgi_47_cheese-wood_573717-86.jpg'); }
        .sub-tile[data-sub="dips"] { background-image: url('assets/other-images/imgi_47_cheese-wood_573717-86.jpg'); }
        .sub-tile[data-sub="antipasti"] { background-image: url('assets/other-images/deli.jpg'); }

        .products-header {
            display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;
            padding: 12px 0; border-top: 2px solid var(--text-black); border-bottom: 2px solid var(--text-black);
            margin-bottom: 20px;
        }
        .products-header-left { display: flex; align-items: center; gap: 8px; font-weight: 700; font-size: 15px; }
        .products-header-left span { font-weight: 400; color: var(--text-medium-gray); font-size: 13px; }
        .products-header-right { display: flex; align-items: center; gap: 16px; }
        .sort-btn, .filter-btn {
            display: inline-flex; align-items: center; gap: 6px; font-size: 13px; font-weight: 600;
            color: var(--text-dark-gray); cursor: pointer; background: none; border: none; padding: 4px 8px;
        }
        .sort-btn:hover, .filter-btn:hover { color: var(--primary-green); }
        .sort-btn .material-icons-outlined, .filter-btn .material-icons-outlined { font-size: 16px; }

        .product-cards-grid { display: grid; grid-template-columns: repeat(5, 1fr); gap: 16px; margin-bottom: 20px; }

        .show-more-wrap { text-align: center; margin-top: 12px; }
        .show-more-btn {
            display: inline-flex; align-items: center; gap: 6px; padding: 10px 24px;
            border: 1px solid var(--border-light); background: var(--bg-white); border-radius: 0;
            font-size: 13px; font-weight: 600; color: var(--text-medium-gray); cursor: pointer; transition: var(--transition-smooth);
        }
        .show-more-btn:hover { border-color: var(--primary-green); color: var(--primary-green); }
        .show-more-btn.go-to-shop {
            padding: 14px 40px;
            background: var(--primary-green);
            color: #fff;
            border: none;
            font-size: 15px;
            font-weight: 700;
            letter-spacing: 1px;
            text-transform: uppercase;
        }
        .show-more-btn.go-to-shop:hover {
            background: var(--brand-mid);
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }

        @media (max-width: 1024px) { .subcategory-grid { grid-template-columns: repeat(3, 1fr); } .product-cards-grid { grid-template-columns: repeat(3, 1fr); } }
        @media (max-width: 768px) { .subcategory-grid { grid-template-columns: repeat(2, 1fr); } .product-cards-grid { grid-template-columns: repeat(2, 1fr); } .category-title { font-size: 28px; } }
        @media (max-width: 480px) { .product-cards-grid { grid-template-columns: repeat(2, 1fr); } }

        .product-card {
            background: #ffffff;
            border: 1px solid #e8ede7;
            border-radius: 0;
            overflow: hidden;
            transition: transform 0.22s ease, box-shadow 0.22s ease, border-color 0.22s ease;
            display: flex;
            flex-direction: column;
            cursor: pointer;
            box-shadow: 0 2px 8px rgba(15, 38, 11, 0.07);
        }
        .product-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 28px rgba(15, 38, 11, 0.14);
            border-color: rgba(255, 94, 58, 0.3);
        }
        .product-card.is-out-of-stock { opacity: 0.65; }
        .product-card-inner {
            display: flex;
            flex-direction: column;
            flex: 1;
            height: 100%;
        }
        .product-image-wrapper {
            position: relative;
            height: 190px;
            background: #f4f7f3;
            overflow: hidden;
            flex-shrink: 0;
        }
        .product-image-wrapper img, .product-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
            transition: transform 0.35s ease;
        }
        .product-card:hover .product-image-wrapper img { transform: scale(1.06); }
        .favorite-btn {
            position: absolute;
            top: 10px;
            right: 10px;
            width: 34px;
            height: 34px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.92);
            border: none;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            z-index: 2;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.12);
            transition: background 0.2s, transform 0.2s;
        }
        .favorite-btn:hover { background: #fff; transform: scale(1.12); }
        .favorite-btn.active .material-icons-outlined { color: #ff5e3a; }
        .favorite-btn .material-icons-outlined { font-size: 18px; color: #666; }
        .stock-badge {
            position: absolute;
            top: 10px;
            left: 10px;
            padding: 3px 9px;
            border-radius: 0;
            font-size: 10px;
            font-weight: 700;
            letter-spacing: 0.4px;
            text-transform: uppercase;
            z-index: 2;
        }
        .stock-badge.sale { background: #ff5e3a; color: #fff; }
        .stock-badge.new { background: #0f260b; color: #caed95; }
        .stock-badge.out { background: #9ca3af; color: #fff; }
        .stock-badge.discount { background: #ff5e3a; color: #fff; }
        .product-image { width: 100%; height: 100%; overflow: hidden; }
        .product-info {
            padding: 12px 14px 14px;
            display: flex;
            flex-direction: column;
            flex: 1;
            gap: 6px;
        }
        .product-name {
            font-size: 14px;
            font-weight: 700;
            color: #0f260b;
            line-height: 1.3;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            min-height: 2.6em;
        }
        .product-shop { font-size: 11px; color: #8a9b88; font-weight: 500; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .shop-link { color: inherit; border-bottom: 1px solid transparent; transition: var(--transition-smooth); }
        .shop-link:hover { color: var(--primary-orange); border-bottom-color: var(--primary-orange); }
        .price-row { display: flex; align-items: center; justify-content: space-between; gap: 8px; flex-wrap: wrap; }
        .price-group { display: flex; align-items: baseline; gap: 6px; }
        .current-price { font-size: 18px; font-weight: 800; color: #ff5e3a; letter-spacing: -0.02em; }
        .original-price { font-size: 12px; color: #9ca3af; text-decoration: line-through; font-weight: 400; }
        .discount-text { font-size: 11px; font-weight: 700; color: #ff5e3a; background: #fff0ec; padding: 2px 7px; border-radius: 0; white-space: nowrap; }
        .unit { font-size: 12px; color: #6b7280; }
        .rating-row { display: flex; align-items: center; gap: 4px; margin-top: 2px; }
        .rating-stars { display: inline-flex; align-items: center; gap: 2px; }
        .rating-stars .material-icons-outlined { font-size: 12px; color: #f4b740; }
        .rating-count { font-size: 11px; color: #9ca3af; }
        .add-to-cart-btn {
            width: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 7px;
            padding: 9px 12px;
            background: #0f260b;
            color: #fff;
            border: none;
            border-radius: 0;
            font-size: 12px;
            font-weight: 700;
            cursor: pointer;
            transition: background 0.22s, transform 0.18s;
            letter-spacing: 0.3px;
            margin-top: auto;
        }
        .add-to-cart-btn:hover:not([disabled]) { background: #1c3c17; transform: translateY(-1px); }
        .add-to-cart-btn:disabled { background: #ccc; cursor: not-allowed; transform: none; }
        .add-to-cart-btn .material-icons-outlined { font-size: 16px; }

        .cart-toast { position: fixed; bottom: 24px; right: 24px; background: var(--brand-dark); color: #fff; display: flex; align-items: center; gap: 10px; padding: 14px 20px; font-size: 14px; font-weight: 600; box-shadow: 0 12px 30px rgba(15, 38, 11, 0.3); transform: translateY(80px); opacity: 0; transition: all 0.35s cubic-bezier(0.4, 0, 0.2, 1); z-index: 9999; pointer-events: none; border-radius: 0; }
        .cart-toast.show { transform: translateY(0); opacity: 1; }
        .cart-toast .material-icons-outlined { font-size: 20px; color: #a5d6a7; }

        .site-footer { background-color: #0b0f0b; background-image: radial-gradient(circle at 15% 0%, rgba(255, 94, 58, 0.08) 0%, transparent 35%), radial-gradient(circle at 85% 100%, rgba(202, 237, 149, 0.06) 0%, transparent 35%), linear-gradient(135deg, #1a2219 0%, #050705 100%); color: #fff; padding: 64px 0 24px; margin-top: 48px; position: relative; overflow: hidden; }
        .site-footer a { transition: color 0.3s; }
        .footer-newsletter { display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 24px; padding-bottom: 40px; border-bottom: 1px solid rgba(255,255,255,0.1); margin-bottom: 40px; position: relative; z-index: 1; }
        .newsletter-content h3 { font-size: 24px; font-weight: 700; color: #fff; margin-bottom: 8px; }
        .newsletter-content p { color: rgba(255,255,255,0.7); font-size: 15px; margin-top: 4px; }
        .footer-grid { display: grid; grid-template-columns: 2fr 1fr 1fr 1.2fr; gap: 32px; align-items: start; position: relative; z-index: 1; }
        .brand-row { display: flex; align-items: center; gap: 12px; margin-bottom: 16px; }
        .footer-brand img { width: 56px; height: 56px; object-fit: contain; }
        .brand-name { font-weight: 700; font-style: italic; font-size: 28px; }
        .footer-tagline { color: rgba(255,255,255,0.8); font-size: 15px; line-height: 1.6; margin-bottom: 12px; }
        .footer-slogan { font-size: 14px; font-weight: 700; letter-spacing: 0.8px; text-transform: uppercase; color: #caed95; margin-bottom: 24px; }
        .social-links { display: flex; gap: 16px; margin-bottom: 24px; }
        .social-links a { display: flex; align-items: center; justify-content: center; width: 36px; height: 36px; background: rgba(255,255,255,0.1); border-radius: 50%; color: #fff; text-decoration: none; }
        .social-links a:hover { background: #ff5e3a; transform: translateY(-2px); }
        .footer-col h4 { font-size: 16px; margin-bottom: 20px; letter-spacing: 0.5px; text-transform: uppercase; color: #fff; font-weight: 700; }
        .footer-col a { display: block; color: rgba(255,255,255,0.7); text-decoration: none; font-size: 14px; margin-bottom: 12px; }
        .footer-col a:hover { color: #ff5e3a; padding-left: 4px; }
        .footer-col p { color: rgba(255,255,255,0.7); font-size: 14px; margin-bottom: 14px; display: flex; align-items: center; gap: 10px; margin-top: 0; }
        .footer-bottom { margin-top: 48px; border-top: 1px solid rgba(255,255,255,0.1); padding-top: 24px; display: flex; justify-content: space-between; align-items: center; z-index: 1; position: relative; }
        .footer-bottom-left { color: rgba(255,255,255,0.6); font-size: 13px; }
        .footer-bottom-links { display: flex; gap: 24px; }
        .footer-bottom-links a { color: rgba(255,255,255,0.6); font-size: 13px; text-decoration: none; }
        .footer-bottom-links a:hover { color: #fff; }

        @media (max-width: 960px) { .top-bar-inner { grid-template-columns: 1fr; } .search-wrap { width: 100%; } .actions { justify-content: space-between; flex-wrap: wrap; } .footer-grid { grid-template-columns: 1fr 1fr; } .footer-brand { grid-column: span 2; } }
        @media (max-width: 768px) { .footer-newsletter { flex-direction: column; align-items: flex-start; } .footer-bottom { flex-direction: column; gap: 16px; align-items: center; text-align: center; } }
        @media (max-width: 640px) { .footer-grid { grid-template-columns: 1fr; } .footer-brand { grid-column: span 1; } .footer-bottom-links { justify-content: center; gap: 12px; } }
    </style>
</head>
<body>

    <header id="siteHeader">
        <div class="top-bar">
            <div class="page-wrap top-bar-inner">
                <div class="brand">
                    <a href="index.html" style="display: flex; align-items: center; gap: 12px;">
                        <img src="assets/logo.png" alt="HuddersHub logo">
                        <span class="brand-text">HuddersHub</span>
                    </a>
                </div>
                <div class="search-wrap">
                    <form class="search-bar" action="search.php" method="get">
                        <input type="text" name="q" placeholder="Search" id="searchInput">
                        <button class="search-icon material-icons-outlined" type="submit" aria-label="Search" style="background:none; border:none; cursor:pointer; color:inherit; padding:0;">search</button>
                    </form>
                </div>
                <div class="actions">
                    <a class="action-btn user-menu" id="loginBtn" href="login.html">
                        <span class="material-icons-outlined" style="font-size: 24px;">person</span>
                        <span>Login / Signup</span>
                    </a>
                    <div class="user-dropdown-wrap" id="userDropdownWrap" style="display: none;">
                        <button class="action-btn user-menu" id="userDropdownBtn" style="border: none; background: none;">
                            <span class="material-icons-outlined" style="font-size: 24px;">person</span>
                            <span id="userGreeting">Hi, User</span>
                            <span class="material-icons-outlined" style="font-size: 16px;" id="dropChevron">expand_more</span>
                        </button>
                        <div class="user-dropdown" id="userDropdown">
                            <a href="../customer/profile.html" class="dropdown-item"><span class="material-icons-outlined">manage_accounts</span>My Profile</a>
                            <a href="../customer/orders.html" class="dropdown-item"><span class="material-icons-outlined">receipt_long</span>My Orders</a>
                            <a href="../customer/wishlist.html" class="dropdown-item"><span class="material-icons-outlined">favorite_border</span>Wishlist</a>
                            <div class="dropdown-divider"></div>
                            <button class="dropdown-item logout-item" id="logoutBtn"><span class="material-icons-outlined">logout</span>Log out</button>
                        </div>
                    </div>
                    <a class="icon-with-badge" href="cart.html" id="cartTrigger">
                        <span class="material-icons-outlined">shopping_cart</span>
                        <span class="badge" id="cartCount"><?php echo (int)$cartCount; ?></span>
                    </a>
                    <a class="icon-with-badge" href="../customer/wishlist.html">
                        <span class="material-icons-outlined">favorite_border</span>
                        <span class="badge" id="wishlistCount">0</span>
                    </a>
                </div>
            </div>
        </div>
        <nav class="nav-bar">
            <div class="page-wrap">
                <div class="nav-list">
                    <a href="index.html" class="nav-item primary">
                        <span class="material-icons-outlined" style="font-size: 18px;">home</span> Home
                    </a>
                    <span class="nav-separator"></span>
                    <div class="categories-wrapper">
                        <span class="nav-item is-active">
                            <span class="material-icons-outlined" style="font-size: 18px;">menu</span> Categories
                        </span>
                        <div class="categories-dropdown">
                            <div class="dropdown-section">
                                <div class="dropdown-section-title">Browse by Shop</div>
                                <div class="dropdown-divider"></div>
                                <a href="category.php?shop=butcher" class="dropdown-item-link">Butcher</a>
                                <a href="category.php?shop=greengrocer" class="dropdown-item-link">Greengrocer</a>
                                <a href="category.php?shop=fishmonger" class="dropdown-item-link">Fishmonger</a>
                                <a href="category.php?shop=bakery" class="dropdown-item-link">Bakery</a>
                                <a href="category.php?shop=delicatessen" class="dropdown-item-link">Delicatessen</a>
                            </div>
                            <div class="dropdown-section">
                                <div class="dropdown-section-title">Browse by Type</div>
                                <div class="dropdown-divider"></div>
                                <a href="category.php?type=veg" class="dropdown-item-link">Vegetarian</a>
                                <a href="category.php?type=non-veg" class="dropdown-item-link">Non-Vegetarian</a>
                                <a href="category.php?type=vegan" class="dropdown-item-link">Vegan</a>
                                <a href="category.php?type=gluten-free" class="dropdown-item-link">Gluten Free</a>
                            </div>
                            <div class="dropdown-divider"></div>
                            <a href="category.php" class="dropdown-item-link all-categories">All Categories</a>
                        </div>
                    </div>
                    <span class="nav-separator"></span>
                    <a href="about.html" class="nav-item">About</a>
                    <span class="nav-separator"></span>
                    <a href="contact.html" class="nav-item">Contact</a>
                </div>
            </div>
        </nav>
    </header>

    <main class="category-page">
        <div class="page-wrap">
            <h2 class="section-title-lg">Browse Categories</h2>
            
            <div class="type-strip">
                <div class="type-header">
                    <h2>Browse by Type</h2>
                    <span>Filter products by dietary preference</span>
                </div>
                <div class="type-tabs">
                    <a href="category.php<?php echo $shop ? '?shop='.$shop : ''; ?>" class="type-tab <?php echo !$type ? 'is-active' : ''; ?>">All <?php echo $shop ? ucfirst($shop) : 'Products'; ?></a>
                    <a href="category.php?type=veg<?php echo $shop ? '&shop='.$shop : ''; ?>" class="type-tab <?php echo $type === 'veg' ? 'is-active' : ''; ?>">Vegetarian</a>
                    <a href="category.php?type=non-veg<?php echo $shop ? '&shop='.$shop : ''; ?>" class="type-tab <?php echo $type === 'non-veg' ? 'is-active' : ''; ?>">Non-Vegetarian</a>
                    <a href="category.php?type=vegan<?php echo $shop ? '&shop='.$shop : ''; ?>" class="type-tab <?php echo $type === 'vegan' ? 'is-active' : ''; ?>">Vegan</a>
                    <a href="category.php?type=gluten-free<?php echo $shop ? '&shop='.$shop : ''; ?>" class="type-tab <?php echo $type === 'gluten-free' ? 'is-active' : ''; ?>">Gluten Free</a>
                </div>
            </div>

            <div class="shop-selector">
                <div class="shop-row">
                    <a href="category.php?shop=fishmonger" class="shop-card <?php echo $shop === 'fishmonger' ? 'is-active' : ''; ?>" data-shop="fishmonger"><span>Fishmonger</span></a>
                    <a href="category.php?shop=greengrocer" class="shop-card <?php echo $shop === 'greengrocer' ? 'is-active' : ''; ?>" data-shop="greengrocer"><span>Greengrocer</span></a>
                    <a href="category.php?shop=bakery" class="shop-card <?php echo $shop === 'bakery' ? 'is-active' : ''; ?>" data-shop="bakery"><span>Bakery</span></a>
                </div>
                <div class="shop-row">
                    <a href="category.php?shop=butcher" class="shop-card <?php echo $shop === 'butcher' ? 'is-active' : ''; ?>" data-shop="butcher"><span>Butcher</span></a>
                    <a href="category.php?shop=delicatessen" class="shop-card <?php echo $shop === 'delicatessen' ? 'is-active' : ''; ?>" data-shop="delicatessen"><span>Delicatessen</span></a>
                </div>
            </div>

            <?php if ($shop || $type): ?>
            <section class="category-section">
                <h2 class="category-title"><?php echo htmlspecialchars($page_title); ?></h2>
                
                <?php if ($shop && !empty($subcategories)): ?>
                <div class="subcategory-grid">
                    <a href="category.php?shop=<?php echo $shop; ?>" class="sub-tile <?php echo !$sub ? 'is-active' : ''; ?>"><span>All <?php echo ucfirst($shop); ?></span></a>
                    <?php foreach(array_slice($subcategories, 0, 5) as $subcat): ?>
                        <a href="category.php?shop=<?php echo $shop; ?>&sub=<?php echo urlencode(strtolower($subcat)); ?>"
                           class="sub-tile <?php echo $sub === strtolower($subcat) ? 'is-active' : ''; ?>" data-sub="<?php echo strtolower($subcat); ?>">
                            <span><?php echo htmlspecialchars($subcat); ?></span>
                        </a>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>

                <div class="products-header">
                    <div class="products-header-left">
                        <?php echo $totalCount; ?> <span>products found</span>
                    </div>
                    <div class="products-header-right">
                        <button class="sort-btn" onclick="cycleSort()">
                            <span class="material-icons-outlined">sort</span>
                            Sort: <?php echo ucfirst(str_replace('-', ' ', $sort)); ?>
                        </button>
                        <button class="filter-btn" onclick="toggleInStock()">
                            <span class="material-icons-outlined">filter_list</span>
                            <?php echo isset($_GET['in_stock']) && $_GET['in_stock'] === '1' ? 'In Stock Only' : 'All Availability'; ?>
                        </button>
                    </div>
                </div>

                <div class="product-cards-grid" id="productGrid">
                    <?php if (empty($products)): ?>
                        <div class="no-products" style="grid-column: 1 / -1; text-align: center; padding: 60px; background: var(--bg-gray); border: 1px solid var(--border-light);">
                            <span class="material-icons-outlined" style="font-size: 48px; color: var(--text-muted); margin-bottom: 16px;">search_off</span>
                            <h3 style="font-size: 20px; color: var(--text-black); margin-bottom: 8px;">No products found</h3>
                            <p style="color: var(--text-medium-gray);">Try adjusting your filters or browse another category.</p>
                        </div>
                    <?php else: ?>
                        <?php foreach($products as $p): echo buildProductCard($p); endforeach; ?>
                    <?php endif; ?>
                </div>

                <?php if ($shop && isset($shopId)): ?>
                <div class="show-more-wrap">
                    <a href="shop.php?shop_id=<?php echo $shopId; ?>" class="show-more-btn go-to-shop">GO TO <?php echo strtoupper($shop); ?> SHOP</a>
                </div>
                <?php endif; ?>
            </section>
            <?php else: ?>
            <section class="category-section">
                <h2 class="category-title">Butcher</h2>
                <div class="subcategory-grid">
                    <a href="category.php?shop=butcher&sub=beef" class="sub-tile" data-sub="beef"><span>Beef</span></a>
                    <a href="category.php?shop=butcher&sub=chicken" class="sub-tile" data-sub="chicken"><span>Chicken</span></a>
                    <a href="category.php?shop=butcher&sub=lamb" class="sub-tile" data-sub="lamb"><span>Lamb</span></a>
                    <a href="category.php?shop=butcher&sub=pork" class="sub-tile" data-sub="pork"><span>Pork</span></a>
                    <a href="category.php?shop=butcher&sub=sausages" class="sub-tile" data-sub="sausages"><span>Sausages</span></a>
                </div>
                <div class="product-cards-grid">
                    <?php foreach($butcherProducts as $p): echo buildProductCard($p); endforeach; ?>
                </div>
                <div class="show-more-wrap">
                    <a href="shop.php?shop_id=<?php echo $butcherId; ?>" class="show-more-btn go-to-shop">GO TO SHOP</a>
                </div>
            </section>

            <section class="category-section">
                <h2 class="category-title">Greengrocer</h2>
                <div class="subcategory-grid">
                    <a href="category.php?shop=greengrocer&sub=fruit" class="sub-tile" data-sub="fruit"><span>Fruit</span></a>
                    <a href="category.php?shop=greengrocer&sub=vegetables" class="sub-tile" data-sub="vegetables"><span>Vegetables</span></a>
                    <a href="category.php?shop=greengrocer&sub=herbs" class="sub-tile" data-sub="herbs"><span>Herbs</span></a>
                    <a href="category.php?shop=greengrocer&sub=organic" class="sub-tile" data-sub="organic"><span>Organic</span></a>
                    <a href="category.php?shop=greengrocer&sub=seasonal" class="sub-tile" data-sub="seasonal"><span>Seasonal</span></a>
                </div>
                <div class="product-cards-grid">
                    <?php foreach($greengrocerProducts as $p): echo buildProductCard($p); endforeach; ?>
                </div>
                <div class="show-more-wrap">
                    <a href="shop.php?shop_id=<?php echo $greengrocerId; ?>" class="show-more-btn go-to-shop">GO TO SHOP</a>
                </div>
            </section>

            <!-- Banner 1 -->
            <div style="margin: 40px 0; border-radius: 12px; overflow: hidden; box-shadow: 0 8px 24px rgba(15,38,11,0.1);">
                <img src="assets/Banner/banner-3.png" alt="Fresh Fish & Seafood" style="width: 100%; height: auto; display: block;">
            </div>

            <!-- Fishmonger Section -->
            <section class="category-section">
                <h2 class="category-title">Fishmonger</h2>
                <div class="subcategory-grid">
                    <a href="category.php?shop=fishmonger&sub=fresh-fish" class="sub-tile" data-sub="fresh-fish"><span>Fresh Fish</span></a>
                    <a href="category.php?shop=fishmonger&sub=shellfish" class="sub-tile" data-sub="shellfish"><span>Shellfish</span></a>
                    <a href="category.php?shop=fishmonger&sub=smoked" class="sub-tile" data-sub="smoked"><span>Smoked</span></a>
                    <a href="category.php?shop=fishmonger&sub=prepared" class="sub-tile" data-sub="prepared"><span>Prepared</span></a>
                    <a href="category.php?shop=fishmonger&sub=seasonal" class="sub-tile" data-sub="seasonal-fish"><span>Seasonal</span></a>
                </div>
                <div class="product-cards-grid">
                    <?php foreach($fishmongerProducts as $p): echo buildProductCard($p); endforeach; ?>
                </div>
            </section>

            <!-- Bakery Section -->
            <section class="category-section">
                <h2 class="category-title">Bakery</h2>
                <div class="subcategory-grid">
                    <a href="category.php?shop=bakery&sub=bread" class="sub-tile" data-sub="bread"><span>Bread</span></a>
                    <a href="category.php?shop=bakery&sub=pastries" class="sub-tile" data-sub="pastries"><span>Pastries</span></a>
                    <a href="category.php?shop=bakery&sub=cakes" class="sub-tile" data-sub="cakes"><span>Cakes</span></a>
                    <a href="category.php?shop=bakery&sub=cookies" class="sub-tile" data-sub="cookies"><span>Cookies</span></a>
                    <a href="category.php?shop=bakery&sub=sourdough" class="sub-tile" data-sub="sourdough"><span>Sourdough</span></a>
                </div>
                <div class="product-cards-grid">
                    <?php foreach($bakeryProducts as $p): echo buildProductCard($p); endforeach; ?>
                </div>
                <div class="show-more-wrap">
                    <a href="shop.php?shop_id=<?php echo $bakeryId; ?>" class="show-more-btn go-to-shop">GO TO SHOP</a>
                </div>
            </section>

            <!-- Banner 2 -->
            <div style="margin: 40px 0; border-radius: 12px; overflow: hidden; box-shadow: 0 8px 24px rgba(15,38,11,0.1);">
                <img src="assets/Banner/banner-4.png" alt="Artisan Bakery & Deli" style="width: 100%; height: auto; display: block;">
            </div>

            <!-- Delicatessen Section -->
            <section class="category-section">
                <h2 class="category-title">Delicatessen</h2>
                <div class="subcategory-grid">
                    <a href="category.php?shop=delicatessen&sub=cheese" class="sub-tile" data-sub="cheese"><span>Cheese</span></a>
                    <a href="category.php?shop=delicatessen&sub=cured-meats" class="sub-tile" data-sub="cured-meats"><span>Cured Meats</span></a>
                    <a href="category.php?shop=delicatessen&sub=olives" class="sub-tile" data-sub="olives"><span>Olives</span></a>
                    <a href="category.php?shop=delicatessen&sub=dips" class="sub-tile" data-sub="dips"><span>Dips</span></a>
                    <a href="category.php?shop=delicatessen&sub=antipasti" class="sub-tile" data-sub="antipasti"><span>Antipasti</span></a>
                </div>
                <div class="product-cards-grid">
                    <?php foreach($delicatessenProducts as $p): echo buildProductCard($p); endforeach; ?>
                </div>
                <div class="show-more-wrap">
                    <a href="shop.php?shop_id=<?php echo $delicatessenId; ?>" class="show-more-btn go-to-shop">GO TO SHOP</a>
                </div>
            </section>
            <?php endif; ?>
        </div>
    </main>

    <div class="cart-toast" id="cartToast">
        <span class="material-icons-outlined">check_circle</span>
        <span id="cartToastMsg">Item added to cart</span>
    </div>

    <footer class="site-footer">
        <div class="page-wrap footer-newsletter">
            <div class="newsletter-content">
                <h3>Fresh from local farms to your table</h3>
                <p>"Quality you can taste, community you can feel."</p>
            </div>
            <div style="display: flex; gap: 12px; align-items: center; width: auto; max-width: none;">
                <a href="login.html" style="padding: 12px 24px; background: rgba(255, 255, 255, 0.1); border: 1px solid rgba(255, 255, 255, 0.2); color: #fff; font-weight: 600; text-decoration: none; border-radius: 4px;">Log In</a>
                <a href="signup.html" style="padding: 12px 24px; background: #ff5e3a; border: none; color: #fff; font-weight: 600; text-decoration: none; border-radius: 4px;">Register Now</a>
            </div>
        </div>
        <div class="page-wrap footer-grid">
            <div class="footer-brand">
                <div class="brand-row">
                    <img src="assets/logo.png" alt="HuddersHub logo"><span class="brand-name">HuddersHub</span>
                </div>
                <p class="footer-tagline">Local food, trusted traders, and fresh picks curated for Huddersfield.</p>
                <p class="footer-slogan">Eat Fresh. Buy Local.</p>
                <div class="social-links">
                    <a href="#" aria-label="Facebook"><span class="material-icons-outlined">facebook</span></a>
                    <a href="#" aria-label="Instagram"><span class="material-icons-outlined">camera_alt</span></a>
                </div>
            </div>
            <div class="footer-col">
                <h4>Shop</h4>
                <a href="category.php?shop=greengrocer">Green Grocer</a>
                <a href="category.php?shop=butcher">The Butcher</a>
                <a href="category.php?shop=bakery">Bakery</a>
                <a href="category.php?shop=delicatessen">Delicatessen</a>
                <a href="category.php?shop=fishmonger">Fishmonger</a>
            </div>
            <div class="footer-col">
                <h4>Company</h4>
                <a href="about.html">About HuddersHub</a>
                <a href="register-trader.html">Become a Trader</a>
                <a href="faq.html">Help Center</a>
                <a href="refund.html">Returns &amp; Refunds</a>
            </div>
            <div class="footer-col">
                <h4>Contact</h4>
                <p><span class="material-icons-outlined" style="font-size: 16px;">location_on</span>Huddersfield, UK</p>
                <p><span class="material-icons-outlined" style="font-size: 16px;">mail</span>support@huddershub.test</p>
                <p><span class="material-icons-outlined" style="font-size: 16px;">phone</span>+44 1484 000 000</p>
            </div>
        </div>
        <div class="page-wrap footer-bottom">
            <div class="footer-bottom-left">
                <span>&copy; 2026 HuddersHub. All rights reserved.</span>
            </div>
            <div class="footer-bottom-links">
                <a href="privacy.html">Privacy Policy</a>
                <a href="terms.html">Terms of Service</a>
                <a href="register-trader.html">Apply as a trader</a>
            </div>
        </div>
    </footer>

    <script>
        window.addEventListener('scroll', function() {
            const header = document.getElementById('siteHeader');
            if (header) header.classList.toggle('scrolled', window.scrollY > 50);
        });

        function cycleSort() {
            const sorts = ['name', 'price-low', 'price-high', 'rating', 'newest'];
            const current = new URLSearchParams(window.location.search).get('sort') || 'name';
            const idx = sorts.indexOf(current);
            const next = sorts[(idx + 1) % sorts.length];
            const url = new URL(window.location.href);
            url.searchParams.set('sort', next);
            window.location.href = url.toString();
        }

        function toggleInStock() {
            const url = new URL(window.location.href);
            const current = url.searchParams.get('in_stock');
            if (current === '1') {
                url.searchParams.delete('in_stock');
            } else {
                url.searchParams.set('in_stock', '1');
            }
            window.location.href = url.toString();
        }

        async function addToCart(productId) {
            const uid = sessionStorage.getItem('user_id');
            if (!uid) { window.location.href = 'login.html'; return; }
            try {
                const res = await fetch('../api/cart/add-to-cart.php', {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ product_id: productId, quantity: 1 })
                });
                const data = await res.json();
                if (data.success) {
                    const countEl = document.getElementById('cartCount');
                    if (countEl) countEl.textContent = data.new_count || (parseInt(countEl.textContent) || 0) + 1;
                    showToast('Added to cart!');
                    loadCartDrawer();
                } else if (data.redirect) {
                    window.location.href = 'login.html';
                } else {
                    showToast(data.message || 'Failed to add to cart', 'error');
                }
            } catch (e) { showToast('Network error', 'error'); }
        }

        function showToast(msg, type = 'success') {
            const toast = document.getElementById('cartToast');
            const msgEl = document.getElementById('cartToastMsg');
            if (!toast || !msgEl) return;
            msgEl.textContent = msg;
            toast.classList.add('show');
            setTimeout(() => toast.classList.remove('show'), 3000);
        }

        async function toggleFavorite(btn, productId) {
            const uid = sessionStorage.getItem('user_id');
            if (!uid) { window.location.href = 'login.html'; return; }
            const icon = btn.querySelector('.material-icons-outlined');
            const isActive = btn.classList.contains('active');
            try {
                const endpoint = isActive ? '../api/customer/remove-wishlist.php' : '../api/customer/add-to-wishlist.php';
                const res = await fetch(endpoint, {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ product_id: productId })
                });
                const data = await res.json();
                if (data.success) {
                    btn.classList.toggle('active');
                    icon.textContent = !isActive ? 'favorite' : 'favorite_border';
                    loadWishlistCount();
                }
            } catch (e) {}
        }

        function initUserMenu() {
            const uid = sessionStorage.getItem('user_id');
            const name = sessionStorage.getItem('user_name');
            const loginBtn = document.getElementById('loginBtn');
            const wrap = document.getElementById('userDropdownWrap');
            if (!uid || !name) { loginBtn.style.display = ''; wrap.style.display = 'none'; return; }
            loginBtn.style.display = 'none'; wrap.style.display = '';
            document.getElementById('userGreeting').textContent = 'Hi, ' + name;
            const btn = document.getElementById('userDropdownBtn');
            const dd = document.getElementById('userDropdown');
            btn.addEventListener('click', function(e) { e.stopPropagation(); dd.classList.toggle('open'); });
            document.addEventListener('click', function(e) { if (!wrap.contains(e.target)) dd.classList.remove('open'); });
            document.getElementById('logoutBtn').addEventListener('click', function() { sessionStorage.clear(); window.location.reload(); });
        }

        async function loadWishlistCount() {
            const uid = sessionStorage.getItem('user_id');
            if (!uid) return;
            try {
                const res = await fetch('../api/customer/get-wishlist.php', { credentials: 'same-origin' });
                const data = await res.json();
                const wc = document.getElementById('wishlistCount');
                if (wc && data.success && data.data) wc.textContent = data.data.length;
            } catch (e) {}
        }

        // Product card interactions
        document.addEventListener('click', function(e) {
            const favBtn = e.target.closest('.favorite-btn');
            if (favBtn) {
                e.preventDefault();
                e.stopPropagation();
                const card = favBtn.closest('.product-card-inner, .carousel-card-inner');
                const pid = card?.dataset.productId;
                if (pid) toggleFavorite(favBtn, parseInt(pid));
                return;
            }
            const cartBtn = e.target.closest('.add-to-cart-btn');
            if (cartBtn) {
                e.preventDefault();
                e.stopPropagation();
                const card = cartBtn.closest('.product-card-inner, .carousel-card-inner');
                const pid = card?.dataset.productId;
                if (pid) addToCart(parseInt(pid));
                return;
            }
            const cardInner = e.target.closest('.product-card-inner, .carousel-card-inner');
            if (cardInner && !e.target.closest('button, a')) {
                const link = cardInner.dataset.link;
                if (link) window.location.href = link;
            }
        });

        initUserMenu();
        loadCartDrawer();
        loadWishlistCount();
    </script>
</body>
</html>
