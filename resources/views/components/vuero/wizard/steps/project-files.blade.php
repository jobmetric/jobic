<section class="wizard-step" data-wizard-step="4" aria-labelledby="wizard-step-4-title" hidden>
    <div class="wizard-step__content">
        <header class="wizard-step__title"><h1 id="wizard-step-4-title">Add files to this project</h1><p>Or you can skip this step. You can always add more files later.</p></header>
        <div class="wizard-placeholder wizard-files-placeholder" data-files-placeholder>
            <img src="{{ asset('vuero/images/illustrations/wizard/upload-placeholder.svg') }}" alt="">
            <h2>Upload project files</h2><p>You can already start adding files to your project if you have them handy. But don't worry, you'll be able to add and manage files later.</p>
            <button type="button" data-show-uploader>Add Files</button>
        </div>
        <div class="wizard-uploader" data-wizard-uploader hidden>
            <div class="wizard-uploader__toolbar"><div>
                <button type="button" data-add-files aria-label="Add files"><x-vuero.icon name="plus" size="18" /></button>
                <button type="button" data-upload-all aria-label="Upload all"><x-vuero.icon name="upload" size="18" /></button>
                <button type="button" data-remove-all aria-label="Remove all"><x-vuero.icon name="x" size="18" /></button>
            </div><span class="wizard-uploader__total"><i data-upload-total></i></span></div>
            <label class="wizard-uploader__dropzone" data-file-dropzone><input type="file" multiple data-project-files><x-vuero.icon name="upload" size="36" /><strong>Upload photos/videos</strong><small>Drop files here or click to browse</small></label>
            <div class="wizard-upload-list" data-upload-list></div>
        </div>
    </div>
</section>
