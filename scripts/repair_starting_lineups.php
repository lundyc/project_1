<?php
// CLI script to repair historical match_players.is_starting corruption
// Usage: php repair_starting_lineups.php > repair_log.txt
//
// This script reconstructs the true starting lineup for each match using shirt numbers 1-11 as primary indicator.
// It resets is_starting for all match_players to reflect only the actual starters at kickoff.
// All changes are logged for auditability. No substitution history is altered.

require_once __DIR__ . '/../app/lib/db.php';

function log_change($msg) {
    echo $msg . "\n";
}

$pdo = db();

$matches = $pdo->query('SELECT id FROM matches')->fetchAll(PDO::FETCH_ASSOC);

foreach ($matches as $match) {
    $matchId = (int)$match['id'];
    // Fetch all match_players for this match, grouped by team_side
    $players = $pdo->prepare('SELECT id, team_side, player_id, shirt_number, is_starting FROM match_players WHERE match_id = ?');
    $players->execute([$matchId]);
    $allPlayers = $players->fetchAll(PDO::FETCH_ASSOC);
    $byTeam = ['home' => [], 'away' => []];
    foreach ($allPlayers as $p) {
        $byTeam[$p['team_side']][] = $p;
    }
    foreach ($byTeam as $side => $teamPlayers) {
        // Find starters: prefer shirt_number 1-11, fallback to earliest ids
        $starters = array_filter($teamPlayers, function($p) {
            return $p['shirt_number'] !== null && $p['shirt_number'] >= 1 && $p['shirt_number'] <= 11;
        });
        if (count($starters) < 11) {
            // Fallback: fill up to 11 with lowest id (earliest added)
            $remaining = 11 - count($starters);
            $nonStarters = array_udiff($teamPlayers, $starters, function($a, $b) { return $a['id'] <=> $b['id']; });
            $fill = array_slice($nonStarters, 0, $remaining);
            $starters = array_merge($starters, $fill);
        } else if (count($starters) > 11) {
            // If more than 11, take lowest ids
            usort($starters, function($a, $b) { return $a['id'] <=> $b['id']; });
            $starters = array_slice($starters, 0, 11);
        }
        $starterIds = array_map(function($p) { return $p['id']; }, $starters);
        // Log before/after
        foreach ($teamPlayers as $p) {
            $shouldBeStarter = in_array($p['id'], $starterIds);
            if ((int)$p['is_starting'] !== ($shouldBeStarter ? 1 : 0)) {
                log_change("Match $matchId [$side] Player {$p['player_id']} (shirt {$p['shirt_number']}): is_starting {$p['is_starting']} => " . ($shouldBeStarter ? 1 : 0));
                $upd = $pdo->prepare('UPDATE match_players SET is_starting = ? WHERE id = ?');
                $upd->execute([$shouldBeStarter ? 1 : 0, $p['id']]);
            }
        }
    }
}
log_change("Repair complete.");
