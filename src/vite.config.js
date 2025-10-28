import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';

export default defineConfig({
    server: {
        host: '0.0.0.0',
        port: 5173,
        hmr: {
            host: 'localhost',
            clientPort: 5173,
        },
        watch: {
            usePolling: true,
        },
    },
    build: {
        chunkSizeWarningLimit: 4000,
    },
    plugins: [
        laravel({
            input: 'resources/js/app.js',
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
    ],
    resolve: {
        alias: {
            '@assets': '/resources/', // Update this with the correct path to your images
            '@favicon': '/resources/images/', // Update this with the correct path to your images
        },
    },
});
