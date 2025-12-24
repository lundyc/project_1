<!doctype html>
<html lang="en">

<head>
          <meta charset="utf-8">
          <title><?= htmlspecialchars($title ?? 'Analytics') ?></title>
          <?php $base = base_path(); ?>
          <?php $cacheBuster = time(); ?>

          <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css?v=<?= $cacheBuster ?>" rel="stylesheet">
          <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css?v=<?= $cacheBuster ?>" rel="stylesheet">
          <link href="<?= htmlspecialchars($base) ?>/assets/css/app.css?v=<?= $cacheBuster ?>" rel="stylesheet">
          <?= $headExtras ?? '' ?>
</head>

<body class="bg-dark text-light">

          <div class="d-flex w-100">
                    <?php require __DIR__ . '/partials/nav.php'; ?>

                    <main class="flex-fill p-4 main-area bg-surface">
                              <?= $content ?>
                    </main>
          </div>

          <script src="https://code.jquery.com/jquery-3.7.1.min.js?v=<?= $cacheBuster ?>"></script>
          <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js?v=<?= $cacheBuster ?>"></script>
          <script src="<?= htmlspecialchars($base) ?>/assets/js/app.js?v=<?= $cacheBuster ?>"></script>
          <?= $footerScripts ?? '' ?>
</body>

</html>