<?php
require_auth();
require_once __DIR__ . '/../../../lib/match_repository.php';
require_once __DIR__ . '/../../../lib/match_permissions.php';

$user = current_user();
$roles = $_SESSION['roles'] ?? [];
$matches = get_matches_for_user($user);
$canManage = can_manage_matches($user, $roles);
$base = base_path();

$success = $_SESSION['match_form_success'] ?? null;
$error = $_SESSION['match_form_error'] ?? null;
unset($_SESSION['match_form_success'], $_SESSION['match_form_error']);

$title = 'Matches library';

$searchQuery = trim((string)($_GET['q'] ?? ''));
$statusFilter = strtolower(trim((string)($_GET['status'] ?? '')));

$totalMatches = count($matches);
$statusCounts = [];
foreach ($matches as $match) {
          $matchStatus = strtolower(trim((string)($match['status'] ?? '')));
          if ($matchStatus === '') {
                    $matchStatus = 'draft';
          }
          $statusCounts[$matchStatus] = ($statusCounts[$matchStatus] ?? 0) + 1;
}

$statusOrder = ['ready', 'draft'];
$orderedStatuses = [];
foreach ($statusOrder as $statusKey) {
          if (isset($statusCounts[$statusKey])) {
                    $orderedStatuses[$statusKey] = $statusCounts[$statusKey];
          }
}
foreach ($statusCounts as $status => $count) {
          if (isset($orderedStatuses[$status])) {
                    continue;
          }
          $orderedStatuses[$status] = $count;
}

$searchNormalized = strtolower($searchQuery);
$filteredMatches = array_values(
          array_filter($matches, function (array $match) use ($searchNormalized, $statusFilter) {
                    if ($statusFilter !== '') {
                              $matchStatus = strtolower(trim((string)($match['status'] ?? '')));
                              if ($matchStatus === '') {
                                        $matchStatus = 'draft';
                              }
                              if ($matchStatus !== $statusFilter) {
                                        return false;
                              }
                    }

                    if ($searchNormalized === '') {
                              return true;
                    }

                    $haystack = strtolower(implode(' ', [
                              $match['home_team'] ?? '',
                              $match['away_team'] ?? '',
                              $match['competition'] ?? '',
                              $match['venue'] ?? '',
                              $match['notes'] ?? '',
                    ]));

                    return str_contains($haystack, $searchNormalized);
          })
);
$displayedMatches = count($filteredMatches);
$tabBasePath = ($base ?: '') . '/matches';

$formatDuration = static function (?int $seconds): string {
          if ($seconds === null || $seconds <= 0) {
                    return '—';
          }
          if ($seconds >= 3600) {
                    return gmdate('G:i:s', $seconds);
          }
          return gmdate('i:s', $seconds);
};

$normalizeStatusClass = static function (string $status): string {
          $clean = preg_replace('/[^a-z0-9_-]+/i', '', strtolower($status));
          return $clean ?: 'status';
};

$formatStatusLabel = static function (string $status): string {
          if ($status === '') {
                    return 'Unknown';
          }
          return ucwords(str_replace('_', ' ', $status));
};

$buildTabUrl = static function (?string $status) use ($tabBasePath, $searchQuery) {
          $params = [];
          if ($searchQuery !== '') {
                    $params['q'] = $searchQuery;
          }
          if ($status !== null && $status !== '') {
                    $params['status'] = $status;
          }
          $query = http_build_query($params);
          return $tabBasePath . ($query ? ('?' . $query) : '');
};

$buildDownloadFilename = static function (string $title, ?int $kickoffTs): string {
          $slug = preg_replace('/[^A-Za-z0-9]+/', '_', $title);
          $slug = preg_replace('/_+/', '_', trim($slug, '_'));
          if ($slug === '') {
                    $slug = 'match';
          }

          if ($kickoffTs === null || $kickoffTs === false) {
                    $timestamp = 'unknown';
          } else {
                    $timestamp = date('Y-m-d_H-i', (int)$kickoffTs);
          }

          return $slug . '_' . $timestamp . '.mp4';
};

ob_start();
?>
<div class="library-layout">
          <header class="library-layout__header">
                    <div>
                              <p class="library-layout__eyebrow">Video library</p>
                              <h1 class="library-layout__title">Matches</h1>
                              <p class="library-layout__description">Review upcoming footage, check statuses, and keep every match aligned with your workflow.</p>
                    </div>
          </header>

          <div class="library-tabs-row">
                    <nav class="library-tabs" role="tablist">
                              <a class="library-tab <?= $statusFilter === '' ? 'library-tab--active' : '' ?>"
                                        role="tab" href="<?= htmlspecialchars($buildTabUrl('')) ?>">
                                        All <span class="library-tab__count"><?= htmlspecialchars((string)$totalMatches) ?></span>
                              </a>
                              <?php foreach ($orderedStatuses as $status => $count): ?>
                                        <?php $statusLabel = $formatStatusLabel($status); ?>
                                        <a class="library-tab <?= $statusFilter === $status ? 'library-tab--active' : '' ?>"
                                                  role="tab" href="<?= htmlspecialchars($buildTabUrl($status)) ?>">
                                                  <?= htmlspecialchars($statusLabel) ?>
                                                  <span class="library-tab__count"><?= htmlspecialchars((string)$count) ?></span>
                                        </a>
                              <?php endforeach; ?>
                    </nav>
                    <div class="library-tabs-row__right">
                              <form method="get" class="library-search library-search--tabs" role="search">
                                        <label class="visually-hidden" for="matches-search">Search matches</label>
                                        <input id="matches-search" name="q" type="search" placeholder="Search teams, competition, venue, or notes" value="<?= htmlspecialchars($searchQuery) ?>">
                                        <?php if ($statusFilter !== ''): ?>
                                                  <input type="hidden" name="status" value="<?= htmlspecialchars($statusFilter) ?>">
                                        <?php endif; ?>
                                        <button type="submit" class="library-search__submit">Filter</button>
                              </form>
                              <?php if ($canManage): ?>
                                        <a href="<?= htmlspecialchars($base) ?>/matches/create" class="btn btn-primary-soft btn-sm library-create-button">Create match</a>
                              <?php endif; ?>
                    </div>
          </div>

          <p class="library-summary">Showing <?= htmlspecialchars((string)$displayedMatches) ?> of <?= htmlspecialchars((string)$totalMatches) ?> matches</p>

          <?php if ($error): ?>
                    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
          <?php elseif ($success): ?>
                    <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
          <?php endif; ?>

          <?php if ($displayedMatches === 0): ?>
                    <div class="library-empty-state">
                              <p>No matches found for the current view.</p>
                              <?php if ($canManage): ?>
                                        <a href="<?= htmlspecialchars($base) ?>/matches/create" class="btn btn-primary-soft btn-sm">Create first match</a>
                              <?php endif; ?>
                    </div>
          <?php else: ?>
                    <div class="library-list" role="list">
                              <?php foreach ($filteredMatches as $match): ?>
                                        <?php
                                                  $matchId = (int)$match['id'];
                                                  $matchUrl = htmlspecialchars($base . '/matches/' . $matchId.'/desk/');
                                                  $title = trim(($match['home_team'] ?? '') . ' vs ' . ($match['away_team'] ?? ''));
                                                  if ($title === '') {
                                                            $title = 'Untitled match';
                                                  }
                                                  $kickoffTs = $match['kickoff_at'] ? strtotime($match['kickoff_at']) : null;
                                                  $dateLabel = $kickoffTs ? date('M j, Y', $kickoffTs) : 'TBD';
                                                  $timeLabel = $kickoffTs ? date('H:i', $kickoffTs) : 'TBD';
                                                  $status = strtolower(trim((string)($match['status'] ?? '')));
                                                  if ($status === '') {
                                                            $status = 'draft';
                                                  }
                                                  $statusLabel = $formatStatusLabel($status);
                                                  $statusClass = $normalizeStatusClass($status);
                                                  $competition = $match['competition'] ?? '';
                                                  $clubName = $match['club_name'] ?? '';
                                                  $sourceType = $match['video_source_type'] ?? 'unknown';
                                                  $downloadPath = trim((string)($match['video_source_path'] ?? ''));
                                                 $durationSeconds = isset($match['video_duration_seconds']) ? (int)$match['video_duration_seconds'] : null;
                                                 $durationLabel = $formatDuration($durationSeconds);
                                                 $hasVideo = $downloadPath !== '';
                                                  $thumbnailRelativePath = '';
                                                  $thumbnailFsPath = '';
                                                  $hasThumbnail = false;
                                                  $downloadFilename = $hasVideo ? $buildDownloadFilename($title, $kickoffTs) : null;
                                                  $dbThumbnail = trim((string)($match['video_thumbnail_path'] ?? ''));
                                                  if ($dbThumbnail !== '') {
                                                            $dbThumbnail = '/' . ltrim($dbThumbnail, '/');
                                                            $thumbnailRelativePath = $dbThumbnail;
                                                            $thumbnailFsPath = $_SERVER['DOCUMENT_ROOT'] . $thumbnailRelativePath;
                                                            $hasThumbnail = is_file($thumbnailFsPath);
                                                  }
                                                  if (!$hasThumbnail) {
                                                            $thumbnailRelativePath = '/storage/matches/' . $matchId . '/thumbnail.jpg';
                                                            $thumbnailFsPath = $_SERVER['DOCUMENT_ROOT'] . $thumbnailRelativePath;
                                                            $hasThumbnail = is_file($thumbnailFsPath);
                                                  }
                                                  if (!$hasThumbnail) {
                                                            $altThumbnailRelative = '/videos/matches/match_' . $matchId . '/source/veo/standard/thumbnail.jpg';
                                                            $altThumbnailFs = $_SERVER['DOCUMENT_ROOT'] . $altThumbnailRelative;
                                                            if (is_file($altThumbnailFs)) {
                                                                      $thumbnailRelativePath = $altThumbnailRelative;
                                                                      $hasThumbnail = true;
                                                            }
                                                  }
                                        ?>
                                        <div class="library-row" data-match-id="<?= htmlspecialchars((string)$matchId) ?>">
                                                  <div class="library-row__main">
                                                            <a href="<?= $matchUrl ?>" class="library-row__link" aria-label="Open <?= htmlspecialchars($title) ?>">
                                                                      <div class="library-row__thumbnail">
                                                                                <?php if ($hasThumbnail): ?>
                                                                                          <img
                                                                                                    src="<?= htmlspecialchars($thumbnailRelativePath) ?>"
                                                                                                    alt="<?= htmlspecialchars($title) ?> thumbnail"
                                                                                                    loading="lazy"
                                                                                          >
                                                                                <?php elseif ($hasVideo): ?>
                                                                                          <span class="library-row__placeholder">Processing</span>
                                                                                <?php else: ?>
                                                                                          <span class="library-row__placeholder">No preview</span>
                                                                                <?php endif; ?>

                                                                                <?php if ($durationSeconds): ?>
                                                                                          <span class="library-row__duration"><?= htmlspecialchars($durationLabel) ?></span>
                                                                                <?php endif; ?>
                                                                      </div>

                                                                      <div class="library-row__details">
                                                                                <div class="library-row__title">
                                                                                          <h3><?= htmlspecialchars($title) ?></h3>
                                                                                          <span class="status-badge status-badge--<?= htmlspecialchars($statusClass) ?>"><?= htmlspecialchars($statusLabel) ?></span>
                                                                                </div>
                                                                                <p class="library-row__meta">
                                                                                          <span><?= htmlspecialchars($dateLabel) ?> · <?= htmlspecialchars($timeLabel) ?></span>
                                                                                          <?php if ($competition !== ''): ?>
                                                                                                    <span class="library-row__meta-separator"> · </span>
                                                                                                    <span><?= htmlspecialchars($competition) ?></span>
                                                                                          <?php endif; ?>
                                                                                </p>
                                                                                <p class="library-row__meta library-row__meta--muted">
                                                                                          <?= $clubName !== '' ? htmlspecialchars($clubName) : 'Club unknown' ?>
                                                                                          <?php if (!empty($match['venue'])): ?>
                                                                                                    <span class="library-row__meta-venue"><?= htmlspecialchars($match['venue']) ?></span>
                                                                                          <?php endif; ?>
                                                                                </p>
                                                                                <p class="library-row__meta library-row__meta--muted">
                                                                                          <span class="library-row__label">Source</span>
                                                                                          <span class="library-row__value"><?= htmlspecialchars(strtoupper($sourceType)) ?></span>
                                                                                </p>
                                                                      </div>
                                                            </a>
                                                  </div>
                                                  <div class="library-row__actions">
                                                            <div class="dropdown">
                                                                      <button class="library-row__menu" type="button" id="matchMenu-<?= htmlspecialchars((string)$matchId) ?>" data-bs-toggle="dropdown" aria-expanded="false" aria-label="More actions for <?= htmlspecialchars($title) ?>">
                                                                                <i class="fa-solid fa-ellipsis-vertical"></i>
                                                                      </button>
                                                                      <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="matchMenu-<?= htmlspecialchars((string)$matchId) ?>">
                                                                                <li>
                                                                                          <a class="dropdown-item" href="<?= htmlspecialchars($base . '/matches/' . $matchId . '/edit') ?>">
                                                                                                    <i class="fa-solid fa-pen"></i>
                                                                                                    Edit
                                                                                          </a>
                                                                                </li>
                                                                                <li>
                                                                                          <button type="button" class="dropdown-item" data-share-match-id="<?= htmlspecialchars((string)$matchId) ?>">
                                                                                                    <i class="fa-solid fa-share-nodes"></i>
                                                                                                    Share
                                                                                          </button>
                                                                                </li>
                                                                                <li>
                                                                                          <form method="post" action="<?= htmlspecialchars($base . '/api/matches/' . $matchId . '/delete') ?>" class="m-0" onsubmit="return confirm('Delete this match?');">
                                                                                                    <input type="hidden" name="match_id" value="<?= htmlspecialchars((string)$matchId) ?>">
                                                                                                    <button type="submit" class="dropdown-item dropdown-item-danger">
                                                                                                              <i class="fa-solid fa-trash"></i>
                                                                                                              Delete
                                                                                                    </button>
                                                                                          </form>
                                                                                </li>
                                                                                <?php if ($hasVideo): ?>
                                                                                          <li>
                                                                                                    <a class="dropdown-item" href="<?= htmlspecialchars($downloadPath) ?>" download="<?= htmlspecialchars($downloadFilename) ?>">
                                                                                                              <i class="fa-solid fa-download"></i>
                                                                                                              Download
                                                                                                    </a>
                                                                                          </li>
                                                                                <?php endif; ?>
                                                                      </ul>
                                                            </div>
                                                  </div>
                                        </div>
                              <?php endforeach; ?>
                    </div>
          <?php endif; ?>
</div>
<div class="modal fade" id="matchShareModal" tabindex="-1" aria-labelledby="matchShareModalLabel" aria-hidden="true">
          <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                              <div class="modal-header">
                                        <h5 class="modal-title" id="matchShareModalLabel">Share your best moments</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                              </div>
                              <div class="modal-body">
                                        <p class="share-modal__description">Remember to keep the shared match setting on Public, so that everyone can enjoy great action.</p>
                                        <div class="share-modal__actions">
                                                  <a class="share-modal__option" href="#" target="_blank" rel="noopener" data-share-modal-whatsapp>
                                                            <span class="share-modal__icon">
                                                                      <i class="fa-brands fa-whatsapp"></i>
                                                            </span>
                                                            <span>Share via WhatsApp</span>
                                                  </a>
                                                  <button type="button" class="share-modal__option share-modal__option--copy" data-share-modal-copy>
                                                            <span class="share-modal__icon">
                                                                      <i class="fa-solid fa-link"></i>
                                                            </span>
                                                            <span>Copy link</span>
                                                  </button>
                                        </div>
                                        <p class="share-modal__link" data-share-modal-link></p>
                              </div>
                    </div>
          </div>
</div>
<?php
$content = ob_get_clean();
    $footerScripts = <<<'HTML'
<script>
(function () {
          console.log('[share-modal] script init');
          const basePath = document.querySelector('meta[name="base-path"]')?.content || '';
          const trimmedBasePath = basePath ? basePath.replace(/^\/+|\/+$/g, '') : '';
          const normalizedBasePath = trimmedBasePath ? '/' + trimmedBasePath : '';
          const apiMatchesBase = normalizedBasePath + '/api/matches';
          const modalEl = document.getElementById('matchShareModal');
          const shareModal = modalEl ? new bootstrap.Modal(modalEl) : null;
          const shareModalLink = modalEl?.querySelector('[data-share-modal-link]');
          const shareModalWhatsApp = modalEl?.querySelector('[data-share-modal-whatsapp]');
          const shareModalCopyBtn = modalEl?.querySelector('[data-share-modal-copy]');
          let currentShareLink = '';

          const copyLink = (text) => {
                    if (navigator.clipboard && navigator.clipboard.writeText) {
                              return navigator.clipboard.writeText(text);
                    }
                    return Promise.reject();
          };

          const buildShareEndpoint = (matchId) => apiMatchesBase + '/' + encodeURIComponent(matchId) + '/share';

          async function fetchShareData(matchId) {
                    const endpoint = buildShareEndpoint(matchId);
                    const response = await fetch(endpoint, {
                              headers: { Accept: 'application/json' },
                              credentials: 'same-origin',
                    });
                    const contentType = response.headers.get('content-type') || '';
                    if (!contentType.includes('application/json')) {
                              throw new Error('Expected JSON, received ' + contentType);
                    }
                    if (!response.ok) {
                              throw new Error(`HTTP ${response.status}`);
                    }
                    const data = await response.json();
                    if (!data || typeof data.share_url !== 'string' || data.share_url.trim() === '') {
                              throw new Error('Invalid share payload');
                    }
                    return data;
          }

          function renderShareModal(data) {
                    const link = (data.share_url ?? '').trim();
                    if (!link) {
                              throw new Error('Missing share URL');
                    }
                    currentShareLink = link;
                    if (shareModalLink) {
                              shareModalLink.textContent = link;
                    }
                    if (shareModalWhatsApp) {
                              shareModalWhatsApp.href = 'https://wa.me/?text=' + encodeURIComponent(link);
                              shareModalWhatsApp.setAttribute('target', '_blank');
                    }
                    shareModal?.show();
          }

          async function openShareModal(matchId) {
                    try {
                              const data = await fetchShareData(matchId);
                              renderShareModal(data);
                    } catch (err) {
                              console.error('[share-modal]', err);
                              alert('Unable to load share options.');
                    }
          }

          shareModalCopyBtn?.addEventListener('click', () => {
                    if (!currentShareLink) {
                              return;
                    }
                    copyLink(currentShareLink).then(() => {
                              alert('Match link copied to clipboard.');
                    }).catch(() => {
                              prompt('Copy match link', currentShareLink);
                    });
          });

          document.addEventListener('click', (event) => {
                    const shareTrigger = event.target.closest('[data-share-match-id]');
                    if (!shareTrigger) {
                              return;
                    }

                    event.preventDefault();

                    const matchId = shareTrigger.dataset.shareMatchId?.trim();
                    if (!matchId) {
                              return;
                    }

                    openShareModal(matchId);
          });
})();
</script>
HTML;
require __DIR__ . '/../../layout.php';
