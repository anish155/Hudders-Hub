<?php
/**
 * Hudders Hub - Category / Shop Page
 * Handles: ?shop=, ?type=, ?sub=, ?sort=, ?page=
 * Connects to Oracle DB via config/database.php
 */

// ==========================================
// STEP 1: INCLUDE CONFIG & READ URL PARAMS
// ==========================================
require_once 'config/database.php';
require_once 'config/session.php';

// Read & sanitize URL parameters
$shop = isset($_GET['shop']) ? strtolower(trim($_GET['shop'])) : '';
$type = isset($_GET['type']) ? strtolower(trim($_GET['type'])) : '';
$sub  = isset($_GET['sub']) ? strtolower(trim($_GET['sub'])) : '';
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'name';
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;

// Validation whitelists (Security: prevent invalid inputs)
$valid_shops = ['butcher', 'greengrocer', 'fishmonger', 'bakery', 'delicatessen'];
$valid_types = ['veg', 'non-veg', 'vegan', 'gluten-free', 'fresh-today'];
$valid_sorts = ['name', 'price-low', 'price-high', 'rating', 'newest'];

if ($shop && !in_array($shop, $valid_shops)) $shop = '';
if ($type && !in_array($type, $valid_types)) $type = '';
if (!in_array($sort, $valid_sorts)) $sort = 'name';

// ==========================================
// STEP 2: SET PAGE TITLE & DESCRIPTION
// ==========================================
$page_title = 'All Products';
$page_desc  = 'Browse fresh items from all our local traders.';
$shop_emoji = '🛒';
$shop_color = '#2D6A4F';

if ($shop) {
    $page_title = ucfirst($shop) . ' Shop';
    $shop_emoji = match($shop) {
        'butcher' => '🥩', 'greengrocer' => '🥬', 'fishmonger' => '🐟',
        'bakery' => '🍞', 'delicatessen' => '🧀', default => '🛒'
    };
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
} elseif ($type) {
    $type_labels = ['veg' => 'Vegetarian', 'non-veg' => 'Non-Vegetarian', 'vegan' => 'Vegan', 'gluten-free' => 'Gluten Free', 'fresh-today' => 'Fresh Today'];
    $page_title = $type_labels[$type] ?? 'Products';
    $page_desc  = 'Products matching your dietary preferences.';
}

// ==========================================
// STEP 3: BUILD & EXECUTE ORACLE QUERY
// ==========================================
$products    = [];
$totalCount  = 0;
$perPage     = 12;
$offset      = ($page - 1) * $perPage;

// Load dynamic subcategories from PRODUCT_CATEGORY (only for shop-type pages)
$subcategories = [];
if ($shop) {
    $sc_sql  = "SELECT DISTINCT pc.category_name FROM PRODUCT p
                JOIN SHOP s ON p.shop_id = s.shop_id
                JOIN PRODUCT_CATEGORY pc ON p.category_id = pc.category_id
                WHERE UPPER(s.shop_type) = :sh_type ORDER BY pc.category_name";
    $sc_stmt = oci_parse($conn, $sc_sql);
    oci_bind_by_name($sc_stmt, ':sh_type', strtoupper($shop));
    oci_execute($sc_stmt);
    while ($row = oci_fetch_assoc($sc_stmt)) {
        $subcategories[] = $row['CATEGORY_NAME'];
    }
    oci_free_statement($sc_stmt);
}

$base  = "SELECT p.product_id, p.name, p.description, p.price, p.stock,
                p.min_order, p.max_order, p.allergen_info, p.dietary_tags,
                p.unit, p.status,
                s.name AS shop_name, s.shop_type,
                pc.category_name,
                NVL(ROUND(AVG(r.rating), 1), 0) AS avg_rating,
                COUNT(r.review_id) AS review_count
         FROM PRODUCT p
         JOIN SHOP s ON p.shop_id = s.shop_id
         LEFT JOIN PRODUCT_CATEGORY pc ON p.category_id = pc.category_id
         LEFT JOIN REVIEW r ON r.product_id = p.product_id
         WHERE p.status = 'Active'";

$w    = "";   // assembled WHERE clause
$wCt  = "";   // same WHERE for count query
$params = [];

if ($shop) {
    $w   .= " AND UPPER(s.shop_type) = :shop_type";
    $wCt .= " AND UPPER(s.shop_type) = :shop_type";
    $params[':shop_type'] = strtoupper($shop);
}
if ($type === 'veg') {
    $w   .= " AND INSTR(UPPER(p.dietary_tags), :veg_tag) > 0";
    $wCt .= " AND INSTR(UPPER(p.dietary_tags), :veg_tag) > 0";
    $params[':veg_tag'] = 'VEGETARIAN';
} elseif ($type === 'vegan') {
    $w   .= " AND INSTR(UPPER(p.dietary_tags), :vegan_tag) > 0";
    $wCt .= " AND INSTR(UPPER(p.dietary_tags), :vegan_tag) > 0";
    $params[':vegan_tag'] = 'VEGAN';
} elseif ($type === 'gluten-free') {
    $w   .= " AND INSTR(UPPER(p.dietary_tags), :gf_tag) > 0";
    $wCt .= " AND INSTR(UPPER(p.dietary_tags), :gf_tag) > 0";
    $params[':gf_tag'] = 'GLUTEN FREE';
} elseif ($type === 'non-veg') {
    $w   .= " AND UPPER(s.shop_type) IN ('BUTCHER', 'FISHMONGER')";
    $wCt .= " AND UPPER(s.shop_type) IN ('BUTCHER', 'FISHMONGER')";
}
if ($sub) {
    $w   .= " AND UPPER(pc.category_name) = :subcategory";
    $wCt .= " AND UPPER(pc.category_name) = :subcategory";
    $params[':subcategory'] = strtoupper($sub);
}

$groupBy = " GROUP BY p.product_id, p.name, p.description, p.price, p.stock,
             p.min_order, p.max_order, p.allergen_info, p.dietary_tags,
             p.unit, p.status, s.name, s.shop_type, pc.category_name";

$sql     = $base . $w . $groupBy . " ORDER BY p.name ASC";

// Count query — same WHERE conditions
$countSql= "SELECT COUNT(DISTINCT p.product_id) AS total FROM PRODUCT p"
          . " JOIN SHOP s ON p.shop_id = s.shop_id"
          . " LEFT JOIN PRODUCT_CATEGORY pc ON p.category_id = pc.category_id"
          . " LEFT JOIN REVIEW r ON r.product_id = p.product_id"
          . " WHERE p.status = 'Active'" . $wCt;

$rawAll   = [];

// ---- Execute parent query (no OFFSET/FETCH — bug with GROUP BY + bind vars on this OCI8) ----
$stmt = oci_parse($conn, $sql);
foreach ($params as $key => $value) {
    oci_bind_by_name($stmt, $key, $value);
}
oci_execute($stmt);
while ($row = oci_fetch_assoc($stmt)) {
    $rawAll[] = $row;
}
oci_free_statement($stmt);

// ---- PHP-level sorts (avoids GROUP BY + ORDER BY complexity in Oracle) ----
if (!empty($rawAll)) {
    if ($sort === 'rating') {
        // Pre-compute avg ratings via separate tiny query
        $pids = array_map('intval', array_column($rawAll, 'PRODUCT_ID'));
        $in   = implode(',', $pids);
        $rSql = "SELECT product_id, NVL(ROUND(AVG(rating),1),0) AS avg_rating FROM REVIEW WHERE product_id IN ($in) GROUP BY product_id";
        $rSt  = oci_parse($conn, $rSql);
        oci_execute($rSt);
        $ratings = [];
        while ($r = oci_fetch_assoc($rSt)) { $ratings[(int)$r['PRODUCT_ID']] = (float)$r['AVG_RATING']; }
        oci_free_statement($rSt);
        usort($rawAll, function($a,$b) use ($ratings) {
            $ra = $ratings[(int)$a['PRODUCT_ID']] ?? 0; $rb = $ratings[(int)$b['PRODUCT_ID']] ?? 0;
            return $rb <=> $ra ?: strcmp($a['NAME'], $b['NAME']);
        });
    } elseif ($sort === 'price-low') {
        usort($rawAll, fn($a,$b) => ($a['PRICE'] <=> $b['PRICE']) ?: strcmp($a['NAME'],$b['NAME']));
    } elseif ($sort === 'price-high') {
        usort($rawAll, fn($a,$b) => ($b['PRICE'] <=> $a['PRICE']) ?: strcmp($a['NAME'],$b['NAME']));
    } elseif ($sort === 'newest') {
        usort($rawAll, fn($a,$b) => ((int)$b['PRODUCT_ID'] <=> (int)$a['PRODUCT_ID']));
    }
    // 'name' → already ordered by $sql → no extra sort needed
}

// ---- Execute count query ----
$countStmt = oci_parse($conn, $countSql);
foreach ($params as $key => $value) {
    if ($key === ':offset' || $key === ':limit') continue;
    oci_bind_by_name($countStmt, $key, $value);
}
oci_execute($countStmt);
$countRow = oci_fetch_assoc($countStmt);
$totalCount = $countRow ? intval($countRow['TOTAL']) : 0;
oci_free_statement($countStmt);

// ---- PHP-level pagination ----
$products     = array_slice($rawAll, $offset, $perPage);
$totalPages   = max(1, ceil($totalCount / $perPage));
$showingFrom  = $totalCount > 0 ? $offset + 1 : 0;
$showingTo    = min($offset + count($products), $totalCount);

oci_close($conn);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?> | Hudders Hub</title>
    <!-- External CSS Files -->
    <link rel="stylesheet" href="css/main.css">
    <link rel="stylesheet" href="css/category.css">
    <!-- Critical Inline Styles for Layout & Theme -->
    <style>
        :root {
            --primary-green: #2D6A4F; --secondary-gold: #D4A373; --orange-accent: #E76F51;
            --bg-cream: #FEFAE0; --dark-slate: #264653; --white: #FFFFFF; --light-grey: #F8F9FA;
        }
        body { font-family: system-ui, -apple-system, sans-serif; background: var(--bg-cream); color: var(--dark-slate); margin: 0; }
        .container { max-width: 1200px; margin: 0 auto; padding: 0 20px; }
        .category-layout { display: grid; grid-template-columns: 250px 1fr; gap: 24px; margin-top: 20px; }
        @media(max-width: 768px) { .category-layout { grid-template-columns: 1fr; } }
        
        /* Sidebar */
        .sidebar { background: var(--white); padding: 20px; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.05); height: fit-content; }
        .sidebar-section { margin-bottom: 24px; }
        .sidebar-title { font-size: 16px; font-weight: 700; margin: 0 0 12px 0; color: var(--primary-green); border-bottom: 2px solid var(--light-grey); padding-bottom: 8px; }
        .sidebar-link { display: block; padding: 8px 12px; color: var(--dark-slate); text-decoration: none; border-radius: 8px; margin-bottom: 4px; transition: 0.2s; font-size: 14px; }
        .sidebar-link:hover { background: var(--light-grey); }
        .sidebar-link.active { background: var(--primary-green); color: white; font-weight: 600; }
        .shop-link { display: flex; align-items: center; gap: 8px; }
        .shop-link span { font-size: 18px; }
        
        /* Main Content */
        .category-banner { background: linear-gradient(135deg, var(--primary-green), #1B4332); color: white; padding: 30px; border-radius: 16px; margin-bottom: 20px; text-align: center; }
        .category-banner h1 { margin: 0 0 8px 0; font-size: 28px; }
        .category-banner p { margin: 0; opacity: 0.9; }
        .sort-bar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 12px; }
        .results-count { font-size: 14px; color: #666; }
        .sort-btn { padding: 6px 12px; border: 1px solid #ddd; background: white; border-radius: 20px; cursor: pointer; font-size: 13px; transition: 0.2s; }
        .sort-btn:hover { border-color: var(--primary-green); }
        .sort-btn.active { background: var(--primary-green); color: white; border-color: var(--primary-green); }
        
        /* Product Grid */
        .product-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; }
        @media(max-width: 1024px) { .product-grid { grid-template-columns: repeat(3, 1fr); } }
        @media(max-width: 768px) { .product-grid { grid-template-columns: repeat(2, 1fr); } }
        @media(max-width: 480px) { .product-grid { grid-template-columns: 1fr; } }
        
        .product-card { background: white; border-radius: 12px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.05); transition: transform 0.2s, box-shadow 0.2s; display: flex; flex-direction: column; }
        .product-card:hover { transform: translateY(-4px); box-shadow: 0 8px 16px rgba(0,0,0,0.1); }
        .product-image { width: 100%; height: 180px; object-fit: cover; background: #f0f0f0; }
        .product-info { padding: 16px; flex: 1; display: flex; flex-direction: column; }
        .shop-tag { font-size: 12px; color: #666; margin-bottom: 4px; display: flex; align-items: center; gap: 4px; }
        .product-name { font-size: 16px; font-weight: 600; margin: 0 0 8px 0; line-height: 1.3; }
        .product-name a { color: var(--dark-slate); text-decoration: none; }
        .product-name a:hover { color: var(--primary-green); }
        .rating { color: #F4B740; font-size: 14px; margin-bottom: 8px; }
        .price-row { display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; }
        .price { font-size: 18px; font-weight: 700; color: var(--primary-green); }
        .unit { font-size: 12px; color: #888; }
        
        /* Stock Badges */
        .badge { display: inline-block; padding: 4px 8px; border-radius: 20px; font-size: 11px; font-weight: 600; text-transform: uppercase; }
        .badge.in-stock { background: #D4EDDA; color: #155724; }
        .badge.low-stock { background: #FFF3CD; color: #856404; }
        .badge.out-of-stock { background: #F8D7DA; color: #721C24; }
        
        .btn-add-cart { width: 100%; padding: 10px; border: none; border-radius: 8px; background: var(--orange-accent); color: white; font-weight: 600; cursor: pointer; margin-top: auto; transition: 0.2s; }
        .btn-add-cart:hover { background: #D65A3B; }
        .btn-add-cart:disabled { background: #ccc; cursor: not-allowed; }
        
        .no-products { grid-column: 1 / -1; text-align: center; padding: 60px 20px; background: white; border-radius: 12px; }
        .pagination { display: flex; justify-content: center; gap: 8px; margin-top: 30px; }
        .page-link { padding: 8px 14px; border: 1px solid #ddd; border-radius: 6px; text-decoration: none; color: var(--dark-slate); }
        .page-link.active { background: var(--primary-green); color: white; border-color: var(--primary-green); }
        .page-link.disabled { opacity: 0.5; pointer-events: none; }
        
        /* Header & Footer Basics */
        .header, .footer { background: white; padding: 16px 0; border-bottom: 1px solid #eee; }
        .header-content, .footer-content { max-width: 1200px; margin: 0 auto; padding: 0 20px; display: flex; justify-content: space-between; align-items: center; }
        .logo { font-size: 24px; font-weight: 800; color: var(--primary-green); text-decoration: none; }
        .breadcrumb { padding: 12px 20px; max-width: 1200px; margin: 0 auto; font-size: 14px; color: #666; }
        .breadcrumb a { color: var(--primary-green); text-decoration: none; }
        .filter-check { display: flex; align-items: center; gap: 8px; margin-bottom: 8px; cursor: pointer; font-size: 14px; }
    </style>
</head>
<body>

    <!-- HEADER -->
    <header class="header">
        <div class="header-content">
            <a href="index.php" class="logo">🥬 Hudders Hub</a>
            <div style="display: flex; align-items: center; gap: 16px;">
                <input type="text" placeholder="Search products..." style="padding: 8px 12px; border: 1px solid #ddd; border-radius: 20px; width: 200px;">
                <span id="cart-count" style="cursor: pointer; font-weight: 600;">🛒 Cart (<span id="cart-num">0</span>)</span>
                <span id="user-status" style="cursor: pointer; font-weight: 600;">Login</span>
            </div>
        </div>
    </header>

    <!-- BREADCRUMB -->
    <nav class="breadcrumb">
        <a href="index.php">Home</a> /
        <a href="category.php">Categories</a> /
        <strong><?php echo htmlspecialchars($page_title); ?></strong>
    </nav>

    <div class="container category-layout">
        <!-- LEFT SIDEBAR -->
        <aside class="sidebar">
            <div class="sidebar-section">
                <h3 class="sidebar-title">Browse by Shop</h3>
                <a href="category.php" class="sidebar-link <?php echo !$shop ? 'active' : ''; ?>">All Shops</a>
                <a href="category.php?shop=butcher" class="sidebar-link shop-link <?php echo $shop==='butcher'?'active':''; ?>">🥩 Butcher</a>
                <a href="category.php?shop=greengrocer" class="sidebar-link shop-link <?php echo $shop==='greengrocer'?'active':''; ?>">🥬 Greengrocer</a>
                <a href="category.php?shop=fishmonger" class="sidebar-link shop-link <?php echo $shop==='fishmonger'?'active':''; ?>">🐟 Fishmonger</a>
                <a href="category.php?shop=bakery" class="sidebar-link shop-link <?php echo $shop==='bakery'?'active':''; ?>">🍞 Bakery</a>
                <a href="category.php?shop=delicatessen" class="sidebar-link shop-link <?php echo $shop==='delicatessen'?'active':''; ?>">🧀 Delicatessen</a>
            </div>

            <div class="sidebar-section">
                <h3 class="sidebar-title">Browse by Type</h3>
                <a href="category.php?type=veg" class="sidebar-link <?php echo $type==='veg'?'active':''; ?>">Vegetarian</a>
                <a href="category.php?type=non-veg" class="sidebar-link <?php echo $type==='non-veg'?'active':''; ?>">Non-Vegetarian</a>
                <a href="category.php?type=vegan" class="sidebar-link <?php echo $type==='vegan'?'active':''; ?>">Vegan</a>
                <a href="category.php?type=gluten-free" class="sidebar-link <?php echo $type==='gluten-free'?'active':''; ?>">Gluten Free</a>
                <a href="category.php?type=fresh-today" class="sidebar-link <?php echo $type==='fresh-today'?'active':''; ?>">Fresh Today</a>
            </div>

            <!-- Dynamic Subcategories -->
            <?php if (!empty($subcategories)): ?>
            <div class="sidebar-section">
                <h3 class="sidebar-title">Subcategories</h3>
                <?php foreach($subcategories as $subcat): ?>
                    <a href="category.php?shop=<?php echo $shop; ?>&sub=<?php echo strtolower($subcat); ?>"
                       class="sidebar-link <?php echo $sub === strtolower($subcat) ? 'active' : ''; ?>">
                        <?php echo htmlspecialchars($subcat); ?>
                    </a>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <div class="sidebar-section">
                <h3 class="sidebar-title">Sort By</h3>
                <button class="sort-btn <?php echo $sort==='name'?'active':''; ?>" onclick="sortProducts('name')">Name A-Z</button>
                <button class="sort-btn <?php echo $sort==='price-low'?'active':''; ?>" onclick="sortProducts('price-low')">Price: Low to High</button>
                <button class="sort-btn <?php echo $sort==='price-high'?'active':''; ?>" onclick="sortProducts('price-high')">Price: High to Low</button>
                <button class="sort-btn <?php echo $sort==='rating'?'active':''; ?>" onclick="sortProducts('rating')">Highest Rated</button>
                <button class="sort-btn <?php echo $sort==='newest'?'active':''; ?>" onclick="sortProducts('newest')">Newest First</button>
            </div>

            <div class="sidebar-section">
                <h3 class="sidebar-title">Filters</h3>
                <label class="filter-check"><input type="checkbox" id="filter-stock"> In Stock Only</label>
                <label class="filter-check"><input type="checkbox" id="filter-veg"> Vegetarian</label>
                <label class="filter-check"><input type="checkbox" id="filter-vegan"> Vegan</label>
                <label class="filter-check"><input type="checkbox" id="filter-gf"> Gluten Free</label>
            </div>
        </aside>

        <!-- RIGHT MAIN CONTENT -->
        <main class="main-content">
            <div class="category-banner">
                <h1><?php echo htmlspecialchars($shop_emoji . ' ' . $page_title); ?></h1>
                <p><?php echo htmlspecialchars($page_desc); ?></p>
            </div>

            <div class="sort-bar">
                <span class="results-count">Showing <?php echo $showingFrom; ?> to <?php echo $showingTo; ?> of <?php echo $totalCount; ?> products</span>
                <div style="display:flex; gap:8px;">
                    <button style="border:1px solid #ddd; padding:4px 8px; background:white; border-radius:4px;">Grid</button>
                    <button style="border:1px solid #ddd; padding:4px 8px; background:white; border-radius:4px;">List</button>
                </div>
            </div>

            <div class="product-grid" id="productGrid">
                <?php if (empty($products)): ?>
                    <div class="no-products">
                        <h2>No products found</h2>
                        <p>Try adjusting your filters or browse all products.</p>
                        <a href="category.php" class="btn-add-cart" style="width:auto; display:inline-block; margin-top:12px; text-decoration:none;">View All Products</a>
                    </div>
                <?php else: ?>
                    <?php foreach($products as $p):
                        $avgRating = round($p['AVG_RATING'], 1);
                        $stars = str_repeat('⭐', max(1, floor($avgRating)));
                        $stock = intval($p['STOCK']);

                        // .. Stock badge logic
                        if ($stock > 5) { $stockBadge = '<span class="badge in-stock">In Stock</span>'; }
                        elseif ($stock > 0) { $stockBadge = '<span class="badge low-stock">Low Stock</span>'; }
                        else { $stockBadge = '<span class="badge out-of-stock">Out of Stock</span>'; }

                        $price = number_format($p['PRICE'], 2);
                        $sEmoji = match(strtolower($p['SHOP_TYPE'])) {
                            'butcher' => '🥩', 'greengrocer' => '🥬', 'fishmonger' => '🐟',
                            'bakery' => '🍞', 'delicatessen' => '🧀', default => '🏪'
                        };

                        // Map product name / shop_type to a real existing asset image
                        $pName = strtolower($p['NAME']);
                        $imgMap = [
                            'green bell pepper'  => 'assets/Item-image/green-bell-pepper-isolated.jpg',
                            'eggs'               => 'assets/Item-image/green-broccoli.jpg',
                            'broccoli'           => 'assets/Item-image/green-broccoli.jpg',
                            'beef'               => 'assets/other-images/fresh-raw-meat-cuts-with-rosemary-spices-dark-slate.jpg',
                            'steak'              => 'assets/other-images/fresh-raw-meat-cuts-with-rosemary-spices-dark-slate.jpg',
                            'sausage'            => 'assets/other-images/fresh-raw-meat-cuts-with-rosemary-spices-dark-slate.jpg',
                            'lamb'               => 'assets/other-images/fresh-raw-meat-cuts-with-rosemary-spices-dark-slate.jpg',
                            'salmon'             => 'assets/other-images/top-view-fresh-fish-slices-dark-table-seafood-ocean-meat-sea-meal-dish-food-salad-water-pepper.jpg',
                            'cod'                => 'assets/other-images/top-view-fresh-fish-slices-dark-table-seafood-ocean-meat-sea-meal-dish-food-salad-water-pepper.jpg',
                            'prawn'              => 'assets/other-images/top-view-fresh-fish-slices-dark-table-seafood-ocean-meat-sea-meal-dish-food-salad-water-pepper.jpg',
                            'seafood'            => 'assets/other-images/top-view-fresh-fish-slices-dark-table-seafood-ocean-meat-sea-meal-dish-food-salad-water-pepper.jpg',
                            'fish'               => 'assets/other-images/top-view-fresh-fish-slices-dark-table-seafood-ocean-meat-sea-meal-dish-food-salad-water-pepper.jpg',
                            'brie'               => 'assets/other-images/imgi_47_cheese-wood_573717-86.jpg',
                            'cheese'             => 'assets/other-images/imgi_47_cheese-wood_573717-86.jpg',
                            'bread'              => 'assets/other-images/imgi_57_sweet-bun-with-chocolate-syrup-peeled-orange_114579-2623.jpg',
                            'sourdough'          => 'assets/other-images/imgi_57_sweet-bun-with-chocolate-syrup-peeled-orange_114579-2623.jpg',
                            'spinach'            => 'assets/Item-image/imgi_46_chinese-broccoli-vegetables_1203-6831.jpg',
                            'carrot'             => 'assets/Item-image/imgi_46_chinese-broccoli-vegetables_1203-6831.jpg',
                            'vegetable'          => 'assets/Item-image/imgi_46_chinese-broccoli-vegetables_1203-6831.jpg',
                        ];
                        $imgSrc = 'assets/Item-image/green-bell-pepper-isolated.jpg';
                        foreach ($imgMap as $k => $v) {
                            if (str_contains($pName, $k)) { $imgSrc = $v; break; }
                        }
                        ?>
                        <div class="product-card">
                            <img src="<?php echo $imgSrc; ?>"
                                 alt="<?php echo htmlspecialchars($p['NAME']); ?>"
                                 class="product-image"
                                 onerror="this.src='assets/Item-image/green-bell-pepper-isolated.jpg';">
                        <div class="product-info">
                            <div class="shop-tag">
                                <?php echo $sEmoji . ' ' . htmlspecialchars($p['SHOP_NAME']); ?>
                            </div>
                            <h3 class="product-name">
                                <a href="product.php?id=<?php echo $p['PRODUCT_ID']; ?>">
                                    <?php echo htmlspecialchars($p['NAME']); ?>
                                </a>
                            </h3>
                            <div class="rating"><?php echo $stars; ?> (<?php echo $p['REVIEW_COUNT']; ?>)</div>
                            <div class="price-row">
                                <div>
                                    <span class="price">Rs. <?php echo $price; ?></span>
                                    <span class="unit">/ <?php echo htmlspecialchars($p['UNIT'] ?: 'unit'); ?></span>
                                </div>
                                <?php echo $stockBadge; ?>
                            </div>
                            <button class="btn-add-cart"
                                    onclick="addToCart(<?php echo $p['PRODUCT_ID']; ?>)"
                                    <?php echo $stock === 0 ? 'disabled' : ''; ?>>
                                <?php echo $stock === 0 ? 'Sold Out' : 'Add to Cart'; ?>
                            </button>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
            <div class="pagination">
                <?php if ($page > 1): ?>
                    <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>" class="page-link">« Prev</a>
                <?php endif; ?>
                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>"
                       class="page-link <?php echo $i === $page ? 'active' : ''; ?>"><?php echo $i; ?></a>
                <?php endfor; ?>
                <?php if ($page < $totalPages): ?>
                    <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>" class="page-link">Next »</a>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </main>
    </div>

    <!-- FOOTER -->
    <footer class="footer" style="margin-top:40px; background:#264653; color:white; padding:30px 0;">
        <div class="footer-content">
            <div>
                <div style="font-size:20px; font-weight:700; margin-bottom:8px;">🥬 Hudders Hub</div>
                <p style="font-size:13px; opacity:0.8; max-width:250px;">Fresh local food delivered straight from farmers and traders to your doorstep.</p>
            </div>
            <div style="display:flex; gap:30px; font-size:13px;">
                <div><strong>Support</strong><br><a href="#" style="color:#D4A373;">Help Center</a><br><a href="#" style="color:#D4A373;">Contact Us</a></div>
                <div><strong>Shops</strong><br><a href="category.php?shop=butcher" style="color:#D4A373;">Butcher</a><br><a href="category.php?shop=greengrocer" style="color:#D4A373;">Greengrocer</a></div>
                <div><strong>Company</strong><br><a href="#" style="color:#D4A373;">About</a><br><a href="#" style="color:#D4A373;">Careers</a></div>
            </div>
            <div style="text-align:right;">
                <div style="margin-bottom:8px;">Newsletter</div>
                <input type="email" placeholder="Your email" style="padding:6px; border-radius:4px; border:none; width:150px;">
                <div style="margin-top:12px; font-size:12px; opacity:0.6;">© 2026 HuddersHub. All rights reserved.</div>
            </div>
        </div>
    </footer>

    <!-- JAVASCRIPT: Cart, Session & Sorting -->
    <script>
        // Initialize page: check session & fetch cart count
        async function initPage() {
            // Check login status via profile lookup
            try {
                const uid = sessionStorage.getItem('user_id');
                if (uid) {
                    const res = await fetch('api/customer/get-profile.php?user_id=' + uid);
                    const data = await res.json();
                    if (data.success && data.data) {
                        const f = (data.data.FIRSTNAME || '').trim();
                        const l = (data.data.LASTNAME || '').trim();
                        const full = [f, l].filter(Boolean).join(' ') || 'User';
                        document.getElementById('user-status').textContent = 'Hi, ' + full;
                        document.getElementById('user-status').href = 'customer/profile.html';
                        sessionStorage.setItem('userName', full);
                    }
                }
            } catch(e) { console.log('Session check skipped'); }

            // Fetch cart count
            try {
                const res = await fetch('api/customer/get-cart-count.php?user_id=' + (sessionStorage.getItem('user_id') || ''));
                const data = await res.json();
                document.getElementById('cart-num').textContent = data.count || 0;
            } catch(e) { console.log('Cart fetch skipped'); }
        }

        // Sort products by updating URL param
        function sortProducts(value) {
            const url = new URL(window.location.href);
            url.searchParams.set('sort', value);
            window.location.href = url.toString();
        }

        // Add to cart function
        async function addToCart(productId) {
            try {
                const res = await fetch('api/cart/add-to-cart.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ product_id: productId, quantity: 1 })
                });
                const data = await res.json();

                if (data.success) {
                    document.getElementById('cart-num').textContent = data.new_count;
                    // Optional: show a toast instead of alert
                    alert('✅ Product added to cart!');
                } else if (data.redirect) {
                    // Not logged in, redirect to login
                    window.location.href = 'login.html';
                } else {
                    alert('❌ ' + (data.message || 'Failed to add to cart.'));
                }
            } catch (error) {
                console.error('Cart error:', error);
                alert('Network error. Please try again.');
            }
        }

        // Run on page load
        document.addEventListener('DOMContentLoaded', initPage);
    </script>
</body>
</html>