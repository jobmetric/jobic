<section class="crud-capability-card crud-menu-link" data-menu-link data-fields-url="{{ route('admin.taxonomies.link-fields', [$type, '__DRIVER__']) }}">
    <header><div><h2>{{ __('taxonomies.link_title') }}</h2><p>{{ __('taxonomies.link_hint') }}</p></div><x-vuero.icon name="link" size="22" /></header>
    <x-form.field variant="crud" :label="__('taxonomies.link_driver')" for="menu-link-driver" error="menu_link_driver">
        <x-form.select id="menu-link-driver" name="menu_link_driver" data-menu-link-driver>
            @foreach($registry->all() as $key => $item)<option value="{{ $key }}" @selected($driver === $key)>{{ $item->label() }}</option>@endforeach
        </x-form.select>
    </x-form.field>
    <div class="crud-menu-link-fields" data-menu-link-fields>
        @include('taxonomies.partials.menu-link-fields', compact('registry', 'driver', 'values'))
    </div>
    <div class="crud-loading" data-menu-link-loading hidden><span class="crud-spinner"></span><span>{{ __('taxonomies.link_loading') }}</span></div>
</section>
