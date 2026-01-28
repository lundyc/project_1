<?php

require_once __DIR__ . '/Controller.php';
require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/../lib/db.php';

class LeagueIntelligenceMatchController extends Controller
{
    private $pdo;

    public function __construct()
    {
        $this->pdo = db();
    }

    private function requirePlatformAdmin(): void
    {
        require_role('platform_admin');
    }

    public function index(): void
    {
        $this->requirePlatformAdmin();

        require_once __DIR__ . '/../lib/league_intelligence_service.php';
        // Pass null for both season and competition to get all matches
        $service = new LeagueIntelligenceService(null, null);
        $matches = $service->getMatchesForCrud();

        // For filters
        require_once __DIR__ . '/../lib/team_repository.php';
        $teams = array_map(function($row) {
            return [
                'id' => $row['id'],
                'name' => $row['name']
            ];
        }, get_all_teams_with_clubs());
        $statuses = ['scheduled', 'completed', 'cancelled'];

        require __DIR__ . '/../views/pages/league-intelligence/matches.php';
    }

    public function create(): void
    {
        $this->requirePlatformAdmin();
        require __DIR__ . '/../views/pages/league-intelligence/match_form.php';
    }

    public function store(): void
    {
        $this->requirePlatformAdmin();
        $data = $_POST;
        $stmt = $this->pdo->prepare('INSERT INTO league_intelligence_matches (competition_id, season_id, home_team_id, away_team_id, kickoff_at, home_goals, away_goals, status, neutral_location) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)');
        $stmt->execute([
            $data['competition_id'],
            $data['season_id'],
            $data['home_team_id'],
            $data['away_team_id'],
            $data['kickoff_at'],
            $data['home_goals'],
            $data['away_goals'],
            $data['status'],
            isset($data['neutral_location']) ? 1 : 0
        ]);
        header('Location: /league-intelligence/matches');
        exit;
    }

    public function edit($id): void
    {
        $this->requirePlatformAdmin();
        $stmt = $this->pdo->prepare('SELECT * FROM league_intelligence_matches WHERE match_id = ?');
        $stmt->execute([$id]);
        $match = $stmt->fetch(PDO::FETCH_ASSOC);
        require __DIR__ . '/../views/pages/league-intelligence/match_form.php';
    }

    public function update($id): void
    {
        $this->requirePlatformAdmin();
        $data = $_POST;
        $stmt = $this->pdo->prepare('UPDATE league_intelligence_matches SET competition_id=?, season_id=?, home_team_id=?, away_team_id=?, kickoff_at=?, home_goals=?, away_goals=?, status=?, neutral_location=? WHERE match_id=?');
        $stmt->execute([
            $data['competition_id'],
            $data['season_id'],
            $data['home_team_id'],
            $data['away_team_id'],
            $data['kickoff_at'],
            $data['home_goals'],
            $data['away_goals'],
            $data['status'],
            isset($data['neutral_location']) ? 1 : 0,
            $id
        ]);
        header('Location: /league-intelligence/matches');
        exit;
    }

    public function delete($id): void
    {
        $this->requirePlatformAdmin();
        $stmt = $this->pdo->prepare('DELETE FROM league_intelligence_matches WHERE match_id = ?');
        $stmt->execute([$id]);
        $redirect = isset($_POST['_redirect']) ? $_POST['_redirect'] : '/league-intelligence/matches';
        header('Location: ' . $redirect);
        exit;
    }
}
