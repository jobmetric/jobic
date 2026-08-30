import { defineConfig } from '@playwright/test';

export default defineConfig({
    testDir: './tests/Browser',
    timeout: 120_000,
    expect: { timeout: 8_000 },
    fullyParallel: false,
    workers: 1,
    retries: 0,
    globalSetup: './tests/Browser/global-setup.js',
    reporter: [['list'], ['json', { outputFile: 'tests/Browser/reports/playwright-results.json' }]],
    use: {
        baseURL: 'https://jobic.test',
        ignoreHTTPSErrors: true,
        storageState: 'tests/Browser/.auth/admin.json',
        trace: 'off',
        screenshot: 'off',
        video: 'off',
    },
});
