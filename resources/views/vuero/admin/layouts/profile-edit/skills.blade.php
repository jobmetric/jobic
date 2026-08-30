@php
    $sections = [
        'languages' => ['Languages', 'How many languages do you speak?', $languages, 'language'],
        'skills' => ['Skills', 'Add your best skills', $skills, 'skill'],
        'tools' => ['Tools', 'Add the tools you work with', $tools, 'tool'],
    ];
@endphp

<div class="account-box is-form is-footerless">
    <x-vuero.layouts.profile.form-head
        title="Skills and Tools"
        subtitle="Edit your skills and tools"
        :back-url="route('vuero.admin.layouts.profile-view')"
    />

    <div class="form-body">
        @foreach ($sections as $key => [$title, $subtitle, $items, $type])
            <div class="fieldset">
                <div class="fieldset-heading" data-profile-panel-list="{{ $key }}">
                    <h4>{{ $title }}</h4>
                    <p>{{ $subtitle }}</p>
                </div>
                <div class="fieldset-heading" data-profile-panel="{{ $key }}" hidden>
                    <button type="button" class="action-link" data-profile-cancel-panel="{{ $key }}">Cancel</button>
                </div>

                <div class="setting-list" data-profile-panel-list="{{ $key }}">
                    @foreach ($items as $item)
                        <div class="setting-item">
                            <span class="icon-wrap has-img"><img src="{{ $item[1] }}" alt=""></span>
                            <div class="meta">
                                <span class="dark-inverted">{{ $item[0] }}</span>
                                <span>{{ $item[2] }}</span>
                            </div>
                            <div class="end"><x-vuero.layouts.flex-table-menu type="project" :label="$title.' actions'" /></div>
                        </div>
                    @endforeach

                    <div class="setting-item is-create">
                        <span class="icon-wrap"><x-vuero.icon name="plus" size="20" /></span>
                        <div class="meta">
                            <span class="dark-inverted">New Item</span>
                            <span>Add a new {{ $type }} item</span>
                        </div>
                        <div class="end">
                            <button type="button" class="v-button is-dark-outlined is-raised add-setting-item" data-profile-toggle-panel="{{ $key }}">
                                <x-vuero.icon name="plus" size="14" />
                                <span>Add</span>
                            </button>
                        </div>
                    </div>
                </div>

                <form class="setting-list setting-form" data-profile-panel="{{ $key }}" hidden>
                    <div class="field-wrap">
                        <div class="columns is-multiline">
                            <div class="column is-6"><x-vuero.forms.field><x-vuero.forms.control><x-vuero.forms.select placeholder="Select a {{ $type }}" :options="['Option 1', 'Option 2']" /></x-vuero.forms.control></x-vuero.forms.field></div>
                            <div class="column is-6"><x-vuero.forms.field><x-vuero.forms.control><x-vuero.forms.select placeholder="Select your level" :options="['Level 1', 'Level 2', 'Level 3']" /></x-vuero.forms.control></x-vuero.forms.field></div>
                            @if ($key === 'languages')
                                <div class="column is-12"><x-vuero.forms.field><x-vuero.forms.control icon="message"><input class="input" type="text" placeholder="Write a quick comment..."></x-vuero.forms.control></x-vuero.forms.field></div>
                            @endif
                            <div class="column is-12"><button type="submit" class="form-button is-primary is-fullwidth" data-profile-save>Add {{ rtrim($title, 's') }}</button></div>
                        </div>
                    </div>
                </form>
            </div>
        @endforeach
    </div>
</div>
