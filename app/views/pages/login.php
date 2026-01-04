<?php
$title = 'Login';

$error = $_SESSION['login_error'] ?? null;
unset($_SESSION['login_error']);

ob_start();
?>
<div class="login-page">
          <div class="login-columns">
                    <div class="login-card shadow-sm">
                              <div class="login-card-brand d-flex align-items-center gap-3 mb-4">
                                        <img src="<?= htmlspecialchars(base_path()) ?>/assets/img/logo.png" alt="Lundy logo" width="48" height="48">
                                        <div>
                                                  <h1 class="h4 mb-1">Welcome back</h1>
                                                  <p class="text-muted-alt mb-0">Sign in to continue reviewing match insights, clips, and player dashboards.</p>
                                        </div>
                              </div>

                              <?php if ($error): ?>
                                        <div class="alert alert-danger shadow-sm"><?= htmlspecialchars($error) ?></div>
                              <?php endif; ?>

                              <form method="post" action="<?= base_path() ?>/api/login" class="login-form mt-4">
                                        <div class="mb-3">
                                                  <label class="form-label" for="login-email">Email address</label>
                                                  <input id="login-email" name="email" type="email" class="form-control login-input" required autofocus>
                                        </div>

                                        <div class="mb-3">
                                                  <label class="form-label" for="login-password">Password</label>
                                                  <input id="login-password" name="password" type="password" class="form-control login-input" required>
                                        </div>

                                        <button class="btn btn-primary w-100 btn-lg text-uppercase fw-semibold">Sign in</button>
                              </form>
                    </div>

                    <div class="login-panel">
                              <h2 class="h4 mb-3">Every match, in sharper focus</h2>
                              <p class="text-muted-alt mb-4">Access curated clips, detailed match periods, and action timelines crafted for coaches, analysts, and video editors.</p>
                              <ul class="login-feature-list list-unstyled mb-0">
                                        <li><i class="fa-solid fa-play-circle"></i> Smart clip recommendations</li>
                                        <li><i class="fa-solid fa-chart-line"></i> Performance dashboards</li>
                                        <li><i class="fa-solid fa-shield-halved"></i> Secure platform roles</li>
                              </ul>
                    </div>
          </div>
</div>
<?php
$content = ob_get_clean();
require __DIR__ . '/../layout.php';
