<?php
require_once 'config/config.php';

global $db;

$stmt = $db->query("SELECT @@session.time_zone;");
$result = $stmt->fetch();

echo "PHP Timezone: " . date_default_timezone_get() . "<br>";
echo "MySQL Session Timezone: " . $result['@@session.time_zone'];
?>