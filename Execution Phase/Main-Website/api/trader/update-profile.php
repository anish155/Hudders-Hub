<?php
// api/trader/update-profile.php
// Actions:
//   update_password    — JSON POST, verifies current_password then updates
//   delete_account     — JSON POST, cascading deletes (trader/shop/products/user)
//   update_all         — multipart POST, updates shop+owner+logo+collection+notifications in one call

ob_start();
error_reporting(0);
require_once '../../config/database.php';
header('Content-Type: application/json');

$user_id = isset($_POST['user_id']) ? (int)$_POST['user_id'] : 0;
$action  = $_POST['action'] ?? $_GET['action'] ?? '';

if (!$user_id) {
    echo json_encode(['success' => false, 'message' => 'Missing user_id']);
    exit;
}

// ── helpers ─────────────────────────────────────────────────────────────────
function updateShopLogo($conn, $shop_id, $file) {
    if ($file['error'] !== UPLOAD_ERR_OK) return true; // no-op if no file
    $mime  = $file['type'];
    $fname = $file['name'];
    $data  = file_get_contents($file['tmp_name']);
    $sql   = "UPDATE SHOP SET shop_logo = EMPTY_BLOB(), mimetype = :mime, filename = :fname WHERE shop_id = :sid RETURNING shop_logo INTO :blob";
    $stmt  = oci_parse($conn, $sql);
    $blob  = oci_new_descriptor($conn, OCI_D_LOB);
    oci_bind_by_name($stmt, ':mime', $mime);
    oci_bind_by_name($stmt, ':fname', $fname);
    oci_bind_by_name($stmt, ':sid',   $shop_id);
    oci_bind_by_name($stmt, ':blob',  $blob, -1, OCI_B_BLOB);
    oci_execute($stmt, OCI_NO_AUTO_COMMIT);
    $blob->save($data);
    oci_free_statement($stmt);
    return true;
}

function commitAndClose($conn, $success = true, $msg = ''): void {
    if ($success) {
        oci_commit($conn);
    } else {
        oci_rollback($conn);
    }
    oci_close($conn);
    echo json_encode(['success' => $success, 'message' => $msg]);
    exit;
}

// ── locate shop ──────────────────────────────────────────────────────────────
$sql_shop = "SELECT shop_id, user_id FROM SHOP WHERE user_id = :user_id";
$stmt_shop = oci_parse($conn, $sql_shop);
oci_bind_by_name($stmt_shop, ':user_id', $user_id);
oci_execute($stmt_shop);
$shop = oci_fetch_assoc($stmt_shop);
oci_free_statement($stmt_shop);

if (!$shop && $action !== 'delete_account') {
    commitAndClose($conn, false, 'Shop not found');
}
$shop_id = $shop ? (int)$shop['SHOP_ID'] : 0;

// ── ROUTES ──────────────────────────────────────────────────────────────────
try {

    // ── update_password ──────────────────────────────────────────────────────
    if ($action === 'update_password') {
        $input    = json_decode(file_get_contents('php://input'), true);
        $current  = $input['current_password'] ?? '';
        $new      = $input['new_password']     ?? '';

        if (!trim($current) || !trim($new)) {
            commitAndClose($conn, false, 'Current and new password are required');
        }
        if (strlen(trim($new)) < 6) {
            commitAndClose($conn, false, 'New password must be at least 6 characters');
        }

        $verify = oci_parse($conn, "SELECT user_password FROM HUDDER_USER WHERE user_id = :user_id");
        oci_bind_by_name($verify, ':user_id', $user_id);
        oci_execute($verify);
        $row = oci_fetch_assoc($verify);
        oci_free_statement($verify);

        if (!$row) {
            commitAndClose($conn, false, 'User not found');
        }

        $db_pass = $row['USER_PASSWORD'];
        $valid = password_verify($current, $db_pass);
        if (!$valid) {
            $valid = ($current === $db_pass);
        }
        
        if (!$valid) {
            commitAndClose($conn, false, 'Current password is incorrect');
        }

        $new_hash = password_hash($new, PASSWORD_DEFAULT);
        $update = oci_parse($conn, "UPDATE HUDDER_USER SET user_password = :pwd WHERE user_id = :user_id");
        oci_bind_by_name($update, ':pwd', $new_hash);
        oci_bind_by_name($update, ':user_id', $user_id);
        oci_execute($update, OCI_COMMIT_ON_SUCCESS);
        oci_free_statement($update);
        commitAndClose($conn, true, 'Password updated successfully');
    }

    // ── delete_account ───────────────────────────────────────────────────────
    if ($action === 'delete_account') {
        // Order items for this shop's products
        $del_order_prod = oci_parse($conn, "DELETE FROM ORDER_PRODUCT WHERE product_id IN (SELECT product_id FROM PRODUCT WHERE shop_id = :sid)");
        oci_bind_by_name($del_order_prod, ':sid', $shop_id);
        oci_execute($del_order_prod, OCI_NO_AUTO_COMMIT);

        // Product images
        $del_images = oci_parse($conn, "DELETE FROM PRODUCT_IMAGE WHERE product_id IN (SELECT product_id FROM PRODUCT WHERE shop_id = :sid)");
        oci_bind_by_name($del_images, ':sid', $shop_id);
        oci_execute($del_images, OCI_NO_AUTO_COMMIT);

        // Reviews on this shop's products
        $del_rev = oci_parse($conn, "DELETE FROM REVIEW WHERE product_id IN (SELECT product_id FROM PRODUCT WHERE shop_id = :sid)");
        oci_bind_by_name($del_rev, ':sid', $shop_id);
        oci_execute($del_rev, OCI_NO_AUTO_COMMIT);

        // Discounts on this shop's products
        $del_disc = oci_parse($conn, "DELETE FROM DISCOUNT WHERE product_id IN (SELECT product_id FROM PRODUCT WHERE shop_id = :sid)");
        oci_bind_by_name($del_disc, ':sid', $shop_id);
        oci_execute($del_disc, OCI_NO_AUTO_COMMIT);

        // Products
        $del_products = oci_parse($conn, "DELETE FROM PRODUCT WHERE shop_id = :sid");
        oci_bind_by_name($del_products, ':sid', $shop_id);
        oci_execute($del_products, OCI_NO_AUTO_COMMIT);

        // Shop
        $del_shop = oci_parse($conn, "DELETE FROM SHOP WHERE shop_id = :sid");
        oci_bind_by_name($del_shop, ':sid', $shop_id);
        oci_execute($del_shop, OCI_NO_AUTO_COMMIT);

        // Trader
        $del_trader = oci_parse($conn, "DELETE FROM TRADER WHERE user_id = :user_id");
        oci_bind_by_name($del_trader, ':user_id', $user_id);
        oci_execute($del_trader, OCI_NO_AUTO_COMMIT);

        // HUDDER_USER (final, cascading any remaining refs)
        $del_user = oci_parse($conn, "DELETE FROM HUDDER_USER WHERE user_id = :user_id");
        oci_bind_by_name($del_user, ':user_id', $user_id);
        oci_execute($del_user, OCI_COMMIT_ON_SUCCESS);
        commitAndClose($conn, true, 'Account deleted');
    }

    // ── update_all (multipart form submit) ───────────────────────────────────
    if ($action === 'update_all') {
        $shop_name        = trim($_POST['shop_name']        ?? '');
        $shop_desc        = trim($_POST['shop_description'] ?? '');
        $shop_addr        = trim($_POST['shop_address']     ?? '');
        $firstname        = trim($_POST['firstname']         ?? '');
        $lastname         = trim($_POST['lastname']          ?? '');
        $email            = trim($_POST['email']             ?? '');
        $phone            = trim($_POST['phone']             ?? '');
        $collection_wed   = isset($_POST['collection_wed'])    ? (int)$_POST['collection_wed']    : 1;
        $collection_thu   = isset($_POST['collection_thu'])    ? (int)$_POST['collection_thu']    : 1;
        $collection_fri   = isset($_POST['collection_fri'])    ? (int)$_POST['collection_fri']    : 1;
        $notify_new_order = isset($_POST['notify_new_order'])  ? (int)$_POST['notify_new_order']  : 1;
        $notify_daily     = isset($_POST['notify_daily_report']) ? (int)$_POST['notify_daily_report'] : 0;
        $notify_weekly    = isset($_POST['notify_weekly_finance']) ? (int)$_POST['notify_weekly_finance'] : 1;
        $notify_monthly   = isset($_POST['notify_monthly_report']) ? (int)$_POST['notify_monthly_report'] : 1;

        if (!$shop_name) {
            commitAndClose($conn, false, 'Shop name is required');
        }
        if (!$shop_id) {
            commitAndClose($conn, false, 'No shop found for this account');
        }

        $errors = [];

        // ── Update SHOP ──
        $sql_up_shop = "UPDATE SHOP SET name=:nm, description=:desc, location=:loc,
                             collection_wed=:wed, collection_thu=:thu, collection_fri=:fri
                        WHERE shop_id=:sid";
        $stmt_s = oci_parse($conn, $sql_up_shop);
        oci_bind_by_name($stmt_s, ':nm',   $shop_name);
        oci_bind_by_name($stmt_s, ':desc', $shop_desc);
        oci_bind_by_name($stmt_s, ':loc',  $shop_addr);
        oci_bind_by_name($stmt_s, ':wed',  $collection_wed);
        oci_bind_by_name($stmt_s, ':thu',  $collection_thu);
        oci_bind_by_name($stmt_s, ':fri',  $collection_fri);
        oci_bind_by_name($stmt_s, ':sid',  $shop_id);
        if (!oci_execute($stmt_s, OCI_NO_AUTO_COMMIT)) {
            $e = oci_error($stmt_s);
            $errors[] = 'Shop update failed: ' . $e['message'];
        }
        oci_free_statement($stmt_s);

        // ── Update notification toggles on HUDDER_USER (always run) ──
        $notifySql = "UPDATE HUDDER_USER
                         SET notify_new_order=:nno,
                             notify_daily_report=:ndr,
                             notify_weekly_finance=:nwf,
                             notify_monthly_report=:nmr
                       WHERE user_id=:uid";
        $notifyStmt = oci_parse($conn, $notifySql);
        oci_bind_by_name($notifyStmt, ':nno', $notify_new_order);
        oci_bind_by_name($notifyStmt, ':ndr', $notify_daily);
        oci_bind_by_name($notifyStmt, ':nwf', $notify_weekly);
        oci_bind_by_name($notifyStmt, ':nmr', $notify_monthly);
        oci_bind_by_name($notifyStmt, ':uid', $user_id);
        if (!oci_execute($notifyStmt, OCI_NO_AUTO_COMMIT)) {
            $e = oci_error($notifyStmt);
            $errors[] = 'Notification settings update failed: ' . $e['message'];
        }
        oci_free_statement($notifyStmt);

        // ── Update owner details on HUDDER_USER (only when fields are non-empty) ──
        if ($firstname !== '' || $lastname !== '' || $email !== '' || $phone !== '') {
            $sql_up_user = "UPDATE HUDDER_USER SET firstname=:fn, lastname=:ln, email=:em,
                                 phone_number=:ph
                            WHERE user_id=:user_id";
            $stmt_u = oci_parse($conn, $sql_up_user);
            oci_bind_by_name($stmt_u, ':fn',  $firstname);
            oci_bind_by_name($stmt_u, ':ln',  $lastname);
            oci_bind_by_name($stmt_u, ':em',  $email);
            oci_bind_by_name($stmt_u, ':ph',  $phone);
            oci_bind_by_name($stmt_u, ':user_id', $user_id);
            if (!oci_execute($stmt_u, OCI_NO_AUTO_COMMIT)) {
                $e = oci_error($stmt_u);
                $errors[] = 'User update failed: ' . $e['message'];
            }
            oci_free_statement($stmt_u);
        }

        // ── Update logo if uploaded ──
        if (isset($_FILES['shop_logo']) && $_FILES['shop_logo']['error'] === UPLOAD_ERR_OK) {
            updateShopLogo($conn, $shop_id, $_FILES['shop_logo']);
        }

        if (!empty($errors)) {
            oci_rollback($conn);
            commitAndClose($conn, false, implode(' | ', $errors));
        }

        if (!oci_commit($conn)) {
            $e = oci_error($conn);
            commitAndClose($conn, false, 'Commit failed: ' . $e['message']);
        }
        commitAndClose($conn, true, 'Profile updated successfully');
    }

    // Unknown action — reach multi-action (merge) if still needed for backwards compat
    if ($action === 'update_shop' || $action === 'update_owner') {
        $input = json_decode(file_get_contents('php://input'), true) ?: [];
        $data = array_merge($_POST, $input);
        
        $shop_name = trim($data['shop_name']        ?? '');
        $shop_desc = trim($data['shop_description'] ?? '');
        $location  = trim($data['location']         ?? $_POST['shop_address']     ?? '');
        $firstname = trim($data['firstname']         ?? '');
        $lastname  = trim($data['lastname']          ?? '');
        $email     = trim($data['email']             ?? '');
        $phone     = trim($data['phone']             ?? '');

        if ($action === 'update_shop' && $shop_name) {
            $sql = "UPDATE SHOP SET name=:nm, description=:desc, location=:loc WHERE shop_id=:sid";
            $stmt = oci_parse($conn, $sql);
            oci_bind_by_name($stmt, ':nm', $shop_name);
            oci_bind_by_name($stmt, ':desc', $shop_desc);
            oci_bind_by_name($stmt, ':loc', $location);
            oci_bind_by_name($stmt, ':sid', $shop_id);
            oci_execute($stmt, OCI_COMMIT_ON_SUCCESS);
            oci_free_statement($stmt);
        }
        if ($action === 'update_owner' && $firstname && $lastname) {
            $sql = "UPDATE HUDDER_USER SET firstname=:fn, lastname=:ln, email=:em, phone_number=:ph WHERE user_id=:user_id";
            $stmt = oci_parse($conn, $sql);
            oci_bind_by_name($stmt, ':fn', $firstname);
            oci_bind_by_name($stmt, ':ln', $lastname);
            oci_bind_by_name($stmt, ':em', $email);
            oci_bind_by_name($stmt, ':ph', $phone);
            oci_bind_by_name($stmt, ':user_id', $user_id);
            oci_execute($stmt, OCI_COMMIT_ON_SUCCESS);
            oci_free_statement($stmt);
        }
        commitAndClose($conn, true, ucfirst(str_replace('_', ' ', $action)) . ' updated');
    }

    // Fallback: unknown action
    commitAndClose($conn, false, 'Unknown action: ' . $action);

} catch (Exception $e) {
    oci_rollback($conn);
    commitAndClose($conn, false, $e->getMessage());
}
oci_close($conn);
exit;
?>

