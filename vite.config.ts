import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
// eslint-disable-next-line @typescript-eslint/ban-ts-comment
// @ts-expect-error
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
        }),
        tailwindcss(),
    ],
});
