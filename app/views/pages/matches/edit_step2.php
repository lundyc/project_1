<?php
require_auth();
require_once __DIR__ . '/../../../lib/match_permissions.php';

$user = current_user();
$roles = $_SESSION['roles'] ?? [];
$base = base_path();

if (!isset($match) || !is_array($match)) {
    http_response_code(404);
    echo '404 Not Found';
    exit;
}

$matchId = (int)$match['id'];

$error = $_SESSION['match_form_error'] ?? null;
$success = $_SESSION['match_form_success'] ?? null;
unset($_SESSION['match_form_error']);
unset($_SESSION['match_form_success']);

$title = 'Edit Match - Video Source';
$videoType = $match['video_source_type'] ?? 'upload';
$videoPath = $match['video_source_path'] ?? '';
$initialDownloadStatus = $match['video_download_status'] ?? '';
$initialDownloadProgress = (int)($match['video_download_progress'] ?? 0);
$initialVeoUrl = $match['video_source_url'] ?? '';

$videoFiles = [];
$videoDir = realpath(dirname(__DIR__, 4) . DIRECTORY_SEPARATOR . 'videos' . DIRECTORY_SEPARATOR . 'raw');
$allowedVideoExt = ['mp4', 'webm', 'mov'];

if ($videoDir && is_dir($videoDir)) {
    $items = scandir($videoDir);
    foreach ($items as $item) {
        if ($item === '.' || $item === '..') {
            continue;
        }
        $full = $videoDir . DIRECTORY_SEPARATOR . $item;
        if (!is_file($full)) {
            continue;
        }
        $real = realpath($full);
        if (!$real || !str_starts_with($real, $videoDir)) {
            continue;
        }
        $ext = strtolower(pathinfo($item, PATHINFO_EXTENSION));
        if (!in_array($ext, $allowedVideoExt, true)) {
            continue;
        }
        $videoFiles[] = [
            'filename' => $item,
            'web_path' => '/videos/raw/' . $item,
        ];
    }
}
$hasCurrentVideo = $videoPath && !empty(array_filter($videoFiles, fn($f) => $f['web_path'] === $videoPath));

$wizardConfig = [
    'basePath' => $base,
    'matchId' => $matchId,
    'updateEndpoint' => $base . '/api/matches/' . $matchId . '/update-video',
    'initialVideoType' => $videoType,
    'initialDownloadStatus' => $initialDownloadStatus ?: null,
    'initialDownloadProgress' => $initialDownloadProgress,
    'initialVeoUrl' => $initialVeoUrl,
    'pollInterval' => 2000,
];

$footerScripts = '<script>window.MatchWizardConfig = ' . json_encode($wizardConfig) . ';</script>';
$footerScripts .= '<script src="' . htmlspecialchars($base) . '/assets/js/match-video-editor.js?v=' . time() . '"></script>';

ob_start();
?>

<div class="bg-slate-950 min-h-screen py-10">
    <div class="max-w-5xl mx-auto space-y-6 px-4">
        <div class="flex items-center justify-between">
            <a href="<?= htmlspecialchars($base) ?>/matches/<?= $matchId ?>/edit" class="text-sm font-medium text-slate-400 hover:text-slate-200 flex items-center gap-2">
                <span aria-hidden="true">←</span>
                Back to match details
            </a>
            <a href="<?= htmlspecialchars($base) ?>/matches/<?= $matchId ?>/desk" class="rounded-full border border-slate-800 px-4 py-2 text-sm font-semibold text-slate-300 transition hover:border-slate-600 hover:text-white">
                Open Analysis Desk
            </a>
        </div>

        <header class="space-y-1">
            <h1 class="text-3xl font-semibold text-white">Video Source</h1>
            <p class="text-sm text-slate-400">Manage video download and processing for this match</p>
            <p class="text-sm text-slate-500"><?= htmlspecialchars($match['home_team']) ?> vs <?= htmlspecialchars($match['away_team']) ?></p>
        </header>

        <?php if ($error): ?>
            <div class="rounded-2xl border border-rose-700/60 bg-rose-900/60 px-4 py-3 text-sm text-rose-100">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php elseif ($success): ?>
            <div class="rounded-2xl border border-emerald-600/60 bg-emerald-900/60 px-4 py-3 text-sm text-emerald-100">
                <?= htmlspecialchars($success) ?>
            </div>
        <?php endif; ?>

        <form id="videoSourceForm" method="post" action="<?= htmlspecialchars($base) ?>/api/matches/<?= $matchId ?>/update-video" class="space-y-6">
            <input type="hidden" name="match_id" value="<?= $matchId ?>">

            <section class="bg-slate-900 border border-slate-800 rounded-xl p-6 space-y-6">
                <div class="flex items-center justify-between">
                    <p class="text-xs uppercase tracking-[0.4em] text-slate-500">Video Source</p>
                </div>
                <div class="grid gap-4 md:grid-cols-2">
                    <label class="cursor-pointer rounded-xl border border-slate-800 bg-slate-950 p-4 transition hover:border-blue-500 hover:bg-slate-900/80" for="videoModeVeo">
                        <div class="flex items-start gap-3">
                            <input type="radio" name="video_mode" id="videoModeVeo" value="veo" <?= $videoType === 'veo' ? 'checked' : '' ?> class="mt-1 h-4 w-4 text-blue-500 focus:ring-blue-500">
                            <div>
                                <p class="text-lg font-semibold text-white">VEO Camera</p>
                                <p class="text-sm text-slate-400">Download from https://app.veo.co/matches/</p>
                            </div>
                        </div>
                    </label>
                    <label class="cursor-pointer rounded-xl border border-slate-800 bg-slate-950 p-4 transition hover:border-blue-500 hover:bg-slate-900/80" for="videoModeUpload">
                        <div class="flex items-start gap-3">
                            <input type="radio" name="video_mode" id="videoModeUpload" value="upload" <?= $videoType !== 'veo' ? 'checked' : '' ?> class="mt-1 h-4 w-4 text-blue-500 focus:ring-blue-500">
                            <div>
                                <p class="text-lg font-semibold text-white">Upload File</p>
                                <p class="text-sm text-slate-400">Import a raw video file from the server</p>
                            </div>
                        </div>
                    </label>
                </div>

                <div id="videoUploadGroup" class="<?= $videoType === 'veo' ? 'hidden' : '' ?> space-y-3">
                    <label class="text-sm font-semibold text-slate-200" for="video_file_select">Select raw video file</label>
                    <select id="video_file_select" name="video_source_path" class="w-full rounded-xl border border-slate-800 bg-slate-950 px-3 py-3 text-sm text-white focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/40" <?= $videoType === 'veo' ? 'disabled' : '' ?> <?= empty($videoFiles) ? 'disabled' : '' ?>>
                        <option value="">Select raw video</option>
                        <?php foreach ($videoFiles as $file): ?>
                            <option value="<?= htmlspecialchars($file['web_path']) ?>" <?= $videoPath === $file['web_path'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($file['filename']) ?>
                            </option>
                        <?php endforeach; ?>
                        <?php if ($videoType !== 'veo' && $videoPath && !$hasCurrentVideo): ?>
                            <option value="<?= htmlspecialchars($videoPath) ?>" selected>
                                Current: <?= htmlspecialchars(basename($videoPath)) ?>
                            </option>
                        <?php endif; ?>
                    </select>
                    <p class="text-xs text-slate-500">
                        <?= empty($videoFiles) ? 'No raw videos found in /videos/raw directory.' : 'Choose from the raw videos directory on the server.' ?>
                    </p>
                </div>

                <div id="videoVeoGroup" class="<?= $videoType !== 'veo' ? 'hidden' : '' ?> space-y-3">
                    <label class="text-sm font-semibold text-slate-200" for="video_url_input">VEO Match URL</label>
                    <input type="text" id="video_url_input" name="veo_url" placeholder="https://app.veo.co/matches/..." value="<?= htmlspecialchars($videoType === 'veo' ? $initialVeoUrl : '') ?>" class="w-full rounded-xl border border-slate-800 bg-slate-950 px-3 py-3 text-sm text-white focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/40" <?= $videoType === 'veo' ? '' : 'disabled' ?>>
                    <p class="text-xs text-slate-500">Server-side download using yt-dlp and ffmpeg.</p>
                </div>

                <div class="flex justify-end">
                    <button type="submit" id="saveVideoBtn" class="rounded-full bg-blue-600 px-5 py-2 text-sm font-semibold text-white transition hover:bg-blue-500">
                        Save video source
                    </button>
                </div>
            </section>

            <section class="bg-slate-900 border border-slate-800 rounded-xl p-6 space-y-4">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <p class="text-xs uppercase tracking-[0.4em] text-slate-500">Download status</p>
                        <p class="text-sm text-slate-400">Monitor ongoing downloads</p>
                    </div>
                    <div class="flex items-center gap-3">
                        <span id="veoInlineStatusText" class="text-sm font-semibold text-slate-200"><?= htmlspecialchars($initialDownloadStatus ?: 'Pending') ?></span>
                        <span id="veoInlineStatusBadge" class="inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold text-slate-300 bg-slate-800">
                            <?= htmlspecialchars($initialDownloadStatus ? ucfirst($initialDownloadStatus) : 'Pending') ?>
                        </span>
                    </div>
                </div>
                <p class="text-sm text-slate-400">
                    <?= empty($initialDownloadStatus) ? 'No active download. Start one from the form above.' : 'Live progress updates will appear below.' ?>
                </p>
                <div id="veoDownloadPanel" class="space-y-3 <?= empty($initialDownloadStatus) || $initialDownloadStatus === 'completed' ? 'hidden' : '' ?>">
                    <div class="flex items-center justify-between text-xs uppercase tracking-[0.3em] text-slate-500">
                        <span>Progress</span>
                        <span id="veoInlineProgressText"><?= (int)$initialDownloadProgress ?>%</span>
                    </div>
                    <div class="h-2 w-full rounded-full bg-slate-800">
                        <div id="veoInlineProgressBar" class="h-full rounded-full bg-gradient-to-r from-blue-500 to-teal-400" style="width: <?= (int)$initialDownloadProgress ?>%;"></div>
                    </div>
                    <p id="veoInlineSummary" class="text-sm text-slate-200">
                        <?= $initialDownloadStatus === 'failed' ? 'Download failed' : ($initialDownloadStatus === 'completed' ? 'Download complete' : 'Downloading...') ?>
                    </p>
                    <p id="veoInlineError" class="hidden text-sm text-rose-400"></p>
                </div>
            </section>
        </form>
    </div>
</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/../../layout.php';
