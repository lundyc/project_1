<?php

return [
          'app' => [
                    'name' => 'Analytics Desk',
                    'env'  => 'local',
                    'debug' => true,
                    'base_path' => '', // Set to '' when deploying at domain root
          ],

          'db' => [
                    'host' => '127.0.0.1',
                    'name' => 'project_1',
                    'user' => 'project_1',
                    'pass' => 'D&DNdtopmoff7!29',
                    'charset' => 'utf8mb4',
          ],

          'session' => [
                    'name' => 'analytics_session',
                    'lifetime' => 0,
          ],

          'phase3' => [
                    'video_lab_enabled' => true,
                    // `PHASE_3_VIDEO_LAB_ENABLED` overrides this value when present.
          ],
];
