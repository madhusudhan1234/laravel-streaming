<?php

return [
    'owner' => env('EPISODES_REPO_OWNER'),
    'name' => env('EPISODES_REPO_NAME'),
    'branch' => env('EPISODES_BRANCH', 'main'),
    'env' => env('EPISODES_ENV', 'production'),
    'token' => env('GITHUB_TOKEN'),
];

