<div class="account-box is-form is-footerless">
    <x-vuero.layouts.profile.form-head
        title="Settings"
        subtitle="Edit your account prefs and settings"
        :back-url="route('vuero.admin.layouts.profile-view')"
    />

    <form class="form-body">
        <div class="fieldset">
            <div class="fieldset-heading">
                <h4>Change Password</h4>
                <p>For an improved account security</p>
            </div>
            <div class="columns is-multiline">
                <div class="column is-12"><x-vuero.forms.field><x-vuero.forms.control icon="shield"><input class="input" type="password" placeholder="Old Password" autocomplete="current-password"></x-vuero.forms.control></x-vuero.forms.field></div>
                <div class="column is-12"><x-vuero.forms.field><x-vuero.forms.control icon="lock"><input class="input" type="password" placeholder="New Password" autocomplete="new-password"></x-vuero.forms.control></x-vuero.forms.field></div>
                <div class="column is-12"><x-vuero.forms.field><x-vuero.forms.control icon="lock"><input class="input" type="password" placeholder="Repeat New Password" autocomplete="new-password"></x-vuero.forms.control></x-vuero.forms.field></div>
            </div>
        </div>

        <div class="fieldset">
            <div class="fieldset-heading">
                <h4>2 Factor Auth</h4>
                <p>Enable or disable 2 factor auth</p>
            </div>
            <div class="columns is-multiline">
                <div class="column is-12">
                    <x-vuero.forms.field>
                        <x-vuero.forms.control>
                            <div class="switch-block">
                                <label for="two-factor">Enable / disable 2 factor</label>
                                <label class="form-switch is-primary"><input id="two-factor" type="checkbox" checked data-profile-two-factor><i aria-hidden="true"></i></label>
                            </div>
                        </x-vuero.forms.control>
                    </x-vuero.forms.field>
                </div>
                <div class="column is-12" data-profile-two-factor-phone>
                    <x-vuero.forms.field><x-vuero.forms.control icon="phone"><input class="input" type="text" placeholder="Phone Number" autocomplete="tel" inputmode="tel"></x-vuero.forms.control></x-vuero.forms.field>
                </div>
            </div>
        </div>

        <div class="fieldset">
            <div class="fieldset-heading">
                <h4>Notifications</h4>
                <p>Configure how you receive notifications</p>
            </div>
            <div class="columns is-multiline">
                <div class="column is-12">
                    <x-vuero.forms.field>
                        <x-vuero.forms.control>
                            <div class="switch-block">
                                <label for="disable-notifications">Disable all notifications</label>
                                <label class="form-switch is-primary"><input id="disable-notifications" type="checkbox"><i aria-hidden="true"></i></label>
                            </div>
                        </x-vuero.forms.control>
                    </x-vuero.forms.field>
                    <x-vuero.forms.field>
                        <x-vuero.forms.control>
                            <div class="switch-block">
                                <label for="disable-low">Disable low priority notifications</label>
                                <label class="form-switch is-primary"><input id="disable-low" type="checkbox"><i aria-hidden="true"></i></label>
                            </div>
                        </x-vuero.forms.control>
                    </x-vuero.forms.field>
                </div>
            </div>
        </div>

        <div class="fieldset">
            <div class="fieldset-heading">
                <h4>Marketing</h4>
                <p>Configure how you receive promotions</p>
            </div>
            <div class="columns is-multiline">
                <div class="column is-12">
                    <x-vuero.forms.field>
                        <x-vuero.forms.control>
                            <div class="switch-block">
                                <label for="marketing">Enable marketing emails</label>
                                <label class="form-switch is-primary"><input id="marketing" type="checkbox"><i aria-hidden="true"></i></label>
                            </div>
                        </x-vuero.forms.control>
                    </x-vuero.forms.field>
                    <x-vuero.forms.field>
                        <x-vuero.forms.control>
                            <div class="switch-block">
                                <label for="partners">Enable partners emails</label>
                                <label class="form-switch is-primary"><input id="partners" type="checkbox"><i aria-hidden="true"></i></label>
                            </div>
                        </x-vuero.forms.control>
                    </x-vuero.forms.field>
                </div>
            </div>
        </div>
    </form>
</div>
