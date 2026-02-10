<?php
$title = 'Login';
$appShellClasses = 'min-h-screen';
$mainClasses = 'p-0 bg-transparent';
$bodyAttributes = 'data-page="login"';

$error = $_SESSION['login_error'] ?? null;
unset($_SESSION['login_error']);

ob_start();
?>
<div class="min-h-screen bg-slate-950 text-slate-100 flex items-center justify-center px-6">
          <div class="w-full max-w-md">
                    <div class="relative overflow-hidden rounded-3xl border border-slate-800/80 bg-slate-900/80 shadow-2xl shadow-black/50 backdrop-blur">
                              <div class="absolute inset-0">
                                        <div class="absolute -top-20 right-0 h-48 w-48 rounded-full bg-sky-500/10 blur-3xl"></div>
                                        <div class="absolute -bottom-24 left-0 h-56 w-56 rounded-full bg-indigo-500/15 blur-3xl"></div>
                              </div>

                              <div class="relative p-8 sm:p-10">
                                        <div class="flex items-center gap-4 text-left mb-8">
                                                  <img src="<?= htmlspecialchars(base_path()) ?>/assets/img/logo.png" alt="Lundy logo" class="h-[3.325rem] w-[3.325rem] shrink-0">
                                                  <div>
                                                            <p class="text-xs uppercase tracking-[0.32em] text-slate-400">Analytics Desk</p>
                                                            <h1 class="mt-2 text-2xl font-semibold text-white">Sign in</h1>
                                                            <p class="mt-1 text-sm text-slate-400">Access your club workspace.</p>
                                                  </div>
                                        </div>

                                        <?php if ($error): ?>
                                                  <div class="mb-4 rounded-2xl border border-rose-500/40 bg-rose-500/10 text-rose-200 px-4 py-3 text-sm">
                                                            <?= htmlspecialchars($error) ?>
                                                  </div>
                                        <?php endif; ?>

                                        <form method="post" action="<?= base_path() ?>/api/login" class="space-y-4">
                                                  <input type="hidden" name="csrf_token" value="<?= get_csrf_token() ?>">
                                                  
                                                  <div>
                                                            <label class="block text-sm font-medium text-slate-200 mb-1" for="login-email">Email</label>
                                                            <input id="login-email" name="email" type="email" class="w-full rounded-2xl border border-slate-700 bg-slate-950/40 px-4 py-3 text-slate-100 placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-sky-500 focus:border-sky-500 transition" required autofocus>
                                                  </div>

                                                  <div>
                                                            <label class="block text-sm font-medium text-slate-200 mb-1" for="login-password">Password</label>
                                                            <input id="login-password" name="password" type="password" class="w-full rounded-2xl border border-slate-700 bg-slate-950/40 px-4 py-3 text-slate-100 placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-sky-500 focus:border-sky-500 transition" required>
                                                  </div>

                                                  <button type="submit" class="login-submit w-full mt-2 inline-flex items-center justify-center gap-2 rounded-2xl bg-gradient-to-r from-sky-500 via-indigo-500 to-blue-600 px-4 py-3 text-sm font-semibold uppercase tracking-wide text-white shadow-lg shadow-blue-500/20 transition duration-200 hover:-translate-y-0.5 hover:shadow-xl hover:shadow-blue-500/40 hover:from-sky-400 hover:via-indigo-400 hover:to-blue-500">
                                                            <span>Sign in</span>
                                                            <i class="fa-solid fa-arrow-right"></i>
                                                  </button>
                                        </form>

                                        <p class="mt-6 text-xs text-slate-500 text-center">Protected access. If you need help, contact your club administrator.</p>
                              </div>
                    </div>
          </div>
</div>
<?php
$content = ob_get_clean();
require __DIR__ . '/../layout.php';
