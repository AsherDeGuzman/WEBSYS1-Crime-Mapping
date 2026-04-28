<?php
header('Content-Type: application/json');
require __DIR__ . '/db.php';

$typesStmt = $pdo->query("SELECT crime_type_id, category, type_name FROM crime_types WHERE is_active = 1 ORDER BY category, type_name");
$types = $typesStmt->fetchAll();

$barangaysStmt = $pdo->query("SELECT barangay_name FROM barangays ORDER BY barangay_name");
$barangays = $barangaysStmt->fetchAll();

$dateStmt = $pdo->query("SELECT MIN(occurred_at) AS min_date, MAX(occurred_at) AS max_date FROM incidents");
$dateRange = $dateStmt->fetch();

$statuses = [
    'pending',
    'under_investigation',
    'action_taken',
    'resolved',
    'dismissed'
];

echo json_encode([
    'ok' => true,
    'data' => [
        'types' => $types,
        'barangays' => array_map(function ($row) {
            return $row['barangay_name'];
        }, $barangays),
        'date_range' => [
            'min' => $dateRange ? $dateRange['min_date'] : null,
            'max' => $dateRange ? $dateRange['max_date'] : null
        ],
        'statuses' => $statuses
    ]
]);
