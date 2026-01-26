<?php
// Admin-only web tool to preview and repair starting lineups for a single match
require_once __DIR__ . '/../../../lib/auth.php';
require_once __DIR__ . '/../../../lib/db.php';

require_auth();
$user = current_user();
$roles = $_SESSION['roles'] ?? [];
if (!in_array('platform_admin', $roles, true)) {
    http_response_code(403);
    echo 'Forbidden';
    exit;
}

$matchId = isset($_GET['match_id']) ? (int)$_GET['match_id'] : 0;
if (!$matchId) {
    http_response_code(400);
    echo 'Missing match_id';
    exit;
}

$pdo = db();

$action = ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['do_repair'])) ? 'repair' : 'preview';

// Find the next match ID in the database
$nextMatchId = null;
$stmt = $pdo->prepare('SELECT id FROM matches WHERE id > ? ORDER BY id ASC LIMIT 1');
$stmt->execute([$matchId]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);
if ($row) {
    $nextMatchId = (int)$row['id'];
}

// After repair, redirect to next match if exists
if ($action === 'repair' && $nextMatchId) {
    header('Location: /matches/' . $nextMatchId . '/repair-lineups');
    exit;
}

$players = $pdo->prepare('SELECT id, team_side, player_id, shirt_number, is_starting FROM match_players WHERE match_id = ?');
$players->execute([$matchId]);
$allPlayers = $players->fetchAll(PDO::FETCH_ASSOC);
$byTeam = ['home' => [], 'away' => []];
foreach ($allPlayers as $p) {
    $byTeam[$p['team_side']][] = $p;
}
$changes = [];
foreach ($byTeam as $side => $teamPlayers) {
    $starters = array_filter($teamPlayers, function($p) {
        return $p['shirt_number'] !== null && $p['shirt_number'] >= 1 && $p['shirt_number'] <= 11;
    });
    if (count($starters) < 11) {
        $remaining = 11 - count($starters);
        $nonStarters = array_udiff($teamPlayers, $starters, function($a, $b) { return $a['id'] <=> $b['id']; });
        $fill = array_slice($nonStarters, 0, $remaining);
        $starters = array_merge($starters, $fill);
    } else if (count($starters) > 11) {
        usort($starters, function($a, $b) { return $a['id'] <=> $b['id']; });
        $starters = array_slice($starters, 0, 11);
    }
    $starterIds = array_map(function($p) { return $p['id']; }, $starters);
    foreach ($teamPlayers as $p) {
        $shouldBeStarter = in_array($p['id'], $starterIds);
        if ((int)$p['is_starting'] !== ($shouldBeStarter ? 1 : 0)) {
            $changes[] = [
                'team_side' => $side,
                'player_id' => $p['player_id'],
                'shirt_number' => $p['shirt_number'],
                'before' => (int)$p['is_starting'],
                'after' => $shouldBeStarter ? 1 : 0,
            ];
            if ($action === 'repair') {
                $upd = $pdo->prepare('UPDATE match_players SET is_starting = ? WHERE id = ?');
                $upd->execute([$shouldBeStarter ? 1 : 0, $p['id']]);
            }
        }
    }
}

?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Repair Starting Lineups</title>
    <style>
        body { font-family: sans-serif; background: #181e29; color: #e0e6ef; }
        table { border-collapse: collapse; width: 100%; margin-top: 2em; }
        th, td { border: 1px solid #333a4d; padding: 6px 10px; }
        th { background: #232b3b; }
        tr:nth-child(even) { background: #232b3b; }
        tr:nth-child(odd) { background: #1a1f2b; }
        .btn { background: #22c55e; color: #fff; border: none; padding: 8px 18px; border-radius: 4px; font-size: 1em; cursor: pointer; margin-top: 1em; }
        .btn:disabled { background: #888; }
        .summary { margin: 1.5em 0; font-size: 1.1em; }
        .success { color: #22c55e; }
        .danger { color: #ef4444; }
    </style>
</head>
<body>
    <h1>Repair Starting Lineups for Match <?= htmlspecialchars($matchId) ?></h1>
    <div class="summary">
        <?php if ($action === 'preview'): ?>
            <span><?= count($changes) ?> changes will be made if you continue.</span>
        <?php else: ?>
            <span class="success">Repair complete. <?= count($changes) ?> changes applied.</span>
        <?php endif; ?>
    </div>
    <div style="min-height: 300px;">
    <?php if ($changes): ?>
        <table>
            <thead>
                <tr>
                    <th>Team</th>
                    <th>Player ID</th>
                    <th>Shirt #</th>
                    <th>is_starting (before)</th>
                    <th>is_starting (after)</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($changes as $chg): ?>
                <tr>
                    <td><?= htmlspecialchars(ucfirst($chg['team_side'])) ?></td>
                    <td><?= htmlspecialchars($chg['player_id']) ?></td>
                    <td><?= htmlspecialchars($chg['shirt_number']) ?></td>
                    <td class="danger"><?= htmlspecialchars($chg['before']) ?></td>
                    <td class="success"><?= htmlspecialchars($chg['after']) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p>No changes needed. All starting lineups are correct.</p>
    <?php endif; ?>
    </div>
    <div class="sticky-action-bar">
    <?php if ($action === 'preview' && $changes): ?>
        <form method="post" style="margin:0;">
            <input type="hidden" name="do_repair" value="1">
            <button class="btn" type="submit">Save Changes</button>
        </form>
    <?php elseif ($action === 'preview' && !$changes && $nextMatchId): ?>
        <form method="get" action="/matches/<?= htmlspecialchars($nextMatchId) ?>/repair-lineups" style="margin:0;">
            <button class="btn" type="submit">Skip to Next Match</button>
        </form>
    <?php endif; ?>
    </div>
</body>
    <style>
        body { font-family: sans-serif; background: #181e29; color: #e0e6ef; }
        table { border-collapse: collapse; width: 100%; margin-top: 2em; }
        th, td { border: 1px solid #333a4d; padding: 6px 10px; }
        th { background: #232b3b; }
        tr:nth-child(even) { background: #232b3b; }
        tr:nth-child(odd) { background: #1a1f2b; }
        .btn { background: #22c55e; color: #fff; border: none; padding: 8px 18px; border-radius: 4px; font-size: 1em; cursor: pointer; }
        .btn:disabled { background: #888; }
        .summary { margin: 1.5em 0; font-size: 1.1em; }
        .success { color: #22c55e; }
        .danger { color: #ef4444; }
        .sticky-action-bar {
            position: fixed;
            left: 0;
            right: 0;
            bottom: 0;
            background: #232b3b;
            padding: 16px 0 16px 0;
            box-shadow: 0 -2px 12px #000a;
            display: flex;
            justify-content: center;
            z-index: 100;
        }
    </style>
