@php
    $accountOptions = [
        ['value' => '', 'label' => 'Select an account', 'icon' => asset('vuero/images/avatars/svg/vuero-1.svg')],
        ['value' => '4156 1000 56565 17', 'label' => '4156 1000 56565 17', 'icon' => asset('vuero/images/icons/dashboards/banking/bank-1.svg')],
        ['value' => '2247 2427 89975 12', 'label' => '2247 2427 89975 12', 'icon' => asset('vuero/images/icons/dashboards/banking/bank-2.svg')],
        ['value' => '2247 2427 89975 19', 'label' => '2247 2427 89975 19', 'icon' => asset('vuero/images/icons/dashboards/banking/bank-3.svg')],
    ];
    $periodOptions = collect(['Last 10 days', 'Last month', 'Last 3 months', 'Last 6 months'])
        ->map(fn ($period) => ['value' => $period, 'label' => $period])
        ->all();
@endphp

<div class="banking-dashboard banking-dashboard-v1" data-banking-v1>
    <aside class="banking-v1-sidebar">
        <article class="dashboard-card banking-v1-card banking-credit-cards-card">
            <header class="banking-card-title">
                <h2>My Cards</h2>
                <button type="button" class="banking-circle-button" aria-label="Add a card">+</button>
            </header>

            <div class="banking-card-block">
                <x-vuero.dashboard.banking.credit-card number="** 4628" type="Credit Card" tone="primary" balance="$2,834.31" />

                <div class="banking-account-info">
                    <header><h3>Information</h3><button type="button" class="dashboard-link" data-bank-edit>Edit</button></header>
                    <p><strong>Status</strong><span><i></i>Active</span></p>
                    <p><strong>Expires in</strong><span>134 days</span></p>
                    <p><strong>Type</strong><span><img src="{{ asset('vuero/images/icons/dashboards/banking/visa-squared-color.svg') }}" alt="Visa">Credit card</span></p>
                </div>

                <div class="banking-card-divider"></div>
                <x-vuero.dashboard.banking.credit-card number="** 2649" type="Debit Card" tone="info" balance="$10,563.18" />
            </div>
        </article>

        <article class="dashboard-card banking-v1-card banking-transactions-card">
            <header class="banking-card-title"><h2>Transactions</h2><button type="button" class="dashboard-link">View All</button></header>
            <div class="banking-transactions">
                <x-vuero.dashboard.banking.transaction title="Food Delivery" date="Oct 23, 2020 - 8:46pm" amount="- $41.49" tone="green" icon="box" />
                <x-vuero.dashboard.banking.transaction title="Market Earnings" date="Oct 18, 2020 - 9:11am" amount="+ $263.39" tone="orange" icon="activity" />
                <x-vuero.dashboard.banking.transaction title="Online Order" date="Oct 16, 2020 - 2:13pm" amount="- $92.00" tone="purple" icon="content" />
            </div>
        </article>
    </aside>

    <main class="banking-v1-main">
        <div class="banking-v1-top-grid">
            <div class="banking-v1-quick-stack">
                <article class="dashboard-card banking-v1-card banking-quick-transfer">
                    <header class="banking-card-title"><h2>Quick Transfer</h2></header>
                    <x-vuero.dashboard.banking.select id="quick-transfer-account" name="quick_transfer_account" :options="$accountOptions" placeholder="Select an account" />
                    <p class="banking-context">Select one of your bank accounts to transfer some funds. The transfer cannot exceed 20% of your balance.</p>
                    <button type="button" class="dashboard-link banking-end-action" data-bank-confirm>Confirm</button>
                </article>

                <article class="dashboard-card banking-v1-card banking-progress-card">
                    <header class="banking-card-title"><h2>Overall Progress</h2><strong>Lvl 3</strong></header>
                    <div class="dashboard-progress"><i style="width:65%"></i></div>
                    <p class="banking-context">This is your Vuero customer level. Reach out higher levels to unlock achievements, special gifts, and more.</p>
                    <button type="button" class="dashboard-link banking-end-action" data-bank-details>Details</button>
                </article>
            </div>

            <article class="dashboard-card banking-v1-card banking-send-money">
                <header class="banking-card-title"><h2>Send money to</h2></header>
                <div class="banking-contacts" role="list" aria-label="Recent contacts">
                    <button type="button" class="is-warning" aria-label="Select Betty" data-bank-contact>BT</button>
                    <button type="button" aria-label="Select Melany" data-bank-contact><img src="{{ asset('vuero/images/avatars/svg/vuero-1.svg') }}" alt=""></button>
                    <button type="button" class="is-info" aria-label="Select John" data-bank-contact>JD</button>
                    <button type="button" aria-label="Select Alice" data-bank-contact><img src="{{ asset('vuero/images/avatars/svg/vuero-1.svg') }}" alt=""></button>
                </div>
                <button type="button" class="banking-see-contacts" data-bank-contacts>See all Contacts <x-vuero.icon name="chevron-right" size="16" /></button>
                <x-vuero.dashboard.banking.select id="send-money-account" name="send_money_account" :options="$accountOptions" placeholder="Select an account" />
                <label class="banking-amount-label" for="banking-transfer-amount">Amount</label>
                <div class="money-input">
                    <label class="banking-currency-select" aria-label="Currency"><select name="currency"><option value="USD">$</option><option value="GBP">£</option><option value="EUR">€</option></select><x-vuero.icon name="chevron-right" size="12" /></label>
                    <input id="banking-transfer-amount" type="number" min="1" step="0.01" placeholder="Amount of money">
                </div>
                <p class="banking-context">Funds will reach destination tomorrow.</p>
                <button type="button" class="vuero-button is-primary">Send Money</button>
            </article>
        </div>

        <article class="dashboard-card banking-v1-card banking-history-card">
            <header class="banking-card-title">
                <h2>History</h2>
                <x-vuero.dashboard.banking.select id="banking-history-period" name="history_period" :options="$periodOptions" placeholder="Select an option" minimal :preselect="false" />
            </header>
            <div class="chart-legend"><span>Expenses</span><span>Earnings</span></div>
            <x-vuero.dashboard.chart height="300" />
        </article>
    </main>
</div>
