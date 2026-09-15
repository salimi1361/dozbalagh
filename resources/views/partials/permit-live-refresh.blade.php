@php($permitLatestEventId = (int) ($events->max('id') ?? 0))
<div id="permitLiveMarker" data-latest-event="{{ $permitLatestEventId }}"></div>
<button type="button" id="permitNewMessageNotice" class="fixed bottom-5 left-5 z-50 hidden rounded-2xl bg-slate-950 px-5 py-3 text-sm font-black text-white shadow-2xl">
    پیام جدید ثبت شده — مشاهده
</button>
<script>
(() => {
    const marker = document.getElementById('permitLiveMarker');
    const notice = document.getElementById('permitNewMessageNotice');
    if (!marker || !notice) return;

    let latestEvent = Number(marker.dataset.latestEvent || 0);
    let formIsDirty = false;
    document.querySelectorAll('form input, form textarea, form select').forEach(field => {
        field.addEventListener('input', () => { formIsDirty = true; }, {once: true});
        field.addEventListener('change', () => { formIsDirty = true; }, {once: true});
    });
    document.querySelectorAll('form').forEach(form => form.addEventListener('submit', () => {
        formIsDirty = false;
    }));
    notice.addEventListener('click', () => window.location.reload());

    async function checkForUpdates() {
        if (document.hidden) return;
        try {
            const response = await fetch(window.location.href, {
                headers: {'X-Requested-With': 'XMLHttpRequest', 'Accept': 'text/html'},
                cache: 'no-store',
            });
            if (!response.ok) return;
            const html = await response.text();
            const nextDocument = new DOMParser().parseFromString(html, 'text/html');
            const nextMarker = nextDocument.getElementById('permitLiveMarker');
            const nextEvent = Number(nextMarker?.dataset.latestEvent || 0);
            if (nextEvent > latestEvent) {
                latestEvent = nextEvent;
                if (formIsDirty) {
                    notice.classList.remove('hidden');
                } else {
                    window.location.reload();
                }
            }
        } catch (_) {
            // اختلال موقت شبکه نباید کار اپراتور را متوقف کند.
        }
    }

    window.setInterval(checkForUpdates, 10000);
})();
</script>
