<?php

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class R2EndpointConfigTest extends TestCase
{
    public static function endpoints(): array
    {
        return [
            'account endpoint' => ['https://abc.r2.cloudflarestorage.com', 'https://abc.r2.cloudflarestorage.com'],
            'trailing slash' => ['https://abc.r2.cloudflarestorage.com/', 'https://abc.r2.cloudflarestorage.com'],
            'endpoint with bucket' => ['https://abc.r2.cloudflarestorage.com/my-bucket', 'https://abc.r2.cloudflarestorage.com'],
            'endpoint with bucket and slash' => ['https://abc.r2.cloudflarestorage.com/my-bucket/', 'https://abc.r2.cloudflarestorage.com'],
            'other path kept' => ['https://abc.r2.cloudflarestorage.com/other', 'https://abc.r2.cloudflarestorage.com/other'],
        ];
    }

    #[DataProvider('endpoints')]
    public function test_bucket_is_stripped_from_r2_endpoint(string $endpoint, string $expected): void
    {
        putenv("R2_ENDPOINT={$endpoint}");
        putenv('R2_BUCKET=my-bucket');
        $_ENV['R2_ENDPOINT'] = $_SERVER['R2_ENDPOINT'] = $endpoint;
        $_ENV['R2_BUCKET'] = $_SERVER['R2_BUCKET'] = 'my-bucket';

        $config = require config_path('filesystems.php');

        $this->assertSame($expected, $config['disks']['r2']['endpoint']);
    }
}
