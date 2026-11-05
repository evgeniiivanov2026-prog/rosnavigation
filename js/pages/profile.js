import { readFlag, writeFlag } from '../utils/storage.js';

export function init() {
  const box = document.getElementById('ether-sound');
  if (!box) return;
  box.checked = readFlag('rosnav-sound') === '1';
  box.addEventListener('change', () => {
    writeFlag('rosnav-sound', box.checked ? '1' : '0');
  });
}
