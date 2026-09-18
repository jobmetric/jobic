<div class="login-universe" aria-hidden="true">
    <div class="login-nebula login-nebula--mint"></div>
    <div class="login-nebula login-nebula--violet"></div>
    <svg class="login-orbital-map" viewBox="0 0 800 800" fill="none">
        <defs>
            <linearGradient id="login-orbit-gradient" x1="80" y1="100" x2="720" y2="680" gradientUnits="userSpaceOnUse"><stop stop-color="#41d9ba" stop-opacity=".7"/><stop offset=".5" stop-color="#8585ef" stop-opacity=".1"/><stop offset="1" stop-color="#68dcca" stop-opacity=".6"/></linearGradient>
        </defs>
        <g class="login-orbit-track login-orbit-track--one"><ellipse cx="400" cy="400" rx="330" ry="210" stroke="url(#login-orbit-gradient)"/><circle cx="730" cy="400" r="5" fill="#43cdb1"/><circle cx="70" cy="400" r="3" fill="#9290f5"/></g>
        <g class="login-orbit-track login-orbit-track--two"><ellipse cx="400" cy="400" rx="300" ry="245" stroke="url(#login-orbit-gradient)" stroke-dasharray="2 12"/><circle cx="400" cy="155" r="4" fill="#9a96f8"/></g>
        <circle cx="400" cy="400" r="345" stroke="url(#login-orbit-gradient)" stroke-opacity=".25"/>
        <path d="M165 270 235 185 358 226M546 585l76-57 45 72" stroke="url(#login-orbit-gradient)"/>
        <g fill="#75bdb5"><circle cx="165" cy="270" r="3"/><circle cx="235" cy="185" r="4"/><circle cx="358" cy="226" r="3"/><circle cx="546" cy="585" r="3"/><circle cx="622" cy="528" r="4"/><circle cx="667" cy="600" r="3"/></g>
        <g class="login-constellations" stroke="var(--primary)" stroke-width="1" stroke-opacity=".3">
            <path d="m70 135 55-42 58 22 36-55m-94 33 15 66 43-44M550 80l60 38 63-19 52 57-37 49m-78-87-24 60 102 27M42 390l49-37 36 62-26 70-55 12m81-82 40 14M604 340l74-29 64 44-19 60-45-104M65 641l68-34 52 69-25 71m-27-140 43-62M405 663l62 54 66-28 29 54m-95-26-10 54M270 65l54 38 43-46 52 38M700 550l-42 54 56 46 40-41"/>
            <g fill="var(--primary)" stroke="none" opacity=".65">
                @foreach([[70,135],[125,93],[183,115],[219,60],[140,159],[550,80],[610,118],[673,99],[725,156],[688,205],[586,178],[42,390],[91,353],[127,415],[101,485],[46,497],[167,429],[604,340],[678,311],[742,355],[723,415],[65,641],[133,607],[185,676],[160,747],[176,545],[405,663],[467,717],[533,689],[562,743],[457,771],[270,65],[324,103],[367,57],[419,95],[700,550],[658,604],[714,650],[754,609]] as [$x, $y])
                    <circle cx="{{ $x }}" cy="{{ $y }}" r="{{ $loop->index % 3 === 0 ? 3.5 : 2 }}" />
                @endforeach
            </g>
        </g>
    </svg>
    <div class="login-starfield" data-starfield></div>
    <div class="login-floating-glyph login-floating-glyph--one"><x-vuero.icon name="content" size="24" /></div>
    <div class="login-floating-glyph login-floating-glyph--two"><x-vuero.icon name="puzzle" size="23" /></div>
    <div class="login-floating-glyph login-floating-glyph--three">✦</div>
</div>
