<?php
// CLI seed loader for demo data.
// Usage: php tools/seed.php

if (php_sapi_name() !== 'cli') {
    echo "Run this script from the command line only.\n";
    exit(1);
}

require __DIR__ . '/../api/db.php';

$seedFile = __DIR__ . '/../sql/seed-demo.sql';
if (!file_exists($seedFile)) {
    echo "Seed file not found: {$seedFile}\n";
    exit(1);
}

$sql = file_get_contents($seedFile);
if ($sql === false) {
    echo "Failed to read seed file.\n";
    exit(1);
}

try {
    $pdo->exec($sql);
    echo "Demo seed data inserted successfully.\n";
} catch (PDOException $exception) {
    echo "Seed failed: " . $exception->getMessage() . "\n";
    exit(1);
}
