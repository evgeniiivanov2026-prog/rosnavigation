import { api } from '../utils/api.js';
import { locate } from '../utils/geo.js';
import { startPolling } from '../utils/poll.js';
import { createSosButton } from '../components/sos-button.js';
import { playRadio } from '../components/radio-sound.js';
import { toast } from '../components/toast.js';

export function init(boot) {
  const form = document.getElementById('sos-form');
  const note = document.getElementById('geo-note');
  const urgent = document.getElementById('urgent');
  if (form) {
    const lat = form.querySelector('#sos-lat');
    const lng = form.querySelector('#sos-lng');
    locate().then((pos) => {
      lat.value = String(pos.lat);
      lng.value = String(pos.lng);
      if (note) note.textContent = 'Точка телефона найдена. Трассу всё равно лучше подписать.';
    }).catch(() => {
      if (note) note.textContent = 'Точку не видим. Напишите трассу — поставим ориентир по ней, это не точный километр.';
    });

    form.querySelectorAll('input[name="type"]').forEach((input) => {
      input.addEventListener('change', () => {
        if (!urgent) return;
        urgent.classList.toggle('is-hot', input.checked && (input.value === 'medical' || input.value === 'accident'));
      });
    });

    const button = document.getElementById('sos-hold');
    let armed = false;
    let pending = false;
    if (button) {
      createSosButton(button, {
        onActivate() {
          armed = true;
          if (typeof form.requestSubmit === 'function') form.requestSubmit(button);
          else form.submit();
        },
      });
    }
    form.addEventListener('submit', async (event) => {
      if (!armed) {
        event.preventDefault();
        return;
      }
      event.preventDefault();
      if (pending) return;
      pending = true;
      const body = Object.fromEntries(new FormData(form).entries());
      try {
        await api('sos.php?action=create', { method: 'POST', body, csrf: boot.csrf });
        playRadio('sos');
        if (navigator.vibrate) navigator.vibrate(30);
        toast('Сигнал в эфире.');
        window.location.href = '/sos.php';
      } catch (err) {
        pending = false;
        armed = false;
        if (err.status === 401) {
          window.location.href = '/auth.php?next=' + encodeURIComponent('/sos.php');
          return;
        }
        toast(err.message || 'Сигнал не ушёл.');
      }
    });
  }

  if (!boot.signalId) return;
  let seenHelper = false;
  const card = document.getElementById('signal-card');
  if (card && card.textContent && card.textContent.includes('На сигнал едет')) seenHelper = true;
  startPolling(async () => {
    const data = await api('poll.php');
    const helper = data.mine && data.mine.helper;
    if (helper && !seenHelper) {
      seenHelper = true;
      playRadio('found');
      window.location.reload();
    }
  }, 15000);
}
