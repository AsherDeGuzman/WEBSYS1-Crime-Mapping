<?php
header('Content-Type: application/json');
require __DIR__ . '/db.php';

$typesParam = isset($_GET['types']) ? trim($_GET['types']) : '';
$types = array_filter(array_map('trim', explode(',', $typesParam)));
$barangay = isset($_GET['barangay']) ? trim($_GET['barangay']) : '';
$status = isset($_GET['status']) ? trim($_GET['status']) : '';
$dateStart = isset($_GET['date_start']) ? trim($_GET['date_start']) : '';
$dateEnd = isset($_GET['date_end']) ? trim($_GET['date_end']) : '';
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$limit = isset($_GET['limit']) ? (int) $_GET['limit'] : 0;

$where = [];
$params = [];

if ($types) {
    $placeholders = [];
    foreach ($types as $index => $type) {
        $key = ':type' . $index;
        $placeholders[] = $key;
        $params[$key] = $type;
    }
    $where[] = 'ct.category IN (' . implode(',', $placeholders) . ')';
}

if ($barangay !== '') {
    $where[] = 'b.barangay_name = :barangay';
    $params[':barangay'] = $barangay;
}

if ($status !== '') {
    $where[] = 'i.status = :status';
    $params[':status'] = $status;
}

if ($dateStart !== '') {
    $where[] = 'i.occurred_at >= :date_start';
    $params[':date_start'] = $dateStart . ' 00:00:00';
}

if ($dateEnd !== '') {
    $where[] = 'i.occurred_at <= :date_end';
    $params[':date_end'] = $dateEnd . ' 23:59:59';
}

if ($search !== '') {
    $where[] = '(i.title LIKE :search OR i.description LIKE :search OR b.barangay_name LIKE :search OR ct.type_name LIKE :search)';
    $params[':search'] = '%' . $search . '%';
}

$sql = "
    SELECT
        i.incident_id AS id,
        ct.category AS type,
        ct.type_name,
        i.title,
        i.description,
        b.barangay_name AS barangay,
        i.status,
        i.severity,
        DATE(i.occurred_at) AS date,
        i.latitude AS lat,
        i.longitude AS lng
    FROM incidents i
    JOIN barangays b ON i.barangay_id = b.barangay_id
    JOIN crime_types ct ON i.crime_type_id = ct.crime_type_id
";

if ($where) {
    $sql .= ' WHERE ' . implode(' AND ', $where);
}

$sql .= ' ORDER BY i.occurred_at DESC';

if ($limit > 0) {
    $sql .= ' LIMIT ' . $limit;
}

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$incidents = $stmt->fetchAll();

echo json_encode([
    'ok' => true,
    'data' => $incidents
]);
