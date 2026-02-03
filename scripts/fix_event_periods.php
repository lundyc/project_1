<?php
/**
 * Script to fix misassigned event periods.
 * 
 * This script recalculates the period_id and minute_extra for all events
 * using the corrected calculate_period_from_event_time() function.
 * 
 * Usage: php scripts/fix_event_periods.php [--dry-run]
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../app/lib/db.php';
require_once __DIR__ . '/../app/lib/event_validation.php';

$dryRun = in_array('--dry-run', $argv, true);

if ($dryRun) {
    echo "🔍 DRY RUN MODE - No changes will be made\n";
} else {
    echo "⚠️  LIVE MODE - Changes will be committed to database\n";
}
echo str_repeat('=', 70) . "\n\n";

try {
    $pdo = db();
    
    // Get all events with their current period assignments
    $stmt = $pdo->query(
        'SELECT 
            e.id,
            e.match_id,
            e.match_second,
            e.period_id AS old_period_id,
            e.minute_extra AS old_minute_extra,
            mp.period_key AS old_period_key,
            mp.label AS old_period_label,
            mp.end_second AS old_period_end
        FROM events e
        JOIN match_periods mp ON mp.id = e.period_id
        ORDER BY e.match_id, e.match_second'
    );
    
    $events = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $corrected = 0;
    $unchanged = 0;
    $corrections = [];
    
    foreach ($events as $event) {
        $matchId = (int)$event['match_id'];
        $matchSecond = (int)$event['match_second'];
        $oldPeriodId = (int)$event['old_period_id'];
        $oldMinuteExtra = (int)$event['old_minute_extra'];
        
        // Calculate correct period using the fixed algorithm
        $calculated = calculate_period_from_event_time($matchId, $matchSecond);
        $newPeriodId = $calculated['period_id'];
        $newMinuteExtra = $calculated['minute_extra'];
        
        // Check if anything changed
        if ($newPeriodId !== $oldPeriodId || $newMinuteExtra !== $oldMinuteExtra) {
            // Get info about the new period
            $periodStmt = $pdo->prepare(
                'SELECT period_key, label FROM match_periods WHERE id = :id AND match_id = :match_id'
            );
            $periodStmt->execute(['id' => $newPeriodId, 'match_id' => $matchId]);
            $newPeriodInfo = $periodStmt->fetch(PDO::FETCH_ASSOC);
            
            $corrections[] = [
                'event_id' => (int)$event['id'],
                'match_id' => $matchId,
                'match_second' => $matchSecond,
                'old_period_id' => $oldPeriodId,
                'old_period_key' => $event['old_period_key'],
                'old_minute_extra' => $oldMinuteExtra,
                'new_period_id' => $newPeriodId,
                'new_period_key' => $newPeriodInfo['period_key'] ?? 'UNKNOWN',
                'new_minute_extra' => $newMinuteExtra,
            ];
            $corrected++;
        } else {
            $unchanged++;
        }
    }
    
    echo "Summary:\n";
    echo "--------\n";
    echo "Total events: " . count($events) . "\n";
    echo "Unchanged: $unchanged\n";
    echo "Require correction: $corrected\n";
    echo "\n";
    
    if (empty($corrections)) {
        echo "✅ All events are correctly assigned!\n";
    } else {
        // Group by match for easier reading
        $byMatch = [];
        foreach ($corrections as $corr) {
            $matchId = $corr['match_id'];
            if (!isset($byMatch[$matchId])) {
                $byMatch[$matchId] = [];
            }
            $byMatch[$matchId][] = $corr;
        }
        
        foreach ($byMatch as $matchId => $matchCorrections) {
            echo "Match ID: $matchId\n";
            echo "Events to correct: " . count($matchCorrections) . "\n";
            
            foreach ($matchCorrections as $corr) {
                $eventId = $corr['event_id'];
                $matchSec = $corr['match_second'];
                $oldPeriod = $corr['old_period_key'];
                $newPeriod = $corr['new_period_key'];
                $oldMinExtra = $corr['old_minute_extra'];
                $newMinExtra = $corr['new_minute_extra'];
                
                echo "  Event #$eventId @ {$matchSec}s: $oldPeriod (extra:{$oldMinExtra}min) → $newPeriod (extra:{$newMinExtra}min)\n";
            }
            echo "\n";
        }
        
        if (!$dryRun) {
            echo "📝 Applying corrections...\n";
            $updateStmt = $pdo->prepare(
                'UPDATE events SET period_id = :period_id, minute_extra = :minute_extra WHERE id = :id'
            );
            
            foreach ($corrections as $corr) {
                $updateStmt->execute([
                    'id' => $corr['event_id'],
                    'period_id' => $corr['new_period_id'],
                    'minute_extra' => $corr['new_minute_extra'],
                ]);
            }
            
            echo "✅ Fixed $corrected events\n";
        } else {
            echo "\n(Use without --dry-run flag to apply these corrections)\n";
        }
    }
    
    echo "\n" . str_repeat('=', 70) . "\n";
    
} catch (\Throwable $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
    exit(1);
}
