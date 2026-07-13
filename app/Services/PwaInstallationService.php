<?php

namespace App\Services;

use App\Models\PwaInstallation;
use Illuminate\Http\Request;

class PwaInstallationService
{
    public function record(Request $request, string $actorType, int $actorId, string $role): PwaInstallation
    {
        $data = $request->validate([
            'device_uuid' => ['required', 'string', 'max:100'],
            'event' => ['required', 'in:seen,installed,standalone'],
            'platform' => ['nullable', 'string', 'max:255'],
            'browser' => ['nullable', 'string', 'max:255'],
            'device_type' => ['nullable', 'in:mobile,tablet,desktop,unknown'],
        ]);

        $now = now();
        $installation = PwaInstallation::firstOrNew([
            'actor_type' => $actorType,
            'actor_id' => $actorId,
            'device_uuid' => $data['device_uuid'],
        ]);

        if (! $installation->exists) {
            $installation->first_seen_at = $now;
        }

        $installation->fill([
            'role' => $role,
            'platform' => $data['platform'] ?? null,
            'browser' => $data['browser'] ?? null,
            'device_type' => $data['device_type'] ?? 'unknown',
            'user_agent' => mb_substr((string) $request->userAgent(), 0, 1000),
            'last_ip' => $request->ip(),
            'last_seen_at' => $now,
        ]);

        if ($data['event'] === 'installed') {
            $installation->is_installed = true;
            $installation->installed_at ??= $now;
        }
        if ($data['event'] === 'standalone') {
            $installation->is_installed = true;
            $installation->is_standalone = true;
            $installation->installed_at ??= $now;
        }

        $installation->save();

        return $installation;
    }
}
