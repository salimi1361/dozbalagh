<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TrainingVideo;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Throwable;

class TrainingVideoController extends Controller
{
    private const STORAGE_DISK = 'public';

    private const STORAGE_DIRECTORY = 'training-videos';

    public function publicIndex(): View
    {
        $videos = TrainingVideo::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderByDesc('id')
            ->get();

        return view('training.index', compact('videos'));
    }

    public function index(): View
    {
        $videos = TrainingVideo::query()
            ->orderBy('sort_order')
            ->orderByDesc('id')
            ->get();

        return view('admin.training.index', compact('videos'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate($this->rules(true));
        $upload = $this->storeUploadedFile($request->file('video'));

        try {
            TrainingVideo::query()->create([
                'title' => $validated['title'],
                'description' => $validated['description'] ?? null,
                'storage_path' => $upload['storage_path'],
                'original_name' => $upload['original_name'],
                'mime_type' => $upload['mime_type'],
                'size_bytes' => $upload['size_bytes'],
                'sort_order' => $validated['sort_order'] ?? 0,
                'is_active' => $request->boolean('is_active'),
            ]);
        } catch (Throwable $exception) {
            Storage::disk(self::STORAGE_DISK)->delete($upload['storage_path']);
            throw $exception;
        }

        return back()->with('success', 'ویدئوی آموزشی با موفقیت اضافه شد.');
    }

    public function update(Request $request, TrainingVideo $trainingVideo): RedirectResponse
    {
        $validated = $request->validate($this->rules(false));
        $upload = null;

        if ($request->hasFile('video')) {
            $upload = $this->storeUploadedFile($request->file('video'));
        }

        $oldStoragePath = $trainingVideo->storage_path;

        try {
            $trainingVideo->fill([
                'title' => $validated['title'],
                'description' => $validated['description'] ?? null,
                'sort_order' => $validated['sort_order'] ?? 0,
                'is_active' => $request->boolean('is_active'),
            ]);

            if ($upload !== null) {
                $trainingVideo->fill($upload);
            }

            $trainingVideo->save();
        } catch (Throwable $exception) {
            if ($upload !== null) {
                Storage::disk(self::STORAGE_DISK)->delete($upload['storage_path']);
            }

            throw $exception;
        }

        if ($upload !== null && $oldStoragePath !== $trainingVideo->storage_path) {
            Storage::disk(self::STORAGE_DISK)->delete($oldStoragePath);
        }

        return back()->with('success', 'ویدئوی آموزشی با موفقیت ویرایش شد.');
    }

    public function destroy(TrainingVideo $trainingVideo): RedirectResponse
    {
        $storagePath = $trainingVideo->storage_path;
        $trainingVideo->delete();
        Storage::disk(self::STORAGE_DISK)->delete($storagePath);

        return back()->with('success', 'ویدئوی آموزشی حذف شد.');
    }

    private function rules(bool $fileRequired): array
    {
        return [
            'title' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:2000'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'is_active' => ['nullable', 'boolean'],
            'video' => [
                $fileRequired ? 'required' : 'nullable',
                'file',
                'mimes:mp4,webm,ogg',
                'mimetypes:video/mp4,video/webm,video/ogg',
                'max:256000',
            ],
        ];
    }

    /**
     * @return array{storage_path: string, original_name: string, mime_type: string, size_bytes: int}
     */
    private function storeUploadedFile(UploadedFile $file): array
    {
        $extension = strtolower($file->getClientOriginalExtension());
        $fileName = Str::uuid()->toString() . '.' . $extension;
        $storagePath = $file->storeAs(self::STORAGE_DIRECTORY, $fileName, self::STORAGE_DISK);

        if (! is_string($storagePath)) {
            throw new \RuntimeException('ذخیره‌سازی ویدئوی آموزشی انجام نشد.');
        }

        return [
            'storage_path' => $storagePath,
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType() ?: 'application/octet-stream',
            'size_bytes' => (int) $file->getSize(),
        ];
    }
}
