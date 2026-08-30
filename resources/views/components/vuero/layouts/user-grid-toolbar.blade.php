@props(['filterOptions' => null, 'filterSelected' => 'All', 'ctaLabel' => null, 'variant' => 'default'])

<header @class(['user-grid-toolbar', 'is-reversed tabs-wrapper is-slider' => $variant === 'tabs'])>
    <label class="user-grid-search">
        <span class="sr-only">Search</span>
        <x-vuero.icon name="search" size="17" />
        <input type="search" class="input custom-text-filter" placeholder="Search..." autocomplete="off" data-user-grid-filter>
        <button type="button" aria-label="Clear search" data-user-grid-filter-clear hidden><x-vuero.icon name="x" size="15" /></button>
    </label>

    @if ($variant === 'tabs')
        <div class="tabs-inner">
            <div class="tabs">
                <ul>
                    <li class="is-active" data-user-grid-tab-item="all">
                        <button type="button" role="tab" aria-selected="true" data-user-grid-tab="all"><span>All</span></button>
                    </li>
                    <li data-user-grid-tab-item="team">
                        <button type="button" role="tab" aria-selected="false" data-user-grid-tab="team"><span>Team</span></button>
                    </li>
                    <li class="tab-naver" aria-hidden="true"></li>
                </ul>
            </div>
        </div>
    @else
        <div class="buttons">
            @if ($filterOptions)
                <div class="field h-hidden-mobile user-grid-multiselect">
                    <x-vuero.forms.select :options="$filterOptions" :selected="$filterSelected" class="user-grid-filter" />
                </div>
            @endif
            @if ($ctaLabel)
                <button type="button" class="button v-button is-primary is-raised" data-user-grid-cta>
                    <span class="icon"><x-vuero.icon name="plus" size="14" /></span>
                    <span>{{ $ctaLabel }}</span>
                </button>
            @endif
        </div>
    @endif
</header>
