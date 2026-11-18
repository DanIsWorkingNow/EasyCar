import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
        }),
        tailwindcss(),
    ],
    build: {
        // three.js and ApexCharts are split into their own lazily-loaded
        // chunks (dynamic import), so the main bundle stays small.
        chunkSizeWarningLimit: 1000,
    },
});
