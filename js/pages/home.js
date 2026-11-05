import { startPolling } from '../utils/poll.js';

function formatNum(value) {
  return new Intl.NumberFormat('ru-RU').format(value);
}

export function init() {
  const online = document.getElementById('live-online');
  const sos = document.getElementById('live-sos');
  const helps = document.getElementById('live-helps');
  const list = document.getElementById('live-signals');
  if (!online) return;

  startPolling(async () => {
    const res = await fetch('/api/poll.php', { headers: { Accept: 'application/json' } });
    if (!res.ok) throw new Error('poll');
    const data = await res.json();
    online.textContent = formatNum(data.stats?.online ?? 0);
    if (sos) sos.textContent = String(data.stats?.sos ?? 0);
    if (helps) helps.textContent = formatNum(data.stats?.helps ?? 0);
    if (!list || !Array.isArray(data.sos)) return;
    list.replaceChildren();
    data.sos.slice(0, 3).forEach((item) => {
      const li = document.createElement('li');
      const link = document.createElement('a');
      link.href = `/sos?id=${item.id}`;
      const left = document.createElement('span');
      left.textContent = `${item.type_label} · ${item.name}`;
      const right = document.createElement('span');
      right.className = 'mono';
      right.textContent = item.route || 'трасса';
      link.append(left, right);
      li.append(link);
      list.append(li);
    });
  }, 30000);
}
