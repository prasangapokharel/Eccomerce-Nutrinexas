// Service Worker for NutriNexus
// Basic caching for better performance

const CACHE_NAME = 'nutrinexus-v1';
const urlsToCache = [
    '/',
    '/css/optimized.css',
    '/js/optimized.js',
    '/images/logo/logo.min.svg',
    '/images/products/default.jpg'
];

// Install event
self.addEventListener('install', function(event) {
    event.waitUntil(
        caches.open(CACHE_NAME)
            .then(function(cache) {
                return cache.addAll(urlsToCache);
            })
    );
});

// Fetch event
self.addEventListener('fetch', function(event) {
    event.respondWith(
        caches.match(event.request)
            .then(function(response) {
                // Return cached version or fetch from network
                return response || fetch(event.request);
            }
        )
    );
});








