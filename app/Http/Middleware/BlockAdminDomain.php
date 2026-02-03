<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class BlockAdminDomain
{
    public function handle(Request $request, Closure $next): Response
    {
        if (app()->runningUnitTests()) {
            return $next($request);
        }
        $admin = (string) config('domains.admin');
        if ($admin !== '' && $request->getHost() === $admin) {
            abort(404);
        }

        return $next($request);
    }
}
