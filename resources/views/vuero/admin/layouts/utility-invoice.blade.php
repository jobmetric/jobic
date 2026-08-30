<x-vuero.layouts.app title="Invoice" :script="$layoutScript">
    <section class="layout-page invoice-wrapper" data-utility-invoice aria-label="Invoice">
        <div class="invoice-header">
            <div class="left">
                <h3>Invoice FC-456-14</h3>
            </div>
            <div class="right">
                <div class="controls">
                    <button type="button" class="action" data-invoice-action="print" aria-label="Print invoice">
                        <x-vuero.icon name="printer" size="16" />
                    </button>
                    <button type="button" class="action" data-invoice-action="download" aria-label="Download invoice">
                        <x-vuero.icon name="download" size="16" />
                    </button>
                    <button type="button" class="action" data-invoice-action="mail" aria-label="Email invoice">
                        <x-vuero.icon name="mail" size="16" />
                    </button>
                    <button type="button" class="action" data-invoice-action="back" aria-label="Go back">
                        <x-vuero.icon name="arrow-left" size="16" />
                    </button>
                </div>
            </div>
        </div>

        <div class="invoice-body">
            <div class="invoice-card">
                <div class="invoice-section is-flex is-bordered">
                    <span class="invoice-user-popover" tabindex="0" aria-describedby="invoice-user-card">
                        <x-vuero.layouts.flex-avatar size="large" picture="/vuero/images/layouts/list-view-v1/avatars/13.jpg" />
                        <span id="invoice-user-card" class="invoice-user-card" role="tooltip">
                            <strong>Tara Svenson</strong><small>Project Manager</small><span>San Diego, CA</span>
                        </span>
                    </span>
                    <div class="meta">
                        <h3>Tara Svenson</h3>
                        <span>tarasvenson@vuero.io</span>
                        <span>+1 546-5491</span>
                    </div>
                    <div class="end">
                        <h3>Invoice FC-456-14</h3>
                        <span>Issued: May 27, 2020</span>
                        <span>Payment Due: June 27, 2015</span>
                    </div>
                </div>

                <div class="invoice-section is-flex is-bordered">
                    <x-vuero.layouts.flex-avatar size="large" class="is-customer" picture="/vuero/images/layouts/invoice/airbnb.svg" squared />
                    <div class="meta">
                        <h3>Airbnb</h3>
                        <span>888 Brannan St, San Francisco,</span>
                        <span>CA 94103, USA</span>
                    </div>
                    <div class="end is-left">
                        <h3>Description</h3>
                        <p>
                            Lorem ipsum dolor sit amet, consectetur adipiscing elit. Quod equidem non
                            reprehendo.
                        </p>
                    </div>
                </div>

                <div class="invoice-section">
                    <div class="flex-table is-rounded invoice-table">
                        <div class="flex-table-header">
                            <span class="is-grow">Description</span>
                            <span>Unit</span>
                            <span>Quantity</span>
                            <span>Rate</span>
                            <span>Subtotal</span>
                        </div>
                        @foreach ($items as $item)
                            <div class="flex-table-item" data-invoice-row data-rate="{{ $item['rate'] }}">
                                <div class="flex-table-cell is-grow" data-th="Description"><span>{{ $item['name'] }}</span></div>
                                <div class="flex-table-cell cell-end" data-th="Unit"><span>{{ $item['unit'] }}</span></div>
                                <div class="flex-table-cell cell-end" data-th="Quantity">
                                    <input class="input" type="number" min="0" step="1" value="{{ $item['quantity'] }}" aria-label="Quantity for {{ $item['name'] }}" data-invoice-quantity>
                                </div>
                                <div class="flex-table-cell cell-end" data-th="Rate"><span>${{ number_format($item['rate'], 0) }}</span></div>
                                <div class="flex-table-cell cell-end" data-th="Subtotal"><span data-invoice-subtotal></span></div>
                            </div>
                        @endforeach
                    </div>

                    <div class="flex-table invoice-totals">
                        <div class="flex-table-item">
                            <div class="flex-table-cell is-grow cell-end"><span class="table-label">Subtotal</span></div>
                            <div class="flex-table-cell cell-end"><span class="table-value" data-invoice-subtotal-value></span></div>
                        </div>
                        <div class="flex-table-item">
                            <div class="flex-table-cell is-grow cell-end"><span class="table-label">Taxes</span></div>
                            <div class="flex-table-cell cell-end"><span class="table-value" data-invoice-tax-value></span></div>
                        </div>
                        <div class="flex-table-item">
                            <div class="flex-table-cell is-grow cell-end"><span class="table-label">Total</span></div>
                            <div class="flex-table-cell cell-end"><span class="table-total is-bigger" data-invoice-total-value></span></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</x-vuero.layouts.app>
