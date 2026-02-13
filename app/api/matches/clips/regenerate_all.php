<?php

require_once __DIR__ . '/../../../lib/auth.php';
require_once __DIR__ . '/../../../lib/api_helpers.php';
require_once __DIR__ . '/../../../lib/match_permissions.php';
require_once __DIR__ . '/../../../lib/match_repository.php';
require_once __DIR__ . '/../../../lib/event_repository.php';
require_once __DIR__ . '/../../../lib/clip_repository.php';
require_once __DIR__ . '/../../../lib/clip_generation_service.php';
require_once __DIR__ . '/../../../lib/db.php';

auth_boot();
require_auth();

$matchId = isset($matchId) ? (int)$matchId : (int)($_GET['match_id'] ?? 0);
if ($matchId <= 0) {
          api_respond_with_json(400, ['ok' => false, 'error' => 'match_id_required']);
}

$match = get_match($matchId);
if (!$match) {
          api_respond_with_json(404, ['ok' => false, 'error' => 'match_not_found']);
}

$user = current_user();
$roles = $_SESSION['roles'] ?? [];
$canEdit = in_array('platform_admin', $roles, true) || in_array('club_admin', $roles, true) || in_array('analyst', $roles, true);
$canManage = $canEdit && can_manage_match_for_club($user, $roles, (int)$match['club_id']);

if (!$canManage) {
          api_respond_with_json(403, ['ok' => false, 'error' => 'forbidden']);
}

$events = event_list_for_match($matchId);
if (!$events) {
          api_respond_with_json(200, ['ok' => true, 'created' => 0, 'updated' => 0, 'skipped' => 0]);
}

$created = 0;
$updated = 0;
$skipped = 0;
$pdo = db();

foreach ($events as $event) {
          $eventTypeKey = strtolower((string)($event['event_type_key'] ?? ''));
          if (in_array($eventTypeKey, ['period_start', 'period_end'], true)) {
                    $skipped++;
                    continue;
          }
          $matchSecond = isset($event['match_second']) ? (int)$event['match_second'] : null;
          if ($matchSecond === null || $matchSecond < 0) {
                    $skipped++;
                    continue;
          }

          $beforeClip = get_clip_for_event((int)$event['id']);
          $beforeClipId = $beforeClip ? (int)$beforeClip['id'] : 0;

          $startSecond = max(0, $matchSecond - 15);
          $endSecond = $matchSecond + 15;
          $clipName = generate_clip_name_from_event($event, $matchId);

          if ($beforeClipId > 0) {
                    $stmt = $pdo->prepare(
                              'UPDATE clips
                               SET clip_name = :clip_name,
                                   start_second = :start_second,
                                   end_second = :end_second,
                                   duration_seconds = :duration_seconds,
                                   updated_by = :updated_by,
                                   generation_source = :generation_source
                               WHERE id = :id'
                    );
                    $stmt->execute([
                              'clip_name' => $clipName,
                              'start_second' => $startSecond,
                              'end_second' => $endSecond,
                              'duration_seconds' => $endSecond - $startSecond,
                              'updated_by' => (int)$user['id'],
                              'generation_source' => 'event_auto',
                              'id' => $beforeClipId,
                    ]);
                    $updated++;
                    continue;
          }

          try {
                    create_clip(
                              $matchId,
                              (int)$match['club_id'],
                              (int)$user['id'],
                              (int)$event['id'],
                              [
                                        'clip_name' => $clipName,
                                        'start_second' => $startSecond,
                                        'end_second' => $endSecond,
                              ],
                              'event_auto'
                    );
                    $created++;
          } catch (\Throwable $e) {
                    $skipped++;
          }
}

api_respond_with_json(200, [
          'ok' => true,
          'created' => $created,
          'updated' => $updated,
          'skipped' => $skipped,
]);
