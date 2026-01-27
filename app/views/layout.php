<?php
require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/../lib/asset_helper.php';
auth_boot();
?>
<!doctype html>
<html lang="en">

<head>
          <meta charset="utf-8">
          <title><?= htmlspecialchars($title ?? 'Analytics') ?></title>
          <?php $base = base_path(); ?>
          <meta name="base-path" content="<?= htmlspecialchars($base ?: '') ?>">

          <?php // Filemtime-based versions keep URLs stable until the asset changes. ?>
          <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css" rel="stylesheet">
          <link href="<?= htmlspecialchars($base) ?>/assets/css/tailwind.css<?= asset_version('/assets/css/tailwind.css') ?>" rel="stylesheet">
          <link href="<?= htmlspecialchars($base) ?>/assets/css/app.css<?= asset_version('/assets/css/app.css') ?>" rel="stylesheet">
          <!-- Removed missing forms.css to avoid 404 -->
          <?= $headExtras ?? '' ?>
</head>

<?php
$bodyClasses = [];
if (is_logged_in() && empty($hideNav)) {
          $bodyClasses[] = 'has-top-nav';
}
$bodyClassAttr = implode(' ', $bodyClasses);
$bodyExtraAttributes = '';
if (!empty($bodyAttributes)) {
          $bodyExtraAttributes = ' ' . trim($bodyAttributes);
}
$appShellClassAttr = htmlspecialchars($appShellClasses ?? 'app-shell');
$mainClassAttr = htmlspecialchars($mainClasses ?? 'app-main flex-fill p-4 bg-surface');
?>
<body class="<?= htmlspecialchars($bodyClassAttr) ?>"<?= $bodyExtraAttributes ?>>

          <?php if (is_logged_in() && empty($hideNav)): ?>
                    <?php require __DIR__ . '/partials/nav.php'; ?>
          <?php endif; ?>

          <div class="<?= $appShellClassAttr ?>">
                    <main class="<?= $mainClassAttr ?>">
                              <?= $content ?>
                    </main>
          </div>

          <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
          <script src="<?= htmlspecialchars($base) ?>/assets/js/components.js<?= asset_version('/assets/js/components.js') ?>"></script>
          <script src="<?= htmlspecialchars($base) ?>/assets/js/app.js<?= asset_version('/assets/js/app.js') ?>"></script>
          
          <?= $footerScripts ?? '' ?>
</body>

</html>
