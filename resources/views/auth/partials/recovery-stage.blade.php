<div class="creative-stage recovery-stage" data-creative-stage data-palette="mint">
    <div class="creative-stage__scene" aria-hidden="true">
        <div class="creative-stage__halo"></div>
        <svg class="recovery-art" viewBox="0 0 600 420" fill="none">
            <defs>
                <linearGradient id="recovery-shield" x1="200" y1="100" x2="410" y2="340" gradientUnits="userSpaceOnUse"><stop stop-color="var(--art-soft)"/><stop offset="1" stop-color="var(--art-accent)"/></linearGradient>
                <linearGradient id="recovery-glass" x1="140" y1="90" x2="420" y2="310" gradientUnits="userSpaceOnUse"><stop stop-color="var(--surface)"/><stop offset="1" stop-color="var(--art-soft)" stop-opacity=".3"/></linearGradient>
            </defs>
            <ellipse cx="300" cy="362" rx="132" ry="16" fill="var(--art-deep)" opacity=".08"/>
            <g class="recovery-art__orbit" stroke="var(--art-accent)" stroke-opacity=".25"><ellipse cx="300" cy="216" rx="245" ry="106" transform="rotate(-23 300 216)"/><ellipse cx="300" cy="216" rx="215" ry="156" transform="rotate(24 300 216)" stroke-dasharray="4 12"/></g>
            <g class="recovery-art__shield">
                <rect x="162" y="72" width="275" height="275" rx="42" transform="rotate(-8 300 210)" fill="url(#recovery-glass)" stroke="var(--art-soft)" stroke-opacity=".7"/>
                <path d="M300 105c38 27 65 32 91 39v73c0 61-40 101-91 127-51-26-91-66-91-127v-73c26-7 53-12 91-39Z" fill="url(#recovery-shield)" stroke="var(--art-soft)" stroke-width="3"/>
                <path d="M300 125c29 20 51 26 72 32v58c0 49-30 83-72 106-42-23-72-57-72-106v-58c21-6 43-12 72-32Z" stroke="white" stroke-opacity=".5" stroke-width="2"/>
                <circle cx="300" cy="213" r="26" fill="var(--art-deep)"/>
                <path d="m291 231-5 28h28l-5-28" fill="var(--art-deep)"/>
                <path d="m324 142 11 4" stroke="white" stroke-width="5" stroke-linecap="round" opacity=".7"/>
            </g>
            <g class="recovery-art__key">
                <rect x="352" y="255" width="166" height="80" rx="24" fill="var(--surface)" stroke="var(--art-soft)"/>
                <circle cx="393" cy="295" r="16" stroke="var(--art-accent)" stroke-width="7"/>
                <path d="M410 295h68m-15 0v14m-16-14v10" stroke="var(--art-accent)" stroke-width="7" stroke-linecap="round"/>
            </g>
            <g class="recovery-art__mail">
                <rect x="76" y="98" width="112" height="88" rx="23" fill="var(--surface)" stroke="var(--art-soft)"/>
                <rect x="99" y="120" width="66" height="44" rx="8" stroke="var(--art-accent)" stroke-width="3"/>
                <path d="m100 124 32 23 32-23" stroke="var(--art-accent)" stroke-width="3" stroke-linejoin="round"/>
                <circle cx="171" cy="110" r="13" fill="var(--art-accent)"/><path d="m165 110 4 4 8-9" stroke="white" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
            </g>
            <g fill="var(--art-accent)"><circle cx="457" cy="119" r="5"/><circle cx="124" cy="286" r="4"/><path d="m459 182 4 10 10 4-10 4-4 10-4-10-10-4 10-4Z"/><path d="m185 328 3 8 8 3-8 3-3 8-3-8-8-3 8-3Z"/></g>
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
