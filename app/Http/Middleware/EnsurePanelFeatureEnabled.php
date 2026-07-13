<?php

namespace App\Http\Middleware;

use App\Services\PanelFeatureService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePanelFeatureEnabled
{
    public function __construct(private readonly PanelFeatureService $features) {}

    public function handle(Request $request, Closure $next): Response
    {
        $role = $request->user()?->role?->name;

        if ($role !== 'admin') {
            abort_unless($this->features->enabledForRoute($role, $request->route()?->getName()), 403, 'این بخش توسط مدیر سامانه غیرفعال شده است.');
        }

        return $next($request);
    }
}
