<div class="creative-stage login-access-stage" data-creative-stage data-palette="mint">
    <div class="creative-stage__scene" aria-hidden="true">
        <div class="creative-stage__halo"></div>
        <svg class="recovery-art" viewBox="0 0 600 420" fill="none">
            <defs>
                <linearGradient id="login-portal" x1="224" y1="85" x2="380" y2="335" gradientUnits="userSpaceOnUse"><stop stop-color="var(--art-soft)"/><stop offset="1" stop-color="var(--art-accent)"/></linearGradient>
                <linearGradient id="login-panel-glass" x1="150" y1="80" x2="430" y2="340" gradientUnits="userSpaceOnUse"><stop stop-color="var(--surface)"/><stop offset="1" stop-color="var(--art-soft)" stop-opacity=".35"/></linearGradient>
            </defs>
            <ellipse cx="301" cy="365" rx="135" ry="16" fill="var(--art-deep)" opacity=".08"/>
            <g class="login-access-orbits" stroke="var(--art-accent)" stroke-opacity=".25">
                <ellipse cx="300" cy="217" rx="232" ry="105" transform="rotate(-18 300 217)"/>
                <ellipse cx="300" cy="217" rx="216" ry="153" transform="rotate(26 300 217)" stroke-dasharray="3 11"/>
            </g>
            <g class="login-access-workspace">
                <rect x="169" y="78" width="278" height="259" rx="30" transform="rotate(8 308 207)" fill="url(#login-panel-glass)" stroke="var(--art-soft)"/>
                <rect x="154" y="90" width="292" height="250" rx="27" fill="var(--surface)" stroke="var(--art-soft)" stroke-width="2"/>
                <path d="M155 133h290" stroke="var(--art-soft)"/>
                <g fill="var(--art-accent)"><circle cx="177" cy="112" r="4"/><circle cx="191" cy="112" r="4" opacity=".5"/><circle cx="205" cy="112" r="4" opacity=".25"/></g>
                <rect x="344" y="108" width="76" height="7" rx="3.5" fill="var(--art-soft)"/>
                <rect x="171" y="151" width="49" height="169" rx="12" fill="var(--art-soft)" opacity=".45"/>
                <rect x="182" y="164" width="27" height="27" rx="8" fill="var(--art-accent)"/>
                <path d="M184 209h23m-23 19h17m-17 19h23m-23 19h17" stroke="var(--art-deep)" stroke-width="4" stroke-linecap="round" opacity=".35"/>
                <rect x="237" y="151" width="188" height="91" rx="17" fill="url(#login-portal)"/>
                <circle cx="276" cy="195" r="24" fill="var(--surface)" opacity=".9"/>
                <circle cx="276" cy="189" r="8" fill="var(--art-accent)"/>
                <path d="M261 209a15 15 0 0 1 30 0" fill="var(--art-accent)"/>
                <path d="M316 185h80m-80 15h56m-56 15h37" stroke="var(--art-deep)" stroke-width="5" stroke-linecap="round" opacity=".5"/>
                <rect x="237" y="257" width="86" height="62" rx="13" fill="var(--art-soft)" opacity=".5"/>
                <path d="M251 300v-15m13 15v-27m13 27v-20m13 20v-33m13 33v-25" stroke="var(--art-accent)" stroke-width="6" stroke-linecap="round"/>
                <rect x="338" y="257" width="87" height="62" rx="13" fill="var(--art-soft)" opacity=".3"/>
                <circle cx="365" cy="288" r="15" stroke="var(--art-accent)" stroke-width="6" stroke-dasharray="65 30" transform="rotate(-90 365 288)"/>
                <path d="M391 282h20m-20 12h14" stroke="var(--art-deep)" stroke-width="4" stroke-linecap="round" opacity=".4"/>
            </g>
            <g class="login-access-profile" transform="translate(-18 -26)">
                <rect x="76" y="111" width="136" height="111" rx="24" fill="var(--surface)" stroke="var(--art-soft)"/>
                <circle cx="114" cy="148" r="16" fill="var(--art-soft)"/>
                <circle cx="114" cy="144" r="6" fill="var(--art-accent)"/>
                <path d="M103 157a11 11 0 0 1 22 0" fill="var(--art-accent)"/>
                <path d="M144 141h43m-43 13h29M99 188h53" stroke="var(--art-accent)" stroke-width="5" stroke-linecap="round" opacity=".65"/>
                <circle cx="184" cy="190" r="12" fill="var(--art-accent)"/><path d="m179 190 4 4 7-8" stroke="white" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
            </g>
            <g class="login-access-entry">
                <rect x="355" y="248" width="161" height="84" rx="25" fill="var(--surface)" stroke="var(--art-soft)"/>
                <path d="M385 290h86m-18-18 18 18-18 18" stroke="var(--art-accent)" stroke-width="6" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M484 272v36" stroke="var(--art-soft)" stroke-width="5" stroke-linecap="round"/>
            </g>
            <g fill="var(--art-accent)"><circle cx="457" cy="141" r="5"/><circle cx="138" cy="292" r="4"/><path d="m459 182 4 10 10 4-10 4-4 10-4-10-10-4 10-4Z"/><path d="m184 316 3 8 8 3-8 3-3 8-3-8-8-3 8-3Z"/></g>
        </svg>
    </div>
    <div class="creative-stage__caption">
        <div class="creative-palette" role="group" aria-label="{{ __('auth.screen.palette_hint') }}">
            @foreach(['mint', 'violet', 'sunset'] as $palette)
                <button type="button" class="creative-palette__swatch creative-palette__swatch--{{ $palette }}" data-creative-palette="{{ $palette }}" aria-label="{{ __('auth.screen.palette_'.$palette) }}" aria-pressed="{{ $palette === 'mint' ? 'true' : 'false' }}"></button>
            @endforeach
        </div>
    </div>
</div>
