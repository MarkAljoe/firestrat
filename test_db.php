<?php
require_once __DIR__ . '/config/database.php';
$db = getDB();
$info = $db->query('SELECT @@port AS port, @@version AS version, DATABASE() AS db_name, (SELECT COUNT(*) FROM incidents) AS incident_count')->fetch();
echo '<pre>';
print_r($info);
echo '</pre>';