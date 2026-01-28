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

        $counts = [
            'processed' => 0,
            'updated' => 0,
            'added' => 0,
            'skipped' => 0,
            'conflicts' => 0,
            'created_teams' => 0,
            'created_competitions' => 0,
        ];

        $pdo->beginTransaction();
        try {
            foreach ($rows as $row) {
                if (!is_array($row)) {
                    continue;
                }

                if ($allowDeletes) {
                    $isDeleted = ($row['deleted'] ?? $row['delete'] ?? '') === '1';
                    if ($isDeleted) {
                        continue;
                    }
                }

                $kickoffAt = $this->normalizeDateTimeInput($row['date_time'] ?? null);
                $homeTeamName = trim((string)($row['home_team_name'] ?? ''));
                $awayTeamName = trim((string)($row['away_team_name'] ?? ''));
                $competitionName = trim((string)($row['competition_name'] ?? ''));

                $homeGoals = $this->normalizeScore($row['home_goals'] ?? null);
                $awayGoals = $this->normalizeScore($row['away_goals'] ?? null);
                $status = $this->normalizeStatus($row['status'] ?? null, $homeGoals, $awayGoals);

                if (!$kickoffAt || $homeTeamName === '' || $awayTeamName === '') {
                    $counts['skipped']++;
                    continue;
                }

                $homeTeamId = isset($row['home_team_id']) ? (int)$row['home_team_id'] : 0;
                if ($homeTeamId <= 0) {
                    $homeTeam = $teamRepository->findByNameForClub($homeTeamName, $clubId);
                    if ($homeTeam) {
                        $homeTeamId = (int)$homeTeam['id'];
                    } else {
                        $created = $teamRepository->create($homeTeamName, $clubId, 'opponent');
                        if ($created) {
                            $homeTeamId = (int)$created['id'];
                            $counts['created_teams']++;
                        }
                    }
                }

                $awayTeamId = isset($row['away_team_id']) ? (int)$row['away_team_id'] : 0;
                if ($awayTeamId <= 0) {
                    $awayTeam = $teamRepository->findByNameForClub($awayTeamName, $clubId);
                    if ($awayTeam) {
                        $awayTeamId = (int)$awayTeam['id'];
                    } else {
                        $created = $teamRepository->create($awayTeamName, $clubId, 'opponent');
                        if ($created) {
                            $awayTeamId = (int)$created['id'];
                            $counts['created_teams']++;
                        }
                    }
                }

                if ($homeTeamId <= 0 || $awayTeamId <= 0) {
                    $counts['skipped']++;
                    continue;
                }

                $competitionId = isset($row['competition_id']) ? (int)$row['competition_id'] : 0;
                $competitionSeasonId = $seasonId;
                if ($competitionId > 0) {
                    $competition = $competitionRepository->findById($competitionId);
                    if ($competition && !empty($competition['season_id'])) {
                        $competitionSeasonId = (int)$competition['season_id'];
                    }
                } elseif ($competitionName !== '') {
                    $competition = $competitionRepository->findByNameForClub($competitionName, $clubId);
                    if ($competition) {
                        $competitionId = (int)$competition['id'];
                        $competitionSeasonId = !empty($competition['season_id']) ? (int)$competition['season_id'] : $seasonId;
                    } else {
                        $type = $this->resolveCompetitionType($competitionName);
                        $created = $competitionRepository->create($competitionName, $clubId, $seasonId, $type);
                        if ($created) {
                            $competitionId = (int)$created['id'];
                            $competitionSeasonId = $seasonId;
                            $counts['created_competitions']++;
                        }
                    }
                }

                if ($competitionId <= 0) {
                    $counts['skipped']++;
                    continue;
                }

                $matches = $matchRepository->findByKickoffAndTeams($kickoffAt, $homeTeamId, $awayTeamId);
                if ($competitionId > 0 && count($matches) > 1) {
                    $filtered = array_values(array_filter($matches, static function (array $match) use ($competitionId) {
                        return (int)($match['competition_id'] ?? 0) === $competitionId;
                    }));
                    if (!empty($filtered)) {
                        $matches = $filtered;
                    }
                }

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
                    $matchRepository->updateMatch((int)$match['match_id'], [
                        'home_goals' => $homeGoals,
                        'away_goals' => $awayGoals,
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
            redirect('/league-intelligence/import');
        }

        $seasonId = $this->resolveSeasonId($clubId);
        if (!$seasonId) {
            $_SESSION['wosfl_import_error'] = 'Unable to determine a season for this import.';
            redirect('/league-intelligence/import');
        }

        try {
            $counts = $this->persistImportRows($rows, $clubId, $seasonId, true);
        } catch (Throwable $e) {
            error_log('WOSFL import save failed: ' . $e->getMessage());
            $_SESSION['wosfl_import_error'] = 'Import save failed. Please try again.';
            redirect('/league-intelligence/import');
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

        redirect('/league-intelligence');
    }

    public function updateWeek(): void
    {
        $this->requirePlatformAdmin();
        $this->requirePost();

        $matches = $this->importService->scrapeWeek();
        $clubId = $this->resolveClubId();
        if (!$clubId) {
            $_SESSION['wosfl_import_error'] = 'Unable to determine a club for this update.';
            redirect('/league-intelligence');
        }

        $seasonId = $this->resolveSeasonId($clubId);
        if (!$seasonId) {
            $_SESSION['wosfl_import_error'] = 'Unable to determine a season for this update.';
            redirect('/league-intelligence');
        }

        try {
            $counts = $this->persistImportRows($matches, $clubId, $seasonId, false);
        } catch (Throwable $e) {
            error_log('WOSFL weekly update failed: ' . $e->getMessage());
            $_SESSION['wosfl_import_error'] = 'Weekly update failed. Please try again.';
            redirect('/league-intelligence');
        }

        $leagueService = new LeagueIntelligenceService();
        $leagueService->syncMatches();

        $messageParts = [
            sprintf(
                'Weekly update complete. %d matches updated, %d added.',
                $counts['updated'],
                $counts['added']
            ),
        ];
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

        redirect('/league-intelligence');
    }
}
