<?php

require_once __DIR__ . '/../lib/stats_context.php';
require_once __DIR__ . '/../lib/StatsService.php';

class StatsController {
    /**
     * Export the statistics dashboard as PDF
     */
    public static function exportPdf(): void
    {
        require_once __DIR__ . '/../lib/stats_context.php';
        require_once __DIR__ . '/../lib/StatsService.php';
        require_once __DIR__ . '/../lib/pdf_helper.php'; // Dompdf wrapper
        $context = resolve_club_context_for_stats();
        $selectedClubId = $context['club_id'];
        $selectedClub = $context['club'] ?? null;
        require_once __DIR__ . '/../lib/season_repository.php';
        require_once __DIR__ . '/../lib/competition_repository.php';
        $seasons = get_seasons_by_club($selectedClubId);
        $competitions = get_competitions_by_club($selectedClubId);
        $seasonId = null;
        if (isset($_GET['season_id']) && $_GET['season_id'] !== '') {
            $seasonId = (int)$_GET['season_id'];
            if ($seasonId <= 0) {
                $seasonId = null;
            }
        }
        $type = null;
        if (!empty($_GET['type']) && in_array($_GET['type'], ['league', 'cup'], true)) {
            $type = $_GET['type'];
        }
        $positionFilter = isset($_GET['position']) ? trim((string)$_GET['position']) : null;
        if ($positionFilter === '') {
            $positionFilter = null;
        }

        $filterSummary = [];
        if ($seasonId !== null) {
            $seasonLabel = null;
            foreach ($seasons as $season) {
                if ((int)($season['id'] ?? 0) === $seasonId) {
                    $seasonLabel = $season['name'] ?? ('Season ' . $seasonId);
                    break;
                }
            }
            $filterSummary[] = 'Season: ' . ($seasonLabel ?? ('Season ' . $seasonId));
        }
        if ($type !== null) {
            $filterSummary[] = 'Type: ' . ucfirst($type);
        }
        if ($positionFilter !== null) {
            $filterSummary[] = 'Position: ' . $positionFilter;
        }

        $statsService = new StatsService($selectedClubId);
        $matches = $statsService->getMatchList();
        $overviewStats = $statsService->getOverviewStats($seasonId, $type);
        $teamPerformance = $statsService->getTeamPerformanceStats($seasonId, $type);
        $playerPerformance = $statsService->getPlayerPerformanceForClub($seasonId, $type);

        // Render PDF template (one page per section)
        ob_start();
        require __DIR__ . '/../views/pages/stats/export_pdf.php';
        $html = ob_get_clean();

        $clubName = $selectedClub['name'] ?? 'Club';
        $filename = 'Stats_Report_' . preg_replace('/[^a-zA-Z0-9_-]/', '_', $clubName) . '_' . date('Ymd') . '.pdf';

        pdf_output($html, $filename);
        exit;
    }
    /**
     * Display the statistics dashboard
     *
     * Data flows from the database through StatsService → API endpoints → this view.
     * Each tab consumes the appropriate endpoint so the controller stays focused on rendering.
     */
    public static function dashboard(): void
    {
        $context = resolve_club_context_for_stats();
        $selectedClubId = $context['club_id'];
        $selectedClub = $context['club'] ?? null;
        $availableClubs = [];

        if (user_has_role('platform_admin')) {
            require_once __DIR__ . '/../lib/club_repository.php';
            $availableClubs = get_all_clubs();
        }

        require_once __DIR__ . '/../lib/season_repository.php';
        require_once __DIR__ . '/../lib/competition_repository.php';
        
        $seasons = get_seasons_by_club($selectedClubId);
        $competitions = get_competitions_by_club($selectedClubId);

        $statsService = new StatsService($selectedClubId);
        $matches = $statsService->getMatchList();

        require __DIR__ . '/../views/pages/stats/index.php';
    }

    public static function match(int $matchId): void
    {
        if ($matchId <= 0) {
            http_response_code(404);
            echo '404 Not Found';
            return;
        }

        $context = resolve_club_context_for_stats();
        global $clubId, $selectedClub;
        $clubId = $context['club_id'];
        $selectedClub = $context['club'] ?? null;

        require_once __DIR__ . '/../lib/match_repository.php';
        require_once __DIR__ . '/../lib/match_permissions.php';

        $match = get_match($matchId);
        if (!$match) {
            http_response_code(404);
            echo '404 Not Found';
            return;
        }

        $user = current_user();
        $roles = $_SESSION['roles'] ?? [];

        if (!can_view_match($user, $roles, (int)$match['club_id']) || (int)$match['club_id'] !== $clubId) {
            http_response_code(403);
            echo '403 Forbidden';
            return;
        }

        $kickoffAt = null;
        if (!empty($match['kickoff_at'])) {
            try {
                $kickoffAt = new DateTime($match['kickoff_at']);
            } catch (Exception $e) {
                $kickoffAt = null;
            }
        }

        $matchDateLabel = $kickoffAt ? $kickoffAt->format('j M Y') : 'TBD';
        $matchTimeLabel = $kickoffAt ? $kickoffAt->format('H:i') : 'TBD';

        $matchStatusLabel = $match['status'] ?? 'Scheduled';

        // Load derived stats and events for server-side rendering
        $statsService = new StatsService($clubId);
        global $primaryTeamId;
        $primaryTeamId = $statsService->getPrimaryTeamId();
        // Fallback: if null, use home_team_id from match (should not happen, but ensures JS logic works)
        if ($primaryTeamId === null && isset($match['home_team_id'])) {
            $primaryTeamId = (int)$match['home_team_id'];
        }
        $derivedData = $statsService->getMatchDerivedData($matchId);
        $derivedStats = $derivedData['derived'] ?? [];
        $events = $derivedData['events'] ?? [];
        $periods = $derivedData['periods'] ?? [];

        // Expose $clubId and $primaryTeamId to the view
        global $clubId, $primaryTeamId;
        require __DIR__ . '/../views/pages/stats/match.php';
    }
}
