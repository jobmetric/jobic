@extends('common.wizard', ['script' => 'resources/js/installation/wizard.js'])
@section('title', __('installation.title'))
@section('content')
    <div class="installation installation-studio" data-installation data-intro="{{ route('installation.intro') }}"
        data-database-verified="{{ $connectionTests['database'] ? 'true' : 'false' }}" data-redis-verified="{{ $connectionTests['redis'] ? 'true' : 'false' }}"
        data-save="{{ route('installation.steps.store') }}" data-database="{{ route('installation.test.database') }}"
        data-redis="{{ route('installation.test.redis') }}" data-start="{{ route('installation.start') }}"
        data-next="{{ route('installation.next') }}" data-status="{{ route('installation.status') }}"
        data-step="{{ min(6, $progress['step'] + 1) }}" data-install-status="{{ $progress['status'] }}">
        <nav class="installation-navigation" aria-label="{{ __('installation.navigation') }}">
            <x-brand compact />
            <strong>{{ __('installation.title') }}</strong>
            <span class="installation-step-count" data-step-count></span>
        </nav>
        <progress class="installation-overall-progress" data-form-progress max="6" value="1" aria-label="{{ __('installation.navigation') }}"></progress>
        <main class="installation-container">
            <div class="installation-heading"><span class="installation-eyebrow"><span></span>{{ __('installation.studio.eyebrow') }}</span><p>{{ __('installation.product') }}</p><h1>{{ __('installation.welcome') }}</h1><p>{{ __('installation.description') }}</p></div>
            <div class="installation-toast" role="status" aria-live="polite" aria-atomic="true" data-ui-toast hidden><span class="installation-toast-dot" aria-hidden="true"></span><span data-ui-toast-message></span><button type="button" data-toast-dismiss aria-label="{{ __('admin.close') }}"><x-vuero.icon name="x" size="16" /></button></div>
            @if ($errors->any())<p role="alert">{{ $errors->first() }}</p>@endif
            @if ($owned)
                <form action="{{ route('installation.language') }}" method="POST" class="installation-language" data-language-form>
                    @csrf
                    <span class="installation-language__label">{{ __('installation.language') }}</span>
                    <div class="installation-language__choices" role="group" aria-label="{{ __('installation.language') }}">
                        @foreach (['en', 'fa'] as $locale)
                            <button type="submit" name="locale" value="{{ $locale }}" class="installation-language__choice {{ app()->getLocale() === $locale ? 'is-active' : '' }}" aria-pressed="{{ app()->getLocale() === $locale ? 'true' : 'false' }}" lang="{{ $locale }}">
                                <span class="installation-language__badge" aria-hidden="true">{{ __('installation.language_badges.'.$locale) }}</span>
                                <span>{{ __('installation.languages.'.$locale) }}</span>
                                <span class="installation-language__check" aria-hidden="true"><x-vuero.icon name="check" size="14" /></span>
                            </button>
                        @endforeach
                    </div>
                </form>
            @endif
            @if (! $owned)
                <p role="alert">{{ __('installation.owned') }}</p>
            @else
                <div class="installation-workspace"><aside class="installation-sidebar"><div class="installation-sidebar-intro"><span class="installation-orbit" aria-hidden="true">✦</span><h2>{{ __('installation.studio.journey') }}</h2><p>{{ __('installation.studio.journey_hint') }}</p></div><nav class="installation-steps" aria-label="{{ __('installation.navigation') }}">
                    @foreach (range(1, 6) as $step)
                        <button type="button" data-jump="{{ $step }}"><span class="installation-step-number">{{ $step }}</span><span><strong>{{ __('installation.steps.'.$step) }}</strong><small>{{ __('installation.step_hints.'.$step) }}</small></span></button>
                    @endforeach
                </nav><div class="installation-trust"><x-vuero.icon name="lock" size="18" /><p>{{ __('installation.studio.private') }}</p></div></aside><div class="installation-stage">
                @foreach (range(1, 5) as $step)
                    <form data-step="{{ $step }}" class="installation-panel" @if ($step !== min(6, $progress['step'] + 1)) hidden @endif>
                        @csrf
                        <input type="hidden" name="step" value="{{ $step }}">
                        <header class="installation-panel-heading"><span class="installation-kicker">{{ __('installation.step_count', ['current' => $step, 'total' => 6]) }}</span><h2 tabindex="-1">{{ __('installation.steps.'.$step) }}</h2><p>{{ __('installation.step_descriptions.'.$step) }}</p></header>
                        @if ($step === 1)
                            <ul class="installation-checks">
                                @foreach ($requirements as $check => $passed)
                                    <li class="{{ $passed ? 'is-passed' : 'is-failed' }}">
                                        <span aria-hidden="true">{{ $passed ? '✓' : '×' }}</span>
                                        {{ str_starts_with($check, 'extension_') ? __('installation.extension', ['name' => substr($check, 10)]) : __('installation.checks.'.$check) }}
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
                                    <div class="installation-connection" data-connection="{{ $group }}" data-state="idle">
                                        <span class="connection-symbol" aria-hidden="true"><svg viewBox="0 0 32 32" fill="none"><circle cx="16" cy="16" r="12"/><path class="connection-check" d="m10 16 4 4 8-8"/><path class="connection-cross" d="m12 12 8 8m0-8-8 8"/></svg></span>
                                        <div class="connection-copy"><strong>{{ __('installation.connection_title') }}</strong><span role="status" aria-live="polite" data-test-result="{{ $group }}">{{ __('installation.connection_idle') }}</span></div>
                                        <button type="button" class="button" data-test="{{ $group }}">{{ __('installation.test') }}</button>
                                    </div>
                                @endif
                            </fieldset>
                        @endforeach
                        <div class="installation-actions installation-actions--split">
                            @if ($step > 1)<button class="button" type="button" data-back>{{ __('installation.back') }}</button>@endif
                            @if ($step === 1)<a class="button installation-recheck" href="{{ route('installation.intro') }}">{{ __('installation.recheck') }}</a>@endif
                            <button class="button button--primary installation-continue" type="submit" @disabled(($step === 1 && in_array(false, $requirements, true)) || ($step === 2 && ! $connectionTests['database']) || ($step === 3 && ! $connectionTests['redis']))>{{ __('installation.save_next') }}</button>
                        </div>
                    </form>
                @endforeach
                <section data-step="6" class="installation-panel" @if ($progress['step'] < 5) hidden @endif>
                    <div class="installation-launch" aria-hidden="true">✦</div><h2 tabindex="-1">{{ __('installation.steps.6') }}</h2><p>{{ __('installation.step_descriptions.6') }}</p>
                    <progress class="installation-run-progress" max="100" value="{{ $progress['percent'] }}" data-run-progress aria-label="{{ __('installation.steps.6') }}"></progress>
                    <p data-run-label>{{ $progress['percent'] }}%</p>
                    <ol class="installation-tasks">
                        @foreach ($tasks as $task)<li data-task="{{ $loop->index }}"><span class="installation-task-mark" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="10"/><path d="m7 12 3.5 3.5L17 9"/></svg></span>{{ __('installation.tasks.'.$task) }}</li>@endforeach
                    </ol>
                    <div class="installation-actions">
                        <button class="button" type="button" data-back>{{ __('installation.back') }}</button>
                        <button class="button button--primary" type="button" data-run>{{ __('installation.run') }}</button>
                    </div>
                    <a href="{{ route('login') }}" class="button button--primary" data-login hidden>{{ __('installation.login') }}</a>
                </section></div></div>
            @endif
            <footer class="installation-footer">{{ __('installation.studio.footer') }}</footer>
        </main>
    </div>
@endsection
