<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();
        $roleName = $user?->role?->name;

        abort_unless($user?->isActive() && $roleName && in_array($roleName, $roles, true), 403);

        return $next($request);
    }
}
