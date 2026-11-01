@extends('layouts.admin')

@section('title', 'آموزش سامانه')
@section('header_title', 'آموزش سامانه')
@section('hero_subtitle', 'بارگذاری و مدیریت ویدئوهای آموزشی صفحه ورود')

@section('content')
    <div class="space-y-6">
        @if (session('success'))
            <div class="rounded-xl border border-emerald-300/30 bg-emerald-500/10 px-4 py-3 text-sm text-emerald-200">
                {{ session('success') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="rounded-xl border border-rose-300/30 bg-rose-500/10 px-4 py-3 text-sm text-rose-100">
                <div class="font-bold">اطلاعات واردشده نیاز به بررسی دارد:</div>
                <ul class="mt-2 list-inside list-disc space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <section class="rounded-2xl border border-slate-200/10 bg-slate-900/50 p-5 shadow-xl">
            <div class="mb-5">
                <h2 class="text-lg font-bold text-white">افزودن ویدئوی آموزشی</h2>
                <p class="mt-1 text-sm text-slate-400">فقط فایل‌های MP4، WebM یا OGG تا حجم ۲۵۰ مگابایت پذیرفته می‌شوند.</p>
            </div>

            <form action="{{ route('admin.training.store') }}" method="POST" enctype="multipart/form-data" class="grid gap-4 md:grid-cols-2">
                @csrf
                <div>
                    <label for="title" class="mb-2 block text-sm font-semibold text-slate-200">عنوان ویدئو</label>
                    <input id="title" name="title" required maxlength="150" value="{{ old('title') }}" class="w-full rounded-xl border border-slate-200/15 bg-slate-950/50 px-3 py-2.5 text-sm text-white outline-none focus:border-sky-400" placeholder="مثلاً: آموزش ثبت درخواست">
                </div>
                <div>
                    <label for="video" class="mb-2 block text-sm font-semibold text-slate-200">فایل ویدئو</label>
                    <input id="video" name="video" required type="file" accept="video/mp4,video/webm,video/ogg" class="w-full rounded-xl border border-slate-200/15 bg-slate-950/50 px-3 py-2 text-sm text-slate-200 file:ml-3 file:rounded-lg file:border-0 file:bg-sky-600 file:px-3 file:py-2 file:text-white">
                </div>
                <div>
                    <label for="sort_order" class="mb-2 block text-sm font-semibold text-slate-200">ترتیب نمایش</label>
                    <input id="sort_order" name="sort_order" type="number" min="0" max="9999" value="{{ old('sort_order', 0) }}" class="w-full rounded-xl border border-slate-200/15 bg-slate-950/50 px-3 py-2.5 text-sm text-white outline-none focus:border-sky-400">
                </div>
                <div>
                    <label for="description" class="mb-2 block text-sm font-semibold text-slate-200">توضیح کوتاه</label>
                    <input id="description" name="description" maxlength="2000" value="{{ old('description') }}" class="w-full rounded-xl border border-slate-200/15 bg-slate-950/50 px-3 py-2.5 text-sm text-white outline-none focus:border-sky-400" placeholder="اختیاری">
                </div>
                <div class="flex items-center gap-2 md:col-span-2">
                    <input id="is_active" name="is_active" value="1" type="checkbox" checked class="h-4 w-4 rounded border-slate-400 bg-slate-900 text-sky-500">
                    <label for="is_active" class="text-sm text-slate-200">در صفحه آموزش نمایش داده شود</label>
                </div>
                <div class="md:col-span-2">
                    <button type="submit" class="rounded-xl bg-sky-600 px-5 py-2.5 text-sm font-bold text-white shadow-lg shadow-sky-900/30 transition hover:bg-sky-500">بارگذاری ویدئو</button>
                </div>
            </form>
        </section>

        <section class="space-y-4">
            <div class="flex items-center justify-between gap-3">
                <h2 class="text-lg font-bold text-white">ویدئوهای موجود</h2>
                <a href="{{ route('training.index') }}" target="_blank" rel="noopener" class="text-sm text-sky-300 hover:text-sky-200">مشاهده صفحه آموزش</a>
            </div>

            @forelse ($videos as $video)
                <article class="grid gap-5 rounded-2xl border border-slate-200/10 bg-slate-900/50 p-5 lg:grid-cols-[280px_1fr]">
                    <div>
                        <video controls preload="metadata" playsinline class="aspect-video w-full rounded-xl bg-black" src="{{ asset('storage/' . ltrim($video->storage_path, '/')) }}">
                            مرورگر شما از پخش ویدئو پشتیبانی نمی‌کند.
                        </video>
                        <div class="mt-2 truncate text-xs text-slate-500" title="{{ $video->original_name }}">{{ $video->original_name }}</div>
                    </div>

                    <div>
                        <form action="{{ route('admin.training.update', $video) }}" method="POST" enctype="multipart/form-data" class="grid gap-4 md:grid-cols-2">
                            @csrf
                            @method('PUT')
                            <div>
                                <label class="mb-2 block text-sm font-semibold text-slate-200">عنوان</label>
                                <input name="title" required maxlength="150" value="{{ $video->title }}" class="w-full rounded-xl border border-slate-200/15 bg-slate-950/50 px-3 py-2.5 text-sm text-white outline-none focus:border-sky-400">
                            </div>
                            <div>
                                <label class="mb-2 block text-sm font-semibold text-slate-200">ترتیب نمایش</label>
                                <input name="sort_order" type="number" min="0" max="9999" value="{{ $video->sort_order }}" class="w-full rounded-xl border border-slate-200/15 bg-slate-950/50 px-3 py-2.5 text-sm text-white outline-none focus:border-sky-400">
                            </div>
                            <div class="md:col-span-2">
                                <label class="mb-2 block text-sm font-semibold text-slate-200">توضیح</label>
                                <textarea name="description" rows="2" maxlength="2000" class="w-full rounded-xl border border-slate-200/15 bg-slate-950/50 px-3 py-2.5 text-sm text-white outline-none focus:border-sky-400">{{ $video->description }}</textarea>
                            </div>
                            <div class="md:col-span-2">
                                <label class="mb-2 block text-sm font-semibold text-slate-200">جایگزینی فایل (اختیاری)</label>
                                <input name="video" type="file" accept="video/mp4,video/webm,video/ogg" class="w-full rounded-xl border border-slate-200/15 bg-slate-950/50 px-3 py-2 text-sm text-slate-200 file:ml-3 file:rounded-lg file:border-0 file:bg-slate-700 file:px-3 file:py-2 file:text-white">
                            </div>
                            <div class="flex items-center gap-2 md:col-span-2">
                                <input id="active-{{ $video->id }}" name="is_active" value="1" type="checkbox" @checked($video->is_active) class="h-4 w-4 rounded border-slate-400 bg-slate-900 text-sky-500">
                                <label for="active-{{ $video->id }}" class="text-sm text-slate-200">فعال و قابل نمایش برای کاربران</label>
                            </div>
                            <div class="flex flex-wrap gap-2 md:col-span-2">
                                <button type="submit" class="rounded-xl bg-sky-600 px-4 py-2 text-sm font-bold text-white transition hover:bg-sky-500">ذخیره تغییرات</button>
                            </div>
                        </form>

                        <form action="{{ route('admin.training.destroy', $video) }}" method="POST" class="mt-3" onsubmit="return confirm('این ویدئو حذف شود؟');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="rounded-xl border border-rose-300/30 px-4 py-2 text-sm font-bold text-rose-200 transition hover:bg-rose-500/10">حذف ویدئو</button>
                        </form>
                    </div>
                </article>
            @empty
                <div class="rounded-2xl border border-dashed border-slate-200/20 bg-slate-900/30 p-8 text-center text-sm text-slate-400">هنوز ویدئویی بارگذاری نشده است.</div>
            @endforelse
        </section>
    </div>
@endsection
