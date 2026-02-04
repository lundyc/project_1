<?php
require 'app/lib/db.php';

try {
    $pdo = db();
    $result = $pdo->query('DESCRIBE rate_limit_attempts');
    $rows = $result->fetchAll();
    if (empty($rows)) {
        echo "Table does not exist\n";
        exit(1);
    } else {
        echo "Table exists with " . count($rows) . " columns\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
