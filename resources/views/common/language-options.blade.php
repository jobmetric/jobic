<div class="cms-language-grid" role="group" aria-label="{{ __('admin.language') }}">
    @foreach(['en' => ['English', 'English', 'us'], 'fa' => ['فارسی', 'Persian', 'ir']] as $locale => [$name, $subtitle, $flag])
        <button class="cms-language-option {{ app()->getLocale() === $locale ? 'is-active' : '' }}" type="{{ ($submit ?? false) ? 'submit' : 'button' }}" name="locale" value="{{ $locale }}" data-cms-locale="{{ $locale }}" aria-pressed="{{ app()->getLocale() === $locale ? 'true' : 'false' }}">
            <span class="country-flag country-flag--{{ $flag }}" aria-hidden="true"></span><strong>{{ $name }}</strong><small>{{ $subtitle }}</small><span class="cms-language-check"><x-vuero.icon name="check" size="14" /></span>
        </button>
    @endforeach
</div>
