<?php
/**
 * Audit script to validate period assignments.
 * 
 * This script checks that all events are correctly assigned to periods based on their match_second.
 * It generates a comprehensive report of any misassignments or warnings.
 * 
 * Usage: php scripts/audit_event_periods.php [--json]
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../app/lib/db.php';
require_once __DIR__ . '/../app/lib/event_validation.php';

$jsonMode = in_array('--json', $argv, true);

$pdo = db();

// Get all events with their period info
$stmt = $pdo->query(
    'SELECT 
        e.id,
        e.match_id,
        e.match_second,
        e.period_id,
        e.minute,
        e.minute_extra,
        mp.period_key,
        mp.start_second,
        mp.end_second,
        (SELECT COUNT(*) FROM events WHERE match_id = e.match_id) as match_event_count
    FROM events e
    JOIN match_periods mp ON mp.id = e.period_id
    ORDER BY e.match_id, e.match_second'
);

$events = $stmt->fetchAll(PDO::FETCH_ASSOC);

$results = [
    'timestamp' => date('Y-m-d H:i:s'),
    'total_events' => count($events),
    'matches' => [],
    'issues' => [],
    'summary' => [
        'correct' => 0,
        'stoppage_time' => 0,
        'warnings' => 0,
        'errors' => 0,
    ],
];

$matchStats = [];

foreach ($events as $event) {
    $matchId = (int)$event['match_id'];
    $eventId = (int)$event['id'];
    $matchSec = (int)$event['match_second'];
    $periodId = (int)$event['period_id'];
    $start = (int)$event['start_second'];
    $end = $event['end_second'] !== null ? (int)$event['end_second'] : null;
    $periodKey = $event['period_key'];
    $minute = (int)$event['minute'];
    $minuteExtra = (int)$event['minute_extra'];
    
    if (!isset($matchStats[$matchId])) {
        $matchStats[$matchId] = [
            'correct' => 0,
            'stoppage_time' => 0,
            'warnings' => 0,
            'errors' => 0,
        ];
    }
    
    // Check if event is within the period
    if ($end !== null && $matchSec >= $start && $matchSec <= $end) {
        // Normal case: event is within period
        $matchStats[$matchId]['correct']++;
        $results['summary']['correct']++;
    } elseif ($end !== null && $matchSec > $end) {
        // Stoppage time: event is after period end but before next period
        // This is valid if no next period has started
        $matchStats[$matchId]['stoppage_time']++;
        $results['summary']['stoppage_time']++;
    } elseif ($end === null && $matchSec >= $start) {
        // Period is still ongoing (no end time set yet)
        $matchStats[$matchId]['correct']++;
        $results['summary']['correct']++;
    } else {
        // Error case
        $issue = [
            'event_id' => $eventId,
            'match_id' => $matchId,
            'match_second' => $matchSec,
            'minute' => $minute,
            'period_id' => $periodId,
            'period_key' => $periodKey,
            'error_type' => 'MISALIGNED',
            'message' => "Event time $matchSec is before period start ($start) for {$periodKey}",
        ];
        $results['issues'][] = $issue;
        $matchStats[$matchId]['errors']++;
        $results['summary']['errors']++;
    }
}

// Add match statistics
foreach ($matchStats as $matchId => $stats) {
    $results['matches'][$matchId] = [
        'match_id' => $matchId,
        'event_count' => (int)($stats['correct'] + $stats['stoppage_time'] + $stats['warnings'] + $stats['errors']),
        'correct_assignments' => $stats['correct'],
        'stoppage_time' => $stats['stoppage_time'],
        'warnings' => $stats['warnings'],
        'errors' => $stats['errors'],
    ];
}

// Output results
if ($jsonMode) {
    header('Content-Type: application/json');
    echo json_encode($results, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
} else {
    echo "Event Period Assignment Audit Report\n";
    echo str_repeat('=', 70) . "\n";
    echo "Generated: {$results['timestamp']}\n";
    echo "Total Events: {$results['total_events']}\n\n";
    
    echo "Summary:\n";
    echo "--------\n";
    echo "✅ Correctly Assigned: {$results['summary']['correct']}\n";
    echo "⏱️  Stoppage Time: {$results['summary']['stoppage_time']}\n";
    echo "⚠️  Warnings: {$results['summary']['warnings']}\n";
    echo "❌ Errors: {$results['summary']['errors']}\n\n";
    
    if (!empty($results['matches'])) {
        echo "By Match:\n";
        echo "--------\n";
        foreach ($results['matches'] as $stats) {
            $matchId = $stats['match_id'];
            $total = $stats['event_count'];
            $correct = $stats['correct_assignments'];
            $stoppage = $stats['stoppage_time'];
            $warnings = $stats['warnings'];
            $errors = $stats['errors'];
            
            $pct = $total > 0 ? round(($correct + $stoppage) / $total * 100) : 0;
            $status = $errors > 0 ? '❌' : '✅';
            
            echo "$status Match #$matchId: $total events | $correct OK + $stoppage stoppage | $pct% valid";
            if ($errors > 0) {
                echo " | ⚠️ $errors errors";
            }
            echo "\n";
        }
    }
    
    if (!empty($results['issues'])) {
        echo "\n" . str_repeat('=', 70) . "\n";
        echo "Issues Found:\n";
        echo "--------\n";
        foreach ($results['issues'] as $issue) {
            echo "Event #{$issue['event_id']} (Match #{$issue['match_id']}): {$issue['message']}\n";
        }
    } else {
        echo "\n✅ No issues found!\n";
    }
    
    echo "\n" . str_repeat('=', 70) . "\n";
}
