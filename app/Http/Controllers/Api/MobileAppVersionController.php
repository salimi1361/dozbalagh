<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MobileAppVersion;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MobileAppVersionController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'platform' => ['required', 'in:android,ios'],
            'build' => ['nullable', 'integer', 'min:0'],
        ]);
        $version = MobileAppVersion::query()
            ->where('platform', $validated['platform'])
            ->where('is_active', true)
            ->first();

        if (! $version) {
            return response()->json(['status' => 'success', 'configured' => false]);
        }

        $currentBuild = (int) ($validated['build'] ?? 0);
        $updateAvailable = $currentBuild > 0 && $currentBuild < $version->latest_build;
        $belowMinimum = $currentBuild > 0 && $currentBuild < $version->minimum_build;

        return response()->json([
            'status' => 'success',
            'configured' => true,
            'platform' => $version->platform,
            'latest_version' => $version->version_name,
            'latest_build' => $version->latest_build,
            'minimum_build' => $version->minimum_build,
            'update_available' => $updateAvailable,
            'update_required' => $belowMinimum || ($version->force_update && $updateAvailable),
            'force_update' => $version->force_update,
            'maintenance_mode' => $version->maintenance_mode,
            'download_url' => $version->download_url,
            'file_checksum' => $version->file_checksum,
            'message' => $version->message,
            'release_notes' => $version->release_notes,
            'published_at' => optional($version->published_at)->toIso8601String(),
        ]);
    }
}
