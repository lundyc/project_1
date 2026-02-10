<?php

require_once __DIR__ . '/Controller.php';
require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/../lib/club_repository.php';
require_once __DIR__ . '/../lib/season_repository.php';
require_once __DIR__ . '/../Services/WosflImportService.php';
require_once __DIR__ . '/../lib/league_intelligence_service.php';

class LeagueIntelligenceImportController extends Controller
{
    private $importService;

    public function __construct(WosflImportService $importService)
    {
        $this->importService = $importService;
    }

    private function requirePlatformAdmin(): void
    {
        require_role('platform_admin');
    }

    private function requirePost(): void
    {
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            http_response_code(405);
            echo '405 Method Not Allowed';
            exit;
        }
    }

    private function resolveClubId(): ?int
    {
        $statsClubId = isset($_SESSION['stats_club_id']) ? (int)$_SESSION['stats_club_id'] : 0;
        if ($statsClubId > 0 && get_club_by_id($statsClubId)) {
            return $statsClubId;
        }

        $sessionClubId = isset($_SESSION['admin_player_club_id']) ? (int)$_SESSION['admin_player_club_id'] : 0;
        if ($sessionClubId > 0 && get_club_by_id($sessionClubId)) {
            return $sessionClubId;
        }

        $user = current_user();
        $userClubId = (int)($user['club_id'] ?? 0);
        if ($userClubId > 0 && get_club_by_id($userClubId)) {
            return $userClubId;
        }

        $clubs = get_all_clubs();
        if (!empty($clubs)) {
            return (int)($clubs[0]['id'] ?? 0);
        }

        return null;
    }

    private function resolveSeasonId(int $clubId): ?int
    {
        $seasons = get_seasons_by_club($clubId);
        if (!empty($seasons)) {
            return (int)($seasons[0]['id'] ?? 0);
        }

        return null;
    }

    private function normalizeDateTimeInput(?string $value): ?string
    {
        $value = trim((string)$value);
        if ($value === '') {
            return null;
        }

        $value = str_replace('T', ' ', $value);
        $formats = [
            'Y-m-d H:i:s',
            'Y-m-d H:i',
            'd/m/Y H:i',
            'd/m/y H:i',
            'd/m/Y',
            'd/m/y',
            'Y-m-d',
        ];

        $timezone = new DateTimeZone('UTC');
        foreach ($formats as $format) {
            $dt = DateTimeImmutable::createFromFormat($format, $value, $timezone);
            if ($dt && $dt->format($format) === $value) {
                return $dt->format('Y-m-d H:i:s');
            }
        }

        try {
            $dt = new DateTimeImmutable($value, $timezone);
            return $dt->format('Y-m-d H:i:s');
        } catch (Throwable $e) {
            return null;
        }
    }

    private function normalizeScore(?string $value): ?int
    {
        $value = trim((string)$value);
        if ($value === '') {
            return null;
        }

        if (preg_match('/^\d+$/', $value) === 1) {
            return (int)$value;
        }

        if (preg_match('/(\d+)/', $value, $matches)) {
            return (int)$matches[1];
        }

        return null;
    }

    private function normalizeStatus(?string $value, ?int $homeGoals, ?int $awayGoals): string
    {
        $value = strtolower(trim((string)$value));
        if ($value === '') {
            return ($homeGoals !== null && $awayGoals !== null) ? 'completed' : 'scheduled';
        }

        $cancelled = ['cancelled', 'canceled', 'abandoned', 'void', 'postponed'];
        if (in_array($value, $cancelled, true)) {
            return 'cancelled';
        }

        $completed = ['completed', 'played', 'final', 'ft', 'full-time'];
        if (in_array($value, $completed, true)) {
            return 'completed';
        }

        if ($value === 'scheduled') {
            return 'scheduled';
        }

        return ($homeGoals !== null && $awayGoals !== null) ? 'completed' : 'scheduled';
    }

    private function resolveSingleMatch(string $name, callable $exactFinder, callable $partialFinder): ?array
    {
        if (trim($name) === '') {
            return null;
        }

        $exactMatches = $exactFinder($name);
        if (is_array($exactMatches)) {
            $count = count($exactMatches);
            if ($count === 1) {
                return $exactMatches[0];
            }
            if ($count > 1) {
                return null;
            }
        }

        $partialMatches = $partialFinder($name);
        if (is_array($partialMatches) && count($partialMatches) === 1) {
            return $partialMatches[0];
        }

        return null;
    }

    private function resolveCompetitionType(string $name): string
    {
        $lower = strtolower($name);
        $cupHints = ['cup', 'trophy', 'shield', 'challenge', 'plate'];
        foreach ($cupHints as $hint) {
            if (strpos($lower, $hint) !== false) {
                return 'cup';
            }
        }

        $leagueHints = ['league', 'division'];
        foreach ($leagueHints as $hint) {
            if (strpos($lower, $hint) !== false) {
                return 'league';
            }
        }

        return 'cup';
    }

    private function persistImportRows(array $rows, int $clubId, int $seasonId, bool $allowDeletes = true): array
    {
        $pdo = db();
        $teamRepository = new TeamRepository($pdo);
        $competitionRepository = new CompetitionRepository($pdo);
        $matchRepository = new LeagueIntelligenceMatchRepository($pdo);

        $teamExactFinder = function (string $value) use ($teamRepository, $clubId): array {
            $rows = $teamRepository->findByNormalizedName($value, $clubId);
            if (empty($rows)) {
                $rows = $teamRepository->findByNormalizedName($value);
            }
            return $rows;
        };

        $teamPartialFinder = function (string $value) use ($teamRepository, $clubId): array {
            $rows = $teamRepository->searchByNormalizedName($value, $clubId);
            if (empty($rows)) {
                $rows = $teamRepository->searchByNormalizedName($value);
            }
            return $rows;
        };

        $counts = [
            'processed' => 0,
            'updated' => 0,
            'added' => 0,
            'skipped' => 0,
            'conflicts' => 0,
            'created_teams' => 0,
            'created_competitions' => 0,
            'missing_team_samples' => [],
            'already_exists_samples' => [],
        ];
        $skipped = [
            'missing_team' => 0,
            'ambiguous_team' => 0,
            'missing_competition' => 0,
            'ambiguous_competition' => 0,
            'invalid_datetime' => 0,
            'outside_window' => 0,
            'already_exists' => 0,
            'other' => 0,
        ];
        $skippedLogCount = 0;

        $pdo->beginTransaction();
        try {
            foreach ($rows as $row) {
                if (!is_array($row)) {
                    continue;
                }

                $kickoffAt = $this->normalizeDateTimeInput($row['date_time'] ?? null);
                $homeTeamName = trim((string)($row['home_team_name'] ?? ''));
                $awayTeamName = trim((string)($row['away_team_name'] ?? ''));
                $competitionName = trim((string)($row['competition_name'] ?? ''));

                $homeGoals = $this->normalizeScore($row['home_goals'] ?? null);
                $awayGoals = $this->normalizeScore($row['away_goals'] ?? null);
                $status = $this->normalizeStatus($row['status'] ?? null, $homeGoals, $awayGoals);

                $homeTeamId = isset($row['home_team_id']) ? (int)$row['home_team_id'] : 0;
                if ($homeTeamId <= 0) {
                    $homeTeam = $this->resolveSingleMatch(
                        $homeTeamName,
                        $teamExactFinder,
                        $teamPartialFinder
                    );
                    if ($homeTeam) {
                        $homeTeamId = (int)$homeTeam['id'];
                    } elseif ($homeTeamName !== '' && empty($row['home_team_ambiguous'])) {
                        $createdHome = $teamRepository->create($homeTeamName, $clubId, 'opponent');
                        if ($createdHome) {
                            $homeTeamId = (int)$createdHome['id'];
                            $counts['created_teams']++;
                        }
                    }
                }

                $awayTeamId = isset($row['away_team_id']) ? (int)$row['away_team_id'] : 0;
                if ($awayTeamId <= 0) {
                    $awayTeam = $this->resolveSingleMatch(
                        $awayTeamName,
                        $teamExactFinder,
                        $teamPartialFinder
                    );
                    if ($awayTeam) {
                        $awayTeamId = (int)$awayTeam['id'];
                    } elseif ($awayTeamName !== '' && empty($row['away_team_ambiguous'])) {
                        $createdAway = $teamRepository->create($awayTeamName, $clubId, 'opponent');
                        if ($createdAway) {
                            $awayTeamId = (int)$createdAway['id'];
                            $counts['created_teams']++;
                        }
                    }
                }

                $competitionId = isset($row['competition_id']) ? (int)$row['competition_id'] : 0;
                $competitionSeasonId = $seasonId;
                if ($competitionId > 0) {
                    $competition = $competitionRepository->findById($competitionId);
                    if ($competition && !empty($competition['season_id'])) {
                        $competitionSeasonId = (int)$competition['season_id'];
                    }
                } elseif ($competitionName !== '') {
                    $competition = $this->resolveSingleMatch(
                        $competitionName,
                        function (string $value) use ($competitionRepository, $clubId): array {
                            return $competitionRepository->findByNormalizedName($value, $clubId);
                        },
                        function (string $value) use ($competitionRepository, $clubId): array {
                            return $competitionRepository->searchByNormalizedName($value, $clubId);
                        }
                    );
                    if ($competition) {
                        $competitionId = (int)$competition['id'];
                        $competitionSeasonId = !empty($competition['season_id']) ? (int)$competition['season_id'] : $seasonId;
                    } elseif (empty($row['competition_ambiguous'])) {
                        $competitionType = $this->resolveCompetitionType($competitionName);
                        $createdCompetition = $competitionRepository->create($competitionName, $clubId, $seasonId, $competitionType);
                        if ($createdCompetition) {
                            $competitionId = (int)$createdCompetition['id'];
                            $competitionSeasonId = (int)($createdCompetition['season_id'] ?? $seasonId);
                            $counts['created_competitions']++;
                        }
                    }
                }

                // Instrumented skip logic (after attempting auto-create)
                $skipReason = null;
                if ($homeTeamId <= 0 || $awayTeamId <= 0) {
                    $skipReason = 'missing_team';
                } elseif (!empty($row['home_team_ambiguous']) || !empty($row['away_team_ambiguous'])) {
                    $skipReason = 'ambiguous_team';
                } elseif ($competitionId <= 0) {
                    $skipReason = 'missing_competition';
                } elseif (!empty($row['competition_ambiguous'])) {
                    $skipReason = 'ambiguous_competition';
                } elseif (empty($kickoffAt)) {
                    $skipReason = 'invalid_datetime';
                } elseif (!empty($row['existing_match'])) {
                    $skipReason = 'already_exists';
                }
                if ($skipReason !== null) {
                    $counts['skipped']++;
                    if (isset($skipped[$skipReason])) {
                        $skipped[$skipReason]++;
                    } else {
                        $skipped['other']++;
                    }
                    if ($skippedLogCount < 20) {
                        if ($skipReason === 'missing_team') {
                            if (count($counts['missing_team_samples']) < 5) {
                                $counts['missing_team_samples'][] = [
                                    'home' => $row['home_team_name'] ?? null,
                                    'away' => $row['away_team_name'] ?? null,
                                    'competition' => $row['competition_name'] ?? null,
                                    'date_time' => $row['date_time'] ?? null,
                                    'home_lookup_status' => $row['home_team_lookup_status'] ?? null,
                                    'away_lookup_status' => $row['away_team_lookup_status'] ?? null,
                                ];
                            }
                            error_log('[WOSFL SKIP] ' . json_encode([
                                'reason' => 'missing_team',
                                'home_team' => $row['home_team_name'] ?? null,
                                'away_team' => $row['away_team_name'] ?? null,
                                'home_lookup_status' => $row['home_team_lookup_status'] ?? null,
                                'away_lookup_status' => $row['away_team_lookup_status'] ?? null,
                                'home_lookup_matches' => $row['home_team_lookup_matches'] ?? null,
                                'away_lookup_matches' => $row['away_team_lookup_matches'] ?? null,
                                'competition' => $row['competition_name'] ?? null,
                            ]));
                        } else {
                            error_log('[WOSFL SKIP] ' . json_encode([
                                'reason' => $skipReason,
                                'home' => $row['home_team_name'] ?? null,
                                'away' => $row['away_team_name'] ?? null,
                                'competition' => $row['competition_name'] ?? null,
                                'date_time' => $row['date_time'] ?? null,
                            ]));
                        }
                        $skippedLogCount++;
                    }
                    continue;
                }

                $matches = $matchRepository->findByKickoffAndTeams($kickoffAt, $homeTeamId, $awayTeamId, $competitionId);

                if (!empty($matches)) {
                    if (count($matches) > 1) {
                        $counts['conflicts']++;
                        error_log(sprintf(
                            'WOSFL import conflict: %d matches found for %s (home=%d away=%d)',
                            count($matches),
                            $kickoffAt,
                            $homeTeamId,
                            $awayTeamId
                        ));
                    }
                    $match = $matches[0];
                    $incomingHasGoals = ($homeGoals !== null && $awayGoals !== null);
                    $updateHomeGoals = $homeGoals;
                    $updateAwayGoals = $awayGoals;
                    if ((int)($match['home_team_id'] ?? 0) === $awayTeamId && (int)($match['away_team_id'] ?? 0) === $homeTeamId) {
                        $updateHomeGoals = $awayGoals;
                        $updateAwayGoals = $homeGoals;
                    }
                    $existingHasGoals = isset($match['home_goals'], $match['away_goals']) && $match['home_goals'] !== null && $match['away_goals'] !== null;
                    $existingStatus = strtolower((string)($match['status'] ?? ''));
                    $existingHomeGoals = $match['home_goals'] !== null ? (int)$match['home_goals'] : null;
                    $existingAwayGoals = $match['away_goals'] !== null ? (int)$match['away_goals'] : null;

                    // Force update unless incoming has no goals and existing already has results.
                    if (!$incomingHasGoals && $existingHasGoals) {
                        $counts['skipped']++;
                        $skipped['already_exists']++;
                        if (count($counts['already_exists_samples']) < 5) {
                            $counts['already_exists_samples'][] = [
                                'home' => $row['home_team_name'] ?? null,
                                'away' => $row['away_team_name'] ?? null,
                                'competition' => $row['competition_name'] ?? null,
                                'date_time' => $row['date_time'] ?? null,
                                'existing_home_goals' => $existingHomeGoals,
                                'existing_away_goals' => $existingAwayGoals,
                                'existing_status' => $existingStatus,
                                'incoming_home_goals' => $updateHomeGoals,
                                'incoming_away_goals' => $updateAwayGoals,
                                'incoming_status' => $status,
                                'skip_reason' => 'incoming_missing_goals',
                            ];
                        }
                        if ($skippedLogCount < 20) {
                            error_log('[WOSFL SKIP] ' . json_encode([
                                'reason' => 'incoming_missing_goals',
                                'home' => $row['home_team_name'] ?? null,
                                'away' => $row['away_team_name'] ?? null,
                                'competition' => $row['competition_name'] ?? null,
                                'date_time' => $row['date_time'] ?? null,
                            ]));
                            $skippedLogCount++;
                        }
                        continue;
                    }

                    $matchRepository->updateMatch((int)$match['match_id'], [
                        'home_goals' => $updateHomeGoals,
                        'away_goals' => $updateAwayGoals,
                        'status' => $status,
                    ]);
                    $counts['updated']++;
                    $counts['processed']++;
                    continue;
                }

                $matchId = $matchRepository->nextSyntheticMatchId();
                $matchRepository->insertMatch([
                    'match_id' => $matchId,
                    'competition_id' => $competitionId,
                    'season_id' => $competitionSeasonId,
                    'home_team_id' => $homeTeamId,
                    'away_team_id' => $awayTeamId,
                    'kickoff_at' => $kickoffAt,
                    'home_goals' => $homeGoals,
                    'away_goals' => $awayGoals,
                    'status' => $status,
                ]);
                $counts['added']++;
                $counts['processed']++;
            }

            $pdo->commit();
        } catch (Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }

        $counts['skipped'] = array_sum($skipped);
        $counts['skipped_breakdown'] = $skipped;
        return $counts;
    }

    public function showImportForm(): void
    {
        $this->requirePlatformAdmin();

        $importRows = $_SESSION['wosfl_import_rows'] ?? [];
        $flashSuccess = $_SESSION['wosfl_import_success'] ?? null;
        $flashError = $_SESSION['wosfl_import_error'] ?? null;
        $flashInfo = $_SESSION['wosfl_import_info'] ?? null;

        unset($_SESSION['wosfl_import_success'], $_SESSION['wosfl_import_error'], $_SESSION['wosfl_import_info']);

        $title = 'League Intelligence Import';
        require __DIR__ . '/../views/pages/league-intelligence/import.php';
    }

    public function runImport(): void
    {
        $this->requirePlatformAdmin();
        $this->requirePost();

        $matches = $this->importService->scrapeAll();
        $rows = $this->importService->preparePreviewRows($matches);
        $_SESSION['wosfl_import_rows'] = $rows;

        if (empty($rows)) {
            $_SESSION['wosfl_import_info'] = 'No WOSFL fixtures or results were returned yet.';
        } else {
            $_SESSION['wosfl_import_info'] = sprintf('Loaded %d rows from WOSFL.', count($rows));
        }

        redirect('/league-intelligence/import');
    }

    public function saveImport(): void
    {
        $this->requirePlatformAdmin();
        $this->requirePost();

        $rows = $_POST['rows'] ?? [];
        if (!is_array($rows)) {
            $rows = [];
        }

        $clubId = $this->resolveClubId();
        if (!$clubId) {
            $_SESSION['wosfl_import_error'] = 'Unable to determine a club for this import.';
            if ($this->isAjax()) {
                header('Content-Type: application/json');
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Unable to determine a club for this import.']);
                exit;
            }
            redirect('/league-intelligence/import');
        }

        $seasonId = $this->resolveSeasonId($clubId);
        if (!$seasonId) {
            $_SESSION['wosfl_import_error'] = 'Unable to determine a season for this import.';
            if ($this->isAjax()) {
                header('Content-Type: application/json');
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Unable to determine a season for this import.']);
                exit;
            }
            redirect('/league-intelligence/import');
        }

        try {
            $counts = $this->persistImportRows($rows, $clubId, $seasonId, true);
        } catch (Throwable $e) {
            error_log('WOSFL import save failed: ' . $e->getMessage());
            $_SESSION['wosfl_import_error'] = 'Import save failed. Please try again.';
            if ($this->isAjax()) {
                header('Content-Type: application/json');
                http_response_code(500);
                echo json_encode(['success' => false, 'error' => 'Import save failed. Please try again.']);
                exit;
            }
            redirect('/league-intelligence/import');
        }

        if ($this->isAjax() && $counts['processed'] === 0) {
            header('Content-Type: application/json');
            http_response_code(422);
            echo json_encode(['success' => false, 'error' => 'Row could not be saved. Resolve missing teams or competition and try again.']);
            exit;
        }

        $leagueService = new LeagueIntelligenceService();
        $leagueService->syncMatches();

        $_SESSION['wosfl_import_rows'] = [];

        $messageParts = [sprintf('Import saved. %d rows processed.', $counts['processed'])];
        if ($counts['created_teams'] > 0) {
            $messageParts[] = sprintf('%d teams created.', $counts['created_teams']);
        }
        if ($counts['created_competitions'] > 0) {
            $messageParts[] = sprintf('%d competitions created.', $counts['created_competitions']);
        }
        if ($counts['conflicts'] > 0) {
            $messageParts[] = sprintf('%d conflicts resolved.', $counts['conflicts']);
        }
        if ($counts['skipped'] > 0) {
            $messageParts[] = sprintf('%d rows skipped.', $counts['skipped']);
        }

        $_SESSION['wosfl_import_success'] = implode(' ', $messageParts);

        if ($this->isAjax()) {
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'counts' => $counts]);
            exit;
        }
        redirect('/league-intelligence');
    }

    private function isAjax(): bool
    {
        return (
            (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') ||
            (isset($_SERVER['HTTP_ACCEPT']) && strpos(strtolower($_SERVER['HTTP_ACCEPT']), 'application/json') !== false)
        );
    }

    public function updateWeek(): void
    {
        $this->requirePlatformAdmin();
        $this->requirePost();

        $clubId = $this->resolveClubId();
        if (!$clubId) {
            if ($this->isAjax()) {
                http_response_code(400);
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'error' => 'Unable to determine a club for this update.']);
                exit;
            }
            $_SESSION['wosfl_import_error'] = 'Unable to determine a club for this update.';
            redirect('/league-intelligence');
        }

        $seasonId = $this->resolveSeasonId($clubId);
        if (!$seasonId) {
            if ($this->isAjax()) {
                http_response_code(400);
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'error' => 'Unable to determine a season for this update.']);
                exit;
            }
            $_SESSION['wosfl_import_error'] = 'Unable to determine a season for this update.';
            redirect('/league-intelligence');
        }

        // 1. Sync internal matches (events → league_intelligence_matches) first
        $leagueService = new LeagueIntelligenceService();
        // For weekly updates, sync ALL competitions/seasons to ensure all valid matches are included
        $leagueService->syncMatches(true);

        // 2. Force import WOSFL results for all available fixtures
        $matches = $this->importService->scrapeAll();
        $matches = array_values(array_filter($matches, static function ($row): bool {
            return is_array($row) && empty($row['skip_reason']);
        }));
        $scheduledMatches = $this->importService->preparePreviewRows($matches);
        $scheduledMatches = array_map(static function (array $row): array {
            $row['existing_match'] = false;
            return $row;
        }, $scheduledMatches);

        try {
            $counts = $this->persistImportRows($scheduledMatches, $clubId, $seasonId, false);
        } catch (Throwable $e) {
            error_log('WOSFL weekly update failed: ' . $e->getMessage());
            if ($this->isAjax()) {
                http_response_code(500);
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'error' => 'Weekly update failed. Please try again.']);
                exit;
            }
            $_SESSION['wosfl_import_error'] = 'Weekly update failed. Please try again.';
            redirect('/league-intelligence');
        }

        // Build skip breakdown for UI
        $skipped = $counts['skipped_breakdown'] ?? [];
        $updated = $counts['updated'] ?? 0;
        $added = $counts['added'] ?? 0;
        $createdTeams = $counts['created_teams'] ?? 0;
        $createdCompetitions = $counts['created_competitions'] ?? 0;
        $conflicts = $counts['conflicts'] ?? 0;

        $parts = [];
        if (is_array($skipped)) {
            foreach ($skipped as $reason => $count) {
                if ($count > 0) {
                    $parts[] = ucfirst(str_replace('_', ' ', $reason)) . ": {$count}";
                }
            }
        }
        $skipBreakdown = $parts ? ' Skipped → ' . implode(', ', $parts) : '';

        $success = sprintf(
            'Weekly update complete. %d matches updated, %d added. %d rows skipped.%s',
            $updated,
            $added,
            is_array($skipped) ? array_sum($skipped) : 0,
            $skipBreakdown
        );

        // If missing_team > 0, append UI hint
        if (is_array($skipped) && !empty($skipped['missing_team'])) {
            $success .= ' (team matching failed — check names)';
        }

        // Optionally append created teams/competitions/conflicts as before
        if ($createdTeams > 0) {
            $success .= sprintf(' %d teams created.', $createdTeams);
        }
        if ($createdCompetitions > 0) {
            $success .= sprintf(' %d competitions created.', $createdCompetitions);
        }
        if ($conflicts > 0) {
            $success .= sprintf(' %d conflicts resolved.', $conflicts);
        }

        if ($this->isAjax()) {
            $missingSamples = $counts['missing_team_samples'] ?? [];
            if (empty($missingSamples) && !empty($skipped['missing_team'])) {
                foreach ($scheduledMatches as $row) {
                    if (empty($row['home_team_found']) || empty($row['away_team_found'])) {
                        $missingSamples[] = [
                            'home' => $row['home_team_name'] ?? null,
                            'away' => $row['away_team_name'] ?? null,
                            'competition' => $row['competition_name'] ?? null,
                            'date_time' => $row['date_time'] ?? null,
                            'home_lookup_status' => $row['home_team_lookup_status'] ?? null,
                            'away_lookup_status' => $row['away_team_lookup_status'] ?? null,
                            'home_lookup_matches' => $row['home_team_lookup_matches'] ?? null,
                            'away_lookup_matches' => $row['away_team_lookup_matches'] ?? null,
                        ];
                    }
                    if (count($missingSamples) >= 5) {
                        break;
                    }
                }
            }

            $existsSamples = $counts['already_exists_samples'] ?? [];
            if (empty($existsSamples) && !empty($skipped['already_exists'])) {
                foreach ($scheduledMatches as $row) {
                    if (!empty($row['existing_match'])) {
                        $existsSamples[] = [
                            'home' => $row['home_team_name'] ?? null,
                            'away' => $row['away_team_name'] ?? null,
                            'competition' => $row['competition_name'] ?? null,
                            'date_time' => $row['date_time'] ?? null,
                        ];
                    }
                    if (count($existsSamples) >= 5) {
                        break;
                    }
                }
            }

            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'message' => $success,
                'debug' => [
                    'missing_team_samples' => $missingSamples,
                    'already_exists_samples' => $existsSamples,
                    'skipped_breakdown' => $counts['skipped_breakdown'] ?? [],
                    'total_rows' => count($scheduledMatches),
                    'existing_match_rows' => array_sum(array_map(static fn($row) => !empty($row['existing_match']) ? 1 : 0, $scheduledMatches)),
                ],
            ]);
            exit;
        }

        $_SESSION['wosfl_import_success'] = $success;

        redirect('/league-intelligence');
    }
}
