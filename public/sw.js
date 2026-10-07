const VERSI = 'bumdes-v2';
const STATIS = ['/offline.html', '/icons/icon-192.png', '/icons/icon-512.png'];

self.addEventListener('install', (e) => {
  e.waitUntil(caches.open(VERSI).then((c) => c.addAll(STATIS)));
  self.skipWaiting();
});

self.addEventListener('activate', (e) => {
  e.waitUntil(
    caches.keys().then((ks) => Promise.all(ks.filter((k) => k !== VERSI).map((k) => caches.delete(k))))
  );
  self.clients.claim();
});

self.addEventListener('fetch', (e) => {
  const req = e.request;
  if (req.method !== 'GET') return;            // POST (nota, bayar piutang) selalu langsung ke server
  const url = new URL(req.url);
  if (url.origin !== self.location.origin) return;

  // Halaman: selalu dari jaringan, tidak pernah dari cache (data uang + token CSRF).
  if (req.mode === 'navigate') {
    e.respondWith(fetch(req).catch(() => caches.match('/offline.html')));
    return;
  }

  // Aset statis saja: cache sambil menyegarkan di belakang.
  if (/\.(css|js|png|jpg|jpeg|svg|webp|woff2?)$/.test(url.pathname)) {
    e.respondWith(
      caches.open(VERSI).then((c) =>
        c.match(req).then((hit) => {
          const baru = fetch(req).then((res) => { if (res.ok) c.put(req, res.clone()); return res; }).catch(() => hit);
          return hit || baru;
        })
      )
    );
  }
});