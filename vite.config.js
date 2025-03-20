import { defineConfig } from 'vite';
import { svelte } from '@sveltejs/vite-plugin-svelte';
import tailwindcss from '@tailwindcss/vite';

// https://vite.dev/config/
export default defineConfig({
    plugins: [
        svelte(),
        tailwindcss()
    ],
    server: {
        proxy: {
                '/dist/assets': {
                target: 'http://localhost:5173',
                changeOrigin: true,
                secure: false,
            },
        },
    },
    build: {
        manifest: true,
        rollupOptions: {
          input: './src/main.js',
        },
    },
});
