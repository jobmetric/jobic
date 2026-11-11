import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';
import { readdirSync } from 'node:fs';

const dashboardScripts = readdirSync(new URL('./resources/js/vuero/pages/admin/dashboards', import.meta.url))
    .filter((file) => file.endsWith('.js'))
    .map((file) => `resources/js/vuero/pages/admin/dashboards/${file}`);
const layoutScripts = readdirSync(new URL('./resources/js/vuero/pages/admin/layouts', import.meta.url))
    .filter((file) => file.endsWith('.js') && !file.includes('-runtime'))
    .map((file) => `resources/js/vuero/pages/admin/layouts/${file}`);

export default defineConfig({
    plugins: [
        vue(),
        laravel({
            buildDirectory: 'vuero/build',
            input: [
                'resources/css/vuero/app.css',
                'resources/js/vuero/app.js',
                'resources/js/vuero/pages/auth/login.js',
                'resources/js/vuero/pages/auth/recovery.js',
                'resources/js/vuero/pages/error/error-page.js',
                'resources/js/vuero/pages/admin/dashboard.js',
                'resources/js/vuero/pages/admin/profile-settings.js',
                'resources/js/vuero/pages/admin/wizard.js',
                'resources/js/installation/wizard.js',
                'resources/js/admin/shell.js',
                'resources/js/languages/index.js',
                'resources/js/languages/form.js',
                'resources/js/posts/index.js',
                'resources/js/posts/form.js',
                'resources/js/workflow/index.js',
                'resources/js/kanban/index.js',
                'resources/js/kanban/boards.js',
                'resources/js/kanban/board-form.js',
                'resources/js/taxonomies/form.js',
                'resources/js/taxonomies/index.js',
                'resources/js/users/index.js',
                'resources/js/users/form.js',
                'resources/js/roles/index.js',
                'resources/js/roles/form.js',
                'resources/css/settings/social-networks.css',
                'resources/css/posts.css',
                'resources/js/settings/social-networks.js',
                'resources/js/vuero/pages/admin/starter.js',
                'resources/js/vuero/pages/utility-roadmap.js',
                'resources/js/vuero/pages/utility-status.js',
                ...dashboardScripts,
                ...layoutScripts,
            ],
            refresh: true,
        }),
    ],
    build: {
        chunkSizeWarningLimit: 550,
        rollupOptions: {
            output: {
                manualChunks: {
                    apexcharts: ['apexcharts'],
                    billboard: ['billboard.js'],
                },
            },
        },
    },
});
