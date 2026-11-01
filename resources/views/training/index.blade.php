<!doctype html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>آموزش سامانه | سامانه جامع حمل و نقل بین‌المللی</title>
    <style>
        @font-face {
            font-family: VazirmatnLocal;
            src: url('{{ asset('assets/fonts/Vazirmatn-Regular.woff2') }}') format('woff2');
            font-style: normal;
            font-weight: 400;
            font-display: swap;
        }

        @font-face {
            font-family: VazirmatnLocal;
            src: url('{{ asset('assets/fonts/Vazirmatn-Bold.woff2') }}') format('woff2');
            font-style: normal;
            font-weight: 700;
            font-display: swap;
        }

        :root {
            color-scheme: dark;
            font-family: VazirmatnLocal, Tahoma, sans-serif;
            background: #071426;
            color: #e8f1fb;
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            min-height: 100vh;
            background: radial-gradient(circle at 15% 10%, #163b5a 0, transparent 35%), #071426;
        }

        .shell { width: min(1120px, calc(100% - 32px)); margin: 0 auto; padding: 32px 0 56px; }
        .topbar { display: flex; align-items: center; justify-content: space-between; gap: 16px; margin-bottom: 34px; }
        .eyebrow { color: #fbbf24; font-size: 13px; font-weight: 700; margin-bottom: 8px; }
        h1 { margin: 0; font-size: clamp(24px, 4vw, 38px); line-height: 1.35; }
        .intro { color: #a8bfd5; margin: 10px 0 0; line-height: 1.9; }
        .back-link { color: #dbeafe; border: 1px solid rgba(255,255,255,.22); border-radius: 12px; padding: 10px 16px; text-decoration: none; white-space: nowrap; }
        .back-link:hover { background: rgba(255,255,255,.1); }
        .grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px; }
        .card { overflow: hidden; border: 1px solid rgba(255,255,255,.13); border-radius: 18px; background: linear-gradient(145deg, rgba(255,255,255,.12), rgba(255,255,255,.04)); box-shadow: 0 18px 45px rgba(0,0,0,.2); }
        video { display: block; width: 100%; aspect-ratio: 16 / 9; background: #020817; object-fit: contain; }
        .card-body { padding: 18px; }
        .card-title { margin: 0; color: #f8c64c; font-size: 18px; font-weight: 700; }
        .card-description { color: #c4d4e4; line-height: 1.9; margin: 10px 0 0; white-space: pre-line; }
        .empty { border: 1px dashed rgba(255,255,255,.25); border-radius: 18px; padding: 36px; text-align: center; color: #b8cadb; }
        @media (max-width: 640px) {
            .shell { width: min(100% - 20px, 1120px); padding-top: 20px; }
            .topbar { align-items: flex-start; flex-direction: column; }
            .back-link { align-self: stretch; text-align: center; }
        }
    </style>
</head>
<body>
    <main class="shell">
        <div class="topbar">
            <div>
                <div class="eyebrow">راهنمای استفاده از سامانه</div>
                <h1>آموزش سامانه</h1>
                <p class="intro">ویدئوی موردنظر را انتخاب کنید تا مراحل استفاده از امکانات سامانه را ببینید.</p>
            </div>
            <a class="back-link" href="{{ route('login') }}">بازگشت به صفحه ورود</a>
        </div>

        @if ($videos->isEmpty())
            <div class="empty">ویدئوی آموزشی هنوز بارگذاری نشده است.</div>
        @else
            <div class="grid">
                @foreach ($videos as $video)
                    <article class="card">
                        <video controls preload="metadata" playsinline src="{{ asset('storage/' . ltrim($video->storage_path, '/')) }}">
                            مرورگر شما از پخش ویدئو پشتیبانی نمی‌کند.
                        </video>
                        <div class="card-body">
                            <h2 class="card-title">{{ $video->title }}</h2>
                            @if ($video->description)
                                <p class="card-description">{{ $video->description }}</p>
                            @endif
                        </div>
                    </article>
                @endforeach
            </div>
        @endif
    </main>
</body>
</html>
