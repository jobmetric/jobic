<?php

use App\Models\User;
use Illuminate\Contracts\Console\Kernel;

require __DIR__.'/../../../vendor/autoload.php';
$app = require __DIR__.'/../../../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
$email = getenv('JOBIC_2FA_TEST_EMAIL');
if (! is_string($email) || ! preg_match('/^two-factor-browser-[a-f0-9]+@example\.test$/', $email)) {
    throw new RuntimeException('A unique browser fixture email is required.');
}
if (($argv[1] ?? '') === 'delete') {
    User::where('email', $email)->delete();
} else {
    User::create(['name' => 'Authenticator browser test', 'email' => $email, 'password' => 'browser-test-password']);
}
