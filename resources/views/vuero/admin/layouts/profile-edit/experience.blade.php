<div class="account-box is-form is-footerless">
    <x-vuero.layouts.profile.form-head
        title="Work Experience"
        subtitle="Edit your work experience info"
        :back-url="route('vuero.admin.layouts.profile-view')"
    />

    <div class="form-body">
        <div class="fieldset">
            <div class="fieldset-heading" data-profile-panel-list="experience">
                <h4>Previous Experiences</h4>
                <p>This will help others assess your experience</p>
            </div>
            <div class="fieldset-heading" data-profile-panel="experience" hidden>
                <button type="button" class="action-link" data-profile-cancel-panel="experience">Cancel</button>
            </div>

            <div class="setting-list" data-profile-panel-list="experience">
                @foreach ($experiences as $experience)
                    <div class="setting-item">
                        <img src="{{ $experience['logo'] }}" alt="">
                        <div class="meta">
                            <span class="dark-inverted">{{ $experience['company'] }}</span>
                            <span><span>{{ $experience['start'] }}</span><i aria-hidden="true"></i><span>{{ $experience['end'] }}</span></span>
                            <span>{{ $experience['role'] }}</span>
                        </div>
                        <div class="end"><x-vuero.layouts.flex-table-menu type="project" label="Experience actions" /></div>
                    </div>
                @endforeach

                <div class="setting-item is-create">
                    <span class="icon-wrap"><x-vuero.icon name="plus" size="20" /></span>
                    <div class="meta">
                        <span class="dark-inverted">New Item</span>
                        <span>Add a new work experience item</span>
                    </div>
                    <div class="end">
                        <button type="button" class="v-button is-dark-outlined is-raised add-setting-item" data-profile-toggle-panel="experience">
                            <x-vuero.icon name="plus" size="14" />
                            <span>Add</span>
                        </button>
                    </div>
                </div>
            </div>

            <form class="setting-list setting-form" data-profile-panel="experience" hidden>
                <div class="profile-upload-placeholder" role="button" tabindex="0" aria-label="Upload company logo">
                    <x-vuero.icon name="upload" size="28" />
                </div>
                <div class="field-wrap">
                    <div class="columns is-multiline">
                        <div class="column is-12">
                            <x-vuero.forms.field class="has-addons has-addons-fullwidth">
                                <x-vuero.forms.control expanded>
                                    <input class="input" type="text" placeholder="Start date">
                                </x-vuero.forms.control>
                                <x-vuero.forms.control>
                                    <span class="addon-button" aria-hidden="true"><x-vuero.icon name="chevron-right" size="16" /></span>
                                </x-vuero.forms.control>
                                <x-vuero.forms.control expanded>
                                    <input class="input" type="text" placeholder="End date">
                                </x-vuero.forms.control>
                            </x-vuero.forms.field>
                        </div>
                        <div class="column is-6"><x-vuero.forms.field><x-vuero.forms.control icon="box"><input class="input" type="text" placeholder="Company Name"></x-vuero.forms.control></x-vuero.forms.field></div>
                        <div class="column is-6"><x-vuero.forms.field><x-vuero.forms.control icon="briefcase"><input class="input" type="text" placeholder="Job Title"></x-vuero.forms.control></x-vuero.forms.field></div>
                        <div class="column is-12"><button type="submit" class="form-button is-primary is-fullwidth" data-profile-save>Add Work Experience</button></div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
