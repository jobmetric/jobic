<div class="cms-language-grid" role="group" aria-label="{{ __('admin.language') }}">
    @foreach(\App\Support\LocaleCatalog::all() as $locale => $language)
        <button class="cms-language-option {{ app()->getLocale() === $locale ? 'is-active' : '' }}" type="{{ ($submit ?? false) ? 'submit' : 'button' }}" name="locale" value="{{ $locale }}" data-cms-locale="{{ $locale }}" aria-pressed="{{ app()->getLocale() === $locale ? 'true' : 'false' }}">
            @include('common.language-mark', ['locale' => $locale])<strong>{{ $language['name'] }}</strong><small>{{ strtoupper($locale) }}</small><span class="cms-language-check"><x-vuero.icon name="check" size="14" /></span>
        </button>
    @endforeach
</div>
