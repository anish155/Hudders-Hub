<?php
session_start();

// AUTH GUARD (disabled for testing)
// Only traders can access this page. Redirect everyone else.
// if (!isset($_SESSION['user']) || $_SESSION['user']['type'] !== 'trader') {
//     header('Location: login.php?type=trader');
//     exit;
// }

// HANDLE PROFILE IMAGE UPLOAD
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['avatar'])) {
    $file    = $_FILES['avatar'];
    $allowed = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    $maxSize = 2 * 1024 * 1024; // 2 MB

    if ($file['error'] === UPLOAD_ERR_OK
        && in_array($file['type'], $allowed)
        && $file['size'] <= $maxSize
    ) {
        $ext      = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = 'trader_' . $_SESSION['user']['email'] . '_' . time() . '.' . $ext;
        $filename = preg_replace('/[^a-zA-Z0-9._-]/', '_', $filename);
        $dest     = __DIR__ . '/../uploads/avatars/' . $filename;

        if (move_uploaded_file($file['tmp_name'], $dest)) {
            $_SESSION['user']['avatar'] = $filename;
            $upload_success = true;
        } else {
            $upload_error = 'Failed to save image. Check folder permissions.';
        }
    } else {
        $upload_error = $file['error'] !== UPLOAD_ERR_OK
            ? 'Upload error (code ' . $file['error'] . ').'
            : 'Invalid file. Use JPEG/PNG/GIF/WEBP under 2 MB.';
    }
}

// HANDLE PROFILE SAVE
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_profile'])) {
    // Update session with submitted values (replace with DB UPDATE in production)
    $fields = ['name','shop','shop_type','description','address','phone','email'];
    foreach ($fields as $f) {
        if (isset($_POST[$f])) {
            $_SESSION['user'][$f] = trim($_POST[$f]);
        }
    }
    // Collection days
    $_SESSION['user']['collection_days'] = $_POST['collection_days'] ?? [];
    // Notifications
    $_SESSION['user']['notifications'] = $_POST['notifications'] ?? [];
    $save_success = true;
}

// PULL TRADER DATA FROM SESSION
$u = $_SESSION['user'];
$trader = [
    'name'        => $u['name']        ?? 'Trader',
    'shop'        => $u['shop']        ?? 'My Shop',
    'type'        => $u['shop_type']   ?? 'Butcher',
    'status'      => $u['status']      ?? 'Active',
    'description' => $u['description'] ?? '',
    'address'     => $u['address']     ?? '',
    'email'       => $u['email']       ?? '',
    'phone'       => $u['phone']       ?? '',
    'avatar'      => $u['avatar']      ?? null,
    'collection_days' => $u['collection_days'] ?? ['Wednesday','Thursday','Friday'],
    'notifications'   => $u['notifications']   ?? ['new_order','daily_report','weekly_finance','monthly_report'],
];

$avatarUrl = $trader['avatar']
    ? '../uploads/avatars/' . htmlspecialchars($trader['avatar'])
    : null;

// SUBPAGE
$page = isset($_GET['page']) ? $_GET['page'] : 'dashboard';
$allowed_pages = ['dashboard', 'products', 'orders', 'finance', 'reports', 'profile'];
if (!in_array($page, $allowed_pages)) $page = 'dashboard';
?>

<?php include 'nav-bar.php'; ?>

<style>
/* TRADER DASHBOARD STYLES (aligned with homepage) */
:root {
    --primary-orange: #FF5E3A;
    --primary-orange-light: #FF8A6A;
    --primary-orange-dark: #E84A2A;
    --primary-green: #0F260B;
    --primary-green-light: rgba(15, 38, 11, 0.12);
    --primary-green-dark: #0B1C08;
    --bg-white: #FFFFFF;
    --bg-light: #FAF9F6;
    --bg-gray: #F5F5F5;
    --border-light: #E5E7EB;
    --text-black: #111111;
    --text-dark-gray: #222222;
    --text-medium-gray: #6B7280;
    --shadow-sm: 0 2px 8px rgba(0, 0, 0, 0.08);
    --shadow-md: 0 4px 16px rgba(0, 0, 0, 0.1);
    --shadow-lg: 0 8px 32px rgba(0, 0, 0, 0.12);
    --transition-smooth: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
    --radius-sm: 6px;
    --radius-md: 10px;
    --nav-width: 240px;
}

* { box-sizing: border-box; margin: 0; padding: 0; }
body {
    font-family: 'Plus Jakarta Sans', sans-serif;
    background: linear-gradient(180deg, #f5f7fb 0%, var(--bg-white) 100%);
    color: var(--text-black);
    min-height: 100vh;
}

/* ── LAYOUT ── */
.page-wrapper {
    padding: 36px 24px 60px;
}
.page-heading {
    font-size: 36px;
    font-weight: 700;
    text-align: center;
    margin-bottom: 24px;
    color: var(--text-dark-gray);
    letter-spacing: -0.4px;
}
.dashboard-grid {
    display: grid;
    grid-template-columns: var(--nav-width) 1fr;
    gap: 0;
    background: var(--bg-white);
    border: 1px solid var(--border-light);
    border-radius: var(--radius-md);
    box-shadow: var(--shadow-md);
    overflow: hidden;
    min-height: 600px;
}

/* ── SIDEBAR ── */
.sidebar {
    background: var(--bg-light);
    border-right: 1px solid var(--border-light);
}
.sidebar-profile {
    border-bottom: 1px solid var(--border-light);
    padding: 20px 16px;
    text-align: center;
}

/* Avatar with upload overlay */
.avatar-wrap { 
    position: relative; 
    width: 64px; 
    height: 64px; 
    margin: 0 auto 10px; 
    cursor: pointer; 
}
.avatar-wrap img, .avatar-wrap .avatar-placeholder {
    width: 64px;
    height: 64px;
    border-radius: 50%;
    border: 2px solid var(--border-light);
    object-fit: cover;
    display: block;
    background: var(--bg-white);
}
.avatar-placeholder { 
    display: flex; 
    align-items: center; 
    justify-content: center; 
}
.avatar-overlay {
    position: absolute;
    inset: 0;
    border-radius: 50%;
    background: rgba(17, 17, 17, 0.5);
    display: flex;
    align-items: center;
    justify-content: center;
    opacity: 0;
    transition: opacity 0.2s;
    font-size: 9px;
    color: #fff;
    text-align: center;
    line-height: 1.3;
    letter-spacing: 0.3px;
}
.avatar-wrap:hover .avatar-overlay { 
    opacity: 1; 
}
.avatar-upload-input { 
    display: none; 
}

.trader-name {
    font-size: 16px;
    font-weight: 700;
    color: var(--text-dark-gray);
}
.trader-status {
    font-size: 13px;
    color: var(--text-medium-gray);
    margin-top: 4px;
}

.sidebar-nav a {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 12px 18px;
    border-bottom: 1px solid var(--border-light);
    font-size: 14px;
    text-decoration: none;
    color: var(--text-dark-gray);
    transition: var(--transition-smooth);
}
.sidebar-nav a:hover {
    background: var(--primary-green-light);
}
.sidebar-nav a.active {
    background: var(--primary-green);
    color: #ffffff;
}
.sidebar-nav a.active svg path, .sidebar-nav a.active svg rect,
.sidebar-nav a.active svg line, .sidebar-nav a.active svg polyline {
    stroke: #ffffff;
}
.sidebar-nav a svg { 
    width: 16px; 
    height: 16px; 
    flex-shrink: 0; 
}
.sidebar-nav .logout-link {
    color: var(--text-dark-gray);
}

/* ── MAIN PANE ── */
.main-pane { padding: 0; }
.main-pane-inner { padding: 28px 32px; }
.welcome-header { 
    text-align: center; 
    margin-bottom: 24px; 
}
.welcome-header h2 {
    font-size: 20px;
    font-weight: 700;
    color: var(--text-dark-gray);
}
.welcome-header p {
    font-size: 14px;
    color: var(--text-medium-gray);
    margin-top: 4px;
}

.section-title {
    display: flex;
    align-items: center;
    gap: 12px;
    font-size: 18px;
    font-weight: 700;
    margin: 22px 0 14px;
    color: var(--text-dark-gray);
}
.section-title::before, .section-title::after {
    content: '';
    flex: 1;
    height: 1px;
    background: var(--border-light);
}

/* ── QUICK STATS ── */
.quick-stats {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    border: 1px solid var(--border-light);
    border-radius: var(--radius-md);
    background: var(--bg-white);
    box-shadow: var(--shadow-sm);
    overflow: hidden;
}
.stat-cell {
    padding: 18px 14px 14px;
    border-right: 1px solid var(--border-light);
    text-align: center;
}
.stat-cell:last-child { 
    border-right: none; 
}
.stat-value {
    font-size: 34px;
    font-weight: 700;
    line-height: 1;
    color: var(--text-dark-gray);
}
.stat-label {
    font-size: 13px;
    color: var(--text-medium-gray);
    margin-top: 6px;
}
.stat-prefix {
    font-size: 16px;
    vertical-align: top;
    line-height: 1.6;
    color: var(--primary-orange);
}

/* ── ORDER CARDS ── */
.order-card {
    border: 1px solid var(--border-light);
    border-radius: var(--radius-sm);
    margin-bottom: 12px;
    padding: 12px 16px;
    background: var(--bg-white);
    box-shadow: var(--shadow-sm);
}
.order-card-header {
    display: flex;
    justify-content: space-between;
    font-size: 13px;
    margin-bottom: 10px;
    color: var(--text-medium-gray);
}
.order-item-row {
    display: flex;
    justify-content: space-between;
    font-size: 14px;
    padding: 2px 0;
}
.order-item-price {
    font-size: 14px;
    font-weight: 700;
    color: var(--primary-orange);
}

/* ── TABLES ── */
.finance-meta {
    font-size: 13px;
    text-align: center;
    color: var(--text-medium-gray);
    margin-bottom: 10px;
}
.generate-btn {
    color: var(--primary-orange);
    text-decoration: underline;
    cursor: pointer;
    margin-left: 6px;
}
.data-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 13px;
}
.data-table th {
    border: 1px solid var(--border-light);
    padding: 8px 10px;
    text-align: center;
    font-weight: 700;
    font-size: 13px;
    background: var(--bg-gray);
    color: var(--text-dark-gray);
}
.data-table td {
    border: 1px solid var(--border-light);
    padding: 7px 10px;
    text-align: center;
}
.data-table tr.total-row td {
    border-top: 2px solid var(--border-light);
    font-weight: 700;
}
.table-actions {
    font-size: 13px;
    text-align: center;
    margin-top: 8px;
}
.table-actions a {
    color: var(--primary-green);
    text-decoration: none;
    margin: 0 8px;
    border-bottom: 1px solid var(--primary-green);
}

/* ── CHART ── */
.chart-wrap { 
    padding: 16px 0 0; 
}
canvas.line-chart { 
    width: 100%; 
    height: 220px; 
    display: block; 
}

/* ── FINANCE TABS ── */
.finance-tabs {
    display: flex;
    border-bottom: 1px solid var(--border-light);
    margin-bottom: 20px;
    background: var(--bg-gray);
}
.finance-tab {
    font-size: 13px;
    padding: 10px 18px;
    cursor: pointer;
    border-right: 1px solid var(--border-light);
    background: none;
    border-top: none;
    border-left: none;
    color: var(--text-dark-gray);
}
.finance-tab.active {
    background: var(--primary-green);
    color: #ffffff;
}
.week-nav {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    font-size: 13px;
    margin-bottom: 10px;
    color: var(--text-medium-gray);
}
.week-nav button {
    background: none;
    border: none;
    cursor: pointer;
    font-size: 14px;
    color: var(--text-dark-gray);
}
.only-collected {
    font-size: 12px;
    color: var(--text-medium-gray);
    text-align: center;
    margin-bottom: 8px;
    font-style: italic;
}

/* ── REPORT ── */
.date-row {
    font-size: 13px;
    text-align: center;
    color: var(--text-medium-gray);
    margin-bottom: 14px;
}
.slot-header {
    font-size: 12px;
    color: var(--text-medium-gray);
    margin: 16px 0 6px;
    letter-spacing: 0.3px;
}
.label-card {
    border: 1px solid var(--border-light);
    padding: 14px 18px;
    font-size: 13px;
    line-height: 1.8;
    display: inline-block;
    min-width: 260px;
    background: var(--bg-white);
    box-shadow: var(--shadow-sm);
    border-radius: var(--radius-sm);
}

/* ── FORMS (products / profile) ── */
.form-section-title {
    font-size: 14px;
    font-weight: 700;
    text-align: center;
    border-bottom: 1px solid var(--border-light);
    border-top: 1px solid var(--border-light);
    padding: 8px 0;
    margin: 18px 0 14px;
    color: var(--text-dark-gray);
}
.form-row { 
    margin-bottom: 14px; 
}
.form-row label {
    display: block;
    font-size: 13px;
    font-weight: 600;
    margin-bottom: 6px;
    color: var(--text-dark-gray);
}
.form-row input[type="text"],
.form-row input[type="number"],
.form-row input[type="email"],
.form-row input[type="tel"],
.form-row input[type="password"],
.form-row textarea,
.form-row select {
    width: 100%;
    border: 1px solid var(--border-light);
    background: var(--bg-white);
    padding: 9px 12px;
    font-size: 13px;
    color: var(--text-dark-gray);
    outline: none;
    resize: vertical;
    border-radius: var(--radius-sm);
    transition: var(--transition-smooth);
}
.form-row input:focus,
.form-row textarea:focus,
.form-row select:focus {
    border-color: var(--primary-orange);
    box-shadow: 0 0 0 2px rgba(255, 111, 60, 0.15);
}
.form-row textarea { 
    min-height: 90px; 
}
.form-row input::placeholder, .form-row textarea::placeholder {
    color: var(--text-medium-gray);
}
.char-count {
    font-size: 12px;
    color: var(--text-medium-gray);
    text-align: right;
    margin-top: 4px;
}
.product-id-bar {
    display: flex;
    justify-content: space-between;
    font-size: 12px;
    color: var(--text-medium-gray);
    margin-bottom: 14px;
}
.image-upload-row { 
    display: flex; 
    gap: 10px; 
    flex-wrap: wrap; 
}
.img-upload-box {
    width: 90px;
    height: 90px;
    border: 1px dashed var(--border-light);
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    font-size: 12px;
    color: var(--text-medium-gray);
    transition: var(--transition-smooth);
    background: var(--bg-white);
    border-radius: var(--radius-sm);
}
.img-upload-box:hover {
    background: var(--bg-gray);
}
.form-actions { 
    text-align: right; 
    margin-top: 20px; 
    display: flex; 
    justify-content: flex-end; 
    gap: 10px; 
}
.btn {
    font-size: 13px;
    padding: 10px 22px;
    border: 1px solid transparent;
    cursor: pointer;
    text-transform: uppercase;
    border-radius: var(--radius-sm);
    transition: var(--transition-smooth);
    letter-spacing: 0.4px;
}
.btn-primary {
    background: var(--primary-orange);
    color: #ffffff;
}
.btn-secondary {
    background: var(--primary-green);
    color: #ffffff;
}
.btn:hover { opacity: 0.9; }
.back-link {
    font-size: 12px;
    color: var(--text-medium-gray);
    text-decoration: none;
    border-bottom: 1px solid var(--border-light);
    margin-right: 16px;
}
.allergy-warning {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 13px;
    margin-bottom: 8px;
    color: var(--text-dark-gray);
}

/* ── PROFILE ── */
.shop-logo-upload {
    border: 1px solid var(--border-light);
    width: 90px;
    height: 80px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 12px;
    color: var(--text-medium-gray);
    text-align: center;
    cursor: pointer;
    overflow: hidden;
    border-radius: var(--radius-sm);
    background: var(--bg-white);
}
.shop-logo-upload img { 
    width: 100%; 
    height: 100%; 
    object-fit: cover; 
}
.change-link {
    font-size: 12px;
    color: var(--primary-orange);
    text-decoration: underline;
    cursor: pointer;
}
.profile-logo-row { 
    display: flex; 
    justify-content: space-between; 
    align-items: flex-start; 
    margin-bottom: 10px; 
}
.checkbox-group label {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 13px;
    margin-bottom: 6px;
    cursor: pointer;
}
.checkbox-group input[type="checkbox"] {
    width: 14px;
    height: 14px;
    accent-color: var(--primary-green);
}
.password-field-wrap { 
    position: relative; 
}
.password-field-wrap input { 
    padding-right: 36px; 
}
.eye-toggle {
    position: absolute;
    right: 10px;
    top: 50%;
    transform: translateY(-50%);
    cursor: pointer;
    color: var(--text-medium-gray);
    background: none;
    border: none;
    font-size: 14px;
}
.delete-btn {
    background: none;
    border: 1px solid var(--border-light);
    color: var(--text-medium-gray);
    font-size: 12px;
    padding: 10px 22px;
    cursor: pointer;
    letter-spacing: 0.4px;
    text-transform: uppercase;
    border-radius: var(--radius-sm);
}
.delete-btn:hover {
    border-color: #cc0000;
    color: #cc0000;
}

/* ── ALERTS ── */
.alert {
    font-size: 13px;
    padding: 10px 14px;
    margin-bottom: 14px;
    border: 1px solid;
    border-radius: var(--radius-sm);
}
.alert-success { 
    border-color: #2d6a4f; 
    color: #2d6a4f; 
    background: #d8f3dc; 
}
.alert-error   { 
    border-color: #9d0208; 
    color: #9d0208; 
    background: #ffd7d7; 
}

@media (max-width: 768px) {
    .dashboard-grid { 
        grid-template-columns: 1fr; 
    }
    .sidebar {
        border-right: none;
        border-bottom: 1px solid var(--border-light);
    }
    .quick-stats { 
        grid-template-columns: repeat(2, 1fr); 
    }
}


#quickie,
#to-or {
    transform: translateX(0);
}

</style>

<div class="page-wrapper">
    <h1 class="page-heading">
        <?php
        $titles = [
            'dashboard' => 'Dashboard',
            'products' => 'Products',
            'orders' => 'Orders',
            'finance' => 'Finance',
            'reports' => 'Report',
            'profile' => 'Profile'
        ];
        echo $titles[$page];
        ?>
    </h1>

    <div class="dashboard-grid">

        <!-- ── SIDEBAR ── -->
        <aside class="sidebar">
            <div class="sidebar-profile">
                <!-- Profile image with upload-on-hover -->
                <form method="POST" action="?page=profile" enctype="multipart/form-data" id="avatarForm">
                    <div class="avatar-wrap" onclick="document.getElementById('avatarInput').click()" title="Click to change photo">
                        <?php if ($avatarUrl): ?>
                            <img src="<?= $avatarUrl ?>" alt="Profile photo">
                        <?php else: ?>
                            <div class="avatar-placeholder">
                                <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                            </div>
                        <?php endif; ?>
                        <div class="avatar-overlay">Change<br>Photo</div>
                    </div>
                    <input type="file" name="avatar" id="avatarInput" class="avatar-upload-input"
                           accept="image/jpeg,image/png,image/gif,image/webp"
                           onchange="this.form.submit()">
                </form>
                <div class="trader-name"><?= htmlspecialchars($trader['name']) ?></div>
                <div class="trader-status">Status: &nbsp;<?= htmlspecialchars($trader['status']) ?></div>
            </div>

            <nav class="sidebar-nav">
                <a href="?page=products" class="<?= $page==='products'?'active':'' ?>">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="2" y="3" width="20" height="14" rx="1"/><path d="M8 21h8M12 17v4"/></svg>
                    Products
                </a>
                <a href="?page=orders" class="<?= $page==='orders'?'active':'' ?>">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M9 5H7a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-2"/><rect x="9" y="3" width="6" height="4" rx="1"/></svg>
                    Orders
                </a>
                <a href="?page=finance" class="<?= $page==='finance'?'active':'' ?>">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="12" r="10"/><path d="M12 8v4l3 3"/></svg>
                    Finance
                </a>
                <a href="?page=reports" class="<?= $page==='reports'?'active':'' ?>">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
                    Reports
                </a>
                <a href="?page=profile" class="<?= $page==='profile'?'active':'' ?>">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="8" r="4"/><path d="M4 20c0-4 3.6-7 8-7s8 3 8 7"/></svg>
                    Profile
                </a>
                <a href="logout.php" class="logout-link">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
                    Logout
                </a>
            </nav>
        </aside>

        <!-- ── MAIN PANE ── -->
        <main class="main-pane">
            <div class="main-pane-inner">
                <div class="welcome-header">
                    <h2>Welcome back, <?= htmlspecialchars(explode(' ', $trader['name'])[0]) ?>!</h2>
                    <p><?= htmlspecialchars($trader['shop']) ?></p>
                </div>

                <?php
                // Show upload feedback only on profile page
                if ($page === 'profile' && isset($upload_success)): ?>
                <div class="alert alert-success">Profile photo updated successfully.</div>
                <?php endif; ?>
                <?php if ($page === 'profile' && isset($upload_error)): ?>
                <div class="alert alert-error"><?= htmlspecialchars($upload_error) ?></div>
                <?php endif; ?>
                <?php if ($page === 'profile' && isset($save_success)): ?>
                <div class="alert alert-success">Profile saved successfully.</div>
                <?php endif; ?>

                <!-- ════════════════════ DASHBOARD ════════════════════ -->
                <?php if ($page === 'dashboard'): ?>

                <div class="section-title" id="quickie">Quick stats</div>
                <div class="quick-stats">
                    <div class="stat-cell"><div class="stat-value">54</div><div class="stat-label">Total orders</div></div>
                    <div class="stat-cell"><div class="stat-value">08</div><div class="stat-label">Total orders Today</div></div>
                    <div class="stat-cell"><div class="stat-value"><span class="stat-prefix">£</span>1.2k</div><div class="stat-label">This week</div></div>
                    <div class="stat-cell"><div class="stat-value">22</div><div class="stat-label">Total products</div></div>
                </div>

                <div class="section-title" id="to-or">Today's Orders</div>
                <?php
                $sample_orders = [
                    ['id'=>'#112345','status'=>'Pending','slot'=>'Wed 10:00 – 13:00','items'=>[['Chicken','5kg','£1500'],['Steak','x3','£800'],['Mutton','2kg','£1500']]],
                    ['id'=>'#112346','status'=>'Pending','slot'=>'Wed 10:00 – 13:00','items'=>[['Chicken','5kg','£1500'],['Steak','x3','£800'],['Mutton','2kg','£1500']]],
                ];
                foreach ($sample_orders as $o): ?>
                <div class="order-card">
                    <div class="order-card-header">
                        <span><strong>Order <?= $o['id'] ?></strong></span>
                        <span>Status: <?= $o['status'] ?></span>
                        <span><?= $o['slot'] ?></span>
                    </div>
                    <?php foreach ($o['items'] as $item): ?>
                    <div class="order-item-row">
                        <span><?= $item[0] ?></span><span><?= $item[1] ?></span>
                        <span class="order-item-price"><?= $item[2] ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endforeach; ?>

                <!-- ════════════════════ FINANCE ════════════════════ -->
                <?php elseif ($page === 'finance'): ?>

                <div class="finance-tabs">
                    <button class="finance-tab active" onclick="switchTab('monthly',this)">Monthly</button>
                    <button class="finance-tab" onclick="switchTab('weekly',this)">Weekly</button>
                </div>

                <div id="tab-monthly">
                    <div class="section-title">Monthly Finance</div>
                    <div class="finance-meta">May – 2026 <span class="generate-btn">[Generate]</span></div>
                    <table class="data-table">
                        <thead><tr><th>Product (A–Z)</th><th>Orders↑</th><th>Income↑</th></tr></thead>
                        <tbody>
                            <?php foreach ([['Chicken',8,800],['Lamb',12,1200],['Mince',6,600],['Pork',6,600],['Sausages',6,600],['Steak',6,600]] as $r): ?>
                            <tr><td><?= $r[0] ?></td><td><?= $r[1] ?></td><td><?= $r[2] ?></td></tr>
                            <?php endforeach; ?>
                            <tr class="total-row"><td>Total</td><td>44</td><td>4400</td></tr>
                        </tbody>
                    </table>
                    <div class="table-actions"><a href="#">[Print]</a><a href="#">[Download]</a></div>
                    <div class="section-title">Monthly graph</div>
                    <div class="chart-wrap"><canvas id="monthlyChart" class="line-chart"></canvas></div>
                </div>

                <div id="tab-weekly" style="display:none">
                    <div class="section-title">Weekly Finance</div>
                    <div class="week-nav">
                        <button>&lt;</button><span>Week 16</span><button>&gt;</button>
                        <span class="generate-btn" style="color:var(--text-medium-gray);font-size:11px;">[Generate]</span>
                    </div>
                    <p class="only-collected">Only includes collected orders</p>
                    <table class="data-table">
                        <thead><tr><th>Day</th><th>Orders</th><th>Collected</th><th>Earned</th></tr></thead>
                        <tbody>
                            <tr><td>Wed</td><td>8</td><td>8</td><td>800</td></tr>
                            <tr><td>Thu</td><td>12</td><td>12</td><td>1200</td></tr>
                            <tr><td>Fri</td><td>6</td><td>6</td><td>600</td></tr>
                            <tr class="total-row"><td>Total</td><td>26</td><td>26</td><td>2600</td></tr>
                        </tbody>
                    </table>
                    <div class="table-actions"><a href="#">[Print]</a><a href="#">[Download]</a></div>
                    <div class="section-title">Weekly graph</div>
                    <div class="chart-wrap"><canvas id="weeklyChart" class="line-chart"></canvas></div>
                </div>

                <!-- ════════════════════ REPORTS ════════════════════ -->
                <?php elseif ($page === 'reports'): ?>

                <div class="section-title">Daily order report</div>
                <div class="date-row">Date: [02/06/2026] &nbsp;<span class="generate-btn">[Generate]</span></div>
                <?php foreach (['10:00 – 13:00','13:00 – 16:00','16:00 – 19:00'] as $slot): ?>
                <div class="slot-header">SLOT: <?= $slot ?></div>
                <table class="data-table">
                    <thead><tr><th>Product</th><th>Qty</th><th>Customer</th></tr></thead>
                    <tbody>
                        <tr><td>Chicken</td><td>8</td><td>User#112, &nbsp;User#115, &nbsp;User#119</td></tr>
                        <tr><td>Steak</td><td>8</td><td>User#112, &nbsp;User#115, &nbsp;User#119</td></tr>
                    </tbody>
                </table>
                <?php endforeach; ?>
                <div class="section-title">Labels</div>
                <div class="label-card">
                    Customer ID: C001<br>Order ID: #1255<br>Product: Chicken<br>Slot: Wed 10–13
                </div>
                <div class="table-actions" style="margin-top:12px;">
                    <a href="#">[Print Labels]</a><a href="#">[Download]</a>
                </div>

                <!-- ════════════════════ PRODUCTS ════════════════════ -->
                <?php elseif ($page === 'products'): ?>

                <div style="text-align:center;margin-bottom:14px;">
                    <a href="?page=products" class="back-link">[Back to Products]</a>
                    <strong style="font-size:16px;">Add new product</strong>
                </div>
                <div class="product-id-bar"><span>Product ID: #1122</span><span>Trader: <?= htmlspecialchars($trader['type']) ?></span></div>

                <div class="form-row"><label>Product name:</label><input type="text" placeholder="e.g. Fresh Chicken Breast"></div>
                <div class="form-row">
                    <label>Product description:</label>
                    <textarea placeholder="Describe your product…" id="prodDesc" oninput="updateCount(this,'descCount',500)"></textarea>
                    <div class="char-count"><span id="descCount">0</span>/500</div>
                </div>
                <div class="form-row">
                    <label>Category:</label>
                    <select><option value="">Please select one category</option><option>Beef</option><option>Chicken</option><option>Lamb</option><option>Pork</option><option>Sausages</option><option>Other</option></select>
                </div>
                <div class="form-row"><label>Price:</label><input type="text" placeholder="in £"></div>
                <div class="form-row"><label>Quantity per item:</label><input type="text" placeholder="e.g. 500gm or 1kg"></div>
                <div class="form-row"><label>Stock Available:</label><input type="number" value="10"></div>
                <div class="form-row"><label>Minimum Order:</label><input type="number" value="1"></div>
                <div class="form-row"><label>Maximum Order:</label><input type="number" value="10"></div>
                <div class="allergy-warning"><svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#000000"><path d="m40-120 440-760 440 760H40Zm138-80h604L480-720 178-200Zm330.5-51.5Q520-263 520-280t-11.5-28.5Q497-320 480-320t-28.5 11.5Q440-297 440-280t11.5 28.5Q463-240 480-240t28.5-11.5ZM440-360h80v-200h-80v200Zm40-100Z"/></svg> Allergy Information</div>
                <div class="form-row"><label>Contains:</label><input type="text" placeholder="None"></div>
                <div class="form-row"><label>May contain:</label><input type="text" placeholder="Sulphites"></div>
                <div class="form-row">
                    <label>Product images</label>
                    <div class="image-upload-row">
                        <div class="img-upload-box">+Add</div>
                        <div class="img-upload-box">+Add</div>
                        <div class="img-upload-box">+Add</div>
                    </div>
                </div>
                <div class="form-actions">
                    <button class="btn btn-primary">Save</button>
                    <button class="btn btn-secondary" onclick="history.back()">Cancel</button>
                </div>

                <!-- ════════════════════ ORDERS ════════════════════ -->
                <?php elseif ($page === 'orders'): ?>

                <div class="section-title">All Orders</div>
                <?php
                $all_orders = [
                    ['id'=>'#112345','status'=>'Pending','slot'=>'Wed 10:00–13:00','items'=>[['Chicken','5kg','£1500'],['Steak','x3','£800']]],
                    ['id'=>'#112346','status'=>'Collected','slot'=>'Thu 10:00–13:00','items'=>[['Lamb','2kg','£900']]],
                    ['id'=>'#112347','status'=>'Pending','slot'=>'Fri 13:00–16:00','items'=>[['Pork','1kg','£400'],['Mince','500g','£300']]],
                ];
                foreach ($all_orders as $o): ?>
                <div class="order-card">
                    <div class="order-card-header">
                        <span><strong>Order <?= $o['id'] ?></strong></span>
                        <span>Status: <?= $o['status'] ?></span>
                        <span><?= $o['slot'] ?></span>
                    </div>
                    <?php foreach ($o['items'] as $item): ?>
                    <div class="order-item-row">
                        <span><?= $item[0] ?></span><span><?= $item[1] ?></span>
                        <span class="order-item-price"><?= $item[2] ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endforeach; ?>

                <!-- ════════════════════ PROFILE ════════════════════ -->
                <?php elseif ($page === 'profile'): ?>

                <form method="POST" action="?page=profile" enctype="multipart/form-data">
                    <input type="hidden" name="save_profile" value="1">

                    <div class="section-title">Profile setting</div>

                    <!-- Shop logo upload -->
                    <div class="profile-logo-row">
                        <div>
                            <label for="shopLogoInput" style="cursor:pointer;">
                                <div class="shop-logo-upload" id="shopLogoPreview">
                                    <?php if ($avatarUrl): ?>
                                        <img src="<?= $avatarUrl ?>" alt="Shop logo">
                                    <?php else: ?>
                                        Shop logo
                                    <?php endif; ?>
                                </div>
                            </label>
                            <input type="file" name="avatar" id="shopLogoInput" style="display:none"
                                   accept="image/jpeg,image/png,image/gif,image/webp"
                                   onchange="previewShopLogo(this)">
                        </div>
                        <span class="change-link" onclick="document.getElementById('shopLogoInput').click()">[Change]</span>
                    </div>

                    <div class="form-section-title">Shop Details</div>
                    <div class="form-row"><label>Shop Name:</label><input type="text" name="shop" value="<?= htmlspecialchars($trader['shop']) ?>"></div>
                    <div class="form-row"><label>Shop Type:</label><input type="text" name="shop_type" value="<?= htmlspecialchars($trader['type']) ?>"></div>
                    <div class="form-row">
                        <label>Description:</label>
                        <textarea name="description" id="shopDesc" oninput="updateCount(this,'shopDescCount',150)"><?= htmlspecialchars($trader['description']) ?></textarea>
                        <div class="char-count"><span id="shopDescCount"><?= strlen($trader['description']) ?></span>/150</div>
                    </div>
                    <div class="form-row"><label>Shop Address:</label><input type="text" name="address" value="<?= htmlspecialchars($trader['address']) ?>"></div>

                    <div class="form-section-title">Owner Details</div>
                    <div class="form-row"><label>Owner Name:</label><input type="text" name="name" value="<?= htmlspecialchars($trader['name']) ?>"></div>
                    <div class="form-row"><label>Email address:</label><input type="email" name="email" value="<?= htmlspecialchars($trader['email']) ?>"></div>
                    <div class="form-row"><label>Phone number:</label><input type="tel" name="phone" value="<?= htmlspecialchars($trader['phone']) ?>"></div>

                    <div class="form-section-title">Change Password</div>
                    <div class="form-row">
                        <label>Current password:</label>
                        <div class="password-field-wrap">
                            <input type="password" name="current_password" placeholder="Current password">
                            <button type="button" class="eye-toggle" onclick="togglePwd(this)">👁</button>
                        </div>
                    </div>
                    <div class="form-row">
                        <label>New password:</label>
                        <div class="password-field-wrap">
                            <input type="password" name="new_password" placeholder="New password">
                            <button type="button" class="eye-toggle" onclick="togglePwd(this)">👁</button>
                        </div>
                    </div>
                    <div class="form-row">
                        <label>Confirm password:</label>
                        <div class="password-field-wrap">
                            <input type="password" name="confirm_password" placeholder="Confirm password">
                            <button type="button" class="eye-toggle" onclick="togglePwd(this)">👁</button>
                        </div>
                    </div>

                    <div class="form-section-title">Shop hours</div>
                    <div style="font-size:13px;margin-bottom:8px;">Collection days</div>
                    <div class="checkbox-group">
                        <?php foreach (['Wednesday','Thursday','Friday'] as $day): ?>
                        <label>
                            <input type="checkbox" name="collection_days[]" value="<?= $day ?>"
                                   <?= in_array($day, $trader['collection_days']) ? 'checked' : '' ?>>
                            <?= $day ?>
                        </label>
                        <?php endforeach; ?>
                    </div>

                    <div class="form-section-title">Notifications</div>
                    <div class="checkbox-group">
                        <?php
                        $notif_opts = ['new_order'=>'New Order Email Alerts','daily_report'=>'Daily Report Email','weekly_finance'=>'Weekly Finance Email','monthly_report'=>'Monthly Report Email'];
                        foreach ($notif_opts as $key => $label): ?>
                        <label>
                            <input type="checkbox" name="notifications[]" value="<?= $key ?>"
                                   <?= in_array($key, $trader['notifications']) ? 'checked' : '' ?>>
                            <?= $label ?>
                        </label>
                        <?php endforeach; ?>
                    </div>

                    <div class="form-actions" style="justify-content:center;flex-direction:column;align-items:center;gap:8px;margin-top:24px;">
                        <button type="submit" class="btn btn-secondary" style="min-width:180px;">Save changes</button>
                        <button type="button" class="delete-btn" style="min-width:180px;"
                                onclick="return confirm('Are you sure you want to delete your account? This cannot be undone.')">
                            Delete account
                        </button>
                    </div>
                </form>

                <?php endif; ?>

            </div><!-- /main-pane-inner -->
        </main>
    </div><!-- /dashboard-grid -->
</div><!-- /page-wrapper -->

<script>
// ── Finance tab switcher ──
function switchTab(tab, btn) {
    document.getElementById('tab-monthly').style.display = tab === 'monthly' ? 'block' : 'none';
    document.getElementById('tab-weekly').style.display  = tab === 'weekly'  ? 'block' : 'none';
    document.querySelectorAll('.finance-tab').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    if (tab === 'weekly' && !window._weeklyChartDrawn) {
        drawChart('weeklyChart', weeklyData);
        window._weeklyChartDrawn = true;
    }
}

// ── Char counter
function updateCount(input, counterId, max) {
    var counter = document.getElementById(counterId);
    if (!counter) return;
    var len = input.value.length;
    if (len > max) {
        input.value = input.value.substring(0, max);
        len = max;
    }
    counter.textContent = len;
}

// ── Toggle password visibility
function togglePwd(btn) {
    var input = btn.parentElement.querySelector('input');
    if (!input) return;
    input.type = input.type === 'password' ? 'text' : 'password';
}

// ── Shop logo preview
function previewShopLogo(input) {
    if (!input.files || !input.files[0]) return;
    var reader = new FileReader();
    reader.onload = function(e) {
        var preview = document.getElementById('shopLogoPreview');
        if (!preview) return;
        preview.innerHTML = '<img src="' + e.target.result + '" alt="Shop logo">';
    };
    reader.readAsDataURL(input.files[0]);
}

// ── Minimal chart helpers (placeholder for testing)
var weeklyData = [800, 1200, 600];
var monthlyData = [1200, 1400, 900, 1600, 1100, 1700];
function drawChart(canvasId, data) {
    var canvas = document.getElementById(canvasId);
    if (!canvas || !canvas.getContext) return;
    var ctx = canvas.getContext('2d');
    ctx.clearRect(0, 0, canvas.width, canvas.height);
    ctx.fillStyle = '#FF5E3A';
    var maxVal = Math.max.apply(null, data);
    var step = canvas.width / (data.length + 1);
    data.forEach(function(val, i) {
        var x = step * (i + 1);
        var y = canvas.height - (val / maxVal) * (canvas.height - 20);
        ctx.beginPath();
        ctx.arc(x, y, 3, 0, Math.PI * 2);
        ctx.fill();
    });
}

if (document.getElementById('monthlyChart')) {
    drawChart('monthlyChart', monthlyData);
}

</script>

<?php include 'footer.php'; ?>
