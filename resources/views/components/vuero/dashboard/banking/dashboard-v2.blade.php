@php
    $periodOptions = collect(['Last 10 days', 'Last month', 'Last 3 months', 'Last 6 months'])
        ->map(fn ($period) => ['value' => $period, 'label' => $period])
        ->all();
@endphp

<div class="banking-dashboard banking-dashboard-v2" data-banking-v2>
    <article class="dashboard-card banking-panel banking-cards-panel">
        <section class="banking-panel__cards">
            <header class="banking-card-title"><h2>My Cards</h2></header>
            <div class="banking-cards-wrapper">
                <button type="button" class="banking-add-card" aria-label="Add a card">+</button>
                <div class="bank-cards" data-bank-cards-carousel>
                    <x-vuero.dashboard.banking.credit-card number="**** **** **** 4986" balance="$6,341.14" large />
                    <x-vuero.dashboard.banking.credit-card number="**** **** **** 3619" balance="$2,211.27" large />
                    <x-vuero.dashboard.banking.credit-card number="**** **** **** 5818" balance="$1,228.49" large />
                    <x-vuero.dashboard.banking.credit-card number="**** **** **** 7652" balance="$3,412.16" large />
                </div>
            </div>
        </section>

        <section class="banking-panel__balance">
            <header class="banking-card-title">
                <h2>Balance</h2>
                <x-vuero.dashboard.banking.select id="banking-balance-period" name="balance_period" :options="$periodOptions" placeholder="Select an option" minimal :preselect="false" />
            </header>
            <div class="banking-balance" data-bank-balance>
                <strong data-bank-balance-value>$6,341.14</strong>
                <span data-bank-balance-number>**** **** **** 4986</span>
                <div class="banking-balance__stats">
                    <p><small>Income</small><strong class="is-positive"><i><x-vuero.icon name="trending-up" size="15" /></i>+ 2,324.12</strong></p>
                    <p><small>Expense</small><strong class="is-negative"><i><x-vuero.icon name="trending-down" size="15" /></i>+ 518.41</strong></p>
                </div>
            </div>
        </section>
    </article>

    <article class="dashboard-card banking-panel banking-summary-panel">
        <section class="banking-monthly-box">
            <header class="banking-card-title"><h2>Monthly Summary</h2><button type="button" class="dashboard-link">All Reports</button></header>
            <div class="banking-monthly-content">
                <aside class="banking-monthly-totals">
                    <p><small>Income</small><strong class="is-positive">+$5,000.00</strong></p>
                    <p><small>Expenses</small><strong class="is-negative">-$263.19</strong></p>
                </aside>
                <div class="banking-monthly-chart">
                    <div class="chart-legend"><span>Cash Expenses</span><span>Card Expenses</span></div>
                    <x-vuero.dashboard.chart tone="purple" height="210" />
                </div>
            </div>
        </section>

        <section class="banking-panel__transactions">
            <header class="banking-card-title"><h2>Transactions</h2><button type="button" class="dashboard-link">View All</button></header>
            <div class="banking-transactions">
                <x-vuero.dashboard.banking.transaction title="Food Delivery" date="Oct 23, 2020 - 8:46pm" amount="- $41.49" tone="green" icon="box" />
                <x-vuero.dashboard.banking.transaction title="Market Earnings" date="Oct 18, 2020 - 9:11am" amount="+ $263.39" tone="orange" icon="activity" />
                <x-vuero.dashboard.banking.transaction title="Online Order" date="Oct 16, 2020 - 2:13pm" amount="- $92.00" tone="purple" icon="content" />
            </div>
            <footer class="button-pair"><button type="button" class="vuero-button is-primary">New</button><button type="button" class="vuero-button">Settings</button></footer>
        </section>
    </article>
</div>
