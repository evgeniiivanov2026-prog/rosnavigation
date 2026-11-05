import { api } from '../utils/api.js';
import { locate } from '../utils/geo.js';
import { startPolling } from '../utils/poll.js';
import { toast } from '../components/toast.js';

function esc(value) {
  return String(value ?? '').replace(/[&<>"']/g, (ch) => ({
    '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;',
  }[ch]));
}

export function init(boot) {
  const state = {
    lat: boot.map?.lat,
    lng: boot.map?.lng,
    radius: boot.map?.radius || 15,
    type: boot.map?.type || '',
    sos: boot.map?.signals || [],
    helpers: boot.map?.helpers || [],
  };
  const list = document.getElementById('map-list');
  const banner = document.getElementById('offline-banner');
  const mapNode = document.getElementById('map');
  let map = null;
  let layer = null;
  let offlineTold = false;

  function showOffline(on) {
    if (banner) banner.hidden = !on;
  }

  window.addEventListener('offline', () => showOffline(true));
  window.addEventListener('online', () => showOffline(false));

  function renderList() {
    if (!list) return;
    list.replaceChildren();
    if (!state.sos.length && !state.helpers.length) {
      const empty = document.createElement('p');
      empty.className = 'empty';
      empty.textContent = 'В этом круге тихо. Расширьте радиус или передайте свой сигнал.';
      list.append(empty);
      return;
    }
    state.sos.forEach((signal) => {
      const card = document.createElement('article');
      card.className = 'signal';
      const top = document.createElement('div');
      top.className = 'signal__top';
      const title = document.createElement('strong');
      title.textContent = signal.type_label || 'Сигнал';
      top.append(title);
      if (signal.demo) {
        const sample = document.createElement('span');
        sample.className = 'sample';
        sample.textContent = 'Учебная';
        top.append(sample);
      }
      card.append(top);
      const who = document.createElement('p');
      who.textContent = (signal.author && signal.author.name) || 'Водитель';
      card.append(who);
      if (signal.comment) {
        const comment = document.createElement('p');
        comment.textContent = signal.comment;
        card.append(comment);
      }
      const meta = document.createElement('p');
      meta.className = 'meta';
      const bits = [];
      if (signal.distance_km != null) bits.push(String(signal.distance_km).replace('.', ',') + ' км');
      if (signal.ago) bits.push(signal.ago);
      meta.textContent = bits.join(' · ');
      card.append(meta);
      if (signal.demo) {
        const note = document.createElement('p');
        note.className = 'meta';
        note.textContent = 'Учебная метка. Это не вызов.';
        card.append(note);
      } else if (signal.can_accept) {
        const form = document.createElement('form');
        form.method = 'post';
        form.action = '/map.php';
        const csrf = document.createElement('input');
        csrf.type = 'hidden';
        csrf.name = 'csrf';
        csrf.value = boot.csrf;
        const id = document.createElement('input');
        id.type = 'hidden';
        id.name = 'id';
        id.value = String(signal.id);
        const button = document.createElement('button');
        button.className = 'btn';
        button.type = 'submit';
        button.textContent = 'Еду';
        form.append(csrf, id, button);
        card.append(form);
      }
      list.append(card);
    });
    state.helpers.forEach((helper) => {
      const link = document.createElement('a');
      link.className = 'card';
      link.href = '/profile.php?id=' + helper.id;
      const name = document.createElement('h3');
      name.textContent = helper.name + (helper.demo ? ' · образец' : '');
      const meta = document.createElement('p');
      meta.className = 'meta';
      meta.textContent = [helper.status_label, helper.city].filter(Boolean).join(' · ');
      link.append(name, meta);
      list.append(link);
    });
  }

  function markerIcon(kind, demo) {
    const cls = demo ? 'pin pin--demo' : 'pin pin--' + kind;
    return window.L.divIcon({
      className: '',
      html: '<div class="' + cls + '"></div>',
      iconSize: [28, 28],
      iconAnchor: [14, 26],
    });
  }

  function draw() {
    if (!map || !window.L) return;
    if (layer) layer.remove();
    layer = window.L.layerGroup().addTo(map);
    const zoom = map.getZoom();
    if (zoom < 6) {
      const buckets = new Map();
      state.sos.forEach((signal) => {
        const key = Math.round(signal.lat) + ':' + Math.round(signal.lng);
        const bucket = buckets.get(key) || { lat: signal.lat, lng: signal.lng, n: 0, route: signal.route };
        bucket.n += 1;
        buckets.set(key, bucket);
      });
      buckets.forEach((bucket) => {
        const icon = window.L.divIcon({
          className: '',
          html: '<div class="cluster">' + esc(bucket.route || bucket.n) + '</div>',
          iconSize: [36, 36],
        });
        window.L.marker([bucket.lat, bucket.lng], { icon }).addTo(layer).on('click', () => map.setView([bucket.lat, bucket.lng], 8));
      });
    } else {
      state.sos.forEach((signal) => {
        const marker = window.L.marker([signal.lat, signal.lng], { icon: markerIcon('danger', signal.demo), zIndexOffset: 400 });
        const bits = [signal.demo ? 'Учебная метка' : signal.type_label, signal.route, signal.comment].filter(Boolean);
        marker.bindTooltip(esc(bits.join(' · ')), { direction: 'top' });
        marker.addTo(layer);
      });
    }
    state.helpers.forEach((helper) => {
      if (helper.lat == null || helper.lng == null) return;
      const kind = helper.status === 'on_route' ? 'brand' : 'ok';
      const marker = window.L.marker([helper.lat, helper.lng], { icon: markerIcon(kind, helper.demo) });
      marker.bindTooltip(esc((helper.demo ? 'Образец · ' : '') + (helper.name || '') + ' · ' + (helper.status_label || '')), { direction: 'top' });
      marker.addTo(layer);
    });
  }

  function bootMap() {
    if (!window.L || !mapNode) return;
    mapNode.replaceChildren();
    const center = state.lat != null ? [state.lat, state.lng] : [55.75, 49.1];
    const zoom = state.lat != null ? 8 : 5;
    map = window.L.map(mapNode, { scrollWheelZoom: true }).setView(center, zoom);
    const tiles = 'https://server.arcgisonline.com/ArcGIS/rest/services/Canvas/World_Dark_Gray_';
    window.L.tileLayer(tiles + 'Base/MapServer/tile/{z}/{y}/{x}', {
      attribution: '&copy; Esri, HERE, Garmin, USGS',
      maxZoom: 16,
    }).addTo(map);
    window.L.tileLayer(tiles + 'Reference/MapServer/tile/{z}/{y}/{x}', {
      maxZoom: 16,
      pane: 'shadowPane',
    }).addTo(map);
    map.on('zoomend', draw);
    draw();
    setTimeout(() => map.invalidateSize(), 200);
  }

  if (window.L) bootMap();
  else window.addEventListener('load', bootMap);

  async function refresh() {
    const params = new URLSearchParams();
    if (state.lat != null) {
      params.set('lat', String(state.lat));
      params.set('lng', String(state.lng));
    }
    params.set('radius', String(state.radius));
    const data = await api('poll.php?' + params.toString());
    let sos = data.sos || [];
    if (state.type) sos = sos.filter((row) => row.type === state.type);
    state.sos = sos;
    state.helpers = data.helpers || [];
    showOffline(false);
    offlineTold = false;
    renderList();
    draw();
  }

  startPolling(async () => {
    try {
      await refresh();
    } catch (err) {
      showOffline(true);
      if (!offlineTold) {
        offlineTold = true;
        toast('Связь потеряна. Показываю последние данные.');
      }
    }
  }, boot.pollMs || 15000);

  function inNetwork(lat, lng) {
    return lat >= 41 && lat <= 82 && lng >= 19 && lng <= 190;
  }

  const geoBtn = document.getElementById('geo-btn');
  if (geoBtn) {
    geoBtn.addEventListener('click', async () => {
      try {
        const pos = await locate();
        if (!inNetwork(pos.lat, pos.lng)) {
          toast('Точка далеко от наших трасс. Карта остаётся на стране.');
          return;
        }
        const url = new URL(window.location.href);
        url.searchParams.set('lat', String(pos.lat));
        url.searchParams.set('lng', String(pos.lng));
        if (!url.searchParams.get('radius')) url.searchParams.set('radius', '15');
        window.location.href = url.toString();
      } catch (err) {
        toast('Телефон не отдал точку. Выберите радиус «Страна».');
      }
    });
  }

  if (state.lat == null) {
    locate().then((pos) => {
      if (!inNetwork(pos.lat, pos.lng)) return;
      state.lat = pos.lat;
      state.lng = pos.lng;
      state.radius = 15;
      if (map) map.setView([pos.lat, pos.lng], 8);
      refresh().catch(() => showOffline(true));
    }).catch(() => {});
  }
}
