<?php

/**
 * Example Configuration File
 * 
 * Copy this file to config.php and update with your actual credentials.
 * The config.php file is ignored by git to protect sensitive data.
 * 
 * IMPORTANT: Use environment variables (.env file) for production deployments.
 * This file serves as a fallback for local development only.
 */

return [
          'app' => [
                    'name' => 'Analytics Desk',
                    'env'  => 'local',  // 'local', 'staging', or 'production'
                    'debug' => true,     // Set to false in production
                    'base_path' => '',   // Set to '' when deploying at domain root
          ],

          'db' => [
                    'host' => '127.0.0.1',
                    'name' => 'project_1',
                    'user' => 'project_1',
                    'pass' => 'YOUR_DATABASE_PASSWORD_HERE',  // ⚠️ Change this!
                    'charset' => 'utf8mb4',
          ],

          'session' => [
                    'name' => 'analytics_session',
                    'lifetime' => 86400,  // 24 hours in seconds (0 = until browser closes)
          ],

          'phase3' => [
                    'video_lab_enabled' => true,
                    // `PHASE_3_VIDEO_LAB_ENABLED` env var overrides this value
          ],
          
          'annotations' => [
                    'enabled' => true,
                    // `ANNOTATIONS_ENABLED` env var overrides this value
          ],
];
