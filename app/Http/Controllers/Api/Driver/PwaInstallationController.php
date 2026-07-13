<?php

namespace App\Http\Controllers\Api\Driver;

use App\Http\Controllers\Controller;
use App\Services\PwaInstallationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PwaInstallationController extends Controller
{
    public function store(Request $request, PwaInstallationService $service): JsonResponse
    {
        $driver = $request->user();
        $service->record($request, 'driver', $driver->id, 'driver');

        return response()->json(['ok' => true]);
    }
}
