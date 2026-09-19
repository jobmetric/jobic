@extends('common.wizard', ['script' => 'resources/js/installation/wizard.js'])
@section('title', __('installation.title'))
@section('content')
    <div class="installation installation-studio" data-installation data-intro="{{ route('installation.intro') }}"
        data-database-verified="{{ $connectionTests['database'] ? 'true' : 'false' }}"
        data-save="{{ route('installation.steps.store') }}" data-database="{{ route('installation.test.database') }}"
        data-start="{{ route('installation.start') }}"
        data-next="{{ route('installation.next') }}" data-status="{{ route('installation.status') }}"
        data-step="{{ ($progress['step'] < 2 ? $progress['step'] + 1 : ($progress['step'] < 5 ? 5 : 6)) }}" data-install-status="{{ $progress['status'] }}">
        <nav class="installation-navigation" aria-label="{{ __('installation.navigation') }}">
            <div class="installation-identity"><x-brand compact /><span><strong>Jobic</strong><small>{{ __('installation.brand_caption') }}</small></span></div>
            <div class="installation-tools">
            @if ($owned)
                <form action="{{ route('installation.language') }}" method="POST" class="installation-language" data-language-form>
                    @csrf
                    <span class="installation-language__label">{{ __('installation.language') }}</span>
                    <div class="installation-language__choices" role="group" aria-label="{{ __('installation.language') }}">
                        @foreach (\App\Support\LocaleCatalog::all() as $locale => $language)
                            <button type="submit" name="locale" value="{{ $locale }}" class="installation-language__choice {{ app()->getLocale() === $locale ? 'is-active' : '' }}" aria-pressed="{{ app()->getLocale() === $locale ? 'true' : 'false' }}" lang="{{ $locale }}">
                                <span class="installation-language__badge" aria-hidden="true">@include('common.language-mark', ['locale' => $locale])</span>
                                <span>{{ $language['name'] }}</span>
                                <span class="installation-language__check" aria-hidden="true"><x-vuero.icon name="check" size="14" /></span>
                            </button>
                        @endforeach
                    </div>
                </form>
            @endif
                <button class="installation-theme theme-toggle" type="button" data-theme-toggle aria-label="{{ __('admin.theme') }}" aria-pressed="false"><x-vuero.icon class="theme-toggle__sun" name="sun" size="22" /><x-vuero.icon class="theme-toggle__moon" name="moon" size="22" /></button>
            </div>
        </nav>
        <progress class="installation-overall-progress" data-form-progress max="4" value="1" aria-label="{{ __('installation.navigation') }}"></progress>
        <main class="installation-container">
            <h1 class="installation-sr-only">{{ __('installation.welcome') }}</h1>

            <div class="installation-toast" role="status" aria-live="polite" aria-atomic="true" data-ui-toast hidden><span class="installation-toast-dot" aria-hidden="true"></span><span data-ui-toast-message></span><button type="button" data-toast-dismiss aria-label="{{ __('admin.close') }}"><x-vuero.icon name="x" size="16" /></button></div>
            @if ($errors->any())<p role="alert">{{ $errors->first() }}</p>@endif
            @if (! $owned)
                <p role="alert">{{ __('installation.owned') }}</p>
            @else
                <div class="installation-workspace"><aside class="installation-sidebar"><div class="installation-sidebar-intro"><span class="installation-orbit" aria-hidden="true">✦</span><h2>{{ __('installation.studio.journey') }}</h2><p>{{ __('installation.studio.journey_hint') }}</p></div><nav class="installation-steps" aria-label="{{ __('installation.navigation') }}">
                    @foreach ([1, 2, 5, 6] as $step)
                        <button type="button" data-jump="{{ $step }}"><span class="installation-step-number">{{ $loop->iteration }}</span><span><strong>{{ __('installation.steps.'.$step) }}</strong><small>{{ __('installation.step_hints.'.$step) }}</small></span></button>
                    @endforeach
                </nav><div class="installation-trust"><x-vuero.icon name="lock" size="18" /><p>{{ __('installation.studio.private') }}</p></div></aside><div class="installation-stage">
                <span class="installation-step-count" data-step-count></span>
                @foreach ([1, 2, 5] as $step)
                    <form data-step="{{ $step }}" class="installation-panel {{ $step === 5 ? 'installation-final-panel' : '' }}" @if ($step !== ($progress['step'] < 2 ? $progress['step'] + 1 : ($progress['step'] < 5 ? 5 : 6))) hidden @endif>
                        @csrf
                        <input type="hidden" name="step" value="{{ $step }}">
                        <header class="installation-panel-heading">@if ($step === 1)<div class="installation-requirements-art"><x-installation.server-art /><span class="installation-handnote installation-handnote--hero">{{ __('installation.foundation_note') }}<span aria-hidden="true">⤺</span></span></div>@elseif ($step === 2)<div class="installation-requirements-art"><x-installation.database-art /><span class="installation-handnote installation-handnote--hero">{{ __('installation.database_note') }}<span aria-hidden="true">⤺</span></span></div>@elseif ($step === 5)<div class="installation-requirements-art"><x-installation.identity-art /><span class="installation-handnote installation-handnote--hero">{{ __('installation.visual.identity_note') }}<span aria-hidden="true">⤺</span></span></div>@endif<h2 tabindex="-1">{{ __('installation.steps.'.$step) }}</h2><p>{{ __('installation.step_descriptions.'.$step) }}</p></header>
                        @if ($step === 1)
                            <x-installation.requirements :requirements="$requirements" />
                        @endif
                        @if ($step === 2)<div class="installation-database-layout"><div class="installation-database-card">@elseif ($step === 5)<div class="installation-finish-layout"><div class="installation-finish-card installation-identity-card">@endif
                        @foreach (($step === 1 ? [] : \App\Support\Installation\Fields::groups($step)) as $group => $fields)
                            <fieldset><legend @if ($step === 5) class="installation-sr-only" @endif>{{ __('installation.groups.'.$group) }}</legend>
                                @if ($step === 5)<div class="installation-section-heading"><span class="reqv-group__icon"><x-vuero.icon :name="$group === 'site' ? 'globe' : 'user'" size="30" /></span><div><h3>{{ __('installation.groups.'.$group) }}</h3><p>{{ __('installation.visual.'.$group.'_hint') }}</p></div></div>@endif
                                @if ($group === 'mail')<p>{{ __('installation.mail_hint') }}</p>@endif
                                @if ($group === 'database')<div class="installation-database-notice"><span class="reqv-group__icon" aria-hidden="true"><x-vuero.icon name="installation-database" size="34" /></span><div><strong>{{ __('installation.database_notice_title') }}</strong><p>{{ __('installation.database_notice_hint') }}</p></div></div>@endif
                                @if ($group === 'administrator')<p>{{ __('installation.password_hint') }}</p>@endif
                                <x-installation.fields :fields="$fields" :values="$values" />
                                @if (in_array($group, ['database', 'redis']))
                                    <div class="installation-connection" data-connection="{{ $group }}" data-state="idle">
                                        <span class="connection-symbol" aria-hidden="true"><svg viewBox="0 0 32 32" fill="none"><circle cx="16" cy="16" r="12"/><path class="connection-check" d="m10 16 4 4 8-8"/><path class="connection-cross" d="m12 12 8 8m0-8-8 8"/></svg></span>
                                        <div class="connection-copy"><strong>{{ __('installation.connection_title') }}</strong><span role="status" aria-live="polite" data-test-result="{{ $group }}">{{ __('installation.connection_idle') }}</span></div>
                                        <button type="button" class="button" data-test="{{ $group }}">@if ($step === 2)<x-vuero.icon name="installation-play" size="19" />@endif{{ __('installation.test') }}</button>
                                    </div>
                                @endif
                            </fieldset>
                        @endforeach
                        <div class="installation-actions installation-actions--split">
                            @if ($step > 1)<button class="button" type="button" data-back>@if ($step === 2)<x-vuero.icon class="installation-direction-icon" name="chevron-left" size="20" />@endif{{ __('installation.back') }}</button>@endif
                            @if ($step === 1)<a class="button installation-recheck" href="{{ route('installation.intro') }}">{{ __('installation.recheck') }}</a>@endif
                            <button class="button button--primary installation-continue" type="submit" @disabled(($step === 1 && in_array(false, $requirements, true)) || ($step === 2 && ! $connectionTests['database']))>{{ __('installation.save_next') }}@if ($step === 2)<x-vuero.icon class="installation-direction-icon" name="chevron-right" size="20" />@endif</button>
                        </div>
                        @if ($step === 2)
                            </div><aside class="installation-database-aside">
                                <div class="reqv-context"><span class="reqv-group__icon" aria-hidden="true"><x-vuero.icon name="server" size="28" /></span><h3>{{ __('installation.database_secure_title') }}</h3><p>{{ __('installation.database_secure_hint') }}</p></div>
                                <x-installation.help-card context="database" />
                                <p class="installation-handnote installation-handnote--footer">{{ __('installation.small_steps_note') }}</p>
                            </aside></div>
                        @elseif ($step === 5)
                            </div><aside class="installation-finish-aside">
                                @foreach (['identity' => 'globe', 'security' => 'shield'] as $tip => $icon)
                                <div class="reqv-context"><span class="reqv-group__icon"><x-vuero.icon :name="$icon" size="28" /></span><h3>{{ __('installation.visual.'.$tip.'_title') }}</h3><p>{{ __('installation.visual.'.$tip.'_body') }}</p></div>
                                @endforeach
                                <x-installation.help-card />
                                <p class="installation-handnote installation-handnote--footer">{{ __('installation.visual.people_note') }}</p>
                            </aside></div>
                        @endif
                    </form>
                @endforeach
                <section data-step="6" class="installation-panel installation-final-panel" @if ($progress['step'] < 5) hidden @endif>
                    <header class="installation-panel-heading"><div class="installation-requirements-art"><x-installation.identity-art variant="install" /><span class="installation-handnote installation-handnote--hero">{{ __('installation.visual.install_note') }}<span aria-hidden="true">⤺</span></span></div><h2 tabindex="-1">{{ __('installation.steps.6') }}</h2><p>{{ __('installation.step_descriptions.6') }}</p></header>
                    <div class="installation-finish-layout"><div class="installation-finish-card installation-run-card">
                    <div class="reqv-summary"><div class="reqv-ring" data-install-ring style="--readiness: {{ $progress['percent'] }}%" aria-hidden="true"><span data-install-count dir="ltr">{{ $progress['task'] }} / {{ count($tasks) }}</span></div><div class="reqv-summary__copy"><h3 data-run-heading>{{ __('installation.visual.run_'.($progress['status'] === 'running' ? 'running' : ($progress['status'] === 'failed' ? 'failed' : 'pending'))) }}</h3><p>{{ __('installation.visual.run_hint') }}</p><progress class="installation-run-progress" max="100" value="{{ $progress['percent'] }}" data-run-progress aria-label="{{ __('installation.steps.6') }}"></progress>
                    <p data-run-label role="status" aria-live="polite">{{ $progress['percent'] }}%</p></div></div>
                    <ol class="installation-tasks">
                        @foreach ($tasks as $task)<li data-task="{{ $loop->index }}" class="{{ $loop->index < $progress['task'] ? 'is-passed' : ($loop->index === $progress['task'] && in_array($progress['status'], ['running', 'failed']) ? 'is-current' : '') }}"><span class="installation-task-mark" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="10"/><path d="m7 12 3.5 3.5L17 9"/></svg></span><span class="installation-task-copy"><strong data-task-title>{{ __('installation.tasks.'.$task) }}</strong><small>{{ __('installation.visual.task_'.$task) }}</small></span><span class="installation-task-status" data-task-status>{{ __('installation.visual.'.($loop->index < $progress['task'] ? 'completed' : ($loop->index === $progress['task'] && in_array($progress['status'], ['running', 'failed']) ? ($progress['status'] === 'failed' ? 'failed' : 'running') : 'pending'))) }}</span></li>@endforeach
                    </ol>
                    <div class="installation-actions installation-actions--split">
                        <button class="button" type="button" data-back>{{ __('installation.back') }}</button>
                        <button class="button button--primary" type="button" data-run>{{ __('installation.run') }}</button>
                    </div>
                    <a href="{{ route('login') }}" class="button button--primary" data-login hidden>{{ __('installation.login') }}</a>
                    </div><aside class="installation-finish-aside">
                        <div class="reqv-context"><span class="reqv-group__icon"><x-vuero.icon name="check" size="28" /></span><h3>{{ __('installation.visual.almost_title') }}</h3><p>{{ __('installation.visual.almost_body') }}</p></div>
                        <div class="reqv-context"><span class="reqv-group__icon"><x-vuero.icon name="info" size="28" /></span><h3>{{ __('installation.visual.tips') }}</h3><ul class="installation-run-tips"><li>{{ __('installation.visual.keep_open') }}</li><li>{{ __('installation.visual.retry_hint') }}</li><li>{{ __('installation.visual.login_hint') }}</li></ul></div>
                        <p class="installation-handnote installation-handnote--footer">{{ __('installation.visual.finish_note') }}</p>
                    </aside></div>
                </section></div></div>
            @endif
            @foreach (['documentation', 'support', 'database_documentation', 'database_support'] as $help)
                <dialog class="installation-help-dialog" data-installation-dialog="{{ $help }}" aria-labelledby="installation-help-{{ $help }}">
                    <h2 id="installation-help-{{ $help }}">{{ __('installation.requirements_'.str_replace('database_', '', $help)) }}</h2>
                    <p>{{ __('installation.'.(str_starts_with($help, 'database_') ? $help : 'requirements_'.$help).'_body') }}</p>
                    <form method="dialog"><button class="button" type="submit">{{ __('admin.close') }}</button></form>
                </dialog>
            @endforeach
            <footer class="installation-footer">{{ __('installation.studio.footer') }}</footer>
        </main>
    </div>
@endsection
