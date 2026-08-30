<div class="account-box is-form is-footerless">
    <x-vuero.layouts.profile.form-head
        title="General Info"
        subtitle="Edit your account's general information"
        :back-url="route('vuero.admin.layouts.profile-view')"
    />

    <div class="form-body">
        <div class="fieldset">
            <div class="fieldset-heading">
                <h4>Profile Picture</h4>
                <p>This is how others will recognize you</p>
            </div>
            <div class="profile-v-avatar">
                <span data-profile-upload-view="avatar">
                    <x-vuero.layouts.flex-avatar size="xl" picture="/vuero/images/layouts/list-view-v1/avatars/vuero-1.svg" />
                    <button type="button" class="edit-button" data-profile-upload-toggle="upload" aria-label="Edit avatar"><x-vuero.icon name="edit" size="16" /></button>
                </span>
                <div data-profile-upload-view="upload" hidden>
                    <div class="profile-upload-placeholder">
                        <x-vuero.icon name="upload" size="28" />
                        <p class="light-text">Click to upload a new profile picture</p>
                    </div>
                    <button type="button" class="form-button is-outlined-light" data-profile-upload-toggle="avatar">Back</button>
                </div>
            </div>
        </div>

        <div class="fieldset">
            <div class="fieldset-heading">
                <h4>Personal Info</h4>
                <p>Others diserve to know you more</p>
            </div>
            <div class="columns is-multiline">
                <div class="column is-6"><x-vuero.forms.field><x-vuero.forms.control icon="user"><input class="input" type="text" placeholder="First Name" autocomplete="given-name"></x-vuero.forms.control></x-vuero.forms.field></div>
                <div class="column is-6"><x-vuero.forms.field><x-vuero.forms.control icon="user"><input class="input" type="text" placeholder="Last Name" autocomplete="family-name"></x-vuero.forms.control></x-vuero.forms.field></div>
                <div class="column is-12"><x-vuero.forms.field><x-vuero.forms.control icon="briefcase"><input class="input" type="text" placeholder="Job Title" autocomplete="organization-title"></x-vuero.forms.control></x-vuero.forms.field></div>
                <div class="column is-12"><x-vuero.forms.field><x-vuero.forms.control icon="map-pin"><input class="input" type="text" placeholder="Location" autocomplete="country-name"></x-vuero.forms.control></x-vuero.forms.field></div>
                <div class="column is-12"><x-vuero.forms.field><x-vuero.forms.control><textarea class="textarea" rows="4" placeholder="About / Bio"></textarea></x-vuero.forms.control></x-vuero.forms.field></div>
            </div>
        </div>

        <div class="fieldset">
            <div class="fieldset-heading">
                <h4>Professional Info</h4>
                <p>This can help you to win some opportunities</p>
            </div>
            <div class="columns is-multiline">
                <div class="column is-6"><x-vuero.forms.field><x-vuero.forms.control><x-vuero.forms.select placeholder="Experience" :options="['0-2 years', '2-5 years', '5-10 years', '10+ years']" /></x-vuero.forms.control></x-vuero.forms.field></div>
                <div class="column is-6"><x-vuero.forms.field><x-vuero.forms.control><x-vuero.forms.select placeholder="Is this your first job?" :options="['Yes', 'No']" /></x-vuero.forms.control></x-vuero.forms.field></div>
                <div class="column is-6"><x-vuero.forms.field><x-vuero.forms.control><x-vuero.forms.select placeholder="Are you flexible?" :options="['Yes', 'No']" /></x-vuero.forms.control></x-vuero.forms.field></div>
                <div class="column is-6"><x-vuero.forms.field><x-vuero.forms.control><x-vuero.forms.select placeholder="Do you work remotely?" :options="['Yes', 'No']" /></x-vuero.forms.control></x-vuero.forms.field></div>
                <div class="column is-12">
                    <x-vuero.forms.field>
                        <x-vuero.forms.control>
                            <div class="profile-tags-input">
                                @foreach (['software', 'saas', 'engineering'] as $tag)
                                    <span class="profile-tag">{{ $tag }}</span>
                                @endforeach
                                <input type="text" placeholder="Add tags" aria-label="Add tags">
                            </div>
                        </x-vuero.forms.control>
                    </x-vuero.forms.field>
                </div>
            </div>
        </div>

        <div class="fieldset">
            <div class="fieldset-heading">
                <h4>Social Profiles</h4>
                <p>This can help others finding you on social media</p>
            </div>
            <div class="columns is-multiline">
                @foreach ([['brand-facebook', 'Facebook URL'], ['brand-twitter', 'Twitter URL'], ['brand-dribbble', 'Dribbble URL'], ['link', 'Instagram URL'], ['brand-github', 'Github URL'], ['link', 'Gitlab URL']] as [$icon, $placeholder])
                    <div class="column is-6"><x-vuero.forms.field><x-vuero.forms.control :icon="$icon"><input class="input" type="text" placeholder="{{ $placeholder }}" inputmode="url"></x-vuero.forms.control></x-vuero.forms.field></div>
                @endforeach
            </div>
        </div>
    </div>
</div>
