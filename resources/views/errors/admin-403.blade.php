@extends('common.admin')
@section('title', __('errors.403.title'))
@section('hide-page-title', 'true')
@section('content')
    <section class="admin-access-denied" role="alert" aria-labelledby="access-denied-title">
        <div class="admin-access-denied__art" aria-hidden="true">
            <svg viewBox="0 0 420 270" role="img">
                <defs>
                    <linearGradient id="access-shield" x1="0" y1="0" x2="1" y2="1">
                        <stop offset="0" stop-color="var(--primary)" />
                        <stop offset="1" stop-color="#0f8f76" />
                    </linearGradient>
                    <linearGradient id="access-card" x1="0" y1="0" x2="0" y2="1">
                        <stop offset="0" stop-color="var(--surface)" />
                        <stop offset="1" stop-color="var(--surface-soft)" />
                    </linearGradient>
                    <filter id="access-shadow" x="-30%" y="-30%" width="160%" height="170%">
                        <feDropShadow dx="0" dy="14" stdDeviation="14" flood-color="#0b3150" flood-opacity=".12" />
                    </filter>
                </defs>
                <path class="admin-access-denied__orbit" d="M51 156C66 75 138 29 226 38c88 9 145 70 143 139" />
                <circle class="admin-access-denied__dot admin-access-denied__dot--one" cx="52" cy="155" r="7" />
                <circle class="admin-access-denied__dot admin-access-denied__dot--two" cx="367" cy="178" r="5" />
                <g filter="url(#access-shadow)">
                    <rect x="78" y="55" width="264" height="164" rx="25" fill="url(#access-card)" />
                    <path class="admin-access-denied__frame" d="M78 91h264" />
                    <circle cx="101" cy="74" r="4" class="admin-access-denied__window-dot" />
                    <circle cx="115" cy="74" r="4" class="admin-access-denied__window-dot" />
                    <circle cx="129" cy="74" r="4" class="admin-access-denied__window-dot" />
                    <path class="admin-access-denied__line" d="M106 119h78M106 139h55M106 159h68" />
                    <rect x="106" y="181" width="65" height="12" rx="6" class="admin-access-denied__button" />
                </g>
                <path d="M259 105c18 13 37 15 47 16v39c0 29-20 48-47 58-27-10-47-29-47-58v-39c10-1 29-3 47-16Z" fill="url(#access-shield)" />
                <path d="M244 158v-10a15 15 0 0 1 30 0v10m-34 0h38v29h-38Z" class="admin-access-denied__lock" />
                <circle cx="259" cy="172" r="3.5" class="admin-access-denied__keyhole" />
                <path class="admin-access-denied__spark" d="m331 43 3 9 9 3-9 3-3 9-3-9-9-3 9-3Z" />
                <path class="admin-access-denied__spark admin-access-denied__spark--small" d="m65 94 2 6 6 2-6 2-2 6-2-6-6-2 6-2Z" />
            </svg>
        </div>
        <div class="admin-access-denied__copy">
            <span>{{ __('errors.403.label') }}</span>
            <h1 id="access-denied-title">{{ __('errors.403.title') }}</h1>
            <p>{{ __('errors.403.description') }}</p>
            <small>{{ __('errors.403.hint') }}</small>
        </div>
    </section>
@endsection
