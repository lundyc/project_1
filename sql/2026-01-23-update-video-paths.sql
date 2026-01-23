-- Migration: Update video storage paths for simplified structure
-- Date: 2026-01-23

-- Update all match_videos source_path to new format (remove old folder structure)
UPDATE match_videos
SET source_path = CONCAT('video_', id, '.mp4')
WHERE source_path LIKE '/videos/%';

-- (Optional) If you want to move files on disk, do so in a separate script or manually.
-- This migration only updates the database paths.
