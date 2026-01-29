<?php
require_once __DIR__ . '/../../../../lib/auth.php';
require_once __DIR__ . '/../../../../lib/player_repository.php';
require_once __DIR__ . '/../../../../lib/team_repository.php';

$context = require_club_admin_access();
$clubId = $context['club_id'];

$playerId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$isEdit = $playerId > 0;
$player = null;

if ($isEdit) {
          $player = get_player_by_id($playerId, $clubId);
          if (!$player) {
                    http_response_code(404);
                    echo '404 Not Found';
                    exit;
          }
}

$teams = get_teams_by_club($clubId);

$flashError = null;
$formInput = [];

if ($isEdit) {
          $flashError = $_SESSION['player_edit_error'] ?? null;
          if (isset($_SESSION['player_edit_target']) && (int)$_SESSION['player_edit_target'] === $playerId) {
                    $formInput = $_SESSION['player_edit_input'] ?? [];
          }
          unset($_SESSION['player_edit_error'], $_SESSION['player_edit_input'], $_SESSION['player_edit_target']);
} else {
          $flashError = $_SESSION['player_create_error'] ?? null;
          $formInput = $_SESSION['player_create_input'] ?? [];
          unset($_SESSION['player_create_error'], $_SESSION['player_create_input']);
}

function field_value(array $formInput, array $player, string $key, $default = null)
{
          if (isset($formInput[$key]) && $formInput[$key] !== '') {
                    return $formInput[$key];
          }

          if ($player && isset($player[$key]) && $player[$key] !== '') {
                    return $player[$key];
          }

          return $default;
}

$values = [
          'first_name' => field_value($formInput, $player ?? [], 'first_name', ''),
          'last_name' => field_value($formInput, $player ?? [], 'last_name', ''),
          'primary_position' => field_value($formInput, $player ?? [], 'primary_position', ''),
          'dob' => field_value($formInput, $player ?? [], 'dob', ''),
          'team_id' => field_value($formInput, $player ?? [], 'team_id', ''),
          'is_active' => field_value($formInput, $player ?? [], 'is_active', $isEdit ? ($player['is_active'] ?? 0) : 1),
];

$title = $isEdit ? 'Edit Player' : 'Create Player';
$base = base_path();
$action = $isEdit ? $base . '/admin/players/' . $playerId : $base . '/admin/players';

ob_start();
?>
<div class="flex items-center justify-between mb-6">
          <div>
                    <h1 class="mb-1"><?= htmlspecialchars($title) ?></h1>
                    <p class="text-muted-alt text-sm mb-0">Maintain player info like positions, assignments, and status.</p>
          </div>
        <a href="<?= htmlspecialchars($base) ?>/admin/players" class="inline-flex items-center gap-2 bg-bg-secondary text-text-primary border border-border-soft px-4 py-2 rounded-md hover:bg-bg-secondary/80 transition">Back to players</a>
</div>

<?php if ($flashError): ?>
          <div class="alert alert-danger"><?= htmlspecialchars($flashError) ?></div>
<?php endif; ?>

<div class="panel p-4">
          <form method="post" action="<?= htmlspecialchars($action) ?>">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                              <div>
                                        <label class="form-label">First name</label>
                                        <input type="text" name="first_name" value="<?= htmlspecialchars($values['first_name']) ?>" class="input-dark w-full" required>
                              </div>
                              <div>
                                        <label class="form-label">Last name</label>
                                        <input type="text" name="last_name" value="<?= htmlspecialchars($values['last_name']) ?>" class="input-dark w-full" required>
                              </div>
                              <div>
                                        <label class="form-label">Primary position</label>
                                        <select name="primary_position" class="input-dark w-full">
                                                  <option value="">Unassigned</option>
                                                  <?php
                                                  // Fetch unique positions from formation_positions
                                                  $positions = [];
                                                  $pdo = db();
                                                  $stmt = $pdo->query("SELECT DISTINCT position_label FROM formation_positions ORDER BY position_label");
                                                  while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                                                      $positions[] = $row['position_label'];
                                                  }
                                                  foreach ($positions as $pos): ?>
                                                      <option value="<?= htmlspecialchars($pos) ?>" <?= $values['primary_position'] === $pos ? 'selected' : '' ?>><?= htmlspecialchars($pos) ?></option>
                                                  <?php endforeach; ?>
                                        </select>
                              </div>
                                        <input type="date" name="dob" value="<?= htmlspecialchars($values['dob']) ?>" class="input-dark w-full">
                              </div>
                              <div>
                                        <label class="form-label">Team (optional)</label>
                                        <select name="team_id" class="select-dark w-full">
                                                  <option value="">Unassigned</option>
                                                  <?php foreach ($teams as $team): ?>
                                                            <option value="<?= (int)$team['id'] ?>" <?= $values['team_id'] !== '' && (int)$team['id'] === (int)$values['team_id'] ? 'selected' : '' ?>>
                                                                      <?= htmlspecialchars($team['name']) ?>
                                                            </option>
                                                  <?php endforeach; ?>
                                        </select>
                              </div>
                    </div>

                    <div class="form-check mt-4">
                              <input type="hidden" name="is_active" value="0">
                              <input class="form-check-input" type="checkbox" id="isActive" name="is_active" value="1" <?= (int)$values['is_active'] === 1 ? 'checked' : '' ?>>
                              <label class="form-check-label" for="isActive">Active player</label>
                    </div>

                    <div class="d-flex justify-content-between align-items-center mt-4">
                              <span class="text-muted-alt text-sm">Changes are tracked in the audit log.</span>
                            <button type="submit" class="inline-flex items-center gap-2 bg-accent-primary text-white px-4 py-2 rounded-md hover:bg-accent-primary/80 transition"><?= $isEdit ? 'Update player' : 'Create player' ?></button>
                    </div>
          </form>
</div>
<?php
$content = ob_get_clean();
require __DIR__ . '/../../../layout.php';