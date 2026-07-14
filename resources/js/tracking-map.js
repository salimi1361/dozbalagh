import maplibregl from 'maplibre-gl';
import 'maplibre-gl/dist/maplibre-gl.css';

const root = document.getElementById('tracking-map-root');

if (root) {
    const dataUrl = root.dataset.url;
    const tileUrl = root.dataset.tiles;
    const list = document.getElementById('tracking-list');
    const status = document.getElementById('tracking-refresh-status');
    const markers = new Map();
    let firstFit = true;

    const map = new maplibregl.Map({
        container: 'tracking-map',
        center: [53.7, 32.4],
        zoom: 4.2,
        attributionControl: false,
        style: {
            version: 8,
            sources: {
                localTiles: {
                    type: 'raster',
                    tiles: [tileUrl],
                    tileSize: 256,
                    minzoom: 0,
                    maxzoom: 14,
                },
            },
            layers: [
                { id: 'background', type: 'background', paint: { 'background-color': '#e2e8f0' } },
                { id: 'local-map', type: 'raster', source: 'localTiles', minzoom: 0, maxzoom: 22 },
            ],
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

    function renderTracks(tracks) {
        const collection = { type: 'FeatureCollection', features: tracks.filter(t => t.points.length > 1).map(lineFeature) };
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

        const currentIds = new Set(tracks.map(track => String(track.item_id)));
        markers.forEach((marker, id) => {
            if (!currentIds.has(id)) {
                marker.remove();
                markers.delete(id);
            }
        });

        tracks.forEach(track => {
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

        list.innerHTML = tracks.length ? tracks.map(track => `
            <button type="button" class="tracking-card" data-lng="${track.latest.longitude}" data-lat="${track.latest.latitude}">
                <span class="tracking-card__dot ${track.is_online ? 'is-online' : ''}"></span>
                <span><b>${escapeHtml(track.driver_name)}</b><small>${escapeHtml(track.company_name || 'بدون شرکت')}</small></span>
                <span><b>${escapeHtml(track.serial_number || track.item_id)}</b><small>${escapeHtml(faDate(track.last_seen_at))}</small></span>
            </button>`).join('') : '<div class="rounded-xl bg-slate-50 p-8 text-center font-bold text-slate-400">هنوز موقعیتی دریافت نشده است.</div>';

        list.querySelectorAll('.tracking-card').forEach(button => button.addEventListener('click', () => {
            map.flyTo({ center: [Number(button.dataset.lng), Number(button.dataset.lat)], zoom: 13 });
        }));

        if (firstFit && tracks.length) {
            const bounds = new maplibregl.LngLatBounds();
            tracks.forEach(track => bounds.extend([track.latest.longitude, track.latest.latitude]));
            map.fitBounds(bounds, { padding: 70, maxZoom: 13 });
            firstFit = false;
        }
    }

    async function refresh() {
        try {
            const response = await fetch(dataUrl, { headers: { Accept: 'application/json' } });
            if (!response.ok) throw new Error('request_failed');
            const payload = await response.json();
            renderTracks(payload.tracks || []);
            status.textContent = `به‌روزرسانی: ${faDate(payload.generated_at)}`;
        } catch (_) {
            status.textContent = 'خطا در دریافت موقعیت‌ها';
        }
    }

    map.on('load', refresh);
    setInterval(refresh, 15000);
}
