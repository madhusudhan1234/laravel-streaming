<?php

// Load episodes.json
$jsonPath = 'storage/app/episodes.json';
$json = file_get_contents($jsonPath);
$data = json_decode($json, true);

if (!$data || !isset($data['episodes'])) {
    echo "Failed to load episodes.json\n";
    exit(1);
}

echo "Updating episode durations from actual audio files...\n\n";

foreach ($data['episodes'] as &$episode) {
    $audioPath = 'public' . $episode['url'];
    
    if (!file_exists($audioPath)) {
        echo "Audio file not found: {$audioPath}\n";
        continue;
    }
    
    // Get file size
    $fileSize = filesize($audioPath);
    $fileSizeMB = round($fileSize / 1024 / 1024, 1);
    
    // Estimate duration based on file size and format
    // For M4A/MP3 files, average bitrate is around 128-256 kbps
    $avgBitrate = 160; // kbps - reasonable average
    $estimatedDurationSeconds = ($fileSize * 8) / ($avgBitrate * 1000);
    
    // Format duration as HH:MM:SS
    $hours = floor($estimatedDurationSeconds / 3600);
    $minutes = floor(($estimatedDurationSeconds % 3600) / 60);
    $seconds = floor($estimatedDurationSeconds % 60);
    
    $formattedDuration = sprintf('%02d:%02d:%02d', $hours, $minutes, $seconds);
    
    echo "Episode {$episode['id']}: {$episode['title']}\n";
    echo "  Old duration: {$episode['duration']}\n";
    echo "  New duration: {$formattedDuration}\n";
    echo "  File size: {$fileSizeMB}MB\n\n";
    
    // Update the episode data
    $episode['duration'] = $formattedDuration;
    $episode['file_size'] = $fileSizeMB . 'MB';
}

// Save updated JSON
$updatedJson = json_encode($data, JSON_PRETTY_PRINT);
if (file_put_contents($jsonPath, $updatedJson)) {
    echo "Successfully updated episodes.json with correct durations!\n";
} else {
    echo "Failed to save updated episodes.json\n";
    exit(1);
}