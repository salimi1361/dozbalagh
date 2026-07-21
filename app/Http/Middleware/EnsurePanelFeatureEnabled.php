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
        $user = $request->user();
        $role = $user?->role?->name;

        if ($role !== 'admin') {
            abort_unless($this->features->enabledForUserRoute($user, $request->route()?->getName()), 403, 'شما به این بخش دسترسی ندارید یا این بخش توسط مدیر سامانه غیرفعال شده است.');
        }

        return $next($request);
    }
}
