import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
        }),
        vue({
            template: {
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                },
            },
        }),
        tailwindcss(),
    ],
    resolve: {
        alias: {
            '@': '/resources/js',
        },
    },
    build: {
        chunkSizeWarningLimit: 800,
        rollupOptions: {
            output: {
                manualChunks(id) {
                    if (id.includes('node_modules')) {
                        if (id.includes('leaflet')) {
                            return 'vendor-leaflet';
                        }
                        if (id.includes('html5-qrcode')) {
                            return 'vendor-scanner';
                        }
                        if (id.includes('lucide-vue-next')) {
                            return 'vendor-icons';
                        }
                        if (id.includes('vue') || id.includes('@inertiajs')) {
                            return 'vendor-core';
                        }
                    }
                },
            },
        },
    },
});
