<?php

require_once __DIR__ . '/../../lib/auth.php';
require_once __DIR__ . '/../../lib/csrf.php';
require_once __DIR__ . '/../../lib/db.php';
require_once __DIR__ . '/../../lib/match_permissions.php';

auth_boot();
require_auth();

// Validate CSRF token for state-changing operation
try {
    require_csrf_token();
} catch (CsrfException $e) {
    http_response_code(403);
    die('Invalid CSRF token');
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    http_response_code(405);
    exit;
}

$user = current_user();
$roles = $_SESSION['roles'] ?? [];
$isPlatformAdmin = in_array('platform_admin', $roles, true);

if (!can_manage_matches($user, $roles)) {
    $_SESSION['match_form_error'] = 'You do not have permission to accept fixtures';
    redirect('/matches');
}

$liMatchId = (int)($_POST['li_match_id'] ?? 0);
if ($liMatchId <= 0) {
    $_SESSION['match_form_error'] = 'Invalid fixture request';
    redirect('/matches');
}

$requestedClubId = $isPlatformAdmin ? (int)($_POST['club_id'] ?? 0) : (int)($user['club_id'] ?? 0);

$redirectInput = trim((string)($_POST['redirect'] ?? ''));
$redirectPath = '/matches';
if ($redirectInput !== '') {
    $parsed = parse_url($redirectInput);
    $path = $parsed['path'] ?? '';
    $query = $parsed['query'] ?? '';
    if ($path !== '' && str_starts_with($path, '/matches')) {
        $redirectPath = $path . ($query ? ('?' . $query) : '');
    }
}

try {
    $pdo = db();

    $stmt = $pdo->prepare('
        SELECT
            lim.match_id,
            lim.competition_id,
            lim.season_id,
            lim.home_team_id,
            lim.away_team_id,
            lim.kickoff_at,
            lim.status,
            ht.club_id AS home_club_id,
            at.club_id AS away_club_id,
            comp.season_id AS competition_season_id
        FROM league_intelligence_matches lim
        JOIN teams ht ON ht.id = lim.home_team_id
        JOIN teams at ON at.id = lim.away_team_id
        LEFT JOIN competitions comp ON comp.id = lim.competition_id
        WHERE lim.match_id = :match_id
        LIMIT 1
    ');
    $stmt->execute(['match_id' => $liMatchId]);
    $fixture = $stmt->fetch();

    if (!$fixture) {
        $_SESSION['match_form_error'] = 'Fixture not found';
        redirect($redirectPath);
    }

    $fixtureStatus = strtolower(trim((string)($fixture['status'] ?? '')));
    if ($fixtureStatus !== 'scheduled') {
        $_SESSION['match_form_error'] = 'Only scheduled fixtures can be accepted';
        redirect($redirectPath);
    }

    $homeClubId = (int)($fixture['home_club_id'] ?? 0);
    $awayClubId = (int)($fixture['away_club_id'] ?? 0);

    if ($requestedClubId <= 0 && $isPlatformAdmin) {
        if ($homeClubId > 0 && $homeClubId === $awayClubId) {
            $requestedClubId = $homeClubId;
        }
    }

    if ($requestedClubId <= 0) {
        $_SESSION['match_form_error'] = 'Club context is required to accept fixtures';
        redirect($redirectPath);
    }

    if ($homeClubId !== $requestedClubId && $awayClubId !== $requestedClubId) {
        $_SESSION['match_form_error'] = 'Fixture does not belong to your club';
        redirect($redirectPath);
    }

    $homeTeamId = (int)$fixture['home_team_id'];
    $awayTeamId = (int)$fixture['away_team_id'];
    $competitionId = $fixture['competition_id'] !== null ? (int)$fixture['competition_id'] : null;
    $kickoffAt = $fixture['kickoff_at'] ?: null;

    $existingStmt = $pdo->prepare('
        SELECT id
        FROM matches
        WHERE club_id = :club_id
          AND competition_id <=> :competition_id
          AND kickoff_at <=> :kickoff_at
          AND (
              (home_team_id = :home_team_id_a AND away_team_id = :away_team_id_a)
              OR
              (home_team_id = :home_team_id_b AND away_team_id = :away_team_id_b)
          )
        LIMIT 1
    ');
    $existingStmt->execute([
        'club_id' => $requestedClubId,
        'competition_id' => $competitionId,
        'kickoff_at' => $kickoffAt,
        'home_team_id_a' => $homeTeamId,
        'away_team_id_a' => $awayTeamId,
        'home_team_id_b' => $awayTeamId,
        'away_team_id_b' => $homeTeamId,
    ]);
    $existingMatchId = (int)($existingStmt->fetchColumn() ?: 0);

    $pdo->beginTransaction();

    $targetMatchId = $existingMatchId;
    if ($existingMatchId <= 0) {
        $seasonId = $fixture['season_id'] !== null ? (int)$fixture['season_id'] : null;
        if ($seasonId === null && $fixture['competition_season_id'] !== null) {
            $seasonId = (int)$fixture['competition_season_id'];
        }

        $insertStmt = $pdo->prepare('
            INSERT INTO matches
              (club_id, season_id, competition_id, home_team_id, away_team_id, kickoff_at, status, created_by)
            VALUES
              (:club_id, :season_id, :competition_id, :home_team_id, :away_team_id, :kickoff_at, :status, :created_by)
        ');
        $insertStmt->execute([
            'club_id' => $requestedClubId,
            'season_id' => $seasonId,
            'competition_id' => $competitionId,
            'home_team_id' => $homeTeamId,
            'away_team_id' => $awayTeamId,
            'kickoff_at' => $kickoffAt,
            'status' => 'draft',
            'created_by' => (int)$user['id'],
        ]);
        $targetMatchId = (int)$pdo->lastInsertId();
    }

    $liTargetStmt = $pdo->prepare('SELECT match_id FROM league_intelligence_matches WHERE match_id = :match_id LIMIT 1');
    $liTargetStmt->execute(['match_id' => $targetMatchId]);
    $existingLiId = (int)($liTargetStmt->fetchColumn() ?: 0);

    if ($existingLiId > 0 && $existingLiId !== $liMatchId) {
        $mergeStmt = $pdo->prepare('
            UPDATE league_intelligence_matches
            SET competition_id = :competition_id,
                season_id = :season_id,
                home_team_id = :home_team_id,
                away_team_id = :away_team_id,
                kickoff_at = :kickoff_at,
                status = :status,
                updated_at = NOW()
            WHERE match_id = :match_id
        ');
        $mergeStmt->execute([
            'competition_id' => $competitionId,
            'season_id' => $fixture['season_id'] !== null ? (int)$fixture['season_id'] : null,
            'home_team_id' => $homeTeamId,
            'away_team_id' => $awayTeamId,
            'kickoff_at' => $kickoffAt,
            'status' => 'scheduled',
            'match_id' => $targetMatchId,
        ]);

        $deleteStmt = $pdo->prepare('DELETE FROM league_intelligence_matches WHERE match_id = :match_id');
        $deleteStmt->execute(['match_id' => $liMatchId]);
    } elseif ($targetMatchId !== $liMatchId) {
        $updateStmt = $pdo->prepare('
            UPDATE league_intelligence_matches
            SET match_id = :new_match_id,
                updated_at = NOW()
            WHERE match_id = :old_match_id
        ');
        $updateStmt->execute([
            'new_match_id' => $targetMatchId,
            'old_match_id' => $liMatchId,
        ]);
    }

    $pdo->commit();

    if ($existingMatchId > 0) {
        $_SESSION['match_form_success'] = 'Fixture linked to existing match';
    } else {
        $_SESSION['match_form_success'] = 'Fixture accepted and saved as draft';
    }
} catch (Throwable $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    $errorContext = '';
    if ($e instanceof PDOException && !empty($e->errorInfo)) {
        $errorContext = sprintf(' SQLSTATE=%s CODE=%s MSG=%s', $e->errorInfo[0] ?? '', $e->errorInfo[1] ?? '', $e->errorInfo[2] ?? '');
    }
    error_log(sprintf('[fixtures-accept] match_id=%d club_id=%d user_id=%d error=%s%s',
        $liMatchId ?? 0,
        $requestedClubId ?? 0,
        (int)($user['id'] ?? 0),
        $e->getMessage(),
        $errorContext
    ));
    if ($isPlatformAdmin) {
        $_SESSION['match_form_error'] = 'Unable to accept fixture: ' . $e->getMessage();
    } else {
        $_SESSION['match_form_error'] = 'Unable to accept fixture';
    }
}

redirect($redirectPath);
