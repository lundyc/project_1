<?php
require_once __DIR__ . '/../../../../lib/auth.php';
require_once __DIR__ . '/../../../../lib/player_repository.php';

$context = require_club_admin_access();
$clubId = $context['club_id'];

$playerId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($playerId <= 0) {
          http_response_code(404);
          echo '404 Not Found';
          exit;
}

$player = get_player_by_id($playerId, $clubId);
if (!$player) {
          $stmt = db()->prepare('SELECT club_id FROM players WHERE id = :id LIMIT 1');
          $stmt->execute(['id' => $playerId]);
          $foundClub = $stmt->fetchColumn();

          if ($foundClub !== false) {
                    http_response_code(403);
                    echo '403 Forbidden';
                    exit;
          }

          http_response_code(404);
          echo '404 Not Found';
          exit;
}

$appearances = get_player_appearances($playerId, $clubId);
$eventStats = get_player_event_stats($playerId, $clubId);
$teamHistory = get_player_team_history($playerId, $clubId);
$matchIds = array_values(array_unique(array_column($appearances, 'match_id')));
$derivedStats = get_derived_stats_for_match_ids($matchIds, $playerId);

$appearanceMap = [];
foreach ($appearances as $appearance) {
          $appearanceMap[(int)$appearance['match_id']] = $appearance;
}

$title = $player['display_name'];
$base = base_path();

ob_start();
?>
<div class="d-flex align-items-center justify-content-between mb-4">
          <div>
                    <div class="text-muted-alt text-sm">Player profile</div>
                    <h1 class="mb-1"><?= htmlspecialchars($player['display_name']) ?></h1>
                    <p class="text-muted-alt text-sm mb-0"><?= htmlspecialchars($player['primary_position'] ?? 'Position not set') ?></p>
          </div>
          <div class="d-flex gap-2">
                    <a href="<?= htmlspecialchars($base) ?>/admin/players" class="btn btn-secondary-soft btn-sm">Back to players</a>
                    <a href="<?= htmlspecialchars($base) ?>/admin/players/<?= (int)$playerId ?>/edit" class="btn btn-primary-soft btn-sm">Edit player</a>
          </div>
</div>

<div class="panel p-4 mb-4">
          <div class="row g-3">
                    <div class="col-md-3">
                              <div class="text-muted-alt text-sm mb-1">Full name</div>
                              <div class="fw-semibold"><?= htmlspecialchars(trim(($player['first_name'] ?? '') . ' ' . ($player['last_name'] ?? '')) ?: $player['display_name']) ?></div>
                    </div>
                    <div class="col-md-3">
                              <div class="text-muted-alt text-sm mb-1">Current team</div>
                              <div><?= htmlspecialchars($player['team_name'] ?? 'Unassigned') ?></div>
                    </div>
                    <div class="col-md-3">
                              <div class="text-muted-alt text-sm mb-1">Status</div>
                              <div>
                                        <?php if ($player['is_active']): ?>
                                                  <span class="badge bg-success">Active</span>
                                        <?php else: ?>
                                                  <span class="badge bg-secondary">Inactive</span>
                                        <?php endif; ?>
                              </div>
                    </div>
                    <div class="col-md-3">
                              <div class="text-muted-alt text-sm mb-1">Date of birth</div>
                              <div><?= $player['dob'] ? htmlspecialchars(date('M j, Y', strtotime($player['dob']))) : 'Unknown' ?></div>
                    </div>
          </div>
</div>

<div class="panel p-3 mb-4">
          <div class="d-flex align-items-center justify-content-between mb-3">
                    <h5 class="mb-0 text-light">Match Appearances</h5>
                    <span class="text-muted-alt text-sm"><?= count($appearances) ?> recorded</span>
          </div>
          <?php if (empty($appearances)): ?>
                    <div class="text-muted-alt text-sm">No appearances recorded.</div>
          <?php else: ?>
                    <div class="match-table-wrap">
                              <table class="match-table mb-0">
                                        <thead class="match-table-head">
                                                  <tr>
                                                            <th>Date</th>
                                                            <th>Match</th>
                                                            <th>Side</th>
                                                            <th>Shirt</th>
                                                            <th>Role</th>
                                                  </tr>
                                        </thead>
                                        <tbody>
                                                  <?php foreach ($appearances as $appearance): ?>
                                                            <tr>
                                                                      <td><?= $appearance['kickoff_at'] ? htmlspecialchars(date('M j, Y', strtotime($appearance['kickoff_at']))) : 'TBD' ?></td>
                                                                      <td><?= htmlspecialchars($appearance['home_team']) ?> <span class="text-muted-alt">vs</span> <?= htmlspecialchars($appearance['away_team']) ?></td>
                                                                      <td><?= htmlspecialchars(ucfirst($appearance['team_side'] ?? 'unknown')) ?></td>
                                                                      <td><?= $appearance['shirt_number'] ? htmlspecialchars((string)$appearance['shirt_number']) : '—' ?></td>
                                                                      <td><?= $appearance['is_starting'] ? 'Starter' : 'Substitute' ?></td>
                                                            </tr>
                                                  <?php endforeach; ?>
                                        </tbody>
                              </table>
                    </div>
          <?php endif; ?>
</div>

<div class="panel p-3 mb-4">
          <div class="d-flex align-items-center justify-content-between mb-3">
                    <h5 class="mb-0 text-light">Event Stats</h5>
                    <span class="text-muted-alt text-sm">Aggregated from live events</span>
          </div>
          <?php if (empty($eventStats['matches'])): ?>
                    <div class="text-muted-alt text-sm">No event stats recorded.</div>
          <?php else: ?>
                    <div class="table-responsive">
                              <table class="table table-dark mb-0">
                                        <thead>
                                                  <tr>
                                                            <th>Date</th>
                                                            <th>Match</th>
                                                            <th>Total events</th>
                                                            <th>Goals</th>
                                                            <th>Assists</th>
                                                            <th>Shots</th>
                                                            <th>Tackles</th>
                                                            <th>Key passes</th>
                                                  </tr>
                                        </thead>
                                        <tbody>
                                                  <?php foreach ($eventStats['matches'] as $row): ?>
                                                            <?php
                                                                      $matchLabel = htmlspecialchars($row['home_team'] . ' vs ' . $row['away_team']);
                                                            ?>
                                                            <tr>
                                                                      <td><?= $row['kickoff_at'] ? htmlspecialchars(date('M j, Y', strtotime($row['kickoff_at']))) : 'TBD' ?></td>
                                                                      <td><?= $matchLabel ?></td>
                                                                      <td><?= (int)$row['total_events'] ?></td>
                                                                      <td><?= (int)$row['goals'] ?></td>
                                                                      <td><?= (int)$row['assists'] ?></td>
                                                                      <td><?= (int)$row['shots'] ?></td>
                                                                      <td><?= (int)$row['tackles'] ?></td>
                                                                      <td><?= (int)$row['key_passes'] ?></td>
                                                            </tr>
                                                  <?php endforeach; ?>
                                        </tbody>
                              </table>
                    </div>
                    <?php if (!empty($eventStats['seasons'])): ?>
                              <div class="mt-3">
                                        <h6 class="text-muted-alt text-sm mb-2">Season totals</h6>
                                        <div class="table-responsive">
                                                  <table class="table table-dark mb-0">
                                                            <thead>
                                                                      <tr>
                                                                                <th>Season</th>
                                                                                <th>Total events</th>
                                                                                <th>Goals</th>
                                                                                <th>Assists</th>
                                                                                <th>Shots</th>
                                                                                <th>Tackles</th>
                                                                                <th>Key passes</th>
                                                                      </tr>
                                                            </thead>
                                                            <tbody>
                                                                      <?php foreach ($eventStats['seasons'] as $season): ?>
                                                                                <tr>
                                                                                          <td><?= htmlspecialchars($season['season_name']) ?></td>
                                                                                          <td><?= (int)$season['total_events'] ?></td>
                                                                                          <td><?= (int)$season['goals'] ?></td>
                                                                                          <td><?= (int)$season['assists'] ?></td>
                                                                                          <td><?= (int)$season['shots'] ?></td>
                                                                                          <td><?= (int)$season['tackles'] ?></td>
                                                                                          <td><?= (int)$season['key_passes'] ?></td>
                                                                                </tr>
                                                                      <?php endforeach; ?>
                                                            </tbody>
                                                  </table>
                                        </div>
                              </div>
                    <?php endif; ?>
          <?php endif; ?>
</div>

<div class="panel p-3 mb-4">
          <div class="d-flex align-items-center justify-content-between mb-3">
                    <h5 class="mb-0 text-light">Derived stats</h5>
                    <span class="text-muted-alt text-sm">Read-only data from cached analytics</span>
          </div>
          <?php if (empty($derivedStats)): ?>
                    <div class="text-muted-alt text-sm">No derived stats available for this player.</div>
          <?php else: ?>
                    <?php foreach ($derivedStats as $entry): ?>
                              <div class="panel p-3 mb-3 bg-surface border border-soft">
                                        <?php
                                                  $matchInfo = $appearanceMap[$entry['match_id']] ?? null;
                                                  $matchLabel = $matchInfo
                                                            ? htmlspecialchars($matchInfo['home_team'] . ' vs ' . $matchInfo['away_team'])
                                                            : 'Match ' . (int)$entry['match_id'];
                                        ?>
                                        <div class="d-flex align-items-center justify-content-between mb-2">
                                                  <div>
                                                            <div class="text-muted-alt text-sm">Match</div>
                                                            <div class="fw-semibold"><?= $matchLabel ?></div>
                                                  </div>
                                                  <div class="text-muted-alt text-sm">
                                                            <?= $entry['computed_at'] ? htmlspecialchars(date('M j, Y · H:i', strtotime($entry['computed_at']))) : 'Computed recently' ?>
                                                  </div>
                                        </div>
                                        <?php if (empty($entry['metrics'])): ?>
                                                  <div class="text-muted-alt text-sm">No player metrics found in derived stats.</div>
                                        <?php else: ?>
                                                  <div class="row g-3">
                                                            <?php foreach ($entry['metrics'] as $metric): ?>
                                                                      <div class="col-md-4">
                                                                                <div class="summary-card p-2">
                                                                                          <div class="text-muted-alt text-xs"><?= htmlspecialchars($metric['label']) ?></div>
                                                                                          <div class="fw-semibold"><?= htmlspecialchars((string)$metric['value']) ?></div>
                                                                                </div>
                                                                      </div>
                                                            <?php endforeach; ?>
                                                  </div>
                                        <?php endif; ?>
                              </div>
                    <?php endforeach; ?>
          <?php endif; ?>
</div>

<?php if (!empty($teamHistory)): ?>
          <div class="panel p-3 mb-4">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                              <h5 class="mb-0 text-light">Team & season history</h5>
                              <span class="text-muted-alt text-sm"><?= count($teamHistory) ?> rows</span>
                    </div>
                    <div class="table-responsive">
                              <table class="table table-dark mb-0">
                                        <thead>
                                                  <tr>
                                                            <th>Season</th>
                                                            <th>Team</th>
                                                            <th>Recorded</th>
                                                  </tr>
                                        </thead>
                                        <tbody>
                                                  <?php foreach ($teamHistory as $row): ?>
                                                            <tr>
                                                                      <td><?= htmlspecialchars($row['season_name'] ?? 'Unassigned') ?></td>
                                                                      <td><?= htmlspecialchars($row['team_name']) ?></td>
                                                                      <td><?= htmlspecialchars(date('M j, Y', strtotime($row['created_at']))) ?></td>
                                                            </tr>
                                                  <?php endforeach; ?>
                                        </tbody>
                              </table>
                    </div>
          </div>
<?php endif; ?>

<?php
$content = ob_get_clean();
require __DIR__ . '/../../../layout.php';