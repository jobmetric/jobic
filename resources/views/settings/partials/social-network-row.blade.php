@php($exampleUrl = !empty($item['type']) && !empty($item['slug']) ? app(\App\Support\SocialNetworks\SocialNetworkRegistry::class)->url($item['type'], $item['slug']) : null)
<div class="cms-social-network-row" data-social-network-row>
    <div class="cms-social-network-row__icon" data-social-network-icon aria-hidden="true">
        <x-vuero.icon :name="$networks[$item['type'] ?? '']['icon'] ?? 'share'" size="21" />
    </div>
    <x-form.field :label="__('settings/social_networks.network_type')" :for="'social-network-type-'.$index">
        <x-form.select :id="'social-network-type-'.$index" :name="'networks['.$index.'][type]'" placeholder data-social-network-type>
            @foreach($networks as $network)
                <option value="{{ $network['key'] }}" data-icon="{{ $network['icon'] }}" data-url-template="{{ $network['url_template'] }}" @selected(($item['type'] ?? '') === $network['key'])>{{ trans($network['name']) }}</option>
            @endforeach
        </x-form.select>
    </x-form.field>
    <x-form.field :label="__('settings/social_networks.slug')" :for="'social-network-slug-'.$index" data-social-network-slug-field :hidden="empty($item['type'])">
        <x-form.input :id="'social-network-slug-'.$index" :name="'networks['.$index.'][slug]'" :value="$item['slug'] ?? ''" data-social-network-slug autocomplete="off" />
    </x-form.field>
    <button type="button" class="cms-social-network-row__remove" data-social-network-remove aria-label="{{ __('settings/social_networks.remove') }}"><x-vuero.icon name="trash" size="17" /></button>
    <aside class="cms-social-network-row__example" data-social-network-example @if(!$exampleUrl) hidden @endif>
        <span><x-vuero.icon name="link" size="15" /></span>
        <small data-social-network-example-text>{{ $exampleUrl ? __('settings/social_networks.address_example', ['url' => $exampleUrl]) : '' }}</small>
    </aside>
</div>
