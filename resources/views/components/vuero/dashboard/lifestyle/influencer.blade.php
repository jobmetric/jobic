<div class="lifestyle-page lifestyle-influencer" data-lifestyle="influencer" data-active-tab="overview">
    <x-vuero.dashboard.lifestyle.influencer.header />

    <nav class="lifestyle-tabs" role="tablist" data-influencer-tabs aria-label="Influencer dashboard sections">
        <button type="button" class="is-active" role="tab" aria-selected="true" aria-controls="influencer-overview" data-tab="overview">Overview</button>
        <button type="button" role="tab" aria-selected="false" aria-controls="influencer-content" data-tab="content">Content</button>
        <button type="button" role="tab" aria-selected="false" aria-controls="influencer-brands" data-tab="brands">Brands</button>
    </nav>

    <x-vuero.dashboard.lifestyle.influencer.overview />
    <x-vuero.dashboard.lifestyle.influencer.content />
    <x-vuero.dashboard.lifestyle.influencer.brands />
</div>
