@php
    $items = is_array($field->getValue()) ? array_values($field->getValue()) : [];
    $items = $items ?: [['type' => '', 'slug' => '']];
    $networks = app(\App\Support\SocialNetworks\SocialNetworkRegistry::class)->all();
@endphp
<div class="cms-social-networks" data-social-networks
     data-address-example="{{ __('settings/social_networks.address_example', ['url' => '{url}']) }}">
    <div class="cms-social-networks__list" data-social-network-list>
        @foreach($items as $index => $item)
            @include('settings.partials.social-network-row', [
                'index' => $index,
                'item' => $item,
                'networks' => $networks,
            ])
        @endforeach
    </div>
    <button type="button" class="vuero-button cms-social-networks__add" data-social-network-add>
        <x-vuero.icon name="plus" size="16" />{{ __('settings/social_networks.add') }}
    </button>
    <template data-social-network-template>
        @include('settings.partials.social-network-row', [
            'index' => '__INDEX__',
            'item' => [
                'type' => '',
                'slug' => '',
            ], 'networks' => $networks,
        ])
    </template>
    @if($hasErrorTagForm)
        @error($field->getNameDot())<small class="custom-field-error">{{ $message }}</small>@enderror
    @endif
</div>
