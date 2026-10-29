import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

// All assets (including the Vazirmatn font) are bundled from node_modules
// into public/build. Nothing is loaded from a CDN or remote font service.
export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/css/filament-font.css', 'resources/js/app.js'],
            refresh: true,
        }),
        tailwindcss(),
    ],
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
