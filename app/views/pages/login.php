<?php
$title = 'Login';

$error = $_SESSION['login_error'] ?? null;
unset($_SESSION['login_error']);

ob_start();
?>
<div class="min-h-screen flex flex-col md:flex-row bg-white text-gray-900">
          <div class="md:w-1/2 relative overflow-hidden flex items-center justify-center p-10 lg:p-14">
                    <div class="absolute inset-0 bg-gradient-to-br from-sky-100/60 via-indigo-100/60 to-blue-100/80"></div>
                    <div class="absolute inset-0 opacity-30" style="background: radial-gradient(circle at 20% 20%, rgba(30,64,175,0.08), transparent 35%), radial-gradient(circle at 80% 0%, rgba(99,102,241,0.07), transparent 30%), radial-gradient(circle at 50% 80%, rgba(56,189,248,0.06), transparent 35%);"></div>
                    <div class="relative max-w-xl">
                              <div class="flex items-center gap-3 mb-6">
                                        <img src="<?= htmlspecialchars(base_path()) ?>/assets/img/logo.png" alt="Lundy logo" class="h-12 w-12 rounded-xl shadow-lg ring-2 ring-blue-200/20">
                                        <div>
                                                  <p class="text-xs uppercase tracking-[0.28em] text-gray-500">Analytics Desk</p>
                                                  <h1 class="text-3xl md:text-4xl font-extrabold text-blue-900 leading-tight">Match intelligence at a glance.</h1>
                                        </div>
                              </div>
                              <p class="text-gray-700 text-lg leading-relaxed mb-6">Review clips, surface key events, and keep your analysts and coaches aligned with secure role-based access.</p>
                              <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-sm text-gray-700">
                                        <div class="flex items-center gap-2 bg-blue-50 border border-blue-100 rounded-lg px-4 py-3 backdrop-blur">
                                                  <span class="h-2 w-2 rounded-full bg-emerald-400 shadow-[0_0_12px_rgba(52,211,153,0.6)]"></span>
                                                  Live match timelines
                                        </div>
                                        <div class="flex items-center gap-2 bg-blue-50 border border-blue-100 rounded-lg px-4 py-3 backdrop-blur">
                                                  <span class="h-2 w-2 rounded-full bg-sky-400 shadow-[0_0_12px_rgba(56,189,248,0.6)]"></span>
                                                  Smart clip queues
                                        </div>
                                        <div class="flex items-center gap-2 bg-blue-50 border border-blue-100 rounded-lg px-4 py-3 backdrop-blur">
                                                  <span class="h-2 w-2 rounded-full bg-amber-300 shadow-[0_0_12px_rgba(252,211,77,0.6)]"></span>
                                                  Player performance
                                        </div>
                                        <div class="flex items-center gap-2 bg-blue-50 border border-blue-100 rounded-lg px-4 py-3 backdrop-blur">
                                                  <span class="h-2 w-2 rounded-full bg-indigo-300 shadow-[0_0_12px_rgba(165,180,252,0.6)]"></span>
                                                  Secure roles & audit
                                        </div>
                              </div>
                    </div>
          </div>

          <div class="md:w-1/2 flex items-center justify-center p-8 md:p-12 lg:p-16 bg-blue-50">
                    <div class="w-full max-w-md bg-white border border-blue-100 rounded-2xl shadow-2xl shadow-blue-200/40 backdrop-blur-lg p-8">
                              <div class="mb-6">
                                        <p class="text-sm uppercase tracking-[0.24em] text-blue-700 mb-2">Welcome back</p>
                                        <h2 class="text-2xl font-bold text-blue-900">Sign in</h2>
                                        <p class="text-sm text-blue-700 mt-1">Access your matches, events, and clip workflows.</p>
                              </div>

                              <?php if ($error): ?>
                                        <div class="mb-4 rounded-lg border border-red-500/40 bg-red-500/10 text-red-700 px-4 py-3 text-sm shadow-lg shadow-red-200/30">
                                                  <?= htmlspecialchars($error) ?>
                                        </div>
                              <?php endif; ?>

                              <form method="post" action="<?= base_path() ?>/api/login" class="space-y-4">
                                        <input type="hidden" name="csrf_token" value="<?= get_csrf_token() ?>">
                                        
                                        <div>
                                                  <label class="block text-sm font-medium text-blue-900 mb-1" for="login-email">Email</label>
                                                  <input id="login-email" name="email" type="email" class="w-full rounded-lg border border-blue-100 bg-blue-50 px-3 py-2 text-blue-900 placeholder-blue-400 focus:outline-none focus:ring-2 focus:ring-sky-400 focus:border-sky-400 transition" required autofocus>
                                        </div>

                                        <div>
                                                  <label class="block text-sm font-medium text-blue-900 mb-1" for="login-password">Password</label>
                                                  <input id="login-password" name="password" type="password" class="w-full rounded-lg border border-blue-100 bg-blue-50 px-3 py-2 text-blue-900 placeholder-blue-400 focus:outline-none focus:ring-2 focus:ring-sky-400 focus:border-sky-400 transition" required>
                                        </div>

                                        <button type="submit" class="w-full mt-2 inline-flex items-center justify-center gap-2 rounded-lg bg-gradient-to-r from-sky-500 via-indigo-500 to-blue-600 px-4 py-3 text-sm font-semibold uppercase tracking-wide text-white shadow-lg shadow-blue-200/40 hover:shadow-xl hover:shadow-blue-300/50 transition">
                                                  <span>Sign in</span>
                                                  <i class="fa-solid fa-arrow-right"></i>
                                        </button>
                              </form>

                              <p class="mt-6 text-xs text-blue-400">Protected access. If you need help, contact your club administrator.</p>
                    </div>
          </div>
</div>
<?php
$content = ob_get_clean();
require __DIR__ . '/../layout.php';
