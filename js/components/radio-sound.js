const KEY = 'rosnav-sound';

export function radioEnabled() {
  try {
    return localStorage.getItem(KEY) === '1';
  } catch (e) {
    return false;
  }
}

export function setRadio(on) {
  try {
    localStorage.setItem(KEY, on ? '1' : '0');
  } catch (e) {
    /* молча */
  }
}

export function bindRadioToggle(input) {
  if (!input) return;
  input.checked = radioEnabled();
  input.addEventListener('change', () => setRadio(input.checked));
}

export function playRadio(kind) {
  if (!radioEnabled()) return;
  const Ctx = window.AudioContext || window.webkitAudioContext;
  if (!Ctx) return;
  const ctx = new Ctx();
  const gain = ctx.createGain();
  gain.connect(ctx.destination);
  gain.gain.setValueAtTime(0.0001, ctx.currentTime);

  function beep(freq, at, len) {
    const osc = ctx.createOscillator();
    osc.type = 'sine';
    osc.frequency.value = freq;
    osc.connect(gain);
    gain.gain.setValueAtTime(0.04, at);
    gain.gain.exponentialRampToValueAtTime(0.0001, at + len);
    osc.start(at);
    osc.stop(at + len);
  }

  if (kind === 'found') {
    beep(520, ctx.currentTime, 0.12);
    beep(680, ctx.currentTime + 0.16, 0.16);
  } else {
    beep(880, ctx.currentTime, 0.18);
  }
  setTimeout(() => ctx.close(), 700);
}
