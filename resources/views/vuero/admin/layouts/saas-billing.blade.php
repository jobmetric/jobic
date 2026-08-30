<x-vuero.layouts.app title="SaaS Billing" :script="$layoutScript">
    <section class="layout-page saas-billing-wrapper" data-saas-billing aria-label="SaaS Billing">
        <div class="plans-wrapper">
            <div class="left">
                <div class="inner-wrap">
                    <h3>Select a Plan</h3>
                    <div class="plans">
                        @foreach ($plans as $plan)
                            <label class="plan" data-plan-option>
                                <input
                                    type="radio"
                                    name="plan_selection"
                                    value="{{ $plan['id'] }}"
                                    @checked($plan['id'] === 'starter')
                                >
                                <span class="plan-inner">
                                    <img src="{{ $plan['icon'] }}" alt="">
                                    <span class="meta">
                                        <span>{{ $plan['name'] }}</span>
                                        <span>{{ $plan['slogan'] }}</span>
                                    </span>
                                    <span class="checkmark" aria-hidden="true">
                                        <x-vuero.icon name="check" size="16" />
                                    </span>
                                </span>
                            </label>
                        @endforeach
                    </div>
                </div>
            </div>

            <div class="right">
                <div class="plan-details">
                    @foreach ($plans as $plan)
                        <div class="plan-details-inner" data-plan-panel="{{ $plan['id'] }}" @if ($plan['id'] !== 'starter') hidden @endif>
                            <div class="plan-description">
                                <div class="left">
                                    <img class="light-image" src="{{ $plan['logo']['light'] }}" alt="">
                                    <img class="dark-image" src="{{ $plan['logo']['dark'] }}" alt="">
                                </div>
                                <div class="right">
                                    <div class="plan-pricing">
                                        <span><strong>{{ $plan['costs']['monthly'] }}</strong> per month</span>
                                        <span>Billed {{ $plan['costs']['yearly'] }} yearly</span>
                                    </div>
                                    <div class="plan-moto">
                                        <p>{{ $plan['intro'] }}</p>
                                    </div>
                                </div>
                            </div>
                            <div class="plan-summary">
                                <p>{{ $plan['summary'] }}</p>
                                <div class="plan-features">
                                    <div class="columns">
                                        <div class="column is-6">
                                            <div class="content">
                                                <ul>
                                                    <li>{{ $plan['limits']['seats'] }} seats</li>
                                                    <li>{{ $plan['limits']['projects'] }} projects</li>
                                                    <li>{{ $plan['limits']['storage'] }} storage</li>
                                                </ul>
                                            </div>
                                        </div>
                                        <div class="column is-6">
                                            <div class="content">
                                                <ul>
                                                    <li>Live updates</li>
                                                    <li>Payments</li>
                                                    <li>{{ $plan['limits']['addons'] }} Addons</li>
                                                </ul>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="billing-options-wrapper">
            <div class="left">
                <div class="option-block">
                    <div class="block-header">
                        <h3>Team Seats</h3>
                        <span>3 remaining</span>
                    </div>
                    <div class="block-body is-seats">
                        <x-vuero.layouts.flex-avatar picture="/vuero/images/layouts/list-view-v1/avatars/vuero-1.svg" />
                        <x-vuero.layouts.flex-avatar initials="BT" color="warning" />
                        <x-vuero.layouts.flex-avatar picture="/vuero/images/layouts/list-view-v1/avatars/18.jpg" />
                        <x-vuero.layouts.flex-avatar initials="JD" color="info" />
                        <x-vuero.layouts.flex-avatar picture="/vuero/images/layouts/list-view-v1/avatars/7.jpg" />
                        <button type="button" class="add-seat" data-billing-add-seat aria-label="Add seat">
                            <x-vuero.icon name="plus" size="16" />
                        </button>
                    </div>
                </div>

                <div class="option-block">
                    <div class="block-header">
                        <h3>Options</h3>
                    </div>
                    <div class="block-body">
                        <div class="switch-block">
                            <label for="invoice_inbox">Send new invoices to my inbox</label>
                            <label class="form-switch is-primary"><input id="invoice_inbox" type="checkbox" name="invoice_inbox"><i aria-hidden="true"></i></label>
                        </div>
                        <div class="switch-block">
                            <label for="billing_warn">Warn me before the end of the billing period</label>
                            <label class="form-switch is-primary"><input id="billing_warn" type="checkbox" name="billing_warn"><i aria-hidden="true"></i></label>
                        </div>
                    </div>
                </div>

                <div class="option-block">
                    <div class="block-header">
                        <h3>Billing Cycle</h3>
                        <a href="#">My Invoices</a>
                    </div>
                    <div class="block-body">
                        <div class="layout-radio-group">
                            <label class="layout-radio">
                                <input type="radio" name="cycle_radio" value="monthly">
                                <span>Monthly</span>
                            </label>
                            <label class="layout-radio">
                                <input type="radio" name="cycle_radio" value="yearly" checked>
                                <span>Yearly</span>
                            </label>
                        </div>
                    </div>
                </div>
            </div>

            <div class="right">
                <div class="payment-form">
                    <div class="form-header">
                        <h3>Payment information</h3>
                        <span
                            tabindex="0"
                            role="button"
                            data-billing-randomize
                        >Randomize</span>
                    </div>

                    <x-vuero.layouts.credit-card />

                    <div class="form-container">
                        <div class="columns is-multiline">
                            <div class="column is-12">
                                <div class="field">
                                    <label for="billing-card-name">Name</label>
                                    <div class="control">
                                        <input class="input" id="billing-card-name" type="text" maxlength="20" autocomplete="cc-name" placeholder="The name on the card" data-credit-card-name-input>
                                    </div>
                                </div>
                            </div>
                            <div class="column is-12">
                                <div class="field">
                                    <label for="billing-card-number">Card Number</label>
                                    <div class="control">
                                        <input class="input" id="billing-card-number" type="text" inputmode="numeric" autocomplete="cc-number" placeholder="Credit card number" data-credit-card-number-input>
                                        <div class="creditcardIcon" data-credit-card-icon></div>
                                    </div>
                                </div>
                            </div>
                            <div class="column is-6">
                                <div class="field">
                                    <label for="billing-card-expiry">Expiration</label>
                                    <div class="control">
                                        <input class="input" id="billing-card-expiry" type="text" inputmode="numeric" autocomplete="cc-exp" placeholder="MM / YY" data-credit-card-expiry-input>
                                    </div>
                                </div>
                            </div>
                            <div class="column is-6">
                                <div class="field">
                                    <label for="billing-card-cvc">CVC</label>
                                    <div class="control">
                                        <input class="input" id="billing-card-cvc" type="text" inputmode="numeric" autocomplete="cc-csc" placeholder="3 digits code" data-credit-card-cvc-input>
                                    </div>
                                </div>
                            </div>
                            <div class="column is-12">
                                <div class="button-wrap">
                                    <button type="button" class="v-button is-primary is-raised is-fullwidth" data-billing-save>Save Payment Method</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</x-vuero.layouts.app>
