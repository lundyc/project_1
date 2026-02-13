<?php
require_role('platform_admin');
require_once dirname(__DIR__, 4) . '/lib/club_repository.php';

$base = base_path();

$flashError = $_SESSION['club_form_error'] ?? null;
$formInput = $_SESSION['club_form_input'] ?? [];
unset($_SESSION['club_form_error'], $_SESSION['club_form_input']);

$title = 'Create Club';

ob_start();
?>
<div class="flex items-center justify-between mb-6">
          <div>
                    <h1 class="mb-1">Create Club</h1>
                    <p class="text-muted-alt text-sm mb-0">Add a new club and start managing teams.</p>
          </div>
          <?php
          $label = 'Back to clubs';
          $href = $base . '/admin/clubs';
          $variant = 'secondary';
          $size = 'sm';
          include __DIR__ . '/../../../partials/ui-button.php';
          ?>
</div>

<?php if ($flashError): ?>
          <div class="alert alert-danger"><?= htmlspecialchars($flashError) ?></div>
<?php endif; ?>

<div class="panel p-4">
          <form method="post" action="<?= htmlspecialchars($base) ?>/api/admin/clubs/create">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                              <div>
                                        <label class="form-label">Club name</label>
                                        <input type="text" name="name" value="<?= htmlspecialchars($formInput['name'] ?? '') ?>" class="input-dark w-full" required>
                              </div>
                    </div>

                    <div class="d-flex justify-content-between align-items-center mt-4">
                              <span class="text-muted-alt text-sm">You can add teams after the club is created.</span>
                              <?php
                              $label = 'Create club';
                              $href = null;
                              $variant = 'primary';
                              $size = 'sm';
                              $type = 'submit';
                              include __DIR__ . '/../../../partials/ui-button.php';
                              ?>
                    </div>
          </form>
</div>
<?php
$content = ob_get_clean();
require __DIR__ . '/../../../layout.php';
