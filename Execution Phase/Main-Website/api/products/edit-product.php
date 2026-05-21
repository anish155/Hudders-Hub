<?php
// api/products/edit-product.php
// This endpoint is handled by api/trader/update-product.php.
// Redirect any legacy calls so nothing silently fails.
$base = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
$target = $base . '/../trader/update-product.php';
header('Location: ' . $target, true, 307);
exit;
?>
