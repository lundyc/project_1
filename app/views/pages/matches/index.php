<?php
require_auth();
require_once __DIR__ . '/../../../lib/match_repository.php';
require_once __DIR__ . '/../../../lib/match_permissions.php';

$user = current_user();
$roles = $_SESSION['roles'] ?? [];
$matches = get_matches_for_user($user);
$canManage = can_manage_matches($user, $roles);
$base = base_path();

$success = $_SESSION['match_form_success'] ?? null;
$error = $_SESSION['match_form_error'] ?? null;
unset($_SESSION['match_form_success'], $_SESSION['match_form_error']);

$title = 'Matches';

ob_start();
?>
<div class="d-flex align-items-center justify-content-between mb-4">
          <div>
                    <h1 class="mb-1">Matches</h1>
                    <p class="text-muted-alt text-sm mb-0">Matches visible to your club context.</p>
          </div>
          <?php if ($canManage): ?>
                    <a href="<?= htmlspecialchars($base) ?>/matches/create" class="btn btn-primary-soft btn-sm">Create Match</a>
          <?php endif; ?>
</div>

<?php if ($error): ?>
          <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
<?php elseif ($success): ?>
          <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
<?php endif; ?>

<?php if (empty($matches)): ?>
          <div class="panel match-panel p-3 text-muted-alt text-sm">No matches yet.</div>
<?php else: ?>
          <div class="match-table-wrap relative overflow-x-auto">
                    <table id="matchesTable" class="match-table w-100 text-sm align-middle mb-0">
                              <thead class="match-table-head">
                                        <tr>
                                                  <th scope="col">
                                                            <button type="button" class="sort-button" data-sort-key="match" data-sort-type="string">
                                                                      Match
                                                                      <i class="fa-solid fa-sort sort-icon"></i>
                                                            </button>
                                                  </th>
                                                  <th scope="col">
                                                            <button type="button" class="sort-button" data-sort-key="competition" data-sort-type="string">
                                                                      Competition
                                                                      <i class="fa-solid fa-sort sort-icon"></i>
                                                            </button>
                                                  </th>
                                                  <th scope="col">
                                                            <button type="button" class="sort-button" data-sort-key="date" data-sort-type="number">
                                                                      Date
                                                                      <i class="fa-solid fa-sort sort-icon"></i>
                                                            </button>
                                                  </th>
                                                  <th scope="col">
                                                            <button type="button" class="sort-button" data-sort-key="time" data-sort-type="number">
                                                                      KO Time
                                                                      <i class="fa-solid fa-sort sort-icon"></i>
                                                            </button>
                                                  </th>
                                                  <th scope="col">
                                                            <button type="button" class="sort-button" data-sort-key="status" data-sort-type="string">
                                                                      Status
                                                                      <i class="fa-solid fa-sort sort-icon"></i>
                                                            </button>
                                                  </th>
                                                  <th scope="col" class="text-end">Actions</th>
                                        </tr>
                              </thead>
                              <tbody>
                                        <?php foreach ($matches as $match): ?>
                                                  <?php
                                                            $kickoffTs = $match['kickoff_at'] ? strtotime($match['kickoff_at']) : null;
                                                            $kickoffDate = $kickoffTs ? date('M j, Y', $kickoffTs) : 'TBD';
                                                            $kickoffTime = $kickoffTs ? date('H:i', $kickoffTs) : 'TBD';
                                                            $kickoffSort = $kickoffTs ?: 0;
                                                            $deskUrl = $base . '/matches/' . (int)$match['id'] . '/desk';
                                                            $summaryUrl = $base . '/matches/' . (int)$match['id'] . '/summary';
                                                            $canManageMatch = can_manage_match_for_club($user, $roles, (int)$match['club_id']);
                                                            $deskLabel = $canManageMatch ? 'Analyse match' : 'View desk';
                                                            $statusKey = strtolower($match['status'] ?? '');
                                                            $statusClass = 'status-pill-neutral';
                                                            if ($statusKey === 'draft') $statusClass = 'status-pill-muted';
                                                  if ($statusKey === 'live') $statusClass = 'status-pill-live';
                                                  if (in_array($statusKey, ['final', 'complete', 'completed'], true)) $statusClass = 'status-pill-final';
                                                  $matchLabel = trim(($match['home_team'] ?? '') . ' vs ' . ($match['away_team'] ?? ''));
                                                  $competitionLabel = $match['competition'] ?? 'N/A';
                                                  ?>
                                                  <tr data-sort-match="<?= htmlspecialchars(strtolower($matchLabel), ENT_QUOTES) ?>"
                                                      data-sort-competition="<?= htmlspecialchars(strtolower($competitionLabel), ENT_QUOTES) ?>"
                                                      data-sort-date="<?= htmlspecialchars((string)$kickoffSort, ENT_QUOTES) ?>"
                                                      data-sort-time="<?= htmlspecialchars((string)$kickoffSort, ENT_QUOTES) ?>"
                                                      data-sort-status="<?= htmlspecialchars($statusKey, ENT_QUOTES) ?>">
                                                            <td>
                                                                      <div class="fw-semibold"><?= htmlspecialchars($match['home_team']) ?> <span class="text-muted-alt">vs</span> <?= htmlspecialchars($match['away_team']) ?></div>
                                                            </td>
                                                            <td><?= htmlspecialchars($competitionLabel) ?></td>
                                                            <td><?= htmlspecialchars($kickoffDate) ?></td>
                                                            <td><?= htmlspecialchars($kickoffTime) ?></td>
                                                            <td>
                                                                      <span class="status-pill <?= $statusClass ?>"><?= htmlspecialchars($match['status']) ?></span>
                                                            </td>
                                                            <td class="text-end">
                                                                      <div class="d-inline-flex align-items-center gap-2">
                                                                                <a href="<?= htmlspecialchars($summaryUrl) ?>" class="btn-icon btn-icon-secondary" aria-label="Match summary" data-bs-toggle="tooltip" data-bs-title="Summary" data-bs-placement="top">
                                                                                          <i class="fa-solid fa-clipboard-list"></i>
                                                                                </a>
                                                                                <a href="<?= htmlspecialchars($deskUrl) ?>" class="btn-icon btn-icon-primary" aria-label="<?= htmlspecialchars($deskLabel) ?>" data-bs-toggle="tooltip" data-bs-title="<?= htmlspecialchars($deskLabel) ?>" data-bs-placement="top">
                                                                                          <i class="fa-solid <?= $canManageMatch ? 'fa-chart-line' : 'fa-eye' ?>"></i>
                                                                                </a>
                                                                                <?php if ($canManageMatch): ?>
                                                                                          <a href="<?= htmlspecialchars($base) ?>/matches/<?= (int)$match['id'] ?>/edit" class="btn-icon btn-icon-secondary" aria-label="Edit match" data-bs-toggle="tooltip" data-bs-title="Edit match" data-bs-placement="top">
                                                                                                    <i class="fa-solid fa-pen"></i>
                                                                                          </a>
                                                                                          <form method="post" action="<?= htmlspecialchars($base) ?>/api/matches/<?= (int)$match['id'] ?>/delete" class="d-inline" onsubmit="return confirm('Delete this match?');">
                                                                                                    <input type="hidden" name="match_id" value="<?= (int)$match['id'] ?>">
                                                                                                    <button type="submit" class="btn-icon btn-icon-danger" aria-label="Delete match" data-bs-toggle="tooltip" data-bs-title="Delete match" data-bs-placement="top">
                                                                                                              <i class="fa-solid fa-trash"></i>
                                                                                                    </button>
                                                                                          </form>
                                                                                <?php endif; ?>
                                                                      </div>
                                                            </td>
                                                  </tr>
                                        <?php endforeach; ?>
                              </tbody>
                    </table>
          </div>
<?php endif; ?>
<?php
$content = ob_get_clean();
$footerScripts = <<<HTML
<script>
document.addEventListener('DOMContentLoaded', function () {
          var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
          tooltipTriggerList.forEach(function (tooltipTriggerEl) {
                    new bootstrap.Tooltip(tooltipTriggerEl);
          });

          const table = document.getElementById('matchesTable');
          if (table) {
                    const tbody = table.querySelector('tbody');
                    const headerButtons = Array.from(table.querySelectorAll('[data-sort-key]'));
                    let currentSort = { key: null, dir: 'asc' };

                    function getValue(row, key, type) {
                              const raw = row.getAttribute('data-sort-' + key) || '';
                              if (type === 'number') {
                                        const num = Number(raw);
                                        return Number.isNaN(num) ? 0 : num;
                              }
                              return raw.toString();
                    }

                    function updateIcons(activeKey, dir) {
                              headerButtons.forEach(function (btn) {
                                        const icon = btn.querySelector('.sort-icon');
                                        if (!icon) return;
                                        if (btn.dataset.sortKey === activeKey) {
                                                  icon.className = 'fa-solid ' + (dir === 'asc' ? 'fa-sort-up' : 'fa-sort-down') + ' sort-icon';
                                        } else {
                                                  icon.className = 'fa-solid fa-sort sort-icon';
                                        }
                              });
                    }

                    headerButtons.forEach(function (btn) {
                              btn.addEventListener('click', function () {
                                        const key = this.dataset.sortKey;
                                        const type = this.dataset.sortType || 'string';
                                        const dir = currentSort.key === key && currentSort.dir === 'asc' ? 'desc' : 'asc';
                                        const rows = Array.from(tbody.querySelectorAll('tr'));

                                        rows.sort(function (a, b) {
                                                  const aVal = getValue(a, key, type);
                                                  const bVal = getValue(b, key, type);

                                                  if (type === 'number') {
                                                            return dir === 'asc' ? aVal - bVal : bVal - aVal;
                                                  }

                                                  return dir === 'asc'
                                                            ? aVal.localeCompare(bVal)
                                                            : bVal.localeCompare(aVal);
                                        });

                                        rows.forEach(function (row) { tbody.appendChild(row); });
                                        currentSort = { key: key, dir: dir };
                                        updateIcons(key, dir);
                              });
                    });
          }
});
</script>
HTML;
require __DIR__ . '/../../layout.php';
