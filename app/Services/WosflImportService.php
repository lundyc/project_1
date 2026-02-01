<?php

require_once __DIR__ . '/../lib/db.php';

class WosflNameNormalizer
{
    public static function normalize(string $name): string
    {
        $name = strtolower(trim($name));
        if ($name === '') {
            return '';
        }

        $name = preg_replace("/[\\.,\\-'\\x{2018}\\x{2019}]+/u", '', $name);
        $name = preg_replace('/\s+/', ' ', $name);

        return trim($name);
    }
}

class WosflHttpClient
{
    private $userAgent;
    private $timeout;

    public function __construct(?string $userAgent = null, int $timeout = 20)
    {
        $this->userAgent = $userAgent ?: 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36';
        $this->timeout = $timeout;
    }

    public function get(string $url): ?string
    {
        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'header' => [
                    'User-Agent: ' . $this->userAgent,
                    'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                    'Accept-Language: en-GB,en;q=0.9',
                ],
                'timeout' => $this->timeout,
            ],
            'https' => [
                'method' => 'GET',
                'header' => [
                    'User-Agent: ' . $this->userAgent,
                    'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                    'Accept-Language: en-GB,en;q=0.9',
                ],
                'timeout' => $this->timeout,
            ],
        ]);

        $html = @file_get_contents($url, false, $context);
        if ($html === false) {
            error_log('WOSFL fetch failed: ' . $url);
            return null;
        }

        return $html;
    }
}

class TeamRepository
{
    private $pdo;

    public function __construct(?PDO $pdo = null)
    {
        $this->pdo = $pdo ?: db();
    }

    private function normalizedNameExpr(): string
    {
        return "LOWER(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(TRIM(name), '.', ''), '-', ''), '''', ''), CHAR(8217), ''), CHAR(8216), ''))";
    }

    public function findByNormalizedName(string $name, ?int $clubId = null): array
    {
        $normalized = WosflNameNormalizer::normalize($name);
        if ($normalized === '') {
            return [];
        }

        if ($clubId !== null && $clubId <= 0) {
            $clubId = null;
        }

        $sql = 'SELECT id, name FROM teams WHERE ' . $this->normalizedNameExpr() . ' = :normalized';
        $params = ['normalized' => $normalized];

        if ($clubId !== null) {
            $sql .= ' AND club_id = :club_id';
            $params['club_id'] = $clubId;
        }

        $sql .= ' ORDER BY name ASC LIMIT 5';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return $rows ?: [];
    }

    public function searchByNormalizedName(string $name, ?int $clubId = null, int $limit = 10): array
    {
        $normalized = WosflNameNormalizer::normalize($name);
        if ($normalized === '') {
            return [];
        }

        if ($clubId !== null && $clubId <= 0) {
            $clubId = null;
        }

        $limit = max(1, min($limit, 25));
        $sql = 'SELECT id, name FROM teams WHERE ' . $this->normalizedNameExpr() . ' LIKE :pattern';
        $params = ['pattern' => '%' . $normalized . '%'];

        if ($clubId !== null) {
            $sql .= ' AND club_id = :club_id';
            $params['club_id'] = $clubId;
        }

        $sql .= ' ORDER BY name ASC LIMIT ' . (int)$limit;
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return $rows ?: [];
    }

    public function findByName(string $name): ?array
    {
        $name = trim($name);
        if ($name === '') {
            return null;
        }

        $stmt = $this->pdo->prepare('SELECT id, name FROM teams WHERE LOWER(name) = LOWER(:name) LIMIT 1');
        $stmt->execute(['name' => $name]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    public function findByNameForClub(string $name, int $clubId): ?array
    {
        $name = trim($name);
        if ($name === '' || $clubId <= 0) {
            return null;
        }

        $stmt = $this->pdo->prepare('SELECT id, name FROM teams WHERE club_id = :club_id AND LOWER(name) = LOWER(:name) LIMIT 1');
        $stmt->execute([
            'club_id' => $clubId,
            'name' => $name,
        ]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    public function create(string $name, int $clubId, string $teamType = 'opponent'): ?array
    {
        $name = trim($name);
        if ($name === '' || $clubId <= 0) {
            return null;
        }

        $stmt = $this->pdo->prepare('INSERT INTO teams (club_id, name, team_type) VALUES (:club_id, :name, :team_type)');
        $stmt->execute([
            'club_id' => $clubId,
            'name' => $name,
            'team_type' => $teamType,
        ]);

        $id = (int)$this->pdo->lastInsertId();
        return [
            'id' => $id,
            'name' => $name,
        ];
    }
}

class CompetitionRepository
{
    private $pdo;

    public function __construct(?PDO $pdo = null)
    {
        $this->pdo = $pdo ?: db();
    }

    private function normalizedNameExpr(): string
    {
        return "LOWER(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(TRIM(name), '.', ''), '-', ''), '''', ''), CHAR(8217), ''), CHAR(8216), ''))";
    }

    public function findByNormalizedName(string $name, ?int $clubId = null): array
    {
        $normalized = WosflNameNormalizer::normalize($name);
        if ($normalized === '') {
            return [];
        }

        if ($clubId !== null && $clubId <= 0) {
            $clubId = null;
        }

        $sql = 'SELECT id, name, season_id, type, club_id FROM competitions WHERE ' . $this->normalizedNameExpr() . ' = :normalized';
        $params = ['normalized' => $normalized];

        if ($clubId !== null) {
            $sql .= ' AND club_id = :club_id';
            $params['club_id'] = $clubId;
        }

        $sql .= ' ORDER BY name ASC LIMIT 5';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return $rows ?: [];
    }

    public function searchByNormalizedName(string $name, ?int $clubId = null, int $limit = 10): array
    {
        $normalized = WosflNameNormalizer::normalize($name);
        if ($normalized === '') {
            return [];
        }

        if ($clubId !== null && $clubId <= 0) {
            $clubId = null;
        }

        $limit = max(1, min($limit, 25));
        $sql = 'SELECT id, name, season_id, type, club_id FROM competitions WHERE ' . $this->normalizedNameExpr() . ' LIKE :pattern';
        $params = ['pattern' => '%' . $normalized . '%'];

        if ($clubId !== null) {
            $sql .= ' AND club_id = :club_id';
            $params['club_id'] = $clubId;
        }

        $sql .= ' ORDER BY name ASC LIMIT ' . (int)$limit;
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return $rows ?: [];
    }

    public function findByName(string $name): ?array
    {
        $name = trim($name);
        if ($name === '') {
            return null;
        }

        $stmt = $this->pdo->prepare('SELECT id, name FROM competitions WHERE LOWER(name) = LOWER(:name) LIMIT 1');
        $stmt->execute(['name' => $name]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    public function findByNameForClub(string $name, int $clubId): ?array
    {
        $name = trim($name);
        if ($name === '' || $clubId <= 0) {
            return null;
        }

        $stmt = $this->pdo->prepare('SELECT id, name, season_id, type FROM competitions WHERE club_id = :club_id AND LOWER(name) = LOWER(:name) LIMIT 1');
        $stmt->execute([
            'club_id' => $clubId,
            'name' => $name,
        ]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    public function findById(int $id): ?array
    {
        if ($id <= 0) {
            return null;
        }

        $stmt = $this->pdo->prepare('SELECT id, name, season_id, type, club_id FROM competitions WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    public function create(string $name, int $clubId, int $seasonId, string $type): ?array
    {
        $name = trim($name);
        if ($name === '' || $clubId <= 0 || $seasonId <= 0) {
            return null;
        }

        $stmt = $this->pdo->prepare('INSERT INTO competitions (club_id, season_id, name, type) VALUES (:club_id, :season_id, :name, :type)');
        $stmt->execute([
            'club_id' => $clubId,
            'season_id' => $seasonId,
            'name' => $name,
            'type' => $type,
        ]);

        $id = (int)$this->pdo->lastInsertId();
        return [
            'id' => $id,
            'name' => $name,
            'season_id' => $seasonId,
            'type' => $type,
        ];
    }
}

class LeagueIntelligenceMatchRepository
{
    private $pdo;

    public function __construct(?PDO $pdo = null)
    {
        $this->pdo = $pdo ?: db();
    }

    public function exists(string $kickoffAt, int $homeTeamId, int $awayTeamId, int $competitionId): bool
    {
        try {
            $stmt = $this->pdo->prepare('
                SELECT 1
                FROM league_intelligence_matches
                WHERE kickoff_at = :kickoff_at
                  AND competition_id = :competition_id
                  AND (
                        (home_team_id = :home_team_id AND away_team_id = :away_team_id)
                        OR (home_team_id = :away_team_id AND away_team_id = :home_team_id)
                  )
                LIMIT 1
            ');

            $stmt->execute([
                'kickoff_at' => $kickoffAt,
                'home_team_id' => $homeTeamId,
                'away_team_id' => $awayTeamId,
                'competition_id' => $competitionId,
            ]);

            return (bool)$stmt->fetchColumn();
        } catch (Throwable $e) {
            error_log('League intelligence match lookup failed: ' . $e->getMessage());
            return false;
        }
    }

    public function findByKickoffAndTeams(string $kickoffAt, int $homeTeamId, int $awayTeamId, ?int $competitionId = null): array
    {
        try {
            $sql = '
                SELECT match_id, competition_id, season_id, home_team_id, away_team_id, home_goals, away_goals, status
                FROM league_intelligence_matches
                WHERE kickoff_at = :kickoff_at
                  AND (
                        (home_team_id = :home_team_id AND away_team_id = :away_team_id)
                        OR (home_team_id = :away_team_id AND away_team_id = :home_team_id)
                  )
            ';

            $params = [
                'kickoff_at' => $kickoffAt,
                'home_team_id' => $homeTeamId,
                'away_team_id' => $awayTeamId,
            ];

            if ($competitionId !== null && $competitionId > 0) {
                $sql .= ' AND competition_id = :competition_id';
                $params['competition_id'] = $competitionId;
            }

            $sql .= ' ORDER BY match_id ASC';

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Throwable $e) {
            error_log('League intelligence match query failed: ' . $e->getMessage());
            return [];
        }
    }

    public function updateMatch(int $matchId, array $data): void
    {
        $stmt = $this->pdo->prepare('
            UPDATE league_intelligence_matches
            SET home_goals = :home_goals,
                away_goals = :away_goals,
                status = :status,
                updated_at = NOW()
            WHERE match_id = :match_id
        ');

        $stmt->execute([
            'home_goals' => $data['home_goals'],
            'away_goals' => $data['away_goals'],
            'status' => $data['status'],
            'match_id' => $matchId,
        ]);
    }

    public function insertMatch(array $data): void
    {
        $stmt = $this->pdo->prepare('
            INSERT INTO league_intelligence_matches
            (match_id, competition_id, season_id, home_team_id, away_team_id, kickoff_at, home_goals, away_goals, status, neutral_location)
            VALUES
            (:match_id, :competition_id, :season_id, :home_team_id, :away_team_id, :kickoff_at, :home_goals, :away_goals, :status, 0)
        ');

        $stmt->execute([
            'match_id' => $data['match_id'],
            'competition_id' => $data['competition_id'],
            'season_id' => $data['season_id'],
            'home_team_id' => $data['home_team_id'],
            'away_team_id' => $data['away_team_id'],
            'kickoff_at' => $data['kickoff_at'],
            'home_goals' => $data['home_goals'],
            'away_goals' => $data['away_goals'],
            'status' => $data['status'],
        ]);
    }

    public function nextSyntheticMatchId(int $baseId = 1000000000000): int
    {
        // Use a high ID range to avoid collisions with match IDs synced from the core matches table.
        $stmt = $this->pdo->prepare('SELECT MAX(match_id) AS max_id FROM league_intelligence_matches WHERE match_id >= :base_id');
        $stmt->execute(['base_id' => $baseId]);
        $maxId = (int)$stmt->fetchColumn();

        return $maxId >= $baseId ? $maxId + 1 : $baseId;
    }
}

class WosflImportService
{
    /**
     * Parses a stored UTC datetime string (Y-m-d H:i:s) into DateTimeImmutable.
     * Returns null if invalid.
     */
    private function parseStoredDateTime(?string $value): ?DateTimeImmutable
    {
        $value = trim((string)$value);
        if ($value === '') {
            return null;
        }

        $dt = DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $value, $this->targetTimezone);
        if ($dt instanceof DateTimeImmutable) {
            return $dt;
        }

        // Fallback: let DateTimeImmutable try to parse it
        try {
            return new DateTimeImmutable($value, $this->targetTimezone);
        } catch (\Throwable $e) {
            error_log('WOSFL: parseStoredDateTime failed: ' . $value . ' (' . $e->getMessage() . ')');
            return null;
        }
    }
    private const BASE_URL = 'https://www.wosfl.co.uk';
    private const STANDINGS_URL = 'https://www.wosfl.co.uk/standingsForDate/847802708/2/-1/-1.html';

    private $teamRepository;
    private $competitionRepository;
    private $matchRepository;
    private $httpClient;
    private $sourceTimezone;
    private $targetTimezone;

    public function __construct(
        ?TeamRepository $teamRepository = null,
        ?CompetitionRepository $competitionRepository = null,
        ?LeagueIntelligenceMatchRepository $matchRepository = null,
        ?WosflHttpClient $httpClient = null,
        ?DateTimeZone $sourceTimezone = null,
        ?DateTimeZone $targetTimezone = null
    ) {
        $this->teamRepository = $teamRepository ?: new TeamRepository();
        $this->competitionRepository = $competitionRepository ?: new CompetitionRepository();
        $this->matchRepository = $matchRepository ?: new LeagueIntelligenceMatchRepository();
        $this->httpClient = $httpClient ?: new WosflHttpClient();
        $this->sourceTimezone = $sourceTimezone ?: new DateTimeZone('Europe/London');
        $this->targetTimezone = $targetTimezone ?: new DateTimeZone('UTC');
    }

    public function scrapeAll(): array
    {
        $teams = $this->fetchTeamsFromStandings();
        if (empty($teams)) {
            return [];
        }

        $matches = [];
        $seen = [];

        foreach ($teams as $team) {
            $teamUrl = $team['url'] ?? '';
            $teamName = $team['name'] ?? '';
            if ($teamUrl === '') {
                continue;
            }

            $matchHubLinks = $this->fetchMatchHubLinks($teamUrl);
            foreach ($matchHubLinks as $link) {
                $this->scrapeMatchHub($link, $matches, $seen, $teamName);
            }
        }

        return array_values($matches);
    }

    public function scrapeWeek(): array
    {
        $matches = $this->scrapeAll();
        if (empty($matches)) {
            return [];
        }

        $today = new DateTimeImmutable('today', $this->targetTimezone);
        $start = $today->modify('-7 days');
        $end = $today->modify('+7 days')->setTime(23, 59, 59);

        $filtered = [];
        foreach ($matches as $match) {
            $dateTime = $match['date_time'] ?? null;
            $score = $match['home_goals'] ?? null;
            $hasScore = isset($match['home_goals'], $match['away_goals']) && is_numeric($match['home_goals']) && is_numeric($match['away_goals']);

            if (empty($dateTime)) {
                // Defensive: If match is completed (has score) but date_time is missing, log and skip.
                if ($hasScore) {
                    error_log('WOSFL: Completed match missing date_time, not included: ' . json_encode($match));
                }
                continue;
            }

            $parsed = $this->parseStoredDateTime($dateTime);
            if (!$parsed) {
                // Defensive: If match is completed but date_time is unparseable, log and skip.
                if ($hasScore) {
                    error_log('WOSFL: Completed match with unparseable date_time, not included: ' . json_encode($match));
                }
                continue;
            }

            // Only include matches within ±7 days of today (inclusive)
            if ($parsed >= $start && $parsed <= $end) {
                // Allow completed matches even if kickoff time is defaulted (e.g., 15:00:00), as long as date is valid.
                // This ensures matches are not excluded just because time is imprecise.
                $filtered[] = $match;
            } else {
                // Defensive: If match is completed but outside window, log reason.
                if ($hasScore) {
                    error_log('WOSFL: Completed match outside ±7 day window, not included: ' . json_encode($match));
                }
            }
        }

        /*
         * Filtering logic:
         * - Only include matches with a valid, parseable date_time within ±7 days of today.
         * - Completed matches (with a valid score) are included even if kickoff time is defaulted (e.g., 15:00:00).
         * - Defensive: If a completed match is missing or has an unparseable date_time, log and skip (do not silently drop).
         * - Matches qualify for inclusion if their date_time is valid and in range, regardless of time precision.
         */

        return $filtered;
    }

    public function preparePreviewRows(array $matches): array
    {
        if (empty($matches)) {
            return [];
        }

        $preview = [];
        $teamCache = [];
        $competitionCache = [];

        foreach ($matches as $match) {
            if (!is_array($match)) {
                continue;
            }

            $homeName = trim((string)($match['home_team_name'] ?? ''));
            $awayName = trim((string)($match['away_team_name'] ?? ''));
            $competitionName = trim((string)($match['competition_name'] ?? ''));

            $homeLookup = $this->resolveTeamMatch($homeName, $teamCache);
            $awayLookup = $this->resolveTeamMatch($awayName, $teamCache);
            $competitionLookup = $this->resolveCompetitionMatch($competitionName, $competitionCache);

            $homeTeam = $homeLookup['match'] ?? null;
            $awayTeam = $awayLookup['match'] ?? null;
            $competition = $competitionLookup['match'] ?? null;

            $homeTeamId = $homeTeam['id'] ?? null;
            $awayTeamId = $awayTeam['id'] ?? null;
            $competitionId = $competition['id'] ?? null;

            $homeFound = ($homeLookup['status'] ?? '') === 'found';
            $awayFound = ($awayLookup['status'] ?? '') === 'found';
            $competitionFound = ($competitionLookup['status'] ?? '') === 'found';
            $teamFound = $homeFound && $awayFound;

            $homeAmbiguous = ($homeLookup['status'] ?? '') === 'ambiguous';
            $awayAmbiguous = ($awayLookup['status'] ?? '') === 'ambiguous';
            $competitionAmbiguous = ($competitionLookup['status'] ?? '') === 'ambiguous';

            $existingMatch = false;
            $kickoffAt = $match['date_time'] ?? null;
            if ($teamFound && $competitionFound && !empty($kickoffAt)) {
                $existingMatch = $this->matchRepository->exists(
                    $kickoffAt,
                    (int)$homeTeamId,
                    (int)$awayTeamId,
                    (int)$competitionId
                );
            }

            $preview[] = array_merge($match, [
                'home_team_id' => $homeTeamId !== null ? (int)$homeTeamId : null,
                'away_team_id' => $awayTeamId !== null ? (int)$awayTeamId : null,
                'competition_id' => $competitionId !== null ? (int)$competitionId : null,
                'team_found' => $teamFound,
                'home_team_found' => $homeFound,
                'away_team_found' => $awayFound,
                'home_team_ambiguous' => $homeAmbiguous,
                'away_team_ambiguous' => $awayAmbiguous,
                'competition_found' => $competitionFound,
                'competition_ambiguous' => $competitionAmbiguous,
                'existing_match' => $existingMatch,
            ]);
        }

        return $preview;
    }

    public function saveImportRows(array $rows): int
    {
        return count($rows);
    }

    private function resolveTeamMatch(string $name, array &$cache): array
    {
        return $this->resolveEntityMatch(
            $name,
            $cache,
            function (string $value): array {
                return $this->teamRepository->findByNormalizedName($value);
            },
            function (string $value): array {
                return $this->teamRepository->searchByNormalizedName($value);
            }
        );
    }

    private function resolveCompetitionMatch(string $name, array &$cache): array
    {
        return $this->resolveEntityMatch(
            $name,
            $cache,
            function (string $value): array {
                return $this->competitionRepository->findByNormalizedName($value);
            },
            function (string $value): array {
                return $this->competitionRepository->searchByNormalizedName($value);
            }
        );
    }

    private function resolveEntityMatch(string $name, array &$cache, callable $exactFinder, callable $partialFinder): array
    {
        $normalized = WosflNameNormalizer::normalize($name);
        if ($normalized === '') {
            return [
                'match' => null,
                'status' => 'missing',
                'matches' => 0,
            ];
        }

        if (array_key_exists($normalized, $cache)) {
            return $cache[$normalized];
        }

        $exactMatches = $exactFinder($name);
        $exactCount = is_array($exactMatches) ? count($exactMatches) : 0;

        if ($exactCount === 1) {
            $cache[$normalized] = [
                'match' => $exactMatches[0],
                'status' => 'found',
                'matches' => 1,
            ];
            return $cache[$normalized];
        }

        if ($exactCount > 1) {
            $cache[$normalized] = [
                'match' => null,
                'status' => 'ambiguous',
                'matches' => $exactCount,
            ];
            return $cache[$normalized];
        }

        $partialMatches = $partialFinder($name);
        $partialCount = is_array($partialMatches) ? count($partialMatches) : 0;

        if ($partialCount === 1) {
            $cache[$normalized] = [
                'match' => $partialMatches[0],
                'status' => 'found',
                'matches' => 1,
            ];
            return $cache[$normalized];
        }

        if ($partialCount > 1) {
            $cache[$normalized] = [
                'match' => null,
                'status' => 'ambiguous',
                'matches' => $partialCount,
            ];
            return $cache[$normalized];
        }

        $cache[$normalized] = [
            'match' => null,
            'status' => 'missing',
            'matches' => 0,
        ];

        return $cache[$normalized];
    }

    private function fetchTeamsFromStandings(): array
    {
        $html = $this->httpClient->get(self::STANDINGS_URL);
        if ($html === null) {
            return [];
        }

        $dom = $this->createDom($html);
        if (!$dom) {
            return [];
        }

        $xpath = new DOMXPath($dom);
        $nodes = $xpath->query("//div[contains(@class,'table-scroll')]//table//a[contains(@href, '/team/')]");

        $teams = [];
        if ($nodes) {
            foreach ($nodes as $node) {
                $name = $this->normalizeWhitespace($node->textContent ?? '');
                $href = $node->getAttribute('href');
                if ($name === '' || $href === '') {
                    continue;
                }
                $url = $this->resolveUrl(self::BASE_URL, $href);
                $teams[$url] = [
                    'name' => $name,
                    'url' => $url,
                ];
            }
        }

        return array_values($teams);
    }

    private function fetchMatchHubLinks(string $teamUrl): array
    {
        $html = $this->httpClient->get($teamUrl);
        if ($html === null) {
            return [];
        }

        $dom = $this->createDom($html);
        if (!$dom) {
            return [];
        }

        $xpath = new DOMXPath($dom);
        $nodes = $xpath->query("//a[contains(@href, '/matchHub/') and (contains(normalize-space(.), 'View All Results') or contains(normalize-space(.), 'View All Matches'))]");

        $links = [];
        if ($nodes) {
            foreach ($nodes as $node) {
                $href = $node->getAttribute('href');
                if ($href === '') {
                    continue;
                }
                $url = $this->resolveUrl(self::BASE_URL, $href);
                $links[$url] = $url;
            }
        }

        return array_values($links);
    }

    private function scrapeMatchHub(string $url, array &$matches, array &$seen, string $sourceTeamName = ''): void
    {
        $nextUrl = $url;
        $visited = [];

        while ($nextUrl) {
            if (isset($visited[$nextUrl])) {
                break;
            }
            $visited[$nextUrl] = true;

            $html = $this->httpClient->get($nextUrl);
            if ($html === null) {
                break;
            }

            $page = $this->parseMatchHubPage($html, $nextUrl);
            foreach ($page['matches'] as $match) {
                if ($sourceTeamName !== '') {
                    $match['source_team_name'] = $sourceTeamName;
                }
                $key = $this->buildMatchKey($match);
                if ($key === '' || isset($seen[$key])) {
                    continue;
                }
                $seen[$key] = true;
                $matches[] = $match;
            }

            $nextUrl = $page['next_url'];
        }
    }

    private function buildMatchKey(array $match): string
    {
        $dateTime = $this->normalizeWhitespace((string)($match['date_time'] ?? ''));
        $homeTeam = $this->normalizeWhitespace((string)($match['home_team_name'] ?? ''));
        $awayTeam = $this->normalizeWhitespace((string)($match['away_team_name'] ?? ''));
        $competition = $this->normalizeWhitespace((string)($match['competition_name'] ?? ''));

        return strtolower(trim($dateTime . '|' . $homeTeam . '|' . $awayTeam . '|' . $competition));
    }

    private function parseMatchHubPage(string $html, string $baseUrl): array
    {
        $dom = $this->createDom($html);
        if (!$dom) {
            return ['matches' => [], 'next_url' => null];
        }

        $xpath = new DOMXPath($dom);
        $rows = $xpath->query("//table[contains(@class, 'js--clickable-rows')]//tbody//tr[@data-match-href]");

        $matches = [];
        if ($rows) {
            foreach ($rows as $row) {
                $parsed = $this->parseMatchRow($row, $xpath);
                if ($parsed !== null) {
                    $matches[] = $parsed;
                }
            }
        }

        $nextUrl = $this->findNextPageUrl($xpath, $baseUrl);

        return [
            'matches' => $matches,
            'next_url' => $nextUrl,
        ];
    }

    private function parseMatchRow(DOMElement $row, DOMXPath $xpath): ?array
    {
        $cells = $row->getElementsByTagName('td');
        if ($cells->length < 4) {
            return null;
        }

        $dateCell = $cells->item(0);
        $homeCell = $cells->item(1);
        $scoreCell = $cells->item(2);
        $awayCell = $cells->item(3);
        $detailsCell = $cells->length > 4 ? $cells->item(4) : null;

        $dateTime = $this->parseDateTimeCell($dateCell);
        $homeTeam = $this->normalizeWhitespace($homeCell ? $homeCell->textContent : '');
        $awayTeam = $this->normalizeWhitespace($awayCell ? $awayCell->textContent : '');
        $competitionName = $this->extractCompetitionName($detailsCell, $xpath);

        $score = $this->parseScore($scoreCell ? $scoreCell->textContent : '');
        $status = $score ? 'completed' : 'scheduled';

        $sourceUrl = $row->getAttribute('data-match-href');
        if ($sourceUrl === '') {
            $sourceUrl = '';
        } else {
            $sourceUrl = $this->resolveUrl(self::BASE_URL, $sourceUrl);
        }

        return [
            'date_time' => $dateTime,
            'home_team_name' => $homeTeam,
            'away_team_name' => $awayTeam,
            'competition_name' => $competitionName,
            'home_goals' => $score['home'] ?? null,
            'away_goals' => $score['away'] ?? null,
            'status' => $status,
            'source_url' => $sourceUrl,
        ];
    }

    private function parseDateTimeCell(?DOMElement $cell): ?string
    {
        if (!$cell) {
            return null;
        }

        $text = $this->normalizeWhitespace($cell->textContent ?? '');
        if ($text === '') {
            return null;
        }

        // Try to parse using DateTimeImmutable directly (handles most textual formats)
        $dt = null;
        $parseText = $text;
        $hasTime = preg_match('/\b\d{1,2}:\d{2}\b/', $parseText);

        // If no time is present, append default time (15:00)
        if (!$hasTime) {
            $parseText = trim($parseText) . ' 15:00';
        }

        // Try parsing with Carbon if available, else DateTimeImmutable
        try {
            if (class_exists('Carbon\\Carbon')) {
                $dt = \Carbon\Carbon::parse($parseText, $this->sourceTimezone);
            } else {
                $dt = new DateTimeImmutable($parseText, $this->sourceTimezone);
            }
        } catch (\Throwable $e) {
            error_log('WOSFL: Failed to parse date: ' . $parseText);
            $dt = null;
        }

        // If parsing failed, try fallback regex for dd/mm/yyyy and dd/mm/yy
        if (!$dt) {
            $date = null;
            $time = null;
            if (preg_match('/\b(\d{1,2}\/\d{1,2}\/\d{2,4})\b/', $text, $matches)) {
                $date = $matches[1];
            }
            if (preg_match('/\b(\d{1,2}:\d{2})\b/', $text, $matches)) {
                $time = $matches[1];
            }
            // If no time, default to 15:00
            if (!$time) {
                $time = '15:00';
            }
            if ($date) {
                $format = (strlen(substr($date, strrpos($date, '/') + 1)) === 4) ? 'd/m/Y' : 'd/m/y';
                $format .= ' H:i';
                $value = $date . ' ' . $time;
                try {
                    if (class_exists('Carbon\\Carbon')) {
                        $dt = \Carbon\Carbon::createFromFormat($format, $value, $this->sourceTimezone);
                    } else {
                        $dt = DateTimeImmutable::createFromFormat($format, $value, $this->sourceTimezone);
                    }
                } catch (\Throwable $e) {
                    error_log('WOSFL date parse failed: ' . $value . ' (' . $e->getMessage() . ')');
                    return null;
                }
            }
        }

        if (!$dt) {
            return null;
        }

        // Always return UTC datetime string in Y-m-d H:i:s
        $dt = $dt->setTimezone($this->targetTimezone);
        return $dt->format('Y-m-d H:i:s');
    }

    /**
     * Parses a score string like "2 - 1" and returns ['home'=>int,'away'=>int], or null if not matched.
     */
    private function parseScore(?string $text): ?array
    {
        $text = $this->normalizeWhitespace((string)$text);
        if ($text === '') {
            return null;
        }

        if (preg_match('/(\d+)\s*-\s*(\d+)/', $text, $matches)) {
            return [
                'home' => (int)$matches[1],
                'away' => (int)$matches[2],
            ];
        }

        return null;
    }

    private function extractCompetitionName(?DOMElement $detailsCell, DOMXPath $xpath): string
    {
        if (!$detailsCell) {
            return '';
        }

        $span = $xpath->query(".//span[contains(@class, 'bold')]", $detailsCell);
        if ($span && $span->length > 0) {
            return $this->normalizeWhitespace($span->item(0)->textContent ?? '');
        }

        return $this->normalizeWhitespace($detailsCell->textContent ?? '');
    }

    private function findNextPageUrl(DOMXPath $xpath, string $baseUrl): ?string
    {
        $candidates = $xpath->query("//a[@rel='next'] | //a[contains(normalize-space(.), 'Next')] | //a[contains(@class, 'next')]");
        if (!$candidates || $candidates->length === 0) {
            return null;
        }

        foreach ($candidates as $candidate) {
            $href = $candidate->getAttribute('href');
            if ($href === '' || $href === '#') {
                continue;
            }
            return $this->resolveUrl($baseUrl, $href);
        }

        return null;
    }

    private function createDom(string $html): ?DOMDocument
    {
        $dom = new DOMDocument();
        libxml_use_internal_errors(true);

        // Use modern, non-deprecated encoding injection for PHP 8.2+
        $loaded = $dom->loadHTML(
            '<?xml encoding="UTF-8">' . $html,
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
        );

        libxml_clear_errors();

        return $loaded ? $dom : null;
    }

    private function resolveUrl(string $baseUrl, string $href): string
    {
        if (strpos($href, 'http://') === 0 || strpos($href, 'https://') === 0) {
            return $href;
        }

        if ($href === '') {
            return $baseUrl;
        }

        if ($href[0] === '/') {
            return rtrim(self::BASE_URL, '/') . $href;
        }

        return rtrim($baseUrl, '/') . '/' . ltrim($href, '/');
    }

    private function normalizeWhitespace(string $value): string
    {
        $value = preg_replace('/\s+/', ' ', $value);
        return trim($value ?? '');
    }
}
