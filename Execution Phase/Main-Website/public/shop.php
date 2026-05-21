<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once '../config/database.php';
require_once '../config/session.php';

$shopId = isset($_GET['shop_id']) ? (int)$_GET['shop_id'] : 0;
if (!$shopId) {
    header('Location: index.html');
    exit;
}

$typeFilter = isset($_GET['type']) ? strtoupper(trim($_GET['type'])) : '';
$userId = getUserId();
$isLoggedIn = isLoggedIn();
$cartCount = 0;
$wishlistCount = 0;
$userFirstname = '';

if ($userId) {
    $cs = oci_parse($conn,
        "SELECT (SELECT SUM(ci.quantity) FROM CART c JOIN CART_ITEM ci ON c.cart_id = ci.cart_id WHERE c.user_id = :uid) AS cart_qty,
                (SELECT COUNT(*) FROM WISHLIST WHERE user_id = :uid) AS wl_count FROM DUAL"
    );
    oci_bind_by_name($cs, ':uid', $userId);
    oci_execute($cs);
    $cr = oci_fetch_assoc($cs);
    $cartCount = (int)($cr['CART_QTY'] ?? 0);
    $wishlistCount = (int)($cr['WL_COUNT'] ?? 0);
    oci_free_statement($cs);
    $userFirstname = $_SESSION['firstname'] ?? '';
}

// ── Shop + Trader info ──────────────────────────────────────────────────────
$sql = "
    SELECT s.shop_id, s.name AS shop_name, s.description AS shop_description,
           s.location, s.contact_number, s.shop_type,
           NVL(s.collection_wed,1) AS collection_wed,
           NVL(s.collection_thu,1) AS collection_thu,
           NVL(s.collection_fri,1) AS collection_fri,
           s.shop_logo, s.mimetype, s.filename,
           u.firstname AS trader_firstname, u.lastname AS trader_lastname,
           u.email AS trader_email, u.phone_number AS trader_phone,
           t.status AS trader_status,
           (SELECT COUNT(*) FROM PRODUCT p WHERE p.shop_id = s.shop_id AND p.status = 'Active') AS product_count,
           (SELECT ROUND(AVG(r.rating),1) FROM REVIEW r JOIN PRODUCT p ON r.product_id = p.product_id WHERE p.shop_id = s.shop_id) AS avg_rating
    FROM SHOP s
    JOIN HUDDER_USER u ON s.user_id = u.user_id
    JOIN TRADER t ON t.user_id = u.user_id
    WHERE s.shop_id = :sid AND ROWNUM = 1
";
$stmt = oci_parse($conn, $sql);
oci_bind_by_name($stmt, ':sid', $shopId);
oci_execute($stmt);
$row = oci_fetch_assoc($stmt);
oci_free_statement($stmt);

if (!$row) {
    oci_close($conn);
    header('Location: index.html');
    exit;
}

$shopName        = $row['SHOP_NAME'];
$shopDesc        = $row['SHOP_DESCRIPTION'] ?? '';
$shopType        = $row['SHOP_TYPE'] ?? '';
$location        = $row['LOCATION'] ?? '';
$contactNumber   = $row['CONTACT_NUMBER'] ?? '';
$traderName      = trim(($row['TRADER_FIRSTNAME'] ?? '') . ' ' . ($row['TRADER_LASTNAME'] ?? ''));
$traderStatus    = $row['TRADER_STATUS'] ?? '';
$productCount    = (int)($row['PRODUCT_COUNT'] ?? 0);
$avgRating       = (float)($row['AVG_RATING'] ?? 0);
$logoRaw         = $row['SHOP_LOGO'] ?? '';
$logoMime        = $row['MIMETYPE'] ?? 'image/png';
$logoDataB64     = base64_encode($logoRaw);
$collWed         = (int)($row['COLLECTION_WED'] ?? 1);
$collThu         = (int)($row['COLLECTION_THU'] ?? 1);
$collFri         = (int)($row['COLLECTION_FRI'] ?? 1);

// ── Product images indexed by product_id ─────────────────────────────────────
$imgStmt = oci_parse($conn,
    "SELECT product_id, image_url FROM PRODUCT_IMAGE
     WHERE product_id IN (SELECT product_id FROM PRODUCT WHERE shop_id = :sid AND status = 'Active')
       AND display_order = 0"
);
oci_bind_by_name($imgStmt, ':sid', $shopId);
oci_execute($imgStmt);
$imgMap = [];
while ($ir = oci_fetch_assoc($imgStmt)) {
    $imgMap[(int)$ir['PRODUCT_ID']] = $ir['IMAGE_URL'];
}
oci_free_statement($imgStmt);

// ── Pagination for products ──────────────────────────────────────────────────
$perPage = 12;
$page    = max(1, (int)($_GET['page'] ?? 1));

$where = "WHERE p.shop_id = :sid AND p.status = 'Active'";
$params = [':sid' => $shopId];

if ($typeFilter) {
    if ($typeFilter === 'VEG') {
        $where .= " AND INSTR(UPPER(p.dietary_tags), 'VEGETARIAN') > 0";
    } elseif ($typeFilter === 'NON-VEG') {
        $where .= " AND (p.dietary_tags IS NULL OR (INSTR(UPPER(p.dietary_tags), 'VEGETARIAN') = 0 AND INSTR(UPPER(p.dietary_tags), 'VEGAN') = 0))";
    } elseif ($typeFilter === 'VEGAN') {
        $where .= " AND INSTR(UPPER(p.dietary_tags), 'VEGAN') > 0";
    } elseif ($typeFilter === 'GLUTEN-FREE') {
        $where .= " AND INSTR(UPPER(p.dietary_tags), 'GLUTEN FREE') > 0";
    }
}

$countRowStmt = oci_parse($conn, "SELECT COUNT(*) AS cnt FROM PRODUCT p $where");
foreach ($params as $k => $v) { oci_bind_by_name($countRowStmt, $k, $params[$k]); }
oci_execute($countRowStmt);
$totalProducts = (int)(oci_fetch_assoc($countRowStmt)['CNT'] ?? 0);
oci_free_statement($countRowStmt);

$offset = ($page - 1) * $perPage;
$totalPages = max(1, (int)ceil($totalProducts / $perPage));
if ($page > $totalPages) $page = $totalPages;

$pStmt = oci_parse($conn, "
    SELECT p.product_id, p.name, p.description, p.price, p.stock, p.unit,
           p.min_order, p.max_order, p.allergen_info, p.category_id,
           c.category_name, NVL(d.dp,0) AS discount_percent,
           NVL(r.avg_rating, 0) AS avg_rating, NVL(r.review_count, 0) AS review_count
    FROM PRODUCT p
    LEFT JOIN PRODUCT_CATEGORY c  ON p.category_id = c.category_id
    LEFT JOIN (SELECT product_id, MAX(discount_percent) dp
               FROM DISCOUNT WHERE valid_until >= TRUNC(SYSDATE) GROUP BY product_id) d
           ON d.product_id = p.product_id
    LEFT JOIN (SELECT product_id, ROUND(AVG(rating), 1) AS avg_rating, COUNT(*) AS review_count
               FROM REVIEW GROUP BY product_id) r
           ON r.product_id = p.product_id
    $where
    ORDER BY p.product_id DESC
    OFFSET :off ROWS FETCH NEXT :lim ROWS ONLY
");
foreach ($params as $k => $v) { oci_bind_by_name($pStmt, $k, $params[$k]); }
oci_bind_by_name($pStmt, ':off', $offset);
oci_bind_by_name($pStmt, ':lim', $perPage);
oci_execute($pStmt);

$products = [];
while ($pr = oci_fetch_assoc($pStmt)) {
    $pid = (int)$pr['PRODUCT_ID'];
    $products[] = [
        'PRODUCT_ID' => $pid,
        'NAME' => $pr['NAME'],
        'DESCRIPTION' => $pr['DESCRIPTION'],
        'PRICE' => (float)$pr['PRICE'],
        'STOCK' => (int)$pr['STOCK'],
        'UNIT' => $pr['UNIT'],
        'CATEGORY_NAME' => $pr['CATEGORY_NAME'],
        'DISCOUNT_PERCENT' => (float)($pr['DISCOUNT_PERCENT'] ?? 0),
        'AVG_RATING' => (float)($pr['AVG_RATING'] ?? 0),
        'REVIEW_COUNT' => (int)($pr['REVIEW_COUNT'] ?? 0),
        'IMAGE_URL' => $imgMap[$pid] ?? ''
    ];
}
oci_free_statement($pStmt);
oci_close($conn);

// ── Star rating HTML ──────────────────────────────────────────────────────────
function buildStars($rating) {
    $n = (int)round($rating);
    $out = '';
    for ($i = 1; $i <= 5; $i++) {
        $out .= '<span class="material-icons-outlined">' . ($i <= $n ? 'star' : 'star_border') . '</span>';
    }
    return $out;
}

// ── Generates product card HTML ───────────────────────────────────────────────
function productCardImgSrc($name, $imgUrl) {
    if ($imgUrl) return htmlspecialchars($imgUrl);
    $n = strtolower($name);
    if (str_contains($n,'meat') || str_contains($n,'steak') || str_contains($n,'beef'))  return 'assets/other-images/fresh-raw-meat-cuts-with-rosemary-spices-dark-slate.jpg';
    if (str_contains($n,'salmon') || str_contains($n,'fish')) return 'assets/other-images/top-view-fresh-fish-slices-dark-table-seafood-ocean-meat-sea-meal-dish-food-salad-water-pepper.jpg';
    if (str_contains($n,'cheese') || str_contains($n,'brie'))    return 'assets/other-images/imgi_47_cheese-wood_573717-86.jpg';
    if (str_contains($n,'bread') || str_contains($n,'croissant'))return 'assets/other-images/imgi_57_sweet-bun-with-chocolate-syrup-peeled-orange_114579-2623.jpg';
    if (str_contains($n,'spinach') || str_contains($n,'carrot'))  return 'assets/Item-image/imgi_46_chinese-broccoli-vegetables_1203-6831.jpg';
    if (str_contains($n,'pepper') || str_contains($n,'tomato'))   return 'assets/Item-image/green-bell-pepper-isolated.jpg';
    return 'assets/Item-image/green-bell-pepper-isolated.jpg';
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

function buildShopUrl($overrides = []) {
    global $shopId, $typeFilter, $page;
    $params = ['shop_id' => $shopId];
    if ($typeFilter) $params['type'] = $typeFilter;
    if ($page > 1) $params['page'] = $page;
    $params = array_merge($params, $overrides);
    if (isset($overrides['type']) && $overrides['type'] === '') unset($params['type']);
    if (isset($overrides['page']) && $overrides['page'] <= 1) unset($params['page']);
    return 'shop.php?' . http_build_query($params);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo htmlspecialchars($shopName); ?> | HuddersHub</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Google+Sans+Flex:ital,wght@0,400;0,500;0,600;0,700;1,400;1,500;1,600;1,700&display=swap" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Google+Sans:ital,opsz,wght@0,17..18,400..700;1,17..18,400..700&family=Plus+Jakarta+Sans:ital,wght@0,200..800;1,200..800&display=swap" rel="stylesheet">
<link href="https://fonts.googleapis.com/icon?family=Material+Icons+Outlined" rel="stylesheet">
<style>
:root{
    --primary-orange:#ff5e3a;--primary-orange-light:#ff8c70;--primary-orange-dark:#e3472c;
    --primary-green:#0f260b;--primary-green-light:rgba(15,38,11,.14);--primary-green-dark:#0b1c08;
    --bg-white:#fff;--bg-light:#f7f6f3;--bg-gray:#f2f4f1;
    --border-light:#dce3da;--text-black:#0b140a;--text-dark-gray:#1e2a1c;--text-muted:#5e6a63;
    --badge-bg:#0f260b;--badge-text:#fff;
    --shadow-sm:0 2px 6px rgba(15,38,11,.08);--shadow-md:0 10px 24px rgba(15,38,11,.12);--shadow-lg:0 18px 36px rgba(15,38,11,.16);
    --transition:all .25s cubic-bezier(.4,0,.2,1);
}
*{box-sizing:border-box;margin:0;padding:0;font-family:'Plus Jakarta Sans',sans-serif}
html{scroll-behavior:smooth}
body{background:linear-gradient(180deg,#f7f6f3 0%,#fff 35%,#f7f6f3 100%);color:#1b2419;line-height:1.6}
a{text-decoration:none;color:inherit;transition:var(--transition)}
button{font-family:inherit;transition:var(--transition);cursor:pointer}

/* ── Header (inherits from collection/payment style) ── */
header{position:fixed;top:0;left:0;right:0;z-index:1000;background:linear-gradient(135deg,#f8faf7 0%,#fff 100%);backdrop-filter:blur(12px)}
header.scrolled{box-shadow:var(--shadow-md);background:#fff}
.top-bar{background:rgba(255,255,255,.98);border-bottom:1px solid var(--border-light);padding:14px 0;transition:var(--transition)}
header.scrolled .top-bar{padding:10px 0}
header.scrolled .brand img{width:42px;height:42px}
header.scrolled .brand-text{font-size:30px}
.page-wrap{width:min(1200px,94%);margin:0 auto}
.top-bar-inner{display:grid;grid-template-columns:auto 1fr auto;gap:18px;align-items:center}
.brand{display:flex;align-items:center;gap:14px;white-space:nowrap}
.brand img{width:56px;height:56px;object-fit:contain;transition:var(--transition);filter:drop-shadow(0 6px 12px rgba(15,38,11,.12))}
.brand-text{font-family:'Google Sans Flex',sans-serif;font-weight:700;font-style:italic;font-size:36px;letter-spacing:.6px;color:#0f260b}
.search-wrap{display:flex;align-items:center;gap:12px}
.search-bar{position:relative;flex:1;min-width:280px}
.search-bar input{width:100%;padding:6px 44px 6px 14px;height:36px;border:1px solid #c8d1c6;background:#fff;font-size:14px;font-weight:500;color:var(--text-black);outline:none;transition:var(--transition)}
.search-bar input:focus{border-color:var(--primary-orange);box-shadow:0 0 0 3px rgba(255,94,58,.22)}
.search-bar input::placeholder{color:#1b2419;opacity:.55}
.search-icon{position:absolute;right:12px;top:50%;transform:translateY(-50%);font-size:18px;color:#1b2419;opacity:.55;border:none;background:transparent;padding:0;cursor:pointer}
.actions{display:flex;align-items:center;gap:16px;white-space:nowrap}
.action-btn,.user-menu{display:inline-flex;align-items:center;gap:6px;font-size:14px;font-weight:600;color:var(--text-muted);cursor:pointer;padding:8px 12px}
.action-btn:hover,.user-menu:hover{background:rgba(15,38,11,.06);color:var(--primary-green)}
.icon-with-badge{position:relative;display:inline-flex;align-items:center;justify-content:center;cursor:pointer;color:var(--text-black);padding:6px;background:transparent;border:1px solid transparent;outline:none}
.icon-with-badge:hover{background:rgba(15,38,11,.06);color:var(--primary-green)}
.icon-with-badge .material-icons-outlined{font-size:24px}
.badge{position:absolute;top:0;right:0;background:var(--badge-bg);color:var(--badge-text);padding:2px 5px;font-size:10px;font-weight:600;line-height:1;min-width:16px;text-align:center}
.user-menu-wrap{position:relative}
.user-dropdown{position:absolute;top:calc(100% + 8px);right:0;background:#fff;border:1px solid var(--border-light);box-shadow:var(--shadow-md);min-width:180px;z-index:2000;display:none;flex-direction:column;padding:6px 0}
.user-dropdown.open{display:flex}
.dropdown-user-item{display:flex;align-items:center;gap:10px;padding:10px 16px;font-size:14px;font-weight:500;color:var(--text-dark-gray);background:none;border:none;width:100%;cursor:pointer;font-style:normal;transition:var(--transition)}
.dropdown-user-item:hover{background:var(--primary-green-light);color:var(--primary-green)}
.dropdown-user-item .material-icons-outlined{font-size:18px}
.dropdown-user-divider{height:1px;background:var(--border-light);margin:4px 0}
.dropdown-logout{color:#dc2626}
.dropdown-logout:hover{background:#fef2f2;color:#dc2626}
.nav-bar{background:#f1f3f0;border-bottom:1px solid var(--border-light);padding:10px 0;transition:var(--transition)}
header.scrolled .nav-bar{padding:8px 0;background:#f3f4f6}
.nav-list{display:flex;align-items:center;gap:8px;flex-wrap:wrap;font-size:14px}
.nav-item{display:inline-flex;align-items:center;gap:6px;padding:8px 14px;border:none;cursor:pointer;text-decoration:none;color:var(--text-black);transition:var(--transition);font-size:15px;font-weight:400;font-family:'Google Sans Flex',sans-serif}
.nav-item.primary{color:#0f260b;font-weight:600;font-size:14px}
.nav-item.is-active{position:relative}
.nav-item.is-active::after{content:"";position:absolute;left:0;right:0;bottom:-6px;height:2px;background:#0f260b}
.nav-separator{width:1px;height:24px;background:var(--border-light);margin:0 6px;display:inline-block}

/* ── Page body ── */
body{padding-top:150px;min-height:100vh}

/* ── Shop hero ── */
.shop-hero{background:linear-gradient(135deg,#0f260b 0%,#1c3c17 60%,#2d5a1e 100%);color:#fff;padding:48px 0 36px;margin-bottom:0}
.shop-hero-inner{width:min(1200px,94%);margin:0 auto;display:flex;gap:28px;align-items:center}
.shop-logo-wrap{width:100px;height:100px;border-radius:50%;background:rgba(255,255,255,.12);display:flex;align-items:center;justify-content:center;flex-shrink:0;overflow:hidden;border:3px solid rgba(255,255,255,.3)}
.shop-logo-wrap img{width:100%;height:100%;object-fit:cover;border-radius:50%}
.shop-logo-wrap .material-icons-outlined{font-size:48px;color:rgba(255,255,255,.7)}
.shop-hero-info{flex:1}
.badge-shop{display:inline-block;background:var(--primary-orange);color:#fff;padding:4px 12px;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.8px;border-radius:12px;margin-bottom:10px}
.shop-hero-info h1{font-family:'Google Sans Flex',sans-serif;font-size:36px;font-weight:800;color:#fff;margin-bottom:8px}
.shop-hero-info .shop-desc{font-size:15px;color:rgba(255,255,255,.8);max-width:620px;margin-bottom:12px}
.shop-meta{display:flex;gap:20px;flex-wrap:wrap}
.shop-meta-item{display:flex;align-items:center;gap:6px;font-size:14px;font-weight:500;color:rgba(255,255,255,.9)}
.shop-meta-item .material-icons-outlined{font-size:16px;color:var(--primary-orange-light)}
.trader-badge{margin-top:14px;display:inline-flex;align-items:center;gap:8px;background:rgba(255,255,255,.12);border:1px solid rgba(255,255,255,.2);padding:8px 16px;border-radius:20px;font-size:14px;font-weight:600;color:rgba(255,255,255,.95)}
.trader-badge .material-icons-outlined{font-size:16px;color:#caed95}

/* ── Info bar ── */
.shop-info-bar{background:#fff;border-bottom:1px solid var(--border-light);padding:16px 0;position:sticky;top:110px;z-index:90}
.shop-info-inner{width:min(1200px,94%);margin:0 auto;display:flex;align-items:center;justify-content:space-between;gap:16px;flex-wrap:wrap}
.info-item{display:flex;align-items:center;gap:8px;font-size:14px;font-weight:500;color:var(--text-dark-gray)}
.info-item .material-icons-outlined{font-size:18px;color:var(--primary-green)}
.info-divider{width:1px;height:24px;background:var(--border-light)}
.product-count-badge{display:inline-flex;align-items:center;gap:6px;background:var(--primary-green);color:#caed95;padding:6px 14px;font-size:13px;font-weight:700;border-radius:12px}

/* ── Products grid ── */
.products-section{padding:40px 0 80px}
.products-section-inner{width:min(1200px,94%);margin:0 auto}
.section-head{display:flex;align-items:baseline;justify-content:space-between;margin-bottom:24px;border-bottom:2px solid #0f260b;padding-bottom:12px}
.section-head h2{font-family:'Google Sans Flex',sans-serif;font-size:22px;font-weight:700;color:#0f260b}
.section-head span{font-size:14px;color:var(--text-muted)}

.product-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:20px}
@media(max-width:1100px){.product-grid{grid-template-columns:repeat(3,1fr)}}
@media(max-width:800px){.product-grid{grid-template-columns:repeat(2,1fr)}}
@media(max-width:500px){.product-grid{grid-template-columns:1fr}}

.product-card{background:#fff;border:1px solid var(--border-light);border-radius:8px;overflow:hidden;transition:var(--transition);cursor:pointer;display:flex;flex-direction:column}
.product-card:hover{transform:translateY(-4px);box-shadow:var(--shadow-lg);border-color:rgba(255,94,58,.35)}
.product-card-out-of-stock{opacity:.6}
.product-img{position:relative;height:200px;background:var(--bg-gray);display:flex;align-items:center;justify-content:center;overflow:hidden}
.product-img img{width:100%;height:100%;object-fit:cover;transition:transform .35s ease}
.product-card:hover .product-img img{transform:scale(1.06)}
.badge-stock{position:absolute;top:10px;left:10px;padding:3px 10px;font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.4px;z-index:2}
.badge-stock.out{background:#9ca3af;color:#fff}
.badge-stock.active{background:var(--primary-green);color:#caed95}
.badge-stock.discount{background:var(--primary-orange);color:#fff}
.badge-fav{position:absolute;top:10px;right:10px;width:34px;height:34px;border-radius:50%;background:rgba(255,255,255,.92);border:none;display:flex;align-items:center;justify-content:center;cursor:pointer;z-index:2;box-shadow:0 2px 6px rgba(0,0,0,.12);transition:background .2s,transform .2s}
.badge-fav:hover{background:#fff;transform:scale(1.12)}
.badge-fav.active .material-icons-outlined{color:var(--primary-orange)}
.badge-fav .material-icons-outlined{font-size:16px;color:#999}
.product-info{padding:14px;display:flex;flex-direction:column;gap:6px;flex:1}
.product-cat{font-size:11px;font-weight:600;color:var(--primary-green);text-transform:uppercase;letter-spacing:.4px}
.product-name{font-size:15px;font-weight:700;color:#0f260b;line-height:1.3;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden}
.product-rating{display:flex;align-items:center;gap:4px;font-size:12px;color:#f4b740}
.product-rating .material-icons-outlined{font-size:13px}
.product-rating span{color:var(--text-muted);font-size:11px;margin-left:2px}
.product-price-row{display:flex;align-items:center;gap:8px;flex-wrap:wrap}
.price-now{font-size:20px;font-weight:800;color:var(--primary-orange)}
.price-old{font-size:13px;color:#9ca3af;text-decoration:line-through;font-weight:400}
.price-unit{font-size:12px;color:var(--text-muted)}
.discount-badge{font-size:11px;font-weight:700;color:var(--primary-orange);background:#fff0ec;padding:2px 7px;border-radius:0}
.product-stock{font-size:12px;color:var(--text-muted)}
.product-stock.low{color:#c47f00}
.product-stock.none{color:#dc3545}
.product-stock.ok{color:#1a7a37}
.add-to-cart{width:100%;padding:10px;background:var(--primary-green);color:#fff;border:none;border-radius:0;font-size:13px;font-weight:700;display:flex;align-items:center;justify-content:center;gap:7px;letter-spacing:.3px;margin-top:auto;transition:background .22s,transform .18s}
.add-to-cart:hover:not(:disabled){background:#1c3c17;transform:translateY(-1px)}
.add-to-cart:disabled{background:#ccc;cursor:not-allowed;transform:none}

/* ── Empty state ── */
.empty-state{text-align:center;padding:80px 20px}
.empty-state .material-icons-outlined{font-size:64px;color:var(--text-muted);margin-bottom:16px}
.empty-state h3{font-size:20px;font-weight:700;margin-bottom:8px}
.empty-state p{color:var(--text-muted)}

/* ── Pagination ── */
.pagination{display:flex;align-items:center;justify-content:center;gap:8px;margin-top:48px}
.pagination a,.pagination span{display:inline-flex;align-items:center;justify-content:center;min-width:40px;height:40px;padding:0 12px;border:1px solid var(--border-light);background:#fff;font-size:14px;font-weight:600;color:var(--text-dark-gray);transition:.15s}
.pagination a:hover{border-color:var(--primary-orange);color:var(--primary-orange);background:#fff8f6}
.pagination .current{background:var(--primary-green);color:#fff;border-color:var(--primary-green)}
.pagination .disabled{opacity:.4;pointer-events:none}

/* ── Footer ── */
.site-footer{background:#0b0f0b;color:#fff;padding:48px 0 24px;margin-top:60px}
.footer-grid{display:grid;grid-template-columns:2fr 1fr 1fr 1.2fr;gap:32px;margin-bottom:32px}
.footer-brand .brand-row{display:flex;align-items:center;gap:10px;margin-bottom:12px}
.footer-brand .brand-row img{width:36px;height:36px;object-fit:contain}
.brand-name{font-weight:700;font-style:italic;font-size:24px;font-family:'Google Sans Flex',sans-serif}
.footer-tagline{font-size:14px;color:rgba(255,255,255,.6);margin-bottom:6px}
.footer-slogan{font-size:13px;font-weight:700;letter-spacing:.8px;text-transform:uppercase;color:#caed95;margin-bottom:16px}
.footer-col h4{font-size:14px;font-weight:700;color:#fff;margin-bottom:16px;text-transform:uppercase;letter-spacing:.5px}
.footer-col a,.footer-col p{display:block;font-size:14px;color:rgba(255,255,255,.6);margin-bottom:10px}
.footer-col a:hover{color:var(--primary-orange);padding-left:4px}
.footer-col p{display:flex;align-items:center;gap:6px}
.footer-bottom{display:flex;align-items:center;justify-content:space-between;padding-top:24px;border-top:1px solid rgba(255,255,255,.1);font-size:13px;color:rgba(255,255,255,.5)}
.footer-bottom-links{display:flex;gap:24px}
.footer-bottom-links a{color:rgba(255,255,255,.5)}
.footer-bottom-links a:hover{color:#fff}

/* ── Cart toast ── */
.cart-toast{position:fixed;bottom:24px;right:24px;background:var(--primary-green);color:#fff;display:flex;align-items:center;gap:10px;padding:14px 20px;font-size:14px;font-weight:600;box-shadow:0 12px 30px rgba(15,38,11,.3);transform:translateY(80px);opacity:0;transition:all .35s cubic-bezier(.4,0,.2,1);z-index:9999;pointer-events:none}
.cart-toast.show{transform:translateY(0);opacity:1}
.cart-toast .material-icons-outlined{font-size:18px;color:#a5d6a7}

@media(max-width:768px){
    .shop-hero-inner{flex-direction:column;text-align:center}
    .shop-meta{justify-content:center}
    .shop-info-inner{flex-direction:column;gap:10px;text-align:center}
    .footer-grid{grid-template-columns:1fr 1fr}
}
@media(max-width:480px){
    .product-grid{grid-template-columns:1fr}
    .footer-grid{grid-template-columns:1fr}
}
</style>
</head>
<body>
<header id="mainHeader">
  <div class="top-bar">
    <div class="top-bar-inner">
      <div class="brand">
        <a href="index.html" style="display:flex;align-items:center;gap:12px;text-decoration:none;color:inherit">
          <img src="assets/logo.png" alt="HuddersHub">
          <span class="brand-text">HuddersHub</span>
        </a>
      </div>
      <div class="search-wrap">
        <form class="search-bar" action="search.php" method="get">
          <input type="text" name="q" placeholder="Search…" aria-label="Search">
          <button type="submit" class="search-icon" aria-label="Search" style="background:none; border:none; cursor:pointer; color:inherit; padding:0;"><span class="material-icons-outlined">search</span></button>
        </form>
      </div>
      <div class="actions">
        <div class="user-menu-wrap">
          <?php if ($isLoggedIn): ?>
          <div class="user-dropdown-wrap" id="userDropdownWrap">
            <button class="action-btn user-menu" id="userDropdownBtn" style="border:none;background:none">
              <span class="material-icons-outlined" style="font-size:24px">person</span>
              <span>Hi, <?php echo htmlspecialchars($userFirstname); ?></span>
              <span class="material-icons-outlined" style="font-size:16px">expand_more</span>
            </button>
            <div class="user-dropdown" id="userDropdown">
              <a href="customer/profile.html" class="dropdown-user-item"><span class="material-icons-outlined">manage_accounts</span> My Profile</a>
              <a href="customer/orders.html" class="dropdown-user-item"><span class="material-icons-outlined">receipt_long</span> My Orders</a>
              <a href="register-trader.html" class="dropdown-user-item"><span class="material-icons-outlined">storefront</span> Apply to be Trader</a>
              <div class="dropdown-user-divider"></div>
              <a href="logout.html" class="dropdown-user-item dropdown-logout"><span class="material-icons-outlined">logout</span> Log out</a>
            </div>
          </div>
          <?php else: ?>
          <a class="action-btn user-menu" href="login.html">
            <span class="material-icons-outlined" style="font-size:24px">person</span>
            <span>Login / Signup</span>
          </a>
          <?php endif; ?>
        </div>
        <a class="icon-with-badge" href="cart.html" aria-label="Cart" id="cartTrigger">
          <span class="material-icons-outlined">shopping_cart</span>
          <span class="badge" id="cartCount"><?php echo $cartCount; ?></span>
        </a>
        <a class="icon-with-badge" href="customer/wishlist.html" aria-label="Wishlist">
          <span class="material-icons-outlined">favorite_border</span>
          <span class="badge" id="wishlistCount"><?php echo $wishlistCount; ?></span>
        </a>
      </div>
    </div>
  </div>
  <nav class="nav-bar">
    <div class="page-wrap">
      <div class="nav-list">
        <a href="index.html" class="nav-item primary"><span class="material-icons-outlined" style="font-size:18px">home</span> Home</a>
        <span class="nav-separator"></span>
        <a href="traders.php" class="nav-item"><span class="material-icons-outlined" style="font-size:18px">storefront</span> Traders</a>
        <span class="nav-separator"></span>
        <a href="category.php?shop=<?php echo strtolower(urlencode($shopType ?: '')); ?>" class="nav-item"><span class="material-icons-outlined" style="font-size:18px">storefront</span> <?php echo htmlspecialchars($shopName); ?></a>
        <span class="nav-separator"></span>
        <span class="nav-item">Collection</span>
      </div>
    </div>
  </nav>
</header>

<!-- ════════════════════════════════════════════════════════════════
     SHOP HERO
════════════════════════════════════════════════════════════════════ -->
<section class="shop-hero">
  <div class="shop-hero-inner">
    <div class="shop-logo-wrap">
      <?php if (!empty($logoRaw) && strlen($logoRaw) > 20): ?>
        <img src="data:<?php echo htmlspecialchars($logoMime); ?>;base64,<?php echo $logoDataB64; ?>" alt="<?php echo htmlspecialchars($shopName); ?>">
      <?php else: ?>
        <span class="material-icons-outlined">storefront</span>
      <?php endif; ?>
    </div>
    <div class="shop-hero-info">
      <span class="badge-shop"><?php echo htmlspecialchars($shopType ?: 'Local Shop'); ?></span>
      <h1><?php echo htmlspecialchars($shopName); ?></h1>
      <p class="shop-desc"><?php echo htmlspecialchars($shopDesc ?: 'Fresh and local products from Huddersfield.'); ?></p>
      <div class="shop-meta">
        <?php if ($location): ?>
        <span class="shop-meta-item"><span class="material-icons-outlined">location_on</span><?php echo htmlspecialchars($location); ?></span>
        <?php endif; ?>
        <?php if ($totalProducts): ?>
        <span class="shop-meta-item"><span class="material-icons-outlined">inventory_2</span><?php echo $totalProducts; ?> product<?php echo $totalProducts>1?'s':''; ?></span>
        <?php endif; ?>
        <?php if ($avgRating > 0): ?>
        <span class="shop-meta-item"><?php echo buildStars($avgRating); ?><span style="font-size:13px;margin-left:4px"><?php echo number_format($avgRating,1); ?></span></span>
        <?php endif; ?>
      </div>
      <?php if (!empty($traderName)): ?>
      <div class="trader-badge"><span class="material-icons-outlined">check_circle</span> Trader: <?php echo htmlspecialchars($traderName); ?></div>
      <?php endif; ?>
    </div>
  </div>
</section>

<!-- collection days strip -->
<?php if ($collWed || $collThu || $collFri): ?>
<div style="background:#f0f4f0;border-bottom:1px solid var(--border-light);padding:10px 0">
  <div class="page-wrap" style="display:flex;align-items:center;gap:14px;font-size:13px;font-weight:500;flex-wrap:wrap">
    <span style="color:var(--text-muted)">Collection:</span>
    <?php if ($collWed): ?><span style="display:flex;align-items:center;gap:4px;color:var(--primary-green)"><span class="material-icons-outlined" style="font-size:15px">check_circle</span>Wednesday</span><?php endif; ?>
    <?php if ($collThu): ?><span style="display:flex;align-items:center;gap:4px;color:var(--primary-green)"><span class="material-icons-outlined" style="font-size:15px">check_circle</span>Thursday</span><?php endif; ?>
    <?php if ($collFri): ?><span style="display:flex;align-items:center;gap:4px;color:var(--primary-green)"><span class="material-icons-outlined" style="font-size:15px">check_circle</span>Friday</span><?php endif; ?>
    <a href="collection.html" style="margin-left:auto;color:var(--primary-orange);font-weight:700">Book a slot →</a>
  </div>
</div>
<?php endif; ?>

<!-- ════════════════════════════════════════════════════════════════
     SHOP INFO BAR
════════════════════════════════════════════════════════════════════ -->
<div class="shop-info-bar">
  <div class="shop-info-inner">
    <div style="display:flex;align-items:center;gap:20px;flex-wrap:wrap">
      <?php if ($location): ?>
      <div class="info-item"><span class="material-icons-outlined">location_on</span> <?php echo htmlspecialchars($location); ?></div>
      <div class="info-divider"></div>
      <?php endif; ?>
      <?php if ($contactNumber): ?>
      <div class="info-item"><span class="material-icons-outlined">call</span> <?php echo htmlspecialchars($contactNumber); ?></div>
      <div class="info-divider"></div>
      <?php endif; ?>
      <div class="info-item"><span class="material-icons-outlined">verified</span> Status: <?php echo htmlspecialchars($traderStatus); ?></div>
    </div>
    <span class="product-count-badge"><span class="material-icons-outlined" style="font-size:16px">inventory_2</span> <?php echo $totalProducts; ?> product<?php echo $totalProducts>1?'s':''; ?></span>
  </div>
</div>

<!-- ════════════════════════════════════════════════════════════════
     PRODUCTS SECTION
════════════════════════════════════════════════════════════════════ -->
<main class="products-section">
  <div class="products-section-inner">
    <div class="section-head">
      <h2>Products</h2>
      <span>Showing <?php echo $offset+1; ?>–<?php echo min($offset+$perPage, $totalProducts); ?> of <?php echo $totalProducts; ?></span>
    </div>

    <div style="margin-bottom: 24px; display: flex; gap: 10px; flex-wrap: wrap;">
        <a href="<?php echo buildShopUrl(['type' => '']); ?>" style="padding: 6px 14px; border-radius: 20px; font-size: 13px; font-weight: 600; border: 1px solid <?php echo !$typeFilter ? 'var(--primary-green)' : 'var(--border-light)'; ?>; background: <?php echo !$typeFilter ? 'var(--primary-green)' : 'transparent'; ?>; color: <?php echo !$typeFilter ? 'white' : 'var(--text-dark-gray)'; ?>;">All Types</a>
        <a href="<?php echo buildShopUrl(['type' => 'veg']); ?>" style="padding: 6px 14px; border-radius: 20px; font-size: 13px; font-weight: 600; border: 1px solid <?php echo $typeFilter === 'VEG' ? 'var(--primary-green)' : 'var(--border-light)'; ?>; background: <?php echo $typeFilter === 'VEG' ? 'var(--primary-green)' : 'transparent'; ?>; color: <?php echo $typeFilter === 'VEG' ? 'white' : 'var(--text-dark-gray)'; ?>;">Vegetarian</a>
        <a href="<?php echo buildShopUrl(['type' => 'non-veg']); ?>" style="padding: 6px 14px; border-radius: 20px; font-size: 13px; font-weight: 600; border: 1px solid <?php echo $typeFilter === 'NON-VEG' ? 'var(--primary-green)' : 'var(--border-light)'; ?>; background: <?php echo $typeFilter === 'NON-VEG' ? 'var(--primary-green)' : 'transparent'; ?>; color: <?php echo $typeFilter === 'NON-VEG' ? 'white' : 'var(--text-dark-gray)'; ?>;">Non-Vegetarian</a>
        <a href="<?php echo buildShopUrl(['type' => 'vegan']); ?>" style="padding: 6px 14px; border-radius: 20px; font-size: 13px; font-weight: 600; border: 1px solid <?php echo $typeFilter === 'VEGAN' ? 'var(--primary-green)' : 'var(--border-light)'; ?>; background: <?php echo $typeFilter === 'VEGAN' ? 'var(--primary-green)' : 'transparent'; ?>; color: <?php echo $typeFilter === 'VEGAN' ? 'white' : 'var(--text-dark-gray)'; ?>;">Vegan</a>
        <a href="<?php echo buildShopUrl(['type' => 'gluten-free']); ?>" style="padding: 6px 14px; border-radius: 20px; font-size: 13px; font-weight: 600; border: 1px solid <?php echo $typeFilter === 'GLUTEN-FREE' ? 'var(--primary-green)' : 'var(--border-light)'; ?>; background: <?php echo $typeFilter === 'GLUTEN-FREE' ? 'var(--primary-green)' : 'transparent'; ?>; color: <?php echo $typeFilter === 'GLUTEN-FREE' ? 'white' : 'var(--text-dark-gray)'; ?>;">Gluten Free</a>
    </div>

    <?php if (empty($products)): ?>
    <div class="empty-state">
      <span class="material-icons-outlined">inventory_2</span>
      <h3>No products yet</h3>
      <p>This shop hasn't added any products yet. Check back soon!</p>
    </div>
    <?php else: ?>
    <div class="product-grid">
    <?php foreach ($products as $p): ?>
      <?php
        $pid   = (int)$p['PRODUCT_ID'];
        $name  = $p['NAME'];
        $price = (float)$p['PRICE'];
        $stock = (int)$p['STOCK'];
        $disc  = (float)($p['DISCOUNT_PERCENT'] ?? 0);
        $cats  = htmlspecialchars($p['CATEGORY_NAME'] ?? '');
        $imgs  = productCardImgSrc($name, $p['IMAGE_URL']);
        $out   = $stock === 0;
      ?>
      <div class="product-card <?php echo $out ? 'product-card-out-of-stock' : ''; ?>" data-product-id="<?php echo $pid; ?>">
        <div class="product-img">
          <img src="<?php echo $imgs; ?>" alt="<?php echo htmlspecialchars($name); ?>" loading="lazy" onerror="this.src='assets/Item-image/green-bell-pepper-isolated.jpg'">
          <?php if ($stock === 0): ?>
            <span class="badge-stock out">Out of stock</span>
          <?php elseif ($disc > 0): ?>
            <span class="badge-stock discount">−<?php echo (int)round($disc); ?>%</span>
          <?php else: ?>
            <span class="badge-stock active">In stock</span>
          <?php endif; ?>
          <button class="badge-fav" type="button" data-product-id="<?php echo $pid; ?>" onclick="toggleFav(<?php echo $pid; ?>, this)" aria-label="Add to wishlist">
            <span class="material-icons-outlined">favorite_border</span>
          </button>
        </div>
        <div class="product-info">
          <?php if ($cats): ?><span class="product-cat"><?php echo $cats; ?></span><?php endif; ?>
          <div class="product-name"><?php echo htmlspecialchars($name); ?></div>
          <div class="product-rating">
            <?php echo renderStars($p['AVG_RATING']); ?>
            <span>(<?php echo $p['REVIEW_COUNT']; ?>)</span>
          </div>
          <div class="product-price-row">
            <?php if ($disc > 0): ?>
            <span class="price-now">£<?php echo number_format($price*(1-$disc/100),2); ?></span>
            <span class="price-old">£<?php echo number_format($price,2); ?></span>
            <span class="discount-badge">−<?php echo (int)round($disc); ?>%</span>
            <?php else: ?>
            <span class="price-now">£<?php echo number_format($price,2); ?></span>
            <?php endif; ?>
            <?php if ($p['UNIT']): ?><span class="price-unit">/ <?php echo htmlspecialchars($p['UNIT']); ?></span><?php endif; ?>
          </div>
          <div class="product-stock <?php echo $stock===0?'none':($stock<10?'low':'ok'); ?>"><?php echo $stock===0 ? 'Out of stock' : ($stock<10 ? "Only $stock left" : "$stock in stock"); ?></div>
          <button class="add-to-cart" onclick="addToCart(<?php echo $pid; ?>)" <?php echo $out?'disabled':''; ?>>
            <span class="material-icons-outlined">shopping_cart</span>
            <?php echo $out ? 'Out of stock' : 'Add to cart'; ?>
          </button>
        </div>
      </div>
    <?php endforeach; ?>
    </div>

    <!-- Pagination -->
    <?php if ($totalPages > 1): ?>
    <div class="pagination">
      <a href="<?php echo buildShopUrl(['page' => $page-1]); ?>" class="page-btn <?php echo $page<=1?'disabled':''; ?>">← Prev</a>
      <?php for($i=1;$i<=$totalPages;$i++): ?>
        <a href="<?php echo buildShopUrl(['page' => $i]); ?>" class="page-btn <?php echo $i==$page?'current':''; ?>"><?php echo $i; ?></a>
      <?php endfor; ?>
      <a href="<?php echo buildShopUrl(['page' => $page+1]); ?>" class="page-btn <?php echo $page>=$totalPages?'disabled':''; ?>">Next →</a>
    </div>
    <?php endif; ?>

    <?php endif; ?>
  </div>
</main>

<!-- ════════════════════════════════════════════════════════════════
     FOOTER
════════════════════════════════════════════════════════════════════ -->
<footer class="site-footer">
  <div class="page-wrap" style="display:grid;grid-template-columns:2fr 1fr 1fr 1.2fr;gap:32px;padding-bottom:24px;">
    <div>
      <div style="display:flex;align-items:center;gap:10px;margin-bottom:12px">
        <img src="assets/logo.png" alt="HuddersHub" style="width:36px;height:36px;object-fit:contain">
        <span class="brand-name">HuddersHub</span>
      </div>
      <p class="footer-tagline">Local food, trusted traders, and fresh picks curated for Huddersfield.</p>
      <p class="footer-slogan">Eat Fresh. Buy Local.</p>
      <div style="display:flex;gap:12px;margin-top:16px">
        <a href="#" style="width:36px;height:36px;background:rgba(255,255,255,.1);border-radius:50%;display:flex;align-items:center;justify-content:center;color:#fff"><span class="material-icons-outlined" style="font-size:18px">facebook</span></a>
        <a href="#" style="width:36px;height:36px;background:rgba(255,255,255,.1);border-radius:50%;display:flex;align-items:center;justify-content:center;color:#fff"><span class="material-icons-outlined" style="font-size:18px">camera_alt</span></a>
      </div>
    </div>
    <div>
      <h4>Shop</h4>
      <a href="shop.php?shop=butcher">Butcher</a>
      <a href="shop.php?shop=greengrocer">Greengrocer</a>
      <a href="shop.php?shop=fishmonger">Fishmonger</a>
      <a href="shop.php?shop=bakery">Bakery</a>
      <a href="shop.php?shop=delicatessen">Delicatessen</a>
    </div>
    <div>
      <h4>Company</h4>
      <a href="about.html">About HuddersHub</a>
      <a href="register-trader.html">Become a Trader</a>
      <a href="faq.html">Help Center</a>
      <a href="refund.html">Returns &amp; Refunds</a>
    </div>
    <div>
      <h4>Contact</h4>
      <p><span class="material-icons-outlined" style="font-size:16px">location_on</span> Huddersfield, UK</p>
      <p><span class="material-icons-outlined" style="font-size:16px">mail</span> support@huddershub.test</p>
      <p><span class="material-icons-outlined" style="font-size:16px">phone</span> +44 1484 000 000</p>
    </div>
  </div>
  <div class="page-wrap" style="display:flex;align-items:center;justify-content:space-between;border-top:1px solid rgba(255,255,255,.1);padding-top:24px;font-size:13px;color:rgba(255,255,255,.5)">
    <span>© 2026 HuddersHub. All rights reserved.</span>
    <div style="display:flex;gap:24px">
      <a href="privacy.html">Privacy Policy</a>
      <a href="terms.html">Terms of Service</a>
      <a href="register-trader.html">Apply as a trader</a>
    </div>
  </div>
</footer>

<!-- ════════════════════════════════════════════════════════════════
     SCRIPTS
════════════════════════════════════════════════════════════════════ -->
<div class="cart-toast" id="cartToast"><span class="material-icons-outlined">check_circle</span><span id="toastMsg">Added to cart</span></div>

<script>
document.getElementById('mainHeader')?.addEventListener('scroll', function(){ document.getElementById('mainHeader').classList.toggle('scrolled', window.scrollY>50)},{passive:true});
document.getElementById('userDropdownBtn')?.addEventListener('click', function(){ document.getElementById('userDropdown').classList.toggle('open'); });
document.addEventListener('click', function(e){ if (!e.target.closest('.user-dropdown-wrap')) document.getElementById('userDropdown')?.classList.remove('open'); });

function showToast(msg) {
  const t = document.getElementById('cartToast');
  document.getElementById('toastMsg').textContent = msg;
  t.classList.add('show');
  setTimeout(()=>t.classList.remove('show'), 2800);
}

async function addToCart(productId) {
  try {
    const res = await fetch('api/cart/add-to-cart.php', { method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify({product_id:productId, quantity:1}) });
    const data = await res.json();
    if (data.success) {
      const el = document.getElementById('cartCount');
      if (el) el.textContent = data.new_count;
      showToast('Added to cart');
    } else if (data.redirect) {
      window.location.href = 'login.html';
    } else {
      if (data.error && data.error.includes("20 products")) {
        alert("Only 20 items are allowed per order. Please check your cart and remove items before adding more.");
      } else {
        alert(data.error || data.message || 'Failed to add to cart');
      }
    }
  } catch(e) { alert('Network error'); }
}

async function toggleFav(pid, btn) {
  try {
    btn.classList.toggle('active');
    const res = await fetch('api/customer/add-to-wishlist.php', { method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify({product_id:pid}) });
    const data = await res.json();
    if (data.success) {
      const wl = document.getElementById('wishlistCount');
      if (wl) wl.textContent = data.count ?? '';
      btn.querySelector('.material-icons-outlined').textContent = btn.classList.contains('active') ? 'favorite' : 'favorite_border';
      showToast(btn.classList.contains('active') ? 'Added to wishlist' : 'Removed from wishlist');
    } else {
      btn.classList.toggle('active');
      if (data.redirect) { window.location.href='login.html'; }
    }
  } catch(e) { btn.classList.toggle('active'); alert('Network error'); }
}
</script>
</body>
</html>
