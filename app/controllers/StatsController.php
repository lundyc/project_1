<?php

require_once __DIR__ . '/../lib/stats_context.php';
require_once __DIR__ . '/../lib/StatsService.php';

class StatsController
{
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
        $derivedData = $statsService->getMatchDerivedData($matchId);
        $derivedStats = $derivedData['derived'] ?? [];
        $events = $derivedData['events'] ?? [];
        $periods = $derivedData['periods'] ?? [];

        require __DIR__ . '/../views/pages/stats/match.php';
    }
}
