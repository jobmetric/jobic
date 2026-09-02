import { chromium } from '@playwright/test';
import fs from 'node:fs';
import path from 'node:path';

const authFile = path.join(process.cwd(), 'tests', 'Browser', '.auth', 'admin.json');

export default async function globalSetup() {
    fs.mkdirSync(path.dirname(authFile), { recursive: true });

    const browser = await chromium.launch();
    const page = await browser.newPage({ ignoreHTTPSErrors: true });

    await page.goto('https://jobic.test/login');
    await page.fill('#email', 'admin@jobic.test');
    await page.fill('#password', 'secret');
    await page.click('button.auth-form__submit');
    await page.waitForURL(/\/admin$/, { timeout: 15_000 });

    await page.context().storageState({ path: authFile });
    await browser.close();
}
