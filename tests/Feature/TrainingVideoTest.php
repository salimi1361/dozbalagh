<?php

namespace Tests\Feature;

use App\Models\TrainingVideo;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class TrainingVideoTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_cards_link_to_training_page(): void
    {
        $this->get('/login')
            ->assertOk()
            ->assertSee(route('training.index'), false);
    }

    public function test_public_training_page_only_shows_active_videos(): void
    {
        TrainingVideo::query()->create([
            'title' => 'آموزش فعال',
            'storage_path' => 'training-videos/active.mp4',
            'original_name' => 'active.mp4',
            'mime_type' => 'video/mp4',
            'size_bytes' => 1024,
            'is_active' => true,
        ]);
        TrainingVideo::query()->create([
            'title' => 'آموزش غیرفعال',
            'storage_path' => 'training-videos/inactive.mp4',
            'original_name' => 'inactive.mp4',
            'mime_type' => 'video/mp4',
            'size_bytes' => 1024,
            'is_active' => false,
        ]);

        $this->get(route('training.index'))
            ->assertOk()
            ->assertSee('آموزش فعال')
            ->assertDontSee('آموزش غیرفعال');
    }

    public function test_admin_can_upload_a_training_video(): void
    {
        Storage::fake('public');
        $admin = $this->adminUser();

        $response = $this->actingAs($admin)->post(route('admin.training.store'), [
            'title' => 'آموزش ثبت درخواست',
            'description' => 'مراحل ثبت درخواست در سامانه',
            'sort_order' => 1,
            'is_active' => '1',
            'video' => UploadedFile::fake()->create('request.mp4', 1024, 'video/mp4'),
        ]);

        $response->assertRedirect()->assertSessionHasNoErrors();
        $video = TrainingVideo::query()->firstOrFail();

        $this->assertSame('آموزش ثبت درخواست', $video->title);
        Storage::disk('public')->assertExists($video->storage_path);
    }

    public function test_non_admin_cannot_manage_training_videos(): void
    {
        $role = Role::firstOrCreate(['name' => 'company'], ['title_fa' => 'شرکت']);
        $user = User::create([
            'role_id' => $role->id,
            'username' => 'training-company',
            'password' => Hash::make('secret-password'),
            'status' => 'active',
            'is_manual' => true,
        ]);

        $this->actingAs($user)
            ->get(route('admin.training.index'))
            ->assertForbidden();
    }

    private function adminUser(): User
    {
        $role = Role::firstOrCreate(['name' => 'admin'], ['title_fa' => 'مدیر']);

        return User::create([
            'role_id' => $role->id,
            'username' => 'training-admin',
            'password' => Hash::make('secret-password'),
            'status' => 'active',
            'is_manual' => true,
        ]);
    }
}
