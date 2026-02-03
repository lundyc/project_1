<?php
require_auth();
require_once __DIR__ . '/../../../lib/match_repository.php';
require_once __DIR__ . '/../../../lib/team_repository.php';
require_once __DIR__ . '/../../../lib/player_repository.php';
require_once __DIR__ . '/../../../lib/club_repository.php';
require_once __DIR__ . '/../../../lib/competition_repository.php';
require_once __DIR__ . '/../../../lib/season_repository.php';
require_once __DIR__ . '/../../../lib/db.php';

$matchId = isset($_GET['match_id']) ? (int)$_GET['match_id'] : 0;
if ($matchId <= 0) {
    http_response_code(404);
    die('Invalid match ID');
}

$pdo = db();
$stmt = $pdo->prepare('SELECT * FROM league_intelligence_matches WHERE match_id = :match_id LIMIT 1');
$stmt->execute(['match_id' => $matchId]);
$fixture = $stmt->fetch();
if (!$fixture) {
    http_response_code(404);
    die('Fixture not found');
}

$homeTeam = get_team_by_id((int)$fixture['home_team_id']);
$awayTeam = get_team_by_id((int)$fixture['away_team_id']);
$homeClub = get_club_by_id((int)$homeTeam['club_id']);
$awayClub = get_club_by_id((int)$awayTeam['club_id']);
$competition = get_competition_by_id((int)$fixture['competition_id']);
$season = get_season_by_id((int)$fixture['season_id']);
$base = base_path();

function get_recent_matches($pdo, $teamId, $seasonId, $limit = 5) {
    $stmt = $pdo->prepare('SELECT * FROM league_intelligence_matches WHERE (home_team_id = :tid OR away_team_id = :tid) AND season_id = :season_id ORDER BY kickoff_at DESC LIMIT :lim');
    $stmt->bindValue(':tid', $teamId, PDO::PARAM_INT);
    $stmt->bindValue(':season_id', $seasonId, PDO::PARAM_INT);
    $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll();
}

function get_goals_for_against($matches, $teamId) {
    $for = 0; $against = 0;
    foreach ($matches as $m) {
        if ($m['home_team_id'] == $teamId) {
            $for += (int)$m['home_goals'];
            $against += (int)$m['away_goals'];
        } else {
            $for += (int)$m['away_goals'];
            $against += (int)$m['home_goals'];
        }
    }
    return [$for, $against];
}

function get_form_string($matches, $teamId) {
    $form = [];
    foreach ($matches as $m) {
        if ($m['home_team_id'] == $teamId) {
            if ($m['home_goals'] > $m['away_goals']) $form[] = 'W';
            elseif ($m['home_goals'] < $m['away_goals']) $form[] = 'L';
            else $form[] = 'D';
        } else {
            if ($m['away_goals'] > $m['home_goals']) $form[] = 'W';
            elseif ($m['away_goals'] < $m['home_goals']) $form[] = 'L';
            else $form[] = 'D';
        }
    }
    return implode(' ', $form);
}

function get_matchday($fixture) {
    return !empty($fixture['matchday']) ? 'Matchday ' . htmlspecialchars($fixture['matchday']) : '';
}

// --- 1. MATCH CONTEXT (OVERVIEW) ---

$recentHome = get_recent_matches($pdo, $homeTeam['id'], $season['id'], 5);
$recentAway = get_recent_matches($pdo, $awayTeam['id'], $season['id'], 5);
list($homeFor, $homeAgainst) = get_goals_for_against($recentHome, $homeTeam['id']);
list($awayFor, $awayAgainst) = get_goals_for_against($recentAway, $awayTeam['id']);
$homeForm = get_form_string($recentHome, $homeTeam['id']);
$awayForm = get_form_string($recentAway, $awayTeam['id']);

$kickoff = $fixture['kickoff_at'] ? date('d/m/Y H:i', strtotime($fixture['kickoff_at'])) : 'TBD';
$venue = $fixture['venue'] ?? 'TBD';
$matchday = get_matchday($fixture);

$liFlags = [];
if (!empty($fixture['flag_early_goal'])) $liFlags[] = 'Early Goal Risk';
if (!empty($fixture['flag_late_goal'])) $liFlags[] = 'Late Goal Risk';
if (!empty($fixture['flag_set_piece'])) $liFlags[] = 'Set Piece Focus';
if (!empty($fixture['flag_high_volatility'])) $liFlags[] = 'High Volatility';
if (!empty($fixture['flag_low_volatility'])) $liFlags[] = 'Low Volatility';

// Narrative
$narrative = [];
if ($homeForm) $narrative[] = "{$homeTeam['name']} recent form: $homeForm";
if ($awayForm) $narrative[] = "{$awayTeam['name']} recent form: $awayForm";
$narrative[] = "{$homeTeam['name']} goals for/against: $homeFor/$homeAgainst";
$narrative[] = "{$awayTeam['name']} goals for/against: $awayFor/$awayAgainst";
$narrative[] = "Home vs Away context: {$homeTeam['name']} at home, {$awayTeam['name']} away.";
$narrative = implode('. ', array_filter($narrative)) . '.';

// --- 2. OUR TEAM PREVIEW ---

$homeFirstHalfGoals = 0;
$homeSecondHalfGoals = 0;
$homeSetPieceGoals = 0;
$homeLateGoalsConceded = 0;
$homeFormSnapshot = [];
$homeFormation = '';
$homeRecentPlayers = [];
$homeHighMinutes = [];

if ($recentHome) {
    foreach ($recentHome as $m) {
        $midpoint = strtotime($m['kickoff_at']) + 45*60;
        if ($m['home_team_id'] == $homeTeam['id']) {
            $homeFirstHalfGoals += (int)($m['home_goals_first_half'] ?? 0);
            $homeSecondHalfGoals += (int)($m['home_goals'] - ($m['home_goals_first_half'] ?? 0));
                if (isset($m['match_second']) && (int)($m['match_second'] ?? 0) / 60 >= 75) $homeLateGoalsConceded += (int)($m['away_goals'] ?? 0);
        } else {
            $homeFirstHalfGoals += (int)($m['away_goals_first_half'] ?? 0);
            $homeSecondHalfGoals += (int)($m['away_goals'] - ($m['away_goals_first_half'] ?? 0));
                if (isset($m['match_second']) && (int)($m['match_second'] ?? 0) / 60 >= 75) $homeLateGoalsConceded += (int)($m['home_goals'] ?? 0);
        }
    }
    // Set-piece goals (event_tags)
    $spStmt = $pdo->prepare('
        SELECT COUNT(*) FROM events e
        JOIN event_tags et ON e.id = et.event_id
        JOIN tags t ON et.tag_id = t.id
        WHERE e.match_id IN (
            SELECT match_id FROM league_intelligence_matches WHERE home_team_id = :tid OR away_team_id = :tid
        )
        AND t.label LIKE :tag
    ');
    $spStmt->execute(['tid' => $homeTeam['id'], 'tag' => '%set piece%']);
    $homeSetPieceGoals = (int)$spStmt->fetchColumn();
    // Most used formation
    // Get all match_ids for this team
    $matchIdStmt = $pdo->prepare('SELECT match_id, home_team_id, away_team_id FROM league_intelligence_matches WHERE home_team_id = :tid OR away_team_id = :tid');
    $matchIdStmt->execute(['tid' => $homeTeam['id']]);
    $matchIds = [];
    foreach ($matchIdStmt->fetchAll() as $m) {
        $matchIds[] = $m['match_id'];
    }
    $homeFormation = null;
    if (!empty($matchIds)) {
        $in = str_repeat('?,', count($matchIds) - 1) . '?';
        $params = $matchIds;
        $params[] = 'home';
        $sql = "SELECT mf.formation_key, mf.format, COUNT(*) as cnt, f.label FROM match_formations mf JOIN formations f ON mf.formation_key = f.formation_key AND mf.format = f.format WHERE mf.match_id IN ($in) AND mf.team_side = ? GROUP BY mf.formation_key, mf.format ORDER BY cnt DESC LIMIT 1";
        $formStmt = $pdo->prepare($sql);
        $formStmt->execute($params);
        $row = $formStmt->fetch();
        if ($row) {
            $homeFormation = $row['label'];
        }
    }
    // Recent starting players (last 5 matches)
    $recentMatchIds = array_slice($matchIds, 0, 5);
    $inRecent = $recentMatchIds ? (str_repeat('?,', count($recentMatchIds) - 1) . '?') : 'NULL';
    $paramsRecent = $recentMatchIds;
    $paramsRecent[] = 'home';
    $playerSql = "SELECT p.id, p.first_name, p.last_name, COUNT(mp.id) as starts FROM match_players mp JOIN players p ON mp.player_id = p.id WHERE mp.match_id IN ($inRecent) AND mp.team_side = ? AND mp.is_starting = 1 GROUP BY p.id ORDER BY starts DESC LIMIT 11";
    $playerStmt = $pdo->prepare($playerSql);
    $playerStmt->execute($paramsRecent);
    $homeRecentPlayers = $playerStmt->fetchAll();
    // High starts load (players who started all recent matches)
    $highMinPlayers = array_filter($homeRecentPlayers, function($p) use ($recentMatchIds) {
        return $p['starts'] == count($recentMatchIds);
    });
    $homeHighMinutes = $highMinPlayers;
}

// --- 3. OPPONENT & MATCH PROFILE ---

$oppCleanSheets = 0;
$oppHighScoring = 0;
$oppLowScoring = 0;
$oppLateGoalsScored = 0;
$oppLateGoalsConceded = 0;
$oppVolatility = '';
if ($recentAway) {
    foreach ($recentAway as $m) {
        $gf = ($m['home_team_id'] == $awayTeam['id']) ? (int)$m['home_goals'] : (int)$m['away_goals'];
        $ga = ($m['home_team_id'] == $awayTeam['id']) ? (int)$m['away_goals'] : (int)$m['home_goals'];
        if ($ga == 0) $oppCleanSheets++;
        if (($gf + $ga) >= 4) $oppHighScoring++;
        if (($gf + $ga) <= 1) $oppLowScoring++;
           if (isset($m['match_second']) && (int)($m['match_second'] ?? 0) / 60 >= 75 && $gf > 0) $oppLateGoalsScored++;
           if (isset($m['match_second']) && (int)($m['match_second'] ?? 0) / 60 >= 75 && $ga > 0) $oppLateGoalsConceded++;
    }
    if ($oppHighScoring > $oppLowScoring) $oppVolatility = 'High';
    elseif ($oppLowScoring > $oppHighScoring) $oppVolatility = 'Low';
    else $oppVolatility = 'Moderate';
}

// --- 4. KEY INSIGHTS & PREPARATION NOTES ---

$insights = [];
if ($homeLateGoalsConceded > 0) $insights[] = "Avoid conceding late: {$homeLateGoalsConceded} goals conceded after 75’ in last 5.";
if ($oppLateGoalsScored > 0) $insights[] = "Opponent scores late: {$oppLateGoalsScored} goals after 75’ in last 5.";
if ($homeSetPieceGoals > 0) $insights[] = "Set-piece threat: {$homeSetPieceGoals} set-piece goals in recent matches.";
if ($oppCleanSheets > 2) $insights[] = "Opponent has {$oppCleanSheets} clean sheets in last 5.";
if ($oppVolatility === 'High') $insights[] = "Opponent matches are high scoring.";
if ($oppVolatility === 'Low') $insights[] = "Opponent matches are low scoring.";
if (empty($insights)) $insights[] = "No clear trends detected. Focus on fundamentals.";

// --- RENDER PAGE ---

ob_start();
$headerTitle = 'Match Preview';
$headerDescription = htmlspecialchars($homeTeam['name']) . ' vs ' . htmlspecialchars($awayTeam['name']) . ' · ' . htmlspecialchars($competition['name']) . ' · ' . htmlspecialchars($season['name']);
$headerButtons = [];
include __DIR__ . '/../../partials/header.php';
?>
<link rel="stylesheet" href="/assets/css/stats-table.css">
<div class="w-full mt-4 text-slate-200">
    <div class="max-w-full">
        <div class="grid grid-cols-12 gap-2 px-4 md:px-6 lg:px-8 w-full">
            <!-- Left Sidebar -->
            <aside class="col-span-2 space-y-4 min-w-0">
                <div class="rounded-xl bg-slate-900/80 border border-white/10 p-3 mb-4">
                    <nav class="flex flex-col gap-2 mb-3" role="tablist" aria-label="Statistics tabs">
                        <button type="button" class="stats-tab w-full text-left px-4 py-2.5 text-sm font-medium rounded-lg border transition-all duration-200 bg-indigo-600 border-indigo-500 text-white shadow-lg shadow-indigo-500/20" role="tab" aria-selected="true" data-tab-id="overview" onclick="showTab('overview')">
                            Overview
                        </button>
                        <button type="button" class="stats-tab w-full text-left px-4 py-2.5 text-sm font-medium rounded-lg border transition-all duration-200 bg-slate-800/40 border-white/10 text-slate-300 hover:bg-slate-700/50 hover:border-white/20" role="tab" aria-selected="false" data-tab-id="our-team" onclick="showTab('our-team')">
                            Our Team
                        </button>
                        <button type="button" class="stats-tab w-full text-left px-4 py-2.5 text-sm font-medium rounded-lg border transition-all duration-200 bg-slate-800/40 border-white/10 text-slate-300 hover:bg-slate-700/50 hover:border-white/20" role="tab" aria-selected="false" data-tab-id="opponent" onclick="showTab('opponent')">
                            Opponent
                        </button>
                        <button type="button" class="stats-tab w-full text-left px-4 py-2.5 text-sm font-medium rounded-lg border transition-all duration-200 bg-slate-800/40 border-white/10 text-slate-300 hover:bg-slate-700/50 hover:border-white/20" role="tab" aria-selected="false" data-tab-id="insights" onclick="showTab('insights')">
                            Key Insights
                        </button>
                    </nav>
                </div>
            </aside>
            <!-- Main Content -->
            <main class="col-span-7 space-y-4 min-w-0">
                <!-- Overview Tab -->
                <div id="tab-overview" class="tab-content">
                    <div class="rounded-2xl bg-slate-800 border border-white/10 p-6 shadow-xl">
                        <div class="mb-6 flex flex-col items-center justify-center gap-2">
                            <h1 class="text-3xl font-extrabold text-white mb-1 tracking-tight"><?= htmlspecialchars($competition['name']) ?></h1>
                            <div class="text-base text-slate-400 mb-1"><?= htmlspecialchars($season['name']) ?> <?= $matchday ? '· ' . $matchday : '' ?></div>
                            <div class="flex flex-col items-center justify-center mb-2">
                                <span class="text-3xl font-extrabold text-indigo-400 drop-shadow-sm"><?= htmlspecialchars($homeTeam['name']) ?></span>
                                <span class="text-lg font-semibold text-slate-400">vs</span>
                                <span class="text-3xl font-extrabold text-pink-400 drop-shadow-sm"><?= htmlspecialchars($awayTeam['name']) ?></span>
                            </div>
                            <div class="text-xs text-slate-400 mb-2">Kickoff: <?= $kickoff ?> · Venue: <?= htmlspecialchars($venue) ?></div>
                            <?php if ($liFlags): ?>
                                <div class="mb-2">
                                    <span class="inline-block bg-yellow-700 text-yellow-200 text-xs px-2 py-1 rounded mr-2 font-semibold tracking-wide">Flags: <?= implode(', ', $liFlags) ?></span>
                                </div>
                            <?php endif; ?>
                        </div>
                        <!-- Stat Cards Row -->
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
                            <div class="rounded-xl bg-slate-900/80 border border-indigo-700/30 p-4 flex flex-col items-center shadow">
                                <div class="text-xs uppercase text-slate-400 font-bold tracking-wider mb-1">Goals For</div>
                                <div class="text-3xl font-extrabold text-green-400"><?= $homeFor ?></div>
                            </div>
                            <div class="rounded-xl bg-slate-900/80 border border-pink-700/30 p-4 flex flex-col items-center shadow">
                                <div class="text-xs uppercase text-slate-400 font-bold tracking-wider mb-1">Goals Against</div>
                                <div class="text-3xl font-extrabold text-red-400"><?= $homeAgainst ?></div>
                            </div>
                            <div class="rounded-xl bg-slate-900/80 border border-yellow-600/30 p-4 flex flex-col items-center shadow">
                                <div class="text-xs uppercase text-slate-400 font-bold tracking-wider mb-1">Set-Piece Goals</div>
                                <div class="text-3xl font-extrabold text-yellow-300"><?= $homeSetPieceGoals ?></div>
                            </div>
                            <div class="rounded-xl bg-slate-900/80 border border-rose-700/30 p-4 flex flex-col items-center shadow">
                                <div class="text-xs uppercase text-slate-400 font-bold tracking-wider mb-1">Late Goals Conceded</div>
                                <div class="text-3xl font-extrabold text-rose-300"><?= $homeLateGoalsConceded ?></div>
                            </div>
                        </div>
                        <!-- Form Pills -->
                        <div class="mb-6 flex flex-col md:flex-row md:items-center md:gap-4">
                            <div class="text-xs uppercase text-slate-400 font-bold tracking-wider mb-1 md:mb-0">Recent Form</div>
                            <div class="flex gap-1">
                                <?php foreach (explode(' ', $homeForm) as $f): ?>
                                    <?php if ($f === 'W'): ?>
                                        <span class="inline-block w-7 h-7 rounded-full bg-green-500 text-white text-center font-bold leading-7">W</span>
                                    <?php elseif ($f === 'D'): ?>
                                        <span class="inline-block w-7 h-7 rounded-full bg-yellow-400 text-white text-center font-bold leading-7">D</span>
                                    <?php elseif ($f === 'L'): ?>
                                        <span class="inline-block w-7 h-7 rounded-full bg-red-500 text-white text-center font-bold leading-7">L</span>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <!-- Simple Bar Charts -->
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-4">
                            <!-- First Half vs Second Half Goals -->
                            <div class="flex flex-col items-center">
                                <div class="text-xs uppercase text-slate-400 font-bold tracking-wider mb-1">First vs Second Half Goals</div>
                                <div class="flex items-end gap-2 h-12">
                                    <div class="flex flex-col items-center">
                                        <div class="bg-blue-500/80 w-7 rounded-t-lg" style="height:<?= max(8, $homeFirstHalfGoals*8) ?>px"></div>
                                        <span class="text-xs text-slate-300 mt-1">1st</span>
                                        <span class="text-xs text-slate-400"><?= $homeFirstHalfGoals ?></span>
                                    </div>
                                    <div class="flex flex-col items-center">
                                        <div class="bg-indigo-400/80 w-7 rounded-t-lg" style="height:<?= max(8, $homeSecondHalfGoals*8) ?>px"></div>
                                        <span class="text-xs text-slate-300 mt-1">2nd</span>
                                        <span class="text-xs text-slate-400"><?= $homeSecondHalfGoals ?></span>
                                    </div>
                                </div>
                            </div>
                            <!-- Goals For vs Against -->
                            <div class="flex flex-col items-center">
                                <div class="text-xs uppercase text-slate-400 font-bold tracking-wider mb-1">Goals For vs Against</div>
                                <div class="flex items-end gap-2 h-12">
                                    <div class="flex flex-col items-center">
                                        <div class="bg-green-500/80 w-7 rounded-t-lg" style="height:<?= max(8, $homeFor*8) ?>px"></div>
                                        <span class="text-xs text-slate-300 mt-1">For</span>
                                        <span class="text-xs text-slate-400"><?= $homeFor ?></span>
                                    </div>
                                    <div class="flex flex-col items-center">
                                        <div class="bg-red-500/80 w-7 rounded-t-lg" style="height:<?= max(8, $homeAgainst*8) ?>px"></div>
                                        <span class="text-xs text-slate-300 mt-1">Agst</span>
                                        <span class="text-xs text-slate-400"><?= $homeAgainst ?></span>
                                    </div>
                                </div>
                            </div>
                            <!-- Late Goals Impact -->
                            <div class="flex flex-col items-center">
                                <div class="text-xs uppercase text-slate-400 font-bold tracking-wider mb-1">Late Goals Impact</div>
                                <div class="flex items-end gap-2 h-12">
                                    <div class="flex flex-col items-center">
                                        <div class="bg-rose-400/80 w-7 rounded-t-lg" style="height:<?= max(8, $homeLateGoalsConceded*8) ?>px"></div>
                                        <span class="text-xs text-slate-300 mt-1">Concd</span>
                                        <span class="text-xs text-slate-400"><?= $homeLateGoalsConceded ?></span>
                                    </div>
                                    <div class="flex flex-col items-center">
                                        <div class="bg-yellow-300/80 w-7 rounded-t-lg" style="height:<?= max(8, $homeSetPieceGoals*8) ?>px"></div>
                                        <span class="text-xs text-slate-300 mt-1">SetPc</span>
                                        <span class="text-xs text-slate-400"><?= $homeSetPieceGoals ?></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Our Team Tab -->
                <div id="tab-our-team" class="tab-content" style="display:none;">
                    <div class="rounded-2xl bg-slate-800 border border-white/10 p-6 shadow-xl space-y-6">
                        <h2 class="text-xl font-bold text-white mb-4 tracking-tight">Our Team Preview</h2>
                        <!-- Stat Cards Row -->
                        <div class="grid grid-cols-2 md:grid-cols-3 gap-4 mb-4">
                            <div class="rounded-xl bg-slate-900/80 border border-indigo-700/30 p-4 flex flex-col items-center shadow">
                                <div class="text-xs uppercase text-slate-400 font-bold tracking-wider mb-1">Goals For</div>
                                <div class="text-2xl font-extrabold text-green-400"><?= $homeFor ?></div>
                            </div>
                            <div class="rounded-xl bg-slate-900/80 border border-pink-700/30 p-4 flex flex-col items-center shadow">
                                <div class="text-xs uppercase text-slate-400 font-bold tracking-wider mb-1">Goals Against</div>
                                <div class="text-2xl font-extrabold text-red-400"><?= $homeAgainst ?></div>
                            </div>
                            <div class="rounded-xl bg-slate-900/80 border border-yellow-600/30 p-4 flex flex-col items-center shadow">
                                <div class="text-xs uppercase text-slate-400 font-bold tracking-wider mb-1">Set-Piece Goals</div>
                                <div class="text-2xl font-extrabold text-yellow-300"><?= $homeSetPieceGoals ?></div>
                            </div>
                        </div>
                        <!-- Form Pills -->
                        <div class="mb-4 flex flex-col md:flex-row md:items-center md:gap-4">
                            <div class="text-xs uppercase text-slate-400 font-bold tracking-wider mb-1 md:mb-0">Recent Form</div>
                            <div class="flex gap-1">
                                <?php foreach (explode(' ', $homeForm) as $f): ?>
                                    <?php if ($f === 'W'): ?>
                                        <span class="inline-block w-7 h-7 rounded-full bg-green-500 text-white text-center font-bold leading-7">W</span>
                                    <?php elseif ($f === 'D'): ?>
                                        <span class="inline-block w-7 h-7 rounded-full bg-yellow-400 text-white text-center font-bold leading-7">D</span>
                                    <?php elseif ($f === 'L'): ?>
                                        <span class="inline-block w-7 h-7 rounded-full bg-red-500 text-white text-center font-bold leading-7">L</span>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <!-- Simple Bar Charts -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-4">
                            <!-- First Half vs Second Half Goals -->
                            <div class="flex flex-col items-center">
                                <div class="text-xs uppercase text-slate-400 font-bold tracking-wider mb-1">First vs Second Half Goals</div>
                                <div class="flex items-end gap-2 h-12">
                                    <div class="flex flex-col items-center">
                                        <div class="bg-blue-500/80 w-7 rounded-t-lg" style="height:<?= max(8, $homeFirstHalfGoals*8) ?>px"></div>
                                        <span class="text-xs text-slate-300 mt-1">1st</span>
                                        <span class="text-xs text-slate-400"><?= $homeFirstHalfGoals ?></span>
                                    </div>
                                    <div class="flex flex-col items-center">
                                        <div class="bg-indigo-400/80 w-7 rounded-t-lg" style="height:<?= max(8, $homeSecondHalfGoals*8) ?>px"></div>
                                        <span class="text-xs text-slate-300 mt-1">2nd</span>
                                        <span class="text-xs text-slate-400"><?= $homeSecondHalfGoals ?></span>
                                    </div>
                                </div>
                            </div>
                            <!-- Goals For vs Against -->
                            <div class="flex flex-col items-center">
                                <div class="text-xs uppercase text-slate-400 font-bold tracking-wider mb-1">Goals For vs Against</div>
                                <div class="flex items-end gap-2 h-12">
                                    <div class="flex flex-col items-center">
                                        <div class="bg-green-500/80 w-7 rounded-t-lg" style="height:<?= max(8, $homeFor*8) ?>px"></div>
                                        <span class="text-xs text-slate-300 mt-1">For</span>
                                        <span class="text-xs text-slate-400"><?= $homeFor ?></span>
                                    </div>
                                    <div class="flex flex-col items-center">
                                        <div class="bg-red-500/80 w-7 rounded-t-lg" style="height:<?= max(8, $homeAgainst*8) ?>px"></div>
                                        <span class="text-xs text-slate-300 mt-1">Agst</span>
                                        <span class="text-xs text-slate-400"><?= $homeAgainst ?></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- Formation & Players -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="rounded-xl bg-slate-900/70 border border-white/10 p-4 flex flex-col gap-2 shadow">
                                <div class="flex items-center gap-2 mb-2">
                                    <span class="text-xs uppercase text-slate-400 font-bold tracking-wider">Most Used Formation</span>
                                    <?php if ($homeFormation): ?>
                                        <span class="inline-block bg-indigo-600 text-white text-xs font-semibold px-3 py-1 rounded-full ml-2 shadow"> <?= htmlspecialchars($homeFormation) ?> </span>
                                    <?php endif; ?>
                                </div>
                                <div class="text-xs text-slate-400">(Last 5 matches)</div>
                            </div>
                            <div class="rounded-xl bg-slate-900/70 border border-white/10 p-4 flex flex-col gap-2 shadow">
                                <span class="text-xs uppercase text-slate-400 font-bold tracking-wider mb-1">Recent Starting Players</span>
                                <div class="flex flex-wrap gap-2">
                                    <?php foreach ($homeRecentPlayers as $p): ?>
                                        <span class="inline-block bg-slate-700 text-white text-xs font-semibold px-3 py-1 rounded-full shadow"> <?= htmlspecialchars($p['first_name'] . ' ' . $p['last_name']) ?> (<?= $p['starts'] ?>) </span>
                                    <?php endforeach; ?>
                                </div>
                                <span class="text-xs uppercase text-slate-400 font-bold tracking-wider mt-2">High Minutes Load</span>
                                <div class="flex flex-wrap gap-2">
                                    <?php foreach ($homeHighMinutes as $p): ?>
                                        <span class="inline-block bg-rose-700 text-white text-xs font-semibold px-3 py-1 rounded-full shadow"> <?= htmlspecialchars($p['first_name'] . ' ' . $p['last_name']) ?> (<?= $p['starts'] ?>) </span>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Opponent Tab -->
                <div id="tab-opponent" class="tab-content" style="display:none;">
                    <div class="rounded-2xl bg-slate-800 border border-white/10 p-6 shadow-xl space-y-6">
                        <h2 class="text-xl font-bold text-white mb-4 tracking-tight">Opponent Match Profile</h2>
                        <!-- Stat Cards Row -->
                        <div class="grid grid-cols-2 md:grid-cols-3 gap-4 mb-4">
                            <div class="rounded-xl bg-slate-900/80 border border-indigo-700/30 p-4 flex flex-col items-center shadow">
                                <div class="text-xs uppercase text-slate-400 font-bold tracking-wider mb-1">Goals For</div>
                                <div class="text-2xl font-extrabold text-green-400"><?= $awayFor ?></div>
                            </div>
                            <div class="rounded-xl bg-slate-900/80 border border-pink-700/30 p-4 flex flex-col items-center shadow">
                                <div class="text-xs uppercase text-slate-400 font-bold tracking-wider mb-1">Goals Against</div>
                                <div class="text-2xl font-extrabold text-red-400"><?= $awayAgainst ?></div>
                            </div>
                            <div class="rounded-xl bg-slate-900/80 border border-yellow-600/30 p-4 flex flex-col items-center shadow">
                                <div class="text-xs uppercase text-slate-400 font-bold tracking-wider mb-1">Clean Sheets</div>
                                <div class="text-2xl font-extrabold text-yellow-300"><?= $oppCleanSheets ?></div>
                            </div>
                        </div>
                        <!-- Form Pills -->
                        <div class="mb-4 flex flex-col md:flex-row md:items-center md:gap-4">
                            <div class="text-xs uppercase text-slate-400 font-bold tracking-wider mb-1 md:mb-0">Recent Form</div>
                            <div class="flex gap-1">
                                <?php foreach (explode(' ', $awayForm) as $f): ?>
                                    <?php if ($f === 'W'): ?>
                                        <span class="inline-block w-7 h-7 rounded-full bg-green-500 text-white text-center font-bold leading-7">W</span>
                                    <?php elseif ($f === 'D'): ?>
                                        <span class="inline-block w-7 h-7 rounded-full bg-yellow-400 text-white text-center font-bold leading-7">D</span>
                                    <?php elseif ($f === 'L'): ?>
                                        <span class="inline-block w-7 h-7 rounded-full bg-red-500 text-white text-center font-bold leading-7">L</span>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <!-- Simple Bar Charts -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-4">
                            <!-- High/Low Scoring Games -->
                            <div class="flex flex-col items-center">
                                <div class="text-xs uppercase text-slate-400 font-bold tracking-wider mb-1">High vs Low Scoring</div>
                                <div class="flex items-end gap-2 h-12">
                                    <div class="flex flex-col items-center">
                                        <div class="bg-green-500/80 w-7 rounded-t-lg" style="height:<?= max(8, $oppHighScoring*8) ?>px"></div>
                                        <span class="text-xs text-slate-300 mt-1">High</span>
                                        <span class="text-xs text-slate-400"><?= $oppHighScoring ?></span>
                                    </div>
                                    <div class="flex flex-col items-center">
                                        <div class="bg-yellow-400/80 w-7 rounded-t-lg" style="height:<?= max(8, $oppLowScoring*8) ?>px"></div>
                                        <span class="text-xs text-slate-300 mt-1">Low</span>
                                        <span class="text-xs text-slate-400"><?= $oppLowScoring ?></span>
                                    </div>
                                </div>
                            </div>
                            <!-- Late Goals Impact -->
                            <div class="flex flex-col items-center">
                                <div class="text-xs uppercase text-slate-400 font-bold tracking-wider mb-1">Late Goals Impact</div>
                                <div class="flex items-end gap-2 h-12">
                                    <div class="flex flex-col items-center">
                                        <div class="bg-rose-400/80 w-7 rounded-t-lg" style="height:<?= max(8, $oppLateGoalsScored*8) ?>px"></div>
                                        <span class="text-xs text-slate-300 mt-1">Scored</span>
                                        <span class="text-xs text-slate-400"><?= $oppLateGoalsScored ?></span>
                                    </div>
                                    <div class="flex flex-col items-center">
                                        <div class="bg-yellow-300/80 w-7 rounded-t-lg" style="height:<?= max(8, $oppLateGoalsConceded*8) ?>px"></div>
                                        <span class="text-xs text-slate-300 mt-1">Concd</span>
                                        <span class="text-xs text-slate-400"><?= $oppLateGoalsConceded ?></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- Volatility Card -->
                        <div class="rounded-xl bg-slate-900/70 border border-white/10 p-4 flex flex-col items-center shadow">
                            <div class="text-xs uppercase text-slate-400 font-bold tracking-wider mb-1">Match Volatility</div>
                            <div class="text-lg font-bold text-indigo-400"><?= htmlspecialchars($oppVolatility) ?></div>
                        </div>
                    </div>
                </div>
                <!-- Key Insights Tab -->
                <div id="tab-insights" class="tab-content" style="display:none;">
                    <div class="rounded-2xl bg-slate-800 border border-white/10 p-6 shadow-xl space-y-6">
                        <h2 class="text-xl font-bold text-white mb-4 tracking-tight">Key Insights & Preparation Notes</h2>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <?php foreach ($insights as $insight): ?>
                                <div class="flex items-start gap-3 rounded-xl border-l-4 border-indigo-500 bg-slate-900/80 p-4 shadow">
                                    <span class="inline-block w-4 h-4 mt-1 rounded-full bg-indigo-500 flex-shrink-0"></span>
                                    <div class="flex-1">
                                        <span class="block text-base font-semibold text-white mb-1">
                                            <svg class="inline-block w-5 h-5 mr-1 text-indigo-400 align-text-bottom" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M12 20.5C7.305 20.5 3.5 16.695 3.5 12S7.305 3.5 12 3.5 20.5 7.305 20.5 12 16.695 20.5 12 20.5z"/></svg>
                                            <?= htmlspecialchars($insight) ?>
                                        </span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <!-- Placeholder for future manual notes -->
                        <div class="text-xs text-slate-500 mt-2">[Manual notes can be added here by coaching staff]</div>
                    </div>
                </div>
            </main>
            <!-- Right Sidebar -->
            <aside class="stats-col-right col-span-3 space-y-6 min-w-0">
                <div class="rounded-2xl bg-slate-800 border border-white/10 p-6 mb-6 shadow-lg">
                    <h5 class="text-slate-200 font-semibold mb-2 text-lg">Opponent Recent Results</h5>
                    <ul class="space-y-2 text-sm">
                        <?php foreach ($recentAway as $r): ?>
                            <li class="flex items-center gap-2">
                                <span class="inline-block w-20 text-slate-400 text-xs"><?= date('d M Y', strtotime($r['kickoff_at'])) ?></span>
                                <span class="flex-1">
                                    <?= htmlspecialchars(get_team_by_id($r['home_team_id'])['name'] ?? '') ?> vs <?= htmlspecialchars(get_team_by_id($r['away_team_id'])['name'] ?? '') ?>
                                </span>
                                <span class="text-slate-400 font-mono text-xs"><?php if ($r['home_goals'] !== null && $r['away_goals'] !== null): ?><?= $r['home_goals'] . ' - ' . $r['away_goals'] ?><?php else: ?>TBD<?php endif; ?></span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </aside>
        </div>
    </div>
</div>
<script>
function showTab(tabId) {
    document.querySelectorAll('.tab-content').forEach(function(el) {
        el.style.display = 'none';
    });
    var tab = document.getElementById('tab-' + tabId);
    if (tab) tab.style.display = 'block';
    document.querySelectorAll('.stats-tab').forEach(function(btn) {
        btn.classList.remove('bg-indigo-600', 'border-indigo-500', 'text-white', 'shadow-lg', 'shadow-indigo-500/20');
        btn.classList.add('bg-slate-800/40', 'border-white/10', 'text-slate-300');
    });
    var activeBtn = document.querySelector('[data-tab-id=\"' + tabId + '\"]');
    if (activeBtn) {
        activeBtn.classList.add('bg-indigo-600', 'border-indigo-500', 'text-white', 'shadow-lg', 'shadow-indigo-500/20');
        activeBtn.classList.remove('bg-slate-800/40', 'border-white/10', 'text-slate-300');
    }
}
window.onload = function() {
    showTab('overview');
};
</script>
<?php
$content = ob_get_clean();
require __DIR__ . '/../../layout.php';
?>