<?php
header('Content-Type: application/json');
require __DIR__ . '/db.php';

$payload = json_decode(file_get_contents('php://input'), true);
if (!$payload) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Invalid request payload.']);
    exit;
}

$required = ['crime_type_id', 'title', 'description', 'barangay', 'occurred_date', 'occurred_time', 'severity', 'latitude', 'longitude'];
foreach ($required as $field) {
    if (!isset($payload[$field]) || $payload[$field] === '') {
        http_response_code(422);
        echo json_encode(['ok' => false, 'error' => 'Missing required fields.']);
        exit;
    }
}

$crimeTypeId = (int) $payload['crime_type_id'];
$barangayName = trim($payload['barangay']);

$typeStmt = $pdo->prepare('SELECT crime_type_id FROM crime_types WHERE crime_type_id = :id AND is_active = 1');
$typeStmt->execute([':id' => $crimeTypeId]);
if (!$typeStmt->fetch()) {
    http_response_code(422);
    echo json_encode(['ok' => false, 'error' => 'Invalid crime type.']);
    exit;
}

$barangayStmt = $pdo->prepare('SELECT barangay_id FROM barangays WHERE barangay_name = :name');
$barangayStmt->execute([':name' => $barangayName]);
$barangayRow = $barangayStmt->fetch();
if (!$barangayRow) {
    http_response_code(422);
    echo json_encode(['ok' => false, 'error' => 'Invalid barangay.']);
    exit;
}

$occurredAt = $payload['occurred_date'] . ' ' . $payload['occurred_time'] . ':00';

$insert = $pdo->prepare(
    'INSERT INTO incidents
    (crime_type_id, title, description, barangay_id, latitude, longitude, occurred_at, severity, status, source, is_public)
    VALUES
    (:crime_type_id, :title, :description, :barangay_id, :latitude, :longitude, :occurred_at, :severity, :status, :source, :is_public)'
);

$insert->execute([
    ':crime_type_id' => $crimeTypeId,
    ':title' => trim($payload['title']),
    ':description' => trim($payload['description']),
    ':barangay_id' => (int) $barangayRow['barangay_id'],
    ':latitude' => $payload['latitude'],
    ':longitude' => $payload['longitude'],
    ':occurred_at' => $occurredAt,
    ':severity' => $payload['severity'],
    ':status' => 'pending',
    ':source' => 'reported',
    ':is_public' => 0
]);

$incidentId = (int) $pdo->lastInsertId();

echo json_encode([
    'ok' => true,
    'incident_id' => $incidentId
]);
