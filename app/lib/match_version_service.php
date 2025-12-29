<?php

require_once __DIR__ . '/db.php';

function bump_events_version(int $matchId): int
{
          $pdo = db();
          $pdo->prepare('UPDATE matches SET events_version = events_version + 1 WHERE id = :id')
                    ->execute(['id' => $matchId]);

          $stmt = $pdo->prepare('SELECT events_version FROM matches WHERE id = :id');
          $stmt->execute(['id' => $matchId]);

          return (int)$stmt->fetchColumn();
}

function bump_clips_version(int $matchId): int
{
          $pdo = db();
          $pdo->prepare('UPDATE matches SET clips_version = clips_version + 1 WHERE id = :id')
                    ->execute(['id' => $matchId]);

          $stmt = $pdo->prepare('SELECT clips_version FROM matches WHERE id = :id');
          $stmt->execute(['id' => $matchId]);

          return (int)$stmt->fetchColumn();
}

function get_clips_version(int $matchId): int
{
          $stmt = db()->prepare('SELECT clips_version FROM matches WHERE id = :id');
          $stmt->execute(['id' => $matchId]);
          $version = $stmt->fetchColumn();

          return $version !== false ? (int)$version : 0;
}
