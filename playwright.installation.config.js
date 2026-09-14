import { defineConfig } from '@playwright/test';

export default defineConfig({
    testDir: './tests/Browser',
    testMatch: ['installation.spec.js', 'cms-layout.spec.js'],
    workers: 1,
    fullyParallel: false,
    timeout: 60_000,
    use: { baseURL: process.env.JOBIC_INSTALLER_URL || 'http://127.0.0.1:8917' },
});
