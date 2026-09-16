<div class="creative-stage" data-creative-stage data-palette="mint">
    <div class="creative-stage__scene" aria-hidden="true">
        <div class="creative-stage__halo"></div>
        <div class="creative-stage__sheet creative-stage__sheet--back"></div>
        <div class="creative-browser">
            <div class="creative-browser__chrome"><span></span><span></span><span></span><i></i><x-brand compact /></div>
            <div class="creative-browser__page">
                <div class="creative-browser__nav"><b></b><span></span><span></span><span></span></div>
                <div class="creative-browser__hero">
                    <div class="creative-browser__copy"><i></i><b></b><b></b><span></span><span></span><em></em></div>
                    <div class="creative-sculpture"><div class="creative-sculpture__ring"></div><div class="creative-sculpture__orb"></div><div class="creative-sculpture__spark">✦</div></div>
                </div>
                <div class="creative-browser__tiles"><i></i><i></i><i></i></div>
            </div>
        </div>
        <div class="creative-float creative-float--type"><span>Aa</span><i></i><i></i></div>
        <div class="creative-float creative-float--image"><svg viewBox="0 0 120 100" fill="none"><circle cx="83" cy="28" r="12" fill="currentColor" opacity=".7"/><path d="M0 100L44 34L89 100Z" fill="currentColor"/><path d="M50 100L90 54L120 100Z" fill="currentColor" opacity=".45"/></svg></div>
        <div class="creative-cursor"><svg viewBox="0 0 24 24"><path d="m4 3 6 18 3-8 8-3Z" fill="currentColor" stroke="white" stroke-width="1.5"/></svg><span>Jobic</span></div>
    </div>
    <div class="creative-stage__caption">
        <p>{{ __('auth.screen.canvas_title') }}</p>
        <div class="creative-palette" role="group" aria-label="{{ __('auth.screen.palette_hint') }}">
            @foreach (['mint', 'violet', 'sunset'] as $palette)
                <button type="button" class="creative-palette__swatch creative-palette__swatch--{{ $palette }}" data-creative-palette="{{ $palette }}" aria-label="{{ __('auth.screen.palette_' . $palette) }}" aria-pressed="{{ $palette === 'mint' ? 'true' : 'false' }}"></button>
            @endforeach
        </div>
    </div>
</div>
