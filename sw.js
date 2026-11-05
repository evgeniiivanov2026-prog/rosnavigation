const CACHE = 'rosnav-v1';
const SHELL = [
  '/offline.html',
  '/favicon.svg',
  '/css/tokens.css',
  '/css/base.css',
  '/css/components.css',
  '/css/pages.css',
  '/css/holidays.css',
  '/js/main.js',
  '/fonts/manrope-cyrillic.woff2',
  '/fonts/unbounded-cyrillic.woff2',
];

self.addEventListener('install', (event) => {
  event.waitUntil(caches.open(CACHE).then((cache) => cache.addAll(SHELL)).then(() => self.skipWaiting()));
});

self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches.keys().then((keys) => Promise.all(keys.filter((key) => key !== CACHE).map((key) => caches.delete(key)))).then(() => self.clients.claim())
  );
});

self.addEventListener('fetch', (event) => {
  const req = event.request;
  if (req.method !== 'GET') return;
  const url = new URL(req.url);
  if (url.origin !== self.location.origin) return;
  if (url.pathname.startsWith('/api/')) return;

  if (req.mode === 'navigate') {
    event.respondWith(fetch(req).catch(() => caches.match('/offline.html')));
    return;
  }

  event.respondWith(
    fetch(req).then((res) => res).catch(() => caches.match(req))
  );
});
