<x-vuero.layouts.app title="Form Layouts 3" :script="$layoutScript">
    <section class="vuero-form-layout" data-form-layout-v3 aria-label="Form Layouts 3">
        <form class="form-layout is-separate" data-form-layout-form>
            <div class="form-outer">
                <div class="form-body">
                    <div class="form-section">
                        <div class="form-section-inner has-padding-bottom">
                            <h3 class="has-text-centered">Personal Information</h3>
                            <div class="columns is-multiline">
                                <div class="column is-6">
                                    <x-vuero.forms.field>
                                        <x-vuero.forms.label for="order-first-name">First Name</x-vuero.forms.label>
                                        <x-vuero.forms.control icon="user">
                                            <input class="input" id="order-first-name" type="text" autocomplete="given-name">
                                        </x-vuero.forms.control>
                                    </x-vuero.forms.field>
                                </div>
                                <div class="column is-6">
                                    <x-vuero.forms.field>
                                        <x-vuero.forms.label for="order-last-name">Last Name</x-vuero.forms.label>
                                        <x-vuero.forms.control icon="user">
                                            <input class="input" id="order-last-name" type="text" autocomplete="family-name">
                                        </x-vuero.forms.control>
                                    </x-vuero.forms.field>
                                </div>
                                <div class="column is-12">
                                    <x-vuero.forms.field>
                                        <x-vuero.forms.label for="order-email">Email Address</x-vuero.forms.label>
                                        <x-vuero.forms.control icon="mail">
                                            <input class="input" id="order-email" type="email" autocomplete="email" inputmode="email">
                                        </x-vuero.forms.control>
                                    </x-vuero.forms.field>
                                </div>
                                <div class="column is-12">
                                    <x-vuero.forms.field>
                                        <x-vuero.forms.label for="order-street">Street</x-vuero.forms.label>
                                        <x-vuero.forms.control icon="map-pin">
                                            <input class="input" id="order-street" type="text" autocomplete="street-address">
                                        </x-vuero.forms.control>
                                    </x-vuero.forms.field>
                                </div>
                                <div class="column is-6">
                                    <x-vuero.forms.field>
                                        <x-vuero.forms.label for="order-city">City</x-vuero.forms.label>
                                        <x-vuero.forms.control icon="map-pin">
                                            <input class="input" id="order-city" type="text" autocomplete="address-level2">
                                        </x-vuero.forms.control>
                                    </x-vuero.forms.field>
                                </div>
                                <div class="column is-6">
                                    <x-vuero.forms.field>
                                        <x-vuero.forms.label for="order-country">Country</x-vuero.forms.label>
                                        <x-vuero.forms.control>
                                            <x-vuero.forms.select
                                                id="order-country"
                                                placeholder="Select a country"
                                                image
                                                :options="[
                                                    ['value' => 'english', 'label' => 'English', 'icon' => '/vuero/images/layouts/list-view-v1/flags/united-states-of-america.svg'],
                                                    ['value' => 'french', 'label' => 'French', 'icon' => '/vuero/images/layouts/list-view-v1/flags/france.svg'],
                                                    ['value' => 'german', 'label' => 'German', 'icon' => '/vuero/images/layouts/list-view-v1/flags/germany.svg'],
                                                    ['value' => 'spanish', 'label' => 'Spanish', 'icon' => '/vuero/images/layouts/list-view-v1/flags/spain.svg'],
                                                ]"
                                            />
                                        </x-vuero.forms.control>
                                    </x-vuero.forms.field>
                                </div>
                                <div class="column is-12">
                                    <x-vuero.forms.field>
                                        <x-vuero.forms.label for="order-phone">Phone</x-vuero.forms.label>
                                        <x-vuero.forms.control icon="phone">
                                            <input class="input" id="order-phone" type="tel" autocomplete="tel" inputmode="tel">
                                        </x-vuero.forms.control>
                                    </x-vuero.forms.field>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="form-section">
                        <div class="form-section-inner">
                            <h3 class="has-text-centered">Delivery</h3>
                            <div class="columns is-multiline">
                                <div class="column is-12">
                                    <x-vuero.forms.field>
                                        <x-vuero.forms.label>Delivery Fee</x-vuero.forms.label>
                                        <x-vuero.forms.control>
                                            <div class="radio-boxes">
                                                <div class="control radio-box">
                                                    <input type="radio" id="delivery-type-free" name="delivery_type" value="0">
                                                    <div class="radio-box-inner">
                                                        <div class="fee"><span>0</span></div>
                                                        <p>3-4 weeks</p>
                                                    </div>
                                                </div>
                                                <div class="control radio-box">
                                                    <input type="radio" id="delivery-type-express" name="delivery_type" value="5" checked>
                                                    <div class="radio-box-inner">
                                                        <div class="fee"><span>5</span></div>
                                                        <p>2-5 days</p>
                                                    </div>
                                                </div>
                                            </div>
                                            <p class="delivery-meta">
                                                <span>Estimated delivery date: <span>Oct 23</span></span>
                                                <span>Each package has a tracking number</span>
                                            </p>
                                        </x-vuero.forms.control>
                                    </x-vuero.forms.field>
                                </div>
                            </div>
                        </div>

                        <div class="form-section-outer">
                            <div class="checkboxes">
                                <x-vuero.forms.field>
                                    <div class="checkbox-row">
                                        <label class="checkbox is-circle">
                                            <input type="checkbox" name="create_account" checked>
                                            <span class="checkbox-mark"></span>
                                            Create an account
                                        </label>
                                        <label class="checkbox is-circle">
                                            <input type="checkbox" name="subscribe">
                                            <span class="checkbox-mark"></span>
                                            Subscribe to our Newsletter
                                        </label>
                                    </div>
                                </x-vuero.forms.field>
                            </div>
                            <div class="button-wrap">
                                <button type="submit" class="form-button is-primary is-bold is-fullwidth">Confirm My Order</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </section>
</x-vuero.layouts.app>
