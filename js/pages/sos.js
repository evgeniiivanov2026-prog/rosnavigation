import { api } from '../utils/api.js';
import { locate } from '../utils/geo.js';
import { createSosButton } from '../components/sos-button.js';
import { chirp } from '../components/radio-sound.js';

export function init({ toast }) {
  const form = document.getElementById('sos-form');
  const hold = document.getElementById('sos-hold');
  const geoBtn = document.getElementById('geo-btn');
  const geoStatus = document.getElementById('geo-status');
  const point = document.getElementById('point');
  const route = document.getElementById('route');

  geoBtn?.addEventListener('click', async () => {
    geoStatus.textContent = 'Слушаю телефон…';
    try {
      const pos = await locate();
      document.getElementById('sos-lat').value = String(pos.lat);
      document.getElementById('sos-lng').value = String(pos.lng);
      geoStatus.textContent = 'Точка есть. Свои увидят, куда ехать.';
    } catch (error) {
      geoStatus.textContent = error.message;
    }
  });

  point?.addEventListener('change', () => {
    const label = point.selectedOptions[0]?.textContent || '';
    if (route && !route.value && label.includes('·')) {
      route.value = label.split('·')[0].trim();
    }
  });

  if (hold && form) {
    const control = createSosButton(hold, {
      onActivate() {
        if (typeof form.requestSubmit === 'function') form.requestSubmit();
        else form.submit();
      },
    });
    form.addEventListener('submit', async (event) => {
      event.preventDefault();
      const data = Object.fromEntries(new FormData(form).entries());
      if (!data.type) {
        toast('Выберите, что случилось.');
        return;
      }
      hold.disabled = true;
      try {
        const result = await api('sos.php?action=create', { method: 'POST', body: data });
        chirp();
        window.location.href = `/sos?id=${result.sos.id}`;
      } catch (error) {
        hold.disabled = false;
        if (error.status === 401) {
          window.location.href = '/auth?next=/sos';
          return;
        }
        toast(error.message);
      }
    });
    control.arm = control.arm;
  }

  const chat = document.getElementById('sos-chat-form');
  chat?.addEventListener('submit', async (event) => {
    event.preventDefault();
    const data = Object.fromEntries(new FormData(chat).entries());
    try {
      const result = await api('sos.php?action=message', { method: 'POST', body: data });
      const box = document.getElementById('sos-chat');
      const item = document.createElement('p');
      item.className = 'chat__item';
      const name = document.createElement('strong');
      name.textContent = result.message.name || 'Вы';
      item.append(name, document.createTextNode(result.message.body));
      box?.append(item);
      chat.reset();
    } catch (error) {
      toast(error.message);
    }
  });
}
