<?php
header('Content-Type: application/json');
require __DIR__ . '/db.php';

$sql = "
    SELECT
        i.incident_id AS id,
        i.title,
        b.barangay_name AS barangay,
        i.severity,
        DATE(i.occurred_at) AS date
    FROM incidents i
    JOIN barangays b ON i.barangay_id = b.barangay_id
    WHERE i.severity = 'high'
    ORDER BY i.occurred_at DESC
    LIMIT 5
";

$stmt = $pdo->query($sql);
$alerts = $stmt->fetchAll();

echo json_encode([
    'ok' => true,
    'data' => $alerts
]);
