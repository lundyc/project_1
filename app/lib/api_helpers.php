<?php

function api_read_request_body(): array
{
          $payload = $_POST;
          $rawBody = file_get_contents('php://input');
          if ((empty($payload) || count($payload) === 0) && $rawBody) {
                    $decoded = json_decode($rawBody, true);
                    if (is_array($decoded)) {
                              $payload = $decoded;
                    }
          }
          return $payload;
}

function api_respond_with_json(int $status, array $payload): void
{
          http_response_code($status);
          header('Content-Type: application/json');
          echo json_encode($payload);
          exit;
}
