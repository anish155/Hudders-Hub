<?php
require_once '../config/database.php';
require_once '../config/session.php';

$userId = getUserId();
$isLoggedIn = isLoggedIn();
$cartCount = 0;

$q = isset($_GET['q']) ? trim($_GET['q']) : '';
$shopFilter = isset($_GET['shop']) ? strtoupper(trim($_GET['shop'])) : '';
$typeFilter = isset($_GET['type']) ? strtoupper(trim($_GET['type'])) : '';
$priceFilter = isset($_GET['price']) ? trim($_GET['price']) : '';
$ratingFilter = isset($_GET['rating']) ? (float)$_GET['rating'] : 0;
$inStockFilter = isset($_GET['in_stock']) && $_GET['in_stock'] === '1';
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'name';

$products = [];

if ($userId) {
    $cartStmt = oci_parse($conn, "SELECT SUM(ci.quantity) AS total_qty FROM CART c JOIN CART_ITEM ci ON c.cart_id = ci.cart_id WHERE c.user_id = :user_id");
    oci_bind_by_name($cartStmt, ':user_id', $userId);
    oci_execute($cartStmt);
    $cartRow = oci_fetch_assoc($cartStmt);
    $cartCount = (int)($cartRow['TOTAL_QTY'] ?? 0);
    oci_free_statement($cartStmt);
}

$sql = "SELECT p.product_id, p.name, p.description, p.price, p.stock, p.unit,
               s.shop_id, s.name AS shop_name, s.shop_type,
               pi.image_url,
               NVL(ROUND(AVG(r.rating), 1), 0) AS avg_rating,
               COUNT(r.review_id) AS review_count
        FROM PRODUCT p
        JOIN SHOP s ON p.shop_id = s.shop_id
        LEFT JOIN PRODUCT_IMAGE pi ON pi.product_id = p.product_id AND pi.display_order = 0
        LEFT JOIN REVIEW r ON r.product_id = p.product_id
        WHERE p.status = 'Active'";

$params = [];
if ($q !== '') {
    $search = '%' . strtoupper($q) . '%';
    $sql .= " AND (UPPER(p.name) LIKE :kw OR UPPER(p.description) LIKE :kw)";
    $params[':kw'] = $search;
}

if ($shopFilter) {
    $sql .= " AND UPPER(s.shop_type) = :shop";
    $params[':shop'] = $shopFilter;
}

if ($typeFilter) {
    if ($typeFilter === 'VEG') {
        $sql .= " AND INSTR(UPPER(p.dietary_tags), 'VEGETARIAN') > 0";
    } elseif ($typeFilter === 'NON-VEG') {
        $sql .= " AND (p.dietary_tags IS NULL OR (INSTR(UPPER(p.dietary_tags), 'VEGETARIAN') = 0 AND INSTR(UPPER(p.dietary_tags), 'VEGAN') = 0))";
    } elseif ($typeFilter === 'VEGAN') {
        $sql .= " AND INSTR(UPPER(p.dietary_tags), 'VEGAN') > 0";
    } elseif ($typeFilter === 'GLUTEN-FREE') {
        $sql .= " AND INSTR(UPPER(p.dietary_tags), 'GLUTEN FREE') > 0";
    }
}

if ($inStockFilter) {
    $sql .= " AND p.stock > 0";
}

if ($priceFilter) {
    if ($priceFilter === '0-5')   $sql .= " AND p.price <= 5";
    elseif ($priceFilter === '5-10')  $sql .= " AND p.price > 5 AND p.price <= 10";
    elseif ($priceFilter === '10-20') $sql .= " AND p.price > 10 AND p.price <= 20";
    elseif ($priceFilter === '20+')   $sql .= " AND p.price > 20";
}

$sql .= " GROUP BY p.product_id, p.name, p.description, p.price, p.stock, p.unit, s.shop_id, s.name, s.shop_type, pi.image_url";

if ($ratingFilter > 0) {
    $sql .= " HAVING AVG(r.rating) >= :rating";
    $params[':rating'] = $ratingFilter;
}

$orderSql = " ORDER BY p.name";
if ($sort === 'price-low')  $orderSql = " ORDER BY p.price ASC";
elseif ($sort === 'price-high') $orderSql = " ORDER BY p.price DESC";
elseif ($sort === 'rating')     $orderSql = " ORDER BY avg_rating DESC";

$sql .= $orderSql;

$stmt = oci_parse($conn, $sql);
foreach ($params as $k => $v) {
    oci_bind_by_name($stmt, $k, $params[$k]);
}
oci_execute($stmt);
while ($row = oci_fetch_assoc($stmt)) {
    $products[] = $row;
}
oci_free_statement($stmt);

$totalCount = count($products);
$showingFrom = $totalCount > 0 ? 1 : 0;
$showingTo = $totalCount;

function buildSearchUrl($overrides = []) {
    $params = $_GET;
    foreach ($overrides as $k => $v) {
        if ($v === null) unset($params[$k]);
        else $params[$k] = $v;
    }
    return 'search.php?' . http_build_query($params);
}

function renderStars($rating) {
    $fullStars = floor($rating);
    $halfStar = ($rating - $fullStars) >= 0.5;
    $html = '';
    for ($i = 1; $i <= 5; $i++) {
        if ($i <= $fullStars) $html .= '<span class="material-icons-outlined">star</span>';
        elseif ($i == $fullStars + 1 && $halfStar) $html .= '<span class="material-icons-outlined">star_half</span>';
        else $html .= '<span class="material-icons-outlined">star_outline</span>';
    }
    return $html;
}

oci_close($conn);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Search results | HuddersHub</title>
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
            --bg-white: #ffffff;
            --bg-light: #f7f6f3;
            --bg-gray: #f2f4f1;
            --border-light: #dce3da;
            --bg-gradient: linear-gradient(135deg, #f8faf7 0%, #ffffff 100%);
            --text-black: #0b140a;
            --text-dark-gray: #1e2a1c;
            --text-medium-gray: #5e6a63;
            --badge-bg: #0f260b;
            --badge-text: #ffffff;
            --shadow-sm: 0 2px 6px rgba(15, 38, 11, 0.08);
            --shadow-md: 0 10px 24px rgba(15, 38, 11, 0.12);
            --shadow-lg: 0 18px 36px rgba(15, 38, 11, 0.16);
            --shadow-xl: 0 25px 50px rgba(15, 38, 11, 0.2);
            --transition-smooth: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
            --radius-sm: 10px;
            --radius-md: 16px;
            --radius-lg: 20px;
            --radius-xl: 24px;
            --radius-pill: 999px;
        }
        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: "Plus Jakarta Sans", sans-serif;
            font-size: 1rem;
            line-height: 1.6;
            color: var(--text-black);
            background: linear-gradient(180deg, #f7f6f3 0%, #ffffff 35%, #f7f6f3 100%);
            padding-top: 140px;
            min-height: 100vh;
        }
        html { scroll-behavior: smooth; }

        /* HEADER STYLES (MATCH HOMEPAGE) */
        header {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1000;
            background: var(--bg-gradient);
            backdrop-filter: blur(12px);
            transition: var(--transition-smooth);
        }
        header.scrolled {
            box-shadow: var(--shadow-md);
            background: var(--bg-white);
        }
        .top-bar {
            background: rgba(255, 255, 255, 0.98);
            border-bottom: 1px solid var(--border-light);
            padding: 14px 0;
            transition: var(--transition-smooth);
        }
        header.scrolled .top-bar { padding: 10px 0; }
        header.scrolled .brand img {
            width: 42px;
            height: 42px;
        }
        header.scrolled .brand-text { font-size: 30px; }
        .page-wrap { width: min(1200px, 94%); margin: 0 auto; }
        .top-bar-inner {
            display: grid;
            grid-template-columns: auto 1fr auto;
            gap: 18px;
            align-items: center;
        }
        .brand {
            display: flex;
            align-items: center;
            gap: 14px;
            white-space: nowrap;
        }
        .brand img {
            width: 56px;
            height: 56px;
            object-fit: contain;
            transition: var(--transition-smooth);
            filter: drop-shadow(0 6px 12px rgba(15, 38, 11, 0.12));
        }
        .brand .brand-text {
            font-family: "Google Sans Flex", sans-serif;
            font-weight: 700;
            font-style: italic;
            font-size: 36px;
            letter-spacing: 0.6px;
            color: #0f260b;
        }
        .search-wrap {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .search-bar {
            position: relative;
            flex: 1;
            min-width: 280px;
        }
        .search-bar input {
            width: 100%;
            padding: 6px 44px 6px 14px;
            height: 36px;
            border: 1px solid #c8d1c6;
            background: var(--bg-white);
            font-size: 14px;
            font-weight: 500;
            color: var(--text-black);
            outline: none;
            transition: var(--transition-smooth);
        }
        .search-bar input:focus {
            border-color: var(--primary-orange);
            box-shadow: 0 0 0 3px rgba(255, 94, 58, 0.22);
        }
        .search-bar input::placeholder { color: #1b2419; opacity: 0.55; }
        .search-bar .search-icon {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            font-size: 18px;
            color: #1b2419;
            opacity: 0.55;
            border: none;
            background: transparent;
            padding: 0;
            cursor: pointer;
        }
        .actions {
            display: flex;
            align-items: center;
            gap: 16px;
            white-space: nowrap;
        }
        .action-btn,
        .user-menu {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 14px;
            font-weight: 600;
            color: var(--text-medium-gray);
            text-decoration: none;
            transition: var(--transition-smooth);
            cursor: pointer;
            padding: 8px 12px;
        }
        .action-btn:hover,
        .user-menu:hover {
            background: rgba(15, 38, 11, 0.06);
            color: var(--primary-green);
        }
        .icon-with-badge {
            position: relative;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: var(--transition-smooth);
            text-decoration: none;
            color: var(--text-black);
            padding: 6px;
            background: transparent;
            border: 1px solid transparent;
            outline: none;
        }
        .icon-with-badge:hover {
            background: rgba(15, 38, 11, 0.06);
            color: var(--primary-green);
        }
        .icon-with-badge .material-icons-outlined {
            font-size: 24px;
            color: inherit;
        }
        .badge {
            position: absolute;
            top: 0;
            right: 0;
            background: var(--badge-bg);
            color: var(--badge-text);
            padding: 2px 5px;
            font-size: 10px;
            font-weight: 600;
            line-height: 1;
            min-width: 16px;
            text-align: center;
        }

        /* USER DROPDOWN */
        .user-menu-wrap { position: relative; }
        .user-dropdown-wrap { position: relative; }
        .user-dropdown {
            position: absolute;
            top: calc(100% + 8px);
            right: 0;
            background: #fff;
            border: 1px solid var(--border-light);
            box-shadow: var(--shadow-lg);
            min-width: 180px;
            z-index: 2000;
            display: none;
            flex-direction: column;
            padding: 6px 0;
        }
        .user-dropdown.open { display: flex; }
        .dropdown-user-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 16px;
            font-size: 14px;
            font-weight: 500;
            color: var(--text-dark-gray);
            text-decoration: none;
            background: none;
            border: none;
            width: 100%;
            cursor: pointer;
            font-family: "Plus Jakarta Sans", sans-serif;
            transition: var(--transition-smooth);
        }
        .dropdown-user-item:hover {
            background: var(--primary-green-light);
            color: var(--primary-green);
        }
        .dropdown-user-item .material-icons-outlined { font-size: 18px; }
        .dropdown-user-divider {
            height: 1px;
            background: var(--border-light);
            margin: 4px 0;
        }
        .dropdown-logout { color: #dc2626; }
        .dropdown-logout:hover {
            background: #fef2f2;
            color: #dc2626;
        }
        .nav-bar {
            background: #f1f3f0;
            border-bottom: 1px solid var(--border-light);
            padding: 10px 0;
            transition: var(--transition-smooth);
        }
        header.scrolled .nav-bar {
            padding: 8px 0;
            background: #f3f4f6;
        }
        .nav-list {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
            font-size: 14px;
        }
        .nav-item {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 14px;
            border: none;
            cursor: pointer;
            text-decoration: none;
            color: var(--text-black);
            transition: var(--transition-smooth);
            font-size: 15px;
            font-weight: 400;
            font-family: "Google Sans Flex", sans-serif;
        }
        .nav-item:not(.primary):hover {
            background: rgba(15, 38, 11, 0.08);
            color: var(--text-black);
        }
        .nav-item.primary {
            background: transparent;
            color: #0f260b;
            font-weight: 600;
            font-size: 14px;
        }
        .nav-item.is-active {
            position: relative;
        }
        .nav-item.is-active::after {
            content: "";
            position: absolute;
            left: 0;
            right: 0;
            bottom: -6px;
            height: 2px;
            background: #0f260b;
        }
        .nav-separator {
            width: 1px;
            height: 24px;
            background: var(--border-light);
            margin: 0 6px;
            display: inline-block;
        }
        .categories-wrapper { position: relative; }
        .categories-dropdown {
            position: absolute;
            top: 100%;
            left: 0;
            background: var(--bg-white);
            border: 1px solid var(--border-light);
            box-shadow: var(--shadow-lg);
            padding: 0;
            min-width: 240px;
            display: none;
            z-index: 1000;
            transform: translateY(-8px);
            opacity: 0;
            transition: opacity 0.2s ease, transform 0.2s ease;
        }
        .categories-wrapper:hover .categories-dropdown,
        .categories-dropdown:hover {
            display: block;
            transform: translateY(0);
            opacity: 1;
        }
        .categories-dropdown::before {
            content: "";
            position: absolute;
            top: -15px;
            left: 0;
            right: 0;
            height: 15px;
        }
        .dropdown-section { padding: 12px 0; }
        .dropdown-section-title {
            padding: 8px 16px;
            font-size: 11px;
            font-weight: 700;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: 0.8px;
        }
        .dropdown-divider { height: 1px; background: var(--border-light); margin: 8px 16px; }
        .dropdown-item {
            display: block;
            padding: 10px 16px;
            color: var(--text-black);
            text-decoration: none;
            font-size: 14px;
            transition: var(--transition-smooth);
            border-left: 3px solid transparent;
        }
        .dropdown-item:hover {
            background: rgba(15, 38, 11, 0.08);
            border-left-color: var(--primary-green);
        }
        .dropdown-item.all-categories {
            font-weight: 600;
            background: linear-gradient(135deg, #f9fafb 0%, #ffffff 100%);
            padding: 12px 16px;
        }
        .dropdown-item.all-categories:hover {
            background: var(--primary-orange);
            color: var(--bg-white);
            border-left-color: var(--primary-orange);
        }

        /* FILTERS */
        .filters-section {
            background: #fff;
            padding: 20px 0;
            border-bottom: 1px solid var(--border-light);
            position: sticky;
            top: 92px;
            z-index: 50;
        }
        .filters-row {
            display: flex;
            align-items: center;
            gap: 12px;
            flex-wrap: wrap;
        }
        .filter-label {
            font-size: 14px;
            font-weight: 600;
            color: var(--text-black);
            margin-right: 4px;
        }
        .filter-chip {
            padding: 8px 16px;
            border-radius: 24px;
            border: 1px solid var(--border-light);
            background: #fff;
            font-size: 13px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s ease;
            font-family: inherit;
            color: var(--text-dark-gray);
            text-decoration: none;
        }
        .filter-chip:hover {
            border-color: var(--primary-green);
            background: var(--primary-green-light);
        }
        .filter-chip.active {
            background: var(--primary-green);
            color: #fff;
            border-color: var(--primary-green);
        }
        .filter-select {
            padding: 8px 16px;
            border-radius: 8px;
            border: 1px solid var(--border-light);
            font-size: 13px;
            font-family: inherit;
            color: var(--text-dark-gray);
            background: #fff;
            cursor: pointer;
            outline: none;
            margin-left: auto;
        }
        .filter-select:focus { border-color: var(--primary-green); }
        .results-count {
            font-size: 13px;
            color: var(--text-medium-gray);
            font-weight: 500;
        }

        /* CONTAINER */
        .search-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 32px 24px 48px;
            display: grid;
            grid-template-columns: 280px 1fr;
            gap: 32px;
        }

        /* SIDEBAR FILTERS */
        .search-sidebar {
            display: flex;
            flex-direction: column;
            gap: 32px;
        }
        .filter-group {
            border-bottom: 1px solid var(--border-light);
            padding-bottom: 24px;
        }
        .filter-group:last-child {
            border-bottom: none;
        }
        .filter-title {
            font-size: 16px;
            font-weight: 700;
            color: var(--text-black);
            margin-bottom: 16px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .filter-options {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        .filter-option {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 14px;
            color: var(--text-dark-gray);
            cursor: pointer;
            transition: var(--transition-smooth);
        }
        .filter-option:hover {
            color: var(--primary-orange);
        }
        .filter-option input[type="checkbox"],
        .filter-option input[type="radio"] {
            width: 18px;
            height: 18px;
            cursor: pointer;
            accent-color: var(--primary-orange);
        }
        .rating-stars-filter {
            display: flex;
            align-items: center;
            gap: 4px;
            color: #f4b740;
        }
        .rating-stars-filter .material-icons-outlined {
            font-size: 18px;
        }

        /* PRODUCT GRID */
        .product-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 20px;
            align-items: stretch;
        }
        @keyframes fadeUp {
            from { opacity: 0; transform: translateY(16px); }
            to { opacity: 1; transform: translateY(0); }
        }
        @media (max-width: 1100px) {
            .search-container {
                grid-template-columns: 240px 1fr;
                gap: 20px;
            }
            .product-grid { grid-template-columns: repeat(2, 1fr); }
        }
        @media (max-width: 850px) {
            .search-container {
                grid-template-columns: 1fr;
            }
            .search-sidebar {
                display: none; /* Can implement a toggle drawer later */
            }
            .product-grid { grid-template-columns: repeat(2, 1fr); }
        }
        @media (max-width: 500px) {
            .product-grid { grid-template-columns: 1fr; }
        }

        /* PRODUCT CARD - MATCHING INDEX PAGE */
        .product-card,
        .carousel-card {
            background: #ffffff;
            border: 1px solid #e8ede7;
            border-radius: 0;
            overflow: hidden;
            transition:
                transform 0.22s ease,
                box-shadow 0.22s ease,
                border-color 0.22s ease;
            display: flex;
            flex-direction: column;
            cursor: pointer;
            box-shadow: 0 2px 8px rgba(15, 38, 11, 0.07);
        }
        .product-card:hover,
        .carousel-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 28px rgba(15, 38, 11, 0.14);
            border-color: rgba(255, 94, 58, 0.3);
        }
        .product-card.is-out-of-stock,
        .carousel-card.is-out-of-stock {
            opacity: 0.65;
        }

        .product-card-inner,
        .carousel-card-inner {
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
        .product-image-wrapper img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
            transition: transform 0.35s ease;
        }
        .product-card:hover .product-image-wrapper img,
        .carousel-card:hover .product-image-wrapper img {
            transform: scale(1.06);
        }

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
            transition:
                background 0.2s,
                transform 0.2s;
        }
        .favorite-btn:hover {
            background: #fff;
            transform: scale(1.12);
        }
        .favorite-btn.active .material-icons-outlined {
            color: #ff5e3a;
        }
        .favorite-btn .material-icons-outlined {
            font-size: 18px;
            color: #666;
        }

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
        .stock-badge.sale {
            background: #ff5e3a;
            color: #fff;
        }
        .stock-badge.new {
            background: #0f260b;
            color: #caed95;
        }
        .stock-badge.out {
            background: #9ca3af;
            color: #fff;
        }
        .stock-badge.discount {
            background: #ff5e3a;
            color: #fff;
        }

        .product-image {
            width: 100%;
            height: 100%;
            overflow: hidden;
        }

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
        .product-shop {
            font-size: 11px;
            color: #8a9b88;
            font-weight: 500;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .shop-link { color: inherit; border-bottom: 1px solid transparent; transition: var(--transition-smooth); }
        .shop-link:hover { color: var(--primary-orange); border-bottom-color: var(--primary-orange); }
        .price-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 8px;
            flex-wrap: wrap;
        }
        .price-group {
            display: flex;
            align-items: baseline;
            gap: 6px;
        }
        .current-price {
            font-size: 18px;
            font-weight: 800;
            color: #ff5e3a;
            letter-spacing: -0.02em;
        }
        .original-price {
            font-size: 12px;
            color: #9ca3af;
            text-decoration: line-through;
            font-weight: 400;
        }
        .discount-text {
            font-size: 11px;
            font-weight: 700;
            color: #ff5e3a;
            background: #fff0ec;
            padding: 2px 7px;
            border-radius: 0;
            white-space: nowrap;
        }
        .unit { font-size: 12px; color: #6b7280; }
        .rating-row {
            display: flex;
            align-items: center;
            gap: 4px;
            margin-bottom: 4px;
        }
        .rating-stars {
            display: inline-flex;
            align-items: center;
            gap: 2px;
        }
        .rating-stars .material-icons-outlined {
            font-size: 12px;
            color: #f4b740;
        }
        .rating-count {
            font-size: 11px;
            color: #9ca3af;
        }
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
            transition:
                background 0.22s,
                transform 0.18s;
            letter-spacing: 0.3px;
            margin-top: auto;
        }
        .add-to-cart-btn:hover:not([disabled]) {
            background: #1c3c17;
            transform: translateY(-1px);
        }
        .add-to-cart-btn:disabled { background: #ccc; cursor: not-allowed; transform: none; }
        .add-to-cart-btn .material-icons-outlined { font-size: 16px; }

        /* EMPTY STATE */
        .empty-state {
            background: var(--bg-white);
            border: 1px solid var(--border-light);
            border-radius: var(--radius-xl);
            padding: 64px 40px;
            text-align: center;
            box-shadow: var(--shadow-sm);
            animation: fadeUp 0.5s ease-out;
        }
        .empty-state .icon-wrap {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: linear-gradient(135deg, rgba(255, 94, 58, 0.1), rgba(255, 94, 58, 0.05));
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 24px;
        }
        .empty-state .icon-wrap .material-icons-outlined {
            font-size: 40px;
            color: var(--primary-orange);
        }
        .empty-state h2 {
            font-family: "Google Sans Flex", sans-serif;
            font-weight: 700;
            font-size: 24px;
            color: var(--text-black);
            margin-bottom: 12px;
        }
        .empty-state p {
            font-size: 15px;
            color: var(--text-medium-gray);
            margin-bottom: 8px;
            line-height: 1.7;
        }
        .empty-state a {
            color: var(--primary-orange);
            font-weight: 700;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 10px 22px;
            border-radius: var(--radius-pill);
            background: rgba(255, 94, 58, 0.1);
            margin-top: 12px;
            transition: all 0.2s;
        }
        .empty-state a:hover {
            background: var(--primary-orange);
            color: #fff;
            box-shadow: 0 4px 12px rgba(255, 94, 58, 0.3);
        }

        /* POPULAR SEARCHES */
        .popular-section {
            padding: 48px 0;
            background: #fff;
            margin-top: 48px;
            border-top: 1px solid var(--border-light);
        }
        .popular-title {
            font-family: "Google Sans Flex", sans-serif;
            font-weight: 700;
            font-size: 22px;
            color: var(--text-black);
            margin-bottom: 24px;
            text-align: center;
        }
        .popular-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
            gap: 16px;
        }
        .popular-item {
            background: var(--bg-light);
            padding: 20px;
            border-radius: 12px;
            text-align: center;
            text-decoration: none;
            color: var(--text-dark-gray);
            font-weight: 600;
            transition: all 0.2s ease;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 8px;
            border: 1px solid var(--border-light);
        }
        .popular-item:hover {
            background: var(--primary-green);
            color: #fff;
            transform: translateY(-4px);
            box-shadow: var(--shadow-md);
        }
        .popular-item span.material-icons-outlined {
            font-size: 28px;
        }

        /* FOOTER STYLES */
        .site-footer {
            background-color: #0b0f0b;
            background-image:
                radial-gradient(circle at 15% 0%, rgba(255, 94, 58, 0.08) 0%, transparent 35%),
                radial-gradient(circle at 85% 100%, rgba(202, 237, 149, 0.06) 0%, transparent 35%),
                linear-gradient(135deg, #1a2219 0%, #050705 100%);
            color: #ffffff;
            padding: 64px 0 24px;
            margin-top: 48px;
            position: relative;
            overflow: hidden;
        }
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

        /* Responsive */
        @media (max-width: 960px) {
            .footer-grid { grid-template-columns: 1fr 1fr; }
            .footer-brand { grid-column: span 2; margin-bottom: 24px; margin-right: 0; }
        }
        @media (max-width: 768px) {
            .product-grid { gap: 16px; }
            .footer-newsletter { flex-direction: column; align-items: flex-start; }
            .footer-bottom { flex-direction: column; gap: 16px; align-items: center; text-align: center; }
            .filters-row { gap: 8px; }
        }
        @media (max-width: 480px) {
            .footer-grid { grid-template-columns: 1fr; }
            .footer-brand { grid-column: span 1; }
            .footer-bottom-links { justify-content: center; gap: 12px; }
            .popular-grid { grid-template-columns: repeat(2, 1fr); }
        }

        /* Toast */
        .toast-wrap {
            position: fixed;
            bottom: 24px;
            right: 24px;
            z-index: 9999;
            display: flex;
            flex-direction: column;
            gap: 8px;
            pointer-events: none;
        }
        .toast {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 14px 18px;
            border-radius: 16px;
            font-size: 14px;
            font-weight: 600;
            color: #fff;
            box-shadow: 0 10px 28px rgba(15, 38, 11, 0.18);
            pointer-events: auto;
            animation: toastSlideIn 0.32s ease-out;
            max-width: 370px;
        }
        .toast.success { background: var(--primary-green); }
        .toast.info    { background: var(--primary-orange); }
        .toast.error   { background: #ef4444; }
        .toast .material-icons-outlined { font-size: 18px; flex-shrink: 0; }
        .toast.fading { animation: toastFadeOut 0.22s ease-in forwards; }
        @keyframes toastSlideIn {
            from { opacity: 0; transform: translateY(18px) scale(0.96); }
            to   { opacity: 1; transform: translateY(0) scale(1); }
        }
        @keyframes toastFadeOut {
            to { opacity: 0; transform: translateY(8px); }
        }
    </style>
</head>
<body>
    <header>
        <div class="top-bar">
            <div class="page-wrap top-bar-inner">
                <div class="brand">
                    <a href="index.html" style="display: flex; align-items: center; gap: 12px; text-decoration: none; color: inherit;">
                        <img src="assets/logo.png" alt="HuddersHub logo">
                        <span class="brand-text">HuddersHub</span>
                    </a>
                </div>
                <div class="search-wrap">
                    <form class="search-bar" action="search.php" method="get">
                        <input type="text" name="q" placeholder="Search" value="<?php echo htmlspecialchars($q); ?>">
                        <button class="search-icon material-icons-outlined" type="submit" aria-label="Search">search</button>
                    </form>
                </div>
                <div class="actions">
                    <div class="user-menu-wrap">
                        <?php if (!$isLoggedIn): ?>
                            <a class="action-btn user-menu" id="loginBtn" href="login.html">
                                <span class="material-icons-outlined" style="font-size: 24px">person</span>
                                <span>Login / Signup</span>
                            </a>
                        <?php else: ?>
                            <div class="user-dropdown-wrap" id="userDropdownWrap">
                                <button class="action-btn user-menu" id="userDropdownBtn" style="border: none; background: none">
                                    <span class="material-icons-outlined" style="font-size: 24px">person</span>
                                    <span>My Account</span>
                                    <span class="material-icons-outlined" style="font-size: 16px" id="dropChevron">expand_more</span>
                                </button>
                                <div class="user-dropdown" id="userDropdown">
                                    <a href="../customer/profile.html" class="dropdown-user-item">
                                        <span class="material-icons-outlined">manage_accounts</span>
                                        My Profile
                                    </a>
                                    <a href="../customer/orders.html" class="dropdown-user-item">
                                        <span class="material-icons-outlined">receipt_long</span>
                                        My Orders
                                    </a>
                                    <a href="register-trader.html" class="dropdown-user-item">
                                        <span class="material-icons-outlined">storefront</span>
                                        Apply to be Trader
                                    </a>
                                    <div class="dropdown-user-divider"></div>
                                    <a href="logout.html" class="dropdown-user-item dropdown-logout" id="logoutBtn">
                                        <span class="material-icons-outlined">logout</span>
                                        Log out
                                    </a>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                    <a class="icon-with-badge" href="cart.html" aria-label="Cart" id="cartTrigger">
                        <span class="material-icons-outlined">shopping_cart</span>
                        <span class="badge" id="cartCount"><?php echo (int)$cartCount; ?></span>
                    </a>
                    <a class="icon-with-badge" href="../customer/wishlist.html" aria-label="Wishlist">
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
                        <span class="material-icons-outlined" style="font-size: 18px;">home</span>
                        Home
                    </a>
                    <span class="nav-separator"></span>
                    <div class="categories-wrapper">
                        <span class="nav-item">
                            <span class="material-icons-outlined" style="font-size: 18px;">menu</span>
                            Categories
                        </span>
                        <div class="categories-dropdown">
                                <div class="dropdown-section">
                                    <div class="dropdown-section-title">Browse by Shop</div>
                                    <div class="dropdown-divider"></div>
                                    <a href="search.php?shop=BUTCHER" class="dropdown-item">Butcher</a>
                                    <a href="search.php?shop=GREENGROCER" class="dropdown-item">Greengrocer</a>
                                    <a href="search.php?shop=FISHMONGER" class="dropdown-item">Fishmonger</a>
                                    <a href="search.php?shop=BAKERY" class="dropdown-item">Bakery</a>
                                    <a href="search.php?shop=DELICATESSEN" class="dropdown-item">Delicatessen</a>
                                </div>
                                <div class="dropdown-section">
                                    <div class="dropdown-section-title">Browse by Type</div>
                                    <div class="dropdown-divider"></div>
                                    <a href="search.php?type=NON-VEG" class="dropdown-item">Non-Vegetarian</a>
                                    <a href="search.php?type=VEG" class="dropdown-item">Vegetarian</a>
                                    <a href="search.php?type=VEGAN" class="dropdown-item">Vegan</a>
                                    <a href="search.php?type=GLUTEN-FREE" class="dropdown-item">Gluten Free</a>
                                </div>
                                <div class="dropdown-divider"></div>
                                <a href="category.php" class="dropdown-item all-categories">All Categories</a>
                            </div>
                    </div>
                    <span class="nav-separator"></span>
                    <a href="about.html" class="nav-item">About</a>
                    <span class="nav-separator"></span>
                    <a href="traders.php" class="nav-item">Traders</a>
                    <span class="nav-separator"></span>
                    <a href="contact.html" class="nav-item">Contact</a>
                </div>
            </div>
        </nav>
    </header>

    <div class="page-wrap" style="padding: 24px 0 0;">
        <h1 style="font-size: 28px; font-weight: 700; color: var(--primary-green); font-family: 'Google Sans Flex', sans-serif;">
            <?php echo $q !== '' ? 'Results for "' . htmlspecialchars($q) . '"' : 'All Products'; ?>
        </h1>
    </div>

    <section class="filters-section">
        <div class="page-wrap">
            <div class="filters-row">
                <span class="results-count">Showing <?php echo $showingFrom; ?> - <?php echo $showingTo; ?> of <?php echo $totalCount; ?> results for "<?php echo htmlspecialchars($q); ?>"</span>
                <select class="filter-select" onchange="window.location.href=this.value" aria-label="Sort products">
                    <option value="<?php echo buildSearchUrl(['sort' => 'name']); ?>" <?php echo $sort === 'name' ? 'selected' : ''; ?>>Sort by: Name A-Z</option>
                    <option value="<?php echo buildSearchUrl(['sort' => 'price-low']); ?>" <?php echo $sort === 'price-low' ? 'selected' : ''; ?>>Price: Low to High</option>
                    <option value="<?php echo buildSearchUrl(['sort' => 'price-high']); ?>" <?php echo $sort === 'price-high' ? 'selected' : ''; ?>>Price: High to Low</option>
                    <option value="<?php echo buildSearchUrl(['sort' => 'rating']); ?>" <?php echo $sort === 'rating' ? 'selected' : ''; ?>>Highest Rated</option>
                </select>
            </div>
        </div>
    </section>

    <div class="search-container">
        <aside class="search-sidebar">
            <!-- SHOP TYPE -->
            <div class="filter-group">
                <h3 class="filter-title">Shop Category</h3>
                <div class="filter-options">
                    <a href="<?php echo buildSearchUrl(['shop' => null]); ?>" class="filter-option">
                        <input type="radio" name="shop" <?php echo !$shopFilter ? 'checked' : ''; ?>>
                        <span>All Shops</span>
                    </a>
                    <?php
                    $shops = ['BUTCHER' => 'Butcher', 'GREENGROCER' => 'Greengrocer', 'FISHMONGER' => 'Fishmonger', 'BAKERY' => 'Bakery', 'DELICATESSEN' => 'Delicatessen'];
                    foreach ($shops as $val => $label): ?>
                        <a href="<?php echo buildSearchUrl(['shop' => $val]); ?>" class="filter-option">
                            <input type="radio" name="shop" <?php echo $shopFilter === $val ? 'checked' : ''; ?>>
                            <span><?php echo $label; ?></span>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- DIETARY -->
            <div class="filter-group">
                <h3 class="filter-title">Dietary Requirements</h3>
                <div class="filter-options">
                    <a href="<?php echo buildSearchUrl(['type' => 'VEG']); ?>" class="filter-option">
                        <input type="checkbox" <?php echo $typeFilter === 'VEG' ? 'checked' : ''; ?>>
                        <span>Vegetarian</span>
                    </a>
                    <a href="<?php echo buildSearchUrl(['type' => 'NON-VEG']); ?>" class="filter-option">
                        <input type="checkbox" <?php echo $typeFilter === 'NON-VEG' ? 'checked' : ''; ?>>
                        <span>Non-Vegetarian</span>
                    </a>
                    <a href="<?php echo buildSearchUrl(['type' => 'VEGAN']); ?>" class="filter-option">
                        <input type="checkbox" <?php echo $typeFilter === 'VEGAN' ? 'checked' : ''; ?>>
                        <span>Vegan</span>
                    </a>
                    <a href="<?php echo buildSearchUrl(['type' => 'GLUTEN-FREE']); ?>" class="filter-option">
                        <input type="checkbox" <?php echo $typeFilter === 'GLUTEN-FREE' ? 'checked' : ''; ?>>
                        <span>Gluten Free</span>
                    </a>
                    <?php if ($typeFilter): ?>
                        <a href="<?php echo buildSearchUrl(['type' => null]); ?>" style="font-size: 12px; color: var(--primary-orange); margin-top: 4px;">Clear dietary filters</a>
                    <?php endif; ?>
                </div>
            </div>

            <!-- PRICE RANGE -->
            <div class="filter-group">
                <h3 class="filter-title">Price Range</h3>
                <div class="filter-options">
                    <a href="<?php echo buildSearchUrl(['price' => '0-5']); ?>" class="filter-option">
                        <input type="radio" <?php echo $priceFilter === '0-5' ? 'checked' : ''; ?>>
                        <span>Under £5</span>
                    </a>
                    <a href="<?php echo buildSearchUrl(['price' => '5-10']); ?>" class="filter-option">
                        <input type="radio" <?php echo $priceFilter === '5-10' ? 'checked' : ''; ?>>
                        <span>£5 to £10</span>
                    </a>
                    <a href="<?php echo buildSearchUrl(['price' => '10-20']); ?>" class="filter-option">
                        <input type="radio" <?php echo $priceFilter === '10-20' ? 'checked' : ''; ?>>
                        <span>£10 to £20</span>
                    </a>
                    <a href="<?php echo buildSearchUrl(['price' => '20+']); ?>" class="filter-option">
                        <input type="radio" <?php echo $priceFilter === '20+' ? 'checked' : ''; ?>>
                        <span>Over £20</span>
                    </a>
                    <?php if ($priceFilter): ?>
                        <a href="<?php echo buildSearchUrl(['price' => null]); ?>" style="font-size: 12px; color: var(--primary-orange); margin-top: 4px;">Clear price filter</a>
                    <?php endif; ?>
                </div>
            </div>

            <!-- RATING -->
            <div class="filter-group">
                <h3 class="filter-title">Customer Rating</h3>
                <div class="filter-options">
                    <a href="<?php echo buildSearchUrl(['rating' => '4']); ?>" class="filter-option">
                        <input type="radio" <?php echo $ratingFilter >= 4 ? 'checked' : ''; ?>>
                        <div class="rating-stars-filter">
                            <span class="material-icons-outlined">star</span>
                            <span class="material-icons-outlined">star</span>
                            <span class="material-icons-outlined">star</span>
                            <span class="material-icons-outlined">star</span>
                            <span class="material-icons-outlined" style="color:#ccc">star</span>
                            <span style="color:var(--text-dark-gray); margin-left:4px;">4.0 & Up</span>
                        </div>
                    </a>
                    <a href="<?php echo buildSearchUrl(['rating' => '3']); ?>" class="filter-option">
                        <input type="radio" <?php echo $ratingFilter >= 3 && $ratingFilter < 4 ? 'checked' : ''; ?>>
                        <div class="rating-stars-filter">
                            <span class="material-icons-outlined">star</span>
                            <span class="material-icons-outlined">star</span>
                            <span class="material-icons-outlined">star</span>
                            <span class="material-icons-outlined" style="color:#ccc">star</span>
                            <span class="material-icons-outlined" style="color:#ccc">star</span>
                            <span style="color:var(--text-dark-gray); margin-left:4px;">3.0 & Up</span>
                        </div>
                    </a>
                    <?php if ($ratingFilter > 0): ?>
                        <a href="<?php echo buildSearchUrl(['rating' => null]); ?>" style="font-size: 12px; color: var(--primary-orange); margin-top: 4px;">Clear rating filter</a>
                    <?php endif; ?>
                </div>
            </div>

            <!-- AVAILABILITY -->
            <div class="filter-group">
                <h3 class="filter-title">Availability</h3>
                <div class="filter-options">
                    <a href="<?php echo buildSearchUrl(['in_stock' => $inStockFilter ? '0' : '1']); ?>" class="filter-option">
                        <input type="checkbox" <?php echo $inStockFilter ? 'checked' : ''; ?>>
                        <span>In Stock Only</span>
                    </a>
                </div>
            </div>
        </aside>

        <main class="search-main">
            <?php if ($q !== '' && empty($products)): ?>
            <!-- NO RESULTS -->
            <div class="empty-state">
                <div class="icon-wrap">
                    <span class="material-icons-outlined">search_off</span>
                </div>
                <h2>No results found</h2>
                <p>We couldn't find anything matching <strong>"<?php echo htmlspecialchars($q); ?>"</strong>.</p>
                <p>Try a different keyword or explore our full product range.</p>
                <a href="category.php">
                    Browse all products
                    <span class="material-icons-outlined" style="font-size:16px;">arrow_forward</span>
                </a>
            </div>

            <?php elseif (!empty($products)): ?>
            <!-- PRODUCT GRID -->
            <div class="product-grid" id="productGrid">
                <?php foreach ($products as $p):
                    $stock = intval($p['STOCK']);
                    $avgRating = (float)$p['AVG_RATING'];
                    $reviewCount = (int)$p['REVIEW_COUNT'];
                    
                    if ($stock === 0) {
                        $stockBadge = '<span class="stock-badge out">Out of stock</span>';
                    } else {
                        $stockBadge = '';
                    }

                    $price = number_format($p['PRICE'], 2);
                    $imgSrc = $p['IMAGE_URL'] ?: 'assets/Item-image/green-bell-pepper-isolated.jpg';
                    ?>
                    <div class="product-card <?php echo $stock === 0 ? 'is-out-of-stock' : ''; ?>" data-price="<?php echo $p['PRICE']; ?>" data-name="<?php echo htmlspecialchars($p['NAME']); ?>">
                        <div class="product-card-inner">
                            <div class="product-image-wrapper">
                                <?php echo $stockBadge; ?>
                                <button class="favorite-btn" aria-label="Add to wishlist" onclick="addToWishlist(<?php echo $p['PRODUCT_ID']; ?>)">
                                    <span class="material-icons-outlined">favorite_border</span>
                                </button>
                                <img src="<?php echo $imgSrc; ?>" alt="<?php echo htmlspecialchars($p['NAME']); ?>" onerror="this.src='assets/Item-image/green-bell-pepper-isolated.jpg';">
                            </div>
                            <div class="product-info">
                                <a class="product-name" href="product.php?id=<?php echo $p['PRODUCT_ID']; ?>">
                                    <?php echo htmlspecialchars($p['NAME']); ?>
                                </a>
                            <div class="product-shop">
                                <a href="shop.php?shop_id=<?php echo $p['SHOP_ID']; ?>" class="shop-link">
                                    <span class="material-icons-outlined">storefront</span>
                                    <?php echo htmlspecialchars($p['SHOP_NAME']); ?>
                                </a>
                            </div>
                                <div class="rating-row">
                                    <div class="rating-stars">
                                        <?php echo renderStars($avgRating); ?>
                                    </div>
                                    <span class="rating-count">(<?php echo $reviewCount; ?>)</span>
                                </div>
                                <div class="price-row">
                                    <div class="price-group">
                                        <span class="current-price">£<?php echo $price; ?></span>
                                        <span class="unit">/ <?php echo htmlspecialchars($p['UNIT'] ?: 'unit'); ?></span>
                                    </div>
                                </div>
                                <button class="add-to-cart-btn" onclick="addToCart(<?php echo $p['PRODUCT_ID']; ?>)" <?php echo $stock === 0 ? 'disabled aria-disabled="true"' : ''; ?>>
                                    <span class="material-icons-outlined">shopping_cart</span>
                                    <?php echo $stock === 0 ? 'Out of stock' : 'Add to cart'; ?>
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </main>
    </div>

    <!-- POPULAR SEARCHES -->
    <section class="popular-section">
        <div class="page-wrap">
            <h2 class="popular-title">Popular Searches</h2>
            <div class="popular-grid">
                <a href="search.php?q=organic+vegetables" class="popular-item">
                    <span class="material-icons-outlined">eco</span>
                    Organic Vegetables
                </a>
                <a href="search.php?q=fresh+fish" class="popular-item">
                    <span class="material-icons-outlined">set_meal</span>
                    Fresh Fish
                </a>
                <a href="search.php?q=artisan+bread" class="popular-item">
                    <span class="material-icons-outlined">bakery_dining</span>
                    Artisan Bread
                </a>
                <a href="search.php?q=free+range+eggs" class="popular-item">
                    <span class="material-icons-outlined">egg</span>
                    Free Range Eggs
                </a>
                <a href="search.php?q=steak" class="popular-item">
                    <span class="material-icons-outlined">restaurant</span>
                    Premium Steak
                </a>
                <a href="search.php?q=cheese" class="popular-item">
                    <span class="material-icons-outlined">local_dining</span>
                    Local Cheese
                </a>
            </div>
        </div>
    </section>

    <!-- Toast notification -->
    <div class="toast-wrap" id="toastWrap"></div>

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
                <a href="shop.php?shop=greengrocer">Green Grocer</a>
                <a href="shop.php?shop=butcher">The Butcher</a>
                <a href="shop.php?shop=bakery">Bakery</a>
                <a href="shop.php?shop=delicatessen">Delicatessen</a>
                <a href="shop.php?shop=fishmonger">Fishmonger</a>
                <a href="category.php">Dairy &amp; Eggs</a>
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
                <a href="privacy.html">Privacy Policy</a><a href="terms.html">Terms of Service</a>
                <a href="register-trader.html">Apply as a trader</a>
            </div>
        </div>
    </footer>

    <script>
        function showToast(msg, type) {
            var wrap = document.getElementById('toastWrap');
            var el = document.createElement('div');
            el.className = 'toast ' + (type || 'info');
            el.innerHTML = '<span class="material-icons-outlined">' + (type === 'success' ? 'check_circle' : type === 'error' ? 'error' : 'info') + '</span><span>' + msg + '</span>';
            wrap.appendChild(el);
            setTimeout(function() {
                el.classList.add('fading');
                el.addEventListener('animationend', function() { el.remove(); });
            }, 3200);
        }

        window.addEventListener('scroll', function() {
            const header = document.querySelector('header');
            if (!header) return;
            if (window.scrollY > 50) {
                header.classList.add('scrolled');
            } else {
                header.classList.remove('scrolled');
            }
        });

        (function initUserMenu() {
            const btn = document.getElementById('userDropdownBtn');
            const menu = document.getElementById('userDropdown');
            if (!btn || !menu) return;

            btn.addEventListener('click', function(e) {
                e.preventDefault();
                menu.classList.toggle('open');
            });

            document.addEventListener('click', function(e) {
                if (!menu.classList.contains('open')) return;
                if (menu.contains(e.target) || btn.contains(e.target)) return;
                menu.classList.remove('open');
            });
        })();

        (function initFilters() {
            const grid = document.getElementById('productGrid');
            const chips = document.querySelectorAll('.filter-chip');
            const countEl = document.querySelector('.results-count');
            if (!grid || chips.length === 0) return;

            const cards = Array.from(grid.querySelectorAll('.product-card'));
            const total = cards.length;

            function updateCount(visible) {
                if (!countEl) return;
                const from = visible > 0 ? 1 : 0;
                countEl.textContent = 'Showing ' + from + ' - ' + visible + ' of ' + total + ' results';
            }

            function applyFilter(filter) {
                let visible = 0;
                cards.forEach(function(card) {
                    const matches = filter === 'all' || card.dataset.stock === filter;
                    card.style.display = matches ? '' : 'none';
                    if (matches) visible += 1;
                });
                updateCount(visible);
            }

            chips.forEach(function(chip) {
                chip.addEventListener('click', function() {
                    chips.forEach(function(btn) { btn.classList.remove('active'); });
                    chip.classList.add('active');
                    applyFilter(chip.dataset.filter || 'all');
                });
            });

            applyFilter('all');
        })();

        async function addToCart(productId) {
            try {
                const res = await fetch('api/cart/add-to-cart.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ product_id: productId, quantity: 1 })
                });
                const data = await res.json();

                if (data.success) {
                    const countEl = document.getElementById('cartCount');
                    if (countEl) countEl.textContent = data.new_count;
                    showToast('Product added to cart.', 'success');
                } else if (data.redirect) {
                    window.location.href = 'login.html';
                } else {
                    showToast(data.message || 'Failed to add to cart.', 'error');
                }
            } catch (error) {
                console.error('Cart error:', error);
                showToast('Network error. Please try again.', 'error');
            }
        }

        function addToWishlist(productId) {
            showToast('Added to wishlist.', 'success');
        }

        function sortProducts(sortBy) {
            const grid = document.getElementById('productGrid');
            if (!grid) return;
            const cards = Array.from(grid.querySelectorAll('.product-card'));
            
            cards.sort((a, b) => {
                if (sortBy === 'price-low') {
                    return parseFloat(a.dataset.price) - parseFloat(b.dataset.price);
                } else if (sortBy === 'price-high') {
                    return parseFloat(b.dataset.price) - parseFloat(a.dataset.price);
                } else if (sortBy === 'name') {
                    return a.dataset.name.localeCompare(b.dataset.name);
                }
                return 0;
            });
            
            cards.forEach(card => grid.appendChild(card));
        }

        async function addToCart(productId) {
            const uid = sessionStorage.getItem("user_id");
            if (!uid) {
                showModal("Please login to add items to cart", "warning");
                window.location.href = "login.html";
                return;
            }
            try {
                const response = await fetch("../api/cart/add-to-cart.php", {
                    method: "POST",
                    credentials: "same-origin",
                    headers: { "Content-Type": "application/json" },
                    body: JSON.stringify({ product_id: parseInt(productId), quantity: 1 }),
                });
                const result = await response.json();
                if (result.success) {
                    const cartCountEl = document.getElementById("cartCount");
                    if (cartCountEl) cartCountEl.textContent = result.new_count;
                    showModal("Added to cart!", "success");
                } else {
                    if (result.error && result.error.includes("20 products")) {
                        showModal("Only 20 items are allowed per order. Please check your cart and remove items before adding more.", "warning", "Cart Limit Reached");
                    } else {
                        showModal(result.error || "Failed to add to cart", "error");
                    }
                }
            } catch (err) {
                console.error("Add to cart error:", err);
                showModal("Network error. Please try again.", "error");
            }
        }

        async function addToWishlist(productId) {
            const uid = sessionStorage.getItem("user_id");
            if (!uid) {
                showModal("Please login to add to wishlist", "warning");
                window.location.href = "login.html";
                return;
            }
            try {
                const response = await fetch("../api/customer/add-to-wishlist.php", {
                    method: "POST",
                    credentials: "same-origin",
                    headers: { "Content-Type": "application/json" },
                    body: JSON.stringify({ product_id: parseInt(productId) }),
                });
                const result = await response.json();
                if (result.success) {
                    const wishCountEl = document.getElementById("wishlistCount");
                    if (wishCountEl) wishCountEl.textContent = result.count || (parseInt(wishCountEl.textContent) || 0) + 1;
                    showModal("Added to wishlist!", "success");
                } else {
                    showModal(result.error || "Error updating wishlist", "error");
                }
            } catch (err) {
                showModal("Network error updating wishlist.", "error");
            }
        }
    </script>
    
    <!-- Professional Modal -->
    <div class="prof-modal-overlay" id="profModal">
        <div class="prof-modal" style="position:relative;">
            <button class="prof-modal-close" onclick="closeProfModal()">&times;</button>
            <div class="prof-modal-icon" id="profModalIcon"></div>
            <div class="prof-modal-title" id="profModalTitle"></div>
            <div class="prof-modal-message" id="profModalMessage"></div>
            <div class="prof-modal-buttons" id="profModalButtons"></div>
        </div>
    </div>
    <style>
        .prof-modal-overlay{position:fixed;inset:0;background:rgba(0,0,0,0.5);backdrop-filter:blur(4px);display:none;align-items:center;justify-content:center;z-index:10000;opacity:0;transition:opacity 0.25s ease}
        .prof-modal-overlay.show{display:flex;opacity:1}
        .prof-modal{background:#fff;border-radius:12px;padding:28px 32px;max-width:400px;width:90%;text-align:center;box-shadow:0 20px 60px rgba(0,0,0,0.2);transform:scale(0.9);transition:transform 0.25s ease}
        .prof-modal-overlay.show .prof-modal{transform:scale(1)}
        .prof-modal-icon{width:64px;height:64px;border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 20px;font-size:32px}
        .prof-modal-icon.success{background:#e8f5e9;color:#2e7d32}
        .prof-modal-icon.error{background:#ffebee;color:#c62828}
        .prof-modal-icon.warning{background:#fff3e0;color:#ef6c00}
        .prof-modal-icon.info{background:#e3f2fd;color:#1565c0}
        .prof-modal-icon.confirm{background:#f3e5f5;color:#7b1fa2}
        .prof-modal-title{font-size:20px;font-weight:600;margin-bottom:12px;color:#1a1a1a}
        .prof-modal-message{font-size:15px;color:#555;line-height:1.5;margin-bottom:24px}
        .prof-modal-buttons{display:flex;gap:12px;justify-content:center}
        .prof-modal-btn{padding:12px 28px;border-radius:8px;font-size:15px;font-weight:500;cursor:pointer;border:none;transition:all 0.2s ease}
        .prof-modal-btn.primary{background:#0f260b;color:#fff}
        .prof-modal-btn.primary:hover{background:#1c3c17}
        .prof-modal-btn.secondary{background:#f0f0f0;color:#333}
        .prof-modal-btn.secondary:hover{background:#e0e0e0}
        .prof-modal-close{position:absolute;top:16px;right:16px;background:none;border:none;font-size:24px;cursor:pointer;color:#999;line-height:1}
    </style>
    <script>
        function showModal(msg, type = "info", title = "") {
            const overlay = document.getElementById("profModal");
            const icon = document.getElementById("profModalIcon");
            const titleEl = document.getElementById("profModalTitle");
            const msgEl = document.getElementById("profModalMessage");
            const btns = document.getElementById("profModalButtons");
            const icons = { success: "✓", error: "✕", warning: "⚠", info: "ℹ", confirm: "?" };
            const titles = { success: "Success", error: "Error", warning: "Warning", info: "Notice", confirm: "Confirm" };
            icon.className = "prof-modal-icon " + type;
            icon.textContent = icons[type] || "ℹ";
            titleEl.textContent = title || titles[type] || "";
            msgEl.textContent = msg;
            btns.innerHTML = '<button class="prof-modal-btn primary" onclick="closeProfModal()">OK</button>';
            overlay.classList.add("show");
            document.body.style.overflow = "hidden";
        }
        function closeProfModal() {
            document.getElementById("profModal").classList.remove("show");
            document.body.style.overflow = "";
        }
    </script>
</body>
</html>
