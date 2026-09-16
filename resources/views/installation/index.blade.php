@extends('common.wizard', ['script' => 'resources/js/installation/wizard.js'])
@section('title', __('installation.title'))
@section('content')
    <div class="wizard-v1 installation" data-installation
        data-save="{{ route('installation.steps.store') }}" data-database="{{ route('installation.test.database') }}"
        data-redis="{{ route('installation.test.redis') }}" data-start="{{ route('installation.start') }}"
        data-next="{{ route('installation.next') }}" data-status="{{ route('installation.status') }}"
        data-step="{{ min(6, $progress['step'] + 1) }}" data-install-status="{{ $progress['status'] }}"
        data-error-message="{{ __('installation.network_failed') }}" data-working="{{ __('installation.working') }}"
        data-complete="{{ __('installation.success') }}">
        <nav class="wizard-navigation installation-navigation" aria-label="{{ __('installation.navigation') }}">
            <x-brand compact />
            <strong>{{ __('installation.title') }}</strong>
            <span class="installation-step-count" data-step-count></span>
        </nav>
        <progress class="wizard-progress" data-form-progress max="6" value="1" aria-label="{{ __('installation.navigation') }}"></progress>
        <main class="installation-container">
            <div class="installation-heading"><p>JOBIC CMS</p><h1>{{ __('installation.welcome') }}</h1><p>{{ __('installation.description') }}</p></div>
            <div class="installation-notice" role="status" aria-live="polite" data-notice></div>
            @if ($errors->any())<p role="alert">{{ $errors->first() }}</p>@endif
            @if ($owned)
                <form action="{{ route('installation.language') }}" method="POST" class="installation-language" data-language-form @if ($progress['step'] > 0) hidden @endif>
                    @csrf
                    <span class="installation-language__label">{{ __('installation.language') }}</span>
                    <div class="installation-language__choices" role="group" aria-label="{{ __('installation.language') }}">
                        @foreach (['en' => ['English', 'EN'], 'fa' => ['فارسی', 'فا']] as $locale => [$label, $badge])
                            <button type="submit" name="locale" value="{{ $locale }}" class="installation-language__choice {{ app()->getLocale() === $locale ? 'is-active' : '' }}" aria-pressed="{{ app()->getLocale() === $locale ? 'true' : 'false' }}" lang="{{ $locale }}">
                                <span class="installation-language__badge" aria-hidden="true">{{ $badge }}</span>
                                <span>{{ $label }}</span>
                                <span class="installation-language__check" aria-hidden="true"><x-vuero.icon name="check" size="14" /></span>
                            </button>
                        @endforeach
                    </div>
                </form>
            @endif
            @if (! $owned)
                <p role="alert">{{ __('installation.owned') }}</p>
            @else
                <nav class="installation-steps" aria-label="{{ __('installation.navigation') }}">
                    @foreach (range(1, 6) as $step)
                        <button type="button" data-jump="{{ $step }}">{{ $step }}. {{ __('installation.steps.'.$step) }}</button>
                    @endforeach
                </nav>
                @foreach (range(1, 5) as $step)
                    <form data-step="{{ $step }}" class="installation-panel" @if ($step !== min(6, $progress['step'] + 1)) hidden @endif>
                        @csrf
                        <input type="hidden" name="step" value="{{ $step }}">
                        <h2 tabindex="-1">{{ __('installation.steps.'.$step) }}</h2>
                        @if ($step === 1)
                            <ul class="installation-checks">
                                @foreach ($requirements as $check => $passed)
                                    <li class="{{ $passed ? 'is-passed' : 'is-failed' }}">
                                        <span aria-hidden="true">{{ $passed ? '✓' : '×' }}</span>
                                        {{ str_starts_with($check, 'extension_') ? substr($check, 10) : __('installation.checks.'.$check) }}
                                        <span>{{ __('installation.'.($passed ? 'available' : 'unavailable')) }}</span>
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                        @foreach (\App\Support\Installation\Fields::groups($step) as $group => $fields)
                            <fieldset><legend>{{ __('installation.groups.'.$group) }}</legend>
                                @if ($group === 'mail')<p>{{ __('installation.mail_hint') }}</p>@endif
                                @if ($group === 'database')<p>{{ __('installation.database_hint') }}</p>@endif
                                @if ($group === 'administrator')<p>{{ __('installation.password_hint') }}</p>@endif
                                <x-installation.fields :fields="$fields" :values="$values" />
                                @if (in_array($group, ['database', 'redis']))
                                    <button type="button" class="button" data-test="{{ $group }}">{{ __('installation.test') }}</button>
                                    <span role="status" data-test-result="{{ $group }}"></span>
                                @endif
                            </fieldset>
                        @endforeach
                        <div class="installation-actions installation-actions--split">
                            @if ($step > 1)<button class="button" type="button" data-back>{{ __('installation.back') }}</button>@endif
                            @if ($step === 1)<a class="button installation-recheck" href="{{ route('installation.intro') }}">{{ __('installation.recheck') }}</a>@endif
                            <button class="button button--primary installation-continue" type="submit" @disabled($step === 1 && in_array(false, $requirements, true))>{{ __('installation.save_next') }}</button>
                        </div>
                    </form>
                @endforeach
                <section data-step="6" class="installation-panel" @if ($progress['step'] < 5) hidden @endif>
                    <h2 tabindex="-1">{{ __('installation.steps.6') }}</h2>
                    <progress class="installation-run-progress" max="100" value="{{ $progress['percent'] }}" data-run-progress aria-label="{{ __('installation.steps.6') }}"></progress>
                    <p data-run-label>{{ $progress['percent'] }}%</p>
                    <ol class="installation-tasks">
                        @foreach ($tasks as $task)<li data-task="{{ $loop->index }}">{{ __('installation.tasks.'.$task) }}</li>@endforeach
                    </ol>
                    <div class="installation-actions">
                        <button class="button" type="button" data-back>{{ __('installation.back') }}</button>
                        <button class="button button--primary" type="button" data-run>{{ __('installation.run') }}</button>
                    </div>
                    <a href="{{ route('login') }}" class="button button--primary" data-login hidden>{{ __('installation.login') }}</a>
                </section>
            @endif
        </main>
    </div>
@endsection
