<?php
// Debug script: Show period events for a match
require_once __DIR__ . '/../../app/lib/db.php';


$pdo = db();
$stmt = $pdo->prepare("SELECT e.id, e.match_id, e.event_type_id, et.label AS event_type_label, e.period_id, mp.label AS period_label, e.match_second FROM events e LEFT JOIN event_types et ON et.id = e.event_type_id LEFT JOIN match_periods mp ON mp.id = e.period_id WHERE (et.label = 'Period Start' OR et.label = 'Period End') ORDER BY e.match_id ASC, e.match_second ASC");
$stmt->execute();
$rows = $stmt->fetchAll();

header('Content-Type: text/html');
echo '<h2>Period Events for All Matches</h2>';
if (count($rows) === 0) {
    echo '<p>No period events found.</p>';
} else {
    $grouped = [];
    foreach ($rows as $row) {
        $grouped[$row['match_id']][] = $row;
    }
    foreach ($grouped as $matchId => $events) {
        echo '<h3>Match ID: ' . htmlspecialchars($matchId) . '</h3>';
        echo '<table border="1" cellpadding="4"><tr><th>ID</th><th>Type</th><th>Label</th><th>Period ID</th><th>Period Name</th><th>Match Second</th></tr>';
        foreach ($events as $row) {
            echo '<tr>';
            echo '<td>' . htmlspecialchars($row['id']) . '</td>';
            echo '<td>' . htmlspecialchars($row['event_type_id']) . '</td>';
            echo '<td>' . htmlspecialchars($row['event_type_label']) . '</td>';
            echo '<td>' . htmlspecialchars($row['period_id']) . '</td>';
            echo '<td>' . htmlspecialchars($row['period_label']) . '</td>';
            echo '<td>' . htmlspecialchars($row['match_second']) . '</td>';
            echo '</tr>';
        }
        echo '</table>';
    }
}
