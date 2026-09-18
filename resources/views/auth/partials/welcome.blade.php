        <section class="auth-visual login-welcome" data-login-universe>
            @include('auth.partials.universe')
            <div class="login-welcome__content">
                <h2>{{ __($recoveryArt ?? false ? 'auth.recovery.story_title' : 'auth.screen.story_title') }}</h2>
                @if($recoveryArt ?? false)
                    @include('auth.partials.recovery-stage')
                @else
                    @include('auth.partials.login-stage')
                @endif

            </div>
        </section>

