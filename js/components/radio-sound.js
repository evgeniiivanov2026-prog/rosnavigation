import { readFlag } from '../utils/storage.js';

export function etherSoundEnabled() {
  return readFlag('rosnav-sound') === '1';
}

export function chirp() {
  if (!etherSoundEnabled()) return;
  const Ctx = window.AudioContext || window.webkitAudioContext;
  if (!Ctx) return;
  const ctx = new Ctx();
  const osc = ctx.createOscillator();
  const gain = ctx.createGain();
  osc.type = 'square';
  osc.frequency.value = 740;
  gain.gain.setValueAtTime(0.03, ctx.currentTime);
  gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.16);
  osc.connect(gain);
  gain.connect(ctx.destination);
  osc.start();
  osc.stop(ctx.currentTime + 0.17);
  osc.onended = () => ctx.close();
}
