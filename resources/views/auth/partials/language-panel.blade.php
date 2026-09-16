        <section class="side-panel-layer" id="languages-panel" data-side-panel="languages" aria-hidden="true">
            <button class="side-panel-layer__overlay" type="button" tabindex="-1" data-panel-close aria-label="{{ __('admin.control_close') }}"></button>
            <aside class="side-panel language-panel" role="dialog" aria-modal="true" aria-labelledby="languages-title" tabindex="-1">
                <header class="side-panel__header"><h2 id="languages-title">{{ __('admin.language') }}</h2><button class="side-panel__close" type="button" data-panel-close aria-label="{{ __('admin.control_close') }}"><x-vuero.icon name="chevron-right" size="20" /></button></header>
                <div class="cms-panel-body"><p class="cms-language-hint">{{ __('auth.screen.language_hint') }}</p>
                    <form action="{{ route($languageRoute ?? 'login.language') }}" method="POST" data-auth-language>@csrf
                        @include('common.language-options', ['submit' => true])
                    </form>
                </div>
            </aside>
        </section>
