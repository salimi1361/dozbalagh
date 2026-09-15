<div
    id="assistantWidget"
    class="assistant-widget"
    dir="rtl"
    data-endpoint="{{ route('assistant.message') }}"
    data-csrf="{{ csrf_token() }}"
>
    <section
        id="assistantPanel"
        class="assistant-panel"
        role="dialog"
        aria-labelledby="assistantTitle"
        aria-hidden="true"
    >
        <header class="assistant-header">
            <div>
                <strong id="assistantTitle">دستیار راهنمای دوزوله</strong>
                <span>پاسخ‌گوی راهنمای سامانه</span>
            </div>
            <button type="button" class="assistant-close" aria-label="بستن دستیار" data-assistant-close>
                <svg viewBox="0 0 24 24" aria-hidden="true">
                    <path d="M6 6l12 12M18 6 6 18"/>
                </svg>
            </button>
        </header>

        <div id="assistantMessages" class="assistant-messages" aria-live="polite">
            <div class="assistant-message assistant-message--bot">
                سلام. درباره ثبت درخواست، تمدید، رد درخواست، شماره سریال، کیف پول یا وضعیت درخواست سؤال کنید.
            </div>
        </div>

        <div id="assistantStatus" class="assistant-status" role="status"></div>

        <form id="assistantForm" class="assistant-form">
            <label for="assistantInput" class="assistant-sr-only">پرسش شما</label>
            <textarea
                id="assistantInput"
                name="message"
                rows="2"
                maxlength="{{ (int) config('assistant.max_message_length', 1000) }}"
                placeholder="پرسش خود را بنویسید…"
                required
            ></textarea>
            <button type="submit" aria-label="ارسال پرسش">
                <svg viewBox="0 0 24 24" aria-hidden="true">
                    <path d="M21 3 10 14M21 3l-7 18-4-7-7-4 18-7z"/>
                </svg>
            </button>
        </form>

        <p class="assistant-notice">این دستیار فقط راهنمایی می‌کند و هیچ عملیاتی در سامانه انجام نمی‌دهد.</p>
    </section>

    <button
        type="button"
        class="assistant-launcher"
        aria-label="باز کردن دستیار راهنما"
        aria-controls="assistantPanel"
        aria-expanded="false"
        data-assistant-open
    >
        <svg viewBox="0 0 24 24" aria-hidden="true">
            <path d="M4 5.5A2.5 2.5 0 0 1 6.5 3h11A2.5 2.5 0 0 1 20 5.5v8a2.5 2.5 0 0 1-2.5 2.5H11l-5 4v-4.1A2.5 2.5 0 0 1 4 13.5z"/>
            <path d="M8 8h8M8 12h5"/>
        </svg>
        <span>دستیار</span>
    </button>
</div>

<style>
    .assistant-widget { position: fixed; left: 1.25rem; bottom: 1.25rem; z-index: 70; font-family: 'Vazirmatn', Tahoma, sans-serif; }
    .assistant-launcher { display: flex; align-items: center; gap: .55rem; border: 0; border-radius: 999px; padding: .8rem 1rem; background: #047857; color: #fff; box-shadow: 0 12px 30px rgba(15, 23, 42, .25); font-size: .85rem; font-weight: 900; cursor: pointer; }
    .assistant-launcher:hover { background: #065f46; }
    .assistant-launcher:focus-visible, .assistant-close:focus-visible, .assistant-form button:focus-visible, .assistant-form textarea:focus-visible { outline: 3px solid rgba(16, 185, 129, .35); outline-offset: 2px; }
    .assistant-launcher svg { width: 1.35rem; height: 1.35rem; fill: none; stroke: currentColor; stroke-linecap: round; stroke-linejoin: round; stroke-width: 1.8; }
    .assistant-panel { display: none; flex-direction: column; width: min(24rem, calc(100vw - 2rem)); height: min(36rem, calc(100vh - 6.5rem)); margin-bottom: .75rem; overflow: hidden; border: 1px solid #dbe4ee; border-radius: 1.25rem; background: #fff; box-shadow: 0 20px 55px rgba(15, 23, 42, .28); }
    .assistant-panel.is-open { display: flex; }
    .assistant-header { display: flex; align-items: center; justify-content: space-between; padding: 1rem 1.1rem; background: #0f172a; color: #fff; }
    .assistant-header strong, .assistant-header span { display: block; }
    .assistant-header strong { font-size: .95rem; font-weight: 900; }
    .assistant-header span { margin-top: .2rem; color: #94a3b8; font-size: .7rem; }
    .assistant-close { display: grid; width: 2rem; height: 2rem; place-items: center; border: 0; border-radius: .65rem; background: rgba(255,255,255,.09); color: #fff; cursor: pointer; }
    .assistant-close svg { width: 1.1rem; height: 1.1rem; fill: none; stroke: currentColor; stroke-linecap: round; stroke-width: 2; }
    .assistant-messages { flex: 1; overflow-y: auto; padding: 1rem; background: #f8fafc; }
    .assistant-message { width: fit-content; max-width: 88%; margin-bottom: .7rem; padding: .7rem .85rem; border-radius: 1rem; font-size: .8rem; line-height: 1.9; white-space: pre-wrap; overflow-wrap: anywhere; }
    .assistant-message--bot { margin-left: auto; border-bottom-right-radius: .3rem; background: #fff; color: #334155; box-shadow: 0 1px 3px rgba(15,23,42,.08); }
    .assistant-message--user { margin-right: auto; border-bottom-left-radius: .3rem; background: #047857; color: #fff; }
    .assistant-status { min-height: 1.6rem; padding: .2rem 1rem; background: #fff; color: #64748b; font-size: .7rem; }
    .assistant-status.is-error { color: #be123c; }
    .assistant-form { display: flex; align-items: flex-end; gap: .5rem; padding: .75rem; border-top: 1px solid #e2e8f0; background: #fff; }
    .assistant-form textarea { flex: 1; min-height: 2.8rem; max-height: 7rem; resize: vertical; border: 1px solid #cbd5e1; border-radius: .85rem; padding: .65rem .75rem; background: #fff; color: #0f172a; font: inherit; font-size: .8rem; line-height: 1.7; }
    .assistant-form button { display: grid; flex: 0 0 2.8rem; width: 2.8rem; height: 2.8rem; place-items: center; border: 0; border-radius: .85rem; background: #047857; color: #fff; cursor: pointer; }
    .assistant-form button:disabled { cursor: wait; opacity: .55; }
    .assistant-form button svg { width: 1.2rem; height: 1.2rem; fill: none; stroke: currentColor; stroke-linecap: round; stroke-linejoin: round; stroke-width: 1.8; transform: scaleX(-1); }
    .assistant-notice { margin: 0; padding: 0 .9rem .75rem; background: #fff; color: #94a3b8; text-align: center; font-size: .62rem; }
    .assistant-sr-only { position: absolute; width: 1px; height: 1px; margin: -1px; overflow: hidden; clip: rect(0, 0, 0, 0); white-space: nowrap; }
    @media (max-width: 640px) {
        .assistant-widget { right: 1rem; left: 1rem; bottom: 1rem; }
        .assistant-panel { width: 100%; height: min(32rem, calc(100vh - 6rem)); }
        .assistant-launcher { margin-right: auto; }
    }
</style>

<script>
    (() => {
        const widget = document.getElementById('assistantWidget');
        if (!widget || widget.dataset.initialized === 'true') return;
        widget.dataset.initialized = 'true';

        const panel = document.getElementById('assistantPanel');
        const launcher = widget.querySelector('[data-assistant-open]');
        const closer = widget.querySelector('[data-assistant-close]');
        const form = document.getElementById('assistantForm');
        const input = document.getElementById('assistantInput');
        const messages = document.getElementById('assistantMessages');
        const status = document.getElementById('assistantStatus');
        const submit = form.querySelector('button[type="submit"]');

        const setOpen = (open) => {
            panel.classList.toggle('is-open', open);
            panel.setAttribute('aria-hidden', String(!open));
            launcher.setAttribute('aria-expanded', String(open));
            if (open) window.setTimeout(() => input.focus(), 50);
        };

        const addMessage = (text, type) => {
            const message = document.createElement('div');
            message.className = `assistant-message assistant-message--${type}`;
            message.textContent = text;
            messages.appendChild(message);
            messages.scrollTop = messages.scrollHeight;
        };

        launcher.addEventListener('click', () => setOpen(!panel.classList.contains('is-open')));
        closer.addEventListener('click', () => setOpen(false));
        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape' && panel.classList.contains('is-open')) setOpen(false);
        });
        input.addEventListener('keydown', (event) => {
            if (event.key === 'Enter' && !event.shiftKey) {
                event.preventDefault();
                form.requestSubmit();
            }
        });

        form.addEventListener('submit', async (event) => {
            event.preventDefault();
            const text = input.value.trim();
            if (!text || submit.disabled) return;

            addMessage(text, 'user');
            input.value = '';
            submit.disabled = true;
            status.classList.remove('is-error');
            status.textContent = 'در حال بررسی راهنما…';

            try {
                const response = await fetch(widget.dataset.endpoint, {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': widget.dataset.csrf,
                    },
                    body: JSON.stringify({ message: text }),
                });
                const payload = await response.json().catch(() => ({}));
                if (!response.ok || typeof payload.answer !== 'string') {
                    throw new Error(response.status === 419 ? 'نشست شما منقضی شده است؛ صفحه را تازه‌سازی کنید.' : 'پاسخ دستیار دریافت نشد. دوباره تلاش کنید.');
                }
                addMessage(payload.answer, 'bot');
                status.textContent = '';
            } catch (error) {
                status.classList.add('is-error');
                status.textContent = error instanceof Error ? error.message : 'ارتباط با دستیار برقرار نشد.';
            } finally {
                submit.disabled = false;
                input.focus();
            }
        });
    })();
</script>
