@php($language = \App\Support\LocaleCatalog::all()[$locale] ?? null)
@php($flagUrl = \App\Support\LocaleCatalog::flagUrl($language['flag'] ?? null))
@if($flagUrl)
    <span class="country-flag cms-package-flag" style="--language-flag: url('{{ $flagUrl }}')" aria-hidden="true"></span>
@else
    <span class="country-flag cms-locale-mark" aria-hidden="true">{{ strtoupper($locale) }}</span>
@endif
