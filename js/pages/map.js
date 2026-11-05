import { startPolling } from '../utils/poll.js';
import { locate } from '../utils/geo.js';

function escapeHtml(value) {
  return String(value).replace(/[&<>"']/g, (ch) => ({
    '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;',
  }[ch]));
}

export function init({ toast }) {
  const bootNode = document.getElementById('map-boot');
  const mapNode = document.getElementById('map');
  if (!bootNode || !mapNode || !window.L) {
    document.getElementById('net-banner')?.removeAttribute('hidden');
    return;
  }
  let data = {};
  try {
    data = JSON.parse(bootNode.textContent || '{}');
  } catch (error) {
    data = {};
  }

  const map = L.map(mapNode, { zoomControl: false, attributionControl: true }).setView([55.75, 49], 5);
  L.control.zoom({ position: 'topright' }).addTo(map);
  const tiles = L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '&copy; участники <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
    maxZoom: 19,
  });
  tiles.addTo(map);
  let tileErrors = 0;
  tiles.on('tileerror', () => {
    tileErrors += 1;
    if (tileErrors > 4) document.getElementById('net-banner')?.removeAttribute('hidden');
  });

  const layer = L.layerGroup().addTo(map);
  let filter = 'all';
  let radius = 0;
  let origin = null;
  const sheet = document.getElementById('sheet');
  const sheetBody = document.getElementById('sheet-body');
  const sheetToggle = document.getElementById('sheet-toggle');

  function kmBetween(a, b) {
    const r = 6371;
    const dLat = (b.lat - a.lat) * Math.PI / 180;
    const dLng = (b.lng - a.lng) * Math.PI / 180;
    const s = Math.sin(dLat / 2) ** 2
      + Math.cos(a.lat * Math.PI / 180) * Math.cos(b.lat * Math.PI / 180) * Math.sin(dLng / 2) ** 2;
    return 2 * r * Math.atan2(Math.sqrt(s), Math.sqrt(1 - s));
  }

  function points() {
    const items = [];
    if (filter === 'all' || filter === 'help') {
      (data.signals || data.sos || []).forEach((item) => items.push({ ...item, kind: 'help' }));
    }
    if (filter === 'all' || filter === 'air' || filter === 'route') {
      (data.helpers || []).forEach((item) => {
        if (item.lat == null) return;
        if (filter === 'air' && item.status !== 'on_air') return;
        if (filter === 'route' && item.status !== 'on_route') return;
        items.push({ ...item, kind: item.status === 'on_route' ? 'route' : 'air' });
      });
    }
    if (filter === 'all' || filter === 'partner') {
      (data.partners || []).forEach((item) => {
        if (item.lat == null) return;
        items.push({ ...item, kind: 'partner' });
      });
    }
    if (origin && radius > 0) {
      return items.filter((item) => kmBetween(origin, item) <= radius);
    }
    return items;
  }

  function draw() {
    layer.clearLayers();
    const items = points();
    const zoom = map.getZoom();
    const buckets = new Map();
    items.forEach((item) => {
      const pt = map.latLngToContainerPoint([item.lat, item.lng]);
      const key = `${Math.round(pt.x / 56)}:${Math.round(pt.y / 56)}`;
      if (!buckets.has(key)) buckets.set(key, []);
      buckets.get(key).push(item);
    });
    buckets.forEach((group) => {
      if (group.length > 1 && zoom < 8) {
        const route = group.find((item) => item.route)?.route || '';
        const html = `<div class="pin pin--cluster"><span>${group.length}${route ? ' · ' + escapeHtml(route) : ''}</span></div>`;
        const marker = L.marker([group[0].lat, group[0].lng], {
          icon: L.divIcon({ className: 'pin-wrap', html, iconSize: [72, 36], iconAnchor: [36, 18] }),
        });
        marker.on('click', () => map.setView([group[0].lat, group[0].lng], Math.min(zoom + 2, 12)));
        marker.addTo(layer);
        return;
      }
      group.forEach((item) => {
        const glyph = item.kind === 'help' ? '!' : item.kind === 'route' ? '→' : item.kind === 'partner' ? '·' : '●';
        const marker = L.marker([item.lat, item.lng], {
          icon: L.divIcon({
            className: 'pin-wrap',
            html: `<div class="pin pin--${item.kind}"><span>${glyph}</span></div>`,
            iconSize: [36, 36],
            iconAnchor: [18, 32],
          }),
        });
        marker.on('click', () => focusItem(item));
        marker.addTo(layer);
      });
    });
    renderList(items);
  }

  function renderList(items) {
    if (!sheetBody) return;
    const signals = items.filter((item) => item.kind === 'help');
    sheetToggle.textContent = `Список · ${signals.length} сигналов`;
    sheetBody.replaceChildren();
    if (!signals.length) {
      const empty = document.createElement('div');
      empty.className = 'state';
      empty.innerHTML = '<h3>Рядом тихо</h3><p>Это хорошая новость.</p>';
      sheetBody.append(empty);
    }
    signals.slice(0, 12).forEach((item) => {
      const card = document.createElement('article');
      card.className = 'signal';
      const title = document.createElement('h3');
      title.textContent = `${item.type_label || 'Сигнал'} · ${item.name || ''}`.trim();
      const meta = document.createElement('p');
      meta.className = 'signal__meta';
      meta.textContent = [item.route, item.km ? `${item.km} км` : '', item.comment || ''].filter(Boolean).join(' · ');
      const link = document.createElement('a');
      link.className = 'btn btn--ghost';
      link.href = `/sos?id=${item.id}`;
      link.textContent = 'Открыть';
      card.append(title, meta, link);
      sheetBody.append(card);
    });
  }

  function focusItem(item) {
    map.setView([item.lat, item.lng], Math.max(map.getZoom(), 9));
    sheet?.classList.remove('is-closed');
    sheetToggle?.setAttribute('aria-expanded', 'true');
  }

  sheetToggle?.addEventListener('click', () => {
    const closed = sheet.classList.toggle('is-closed');
    sheetToggle.setAttribute('aria-expanded', closed ? 'false' : 'true');
  });

  document.querySelectorAll('[data-filter]').forEach((button) => {
    button.addEventListener('click', () => {
      filter = button.dataset.filter;
      document.querySelectorAll('[data-filter]').forEach((chip) => {
        const on = chip === button;
        chip.classList.toggle('is-on', on);
        chip.setAttribute('aria-pressed', on ? 'true' : 'false');
      });
      draw();
    });
  });
  document.querySelectorAll('[data-radius]').forEach((button) => {
    button.addEventListener('click', () => {
      radius = Number(button.dataset.radius || 0);
      document.querySelectorAll('[data-radius]').forEach((chip) => {
        const on = chip === button;
        chip.classList.toggle('is-on', on);
        chip.setAttribute('aria-pressed', on ? 'true' : 'false');
      });
      if (radius > 0 && !origin) {
        toast('Радиус включится, когда разрешите точку.');
      }
      draw();
    });
  });

  document.getElementById('locate')?.addEventListener('click', async () => {
    try {
      origin = await locate();
      map.setView([origin.lat, origin.lng], 9);
      if (radius === 0) {
        document.querySelector('[data-radius="15"]')?.click();
      } else {
        draw();
      }
    } catch (error) {
      toast(error.message);
    }
  });

  map.on('zoomend moveend', draw);
  draw();
  const bounds = points().map((item) => [item.lat, item.lng]);
  if (bounds.length) map.fitBounds(bounds, { padding: [40, 40], maxZoom: 6 });

  window.addEventListener('offline', () => document.getElementById('net-banner')?.removeAttribute('hidden'));
  window.addEventListener('online', () => document.getElementById('net-banner')?.setAttribute('hidden', ''));

  startPolling(async () => {
    const params = new URLSearchParams();
    if (origin) {
      params.set('lat', String(origin.lat));
      params.set('lng', String(origin.lng));
      if (radius > 0) params.set('radius', String(radius));
    }
    const res = await fetch(`/api/poll.php?${params}`, { headers: { Accept: 'application/json' } });
    if (!res.ok) throw new Error('poll');
    const fresh = await res.json();
    data.signals = fresh.sos || [];
    data.helpers = fresh.helpers || data.helpers;
    if (fresh.partners) data.partners = fresh.partners;
    draw();
    document.getElementById('net-banner')?.setAttribute('hidden', '');
  }, 15000);
}
