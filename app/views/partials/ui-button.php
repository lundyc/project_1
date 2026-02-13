<?php
$label = $label ?? '';
$href = $href ?? null;
$type = $type ?? 'button';
$variant = $variant ?? 'primary';
$size = $size ?? 'md';
$class = $class ?? '';
$attrs = $attrs ?? '';

$validTypes = ['button', 'submit', 'reset'];
if (!in_array($type, $validTypes, true)) {
    $type = 'button';
}

$validVariants = ['primary', 'secondary', 'danger', 'ghost'];
if (!in_array($variant, $validVariants, true)) {
    $variant = 'primary';
}

$validSizes = ['sm', 'md', 'lg'];
if (!in_array($size, $validSizes, true)) {
    $size = 'md';
}

$classes = trim('ui-btn ui-btn--' . $variant . ($size !== 'md' ? ' ui-btn--' . $size : '') . ' ' . $class);
$labelEscaped = htmlspecialchars((string)$label, ENT_QUOTES, 'UTF-8');
?>

<?php if ($href): ?>
    <a href="<?= htmlspecialchars((string)$href, ENT_QUOTES, 'UTF-8') ?>" class="<?= $classes ?>" <?= $attrs ?>><?= $labelEscaped ?></a>
<?php else: ?>
    <button type="<?= htmlspecialchars($type, ENT_QUOTES, 'UTF-8') ?>" class="<?= $classes ?>" <?= $attrs ?>><?= $labelEscaped ?></button>
<?php endif; ?>
