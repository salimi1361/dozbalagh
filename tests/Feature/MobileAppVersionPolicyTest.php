<?php

namespace Tests\Feature;

use App\Models\MobileAppVersion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MobileAppVersionPolicyTest extends TestCase
{
    use RefreshDatabase;

    public function test_version_api_requires_update_below_minimum_build(): void
    {
        MobileAppVersion::create([
            'platform' => 'android',
            'version_name' => '2.1.0',
            'latest_build' => 21,
            'minimum_build' => 18,
            'download_url' => 'https://example.test/app.apk',
            'is_active' => true,
        ]);

        $this->getJson('/api/v1/mobile-app/version?platform=android&build=17')
            ->assertOk()
            ->assertJson([
                'configured' => true,
                'update_available' => true,
                'update_required' => true,
                'latest_build' => 21,
                'minimum_build' => 18,
            ]);
    }

    public function test_optional_update_does_not_block_supported_build(): void
    {
        MobileAppVersion::create([
            'platform' => 'android',
            'version_name' => '2.1.0',
            'latest_build' => 21,
            'minimum_build' => 18,
            'force_update' => false,
            'download_url' => 'https://example.test/app.apk',
            'is_active' => true,
        ]);

        $this->getJson('/api/v1/mobile-app/version?platform=android&build=19')
            ->assertOk()
            ->assertJson([
                'update_available' => true,
                'update_required' => false,
            ]);
    }
}
