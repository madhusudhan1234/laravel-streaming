<?php

namespace Tests;

use App\Models\Episode;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\DB;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class);
        config(['domains.admin' => 'controls.localhost']);
        try {
            DB::statement('TRUNCATE TABLE episodes RESTART IDENTITY CASCADE');
        } catch (\Throwable $e) {
            Episode::query()->delete();
        }
    }
}
