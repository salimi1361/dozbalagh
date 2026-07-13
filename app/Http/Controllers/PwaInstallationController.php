<?php

namespace App\Http\Controllers;

use App\Services\PwaInstallationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PwaInstallationController extends Controller
{
    public function store(Request $request, PwaInstallationService $service): JsonResponse
    {
        $user = $request->user();
        $service->record($request, 'user', $user->id, $user->role?->name ?? 'unknown');

        return response()->json(['ok' => true]);
    }
}
