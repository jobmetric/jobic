@props(['context' => 'requirements'])
        <div class="reqv-help">
            <h3><span class="reqv-group__icon" aria-hidden="true"><x-vuero.icon name="life-buoy" size="24" /></span>{{ __('installation.requirements_help') }}</h3>
            <p>{{ __('installation.'.$context.'_help_hint') }}</p>
            <button type="button" class="button reqv-help__documentation" data-installation-help="{{ $context === 'database' ? 'database_documentation' : 'documentation' }}"><x-vuero.icon name="external-link" size="18" />{{ __('installation.requirements_documentation') }}</button>
            <button type="button" class="reqv-help__support" data-installation-help="{{ $context === 'database' ? 'database_support' : 'support' }}"><x-vuero.icon name="headphones" size="18" />{{ __('installation.requirements_support') }}</button>
        </div>
