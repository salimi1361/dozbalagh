import maplibregl from 'maplibre-gl';
import 'maplibre-gl/dist/maplibre-gl.css';

const root = document.getElementById('tracking-map-root');

if (root) {
    const dataUrl = root.dataset.url;
    const tileUrl = root.dataset.tiles;
    const tilesEnabled = root.dataset.tilesEnabled === '1';
    const outlineUrl = root.dataset.outline;
    const list = document.getElementById('tracking-list');
    const status = document.getElementById('tracking-refresh-status');
    const markers = new Map();
    let firstFit = true;

    const mapSources = {};
    const mapLayers = [
        { id: 'background', type: 'background', paint: { 'background-color': '#dce8ef' } },
    ];
    if (tilesEnabled) {
        mapSources.localTiles = { type: 'raster', tiles: [tileUrl], tileSize: 256, minzoom: 0, maxzoom: 19 };
        mapLayers.push({ id: 'local-map', type: 'raster', source: 'localTiles', minzoom: 0, maxzoom: 22 });
    }

    const map = new maplibregl.Map({
        container: 'tracking-map',
        center: [53.7, 32.4],
        zoom: 4.2,
        attributionControl: false,
        style: {
            version: 8,
            sources: mapSources,
            layers: mapLayers,
        },
    });

    map.addControl(new maplibregl.NavigationControl({ showCompass: true }), 'top-left');

    const escapeHtml = value => String(value ?? '').replace(/[&<>"']/g, char => ({
        '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;',
    }[char]));

    const faDate = value => value ? new Intl.DateTimeFormat('fa-IR', {
        dateStyle: 'short', timeStyle: 'medium',
    }).format(new Date(value)) : 'بدون داده';

    function lineFeature(track) {
        return {
            type: 'Feature',
            properties: { item_id: track.item_id },
            geometry: {
                type: 'LineString',
                coordinates: track.points.map(point => [point.longitude, point.latitude]),
            },
        };
    }

    const isValidPoint = point => point
        && Number.isFinite(Number(point.longitude))
        && Number.isFinite(Number(point.latitude))
        && Number(point.longitude) >= -180
        && Number(point.longitude) <= 180
        && Number(point.latitude) >= -90
        && Number(point.latitude) <= 90;

    function renderTracks(tracks, drivers) {
        const validTracks = tracks
            .map(track => ({
                ...track,
                points: Array.isArray(track.points) ? track.points.filter(isValidPoint) : [],
            }))
            .filter(track => isValidPoint(track.latest));
        const collection = { type: 'FeatureCollection', features: validTracks.filter(t => t.points.length > 1).map(lineFeature) };
        const source = map.getSource('tracking-lines');
        if (source) {
            source.setData(collection);
        } else {
            map.addSource('tracking-lines', { type: 'geojson', data: collection });
            map.addLayer({
                id: 'tracking-lines-layer',
                type: 'line',
                source: 'tracking-lines',
                paint: { 'line-color': '#0284c7', 'line-width': 4, 'line-opacity': 0.8 },
            });
        }

        const currentIds = new Set(validTracks.map(track => String(track.item_id)));
        markers.forEach((marker, id) => {
            if (!currentIds.has(id)) {
                marker.remove();
                markers.delete(id);
            }
        });

        validTracks.forEach(track => {
            const id = String(track.item_id);
            let marker = markers.get(id);
            if (!marker) {
                const element = document.createElement('button');
                element.className = 'tracking-marker';
                element.type = 'button';
                element.innerHTML = '<span></span>';
                marker = new maplibregl.Marker({ element })
                    .setPopup(new maplibregl.Popup({ offset: 20 }))
                    .addTo(map);
                markers.set(id, marker);
            }
            marker.setLngLat([track.latest.longitude, track.latest.latitude]);
            marker.getElement().classList.toggle('is-online', Boolean(track.is_online));
            marker.getPopup().setHTML(`
                <div dir="rtl" class="tracking-popup">
                    <strong>${escapeHtml(track.driver_name)}</strong>
                    <span>سریال: ${escapeHtml(track.serial_number || track.item_id)}</span>
                    <span>${escapeHtml(track.company_name || '')}</span>
                    <span>آخرین دریافت: ${escapeHtml(faDate(track.last_seen_at))}</span>
                </div>`);
        });

        list.innerHTML = drivers.length ? drivers.map(driver => {
            const driverTracks = validTracks.filter(track => Number(track.driver_id) === Number(driver.id));
            const track = driverTracks.sort((a, b) => new Date(b.last_seen_at) - new Date(a.last_seen_at))[0];
            const locationData = track ? `data-lng="${track.latest.longitude}" data-lat="${track.latest.latitude}"` : '';
            const state = driver.is_online ? 'آنلاین' : driver.has_location ? 'آفلاین' : 'ردیاب خاموش / بدون داده';
            return `
                <button type="button" class="tracking-card ${track ? '' : 'is-disabled'}" ${locationData}>
                    <span class="tracking-card__dot ${driver.is_online ? 'is-online' : ''}"></span>
                    <span><b>${escapeHtml(driver.name)}</b><small>${escapeHtml(driver.company_name || 'بدون شرکت')}</small></span>
                    <span><b>${escapeHtml(state)}</b><small>${escapeHtml(faDate(driver.last_seen_at))}</small></span>
                </button>`;
        }).join('') : '<div class="rounded-xl bg-slate-50 p-8 text-center font-bold text-slate-400">راننده‌ای در این محدوده دسترسی ثبت نشده است.</div>';

        list.querySelectorAll('.tracking-card').forEach(button => button.addEventListener('click', () => {
            if (button.dataset.lng && button.dataset.lat) {
                map.flyTo({ center: [Number(button.dataset.lng), Number(button.dataset.lat)], zoom: 13 });
            }
        }));

        if (firstFit && validTracks.length === 1) {
            map.flyTo({
                center: [Number(validTracks[0].latest.longitude), Number(validTracks[0].latest.latitude)],
                zoom: 13,
            });
            firstFit = false;
        } else if (firstFit && validTracks.length > 1) {
            const bounds = new maplibregl.LngLatBounds();
            validTracks.forEach(track => bounds.extend([
                Number(track.latest.longitude),
                Number(track.latest.latitude),
            ]));
            map.fitBounds(bounds, { padding: 70, maxZoom: 13 });
            firstFit = false;
        }
    }

    async function refresh() {
        try {
            const url = new URL(dataUrl, window.location.origin);
            url.searchParams.set('_', Date.now().toString());
            const response = await fetch(url, {
                credentials: 'same-origin',
                cache: 'no-store',
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });
            if (!response.ok) throw new Error(`HTTP ${response.status}`);

            const contentType = response.headers.get('content-type') || '';
            if (!contentType.includes('application/json')) {
                throw new Error('پاسخ سرور JSON نیست؛ احتمالاً نشست کاربری منقضی شده است');
            }

            const payload = await response.json();
            if (!Array.isArray(payload.tracks) || !Array.isArray(payload.drivers)) {
                throw new Error('ساختار پاسخ سرور معتبر نیست');
            }
            renderTracks(payload.tracks || [], payload.drivers || []);
            status.textContent = `به‌روزرسانی: ${faDate(payload.generated_at)}`;
        } catch (error) {
            status.textContent = `خطا در دریافت موقعیت‌ها (${error?.message || 'خطای ناشناخته'})`;
        }
    }

    map.on('load', () => {
        map.addSource('iran-outline', { type: 'geojson', data: outlineUrl });
        map.addLayer({ id: 'iran-fill', type: 'fill', source: 'iran-outline', paint: { 'fill-color': '#f8fafc', 'fill-opacity': tilesEnabled ? 0.04 : 0.92 } });
        map.addLayer({ id: 'iran-border', type: 'line', source: 'iran-outline', paint: { 'line-color': '#64748b', 'line-width': 2 } });
        refresh();
    });
    map.on('error', event => {
        if (event?.error?.message) status.textContent = `خطای نقشه: ${event.error.message}`;
    });
    setInterval(refresh, 15000);
}
