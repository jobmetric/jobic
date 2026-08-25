import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import { readdirSync } from 'node:fs';

const dashboardScripts = readdirSync(new URL('./resources/js/vuero/pages/admin/dashboards', import.meta.url))
    .filter((file) => file.endsWith('.js'))
    .map((file) => `resources/js/vuero/pages/admin/dashboards/${file}`);

export default defineConfig({
    plugins: [
        laravel({
            buildDirectory: 'vuero/build',
            input: [
                'resources/css/vuero/app.css',
                'resources/js/vuero/app.js',
                'resources/js/vuero/pages/auth/login.js',
                'resources/js/vuero/pages/admin/dashboard.js',
                'resources/js/vuero/pages/admin/profile-settings.js',
                ...dashboardScripts,
            ],
            refresh: true,
        }),
    ],
    build: {
        chunkSizeWarningLimit: 550,
        rollupOptions: {
            output: {
                manualChunks: {
                    charts: ['apexcharts'],
                },
            },
        },
    },
});
