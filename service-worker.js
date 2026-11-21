const CACHE = 'bakery-pos-shell-v1';
const SHELL = [
  './assets/css/style.css',
  './assets/css/landing.css',
  './assets/js/app.js'
];

self.addEventListener('install', (event) => {
  event.waitUntil(caches.open(CACHE).then((cache) => cache.addAll(SHELL)));
});

self.addEventListener('fetch', (event) => {
  const url = new URL(event.request.url);
  if (url.pathname.includes('/api/') || event.request.method !== 'GET') {
    return;
  }
  event.respondWith(
    fetch(event.request).catch(() => caches.match(event.request))
  );
});
