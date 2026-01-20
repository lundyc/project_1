<?php
$title = 'Login';

$error = $_SESSION['login_error'] ?? null;
unset($_SESSION['login_error']);

ob_start();
?>
<div class="min-h-screen flex flex-col md:flex-row bg-slate-950 text-slate-100">
          <div class="md:w-1/2 relative overflow-hidden flex items-center justify-center p-10 lg:p-14">
                    <div class="absolute inset-0 bg-gradient-to-br from-sky-600/60 via-indigo-700/60 to-slate-900/80"></div>
                    <div class="absolute inset-0 opacity-30" style="background: radial-gradient(circle at 20% 20%, rgba(255,255,255,0.15), transparent 35%), radial-gradient(circle at 80% 0%, rgba(255,255,255,0.12), transparent 30%), radial-gradient(circle at 50% 80%, rgba(255,255,255,0.08), transparent 35%);"></div>
                    <div class="relative max-w-xl">
                              <div class="flex items-center gap-3 mb-6">
                                        <img src="<?= htmlspecialchars(base_path()) ?>/assets/img/logo.png" alt="Lundy logo" class="h-12 w-12 rounded-xl shadow-lg ring-2 ring-white/20">
                                        <div>
                                                  <p class="text-xs uppercase tracking-[0.28em] text-white/70">Analytics Desk</p>
                                                  <h1 class="text-3xl md:text-4xl font-extrabold text-white leading-tight">Match intelligence at a glance.</h1>
                                        </div>
                              </div>
                              <p class="text-white/80 text-lg leading-relaxed mb-6">Review clips, surface key events, and keep your analysts and coaches aligned with secure role-based access.</p>
                              <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-sm text-white/80">
                                        <div class="flex items-center gap-2 bg-white/5 border border-white/10 rounded-lg px-4 py-3 backdrop-blur">
                                                  <span class="h-2 w-2 rounded-full bg-emerald-400 shadow-[0_0_12px_rgba(52,211,153,0.6)]"></span>
                                                  Live match timelines
                                        </div>
                                        <div class="flex items-center gap-2 bg-white/5 border border-white/10 rounded-lg px-4 py-3 backdrop-blur">
                                                  <span class="h-2 w-2 rounded-full bg-sky-400 shadow-[0_0_12px_rgba(56,189,248,0.6)]"></span>
                                                  Smart clip queues
                                        </div>
                                        <div class="flex items-center gap-2 bg-white/5 border border-white/10 rounded-lg px-4 py-3 backdrop-blur">
                                                  <span class="h-2 w-2 rounded-full bg-amber-300 shadow-[0_0_12px_rgba(252,211,77,0.6)]"></span>
                                                  Player performance
                                        </div>
                                        <div class="flex items-center gap-2 bg-white/5 border border-white/10 rounded-lg px-4 py-3 backdrop-blur">
                                                  <span class="h-2 w-2 rounded-full bg-indigo-300 shadow-[0_0_12px_rgba(165,180,252,0.6)]"></span>
                                                  Secure roles & audit
                                        </div>
                              </div>
                    </div>
          </div>

          <div class="md:w-1/2 flex items-center justify-center p-8 md:p-12 lg:p-16 bg-slate-900">
                    <div class="w-full max-w-md bg-slate-900/80 border border-white/5 rounded-2xl shadow-2xl shadow-black/40 backdrop-blur-lg p-8">
                              <div class="mb-6">
                                        <p class="text-sm uppercase tracking-[0.24em] text-white/60 mb-2">Welcome back</p>
                                        <h2 class="text-2xl font-bold text-white">Sign in</h2>
                                        <p class="text-sm text-white/60 mt-1">Access your matches, events, and clip workflows.</p>
                              </div>

                              <?php if ($error): ?>
                                        <div class="mb-4 rounded-lg border border-red-500/40 bg-red-500/10 text-red-100 px-4 py-3 text-sm shadow-lg shadow-red-900/30">
                                                  <?= htmlspecialchars($error) ?>
                                        </div>
                              <?php endif; ?>

                              <form method="post" action="<?= base_path() ?>/api/login" class="space-y-4">
                                        <div>
                                                  <label class="block text-sm font-medium text-white/80 mb-1" for="login-email">Email</label>
                                                  <input id="login-email" name="email" type="email" class="w-full rounded-lg border border-white/10 bg-white/5 px-3 py-2 text-white placeholder-white/40 focus:outline-none focus:ring-2 focus:ring-sky-400 focus:border-sky-400 transition" required autofocus>
                                        </div>

                                        <div>
                                                  <label class="block text-sm font-medium text-white/80 mb-1" for="login-password">Password</label>
                                                  <input id="login-password" name="password" type="password" class="w-full rounded-lg border border-white/10 bg-white/5 px-3 py-2 text-white placeholder-white/40 focus:outline-none focus:ring-2 focus:ring-sky-400 focus:border-sky-400 transition" required>
                                        </div>

                                        <button class="w-full mt-2 inline-flex items-center justify-center gap-2 rounded-lg bg-gradient-to-r from-sky-500 via-indigo-500 to-blue-600 px-4 py-3 text-sm font-semibold uppercase tracking-wide text-white shadow-lg shadow-sky-900/40 hover:shadow-xl hover:shadow-sky-900/50 transition">
                                                  <span>Sign in</span>
                                                  <i class="fa-solid fa-arrow-right"></i>
                                        </button>
                              </form>

                              <p class="mt-6 text-xs text-white/50">Protected access. If you need help, contact your club administrator.</p>
                    </div>
          </div>
</div>
<?php
$content = ob_get_clean();
require __DIR__ . '/../layout.php';
