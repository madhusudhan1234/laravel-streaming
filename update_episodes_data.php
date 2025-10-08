<?php

// Load the episodes.json file
$jsonFile = 'storage/app/episodes.json';
$data = json_decode(file_get_contents($jsonFile), true);

// Starting date: March 21, 2025 (Friday)
// First episode: March 21, 2025 (Friday)
// Subsequent episodes: Every Sunday starting March 23, 2025
$startDate = new DateTime('2025-03-21'); // Friday - Episode 1
$firstSunday = new DateTime('2025-03-23'); // Sunday - Episode 2

foreach ($data['episodes'] as $index => &$episode) {
    if ($index === 0) {
        // First episode stays on March 21, 2025 (Friday)
        $episode['published_date'] = '2025-03-21';
    } else {
        // Calculate Sunday dates: March 23, March 30, April 6, etc.
        $weeksToAdd = $index - 1; // 0 weeks for episode 2, 1 week for episode 3, etc.
        $episodeDate = clone $firstSunday;
        $episodeDate->add(new DateInterval('P' . ($weeksToAdd * 7) . 'D'));
        $episode['published_date'] = $episodeDate->format('Y-m-d');
    }
    
    // Fix duration format (remove "00:" prefix if present)
    if (isset($episode['duration']) && strpos($episode['duration'], '00:') === 0) {
        $episode['duration'] = substr($episode['duration'], 3);
    }
    
    // Remove description field if it exists
    if (isset($episode['description'])) {
        unset($episode['description']);
    }
}

// Save the updated data back to the file
file_put_contents($jsonFile, json_encode($data, JSON_PRETTY_PRINT));

echo "Episodes data updated successfully!\n";
echo "Episode 1: 2025-03-21 (Friday)\n";
echo "Episode 2: 2025-03-23 (Sunday)\n";
echo "Episode 3: 2025-03-30 (Sunday)\n";
echo "Episode 4: 2025-04-06 (Sunday)\n";
echo "Episode 5: 2025-04-13 (Sunday)\n";

?>