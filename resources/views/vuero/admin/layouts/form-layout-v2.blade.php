<x-vuero.layouts.app title="Form Layouts 2" :script="$layoutScript">
    <section class="vuero-form-layout" data-form-layout-v2 aria-label="Form Layouts 2">
        <form class="form-layout is-split" data-form-layout-form>
            <div class="form-outer">
                <x-vuero.forms.header title="Request Payout" submit="Request Payout" />

                <div class="form-body">
                    <div class="form-section">
                        <div class="left">
                            <h3 class="has-text-centered">Choose an amount</h3>
                            <div class="operator"><span>Or</span></div>
                            <x-vuero.forms.field>
                                <x-vuero.forms.control>
                                    <div class="radio-pills">
                                        @foreach ([20, 40, 60, 100] as $amount)
                                            <label class="radio-pill">
                                                <input type="radio" name="amount_selection" value="{{ $amount }}">
                                                <span class="radio-pill-inner"><span>${{ $amount }}</span></span>
                                            </label>
                                        @endforeach
                                    </div>
                                </x-vuero.forms.control>
                            </x-vuero.forms.field>
                        </div>
                        <div class="right">
                            <h3 class="has-text-centered">Enter your own</h3>
                            <x-vuero.forms.field>
                                <x-vuero.forms.control icon="dollar">
                                    <input class="input" type="number" min="0" step="10" placeholder="Amount...">
                                </x-vuero.forms.control>
                            </x-vuero.forms.field>
                        </div>
                    </div>

                    <div class="form-section is-grey">
                        <div class="left">
                            <h3>Personal Info</h3>
                            <x-vuero.forms.field>
                                <x-vuero.forms.control icon="user">
                                    <input class="input" type="text" placeholder="First Name *" autocomplete="given-name">
                                </x-vuero.forms.control>
                            </x-vuero.forms.field>
                            <x-vuero.forms.field>
                                <x-vuero.forms.control icon="user">
                                    <input class="input" type="text" placeholder="Last Name *" autocomplete="family-name">
                                </x-vuero.forms.control>
                            </x-vuero.forms.field>
                            <x-vuero.forms.field>
                                <x-vuero.forms.control icon="mail">
                                    <input class="input" type="email" placeholder="Email Address *" autocomplete="email" inputmode="email">
                                </x-vuero.forms.control>
                            </x-vuero.forms.field>
                            <x-vuero.forms.field>
                                <x-vuero.forms.control icon="phone">
                                    <input class="input" type="tel" placeholder="Phone Number *" autocomplete="tel" inputmode="tel">
                                </x-vuero.forms.control>
                            </x-vuero.forms.field>
                        </div>
                        <div class="right">
                            <h3>Payout Details</h3>
                            <x-vuero.forms.field>
                                <x-vuero.forms.control>
                                    <x-vuero.forms.select
                                        placeholder="Bank Account"
                                        :options="['AMEX **** 42', 'HSBC **** 29']"
                                    />
                                </x-vuero.forms.control>
                            </x-vuero.forms.field>
                            <x-vuero.forms.field>
                                <x-vuero.forms.control>
                                    <x-vuero.forms.select
                                        placeholder="Bank Account"
                                        :options="['Super Fast - $3.00', 'Regular - $0.50']"
                                    />
                                </x-vuero.forms.control>
                            </x-vuero.forms.field>
                            <x-vuero.forms.field>
                                <x-vuero.forms.label>Notify me when funds are ready?</x-vuero.forms.label>
                                <x-vuero.forms.control>
                                    <label class="radio"><input type="radio" name="notify-me" value="yes" checked> Yes</label>
                                    <label class="radio"><input type="radio" name="notify-me" value="no"> No</label>
                                </x-vuero.forms.control>
                            </x-vuero.forms.field>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </section>
</x-vuero.layouts.app>
