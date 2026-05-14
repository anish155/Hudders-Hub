<?php
require_once 'config/database.php';

$sql = "SELECT table_name FROM user_tables";
$stmt = oci_parse($conn, $sql);
oci_execute($stmt);

echo "Connected! Your tables:<br><br>";
while ($row = oci_fetch_assoc($stmt)) {
    echo "- " . $row['TABLE_NAME'] . "<br>";
}
oci_close($conn);
?>