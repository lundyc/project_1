<?php
// scripts/generate_analysis_proxy.php
// Generates a 720p analysis proxy MP4 from a source video using ffmpeg.
// Usage: php generate_analysis_proxy.php /path/to/source.mp4

if ($argc < 2) {
    echo "Usage: php generate_analysis_proxy.php /path/to/source.mp4 [output_path]\n";
    exit(1);
}

$source = $argv[1];
$output = $argv[2] ?? preg_replace('/\.mp4$/i', '_proxy.mp4', $source);

if (!file_exists($source)) {
    echo "Source file does not exist: $source\n";
    exit(1);
}

$cmd = sprintf(
    'ffmpeg -y -i %s -vf "scale=-2:720" -c:v libx264 -profile:v main -preset fast -crf 23 -movflags +faststart -an %s',
    escapeshellarg($source),
    escapeshellarg($output)
);

// Run ffmpeg
passthru($cmd, $exitCode);

if ($exitCode === 0) {
    echo "Proxy generated: $output\n";
} else {
    echo "Failed to generate proxy.\n";
    exit($exitCode);
}
