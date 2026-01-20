<?php
require_auth();

$title = 'Dashboard';

ob_start();
?>
<h1>Dashboard</h1>

<p class="text-muted-alt text-sm mb-0">
          Logged in as <strong><?= htmlspecialchars(current_user()['display_name']) ?></strong>
</p>

<a href="<?= base_path() ?>/logout" class="inline-flex items-center rounded-lg px-4 py-2 text-sm border border-gray-300 bg-transparent text-white hover:bg-gray-700 transition">Logout</a>
<?php
$content = ob_get_clean();
require __DIR__ . '/../layout.php';
