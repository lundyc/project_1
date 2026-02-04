<?php
require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/../lib/asset_helper.php';
auth_boot();

$cspNonce = function_exists('get_csp_nonce') ? get_csp_nonce() : '';
function add_csp_nonce_to_inline_scripts(string $html, string $nonce): string
{
          if ($nonce === '' || $html === '') {
                    return $html;
          }

          return preg_replace_callback(
                    '/<script(?![^>]*\snonce=)(?![^>]*\ssrc=)([^>]*)>/i',
                    function (array $matches) use ($nonce): string {
                              $escaped = htmlspecialchars($nonce, ENT_QUOTES, 'UTF-8');
                              return '<script' . $matches[1] . ' nonce="' . $escaped . '">';
                    },
                    $html
          ) ?? $html;
}
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
          <?= add_csp_nonce_to_inline_scripts($headExtras ?? '', $cspNonce) ?>
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
                  <?php
                  ob_start();
                  require __DIR__ . '/partials/nav.php';
                  $navContent = ob_get_clean();
                  echo add_csp_nonce_to_inline_scripts($navContent, $cspNonce);
                  ?>
        <?php endif; ?>

          <div class="<?= $appShellClassAttr ?>">
                    <main class="<?= $mainClassAttr ?>">
                              <?= add_csp_nonce_to_inline_scripts($content, $cspNonce) ?>
                    </main>
          </div>

          <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
          <script src="<?= htmlspecialchars($base) ?>/assets/js/components.js<?= asset_version('/assets/js/components.js') ?>"></script>
          <script src="<?= htmlspecialchars($base) ?>/assets/js/app.js<?= asset_version('/assets/js/app.js') ?>"></script>
          
          <?= add_csp_nonce_to_inline_scripts($footerScripts ?? '', $cspNonce) ?>
</body>

</html>
