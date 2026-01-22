<?php
require_once __DIR__ . '/../../../lib/auth.php';
require_once __DIR__ . '/../../../lib/competition_repository.php';
require_once __DIR__ . '/../../../lib/season_repository.php';
require_once __DIR__ . '/../../../lib/team_repository.php';
require_once __DIR__ . '/../../../lib/club_repository.php';

$context = require_club_admin_access();
$user = $context['user'];
$clubId = $context['club_id'];
$roles = $context['roles'];
$isPlatformAdmin = in_array('platform_admin', $roles, true);
$clubs = $isPlatformAdmin ? get_all_clubs() : [];

$seasons = get_seasons_by_club($clubId);
$competitions = get_competitions_by_club($clubId);
$teams = get_teams_by_club($clubId);
$competitionTeams = [];
foreach ($competitions as $competition) {
          $competitionTeams[(int)$competition['id']] = list_competition_teams((int)$competition['id']);
}

$title = 'Leagues & Competitions';
$base = base_path();

ob_start();
?>
<?php
    $pageTitle = 'Leagues & Cups';
    $pageDescription = 'Manage competitions, types, seasons, and participating teams.';
    include __DIR__ . '/../../partials/club_context_header.php';
?>

<div class="panel panel-dark p-4 mb-4">
          <h5 class="text-light mb-3">Create competition</h5>
          <form id="competition-create-form" class="row g-3">
                    <?php if ($isPlatformAdmin): ?>
                              <div class="col-md-3">
                                        <label class="form-label">Club</label>
                                        <select name="club_id" class="form-select select-dark">
                                                  <?php foreach ($clubs as $club): ?>
                                                            <option value="<?= (int)$club['id'] ?>" <?= (int)$club['id'] === $clubId ? 'selected' : '' ?>><?= htmlspecialchars($club['name']) ?></option>
                                                  <?php endforeach; ?>
                                        </select>
                              </div>
                    <?php endif; ?>
                    <div class="col-md-3">
                              <label class="form-label">Name</label>
                              <input type="text" name="name" class="form-control input-dark" required>
                    </div>
                    <div class="col-md-2">
                              <label class="form-label">Type</label>
                              <select name="type" class="form-select select-dark">
                                        <option value="league">League</option>
                                        <option value="cup" selected>Cup</option>
                              </select>
                    </div>
                    <div class="col-md-2">
                              <label class="form-label">Season</label>
                              <select name="season_id" class="form-select select-dark" required>
                                        <?php foreach ($seasons as $season): ?>
                                                  <option value="<?= (int)$season['id'] ?>"><?= htmlspecialchars($season['name']) ?></option>
                                        <?php endforeach; ?>
                              </select>
                    </div>
                    <div class="col-12 text-end">
                              <button type="submit" class="btn btn-primary-soft btn-sm">Create competition</button>
                    </div>
          </form>
          <div id="competition-create-error" class="text-danger small mt-2" style="display:none;"></div>
</div>

<div class="panel panel-dark p-4">
          <div class="d-flex align-items-center justify-content-between mb-3">
                    <h5 class="text-light mb-0">Competitions</h5>
                    <?php if ($isPlatformAdmin): ?>
                              <form method="get" class="d-flex align-items-center gap-2">
                                        <label class="form-label text-muted-alt text-xs mb-0">Club</label>
                                        <select name="club_id" class="form-select select-dark" onchange="this.form.submit()">
                                                  <?php foreach ($clubs as $club): ?>
                                                            <option value="<?= (int)$club['id'] ?>" <?= (int)$club['id'] === $clubId ? 'selected' : '' ?>><?= htmlspecialchars($club['name']) ?></option>
                                                  <?php endforeach; ?>
                                        </select>
                              </form>
                    <?php endif; ?>
          </div>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4" id="competition-grid">
                    <?php if (empty($competitions)): ?>
                              <div class="text-muted">No competitions yet.</div>
                    <?php else: ?>
                              <?php foreach ($competitions as $competition): 
                                        $compId = (int)$competition['id'];
                                        $assigned = $competitionTeams[$compId] ?? [];
                              ?>
                                        <div class="panel panel-secondary p-3" data-competition-id="<?= $compId ?>">
                                                  <div class="d-flex align-items-center justify-content-between mb-2">
                                                            <h6 class="mb-0 text-light">Competition #<?= $compId ?></h6>
                                                            <span class="badge bg-dark text-uppercase text-xs"><?= htmlspecialchars($competition['type']) ?></span>
                                                  </div>
                                                  <div class="row g-2 mb-2">
                                                            <div class="col-12">
                                                                      <label class="form-label text-xs text-muted">Name</label>
                                                                      <input type="text" name="name" class="form-control form-control-sm input-dark" value="<?= htmlspecialchars($competition['name']) ?>">
                                                            </div>
                                                            <div class="col-6">
                                                                      <label class="form-label text-xs text-muted">Type</label>
                                                                      <select name="type" class="form-select form-select-sm select-dark">
                                                                                <option value="league" <?= $competition['type'] === 'league' ? 'selected' : '' ?>>League</option>
                                                                                <option value="cup" <?= $competition['type'] !== 'league' ? 'selected' : '' ?>>Cup</option>
                                                                      </select>
                                                            </div>
                                                            <div class="col-6">
                                                                      <label class="form-label text-xs text-muted">Season</label>
                                                                      <select name="season_id" class="form-select form-select-sm select-dark">
                                                                                <?php foreach ($seasons as $season): ?>
                                                                                          <option value="<?= (int)$season['id'] ?>" <?= (int)$season['id'] === (int)$competition['season_id'] ? 'selected' : '' ?>><?= htmlspecialchars($season['name']) ?></option>
                                                                                <?php endforeach; ?>
                                                                      </select>
                                                            </div>
                                                  </div>
                                                  <div class="d-flex gap-2 mb-3">
                                                            <button type="button" class="btn btn-secondary-soft btn-sm" data-action="save-competition">Save</button>
                                                            <button type="button" class="btn btn-danger-soft btn-sm" data-action="delete-competition">Delete</button>
                                                  </div>
                                                  <div class="border-top pt-2">
                                                            <div class="d-flex align-items-center justify-content-between mb-2">
                                                                      <strong class="text-light text-sm">Teams</strong>
                                                                      <div class="d-flex gap-2">
                                                                                <select class="form-select form-select-sm select-dark" data-role="team-picker">
                                                                                          <?php foreach ($teams as $team): ?>
                                                                                                    <option value="<?= (int)$team['id'] ?>"><?= htmlspecialchars($team['name']) ?></option>
                                                                                          <?php endforeach; ?>
                                                                                </select>
                                                                                <button type="button" class="btn btn-primary-soft btn-sm" data-action="add-team">Add</button>
                                                                      </div>
                                                            </div>
                                                            <ul class="list-group list-group-flush" data-role="team-list">
                                                                      <?php if (empty($assigned)): ?>
                                                                                <li class="list-group-item bg-transparent text-muted text-xs">No teams assigned.</li>
                                                                      <?php else: ?>
                                                                                <?php foreach ($assigned as $row): ?>
                                                                                          <li class="list-group-item bg-transparent d-flex justify-content-between align-items-center" data-team-id="<?= (int)$row['team_id'] ?>">
                                                                                                    <span><?= htmlspecialchars($row['name']) ?></span>
                                                                                                    <button type="button" class="btn btn-danger-soft btn-sm" data-action="remove-team">Remove</button>
                                                                                          </li>
                                                                                <?php endforeach; ?>
                                                                      <?php endif; ?>
                                                            </ul>
                                                  </div>
                                        </div>
                              <?php endforeach; ?>
                    <?php endif; ?>
          </div>
          <div id="competition-action-error" class="text-danger small mt-2" style="display:none;"></div>
</div>

<script>
(function() {
          const base = <?= json_encode($base) ?>;
          const clubId = <?= (int)$clubId ?>;
          const isPlatformAdmin = <?= $isPlatformAdmin ? 'true' : 'false' ?>;

          const createForm = document.getElementById('competition-create-form');
          const createError = document.getElementById('competition-create-error');
          const actionError = document.getElementById('competition-action-error');

          function showError(el, msg) {
                    if (!el) return;
                    el.textContent = msg;
                    el.style.display = msg ? '' : 'none';
          }

          function handleCreate(evt) {
                    evt.preventDefault();
                    showError(createError, '');
                    const formData = new FormData(createForm);
                    if (!isPlatformAdmin) {
                              formData.set('club_id', String(clubId));
                    }
                    fetch(`${base}/api/competitions/create`, { method: 'POST', body: formData })
                              .then(resp => resp.json())
                              .then(payload => {
                                        if (payload.ok) { window.location.reload(); return; }
                                        showError(createError, payload.error || 'Unable to create competition');
                              })
                              .catch(() => showError(createError, 'Unable to create competition'));
          }

          function handleCardClick(evt) {
                    const btn = evt.target.closest('button[data-action]');
                    if (!btn) return;
                    const card = btn.closest('[data-competition-id]');
                    if (!card) return;
                    const compId = card.getAttribute('data-competition-id');
                    const name = card.querySelector('input[name="name"]').value.trim();
                    const type = card.querySelector('select[name="type"]').value;
                    const seasonId = card.querySelector('select[name="season_id"]').value;
                    const picker = card.querySelector('[data-role="team-picker"]');
                    const teamList = card.querySelector('[data-role="team-list"]');
                    const clubSelector = document.querySelector('select[name="club_id"]');
                    const payloadClubId = isPlatformAdmin && clubSelector ? clubSelector.value : clubId;

                    if (btn.getAttribute('data-action') === 'save-competition') {
                              showError(actionError, '');
                              const formData = new FormData();
                              formData.set('id', compId);
                              formData.set('club_id', payloadClubId);
                              formData.set('name', name);
                              formData.set('type', type);
                              formData.set('season_id', seasonId);

                              fetch(`${base}/api/competitions/update`, { method: 'POST', body: formData })
                                        .then(resp => resp.json())
                                        .then(payload => {
                                                  if (payload.ok) return;
                                                  showError(actionError, payload.error || 'Unable to update competition');
                                        })
                                        .catch(() => showError(actionError, 'Unable to update competition'));
                    }

                    if (btn.getAttribute('data-action') === 'delete-competition') {
                              if (!confirm('Delete this competition? Matches will block deletion.')) return;
                              showError(actionError, '');
                              const formData = new FormData();
                              formData.set('id', compId);
                              formData.set('club_id', payloadClubId);

                              fetch(`${base}/api/competitions/delete`, { method: 'POST', body: formData })
                                        .then(resp => resp.json())
                                        .then(payload => {
                                                  if (payload.ok) {
                                                            card.remove();
                                                            return;
                                                  }
                                                  showError(actionError, payload.error || 'Unable to delete competition');
                                        })
                                        .catch(() => showError(actionError, 'Unable to delete competition'));
                    }

                    if (btn.getAttribute('data-action') === 'add-team') {
                              showError(actionError, '');
                              const teamId = picker.value;
                              const formData = new FormData();
                              formData.set('competition_id', compId);
                              formData.set('team_id', teamId);
                              formData.set('club_id', payloadClubId);
                              fetch(`${base}/api/competitions/add-team`, { method: 'POST', body: formData })
                                        .then(resp => resp.json())
                                        .then(payload => {
                                                  if (!payload.ok) {
                                                            showError(actionError, payload.error || 'Unable to add team');
                                                            return;
                                                  }
                                                  const existing = teamList.querySelector(`[data-team-id="${teamId}"]`);
                                                  if (existing) return;
                                                  const li = document.createElement('li');
                                                  li.className = 'list-group-item bg-transparent d-flex justify-content-between align-items-center';
                                                  li.setAttribute('data-team-id', teamId);
                                                  const label = picker.options[picker.selectedIndex].textContent;
                                                  li.innerHTML = `<span>${label}</span><button type="button" class="btn btn-danger-soft btn-sm" data-action="remove-team">Remove</button>`;
                                                  const empty = teamList.querySelector('.text-muted');
                                                  if (empty) empty.remove();
                                                  teamList.appendChild(li);
                                        })
                                        .catch(() => showError(actionError, 'Unable to add team'));
                    }

                    if (btn.getAttribute('data-action') === 'remove-team') {
                              showError(actionError, '');
                              const li = btn.closest('[data-team-id]');
                              const teamId = li?.getAttribute('data-team-id');
                              if (!teamId) return;
                              const formData = new FormData();
                              formData.set('competition_id', compId);
                              formData.set('team_id', teamId);
                              formData.set('club_id', payloadClubId);
                              fetch(`${base}/api/competitions/remove-team`, { method: 'POST', body: formData })
                                        .then(resp => resp.json())
                                        .then(payload => {
                                                  if (!payload.ok) {
                                                            showError(actionError, payload.error || 'Unable to remove team');
                                                            return;
                                                  }
                                                  li.remove();
                                                  if (!teamList.querySelector('[data-team-id]')) {
                                                            const empty = document.createElement('li');
                                                            empty.className = 'list-group-item bg-transparent text-muted text-xs';
                                                            empty.textContent = 'No teams assigned.';
                                                            teamList.appendChild(empty);
                                                  }
                                        })
                                        .catch(() => showError(actionError, 'Unable to remove team'));
                    }
          }

          createForm?.addEventListener('submit', handleCreate);
          document.getElementById('competition-grid')?.addEventListener('click', handleCardClick);
})();
</script>

<?php
$content = ob_get_clean();
require __DIR__ . '/../../layout.php';
