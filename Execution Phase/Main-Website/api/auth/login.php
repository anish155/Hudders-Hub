<?php
error_reporting(0);
ini_set('display_errors', 0);
header('Content-Type: application/json');
require_once '../../config/database.php';
require_once '../../config/session.php';

$data = json_decode(file_get_contents('php://input'), true);

try {
    if (!$data || empty($data['email']) || !isset($data['password'])) {
        throw new Exception('Email and password are required.');
    }

    $email          = trim($data['email']);
    $password       = $data['password'];
    $requested_role = strtolower(trim($data['role'] ?? 'customer'));

    $sql  = "SELECT user_id, firstname, lastname, email, user_password, user_role, verification_token, verified_at FROM HUDDER_USER WHERE email = :em";
    $stmt = oci_parse($conn, $sql);
    oci_bind_by_name($stmt, ':em', $email);
    oci_execute($stmt);
    $user = oci_fetch_assoc($stmt);
    oci_free_statement($stmt);

    if (!$user) {
        // Check if the email exists at all vs wrong password
        $chk = oci_parse($conn, "SELECT COUNT(*) AS CNT FROM HUDDER_USER WHERE UPPER(email) = UPPER(:em)");
        oci_bind_by_name($chk, ':em', $email);
        oci_execute($chk);
        $row = oci_fetch_assoc($chk);
        oci_free_statement($chk);

        if ($row && (int)$row['CNT'] > 0) {
            throw new Exception('Incorrect password. Please try again.');
        } else {
            throw new Exception('Email not found. Please check your email or sign up.');
        }
    }

    // Role check
    $db_role = strtolower($user['USER_ROLE']);
    if ($db_role !== $requested_role) {
        if ($db_role === 'trader' && $requested_role === 'customer') {
            throw new Exception('This email is registered as a Trader. Please switch to the Trader login tab.');
        } elseif ($db_role === 'customer' && $requested_role === 'trader') {
            throw new Exception('This email is registered as a Customer. Please switch to the Customer login tab.');
        } else {
            throw new Exception('Account not found. Please use the ' . ucfirst($db_role) . ' login.');
        }
    }

    // Trader approval check (BEFORE password so pending traders get the right message)
    if ($requested_role === 'trader') {
        $ts   = oci_parse($conn, "SELECT status FROM TRADER WHERE user_id = :user_id");
        oci_bind_by_name($ts, ':user_id', $user['USER_ID']);
        oci_execute($ts);
        $trow = oci_fetch_assoc($ts);
        oci_free_statement($ts);

        if ($trow && strtolower(trim($trow['STATUS'])) === 'pending') {
            echo json_encode(['success' => false, 'trader_pending' => true,
                'message' => 'Your trader account is pending admin approval. Please wait for confirmation before logging in.']);
            exit;
        }
    }

    // Password verification (supports bcrypt and plain-text fallback)
    $db_pass = $user['USER_PASSWORD'];
    $valid   = password_verify($password, $db_pass);
    if (!$valid) {
        $valid = ($password === $db_pass);
    }
    if (!$valid) {
        echo json_encode(['success' => false, 'message' => 'Incorrect password. Please try again.', 'password_error' => true]);
        exit;
    }

    // Email verification check
    if (!empty($user['VERIFICATION_TOKEN']) && empty($user['VERIFIED_AT'])) {
        echo json_encode([
            'success' => false,
            'email_not_verified' => true,
            'message' => 'Please verify your email before logging in.'
        ]);
        exit;
    }

    // Set session
    $_SESSION['user_id']   = $user['USER_ID'];
    $_SESSION['user_role'] = $user['USER_ROLE'];
    $_SESSION['email']     = $user['EMAIL'];
    $_SESSION['firstname'] = $user['FIRSTNAME'];

    echo json_encode([
        'success'   => true,
        'name'      => $user['FIRSTNAME'],
        'lastname'  => $user['LASTNAME'],
        'role'      => $user['USER_ROLE'],
        'user_id'   => $user['USER_ID'],
        'email'     => $user['EMAIL']
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} finally {
    if (isset($conn)) oci_close($conn);
}
?>
