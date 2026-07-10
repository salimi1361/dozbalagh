<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $roleName = $request->user()?->role?->name;

        abort_unless($roleName && in_array($roleName, $roles, true), 403);

        return $next($request);
    }
}
