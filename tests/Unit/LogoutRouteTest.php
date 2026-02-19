<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

final class LogoutRouteTest extends TestCase
{
    public function test_authenticated_session_controller_has_destroy_method(): void
    {
        $reflection = new ReflectionClass(AuthenticatedSessionController::class);

        $this->assertTrue($reflection->hasMethod('destroy'));
    }
}
