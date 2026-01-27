<?php

function match_session_secret(): string
{
          $secret = getenv('MATCH_SESSION_SECRET');
          if (is_string($secret) && $secret !== '') {
                    return $secret;
          }
          return 'dev-session-secret-change-me';
}

function match_session_base64url_encode(string $data): string
{
          return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

function match_session_create_token(array $payload): string
{
          $payloadJson = json_encode($payload, JSON_UNESCAPED_SLASHES);
          if ($payloadJson === false) {
                    throw new RuntimeException('Unable to encode match session payload');
          }
          $payloadEncoded = match_session_base64url_encode($payloadJson);
          $signatureRaw = hash_hmac('sha256', $payloadJson, match_session_secret(), true);
          $signatureEncoded = match_session_base64url_encode($signatureRaw);
          return $payloadEncoded . '.' . $signatureEncoded;
}
