<?php

declare(strict_types=1);

namespace App\Services\Auth;

use App\Facades\Hooks;
use App\Http\Requests\Settings\TwoFactorRequest;
use App\Models\User;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use JobMetric\PackageCore\Output\Response;
use PragmaRX\Google2FA\Google2FA;
use Throwable;

/**
 * Manage encrypted authenticator enrollment and consume one-time credentials.
 */
class TwoFactor
{
    /**
     * Validate an enrollment operation and persist it under an account lock.
     *
     * @param array<string, mixed> $data
     *
     * @throws Throwable
     */
    public function update(array $data, int $userId): Response
    {
        $values = dto($data, TwoFactorRequest::class);

        $context = ['user_id' => $userId, 'operation' => $values['action']];
        $result = DB::transaction(function () use ($values, $userId, $context): array {
            $user = User::query()->lockForUpdate()->findOrFail($userId);
            $google = new Google2FA;
            $action = $values['action'];
            $codes = [];

            if ($action === 'setup') {
                if ($user->two_factor_enabled_at) {
                    throw ValidationException::withMessages(['code' => __('two-factor.already_enabled')]);
                }

                $secret = $google->generateSecretKey();
                $changes = ['two_factor_pending_secret' => $secret, 'two_factor_pending_at' => now()];
                $uri = $google->getQRCodeUrl((string) config('app.name'), $user->email, $secret);
                $writer = new Writer(new ImageRenderer(new RendererStyle(240), new SvgImageBackEnd));
                $result = [
                    'setup'  => true,
                    'secret' => $secret,
                    'qr'     => 'data:image/svg+xml;base64,' . base64_encode($writer->writeString($uri)),
                ];
            }
            else if ($action === 'enable') {
                if ($user->two_factor_enabled_at || ! $user->two_factor_pending_at || $user->two_factor_pending_at->lt(now()->subMinutes(10))) {
                    throw ValidationException::withMessages(['code' => __('two-factor.expired')]);
                }

                $step = $google->verifyKeyNewer($user->two_factor_pending_secret, $values['code'], 0, 1);
                if ($step === false) {
                    throw ValidationException::withMessages(['code' => __('two-factor.invalid')]);
                }

                for ($i = 0 ; $i < 8 ; $i++) {
                    $codes[] = bin2hex(random_bytes(8));
                }

                $changes = [
                    'two_factor_secret'         => $user->two_factor_pending_secret,
                    'two_factor_enabled_at'     => now(),
                    'two_factor_last_step'      => $step,
                    'two_factor_pending_secret' => null,
                    'two_factor_pending_at'     => null,
                    'two_factor_recovery_codes' => array_map(fn (string $code
                    ): string => hash('sha256', $code), $codes),
                    'remember_token'            => Str::random(60),
                ];
                $result = [
                    'enabled'        => true,
                    'recovery_codes' => $codes,
                ];
            }
            else {
                if (! $user->two_factor_enabled_at || ! $this->consume($user, $values['code'])) {
                    throw ValidationException::withMessages(['code' => __('two-factor.invalid')]);
                }

                $changes = [
                    'two_factor_secret'         => null,
                    'two_factor_enabled_at'     => null,
                    'two_factor_last_step'      => null,
                    'two_factor_pending_secret' => null,
                    'two_factor_pending_at'     => null,
                    'two_factor_recovery_codes' => null,
                    'remember_token'            => Str::random(60),
                ];
                $result = ['enabled' => false];
            }

            Hooks::action('jobic.settings.two_factor.saving', $context);
            $user->forceFill($changes)->save();

            return $result;
        });

        Hooks::notify('jobic.settings.two_factor.saved', $context);

        return Response::make(true, __('two-factor.saved'), $result);
    }

    /**
     * Consume an unused TOTP step or recovery code while the caller holds the account lock.
     *
     *
     * @throws Throwable
     */
    public function consume(User $user, string $code): bool
    {
        if (! $user->two_factor_enabled_at || ! $user->two_factor_secret) {
            return false;
        }

        if (preg_match('/^[0-9]{6}$/D', $code)) {
            $step = (new Google2FA)->verifyKeyNewer($user->two_factor_secret, $code, $user->two_factor_last_step ?? 0, 1);
            if ($step === false) {
                return false;
            }

            $user->forceFill(['two_factor_last_step' => $step])->save();

            return true;
        }

        $codes = $user->two_factor_recovery_codes ?? [];
        $index = array_search(hash('sha256', $code), $codes, true);

        if ($index === false) {
            return false;
        }

        unset($codes[$index]);
        $user->forceFill(['two_factor_recovery_codes' => array_values($codes)])->save();

        return true;
    }
}
