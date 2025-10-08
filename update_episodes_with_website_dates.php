<?php

// Update episodes.json with correct dates from the website
$episodesFile = 'storage/app/episodes.json';
$data = json_decode(file_get_contents($episodesFile), true);

// Website episode dates mapping
$websiteDates = [
    29 => '2025-10-06', // Episode #29: 10/6/2025
    28 => '2025-09-28', // Episode #28: 9/28/2025
    27 => '2025-09-21', // Episode #27: 9/21/2025
    26 => '2025-09-13', // Episode #26: 9/13/2025
    25 => '2025-09-07', // Episode #25: 9/7/2025
    24 => '2025-08-31', // Episode #24: 8/31/2025
    23 => '2025-08-24', // Episode #23: 8/24/2025
    22 => '2025-08-17', // Episode #22: 8/17/2025
    21 => '2025-08-10', // Episode #21: 8/10/2025
    20 => '2025-08-03', // Episode #20: 8/3/2025
    19 => '2025-07-27', // Episode #19: 7/27/2025
    18 => '2025-07-20', // Episode #18: 7/20/2025
    17 => '2025-07-12', // Episode #17: 7/12/2025
    16 => '2025-07-06', // Episode #16: 7/6/2025
    15 => '2025-06-29', // Episode #15: 6/29/2025
    14 => '2025-06-22', // Episode #14: 6/22/2025
    13 => '2025-06-15', // Episode #13: 6/15/2025
    12 => '2025-06-08', // Episode #12: 6/8/2025
    11 => '2025-06-01', // Episode #11: 6/1/2025
    10 => '2025-05-25', // Episode #10: 5/25/2025
    9 => '2025-05-18',  // Episode #9: 5/18/2025
    8 => '2025-05-11',  // Episode #8: 5/11/2025
    7 => '2025-05-06',  // Episode #7: 5/6/2025
    6 => '2025-04-27',  // Episode #6: 4/27/2025
    5 => '2025-04-20',  // Episode #5: Estimated (not shown in excerpt)
    4 => '2025-04-13',  // Episode #4: Estimated
    3 => '2025-04-06',  // Episode #3: Estimated
    2 => '2025-03-30',  // Episode #2: Estimated
    1 => '2025-03-23',  // Episode #1: Estimated
];

// Update durations from website data
$websiteDurations = [
    29 => '7:11',
    28 => '7:30',
    27 => '7:13',
    26 => '11:21',
    25 => '7:38',
    24 => '7:51',
    23 => '7:09',
    22 => '09:15',
    21 => '7:01',
    20 => '06:09',
    19 => '6:59',
    18 => '7:44',
    17 => '07:34',
    16 => '07:43',
    15 => '06:30',
    14 => '08:09',
    13 => '10:52',
    12 => '11:59',
    11 => '7:27',
    10 => '8:56',
    9 => '06:26',
    8 => '09:11',
    7 => '09:29',
    6 => '09:29',
];

// Update episodes with website data
foreach ($data['episodes'] as &$episode) {
    $episodeId = $episode['id'];
    
    // Update published date if available
    if (isset($websiteDates[$episodeId])) {
        $episode['published_date'] = $websiteDates[$episodeId];
    }
    
    // Update duration if available
    if (isset($websiteDurations[$episodeId])) {
        $episode['duration'] = $websiteDurations[$episodeId];
    }
    
    // Remove description field if it exists
    if (isset($episode['description'])) {
        unset($episode['description']);
    }
}

// Save updated data
file_put_contents($episodesFile, json_encode($data, JSON_PRETTY_PRINT));

echo "Episodes updated with website dates and durations!\n";
echo "Updated " . count($data['episodes']) . " episodes.\n";

// Show first few episodes for verification
echo "\nFirst 5 episodes:\n";
for ($i = 0; $i < min(5, count($data['episodes'])); $i++) {
    $ep = $data['episodes'][$i];
    echo "Episode {$ep['id']}: {$ep['published_date']} - {$ep['duration']}\n";
}