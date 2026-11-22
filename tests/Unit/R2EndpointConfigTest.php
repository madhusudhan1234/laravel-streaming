<?php

namespace Tests\Unit;

use App\Support\R2Endpoint;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

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
            'not set' => [null, null],
        ];
    }

    #[DataProvider('endpoints')]
    public function test_bucket_is_stripped_from_r2_endpoint(?string $endpoint, ?string $expected): void
    {
        $this->assertSame($expected, R2Endpoint::normalize($endpoint, 'my-bucket'));
    }

    public function test_empty_bucket_only_trims_slash(): void
    {
        $this->assertSame('https://abc.r2.cloudflarestorage.com', R2Endpoint::normalize('https://abc.r2.cloudflarestorage.com/', ''));
    }
}
