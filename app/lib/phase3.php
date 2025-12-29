<?php

require_once __DIR__ . '/audit_service.php';

/**
 * Determine whether Phase 3 features are enabled.
 */
function phase3_is_enabled(): bool
{
          static $cached;

          if ($cached !== null) {
                    return $cached;
          }

          $env = getenv('PHASE_3_VIDEO_LAB_ENABLED');
          $cached = phase3_normalize_env_flag($env);
          if ($cached !== null) {
                    return $cached;
          }

          $config = require __DIR__ . '/../../config/config.php';
          $cached = isset($config['phase3']['video_lab_enabled']) ? (bool)$config['phase3']['video_lab_enabled'] : true;

          return $cached;
}

/**
 * Normalize an environment flag value to true/false or null when not provided.
 */
function phase3_normalize_env_flag($value): ?bool
{
          if ($value === false || $value === null) {
                    return null;
          }

          $normalized = strtolower(trim((string)$value));
          if ($normalized === '' || in_array($normalized, ['1', 'true', 'yes'], true)) {
                    return true;
          }

          if (in_array($normalized, ['0', 'false', 'no'], true)) {
                    return false;
          }

          return null;
}

/**
 * Log lightweight phase 3 clip actions (generated/regenerated/review/disabled).
 */
function phase3_log_clip_action(int $clubId, int $clipId, int $userId, string $action): void
{
          if ($clipId <= 0) {
                    return;
          }

          audit($clubId, $userId, 'clip', $clipId, $action, null, null);
}

/**
 * Log lightweight telemetry for Phase 3 activities.
 *
 * @param array<string, mixed> $metrics
 */
function phase3_log_metrics(array $metrics): void
{
          if (empty($metrics)) {
                    return;
          }

          $parts = [];
          foreach ($metrics as $key => $value) {
                    if (is_array($value) || is_object($value)) {
                              $value = json_encode($value);
                    }
                    $parts[] = sprintf('%s=%s', $key, (string)$value);
          }

          error_log('[phase3-telemetry] ' . implode(' ', $parts));
}
