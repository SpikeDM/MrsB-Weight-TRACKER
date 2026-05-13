// ============================================================
// Mrs B Fitness Tracker — Service Worker
// Handles offline caching so the app works without internet
// ============================================================

const CACHE_NAME   = 'mrsb-tracker-v1';
const OFFLINE_URL  = '/offline.html';

// Files to cache immediately on install
const PRECACHE = [
  '/offline.html',
  '/assets/css/style.css',
  '/assets/img/icons/icon-192.png',
  '/assets/img/icons/icon-512.png',
  '/assets/img/icons/apple-touch-icon.png',
];

// ---- Install: pre-cache shell assets ----------------------
self.addEventListener('install', event => {
  event.waitUntil(
    caches.open(CACHE_NAME)
      .then(cache => cache.addAll(PRECACHE))
      .then(() => self.skipWaiting())
  );
});

// ---- Activate: clean up old caches ------------------------
self.addEventListener('activate', event => {
  event.waitUntil(
    caches.keys().then(keys =>
      Promise.all(
        keys
          .filter(key => key !== CACHE_NAME)
          .map(key => caches.delete(key))
      )
    ).then(() => self.clients.claim())
  );
});

// ---- Fetch: network-first, fall back to cache -------------
self.addEventListener('fetch', event => {
  // Only handle GET requests
  if (event.request.method !== 'GET') return;

  // Skip cross-origin requests
  if (!event.request.url.startsWith(self.location.origin)) return;

  event.respondWith(
    fetch(event.request)
      .then(response => {
        // Cache successful responses for static assets
        if (response.ok) {
          const url = event.request.url;
          if (
            url.includes('/assets/') ||
            url.includes('/assets/img/') ||
            url.includes('/offline.html')
          ) {
            const clone = response.clone();
            caches.open(CACHE_NAME).then(cache => cache.put(event.request, clone));
          }
        }
        return response;
      })
      .catch(() => {
        // Offline — return cached version if available, else offline page
        return caches.match(event.request)
          .then(cached => cached || caches.match(OFFLINE_URL));
      })
  );
});
