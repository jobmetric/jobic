import fs from 'node:fs';
import path from 'node:path';
import crypto from 'node:crypto';
import { EventEmitter } from 'node:events';

// Exercise the real Apache/FastCGI SAPI with a token-protected, temporary entrypoint.
// All application state remains inside the already-created disposable sandbox.
export function apacheSandbox(directory, locale) {
    const name = `_jobic_extensions_${crypto.randomBytes(16).toString('hex')}`;
    const secret = crypto.randomBytes(32).toString('hex');
    const cookie = `jobic_test_${crypto.randomBytes(8).toString('hex')}`;
    const bridge = path.resolve('public', name);
    const origin = process.env.JOBIC_TEST_APACHE_ORIGIN || 'https://jobic.test';
    const applicationBase = `${origin}/${name}/index.php`;
    const environment = {
        APP_ENV: 'testing', APP_CONFIG_CACHE: path.relative(process.cwd(), path.join(directory, 'config.php')),
        APP_ROUTES_CACHE: path.relative(process.cwd(), path.join(directory, 'routes.php')),
        JOBIC_EXTENSIONS_SANDBOX: directory, JOBIC_EXTENSIONS_LOCALE: locale,
        JOBIC_EXTENSIONS_BASE_URL: applicationBase, JOBIC_EXTENSIONS_ASSET_URL: origin,
        JOBIC_EXTENSION_PACKAGE_SOURCE: '',
    };
    const encoded = Buffer.from(JSON.stringify(environment)).toString('base64');
    const root = Buffer.from(process.cwd().replaceAll('\\', '/')).toString('base64');
    const server = new EventEmitter();
    server.stderr = new EventEmitter(); server.stdout = new EventEmitter();
    let removed = false;
    const cleanup = () => {
        if (!removed) { fs.rmSync(bridge, {recursive: true, force: true, maxRetries: 10, retryDelay: 100}); removed = true; }
    };
    fs.mkdirSync(bridge);
    process.once('exit', cleanup);
    try {
        fs.writeFileSync(path.join(bridge, 'index.php'), `<?php
if (!hash_equals('${secret}', $_COOKIE['${cookie}'] ?? '')) { http_response_code(404); exit; }
header('Cache-Control: no-store');
header('X-Jobic-Test-SAPI: '.PHP_SAPI);
$environment = json_decode(base64_decode('${encoded}'), true, flags: JSON_THROW_ON_ERROR);
foreach ($environment as $key => $value) { putenv($key.'='.$value); $_SERVER[$key] = $_ENV[$key] = $value; }
// Keep persistent FastCGI workers out of the disposable bridge directory.
chdir(base64_decode('${root}'));
require base64_decode('${root}').'/tests/Browser/extensions-router.php';
`);
    } catch (error) { cleanup(); throw error; }
    server.kill = () => { cleanup(); process.removeListener('exit', cleanup); setImmediate(() => server.emit('exit')); };
    return {server, applicationBase, cookie: {name: cookie, value: secret, domain: new URL(origin).hostname, path: `/${name}`, httpOnly: true, secure: origin.startsWith('https:'), sameSite: 'Strict'}};
}
