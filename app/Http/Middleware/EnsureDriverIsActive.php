<?php

namespace App\Http\Middleware;

use App\Models\Driver;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureDriverIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $driver = $request->user();

        if ($driver instanceof Driver && ! $driver->isActive()) {
            return response()->json([
                'status' => 'error',
                'message' => 'دسترسی این راننده توسط انجمن غیرفعال شده است. برای پیگیری با انجمن تماس بگیرید.',
            ], 403);
        }

        return $next($request);
    }
}
