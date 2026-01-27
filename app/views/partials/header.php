
<header class="flex flex-col md:flex-row md:items-center md:justify-between gap-3 px-4 md:px-6 mb-0">
	<div>
		<h1 class="text-2xl md:text-3xl font-bold tracking-tight">
			<?= isset($headerTitle) ? htmlspecialchars($headerTitle) : '' ?>
		</h1>
		<?php if (!empty($headerDescription)): ?>
			<p class="text-slate-200 text-sm"><?= htmlspecialchars($headerDescription) ?></p>
		<?php endif; ?>
	</div>
	<div class="flex items-end gap-3">
		<?php if (!empty($headerButtons) && is_array($headerButtons)): ?>
			<?php foreach ($headerButtons as $buttonHtml): ?>
				<?= $buttonHtml ?>
			<?php endforeach; ?>
		<?php endif; ?>
	</div>
</header>
