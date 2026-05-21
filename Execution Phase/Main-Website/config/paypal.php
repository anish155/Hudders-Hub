<?php
/**
 * PayPal Configuration
 * 
 * Get your sandbox credentials from:
 * https://developer.paypal.com/dashboard/applications/sandbox
 * 
 * For production, switch to live credentials and change PAYPAL_API_URL.
 */

// ── Sandbox (Testing) ──────────────────────────────────────────────────
define('PAYPAL_CLIENT_ID', 'AV177GLmNtAt_QsGuJnCP8UVzyEvuWSc7RKu0z-vMPX02i-SUQhtXIVsilDsE_pmLVvrRWk5I9F7LMzS');
define('PAYPAL_CLIENT_SECRET', 'EHutf57JDOJg_an07e7cFvIqiHtOPp-ZY1fqFpg4Vsyni6Qq1u_2a9kvXQp3lPUHhx4ZxLRozj8vp40U');
define('PAYPAL_API_URL', 'https://api-m.sandbox.paypal.com');

// ── Production (Uncomment when ready) ──────────────────────────────────
// define('PAYPAL_CLIENT_ID', 'YOUR_LIVE_CLIENT_ID');
// define('PAYPAL_CLIENT_SECRET', 'YOUR_LIVE_CLIENT_SECRET');
// define('PAYPAL_API_URL', 'https://api-m.paypal.com');

// ── Base URLs ──────────────────────────────────────────────────────────
define('BASE_URL', 'http://localhost/Main-Website');
define('RETURN_URL', BASE_URL . '/public/payment.html');
define('CANCEL_URL', BASE_URL . '/public/payment.html?cancelled=1');
?>
