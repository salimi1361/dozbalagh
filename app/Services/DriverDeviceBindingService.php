<?php

namespace App\Services;

use App\Exceptions\DriverDeviceBindingException;
use App\Models\Driver;
use App\Models\MobileAppInstallation;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class DriverDeviceBindingService
{
    private const FIELDS = [
        'device_uuid',
        'platform',
        'manufacturer',
        'model',
        'device_name',
        'os_version',
        'sdk_version',
        'app_version',
        'app_build',
        'app_identifier',
        'locale',
        'fcm_token',
        'notifications_enabled',
    ];

    /**
     * Bind one active installation to a driver before a session token is issued.
     * The driver row is locked so two first-time logins cannot win concurrently.
     */
    public function bind(
        Driver $driver,
        array $data,
        ?string $ip = null,
        bool $allowRevokedRebind = false,
    ): MobileAppInstallation {
        return DB::transaction(function () use ($driver, $data, $ip, $allowRevokedRebind): MobileAppInstallation {
            $lockedDriver = Driver::query()
                ->lockForUpdate()
                ->findOrFail($driver->getKey());
            $deviceUuid = trim((string) ($data['device_uuid'] ?? ''));

            $installation = MobileAppInstallation::query()
                ->where('driver_id', $lockedDriver->getKey())
                ->where('device_uuid', $deviceUuid)
                ->first();

            $activeInstallation = MobileAppInstallation::query()
                ->where('driver_id', $lockedDriver->getKey())
                ->where('device_uuid', $deviceUuid)
                ->whereNull('revoked_at')
                ->first();

            $otherActiveInstallation = MobileAppInstallation::query()
                ->where('driver_id', $lockedDriver->getKey())
                ->where('device_uuid', '!=', $deviceUuid)
                ->whereNull('revoked_at')
                ->exists();

            if ($otherActiveInstallation) {
                throw new DriverDeviceBindingException(DriverDeviceBindingException::ANOTHER_DEVICE);
            }

            if ($installation?->revoked_at && ! ($allowRevokedRebind && ! $activeInstallation)) {
                throw new DriverDeviceBindingException(DriverDeviceBindingException::REVOKED_DEVICE);
            }

            $now = now();
            if (! $installation) {
                $installation = new MobileAppInstallation([
                    'driver_id' => $lockedDriver->getKey(),
                    'device_uuid' => $deviceUuid,
                ]);
                $installation->installed_at = $now;
            } elseif ($installation->revoked_at) {
                // A reset invalidates the old session and PIN. A later OTP on
                // the same physical phone may bind it again as a new session.
                $installation->revoked_at = null;
                $installation->revoked_by_user_id = null;
                $installation->device_pin_hash = null;
                $installation->device_pin_updated_at = null;
                $installation->installed_at = $now;
            }

            $payload = Arr::only($data, self::FIELDS);
            if (array_key_exists('fcm_token', $payload)
                && $installation->fcm_token !== $payload['fcm_token']) {
                $installation->fcm_token_updated_at = $payload['fcm_token'] ? $now : null;
            }

            $installation->fill($payload);
            $installation->last_ip = $ip;
            $installation->last_seen_at = $now;
            $installation->save();

            return $installation->fresh();
        });
    }
}
