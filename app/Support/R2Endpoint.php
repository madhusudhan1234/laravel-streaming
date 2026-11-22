<?php

namespace App\Support;

class R2Endpoint
{
    /**
     * Remove a trailing "/<bucket>" from an R2 S3 endpoint.
     *
     * Path-style requests add the bucket to the URL themselves. If the endpoint already ends with
     * the bucket (as copied from the Cloudflare dashboard), the bucket appears twice and every
     * object is stored under a "<bucket>/" prefix.
     */
    public static function normalize(?string $endpoint, ?string $bucket): ?string
    {
        $endpoint = rtrim((string) $endpoint, '/');

        if ($bucket !== null && $bucket !== '') {
            $endpoint = (string) preg_replace('#/'.preg_quote($bucket, '#').'$#', '', $endpoint);
        }

        return $endpoint !== '' ? $endpoint : null;
    }
}
