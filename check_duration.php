<?php

$audioFile = 'public/audios/fifth-episode.MP3';

if (file_exists($audioFile)) {
    // Get file size
    $fileSize = filesize($audioFile);
    echo "File size: " . number_format($fileSize / 1024 / 1024, 2) . " MB\n";
    
    // Try to get duration using HTML5 audio metadata (if available)
    // For now, let's just check if the file exists and is readable
    echo "File exists and is readable\n";
    
    // Calculate approximate duration based on file size (rough estimate for MP3)
    // Average MP3 bitrate is around 128 kbps
    $estimatedDuration = ($fileSize * 8) / (128 * 1000); // in seconds
    $minutes = floor($estimatedDuration / 60);
    $seconds = floor($estimatedDuration % 60);
    echo "Estimated duration: " . sprintf('%02d:%02d', $minutes, $seconds) . "\n";
} else {
    echo "File not found: $audioFile\n";
}