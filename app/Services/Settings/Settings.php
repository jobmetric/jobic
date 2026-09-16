<?php

declare(strict_types=1);

namespace App\Services\Settings;

use App\Facades\Hooks;
use App\Http\Requests\Settings\AppearanceRequest;
use App\Http\Requests\Settings\GeneralRequest;
use App\Http\Requests\Settings\ProfileRequest;
use App\Http\Requests\Settings\SecurityRequest;
use App\Models\LoginEvent;
use App\Models\User;
use App\Support\Installation\Environment;
use Illuminate\Support\Str;
use JobMetric\PackageCore\Output\Response;
use Throwable;

class Settings
{
    public function __construct(private readonly Environment $environment) {}

    public function page(string $section, int $userId): array
    {
        $user = User::query()->findOrFail($userId);
        $values = $this->environment->read();

        return ['section' => $section, 'account' => $user,
            'site' => ['APP_NAME' => $values['APP_NAME'] ?? config('app.name'), 'APP_URL' => $values['APP_URL'] ?? config('app.url')],
            'preferences' => array_replace(config('admin.appearance'), $user->ui_preferences ?? []),
            'loginEvents' => $section === 'security' ? LoginEvent::query()->where('user_id', $userId)->latest('created_at')->limit(15)->get() : collect()];
    }

    public function general(array $data, ?int $userId = null): Response
    {
        $values = dto($data, GeneralRequest::class);
        $context = ['section' => 'general', 'user_id' => $userId, 'fields' => array_keys($values)];
        Hooks::action('jobic.settings.general.saving', $context);
        try {
            $this->environment->write($values);

            Hooks::notify('jobic.settings.'.$context['section'].'.saved', $context);

            return Response::make(true, trans('admin.saved'));
        } catch (Throwable) {
            return Response::make(false, trans('installation.save_failed'), null, 422);
        }
    }

    public function appearance(array $data, int $userId): Response
    {
        $values = dto($data, AppearanceRequest::class);
        $context = ['section' => 'appearance', 'user_id' => $userId, 'fields' => array_keys($values)];
        $user = User::query()->findOrFail($userId);
        Hooks::action('jobic.settings.appearance.saving', $context);
        $user->forceFill(['ui_preferences' => $values])->save();

        Hooks::notify('jobic.settings.'.$context['section'].'.saved', $context);

        return Response::make(true, trans('admin.saved'), $values);
    }

    public function profile(array $data, int $userId): Response
    {
        $values = dto($data, ProfileRequest::class);
        $context = ['section' => 'profile', 'user_id' => $userId, 'fields' => array_keys($values)];
        $values['email'] = Str::lower($values['email']);
        if (User::query()->where('email', $values['email'])->whereKeyNot($userId)->exists()) {
            return Response::make(false, trans('admin.email_taken'), null, 422);
        }
        $user = User::query()->findOrFail($userId);
        if ($values['email'] !== $user->email) {
            $user->email_verified_at = null;
        }
        Hooks::action('jobic.settings.profile.saving', $context);
        $user->fill($values)->save();

        Hooks::notify('jobic.settings.'.$context['section'].'.saved', $context);

        return Response::make(true, trans('admin.saved'));
    }

    public function security(array $data, int $userId): Response
    {
        $values = dto($data, SecurityRequest::class);
        $context = ['section' => 'security', 'user_id' => $userId, 'fields' => array_keys($values)];
        $user = User::query()->findOrFail($userId);
        Hooks::action('jobic.settings.security.saving', $context);
        $user->forceFill(['password' => $values['password'], 'remember_token' => Str::random(60)])->save();

        Hooks::notify('jobic.settings.'.$context['section'].'.saved', $context);

        return Response::make(true, trans('admin.saved'));
    }
}
