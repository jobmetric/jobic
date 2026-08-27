@props(['customers'])

<section class="wizard-step" data-wizard-step="3" aria-labelledby="wizard-step-3-title" hidden>
    <div class="wizard-step__content">
        <header class="wizard-step__title"><h1 id="wizard-step-3-title">Add more details</h1><p>Add useful details to your project. You can edit this later.</p></header>
        <div class="wizard-details">
            <section class="wizard-detail-section wizard-customer"><h2>Customer</h2>
                <label class="wizard-field wizard-field--icon" data-customer-search-wrap><x-vuero.icon name="search" size="18" /><input type="search" placeholder="search..." autocomplete="off" data-customer-search></label>
                <div class="wizard-search-results" data-customer-results hidden>
                    @foreach ($customers as $customer)
                        <button type="button" data-customer-option data-customer-name="{{ $customer['name'] }}" data-customer-location="{{ $customer['location'] }}" data-customer-image="{{ asset('vuero/images/wizard/customers/'.$customer['image']) }}">
                            <img src="{{ asset('vuero/images/wizard/customers/'.$customer['image']) }}" alt=""><span><strong>{{ $customer['name'] }}</strong><small>{{ $customer['location'] }}</small></span><i><x-vuero.icon name="plus" size="15" /></i>
                        </button>
                    @endforeach
                </div>
                <div class="wizard-selected-customer" data-selected-customer hidden><img src="" alt="" data-selected-customer-image><span><strong data-selected-customer-name></strong><small data-selected-customer-location></small></span><button type="button" data-customer-remove aria-label="Remove customer"><x-vuero.icon name="x" size="15" /></button></div>
            </section>
            <section class="wizard-detail-section"><h2>Project Time Frame</h2><div class="wizard-date-range">
                <label><i><x-vuero.icon name="location" size="18" /></i><span class="sr-only">Start date</span><input type="date" data-project-start></label><span></span><label><i><x-vuero.icon name="flag" size="18" /></i><span class="sr-only">End date</span><input type="date" data-project-end></label>
            </div></section>
            <section class="wizard-detail-section"><h2>Project Budget</h2><div class="wizard-budget" role="radiogroup" aria-label="Project budget">
                @foreach (['< 5K', '< 30K', '< 100K', '100K+'] as $budget)
                    <button type="button" role="radio" aria-checked="{{ $loop->first ? 'true' : 'false' }}" class="{{ $loop->first ? 'is-active' : '' }}" data-project-budget="{{ $budget }}"><span>{{ $budget }}</span></button>
                @endforeach
            </div></section>
        </div>
    </div>
</section>
