<?php

declare(strict_types=1);

namespace App\Services\Settings;

use App\Facades\Hooks;
use App\Http\Requests\Settings\AppearanceRequest;
use App\Http\Requests\Settings\AvatarRequest;
use App\Http\Requests\Settings\GeneralRequest;
use App\Http\Requests\Settings\ProfileRequest;
use App\Http\Requests\Settings\SecurityRequest;
use App\Models\LoginEvent;
use App\Models\User;
use App\Support\Installation\Environment;
use DateInvalidTimeZoneException;
use DateMalformedStringException;
use DateTimeImmutable;
use DateTimeZone;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use JobMetric\PackageCore\Output\Response;
use Throwable;

/**
 * Manage website and account settings.
 */
readonly class Settings
{
    /**
     * Initialize the service dependencies.
     *
     * @param Environment $environment
     *
     * @return void
     */
    public function __construct(private Environment $environment)
    {
    }

    /**
     * Get the account and configuration data for a settings page.
     *
     * @param string $section
     * @param int $userId
     *
     * @return array<string, mixed>
     * @throws ModelNotFoundException
     * @throws DateMalformedStringException
     * @throws DateInvalidTimeZoneException
     */
    public function page(string $section, int $userId): array
    {
        $user = User::query()->findOrFail($userId);
        $values = $this->environment->read();

        return [
            'section'     => $section,
            'account'     => $user,
            'profileMeta' => [
                'first_name' => $user->getMetadata('first_name'),
                'last_name'  => $user->getMetadata('last_name'),
                'gender'     => $user->getMetadata('gender'),
            ],
            'site'        => [
                'APP_NAME'     => $values['APP_NAME'] ?? config('app.name'),
                'APP_URL'      => $values['APP_URL'] ?? config('app.url'),
                'APP_TIMEZONE' => $values['APP_TIMEZONE'] ?? config('app.timezone', 'UTC'),
            ],
            'timezones'   => DateTimeZone::listIdentifiers(),
            'serverTime'  => (new DateTimeImmutable('now', new DateTimeZone((string) ($values['APP_TIMEZONE'] ?? config('app.timezone', 'UTC')))))->format('Y-m-d H:i:s T'),
            'preferences' => array_replace(config('admin.appearance'), $user->ui_preferences ?? []),
            'loginEvents' => $section === 'security' ? LoginEvent::query()
                ->where('user_id', $userId)
                ->latest('created_at')
                ->limit(15)
                ->get() : collect(),
        ];
    }

    /**
     * Validate and save the website name and URL.
     *
     * @param array<string, mixed> $data
     * @param int|null $userId
     *
     * @return Response
     * @throws ValidationException
     * @throws Throwable
     */
    public function general(array $data, ?int $userId = null): Response
    {
        $values = dto($data, GeneralRequest::class);

        $context = [
            'section' => 'general',
            'user_id' => $userId,
            'fields'  => array_keys($values),
        ];

        Hooks::action('jobic.settings.general.saving', $context);

        try {
            $this->environment->write($values);
            config([
                'app.name'     => $values['APP_NAME'],
                'app.url'      => $values['APP_URL'],
                'app.timezone' => $values['APP_TIMEZONE'],
            ]);
            date_default_timezone_set($values['APP_TIMEZONE']);

            Hooks::notify('jobic.settings.' . $context['section'] . '.saved', $context);

            return Response::make(true, trans('admin.saved'));
        } catch (Throwable) {
            return Response::make(false, trans('installation.save_failed'), null, 422);
        }
    }

    /**
     * Validate and save the current account appearance preferences.
     *
     * @param array<string, mixed> $data
     * @param int $userId
     *
     * @return Response
     * @throws ValidationException
     * @throws ModelNotFoundException
     * @throws Throwable
     */
    public function appearance(array $data, int $userId): Response
    {
        $values = dto($data, AppearanceRequest::class);

        $context = [
            'section' => 'appearance',
            'user_id' => $userId,
            'fields'  => array_keys($values),
        ];
        $user = User::query()->findOrFail($userId);

        Hooks::action('jobic.settings.appearance.saving', $context);

        $user->forceFill(['ui_preferences' => $values])->save();

        Hooks::notify('jobic.settings.' . $context['section'] . '.saved', $context);

        return Response::make(true, trans('admin.saved'), $values);
    }

    /**
     * Validate and update the current account profile.
     *
     * @param array<string, mixed> $data
     * @param int $userId
     *
     * @return Response
     * @throws ValidationException
     * @throws ModelNotFoundException
     * @throws Throwable
     */
    public function profile(array $data, int $userId): Response
    {
        $values = dto($data, ProfileRequest::class);

        $context = [
            'section' => 'profile',
            'user_id' => $userId,
            'fields'  => array_keys($values),
        ];
        $values['email'] = Str::lower($values['email']);
        $profileMetadata = [
            'first_name' => trim((string) ($values['first_name'] ?? '')),
            'last_name'  => trim((string) ($values['last_name'] ?? '')),
            'gender'     => $values['gender'] ?? null,
        ];
        unset($values['first_name'], $values['last_name'], $values['gender']);

        $displayName = trim($profileMetadata['first_name'] . ' ' . $profileMetadata['last_name']);
        if ($displayName !== '') {
            $values['name'] = $displayName;
        }

        if (User::query()->where('email', $values['email'])->whereKeyNot($userId)->exists()) {
            return Response::make(false, trans('admin.email_taken'), null, 422);
        }

        $user = User::query()->findOrFail($userId);
        if ($values['email'] !== $user->email) {
            $user->email_verified_at = null;
        }

        Hooks::action('jobic.settings.profile.saving', $context);

        $user->fill(['email' => $values['email'], 'name' => $values['name'] ?? $user->name, 'avatar_preset' => $values['avatar_preset'] ?? $user->avatar_preset, 'metadata' => $profileMetadata])->save();

        Hooks::notify('jobic.settings.' . $context['section'] . '.saved', $context);

        return Response::make(true, trans('admin.saved'), [
            'avatar_url'    => $user->avatarUrl(),
            'avatar_preset' => $user->avatar_preset,
        ]);
    }

    /**
     * Validate and immediately save the current account avatar selection.
     *
     * @param array<string, mixed> $data
     * @param int $userId
     *
     * @return Response
     * @throws ValidationException
     * @throws ModelNotFoundException
     * @throws Throwable
     */
    public function avatar(array $data, int $userId): Response
    {
        $values = dto($data, AvatarRequest::class);
        $context = [
            'section' => 'profile',
            'user_id' => $userId,
            'fields'  => ['avatar_preset'],
        ];
        $user = User::query()->findOrFail($userId);

        Hooks::action('jobic.settings.profile.saving', $context);

        $user->forceFill([
            'avatar_preset' => $values['avatar_preset'],
        ])->save();

        Hooks::notify('jobic.settings.profile.saved', $context);

        return Response::make(true, trans('admin.avatar_saved'), [
            'avatar_url'    => $user->avatarUrl(),
            'avatar_preset' => $user->avatar_preset,
        ]);
    }

    /**
     * Validate and change the current account password.
     *
     * @param array<string, mixed> $data
     * @param int $userId
     *
     * @return Response
     * @throws ValidationException
     * @throws ModelNotFoundException
     * @throws Throwable
     */
    public function security(array $data, int $userId): Response
    {
        $values = dto($data, SecurityRequest::class);

        $context = [
            'section' => 'security',
            'user_id' => $userId,
            'fields'  => array_keys($values),
        ];
        $user = User::query()->findOrFail($userId);

        Hooks::action('jobic.settings.security.saving', $context);

        $user->forceFill([
            'password'       => $values['password'],
            'remember_token' => Str::random(60),
        ])->save();

        Hooks::notify('jobic.settings.' . $context['section'] . '.saved', $context);

        return Response::make(true, trans('admin.saved'));
    }
}
