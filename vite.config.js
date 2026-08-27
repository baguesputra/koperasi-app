import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import react from '@vitejs/plugin-react';
import { VitePWA } from 'vite-plugin-pwa';

export default defineConfig({
    plugins: [
        laravel({
            input: 'resources/js/app.jsx',
            refresh: true,
        }),
        react(),
        VitePWA({
            registerType: 'prompt',
            includeAssets: ['favicon.ico', 'favicon_custom.ico', 'images/logo.png'],
            manifest: {
                name: 'Koperasi App',
                short_name: 'Koperasi',
                description: 'Aplikasi Koperasi Simpan Pinjam',
                theme_color: '#1f2937',
                background_color: '#ffffff',
                display: 'standalone',
                orientation: 'portrait',
                scope: '/',
                start_url: '/',
                icons: [
                    {
                        src: '/images/logo.png',
                        sizes: '192x192',
                        type: 'image/png',
                        purpose: 'any maskable'
                    },
                    {
                        src: '/images/logo.png',
                        sizes: '512x512',
                        type: 'image/png',
                        purpose: 'any maskable'
                    }
                ],
            },
            workbox: {
                globPatterns: ['**/*.{js,css,html,ico,png,svg,woff2}'],
                additionalManifestEntries: [
                    { url: '/', revision: null },
                ],
                runtimeCaching: [
                    {
                        urlPattern: /^https?.*\.(?:js|css|png|jpg|jpeg|svg|woff2?)$/,
                        handler: 'CacheFirst',
                        options: {
                            cacheName: 'static-assets',
                            expiration: {
                                maxEntries: 100,
                                maxAgeSeconds: 60 * 60 * 24 * 30,
                            },
                        },
                    },
                    {
                        urlPattern: /^https?.*\/api\/.*/,
                        handler: 'NetworkOnly',
                    },
                ],
            },
        }),
    ],
});
