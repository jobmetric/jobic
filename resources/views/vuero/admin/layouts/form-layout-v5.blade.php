@php
    $steps = [
        ['title' => 'General Information', 'help' => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed quod proximum fuit non vidit. Quantum Aristoxeni ingenium consumptum videmus in musicis? An eiusdem modi? Quae similitudo in genere etiam humano apparet.'],
        ['title' => 'Shipment Owner', 'help' => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Idem iste, inquam, de voluptate quid sentit? Hanc igitur nosse volumus. Duo Reges: constructio interrete. Sed ad bona praeterita redeamus.'],
        ['title' => 'Shipment Taxes', 'help' => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Quae similitudo in genere etiam humano apparet. Idem iste, inquam, de voluptate quid sentit? Hanc igitur nosse volumus.'],
        ['title' => 'Options', 'help' => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Quantum Aristoxeni ingenium consumptum videmus in musicis? An eiusdem modi? Quae similitudo in genere etiam humano apparet.'],
        ['title' => 'Validation', 'help' => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed quod proximum fuit non vidit. Hanc igitur nosse volumus. Duo Reges: constructio interrete.'],
    ];

    $options = [
        ['icon' => 'lnil-consulting', 'title' => 'Double check', 'text' => 'Second control pass'],
        ['icon' => 'lnil-tie', 'title' => 'Agent', 'text' => 'Dedicated agent'],
        ['icon' => 'lnil-handshake', 'title' => 'Insurance', 'text' => 'Level 1-3 goods'],
        ['icon' => 'lnil-licencse', 'title' => 'Extension', 'text' => 'License extension'],
        ['icon' => 'lnil-pie-chart-alt', 'title' => 'BI Reports', 'text' => 'Custom made reports'],
        ['icon' => 'lnil-customer', 'title' => 'Metrics', 'text' => 'Setup live metrics'],
    ];
@endphp

<x-vuero.layouts.app title="Form Layouts 5" :script="$layoutScript">
    <section class="vuero-form-layout vuero-form-layout--stepper" data-form-layout-v5 data-dashboard-url="{{ route('vuero.admin.dashboard') }}" aria-label="Form Layouts 5">
        <form data-form-layout-form>
            <div class="mobile-steps is-active" aria-label="Form progress">
                <ul class="steps has-content-centered is-thin is-horizontal is-short">
                    @foreach ($steps as $index => $step)
                        <li class="steps-segment @if ($index === 0) is-active @endif" data-mobile-step="{{ $index }}">
                            <span class="steps-marker"></span>
                            <a href="#form-step-{{ $index }}" class="steps-content" data-mobile-step-link>
                                <p class="step-number">Step {{ $index + 1 }}</p>
                            </a>
                        </li>
                    @endforeach
                </ul>
            </div>

            <div class="stepper-form">
                <div class="form-sections">
                    <div id="form-step-0" class="form-section is-active" data-form-step="0">
                        <h3 class="form-section-title">
                            <span>{{ $steps[0]['title'] }}</span>
                            <button type="button" class="help-button" data-help-toggle="0" aria-label="Show help for step 1">
                                <x-vuero.icon name="help-circle" size="18" />
                            </button>
                        </h3>
                        <div class="form-section-inner">
                            <x-vuero.forms.field>
                                <x-vuero.forms.control>
                                    <input class="input" type="text" placeholder="What is this shipment about?">
                                </x-vuero.forms.control>
                            </x-vuero.forms.field>
                            <x-vuero.forms.field>
                                <x-vuero.forms.control>
                                    <button type="button" class="input-button" data-input-button>
                                        <x-vuero.icon name="plus" size="18" />
                                        <span>Add shipment group</span>
                                    </button>
                                </x-vuero.forms.control>
                            </x-vuero.forms.field>
                            <div class="fieldset">
                                <x-vuero.forms.field class="is-grouped">
                                    <x-vuero.forms.control class="is-expanded">
                                        <input class="input" type="text" placeholder="Group name">
                                    </x-vuero.forms.control>
                                    <x-vuero.forms.control class="subcontrol">
                                        <x-vuero.forms.switch-segment label-false="Ground" label-true="Other" primary />
                                    </x-vuero.forms.control>
                                </x-vuero.forms.field>
                                <x-vuero.forms.field class="is-grouped">
                                    <x-vuero.forms.control class="is-expanded">
                                        <input class="input" type="text" placeholder="Group description">
                                    </x-vuero.forms.control>
                                    <x-vuero.forms.control class="subcontrol">
                                        <label class="checkbox"><input type="checkbox"><span class="checkbox-mark" aria-hidden="true"></span> Fragile</label>
                                    </x-vuero.forms.control>
                                </x-vuero.forms.field>
                                <div class="fieldset-separator"></div>
                                <x-vuero.forms.field class="is-grouped">
                                    <x-vuero.forms.control class="is-expanded">
                                        <div class="flex-label"><h4>Control Service</h4></div>
                                    </x-vuero.forms.control>
                                    <x-vuero.forms.control class="subcontrol">
                                        <x-vuero.forms.switch-segment label-false="OFF" label-true="ON" :checked="true" />
                                    </x-vuero.forms.control>
                                </x-vuero.forms.field>
                                <x-vuero.forms.field>
                                    <x-vuero.forms.label for="control-type">Type</x-vuero.forms.label>
                                    <x-vuero.forms.control>
                                        <x-vuero.forms.select
                                            id="control-type"
                                            placeholder="Select a control type"
                                            :options="['X-REF Quality', 'SERPA Check', 'AMF Check']"
                                        />
                                    </x-vuero.forms.control>
                                </x-vuero.forms.field>
                                <x-vuero.forms.field>
                                    <x-vuero.forms.label for="control-notes">Additional Notes</x-vuero.forms.label>
                                    <x-vuero.forms.control>
                                        <input class="input" id="control-notes" type="text" placeholder="Add some additional notes">
                                    </x-vuero.forms.control>
                                </x-vuero.forms.field>
                                <x-vuero.forms.field>
                                    <x-vuero.forms.label for="storage-type">Method</x-vuero.forms.label>
                                    <x-vuero.forms.control>
                                        <x-vuero.forms.select
                                            id="storage-type"
                                            placeholder="Select a storage"
                                            :options="['LVL 1 Security warehouse', 'LVL 2 Security warehouse', 'LVL 3 Security warehouse']"
                                        />
                                    </x-vuero.forms.control>
                                </x-vuero.forms.field>
                                <x-vuero.forms.field>
                                    <x-vuero.forms.label for="quantity">Quantity (units)</x-vuero.forms.label>
                                    <x-vuero.forms.control>
                                        <input class="input" id="quantity" type="number" placeholder="Enter a quantity">
                                    </x-vuero.forms.control>
                                </x-vuero.forms.field>
                                <x-vuero.forms.field>
                                    <x-vuero.forms.label for="duration">Duration (days)</x-vuero.forms.label>
                                    <x-vuero.forms.control>
                                        <input class="input" id="duration" type="number" placeholder="Enter a storage duration">
                                    </x-vuero.forms.control>
                                </x-vuero.forms.field>
                                <x-vuero.forms.field>
                                    <div class="buttons">
                                        <button type="button" class="form-button" data-add-shipment>Add Shipment</button>
                                    </div>
                                </x-vuero.forms.field>
                            </div>
                        </div>
                        <div class="form-section-output">
                            <div class="output" data-output-item>
                                <x-vuero.icon name="archive" size="18" />
                                <span>15 i9 Laptops - b2</span>
                                <div class="action">
                                    <button type="button" data-output-remove aria-label="Remove shipment"><x-vuero.icon name="trash-2" size="16" /></button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div id="form-step-1" class="form-section" data-form-step="1">
                        <h3 class="form-section-title">
                            <span>{{ $steps[1]['title'] }}</span>
                            <button type="button" class="help-button" data-help-toggle="1" aria-label="Show help for step 2">
                                <x-vuero.icon name="help-circle" size="18" />
                            </button>
                        </h3>
                        <div class="form-section-inner">
                            <x-vuero.forms.field>
                                <x-vuero.forms.control>
                                    <input class="input" type="text" placeholder="Owning company name">
                                </x-vuero.forms.control>
                            </x-vuero.forms.field>
                            <x-vuero.forms.field>
                                <x-vuero.forms.control>
                                    <button type="button" class="input-button" data-input-button>
                                        <x-vuero.icon name="plus" size="18" />
                                        <span>Add contact</span>
                                    </button>
                                </x-vuero.forms.control>
                            </x-vuero.forms.field>
                            <div class="fieldset">
                                <x-vuero.forms.field class="is-grouped">
                                    <x-vuero.forms.control class="is-expanded">
                                        <input class="input" type="text" placeholder="Full name">
                                    </x-vuero.forms.control>
                                    <x-vuero.forms.control class="subcontrol">
                                        <x-vuero.forms.switch-segment label-false="Other" label-true="Ground" :checked="true" />
                                    </x-vuero.forms.control>
                                </x-vuero.forms.field>
                                <x-vuero.forms.field class="is-grouped">
                                    <x-vuero.forms.control class="is-expanded">
                                        <input class="input" type="email" placeholder="Email address">
                                    </x-vuero.forms.control>
                                    <x-vuero.forms.control class="subcontrol">
                                        <label class="checkbox"><input type="checkbox"><span class="checkbox-mark" aria-hidden="true"></span> Primary</label>
                                    </x-vuero.forms.control>
                                </x-vuero.forms.field>
                                <x-vuero.forms.field class="is-grouped">
                                    <x-vuero.forms.control class="is-expanded">
                                        <input class="input" type="tel" placeholder="Phone number">
                                    </x-vuero.forms.control>
                                    <x-vuero.forms.control class="subcontrol"></x-vuero.forms.control>
                                </x-vuero.forms.field>
                            </div>
                        </div>
                        <div class="form-section-output">
                            @foreach (['Erik Kovalsky', 'Elsa Walker'] as $contact)
                                <div class="output" data-output-item>
                                    <x-vuero.icon name="user" size="18" />
                                    <span>{{ $contact }}</span>
                                    <div class="action">
                                        <button type="button" data-output-remove aria-label="Remove {{ $contact }}"><x-vuero.icon name="trash-2" size="16" /></button>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <div id="form-step-2" class="form-section" data-form-step="2">
                        <h3 class="form-section-title">
                            <span>{{ $steps[2]['title'] }}</span>
                            <button type="button" class="help-button" data-help-toggle="2" aria-label="Show help for step 3">
                                <x-vuero.icon name="help-circle" size="18" />
                            </button>
                        </h3>
                        <div class="form-section-inner">
                            <x-vuero.forms.field>
                                <x-vuero.forms.control>
                                    <input class="input" type="text" placeholder="Company Tax ID">
                                </x-vuero.forms.control>
                            </x-vuero.forms.field>
                            <div class="columns">
                                <div class="column is-6">
                                    <x-vuero.forms.field>
                                        <x-vuero.forms.control>
                                            <x-vuero.forms.select
                                                placeholder="Tax Type"
                                                :options="['VAT', 'RFC', 'SFC']"
                                            />
                                        </x-vuero.forms.control>
                                    </x-vuero.forms.field>
                                </div>
                                <div class="column is-6">
                                    <x-vuero.forms.field>
                                        <x-vuero.forms.control>
                                            <x-vuero.forms.select
                                                placeholder="Tax Statements"
                                                :options="['W-12 Form', 'W-12a Form', 'Z-Form']"
                                            />
                                        </x-vuero.forms.control>
                                    </x-vuero.forms.field>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div id="form-step-3" class="form-section" data-form-step="3">
                        <h3 class="form-section-title">
                            <span>{{ $steps[3]['title'] }}</span>
                            <button type="button" class="help-button" data-help-toggle="3" aria-label="Show help for step 4">
                                <x-vuero.icon name="help-circle" size="18" />
                            </button>
                        </h3>
                        <div class="form-section-inner">
                            <div class="options">
                                @foreach ($options as $option)
                                    <x-vuero.forms.field class="option">
                                        <input type="checkbox" id="form-option-{{ $loop->index }}" aria-label="{{ $option['title'] }}">
                                        <div class="indicator"><x-vuero.icon name="check" size="14" /></div>
                                        <div class="option-inner">
                                            <x-vuero.lnil-icon :name="$option['icon']" />
                                            <h4>{{ $option['title'] }}</h4>
                                            <p>{{ $option['text'] }}</p>
                                        </div>
                                    </x-vuero.forms.field>
                                @endforeach
                            </div>
                        </div>
                    </div>

                    <div id="form-step-4" class="form-section" data-form-step="4">
                        <h3 class="form-section-title">
                            <span>{{ $steps[4]['title'] }}</span>
                            <button type="button" class="help-button" data-help-toggle="4" aria-label="Show help for step 5">
                                <x-vuero.icon name="help-circle" size="18" />
                            </button>
                        </h3>
                        <div class="form-section-inner">
                            <div class="validation-box">
                                <div class="box-content">
                                    <h3>Excellent</h3>
                                    <p>Before submitting the form, make sure you've filled all the required fields. Once submitted, you won't be able to change the info for this shipment.</p>
                                </div>
                                <div class="box-illustration">
                                    <img src="/vuero/images/layouts/form-layouts/plant-1.svg" alt="">
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="navigation-buttons">
                        <div class="buttons is-right">
                            <button type="submit" class="form-button is-primary is-bold" data-step-continue>Continue</button>
                        </div>
                    </div>
                </div>

                <div class="form-stepper">
                    <ul class="steps is-vertical is-thin is-short" data-stepper-list>
                        @foreach ($steps as $index => $step)
                            <li class="steps-segment @if ($index === 0) is-active @endif" data-sidebar-step="{{ $index }}" role="button" tabindex="0">
                                <span class="steps-marker"></span>
                                <div class="steps-content">
                                    <p class="step-number">STEP {{ $index + 1 }}</p>
                                    <p class="step-info">{{ $step['title'] }}</p>
                                </div>
                            </li>
                        @endforeach
                    </ul>

                    <div class="form-help" data-help-container hidden>
                        @foreach ($steps as $index => $step)
                            <div class="form-help-inner" data-help-panel="{{ $index }}">
                                <button type="button" class="close-help-button" data-help-close aria-label="Close help">
                                    <x-vuero.icon name="x" size="18" />
                                </button>
                                <h3>{{ $step['title'] }}</h3>
                                <p>{{ $step['help'] }}</p>
                                <div class="list-wrap">
                                    <ul>
                                        @foreach (range(1, in_array($index, [1, 3, 4], true) ? 2 : 3) as $item)
                                            <li><x-vuero.icon name="check" size="14" /><span>Some nice list item</span></li>
                                        @endforeach
                                    </ul>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </form>
    </section>
</x-vuero.layouts.app>
