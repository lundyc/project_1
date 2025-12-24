<?php
$title = 'Login';

$error = $_SESSION['login_error'] ?? null;
unset($_SESSION['login_error']);

ob_start();
?>
<div class="row justify-content-center">
          <div class="col-md-4">
                    <h1 class="mb-3">Login</h1>

                    <?php if ($error): ?>
                              <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                    <?php endif; ?>

                    <form method="post" action="<?= base_path() ?>/api/login">
                              <div class="mb-3">
                                        <label class="form-label">Email</label>
                                        <input name="email" type="email" class="form-control" required>
                              </div>

                              <div class="mb-3">
                                        <label class="form-label">Password</label>
                                        <input name="password" type="password" class="form-control" required>
                              </div>

                              <button class="btn btn-primary w-100">Sign in</button>
                    </form>
          </div>
</div>
<?php
$content = ob_get_clean();
require __DIR__ . '/../layout.php';
