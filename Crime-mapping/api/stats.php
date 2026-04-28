<?php
header('Content-Type: application/json');
require __DIR__ . '/db.php';

$dailyStmt = $pdo->query("SELECT COUNT(*) AS total FROM incidents WHERE occurred_at >= (NOW() - INTERVAL 1 DAY)");
$daily = (int) $dailyStmt->fetchColumn();

$activeStmt = $pdo->query("SELECT COUNT(*) AS total FROM incidents WHERE status IN ('pending','under_investigation','action_taken')");
$active = (int) $activeStmt->fetchColumn();

$hotspotStmt = $pdo->query("
    SELECT b.barangay_name, COUNT(*) AS total
    FROM incidents i
    JOIN barangays b ON i.barangay_id = b.barangay_id
    WHERE i.occurred_at >= (NOW() - INTERVAL 30 DAY)
    GROUP BY b.barangay_id
    ORDER BY total DESC
    LIMIT 1
");
$hotspotRow = $hotspotStmt->fetch();
$hotspot = $hotspotRow ? $hotspotRow['barangay_name'] : '-';

echo json_encode([
    'ok' => true,
    'data' => [
        'daily' => $daily,
        'active' => $active,
        'hotspot' => $hotspot
    ]
]);
