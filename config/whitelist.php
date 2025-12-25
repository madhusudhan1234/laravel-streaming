<?php

$csv = env('AUTH_WHITELIST_EMAILS', '');
$emails = array_values(array_filter(array_map('trim', explode(',', $csv))));

return [
    'enabled' => (bool) env('AUTH_WHITELIST_ENABLED', false),
    'emails' => $emails,
];
