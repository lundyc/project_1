<?php
require_once __DIR__ . '/../lib/auth.php';
auth_boot();
?>
<!doctype html>
<html lang="en">

<head>
          <meta charset="utf-8">
          <title><?= htmlspecialchars($title ?? 'Analytics') ?></title>
          <?php $base = base_path(); ?>
          <?php $cacheBuster = time(); ?>
          <meta name="base-path" content="<?= htmlspecialchars($base ?: '') ?>">

          <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css?v=<?= $cacheBuster ?>" rel="stylesheet">
          <link href="<?= htmlspecialchars($base) ?>/assets/css/tailwind.css?v=<?= $cacheBuster ?>" rel="stylesheet">
          <link href="<?= htmlspecialchars($base) ?>/assets/css/app.css?v=<?= $cacheBuster ?>" rel="stylesheet">
          <link href="<?= htmlspecialchars($base) ?>/assets/css/forms.css?v=<?= $cacheBuster ?>" rel="stylesheet">
          <?= $headExtras ?? '' ?>
</head>

<?php
$bodyClasses = ['bg-dark', 'text-light'];
if (is_logged_in()) {
          $bodyClasses[] = 'has-top-nav';
}
$bodyClassAttr = implode(' ', $bodyClasses);
$bodyExtraAttributes = '';
if (!empty($bodyAttributes)) {
          $bodyExtraAttributes = ' ' . trim($bodyAttributes);
}
?>
<body class="<?= htmlspecialchars($bodyClassAttr) ?>"<?= $bodyExtraAttributes ?>>

          <?php if (is_logged_in()): ?>
                    <?php require __DIR__ . '/partials/nav.php'; ?>
          <?php endif; ?>

          <div class="app-shell">
                    <main class="app-main flex-fill p-4 bg-surface">
                              <?= $content ?>
                    </main>
          </div>

          <script src="https://code.jquery.com/jquery-3.7.1.min.js?v=<?= $cacheBuster ?>"></script>
          <script src="<?= htmlspecialchars($base) ?>/assets/js/app.js?v=<?= $cacheBuster ?>"></script>
          
          <?= $footerScripts ?? '' ?>
</body>

</html>
