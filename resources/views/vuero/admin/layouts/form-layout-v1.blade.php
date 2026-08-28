<x-vuero.layouts.app title="Form Layouts 1" :script="$layoutScript">
    <section class="vuero-form-layout" data-form-layout-v1 aria-label="Form Layouts 1">
        <form class="form-layout" data-form-layout-form>
            <div class="form-outer">
                <x-vuero.forms.header title="Request a Demo" submit="Schedule" />

                <div class="form-body">
                    <x-vuero.forms.fieldset title="Personal Info" subtitle="This helps us to know you">
                        <div class="columns is-multiline">
                            <div class="column is-6">
                                <x-vuero.forms.field>
                                    <x-vuero.forms.label for="demo-first-name">First Name</x-vuero.forms.label>
                                    <x-vuero.forms.control icon="user">
                                        <input class="input" id="demo-first-name" type="text" autocomplete="given-name">
                                    </x-vuero.forms.control>
                                </x-vuero.forms.field>
                            </div>
                            <div class="column is-6">
                                <x-vuero.forms.field>
                                    <x-vuero.forms.label for="demo-last-name">Last Name</x-vuero.forms.label>
                                    <x-vuero.forms.control icon="user">
                                        <input class="input" id="demo-last-name" type="text" autocomplete="family-name">
                                    </x-vuero.forms.control>
                                </x-vuero.forms.field>
                            </div>
                            <div class="column is-12">
                                <x-vuero.forms.field>
                                    <x-vuero.forms.label for="demo-email">Email Address</x-vuero.forms.label>
                                    <x-vuero.forms.control icon="mail">
                                        <input class="input" id="demo-email" type="email" autocomplete="email" inputmode="email">
                                    </x-vuero.forms.control>
                                </x-vuero.forms.field>
                            </div>
                        </div>
                    </x-vuero.forms.fieldset>

                    <x-vuero.forms.fieldset title="Company Info" subtitle="Tell us about your company">
                        <div class="columns is-multiline">
                            <div class="column is-6">
                                <x-vuero.forms.field>
                                    <x-vuero.forms.label for="demo-company">Company Name</x-vuero.forms.label>
                                    <x-vuero.forms.control icon="briefcase">
                                        <input class="input" id="demo-company" type="text" autocomplete="organization">
                                    </x-vuero.forms.control>
                                </x-vuero.forms.field>
                            </div>
                            <div class="column is-6">
                                <x-vuero.forms.field>
                                    <x-vuero.forms.label for="demo-phone">Company Phone</x-vuero.forms.label>
                                    <x-vuero.forms.control icon="phone">
                                        <input class="input" id="demo-phone" type="tel" autocomplete="tel" inputmode="tel">
                                    </x-vuero.forms.control>
                                </x-vuero.forms.field>
                            </div>
                            <div class="column is-6">
                                <x-vuero.forms.field>
                                    <x-vuero.forms.label for="demo-size">Company Size</x-vuero.forms.label>
                                    <x-vuero.forms.control>
                                        <x-vuero.forms.select
                                            id="demo-size"
                                            placeholder="Select a size"
                                            :options="['1-5 Employees', '5-25 Employees', '25-50 Employees', '50-100 Employees', '100+ Employees']"
                                        />
                                    </x-vuero.forms.control>
                                </x-vuero.forms.field>
                            </div>
                            <div class="column is-6">
                                <x-vuero.forms.field>
                                    <x-vuero.forms.label for="demo-type">Business Type</x-vuero.forms.label>
                                    <x-vuero.forms.control>
                                        <x-vuero.forms.select
                                            id="demo-type"
                                            placeholder="Select a type"
                                            :options="['Government', 'Medical', 'Finance', 'Services', 'Technology']"
                                        />
                                    </x-vuero.forms.control>
                                </x-vuero.forms.field>
                            </div>
                            <div class="column is-12">
                                <x-vuero.forms.field>
                                    <x-vuero.forms.label for="demo-company-email">Company Email</x-vuero.forms.label>
                                    <x-vuero.forms.control icon="mail">
                                        <input class="input" id="demo-company-email" type="email" autocomplete="email" inputmode="email">
                                    </x-vuero.forms.control>
                                </x-vuero.forms.field>
                            </div>
                        </div>
                    </x-vuero.forms.fieldset>

                    <x-vuero.forms.fieldset title="Demonstration" subtitle="how would you like your demo?">
                        <div class="columns is-multiline">
                            <div class="column is-6">
                                <x-vuero.forms.field>
                                    <x-vuero.forms.label for="demo-product">Product to demo</x-vuero.forms.label>
                                    <x-vuero.forms.control>
                                        <x-vuero.forms.select
                                            id="demo-product"
                                            placeholder="Select a product"
                                            :options="['Vuero Starter', 'Vuero Pro', 'Vuero Business']"
                                        />
                                    </x-vuero.forms.control>
                                </x-vuero.forms.field>
                            </div>
                            <div class="column is-6">
                                <x-vuero.forms.field>
                                    <x-vuero.forms.label for="demo-date">Prefered Date</x-vuero.forms.label>
                                    <x-vuero.forms.control icon="calendar">
                                        <input class="input" id="demo-date" type="text" placeholder="Select a date" autocomplete="off">
                                    </x-vuero.forms.control>
                                </x-vuero.forms.field>
                            </div>
                            <div class="column is-12">
                                <x-vuero.forms.field>
                                    <x-vuero.forms.label for="demo-notes">Special Instructions</x-vuero.forms.label>
                                    <x-vuero.forms.control>
                                        <textarea class="textarea" id="demo-notes" rows="4" placeholder="Tell us about any details you'd like us to know..." autocomplete="off" spellcheck="true"></textarea>
                                    </x-vuero.forms.control>
                                </x-vuero.forms.field>
                            </div>
                        </div>
                    </x-vuero.forms.fieldset>
                </div>
            </div>
        </form>
    </section>
</x-vuero.layouts.app>
