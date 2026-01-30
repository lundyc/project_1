<?php
// Query for Saltcoats vs Vale of Leven fixture on 31/01/2026
$config = require __DIR__ . '/../config/config.php';
$db = $config['db'];

$mysqli = new mysqli($db['host'], $db['user'], $db['pass'], $db['name']);
if ($mysqli->connect_errno) {
    die("Failed to connect to MySQL: " . $mysqli->connect_error);
}
$mysqli->set_charset($db['charset']);

// Try to find the fixture in both 'fixtures' and 'matches' tables
$sql = "SELECT f.id, f.home_team, f.away_team, f.kickoff_at, f.competition_id, f.season_id
        FROM fixtures f
        WHERE (f.home_team LIKE '%Saltcoats%' OR f.away_team LIKE '%Saltcoats%')
          AND (f.home_team LIKE '%Vale of Leven%' OR f.away_team LIKE '%Vale of Leven%')
          AND DATE(f.kickoff_at) = '2026-01-31'";

$result = $mysqli->query($sql);
if (!$result) {
    die("Query error: " . $mysqli->error);
}

if ($result->num_rows === 0) {
    echo "No fixture found for Saltcoats vs Vale of Leven on 31/01/2026.\n";
} else {
    while ($row = $result->fetch_assoc()) {
        echo "Fixture ID: {$row['id']}\n";
        echo "Home Team: {$row['home_team']}\n";
        echo "Away Team: {$row['away_team']}\n";
        echo "Kickoff: {$row['kickoff_at']}\n";
        echo "Competition ID: {$row['competition_id']}\n";
        echo "Season ID: {$row['season_id']}\n";
        echo "-----------------------------\n";
    }
}

$mysqli->close();
