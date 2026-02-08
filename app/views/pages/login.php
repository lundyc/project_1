<?php
$title = 'Login';

$error = $_SESSION['login_error'] ?? null;
unset($_SESSION['login_error']);

ob_start();
?>
<div class="min-h-screen bg-slate-900 text-slate-100">
          <div class="relative overflow-hidden">
                    <div class="absolute inset-0">
                              <div class="absolute -top-24 -left-20 h-72 w-72 rounded-full bg-sky-500/15 blur-3xl"></div>
                              <div class="absolute top-20 right-0 h-64 w-64 rounded-full bg-indigo-500/20 blur-3xl"></div>
                              <div class="absolute bottom-0 left-1/2 h-80 w-80 -translate-x-1/2 rounded-full bg-blue-600/10 blur-3xl"></div>
                              <div class="absolute inset-0 bg-gradient-to-br from-slate-950 via-slate-900 to-slate-950"></div>
                              <div class="absolute inset-0 opacity-20" style="background-image: radial-gradient(circle at 20% 20%, rgba(56,189,248,0.35), transparent 35%), radial-gradient(circle at 80% 0%, rgba(99,102,241,0.3), transparent 30%), radial-gradient(circle at 50% 80%, rgba(59,130,246,0.25), transparent 40%);"></div>
                    </div>

                    <div class="relative mx-auto max-w-6xl px-6 py-10 lg:py-16">
                              <div class="flex items-center justify-between gap-4">
                                        <div class="flex items-center gap-3">
                                                  <img src="<?= htmlspecialchars(base_path()) ?>/assets/img/logo.png" alt="Lundy logo" class="h-11 w-11 rounded-xl shadow-lg ring-2 ring-blue-400/20">
                                                  <div>
                                                            <p class="text-xs uppercase tracking-[0.32em] text-slate-400">Analytics Desk</p>
                                                            <p class="text-sm text-slate-300">Secure club access</p>
                                                  </div>
                                        </div>
                                        <div class="hidden sm:flex items-center gap-3 text-xs text-slate-400">
                                                  <span class="h-2 w-2 rounded-full bg-emerald-400 shadow-[0_0_12px_rgba(52,211,153,0.6)]"></span>
                                                  Live collaboration enabled
                                        </div>
                              </div>

                              <div class="mt-10 grid gap-10 lg:grid-cols-[1fr_1fr] items-center">
                                        <div class="space-y-6">
                                                  <div>
                                                            <h1 class="text-3xl sm:text-4xl font-semibold text-white leading-tight">Welcome to the match intelligence hub.</h1>
                                                            <p class="mt-3 text-base text-slate-300 max-w-xl">Capture moments, align analysis, and deliver actionable insights with a workspace tailored for elite performance teams.</p>
                                                  </div>
                                                  <div class="grid gap-4 sm:grid-cols-2">
                                                            <div class="rounded-2xl border border-slate-800/80 bg-slate-900/70 px-4 py-4">
                                                                      <div class="text-sm font-semibold text-slate-100">Live timelines</div>
                                                                      <p class="text-xs text-slate-400 mt-1">Organise key events instantly.</p>
                                                            </div>
                                                            <div class="rounded-2xl border border-slate-800/80 bg-slate-900/70 px-4 py-4">
                                                                      <div class="text-sm font-semibold text-slate-100">Smart clips</div>
                                                                      <p class="text-xs text-slate-400 mt-1">Auto-generated highlight reels.</p>
                                                            </div>
                                                            <div class="rounded-2xl border border-slate-800/80 bg-slate-900/70 px-4 py-4">
                                                                      <div class="text-sm font-semibold text-slate-100">Unified stats</div>
                                                                      <p class="text-xs text-slate-400 mt-1">Performance insights in one view.</p>
                                                            </div>
                                                            <div class="rounded-2xl border border-slate-800/80 bg-slate-900/70 px-4 py-4">
                                                                      <div class="text-sm font-semibold text-slate-100">Secure access</div>
                                                                      <p class="text-xs text-slate-400 mt-1">Role-based control & auditing.</p>
                                                            </div>
                                                  </div>
                                        </div>

                                        <div class="w-full">
                                                  <div class="mx-auto w-full max-w-md rounded-3xl border border-slate-800/80 bg-slate-900/80 p-6 sm:p-8 shadow-2xl shadow-black/40 backdrop-blur">
                                                            <div class="mb-6">
                                                                      <p class="text-xs uppercase tracking-[0.28em] text-slate-400">Welcome back</p>
                                                                      <h2 class="text-2xl font-semibold text-white">Sign in</h2>
                                                                      <p class="text-sm text-slate-400 mt-2">Continue to your club workspace.</p>
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

                                                                      <button type="submit" class="w-full mt-2 inline-flex items-center justify-center gap-2 rounded-2xl bg-gradient-to-r from-sky-500 via-indigo-500 to-blue-600 px-4 py-3 text-sm font-semibold uppercase tracking-wide text-white shadow-lg shadow-blue-500/20 hover:shadow-blue-500/40 transition">
                                                                                <span>Sign in</span>
                                                                                <i class="fa-solid fa-arrow-right"></i>
                                                                      </button>
                                                            </form>

                                                            <p class="mt-6 text-xs text-slate-500">Protected access. If you need help, contact your club administrator.</p>
                                                  </div>
                                        </div>
                              </div>
                    </div>
          </div>
</div>
<?php
$content = ob_get_clean();
require __DIR__ . '/../layout.php';
