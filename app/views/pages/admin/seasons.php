<?php
require_once __DIR__ . '/../../../lib/auth.php';
require_once __DIR__ . '/../../../lib/season_repository.php';
require_once __DIR__ . '/../../../lib/club_repository.php';

$context = require_club_admin_access();
$user = $context['user'];
$clubId = $context['club_id'];
$roles = $context['roles'];
$isPlatformAdmin = in_array('platform_admin', $roles, true);
$clubs = $isPlatformAdmin ? get_all_clubs() : [];
$seasons = get_seasons_by_club($clubId);

$title = 'Seasons';
$base = base_path();

ob_start();
?>
<?php
    $pageTitle = 'Seasons';
    $pageDescription = 'Create and manage seasons for this club.';
    include __DIR__ . '/../../partials/club_context_header.php';
?>

<div class="panel panel-dark p-4 mb-4">
          <h5 class="text-light mb-3">Create season</h5>
          <form id="season-create-form" class="row g-3">
                    <?php if ($isPlatformAdmin): ?>
                              <div class="col-md-4">
                                        <label class="form-label">Club</label>
                                        <select name="club_id" class="form-select select-dark">
                                                  <?php foreach ($clubs as $club): ?>
                                                            <option value="<?= (int)$club['id'] ?>" <?= (int)$club['id'] === $clubId ? 'selected' : '' ?>><?= htmlspecialchars($club['name']) ?></option>
                                                  <?php endforeach; ?>
                                        </select>
                              </div>
                    <?php endif; ?>
                    <div class="col-md-4">
                              <label class="form-label">Name</label>
                              <input type="text" name="name" class="form-control input-dark" required>
                    </div>
                    <div class="col-md-2">
                              <label class="form-label">Start date</label>
                              <input type="date" name="start_date" class="form-control input-dark">
                    </div>
                    <div class="col-md-2">
                              <label class="form-label">End date</label>
                              <input type="date" name="end_date" class="form-control input-dark">
                    </div>
                    <div class="col-12 text-end">
                            <button type="submit" class="inline-flex items-center gap-2 bg-accent-primary text-white px-4 py-2 rounded-md hover:bg-accent-primary/80 transition">Create season</button>
                    </div>
          </form>
          <div id="season-create-error" class="text-danger small mt-2" style="display:none;"></div>
</div>

<div class="panel panel-dark p-4">
          <div class="d-flex align-items-center justify-content-between mb-3">
                    <h5 class="text-light mb-0">Existing seasons</h5>
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
          <div class="table-responsive">
                    <table class="table table-sm table-dark align-middle mb-0">
                              <thead>
                                        <tr>
                                                  <th>Name</th>
                                                  <th>Start</th>
                                                  <th>End</th>
                                                  <th class="text-end">Actions</th>
                                        </tr>
                              </thead>
                              <tbody id="seasons-table-body">
                                        <?php if (empty($seasons)): ?>
                                                  <tr><td colspan="4" class="text-muted text-center">No seasons yet.</td></tr>
                                        <?php else: ?>
                                                  <?php foreach ($seasons as $season): ?>
                                                            <tr data-season-id="<?= (int)$season['id'] ?>">
                                                                      <td><input type="text" class="form-control form-control-sm input-dark" name="name" value="<?= htmlspecialchars($season['name']) ?>"></td>
                                                                      <td><input type="date" class="form-control form-control-sm input-dark" name="start_date" value="<?= htmlspecialchars($season['start_date'] ?? '') ?>"></td>
                                                                      <td><input type="date" class="form-control form-control-sm input-dark" name="end_date" value="<?= htmlspecialchars($season['end_date'] ?? '') ?>"></td>
                                                                      <td class="text-end">
                                                                                <button type="button" class="inline-flex items-center gap-2 bg-bg-secondary text-text-primary border border-border-soft px-4 py-2 rounded-md hover:bg-bg-secondary/80 transition" data-action="save-season">Save</button>
                                                                                <button type="button" class="inline-flex items-center gap-2 bg-accent-danger text-white px-4 py-2 rounded-md hover:bg-accent-danger/80 transition" data-action="delete-season">Delete</button>
                                                                      </td>
                                                            </tr>
                                                  <?php endforeach; ?>
                                        <?php endif; ?>
                              </tbody>
                    </table>
          </div>
          <div id="season-action-error" class="text-danger small mt-2" style="display:none;"></div>
</div>

<script>
(function() {
          const base = <?= json_encode($base) ?>;
          const clubId = <?= (int)$clubId ?>;
          const isPlatformAdmin = <?= $isPlatformAdmin ? 'true' : 'false' ?>;

          const createForm = document.getElementById('season-create-form');
          const createError = document.getElementById('season-create-error');
          const actionError = document.getElementById('season-action-error');

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

                    fetch(`${base}/api/seasons/create`, {
                              method: 'POST',
                              body: formData,
                    })
                              .then(resp => resp.json())
                              .then(payload => {
                                        if (payload.ok) {
                                                  window.location.reload();
                                                  return;
                                        }
                                        showError(createError, payload.error || 'Unable to create season');
                              })
                              .catch(() => showError(createError, 'Unable to create season'));
          }

          function handleTableClick(evt) {
                    const btn = evt.target.closest('button[data-action]');
                    if (!btn) return;
                    const row = btn.closest('tr[data-season-id]');
                    if (!row) return;
                    const seasonId = row.getAttribute('data-season-id');
                    const name = row.querySelector('input[name="name"]').value.trim();
                    const startDate = row.querySelector('input[name="start_date"]').value || '';
                    const endDate = row.querySelector('input[name="end_date"]').value || '';
                    const action = btn.getAttribute('data-action');

                    if (action === 'save-season') {
                              showError(actionError, '');
                              const formData = new FormData();
                              formData.set('id', seasonId);
                              formData.set('club_id', isPlatformAdmin ? (document.querySelector('select[name="club_id"]')?.value || clubId) : clubId);
                              formData.set('name', name);
                              formData.set('start_date', startDate);
                              formData.set('end_date', endDate);

                              fetch(`${base}/api/seasons/update`, { method: 'POST', body: formData })
                                        .then(resp => resp.json())
                                        .then(payload => {
                                                  if (payload.ok) return;
                                                  showError(actionError, payload.error || 'Unable to update season');
                                        })
                                        .catch(() => showError(actionError, 'Unable to update season'));
                    }

                    if (action === 'delete-season') {
                              if (!confirm('Delete this season? Matches or competitions attached will block deletion.')) return;
                              showError(actionError, '');
                              const formData = new FormData();
                              formData.set('id', seasonId);
                              formData.set('club_id', isPlatformAdmin ? (document.querySelector('select[name="club_id"]')?.value || clubId) : clubId);

                              fetch(`${base}/api/seasons/delete`, { method: 'POST', body: formData })
                                        .then(resp => resp.json())
                                        .then(payload => {
                                                  if (payload.ok) {
                                                            row.remove();
                                                            return;
                                                  }
                                                  showError(actionError, payload.error || 'Unable to delete season');
                                        })
                                        .catch(() => showError(actionError, 'Unable to delete season'));
                    }
          }

          createForm?.addEventListener('submit', handleCreate);
          document.getElementById('seasons-table-body')?.addEventListener('click', handleTableClick);
})();
</script>

<?php
$content = ob_get_clean();
require __DIR__ . '/../../layout.php';
