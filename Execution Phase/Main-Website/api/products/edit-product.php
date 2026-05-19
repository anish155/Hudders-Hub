<?php
// api/products/edit-product.php
// This endpoint is handled by api/trader/update-product.php.
// Redirect any legacy calls so nothing silently fails.
header('Location: ../trader/update-product.php');
exit;
?>
