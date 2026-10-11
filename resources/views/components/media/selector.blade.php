@props(['collection', 'multiple' => false, 'groups' => ['image'], 'selected' => collect()])
<div class="taxonomy-media-picker" data-media-picker data-collection="{{ $collection }}" data-multiple="{{ $multiple ? 'true' : 'false' }}" data-groups='@json($groups)'>
    <input id="media-{{ $collection }}" type="hidden" name="media_selection[{{ $collection }}]" value='@json($selected->pluck('id')->values())'>
    <div class="taxonomy-media-picker__items" data-media-selected>
        @foreach($selected as $media)<article data-media-id="{{ $media['id'] }}" data-media-record='@json($media)'><button type="button" class="media-selector-preview" data-media-open aria-label="{{ __('taxonomies.media_replace') }}">@if($media['src'])<img src="{{ $media['src'] }}" alt="{{ $media['name'] }}">@else<x-vuero.icon name="file" size="25" />@endif</button><span title="{{ $media['name'] }}">{{ $media['name'] }}</span><button type="button" data-media-remove aria-label="{{ __('taxonomies.media_remove') }}"><x-vuero.icon name="trash" size="16" /></button></article>@endforeach
    </div>
    <button class="taxonomy-media-picker__open" type="button" data-media-open @if(!$multiple && $selected->isNotEmpty()) hidden @endif><span><x-vuero.icon name="media" size="18" /></span><strong>{{ __('taxonomies.media_choose') }}</strong><small>{{ __('taxonomies.media_choose_hint') }}</small></button>
</div>
