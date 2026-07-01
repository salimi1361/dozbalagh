<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ورود به سامانه دوزبلاغ</title>
    
    <script src="{{ asset('assets/js/tailwindcss.js') }}"></script>
    
    <style>
        @font-face {
            font-family: 'Vazirmatn';
            src: url('{{ asset('assets/fonts/Vazirmatn-Regular.woff2') }}') format('woff2');
            font-weight: 400;
        }
        @font-face {
            font-family: 'Vazirmatn';
            src: url('{{ asset('assets/fonts/Vazirmatn-Bold.woff2') }}') format('woff2');
            font-weight: 700;
        }
        body { font-family: 'Vazirmatn', sans-serif; }
        .dashed-border-bottom { border-bottom: 1px dashed rgba(255, 255, 255, 0.5); }
    </style>
</head>

<body class="min-h-screen flex flex-col items-center justify-center relative bg-slate-900" 
      style="background-image: url('{{ asset('images/bg-truck.jpg') }}'); background-size: cover; background-position: center;">
    
    <div class="absolute inset-0 bg-black/50 backdrop-blur-md z-0"></div>

    <div class="relative z-10 w-full max-w-md p-8 bg-zinc-900/70 backdrop-blur-lg rounded-2xl border border-white/10 shadow-2xl">
        
        <div class="text-center mb-8">
            <div class="flex justify-center mb-4">
                <img src="{{ asset('images/logo1.png') }}" alt="لوگو" class="h-25 w-auto drop-shadow-xl">
            </div>
            <h1 class="text-xl font-bold text-amber-400">سامانه جامع دوزوله</h1>
            <p class="text-sm text-white font-semibold mt-2">انجمن بین المللی خراسان رضوی</p>
        </div>

        @if(session('success'))
            <div class="bg-emerald-500/20 text-emerald-300 p-3 rounded-lg mb-6 text-sm font-bold border border-emerald-500/30">
                {{ session('success') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="bg-rose-500/20 text-rose-300 p-3 rounded-lg mb-6 text-sm font-bold border border-rose-500/30">
                {{ $errors->first() }}
            </div>
        @endif

        <form action="{{ route('login') }}" method="POST" class="space-y-5" id="loginForm">
            @csrf
            
            <div class="flex items-center gap-4">
                <label class="w-1/4 text-sm font-bold text-white text-right">نام کاربری<span class="text-rose-500 mr-1">*</span></label>
                <input type="text" name="username" value="{{ old('username') }}" required autofocus 
                       maxlength="11"
                       oninput="this.value = this.value.replace(/[^a-zA-Z0-9]/g, '')"
                       class="w-3/4 px-3 py-2 border-none rounded-md focus:ring-2 focus:ring-sky-500 focus:outline-none text-left font-mono text-slate-800 bg-white placeholder-slate-400 text-sm" 
                       placeholder="شناسه ملی شرکت">
            </div>

            <div class="flex items-center gap-4">
                <label class="w-1/4 text-sm font-bold text-white text-right">رمز عبور<span class="text-rose-500 mr-1">*</span></label>
                <div class="w-3/4 relative">
                    <input type="password" id="passwordInput" name="password" required 
                           class="w-full pl-10 pr-3 py-2 border-none rounded-md focus:ring-2 focus:ring-sky-500 focus:outline-none text-left font-mono text-slate-800 bg-white placeholder-slate-400 text-sm" 
                           placeholder="********">
                    
                    <button type="button" onclick="togglePassword()" class="absolute inset-y-0 left-0 pl-3 flex items-center text-slate-400 hover:text-sky-600 transition-colors">
                        <svg id="eyeIcon" class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                        </svg>
                    </button>
                </div>
            </div>

            <div class="flex items-center gap-4">
                <label class="w-1/4 text-sm font-bold text-white text-right">کد امنیتی<span class="text-rose-500 mr-1">*</span></label>
                <div class="w-3/4 flex gap-2">
                    <input type="text" id="captchaInput" class="w-1/3 px-2 py-2 border-none rounded-md text-center text-sm font-mono text-slate-800 focus:outline-none focus:ring-2 focus:ring-sky-500" required placeholder="کد" autocomplete="off" maxlength="4">
                    
                    <div class="w-1/3 bg-white rounded-md overflow-hidden flex items-center justify-center cursor-pointer" onclick="generateCaptcha()" title="تولید کد جدید">
                        <canvas id="captchaCanvas" width="80" height="36"></canvas>
                    </div>
                    
                    <button type="button" onclick="generateCaptcha()" class="w-1/3 bg-sky-500 hover:bg-sky-600 text-white rounded-md flex items-center justify-center transition-colors shadow-md">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                    </button>
                </div>
            </div>
            
            <div id="captchaError" class="hidden text-rose-400 text-xs text-center font-bold">کد امنیتی وارد شده اشتباه است.</div>

            <div class="pt-4">
                <button type="submit" class="w-full bg-sky-500 hover:bg-sky-600 text-white font-bold py-3 px-4 rounded-md transition-colors text-sm shadow-lg">
                    ورود به سامانه
                </button>
            </div>

            <div class="text-center mt-4">
                <a href="#" class="text-white text-xs hover:text-amber-400 transition-colors dashed-border-bottom pb-1 inline-flex items-center gap-1">
                    رمز عبور خود را فراموش کرده ام
                </a>
            </div>

            <div class="pt-2 flex justify-center">
                <div class="bg-white/5 backdrop-blur-sm p-1.5 rounded-xl border border-white/10 shadow-md transition-all duration-300 hover:bg-white/15 hover:scale-105 group">
                    <a referrerpolicy='origin' target='_blank' href='https://trustseal.enamad.ir/?id=6548908&Code=QkDQcJ60ZPdYBzZfCaTLExBWWZC8wrZJ' class="block">
                        <img referrerpolicy='origin' src='https://trustseal.enamad.ir/logo.aspx?id=6548908&Code=QkDQcJ60ZPdYBzZfCaTLExBWWZC8wrZJ' alt='اینماد دوزبلاغ' class="w-14 h-14 object-contain opacity-75 group-hover:opacity-100 transition-opacity" style='cursor:pointer' code='QkDQcJ60ZPdYBzZfCaTLExBWWZC8wrZJ'>
                    </a>
                </div>
            </div>
        </form>
    </div>

    <script>
        function togglePassword() {
            const passwordInput = document.getElementById('passwordInput');
            const eyeIcon = document.getElementById('eyeIcon');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                eyeIcon.innerHTML = `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"></path>`;
            } else {
                passwordInput.type = 'password';
                eyeIcon.innerHTML = `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>`;
            }
        }

        let captchaCode = "";
        function generateCaptcha() {
            const canvas = document.getElementById('captchaCanvas');
            const ctx = canvas.getContext('2d');
            ctx.clearRect(0, 0, canvas.width, canvas.height);
            captchaCode = Math.floor(1000 + Math.random() * 9000).toString();
            ctx.font = 'bold 20px monospace';
            ctx.fillStyle = '#334155'; 
            ctx.textAlign = 'center';
            ctx.textBaseline = 'middle';
            ctx.fillText(captchaCode, canvas.width/2, canvas.height/2);
            for(let i = 0; i < 5; i++) {
                ctx.beginPath();
                ctx.moveTo(Math.random() * canvas.width, Math.random() * canvas.height);
                ctx.lineTo(Math.random() * canvas.width, Math.random() * canvas.height);
                ctx.strokeStyle = '#94a3b8';
                ctx.lineWidth = 1;
                ctx.stroke();
            }
        }

        window.onload = generateCaptcha;

        document.getElementById('loginForm').addEventListener('submit', function(e) {
            const inputVal = document.getElementById('captchaInput').value;
            if(inputVal !== captchaCode) {
                e.preventDefault();
                document.getElementById('captchaError').classList.remove('hidden');
                generateCaptcha();
                document.getElementById('captchaInput').value = '';
                document.getElementById('captchaInput').focus();
            } else {
                document.getElementById('captchaError').classList.add('hidden');
            }
        });
    </script>
</body>
</html>