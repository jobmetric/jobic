<section class="wizard-step" data-wizard-step="2" aria-labelledby="wizard-step-2-title" hidden>
    <div class="wizard-step__content">
        <header class="wizard-step__title"><h1 id="wizard-step-2-title">What is this project about?</h1><p>Manage better by adding all relevant project information</p></header>
        <div class="wizard-project-info">
            <div class="wizard-logo-field">
                <label class="wizard-logo-upload" data-logo-dropzone>
                    <input type="file" accept="image/png,image/jpeg,image/gif" data-project-logo>
                    <span class="wizard-logo-upload__empty"><x-vuero.icon name="upload" size="27" /></span>
                    <img src="" alt="Project logo preview" data-project-logo-preview hidden>
                    <button type="button" data-project-logo-remove aria-label="Remove project logo" hidden><x-vuero.icon name="x" size="14" /></button>
                </label>
                <p><strong>Upload a project logo</strong><span>File size cannot exceed 2MB</span></p>
            </div>
            <div class="wizard-project-info__fields">
                <label class="wizard-field"><span class="sr-only">Project name</span><input type="text" placeholder="Project Name" maxlength="80" data-project-name></label>
                <label class="wizard-field"><span class="sr-only">Project description</span><textarea rows="4" placeholder="Describe your project..." maxlength="500" data-project-description></textarea><small data-description-help>Minimum of 50 characters</small></label>
                <div class="wizard-field"><span>Related Industries</span><div class="wizard-multiselect" data-related-select>
                    <input type="hidden" value="UI/UX Design" data-project-related>
                    <button type="button" class="wizard-multiselect__trigger" data-related-toggle aria-haspopup="listbox" aria-expanded="false"><span data-related-current>UI/UX Design</span></button>
                    <button type="button" class="wizard-multiselect__clear" data-related-clear aria-label="Clear related industry"><x-vuero.icon name="x" size="13" /></button>
                    <x-vuero.icon class="wizard-multiselect__chevron" name="chevron-right" size="15" />
                    <div class="wizard-multiselect__options" role="listbox" data-related-options hidden>
                        @foreach (['UI/UX Design', 'Web Development', 'Marketing'] as $industry)
                            <button type="button" role="option" data-related-option="{{ $industry }}">{{ $industry }}</button>
                        @endforeach
                    </div>
                </div></div>
            </div>
        </div>
    </div>
</section>
