<x-vuero.layouts.app title="Form Layouts 4" :script="$layoutScript">
    <section class="vuero-form-layout" data-form-layout-v4 aria-label="Form Layouts 4">
        <form class="form-layout is-stacked" data-form-layout-form>
            <div class="form-outer">
                <x-vuero.forms.header title="Schedule a meeting" submit="Schedule" />

                <div class="form-body">
                    <div class="form-section">
                        <div class="columns is-multiline">
                            <div class="column is-12">
                                <x-vuero.forms.field>
                                    <x-vuero.forms.control icon="slack">
                                        <input class="input" type="text" placeholder="What is this meeting about?">
                                    </x-vuero.forms.control>
                                </x-vuero.forms.field>
                            </div>
                            <div class="column is-6">
                                <div class="columns v-calendar-combo">
                                    <div class="column is-6">
                                        <x-vuero.forms.field>
                                            <x-vuero.forms.label for="meeting-start-date">Meeting start date</x-vuero.forms.label>
                                            <x-vuero.forms.control icon="calendar">
                                                <input class="input form-datepicker" id="meeting-start-date" type="text" placeholder="Start Date" autocomplete="off">
                                            </x-vuero.forms.control>
                                        </x-vuero.forms.field>
                                    </div>
                                    <div class="column is-6">
                                        <x-vuero.forms.field>
                                            <x-vuero.forms.label for="meeting-end-date" hidden>Meeting end date</x-vuero.forms.label>
                                            <x-vuero.forms.control icon="calendar">
                                                <input class="input form-datepicker" id="meeting-end-date" type="text" placeholder="End Date" autocomplete="off">
                                            </x-vuero.forms.control>
                                        </x-vuero.forms.field>
                                    </div>
                                </div>
                            </div>
                            <div class="column is-3">
                                <x-vuero.forms.field>
                                    <x-vuero.forms.label for="meeting-start-time">Meeting start time</x-vuero.forms.label>
                                    <x-vuero.forms.control>
                                        <input class="input form-timepicker" id="meeting-start-time" type="text" placeholder="09:00" autocomplete="off">
                                    </x-vuero.forms.control>
                                </x-vuero.forms.field>
                            </div>
                            <div class="column is-3">
                                <x-vuero.forms.field>
                                    <x-vuero.forms.label for="meeting-end-time" hidden>Meeting end time</x-vuero.forms.label>
                                    <x-vuero.forms.control>
                                        <input class="input form-timepicker" id="meeting-end-time" type="text" placeholder="10:00" autocomplete="off">
                                    </x-vuero.forms.control>
                                </x-vuero.forms.field>
                            </div>
                            <div class="column is-12">
                                <x-vuero.forms.field>
                                    <x-vuero.forms.control>
                                        <label class="radio"><input type="radio" name="meeting_repeat" value="all-day" checked> All day</label>
                                        <label class="radio"><input type="radio" name="meeting_repeat" value="weekly"> Repeat every week</label>
                                    </x-vuero.forms.control>
                                </x-vuero.forms.field>
                            </div>
                        </div>
                    </div>

                    <div class="form-section is-grey">
                        <div class="form-section-header">
                            <div class="left"><h3>Meeting details</h3></div>
                            <div class="right">
                                <button type="button" class="form-button is-light" data-add-people>Add People</button>
                            </div>
                        </div>

                        <div class="form-section-inner is-horizontal">
                            <x-vuero.forms.field class="is-horizontal">
                                <x-vuero.forms.label>Participants</x-vuero.forms.label>
                                <x-vuero.forms.control>
                                    <div class="participants">
                                        <x-vuero.layouts.flex-avatar picture="/vuero/images/avatars/svg/vuero-1.svg" />
                                        <x-vuero.layouts.flex-avatar initials="BT" color="warning" />
                                        <x-vuero.layouts.flex-avatar picture="/vuero/images/layouts/list-view-v1/avatars/18.jpg" />
                                        <x-vuero.layouts.flex-avatar initials="JD" color="info" />
                                        <x-vuero.layouts.flex-avatar picture="/vuero/images/layouts/list-view-v1/avatars/7.jpg" />
                                        <button type="button" class="add-participant" data-add-participant aria-label="Add participant">
                                            <x-vuero.icon name="plus" size="16" />
                                        </button>
                                    </div>
                                </x-vuero.forms.control>
                            </x-vuero.forms.field>

                            <x-vuero.forms.field class="is-horizontal">
                                <x-vuero.forms.label for="meeting-where">Where</x-vuero.forms.label>
                                <x-vuero.forms.control icon="map-pin">
                                    <input class="input" id="meeting-where" type="text" placeholder="e.g. Conference room">
                                </x-vuero.forms.control>
                            </x-vuero.forms.field>

                            <x-vuero.forms.field class="is-horizontal">
                                <x-vuero.forms.label for="meeting-url">Meeting URL</x-vuero.forms.label>
                                <x-vuero.forms.control icon="link">
                                    <input class="input" id="meeting-url" type="url" placeholder="https://zoom.com/m/156546" inputmode="url">
                                </x-vuero.forms.control>
                            </x-vuero.forms.field>

                            <x-vuero.forms.field class="is-horizontal">
                                <x-vuero.forms.label for="meeting-calendar">Clendar</x-vuero.forms.label>
                                <x-vuero.forms.control>
                                    <x-vuero.forms.select
                                        id="meeting-calendar"
                                        placeholder="Pick a calendar"
                                        :options="['My Calendar', 'Team Calendar', 'Company Calendar']"
                                    />
                                </x-vuero.forms.control>
                            </x-vuero.forms.field>

                            <x-vuero.forms.field class="is-horizontal">
                                <x-vuero.forms.label for="meeting-description">Description</x-vuero.forms.label>
                                <x-vuero.forms.control>
                                    <textarea class="textarea" id="meeting-description" rows="4" placeholder="Tell us about any details you'd like us to know..." spellcheck="true"></textarea>
                                    <button type="button" class="add-link" data-add-link>Add Attachments</button>
                                </x-vuero.forms.control>
                            </x-vuero.forms.field>
                        </div>
                    </div>

                    <div class="form-section is-grey">
                        <div class="form-section-inner is-horizontal">
                            <x-vuero.forms.field class="is-horizontal">
                                <x-vuero.forms.label>Color Code</x-vuero.forms.label>
                                <x-vuero.forms.control>
                                    <div class="color-codes">
                                        @foreach (['primary', 'secondary', 'info', 'success', 'purple'] as $color)
                                            <button type="button" class="color-code is-{{ $color }} @if ($loop->first) is-active @endif" data-color-code="{{ $color }}" aria-pressed="{{ $loop->first ? 'true' : 'false' }}" aria-label="{{ ucfirst($color) }} color"></button>
                                        @endforeach
                                    </div>
                                </x-vuero.forms.control>
                            </x-vuero.forms.field>

                            <x-vuero.forms.field class="is-horizontal has-subcontrol">
                                <x-vuero.forms.label for="notification-medium">Notifications</x-vuero.forms.label>
                                <x-vuero.forms.control>
                                    <x-vuero.forms.select
                                        id="notification-medium"
                                        placeholder="Select a channel"
                                        :options="['Email', 'SMS', 'Slack', 'Project Board']"
                                    />
                                    <button type="button" class="add-link" data-add-notification>Add Notification</button>
                                </x-vuero.forms.control>
                                <x-vuero.forms.control class="is-subcontrol">
                                    <x-vuero.forms.select
                                        id="notification-target"
                                        placeholder="Select a target"
                                        image
                                        :options="[
                                            ['value' => 'all', 'label' => 'All', 'icon' => '/vuero/images/layouts/list-view-v1/avatars/9.jpg'],
                                            ['value' => 'eric', 'label' => 'Erik K.', 'icon' => '/vuero/images/avatars/svg/vuero-1.svg'],
                                            ['value' => 'joshua', 'label' => 'Joshua S.', 'icon' => '/vuero/images/layouts/list-view-v1/avatars/12.jpg'],
                                            ['value' => 'melany', 'label' => 'Melany W.', 'icon' => '/vuero/images/layouts/list-view-v1/avatars/25.jpg'],
                                            ['value' => 'alice', 'label' => 'Alice C.', 'icon' => '/vuero/images/layouts/list-view-v1/avatars/7.jpg'],
                                            ['value' => 'esteban', 'label' => 'Esteban C.', 'icon' => '/vuero/images/layouts/list-view-v1/avatars/18.jpg'],
                                        ]"
                                    />
                                </x-vuero.forms.control>
                            </x-vuero.forms.field>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </section>
</x-vuero.layouts.app>
