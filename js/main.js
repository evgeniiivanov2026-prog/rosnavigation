import { bindRadioToggle } from './components/radio-sound.js';
import { init as initHome } from './pages/home.js';
import { init as initSos } from './pages/sos.js';
import { init as initMap } from './pages/map.js';
import { init as initChannel } from './pages/channel.js';

const bootNode = document.getElementById('boot');
const boot = bootNode ? JSON.parse(bootNode.textContent || '{}') : {};
const page = document.body.dataset.page || boot.page || '';

function syncMenu() {
  const toggle = document.getElementById('nav-toggle');
  const button = document.querySelector('.nav-toggle');
  if (!toggle || !button) return;
  const open = toggle.checked;
  button.setAttribute('aria-expanded', open ? 'true' : 'false');
  const label = button.querySelector('.visually-hidden');
  if (label) label.textContent = open ? 'Закрыть меню' : 'Открыть меню';
}

const navToggle = document.getElementById('nav-toggle');
if (navToggle) {
  navToggle.addEventListener('change', syncMenu);
  syncMenu();
  document.querySelectorAll('.nav-panel a').forEach((link) => {
    link.addEventListener('click', () => {
      if (window.matchMedia('(max-width: 1023px)').matches) {
        navToggle.checked = false;
        syncMenu();
      }
    });
  });
}

document.addEventListener('keydown', (event) => {
  if (event.key === 'Escape' && navToggle && navToggle.checked) {
    navToggle.checked = false;
    syncMenu();
  }
});

const themeButton = document.getElementById('theme-toggle');
function applyTheme(theme) {
  if (theme === 'light') document.documentElement.setAttribute('data-theme', 'light');
  else document.documentElement.removeAttribute('data-theme');
  if (themeButton) {
    const light = theme === 'light';
    themeButton.setAttribute('aria-pressed', light ? 'true' : 'false');
    themeButton.setAttribute('aria-label', light ? 'Тёмная тема' : 'Светлая тема');
  }
  const color = document.querySelector('meta[name="theme-color"]');
  if (color) color.setAttribute('content', theme === 'light' ? '#F5F7FA' : '#0A0E13');
}
applyTheme(document.documentElement.getAttribute('data-theme') || 'dark');
if (themeButton) {
  themeButton.addEventListener('click', () => {
    const next = document.documentElement.getAttribute('data-theme') === 'light' ? 'dark' : 'light';
    try { localStorage.setItem('rosnav-theme', next); } catch (e) { /* приватный режим */ }
    applyTheme(next);
  });
}

bindRadioToggle(document.getElementById('sound-toggle'));

const progress = document.querySelector('.progress');
if (progress && page !== 'map') {
  const onScroll = () => {
    const scrollable = document.documentElement.scrollHeight - window.innerHeight;
    const ratio = scrollable > 0 ? window.scrollY / scrollable : 0;
    progress.style.transform = 'scaleX(' + Math.min(1, Math.max(0, ratio)) + ')';
  };
  onScroll();
  window.addEventListener('scroll', onScroll, { passive: true });
}

if ('serviceWorker' in navigator) {
  window.addEventListener('load', () => {
    navigator.serviceWorker.register('/sw.js').catch(() => {});
  });
}

const pages = { home: initHome, sos: initSos, map: initMap, channel: initChannel };
if (pages[page]) pages[page](boot);
