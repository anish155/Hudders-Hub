<?php
require_once '../config/database.php';
require_once '../config/session.php';

$userId = getUserId();
$isLoggedIn = isLoggedIn();
$cartCount = 0;
$wishlistCount = 0;

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
}

// Fetch all active shops
$sql = "
    SELECT s.shop_id, s.name AS shop_name, s.description AS shop_description,
           s.location, s.shop_type, s.shop_logo, s.mimetype,
           (SELECT COUNT(*) FROM PRODUCT p WHERE p.shop_id = s.shop_id AND p.status = 'Active') AS product_count,
           (SELECT ROUND(AVG(r.rating), 1) FROM REVIEW r JOIN PRODUCT p ON r.product_id = p.product_id WHERE p.shop_id = s.shop_id) AS avg_rating
    FROM SHOP s
    JOIN TRADER t ON s.user_id = t.user_id
    WHERE t.status = 'Active'
    ORDER BY s.name ASC
";
$stmt = oci_parse($conn, $sql);
oci_execute($stmt);
$shops = [];
while ($row = oci_fetch_assoc($stmt)) {
    $shops[] = $row;
}
oci_free_statement($stmt);
oci_close($conn);

function buildStars($rating) {
    $n = (int)round($rating);
    $out = '';
    for ($i = 1; $i <= 5; $i++) {
        $out .= '<span class="material-icons-outlined">' . ($i <= $n ? 'star' : 'star_border') . '</span>';
    }
    return $out;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Our Traders | HuddersHub</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Google+Sans+Flex:ital,wght@0,400;0,500;0,600;0,700;1,400;1,500;1,600;1,700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:ital,wght@0,200..800;1,200..800&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons+Outlined" rel="stylesheet">
    <style>
        :root {
            --primary-orange: #ff5e3a;
            --primary-green: #0f260b;
            --bg-light: #f7f6f3;
            --border-light: #dce3da;
            --text-black: #0b140a;
            --text-muted: #5e6a63;
            --shadow-md: 0 10px 24px rgba(15, 38, 11, 0.12);
            --transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
        }
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Plus Jakarta Sans', sans-serif; }
        body { background: var(--bg-light); color: var(--text-black); line-height: 1.6; padding-top: 140px; }
        a { text-decoration: none; color: inherit; transition: var(--transition); }
        
        /* HEADER */
        header { position: fixed; top: 0; left: 0; right: 0; z-index: 1000; background: #fff; border-bottom: 1px solid var(--border-light); }
        .top-bar { padding: 14px 0; }
        .page-wrap { width: min(1200px, 94%); margin: 0 auto; }
        .top-bar-inner { display: grid; grid-template-columns: auto 1fr auto; gap: 18px; align-items: center; }
        .brand { display: flex; align-items: center; gap: 14px; }
        .brand img { width: 56px; height: 56px; object-fit: contain; }
        .brand-text { font-family: 'Google Sans Flex', sans-serif; font-weight: 700; font-style: italic; font-size: 36px; color: #0f260b; }
        .actions { display: flex; align-items: center; gap: 16px; }
        .action-btn { display: flex; align-items: center; gap: 6px; font-size: 14px; font-weight: 600; padding: 8px 12px; }
        .icon-with-badge { position: relative; padding: 6px; }
        .badge { position: absolute; top: 0; right: 0; background: var(--primary-green); color: #fff; padding: 2px 5px; font-size: 10px; border-radius: 10px; }

        /* HERO */
        .traders-hero { background: var(--primary-green); color: #fff; padding: 60px 0; text-align: center; margin-bottom: 40px; }
        .traders-hero h1 { font-family: 'Google Sans Flex', sans-serif; font-size: 42px; font-weight: 800; margin-bottom: 12px; }
        .traders-hero p { font-size: 18px; opacity: 0.8; max-width: 600px; margin: 0 auto; }

        /* GRID */
        .traders-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(350px, 1fr)); gap: 30px; padding-bottom: 80px; }
        .trader-card { background: #fff; border: 1px solid var(--border-light); border-radius: 16px; overflow: hidden; transition: var(--transition); display: flex; flex-direction: column; }
        .trader-card:hover { transform: translateY(-6px); box-shadow: var(--shadow-md); border-color: var(--primary-orange); }
        
        .trader-header { height: 120px; background: linear-gradient(135deg, #f8faf7 0%, #e8ede7 100%); position: relative; }
        .trader-logo { position: absolute; bottom: -30px; left: 24px; width: 80px; height: 80px; border-radius: 50%; background: #fff; border: 4px solid #fff; box-shadow: 0 4px 12px rgba(0,0,0,0.1); overflow: hidden; display: flex; align-items: center; justify-content: center; }
        .trader-logo img { width: 100%; height: 100%; object-fit: cover; }
        .trader-logo .material-icons-outlined { font-size: 40px; color: var(--text-muted); }
        
        .trader-body { padding: 45px 24px 24px; flex: 1; display: flex; flex-direction: column; }
        .trader-type { display: inline-block; background: var(--primary-green); color: #caed95; padding: 4px 12px; font-size: 11px; font-weight: 700; text-transform: uppercase; border-radius: 12px; margin-bottom: 12px; align-self: flex-start; }
        .trader-card h2 { font-size: 24px; font-weight: 700; margin-bottom: 8px; color: var(--primary-green); }
        .trader-desc { font-size: 14px; color: var(--text-muted); margin-bottom: 16px; flex: 1; }
        
        .trader-footer { display: flex; align-items: center; justify-content: space-between; padding-top: 16px; border-top: 1px solid var(--bg-light); }
        .trader-stat { display: flex; align-items: center; gap: 6px; font-size: 13px; font-weight: 600; }
        .trader-stat .material-icons-outlined { font-size: 18px; color: var(--primary-orange); }
        
        .view-shop-btn { background: var(--primary-green); color: #fff; padding: 10px 20px; border-radius: 8px; font-weight: 700; font-size: 14px; }
        .view-shop-btn:hover { background: #1c3c17; transform: scale(1.05); }

        @media (max-width: 600px) {
            .traders-grid { grid-template-columns: 1fr; }
            .traders-hero h1 { font-size: 32px; }
        }
    </style>
</head>
<body>
    <header>
        <div class="top-bar">
            <div class="page-wrap top-bar-inner">
                <div class="brand">
                    <a href="index.html">
                        <img src="assets/logo.png" alt="HuddersHub">
                        <span class="brand-text">HuddersHub</span>
                    </a>
                </div>
                <div class="actions">
                    <a class="icon-with-badge" href="cart.html">
                        <span class="material-icons-outlined">shopping_cart</span>
                        <span class="badge"><?php echo $cartCount; ?></span>
                    </a>
                    <a class="icon-with-badge" href="customer/wishlist.html">
                        <span class="material-icons-outlined">favorite_border</span>
                        <span class="badge"><?php echo $wishlistCount; ?></span>
                    </a>
                </div>
            </div>
        </div>
    </header>

    <section class="traders-hero">
        <div class="page-wrap">
            <h1>Meet Our Local Traders</h1>
            <p>Support the heart of Huddersfield by shopping directly from our local independent businesses.</p>
        </div>
    </section>

    <main class="page-wrap">
        <div class="traders-grid">
            <?php foreach ($shops as $s): ?>
                <div class="trader-card">
                    <div class="trader-header"></div>
                    <div class="trader-logo">
                        <?php if ($s['SHOP_LOGO']): ?>
                            <img src="data:<?php echo $s['MIMETYPE']; ?>;base64,<?php echo base64_encode($s['SHOP_LOGO']); ?>" alt="<?php echo htmlspecialchars($s['SHOP_NAME']); ?>">
                        <?php else: ?>
                            <span class="material-icons-outlined">storefront</span>
                        <?php endif; ?>
                    </div>
                    <div class="trader-body">
                        <span class="trader-type"><?php echo htmlspecialchars($s['SHOP_TYPE']); ?></span>
                        <h2><?php echo htmlspecialchars($s['SHOP_NAME']); ?></h2>
                        <p class="trader-desc"><?php echo htmlspecialchars($s['SHOP_DESCRIPTION'] ?: 'Fresh and local products from Huddersfield.'); ?></p>
                        <div class="trader-footer">
                            <div style="display:flex; flex-direction:column; gap:4px;">
                                <div class="trader-stat">
                                    <span class="material-icons-outlined">inventory_2</span>
                                    <span><?php echo $s['PRODUCT_COUNT']; ?> Products</span>
                                </div>
                                <?php if ($s['AVG_RATING']): ?>
                                <div class="trader-stat">
                                    <span class="material-icons-outlined">star</span>
                                    <span><?php echo $s['AVG_RATING']; ?> / 5.0</span>
                                </div>
                                <?php endif; ?>
                            </div>
                            <a href="shop.php?shop_id=<?php echo $s['SHOP_ID']; ?>" class="view-shop-btn">View Shop</a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </main>

    <footer style="background:#0b0f0b; color:#fff; padding:40px 0; text-align:center;">
        <div class="page-wrap">
            <p>© 2026 HuddersHub. Supporting Huddersfield's local economy.</p>
        </div>
    </footer>
</body>
</html>