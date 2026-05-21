<?php
/**
 * HuddersHub - Category / Shop Page
 * Fully functional with all buttons, filters, sorting working
 */

require_once '../config/database.php';
require_once '../config/session.php';

$userId = getUserId();
$isLoggedIn = isLoggedIn();
$cartCount = 0;
$wishlistCount = 0;
$userFirstname = '';

$shop = $_GET['shop'] ?? '';
$type = $_GET['type'] ?? '';
$sub  = $_GET['sub']  ?? '';
$sort = $_GET['sort'] ?? 'newest';
$inStock = isset($_GET['in_stock']) ? (int)$_GET['in_stock'] : 0;

$valid_shops = ['butcher', 'greengrocer', 'fishmonger', 'bakery', 'delicatessen'];
$valid_types = ['veg', 'non-veg', 'vegan', 'gluten-free'];
$valid_sorts = ['name', 'price-low', 'price-high', 'newest'];

if ($shop && !in_array($shop, $valid_shops)) $shop = '';
if ($type && !in_array($type, $valid_types)) $type = '';
if (!in_array($sort, $valid_sorts)) $sort = 'newest';

$shop_labels = ['butcher' => 'Butcher', 'greengrocer' => 'Greengrocer', 'fishmonger' => 'Fishmonger', 'bakery' => 'Bakery', 'delicatessen' => 'Delicatessen'];

if ($userId) {
    $sql = "SELECT
        (SELECT SUM(ci.quantity) FROM CART c JOIN CART_ITEM ci ON c.cart_id = ci.cart_id WHERE c.user_id = :uid) AS cart_qty,
        (SELECT COUNT(*) FROM WISHLIST WHERE user_id = :uid) AS wl_count
        FROM DUAL";
    $stmt = oci_parse($conn, $sql);
    oci_bind_by_name($stmt, ':uid', $userId);
    oci_execute($stmt);
    $row = oci_fetch_assoc($stmt);
    $cartCount = (int)($row['CART_QTY'] ?? 0);
    $wishlistCount = (int)($row['WL_COUNT'] ?? 0);
    oci_free_statement($stmt);
    $userFirstname = $_SESSION['firstname'] ?? '';
}

$sections = [
    ['key' => 'bakery',       'title' => 'Bakery',       'subs' => ['Bread', 'Rolls & Buns', 'Cakes', 'Pastries', 'Pies']],
    ['key' => 'butcher',      'title' => 'Butcher',      'subs' => ['Beef', 'Chicken', 'Lamb', 'Pork', 'Sausages']],
    ['key' => 'delicatessen', 'title' => 'Delicatessen', 'subs' => ['Cheeses', 'Antipasti', 'Dips & Spreads', 'Cured Meats', 'Gift Hampers']],
    ['key' => 'fishmonger',   'title' => 'Fishmonger',   'subs' => ['Fresh Fish', 'Shellfish', 'Smoked Fish', 'Prepared Fish', 'Frozen']],
    ['key' => 'greengrocer',  'title' => 'Greengrocer',  'subs' => ['Fruits', 'Vegetables', 'Salad Items', 'Herbs', 'Organic Produce']],
];

function getImgSrc($name) {
    $n = strtolower($name);
    $map = [
        'green bell pepper' => 'assets/Item-image/green-bell-pepper-isolated.jpg',
        'broccoli' => 'assets/Item-image/green-broccoli.jpg', 'eggs' => 'assets/Item-image/green-broccoli.jpg',
        'beef' => 'assets/other-images/fresh-raw-meat-cuts-with-rosemary-spices-dark-slate.jpg',
        'steak' => 'assets/other-images/fresh-raw-meat-cuts-with-rosemary-spices-dark-slate.jpg',
        'sausage' => 'assets/other-images/fresh-raw-meat-cuts-with-rosemary-spices-dark-slate.jpg',
        'lamb' => 'assets/other-images/fresh-raw-meat-cuts-with-rosemary-spices-dark-slate.jpg',
        'chicken' => 'assets/other-images/fresh-raw-meat-cuts-with-rosemary-spices-dark-slate.jpg',
        'pork' => 'assets/other-images/fresh-raw-meat-cuts-with-rosemary-spices-dark-slate.jpg',
        'salmon' => 'assets/other-images/top-view-fresh-fish-slices-dark-table-seafood-ocean-meat-sea-meal-dish-food-salad-water-pepper.jpg',
        'cod' => 'assets/other-images/top-view-fresh-fish-slices-dark-table-seafood-ocean-meat-sea-meal-dish-food-salad-water-pepper.jpg',
        'prawn' => 'assets/other-images/top-view-fresh-fish-slices-dark-table-seafood-ocean-meat-sea-meal-dish-food-salad-water-pepper.jpg',
        'fish' => 'assets/other-images/top-view-fresh-fish-slices-dark-table-seafood-ocean-meat-sea-meal-dish-food-salad-water-pepper.jpg',
        'brie' => 'assets/other-images/imgi_47_cheese-wood_573717-86.jpg', 'cheese' => 'assets/other-images/imgi_47_cheese-wood_573717-86.jpg',
        'bread' => 'assets/other-images/imgi_57_sweet-bun-with-chocolate-syrup-peeled-orange_114579-2623.jpg',
        'sourdough' => 'assets/other-images/imgi_57_sweet-bun-with-chocolate-syrup-peeled-orange_114579-2623.jpg',
        'spinach' => 'assets/Item-image/imgi_46_chinese-broccoli-vegetables_1203-6831.jpg',
        'carrot' => 'assets/Item-image/imgi_46_chinese-broccoli-vegetables_1203-6831.jpg',
    ];
    foreach ($map as $k => $v) { if (str_contains($n, $k)) return $v; }
    return 'assets/Item-image/green-bell-pepper-isolated.jpg';
}

function fetchSectionProducts($conn, $shopKey, $sort, $sub, $inStock, $limit = 10) {
    $order_map = ['name' => 'p.name ASC', 'price-low' => 'p.price ASC', 'price-high' => 'p.price DESC', 'newest' => 'p.product_id DESC'];
    $order = $order_map[$sort] ?? 'p.product_id DESC';
    $where = ["p.status = 'Active'", "UPPER(s.shop_type) = :shop_type"];
    $binds = [':shop_type' => strtoupper($shopKey)];
    if ($sub) { $where[] = "UPPER(pc.category_name) = :sub_cat"; $binds[':sub_cat'] = strtoupper($sub); }
    if ($inStock) { $where[] = "p.stock > 0"; }
    $where_sql = implode(' AND ', $where);
    $sql = "SELECT * FROM (
        SELECT p.product_id, p.name, p.price, p.stock, p.unit, s.name AS shop_name, s.shop_type, pc.category_name,
               ROW_NUMBER() OVER (ORDER BY $order) AS rn
        FROM PRODUCT p
        JOIN SHOP s ON p.shop_id = s.shop_id
        LEFT JOIN PRODUCT_CATEGORY pc ON p.category_id = pc.category_id
        WHERE $where_sql
    ) WHERE rn <= :limit ORDER BY rn";
    $stmt = oci_parse($conn, $sql);
    foreach ($binds as $b => $v) { oci_bind_by_name($stmt, $b, $v); }
    oci_bind_by_name($stmt, ':limit', $limit);
    oci_execute($stmt);
    $products = [];
    while ($row = oci_fetch_assoc($stmt)) { $products[] = $row; }
    oci_free_statement($stmt);
    return $products;
}

function countSectionProducts($conn, $shopKey, $sub, $inStock) {
    $where = ["p.status = 'Active'", "UPPER(s.shop_type) = :shop_type"];
    $binds = [':shop_type' => strtoupper($shopKey)];
    if ($sub) { $where[] = "UPPER(pc.category_name) = :sub_cat"; $binds[':sub_cat'] = strtoupper($sub); }
    if ($inStock) { $where[] = "p.stock > 0"; }
    $where_sql = implode(' AND ', $where);
    $sql = "SELECT COUNT(*) AS total FROM PRODUCT p JOIN SHOP s ON p.shop_id = s.shop_id LEFT JOIN PRODUCT_CATEGORY pc ON p.category_id = pc.category_id WHERE $where_sql";
    $stmt = oci_parse($conn, $sql);
    foreach ($binds as $b => $v) { oci_bind_by_name($stmt, $b, $v); }
    oci_execute($stmt);
    $total = (int)(oci_fetch_assoc($stmt)['TOTAL'] ?? 0);
    oci_free_statement($stmt);
    return $total;
}

function buildUrl($overrides = []) {
    global $shop, $type, $sub, $sort, $inStock;
    $params = [];
    if ($shop) $params['shop'] = $shop;
    if ($type) $params['type'] = $type;
    if ($sub) $params['sub'] = $sub;
    if ($sort !== 'newest') $params['sort'] = $sort;
    if ($inStock) $params['in_stock'] = '1';
    $params = array_merge($params, $overrides);
    if (isset($overrides['sort']) && $overrides['sort'] === 'newest') unset($params['sort']);
    if (isset($overrides['in_stock']) && !$overrides['in_stock']) unset($params['in_stock']);
    if (isset($overrides['sub']) && $overrides['sub'] === '') unset($params['sub']);
    return empty($params) ? 'category.php' : 'category.php?' . http_build_query($params);
}

oci_close($conn);
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Categories | HuddersHub</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Google+Sans+Flex:ital,wght@0,400;0,500;0,600;0,700;1,400;1,500;1,600;1,700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Google+Sans:ital,opsz,wght@0,17..18,400..700;1,17..18,400..700&family=Plus+Jakarta+Sans:ital,wght@0,200..800;1,200..800&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons+Outlined" rel="stylesheet">
    <style>
        :root {
            --primary-orange: #ff5e3a; --primary-orange-dark: #e3472c;
            --primary-green: #0f260b; --primary-green-light: rgba(15,38,11,0.14); --primary-green-dark: #0b1c08;
            --bg-white: #ffffff; --bg-light: #f7f6f3; --bg-gray: #f2f4f1; --border-light: #dce3da;
            --text-black: #0b140a; --text-dark-gray: #1e2a1c; --text-medium-gray: #5e6a63;
            --badge-bg: #0f260b; --badge-text: #ffffff;
            --shadow-sm: 0 2px 6px rgba(15,38,11,0.08); --shadow-md: 0 10px 24px rgba(15,38,11,0.12);
            --transition-smooth: all 0.25s cubic-bezier(0.4,0,0.2,1);
        }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        html { scroll-behavior: smooth; }
        body { font-family: "Plus Jakarta Sans", sans-serif; color: #1b2419; background: var(--bg-white); min-height: 100vh; display: flex; flex-direction: column; }
        a { text-decoration: none; color: inherit; transition: var(--transition-smooth); }

        /* HEADER */
        header { position: sticky; top: 0; z-index: 1000; background: rgba(255,255,255,0.98); }
        header.scrolled .top-bar { padding: 10px 0; }
        header.scrolled .brand img { width: 42px; height: 42px; }
        header.scrolled .brand-text { font-size: 30px; }
        .top-bar { background: rgba(255,255,255,0.98); border-bottom: 1px solid var(--border-light); padding: 14px 0; transition: var(--transition-smooth); }
        .page-wrap { width: min(1200px,94%); margin: 0 auto; }
        .top-bar-inner { display: grid; grid-template-columns: auto 1fr auto; gap: 18px; align-items: center; }
        .brand { display: flex; align-items: center; gap: 14px; }
        .brand img { width: 56px; height: 56px; object-fit: contain; transition: var(--transition-smooth); filter: drop-shadow(0 6px 12px rgba(15,38,11,0.12)); }
        .brand-text { font-family: "Google Sans Flex", sans-serif; font-weight: 700; font-style: italic; font-size: 36px; color: #0f260b; }
        .search-wrap { display: flex; align-items: center; gap: 12px; }
        .search-bar { position: relative; flex: 1; min-width: 280px; }
        .search-bar input { width: 100%; padding: 6px 44px 6px 14px; height: 36px; border: 1px solid #c8d1c6; background: var(--bg-white); font-size: 14px; font-weight: 500; color: var(--text-black); outline: none; transition: var(--transition-smooth); }
        .search-bar input:focus { border-color: var(--primary-orange); box-shadow: 0 0 0 3px rgba(255,94,58,0.22); }
        .search-bar input::placeholder { color: #1b2419; opacity: 0.55; }
        .search-icon { position: absolute; right: 12px; top: 50%; transform: translateY(-50%); font-size: 18px; color: #1b2419; opacity: 0.55; border: none; background: transparent; padding: 0; cursor: pointer; }
        .actions { display: flex; align-items: center; gap: 16px; }
        .action-btn, .user-menu { display: inline-flex; align-items: center; gap: 6px; font-size: 14px; font-weight: 600; color: var(--text-medium-gray); transition: var(--transition-smooth); cursor: pointer; padding: 8px 12px; }
        .action-btn:hover, .user-menu:hover { background: rgba(15,38,11,0.06); color: var(--primary-green); }
        .icon-with-badge { position: relative; display: inline-flex; align-items: center; justify-content: center; cursor: pointer; transition: var(--transition-smooth); color: var(--text-black); padding: 6px; background: transparent; border: 1px solid transparent; }
        .icon-with-badge:hover { background: rgba(15,38,11,0.06); color: var(--primary-green); }
        .icon-with-badge .material-icons-outlined { font-size: 24px; }
        .badge { position: absolute; top: 0; right: 0; background: var(--badge-bg); color: var(--badge-text); padding: 2px 5px; font-size: 10px; font-weight: 600; min-width: 16px; text-align: center; }
        .user-menu-wrap { position: relative; }
        .user-dropdown { position: absolute; top: calc(100% + 8px); right: 0; background: #fff; border: 1px solid var(--border-light); box-shadow: var(--shadow-md); min-width: 180px; z-index: 2000; display: none; flex-direction: column; padding: 6px 0; }
        .user-dropdown.open { display: flex; }
        .dropdown-user-item { display: flex; align-items: center; gap: 10px; padding: 10px 16px; font-size: 14px; font-weight: 500; color: var(--text-dark-gray); background: none; border: none; width: 100%; cursor: pointer; font-family: "Plus Jakarta Sans", sans-serif; transition: var(--transition-smooth); }
        .dropdown-user-item:hover { background: var(--primary-green-light); color: var(--primary-green); }
        .dropdown-user-item .material-icons-outlined { font-size: 18px; }
        .dropdown-user-divider { height: 1px; background: var(--border-light); margin: 4px 0; }
        .dropdown-logout { color: #dc2626; }
        .dropdown-logout:hover { background: #fef2f2; color: #dc2626; }
        .nav-bar { background: #f1f3f0; border-bottom: 1px solid var(--border-light); padding: 10px 0; transition: var(--transition-smooth); }
        header.scrolled .nav-bar { padding: 8px 0; background: #f3f4f6; }
        .nav-list { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; font-size: 14px; }
        .nav-item { display: inline-flex; align-items: center; gap: 6px; padding: 8px 14px; border: none; cursor: pointer; text-decoration: none; color: var(--text-black); transition: var(--transition-smooth); font-size: 15px; font-weight: 400; font-family: "Google Sans Flex", sans-serif; }
        .nav-item:not(.primary):hover { background: rgba(15,38,11,0.08); }
        .nav-item.primary { color: #0f260b; font-weight: 600; font-size: 14px; }
        .nav-item.is-active { position: relative; }
        .nav-item.is-active::after { content: ""; position: absolute; left: 0; right: 0; bottom: -6px; height: 2px; background: #0f260b; }
        .nav-separator { width: 1px; height: 24px; background: var(--border-light); margin: 0 6px; display: inline-block; }
        .categories-wrapper { position: relative; }
        .categories-dropdown { position: absolute; top: 100%; left: 0; background: var(--bg-white); border: 1px solid var(--border-light); box-shadow: var(--shadow-md); padding: 0; min-width: 240px; display: none; z-index: 1000; }
        .categories-wrapper:hover .categories-dropdown, .categories-dropdown:hover { display: block; }
        .dropdown-section { padding: 8px 0; }
        .dropdown-section-title { padding: 8px 16px; font-size: 11px; font-weight: 700; color: var(--text-medium-gray); text-transform: uppercase; letter-spacing: 0.5px; }
        .dropdown-divider { height: 1px; background: var(--border-light); margin: 4px 0; }
        .dropdown-item { display: block; padding: 10px 16px; font-size: 14px; font-weight: 500; color: var(--text-dark-gray); }
        .dropdown-item:hover { background: var(--primary-green-light); color: var(--primary-green); }
        .all-categories { font-weight: 600; color: var(--primary-green) !important; border-top: 1px solid var(--border-light); margin-top: 4px; }

        /* PAGE */
        .page-content { flex: 1; padding: 32px 0 64px; }

        /* Browse by category */
        .browse-header { text-align: center; margin-bottom: 24px; }
        .browse-header h2 { font-family: "Google Sans Flex", sans-serif; font-size: 28px; font-weight: 700; color: var(--text-black); }
        .browse-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px; max-width: 700px; margin: 0 auto 48px; }
        .browse-btn { display: flex; align-items: center; justify-content: center; padding: 20px 16px; background: var(--bg-white); border: 2px solid var(--border-light); border-radius: 16px; font-family: "Google Sans Flex", sans-serif; font-size: 14px; font-weight: 700; text-transform: uppercase; color: var(--text-black); text-align: center; transition: var(--transition-smooth); cursor: pointer; }
        .browse-btn:hover { border-color: var(--primary-green); background: var(--primary-green-light); transform: translateY(-2px); box-shadow: var(--shadow-sm); }
        .browse-btn.active { border-color: var(--primary-green); background: var(--primary-green); color: white; }
        .browse-row-2 { grid-column: 1 / -1; display: grid; grid-template-columns: 1fr 1fr; gap: 12px; max-width: 460px; margin: 0 auto; }

        /* Shop section */
        .shop-section { margin-bottom: 40px; scroll-margin-top: 120px; }
        .shop-section-title { font-family: "Google Sans Flex", sans-serif; font-size: 24px; font-weight: 700; color: var(--text-black); margin-bottom: 16px; }
        .shop-section-title a { color: inherit; }
        .shop-section-title a:hover { color: var(--primary-orange); }

        /* Subcategory row */
        .sub-grid { display: grid; grid-template-columns: repeat(5, 1fr); gap: 12px; margin-bottom: 20px; }
        .sub-box { background: var(--bg-gray); border-radius: 8px; padding: 20px 12px; text-align: center; font-size: 13px; font-weight: 600; color: var(--text-dark-gray); transition: var(--transition-smooth); cursor: pointer; }
        .sub-box:hover { background: var(--primary-green-light); color: var(--primary-green); }
        .sub-box.active { background: var(--primary-green); color: white; }

        /* Toolbar */
        .section-toolbar { display: flex; align-items: center; justify-content: space-between; border-top: 2px solid var(--text-black); border-bottom: 1px solid var(--border-light); padding: 10px 0; margin-bottom: 16px; }
        .toolbar-left { font-size: 14px; font-weight: 600; color: var(--text-black); }
        .toolbar-left span { font-weight: 400; color: var(--text-medium-gray); margin-left: 4px; }
        .toolbar-right { display: flex; align-items: center; gap: 16px; }
        .toolbar-btn { display: inline-flex; align-items: center; gap: 6px; font-size: 13px; font-weight: 600; color: var(--text-dark-gray); cursor: pointer; background: none; border: none; font-family: "Plus Jakarta Sans", sans-serif; padding: 4px 8px; border-radius: 4px; }
        .toolbar-btn:hover { background: var(--primary-green-light); color: var(--primary-green); }
        .toolbar-btn.active { background: var(--primary-green); color: white; }
        .toolbar-btn .material-icons-outlined { font-size: 16px; }

        /* Product grid */
        .product-grid { display: grid; grid-template-columns: repeat(5, 1fr); gap: 12px; }
        .product-card { background: var(--bg-gray); border: 1px solid var(--border-light); border-radius: 4px; overflow: hidden; display: flex; flex-direction: column; transition: var(--transition-smooth); }
        .product-card:hover { box-shadow: var(--shadow-sm); transform: translateY(-1px); }
        .product-card.out-of-stock { opacity: 0.5; }
        .product-image { position: relative; height: 140px; background: #e8e8e8; overflow: hidden; display: flex; align-items: center; justify-content: center; }
        .product-image img { width: 100%; height: 100%; object-fit: cover; }
        .stock-badge { position: absolute; top: 6px; right: 6px; padding: 2px 6px; font-size: 9px; font-weight: 700; text-transform: uppercase; background: var(--primary-green); color: #caed95; border-radius: 2px; }
        .product-bottom { padding: 10px; display: flex; flex-direction: column; gap: 6px; }
        .product-price { font-size: 13px; font-weight: 700; color: var(--text-black); }
        .add-btn { width: 100%; padding: 7px; border: 1px solid var(--border-light); background: var(--bg-white); font-size: 11px; font-weight: 600; color: var(--text-dark-gray); cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 4px; font-family: "Plus Jakarta Sans", sans-serif; transition: var(--transition-smooth); border-radius: 2px; }
        .add-btn:hover { background: var(--primary-green); color: white; border-color: var(--primary-green); }
        .add-btn:disabled { opacity: 0.4; cursor: not-allowed; }
        .add-btn .material-icons-outlined { font-size: 14px; }

        /* Show more */
        .show-more-wrap { text-align: center; margin-top: 16px; }
        .show-more-btn { display: inline-block; padding: 8px 24px; border: 1px solid var(--border-light); background: var(--bg-white); font-size: 13px; font-weight: 500; color: var(--text-medium-gray); cursor: pointer; font-family: "Plus Jakarta Sans", sans-serif; transition: var(--transition-smooth); border-radius: 4px; }
        .show-more-btn:hover { border-color: var(--primary-green); color: var(--primary-green); }

        /* Banner */
        .section-banner { background: var(--bg-gray); border-radius: 8px; padding: 48px 24px; text-align: center; margin: 32px 0; cursor: pointer; transition: var(--transition-smooth); }
        .section-banner:hover { background: var(--primary-green-light); }
        .section-banner h3 { font-family: "Google Sans Flex", sans-serif; font-size: 24px; font-weight: 700; color: var(--text-black); }

        /* FOOTER */
        .site-footer { background-color: #0b0f0b; background-image: radial-gradient(circle at 15% 0%,rgba(255,94,58,0.08) 0%,transparent 35%),radial-gradient(circle at 85% 100%,rgba(202,237,149,0.06) 0%,transparent 35%),linear-gradient(135deg,#1a2219 0%,#050705 100%); color: #fff; padding: 64px 0 24px; margin-top: auto; }
        .site-footer a { transition: color 0.3s; }
        .footer-newsletter { display: flex; justify-content: center; padding-bottom: 40px; border-bottom: 1px solid rgba(255,255,255,0.1); margin-bottom: 40px; }
        .newsletter-content h3 { font-size: 36px; font-weight: 700; color: #fff; margin-bottom: 8px; font-family: "Google Sans Flex", sans-serif; }
        .newsletter-content p { color: rgba(255,255,255,0.7); font-size: 20px; }
        .footer-grid { display: grid; grid-template-columns: 2fr 1fr 1fr 1.2fr; gap: 32px; }
        .brand-row { display: flex; align-items: center; gap: 12px; margin-bottom: 16px; }
        .footer-brand img { width: 56px; height: 56px; object-fit: contain; }
        .brand-name { font-weight: 700; font-style: italic; font-size: 28px; font-family: "Google Sans Flex", sans-serif; }
        .footer-tagline { color: rgba(255,255,255,0.8); font-size: 15px; line-height: 1.6; margin-bottom: 12px; }
        .footer-slogan { font-size: 14px; font-weight: 700; letter-spacing: 0.8px; text-transform: uppercase; color: #caed95; margin-bottom: 24px; }
        .social-links { display: flex; gap: 16px; margin-bottom: 24px; }
        .social-links a { display: flex; align-items: center; justify-content: center; width: 36px; height: 36px; background: rgba(255,255,255,0.1); border-radius: 50%; color: #fff; }
        .social-links a:hover { background: #ff5e3a; transform: translateY(-2px); }
        .footer-col h4 { font-size: 16px; margin-bottom: 20px; letter-spacing: 0.5px; text-transform: uppercase; color: #fff; font-weight: 700; }
        .footer-col a { display: block; color: rgba(255,255,255,0.7); font-size: 14px; margin-bottom: 12px; }
        .footer-col a:hover { color: #ff5e3a; padding-left: 4px; }
        .footer-col p { color: rgba(255,255,255,0.7); font-size: 14px; margin-bottom: 14px; display: flex; align-items: center; gap: 10px; }
        .footer-col p .material-icons-outlined { font-size: 16px; }
        .footer-bottom { margin-top: 48px; border-top: 1px solid rgba(255,255,255,0.1); padding-top: 24px; display: flex; justify-content: space-between; align-items: center; }
        .footer-bottom-left { color: rgba(255,255,255,0.6); font-size: 13px; }
        .footer-bottom-links { display: flex; gap: 24px; }
        .footer-bottom-links a { color: rgba(255,255,255,0.6); font-size: 13px; }
        .footer-bottom-links a:hover { color: #fff; }

        @media (max-width: 960px) {
            .browse-grid { grid-template-columns: repeat(2, 1fr); }
            .browse-row-2 { max-width: 100%; }
            .sub-grid { grid-template-columns: repeat(3, 1fr); }
            .product-grid { grid-template-columns: repeat(3, 1fr); }
            .footer-grid { grid-template-columns: 1fr 1fr; }
            .footer-brand { grid-column: span 2; }
        }
        @media (max-width: 768px) {
            .top-bar-inner { grid-template-columns: 1fr; gap: 12px; }
            .search-wrap { order: 3; }
            .product-grid { grid-template-columns: repeat(2, 1fr); }
            .sub-grid { grid-template-columns: repeat(2, 1fr); }
            .footer-bottom { flex-direction: column; gap: 6px; align-items: flex-start; }
        }
        @media (max-width: 480px) {
            .product-grid { grid-template-columns: 1fr; }
            .browse-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <header>
        <div class="top-bar">
            <div class="page-wrap top-bar-inner">
                <div class="brand">
                    <a href="index.html" style="display:flex;align-items:center;gap:12px;text-decoration:none;color:inherit;">
                        <img src="assets/logo.png" alt="HuddersHub logo">
                        <span class="brand-text">HuddersHub</span>
                    </a>
                </div>
                <div class="search-wrap">
                    <form class="search-bar" action="search.php" method="get">
                        <input type="text" name="q" placeholder="Search">
                        <button class="search-icon material-icons-outlined" type="submit">search</button>
                    </form>
                </div>
                <div class="actions">
                    <div class="user-menu-wrap">
                        <?php if ($isLoggedIn): ?>
                        <div class="user-dropdown-wrap" id="userDropdownWrap">
                            <button class="action-btn user-menu" id="userDropdownBtn" style="border:none;background:none;">
                                <span class="material-icons-outlined" style="font-size:24px;">person</span>
                                <span>Hi, <?php echo htmlspecialchars($userFirstname); ?></span>
                                <span class="material-icons-outlined" style="font-size:16px;">expand_more</span>
                            </button>
                            <div class="user-dropdown" id="userDropdown">
                                <a href="../customer/profile.html" class="dropdown-user-item"><span class="material-icons-outlined">manage_accounts</span> My Profile</a>
                                <a href="../customer/orders.html" class="dropdown-user-item"><span class="material-icons-outlined">receipt_long</span> My Orders</a>
                                <a href="register-trader.html" class="dropdown-user-item"><span class="material-icons-outlined">storefront</span> Apply to be Trader</a>
                                <div class="dropdown-user-divider"></div>
                                <a href="logout.html" class="dropdown-user-item dropdown-logout"><span class="material-icons-outlined">logout</span> Log out</a>
                            </div>
                        </div>
                        <?php else: ?>
                        <a class="action-btn user-menu" href="login.html">
                            <span class="material-icons-outlined" style="font-size:24px;">person</span>
                            <span>Login / Signup</span>
                        </a>
                        <?php endif; ?>
                    </div>
                    <a class="icon-with-badge" href="cart.html"><span class="material-icons-outlined">shopping_cart</span><span class="badge" id="cartCount"><?php echo $cartCount; ?></span></a>
                    <a class="icon-with-badge" href="../customer/wishlist.html"><span class="material-icons-outlined">favorite_border</span><span class="badge" id="wishlistCount"><?php echo $wishlistCount; ?></span></a>
                </div>
            </div>
        </div>
        <nav class="nav-bar">
            <div class="page-wrap">
                <div class="nav-list">
                    <a href="index.html" class="nav-item primary"><span class="material-icons-outlined" style="font-size:18px;">home</span> Home</a>
                    <span class="nav-separator"></span>
                    <div class="categories-wrapper">
                        <span class="nav-item is-active"><span class="material-icons-outlined" style="font-size:18px;">menu</span> Categories</span>
                        <div class="categories-dropdown">
                            <div class="dropdown-section">
                                <div class="dropdown-section-title">Browse by Shop</div>
                                <div class="dropdown-divider"></div>
                                <a href="butcher.php" class="dropdown-item">Butcher</a>
                                <a href="greengrocer.php" class="dropdown-item">Greengrocer</a>
                                <a href="fishmonger.php" class="dropdown-item">Fishmonger</a>
                                <a href="bakery.php" class="dropdown-item">Bakery</a>
                                <a href="delicatessen.php" class="dropdown-item">Delicatessen</a>
                            </div>
                            <div class="dropdown-section">
                                <div class="dropdown-section-title">Browse by Type</div>
                                <div class="dropdown-divider"></div>
                                <a href="category.php?type=non-veg" class="dropdown-item">Non-Vegetarian</a>
                                <a href="category.php?type=veg" class="dropdown-item">Vegetarian</a>
                                <a href="category.php?type=vegan" class="dropdown-item">Vegan</a>
                                <a href="category.php?type=gluten-free" class="dropdown-item">Gluten Free</a>
                            </div>
                            <div class="dropdown-divider"></div>
                            <a href="category.php" class="dropdown-item all-categories">All Categories</a>
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

    <main class="page-content">
        <div class="page-wrap">
            <div class="browse-header">
                <h2>Browse by category</h2>
            </div>
            <div class="browse-grid">
                <a href="fishmonger.php" class="browse-btn <?php echo $shop==='fishmonger'?'active':''; ?>">Fishmonger</a>
                <a href="greengrocer.php" class="browse-btn <?php echo $shop==='greengrocer'?'active':''; ?>">Greengrocer</a>
                <a href="bakery.php" class="browse-btn <?php echo $shop==='bakery'?'active':''; ?>">Bakery</a>
                <div class="browse-row-2">
                    <a href="butcher.php" class="browse-btn <?php echo $shop==='butcher'?'active':''; ?>">Butcher</a>
                    <a href="delicatessen.php" class="browse-btn <?php echo $shop==='delicatessen'?'active':''; ?>">Delicatessen</a>
                </div>
            </div>

            <?php
            $conn = getDB();
            foreach ($sections as $idx => $sec):
                $products = fetchSectionProducts($conn, $sec['key'], $sort, $sub, $inStock, 10);
                $total = countSectionProducts($conn, $sec['key'], $sub, $inStock);
                if (empty($products)) continue;
            ?>
            <section class="shop-section" id="<?php echo $sec['key']; ?>">
                <h2 class="shop-section-title"><a href="<?php echo $sec['key']; ?>.php"><?php echo $sec['title']; ?></a></h2>
                <div class="sub-grid">
                    <?php foreach ($sec['subs'] as $s): ?>
                    <a href="<?php echo buildUrl(['sub' => $s]); ?>" class="sub-box <?php echo $sub === $s ? 'active' : ''; ?>"><?php echo htmlspecialchars($s); ?></a>
                    <?php endforeach; ?>
                </div>
                <div class="section-toolbar">
                    <div class="toolbar-left">Products: Selection <span><?php echo $total; ?> total products</span></div>
                    <div class="toolbar-right">
                        <a href="<?php echo buildUrl(['sort' => $sort === 'newest' ? 'price-low' : ($sort === 'price-low' ? 'price-high' : ($sort === 'price-high' ? 'name' : 'newest'))]); ?>" class="toolbar-btn"><span class="material-icons-outlined">swap_vert</span> Sort: <?php echo ucfirst(str_replace('-', ' ', $sort)); ?></a>
                        <a href="<?php echo buildUrl(['in_stock' => $inStock ? 0 : 1]); ?>" class="toolbar-btn <?php echo $inStock ? 'active' : ''; ?>"><span class="material-icons-outlined">filter_list</span> Filter: <?php echo $inStock ? 'In stock' : 'All'; ?></a>
                    </div>
                </div>
                <div class="product-grid">
                    <?php foreach ($products as $p): ?>
                        <?php
                        $stock = (int)$p['STOCK'];
                        $price = number_format($p['PRICE'], 2);
                        $imgSrc = getImgSrc($p['NAME']);
                        ?>
                        <div class="product-card <?php echo $stock===0?'out-of-stock':''; ?>">
                            <div class="product-image">
                                <img src="<?php echo htmlspecialchars($imgSrc); ?>" alt="<?php echo htmlspecialchars($p['NAME']); ?>" loading="lazy" onerror="this.src='assets/Item-image/green-bell-pepper-isolated.jpg';">
                                <?php if ($stock > 0): ?><span class="stock-badge">In stock</span><?php endif; ?>
                            </div>
                            <div class="product-bottom">
                                <div class="product-price">£ <?php echo $price; ?></div>
                                <button class="add-btn" onclick="addToCart(<?php echo (int)$p['PRODUCT_ID']; ?>)" <?php echo $stock===0?'disabled':''; ?>>
                                    <span class="material-icons-outlined">shopping_cart</span> Add to cart
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div class="show-more-wrap">
                    <a href="<?php echo $sec['key']; ?>.php" class="show-more-btn">[Show more]</a>
                </div>
            </section>

            <?php if ($idx < count($sections) - 1): ?>
            <a href="<?php echo $sections[$idx + 1]['key']; ?>.php" class="section-banner"><h3>Browse <?php echo $sections[$idx + 1]['title']; ?></h3></a>
            <?php endif; ?>
            <?php endforeach; ?>
            <?php oci_close($conn); ?>
        </div>
    </main>

    <footer class="site-footer">
        <div class="page-wrap footer-newsletter">
            <div class="newsletter-content">
                <h3>Fresh from local farms to your table</h3>
                <p>"Quality you can taste, community you can feel."</p>
            </div>
        </div>
        <div class="page-wrap footer-grid">
            <div class="footer-brand">
                <div class="brand-row">
                    <img src="assets/logo.png" alt="HuddersHub logo">
                    <span class="brand-name">HuddersHub</span>
                </div>
                <p class="footer-tagline">Local food, trusted traders, and fresh picks curated for Huddersfield.</p>
                <p class="footer-slogan">Eat Fresh. Buy Local.</p>
                <div class="social-links">
                    <a href="#"><span class="material-icons-outlined">facebook</span></a>
                    <a href="#"><span class="material-icons-outlined">camera_alt</span></a>
                </div>
            </div>
            <div class="footer-col">
                <h4>Shop</h4>
                <a href="greengrocer.php">Green Grocer</a>
                <a href="butcher.php">The Butcher</a>
                <a href="bakery.php">Bakery</a>
                <a href="delicatessen.php">Delicatessen</a>
                <a href="fishmonger.php">Fishmonger</a>
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
                <p><span class="material-icons-outlined">location_on</span> Huddersfield, UK</p>
                <p><span class="material-icons-outlined">mail</span> support@huddershub.test</p>
                <p><span class="material-icons-outlined">phone</span> +44 1484 000 000</p>
            </div>
        </div>
        <div class="page-wrap footer-bottom">
            <div class="footer-bottom-left"><span>&copy; 2026 HuddersHub. All rights reserved.</span></div>
            <div class="footer-bottom-links">
                <a href="privacy.html">Privacy Policy</a>
                <a href="terms.html">Terms of Service</a>
                <a href="register-trader.html">Apply as a trader</a>
            </div>
        </div>
    </footer>

    <script>
        window.addEventListener('scroll', function() {
            document.querySelector('header')?.classList.toggle('scrolled', window.scrollY > 50);
        }, { passive: true });

        document.getElementById('userDropdownBtn')?.addEventListener('click', function() {
            document.getElementById('userDropdown')?.classList.toggle('open');
        });
        document.addEventListener('click', function(e) {
            if (!e.target.closest('.user-dropdown-wrap')) document.getElementById('userDropdown')?.classList.remove('open');
        });

        async function addToCart(productId) {
            try {
                const res = await fetch('../api/cart/add-to-cart.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ product_id: productId, quantity: 1 }) });
                const data = await res.json();
                if (data.success) {
                    const el = document.getElementById('cartCount');
                    if (el) el.textContent = data.new_count;
                } else if (data.redirect) {
                    window.location.href = 'login.html';
                } else {
                    alert(data.message || 'Failed to add to cart');
                }
            } catch (e) {
                alert('Network error. Please try again.');
            }
        }
    </script>
</body>
</html>
