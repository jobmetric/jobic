@props([
    'requirements',
])

@php
    $coreChecks = [];
    $extensionChecks = [];

    foreach ($requirements as $check => $passed) {
        if (str_starts_with($check, 'extension_')) {
            $extensionChecks[$check] = $passed;
        } else {
            $coreChecks[$check] = $passed;
        }
    }

    $total = count($requirements);
    $passedCount = count(array_filter($requirements));
    $warningCount = 0; // The current requirement contract only reports pass/fail outcomes.
    $failedCount = $total - $passedCount;
    $ready = $total > 0 && $failedCount === 0;
    $percentage = $total > 0 ? round($passedCount / $total * 100) : 0;
    $number = fn ($value) => extension_loaded('intl') ? \Illuminate\Support\Number::format($value, locale: app()->getLocale()) : (string) $value;

    $corePassed = count(array_filter($coreChecks));
    $coreTotal = count($coreChecks);

    $extPassed = count(array_filter($extensionChecks));
    $extTotal = count($extensionChecks);

    $extensionList = [];

    foreach ($extensionChecks as $check => $passed) {
        $extensionList[] = [$check, $passed];
    }

    // Fill rows across the two columns, retaining the backend check order.
    $extensionPairs = [];
    foreach ($extensionList as $index => $extension) {
        $extensionPairs[$index % 2][] = $extension;
    }
@endphp

<section class="reqv {{ $ready ? 'is-ready' : 'needs-attention' }}" aria-label="{{ __('installation.requirements_tracker_title') }}">
    <div class="reqv-summary">
        <div class="reqv-ring" style="--readiness: {{ $percentage }}%" aria-hidden="true"><span><strong>{{ $number($passedCount) }}</strong> / {{ $number($total) }}</span></div>
        <div class="reqv-summary__copy">
            <h3>{{ __('installation.'.($ready ? 'requirements_all_passed' : 'requirements_attention')) }}</h3>
            <p>{{ __('installation.'.($ready ? 'requirements_ready_summary' : 'requirements_failed')) }}</p>
            <progress max="{{ max(1, $total) }}" value="{{ $passedCount }}" aria-label="{{ __('installation.requirements_tracker_title') }}"></progress>
            <ul class="reqv-totals">
                <li>{{ __('installation.requirements_passed_count', ['count' => $number($passedCount)]) }}</li>
                <li class="reqv-totals__warnings">{{ __('installation.requirements_warning_count', ['count' => $number($warningCount)]) }}</li>
                <li class="reqv-totals__errors">{{ __('installation.requirements_error_count', ['count' => $number($failedCount)]) }}</li>
            </ul>
        </div>
        @if ($ready)
            <div class="reqv-celebration"><span aria-hidden="true">🎉</span><div><strong>{{ __('installation.requirements_great') }}</strong><p>{{ __('installation.requirements_looks_good') }}</p></div></div>
        @endif
    </div>
    <div class="reqv-body">
    {{-- Requirement groups --}}
    <div class="reqv-groups">

        <details class="reqv-group" open>
            <summary class="reqv-group__header">

                <span
                    class="reqv-group__icon"
                    aria-hidden="true"
                >
                    <x-vuero.icon name="server" size="24" />
                </span>

                <span class="reqv-group__copy">
                    <strong class="reqv-group__title">
                        {{ __('installation.requirements_environment') }}
                    </strong>

                    <span class="reqv-group__description">
                        {{ __('installation.requirements_environment_hint') }}
                    </span>
                </span>

                <span class="reqv-group__status">
                    <span
                        class="reqv-group__count {{ $corePassed === $coreTotal ? 'reqv-group__count--ready' : 'reqv-group__count--missing' }}"
                    >
                        {{ $number($corePassed) }} / {{ $number($coreTotal) }}
                    </span>

                    <span
                        class="reqv-group__chevron"
                        aria-hidden="true"
                    >
                        <x-vuero.icon name="chevron-down" size="15" />
                    </span>
                </span>

            </summary>

            <div class="reqv-group__content">
                <ul class="reqv-list">

                    @foreach ($coreChecks as $check => $passed)
                        <x-installation.requirement-row
                            variant="full"
                            :label="__('installation.checks.'.$check)"
                            :description="$passed ? __('installation.check_hints.'.$check) : __('installation.requirements_check_failed')"
                            :passed="$passed"
                        />
                    @endforeach

                </ul>
            </div>
        </details>


        <details class="reqv-group" open>
            <summary class="reqv-group__header">

                <span
                    class="reqv-group__icon"
                    aria-hidden="true"
                >
                    <x-vuero.icon name="installation-puzzle" size="24" />
                </span>

                <span class="reqv-group__copy">
                    <strong class="reqv-group__title">
                        {{ __('installation.requirements_extensions') }}
                    </strong>

                    <span class="reqv-group__description">
                        {{ __('installation.requirements_extensions_hint') }}
                    </span>
                </span>

                <span class="reqv-group__status">
                    <span
                        class="reqv-group__count {{ $extPassed === $extTotal ? 'reqv-group__count--ready' : 'reqv-group__count--missing' }}"
                    >
                        {{ $number($extPassed) }} / {{ $number($extTotal) }}
                    </span>

                    <span
                        class="reqv-group__chevron"
                        aria-hidden="true"
                    >
                        <x-vuero.icon name="chevron-down" size="15" />
                    </span>
                </span>

            </summary>

            <div class="reqv-group__content reqv-group__content--extensions">

                @foreach ($extensionPairs as $column)
                    <ul class="reqv-list reqv-list--compact">

                        @foreach ($column as [$check, $passed])
                            @php($extensionName = substr($check, 10))

                            <x-installation.requirement-row
                                variant="compact"
                                :label="$extensionName"
                                :passed="$passed"
                            />
                        @endforeach

                    </ul>
                @endforeach

            </div>
        </details>

    </div>
        <aside class="reqv-aside"><div class="reqv-context">
            <span class="reqv-group__icon" aria-hidden="true"><x-vuero.icon name="server" size="24" /></span>
            <h3>{{ __('installation.'.($ready ? 'requirements_server_ready' : 'requirements_attention')) }}</h3>
            <p>{{ __('installation.'.($ready ? 'requirements_ready_hint' : 'requirements_failed')) }}</p>
        </div>
        <x-installation.help-card />
        <p class="installation-handnote installation-handnote--footer">{{ __('installation.small_steps_note') }}</p>
        </aside>
    </div>
</section>
