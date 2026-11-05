import { readFlag, writeFlag } from './utils/storage.js';

const header = document.querySelector('.site-header');
const navToggle = document.querySelector('.nav-check');
const navButton = document.querySelector('.nav-toggle');
const navPanel = document.querySelector('.nav-panel');
const themeButton = document.querySelector('[data-theme-toggle]');

function onScroll() {
  if (header) header.classList.toggle('is-stuck', window.scrollY > 8);
}
onScroll();
window.addEventListener('scroll', onScroll, { passive: true });

function syncMenu() {
  if (!navToggle || !navButton) return;
  const open = navToggle.checked;
  navButton.setAttribute('aria-expanded', open ? 'true' : 'false');
  const label = navButton.querySelector('.visually-hidden');
  if (label) label.textContent = open ? 'Закрыть меню' : 'Открыть меню';
}
if (navToggle) {
  navToggle.addEventListener('change', syncMenu);
  syncMenu();
}
if (navPanel && navToggle) {
  navPanel.querySelectorAll('a').forEach((link) => {
    link.addEventListener('click', () => {
      if (window.matchMedia('(max-width: 1023px)').matches) {
        navToggle.checked = false;
        syncMenu();
      }
    });
  });
}
document.addEventListener('keydown', (event) => {
  if (event.key === 'Escape' && navToggle?.checked) {
    navToggle.checked = false;
    syncMenu();
    navButton?.focus();
  }
});

function applyTheme(theme) {
  if (theme === 'light') document.documentElement.setAttribute('data-theme', 'light');
  else document.documentElement.removeAttribute('data-theme');
  if (themeButton) {
    const light = theme === 'light';
    themeButton.setAttribute('aria-pressed', light ? 'true' : 'false');
    themeButton.setAttribute('aria-label', light ? 'Тёмная тема' : 'Светлая тема');
  }
}
applyTheme(readFlag('rosnav-theme') === 'light' ? 'light' : 'dark');
themeButton?.addEventListener('click', () => {
  const next = document.documentElement.getAttribute('data-theme') === 'light' ? 'dark' : 'light';
  writeFlag('rosnav-theme', next);
  applyTheme(next);
});

export function toast(message) {
  const root = document.getElementById('toasts');
  if (!root) return;
  const node = document.createElement('p');
  node.className = 'toast';
  node.setAttribute('role', 'status');
  node.textContent = message;
  root.append(node);
  setTimeout(() => node.remove(), 4200);
}

let deferredInstall = null;
window.addEventListener('beforeinstallprompt', (event) => {
  event.preventDefault();
  deferredInstall = event;
  document.querySelectorAll('[data-install]').forEach((button) => {
    button.hidden = false;
  });
});
document.querySelectorAll('[data-install]').forEach((button) => {
  button.addEventListener('click', async () => {
    if (!deferredInstall) return;
    deferredInstall.prompt();
    await deferredInstall.userChoice;
    deferredInstall = null;
    button.hidden = true;
  });
});

if ('serviceWorker' in navigator) {
  window.addEventListener('load', () => {
    navigator.serviceWorker.register('/sw.js').catch(() => {});
  });
}

const page = document.body.dataset.page;
const pages = {
  home: () => import('./pages/home.js'),
  sos: () => import('./pages/sos.js'),
  map: () => import('./pages/map.js'),
  partners: () => import('./pages/partners.js'),
  channel: () => import('./pages/channel.js'),
  auth: () => import('./pages/auth.js'),
  profile: () => import('./pages/profile.js'),
};
if (page && pages[page]) {
  pages[page]().then((mod) => mod.init?.({ toast })).catch((error) => console.warn(error));
}
